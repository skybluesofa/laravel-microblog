<?php
namespace Skybluesofa\Microblog\Model\Contract;

use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Skybluesofa\Microblog\Model\Scope\Journal\PrivacyScope as JournalPrivacyScope;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;
use Skybluesofa\Microblog\Model\User;
use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\Image;

/**
 * Class MicroblogJournal
 * @package Skybluesofa\Microblog\Model\Contract\MicroblogJournal
 */
class MicroblogJournal extends Model
{
    use MicroblogCurrentUser;

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

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

    public static function forUser(Model $user) : MicroblogJournal
    {
        return static::getOrCreate($user);
    }

    public static function getOrCreate(Model $user) : MicroblogJournal
    {
        $microblogJournal = MicroblogJournal::withoutGlobalScope(JournalPrivacyScope::class)
            ->where('user_id', $user->id);

        if (!$microblogJournal->count()) {
            $microblogJournal = new MicroblogJournal;
            $microblogJournal->user_id = $user->id;
            $microblogJournal->save();
        } else {
            $microblogJournal = $microblogJournal->get()->first();
        };

        return $microblogJournal;
    }

    public function hide() : self
    {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::PERSONAL;
            $this->save();
        }

        return $this;
    }

    public function shareWithFriends() : self
    {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::SHARED;
            $this->save();
        }

        return $this;
    }

    public function shareWithEveryone() : self
    {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::UNIVERSAL;
            $this->save();
        }

        return $this;
    }

    public function belongsToCurrentUser() : bool
    {
        $currentUser = $this->currentUser();

        if ($currentUser) {
            return $this->user_id == $currentUser->id;
        }
        
        return false;
    }

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->table = config('microblog.tables.microblog_journals');
        $this->incrementing = $this->getIncrementing();

        parent::__construct($attributes);
    }

    public function getIncrementing() : bool
    {
        // We use a UUID, so the model key is not going to increment automatically
        return false;
    }

    /**
     * This function overwrites the default boot static method of Eloquent models. It will hook
     * the creation event with a simple closure to insert the UUID
     */
    public static function boot() : void
    {
        parent::boot();

        static::creating(function ($model) {
            // This is necessary because on \Illuminate\Database\Eloquent\Model::performInsert
            // will not check for $this->getIncrementing() but directly for $this->incrementing
            $model->incrementing = false;
            $uuidVersion = (!empty($model->uuidVersion) ? $model->uuidVersion : 4);   // defaults to 4
            $uuid = Uuid::generate($uuidVersion);
            $model->attributes[$model->getKeyName()] = $uuid->string;

            $model->attributes['visibility'] = isset($model->attributes['visibility'])
                ? $model->attributes['visibility']
                : Visibility::SHARED;
        }, 0);

        static::addGlobalScope(new JournalPrivacyScope);
    }
}
