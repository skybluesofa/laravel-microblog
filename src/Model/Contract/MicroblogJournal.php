<?php

namespace Skybluesofa\Microblog\Model\Contract;

use Illuminate\Database\Eloquent\Model;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Events\Journal\MicroblogJournalCreated;
use Skybluesofa\Microblog\Events\Journal\MicroblogJournalShared;
use Skybluesofa\Microblog\Events\Journal\MicroblogJournalUnshared;
use Skybluesofa\Microblog\Model\Image;
use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\Scope\Journal\PrivacyScope as JournalPrivacyScope;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Skybluesofa\Microblog\Model\User;
use Webpatser\Uuid\Uuid;

class MicroblogJournal extends Model
{
    use MicroblogCurrentUser;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dispatchesEvents = [
        'created' => MicroblogJournalCreated::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->first();
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'journal_id')->orderBy('available_on');
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'journal_id')->orderBy('created_on');
    }

    public static function forUser(Model $user): MicroblogJournal
    {
        return static::getOrCreate($user);
    }

    public static function getOrCreate(Model $user): MicroblogJournal
    {
        $microblogJournal = MicroblogJournal::withoutGlobalScope(JournalPrivacyScope::class)
            ->where('user_id', $user->id);

        if (! $microblogJournal->count()) {
            $microblogJournal = new MicroblogJournal;
            $microblogJournal->user_id = $user->id;
            $microblogJournal->save();
        } else {
            $microblogJournal = $microblogJournal->get()->first();
        }

        return $microblogJournal;
    }

    public function hide(): self
    {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::PERSONAL;
            $this->save();
        }

        return $this;
    }

    public function shareWithFriends(): self
    {
        return $this->share();
    }

    public function shareWithEveryone(): self
    {
        return $this->share(false);
    }

    public function share($onlyToFriends = true): self
    {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = $onlyToFriends ? Visibility::SHARED : Visibility::UNIVERSAL;
            $this->save();
        }

        return $this;
    }

    public function belongsToCurrentUser(): bool
    {
        $currentUser = $this->currentUser();

        if ($currentUser) {
            return $this->user_id == $currentUser->id;
        }

        return false;
    }

    /**
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('microblog.tables.microblog_journals');
        $this->incrementing = $this->getIncrementing();

        parent::__construct($attributes);
    }

    public function getIncrementing(): bool
    {
        // We use a UUID, so the model key is not going to increment automatically
        return false;
    }

    /**
     * This function overwrites the default boot static method of Eloquent models. It will hook
     * the creation event with a simple closure to insert the UUID
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($journal) {
            // This is necessary because on \Illuminate\Database\Eloquent\Model::performInsert
            // will not check for $this->getIncrementing() but directly for $this->incrementing
            $journal->incrementing = false;
            $uuidVersion = (! empty($journal->uuidVersion) ? $journal->uuidVersion : 4);   // defaults to 4
            $uuid = Uuid::generate($uuidVersion);
            $journal->attributes[$journal->getKeyName()] = $uuid->string;

            $journal->attributes['visibility'] = isset($journal->attributes['visibility'])
                ? $journal->attributes['visibility']
                : Visibility::SHARED;
        }, 0);

        static::updated(function ($journal) {
            if (!$journal->originalIsEquivalent('visibility')) {
                if ($journal->visibility == Visibility::PERSONAL) {
                    MicroblogJournalUnshared::dispatch($journal);
                } elseif ($journal->getOriginal('visibility') == Visibility::PERSONAL) {
                    MicroblogJournalShared::dispatch($journal);
                }
            }
        }, 0);

        static::addGlobalScope(new JournalPrivacyScope);
    }
}
