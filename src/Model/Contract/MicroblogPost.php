<?php

namespace Skybluesofa\Microblog\Model\Contract;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Skybluesofa\Microblog\Enums\Status;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Events\Post\MicroblogPostCreated;
use Skybluesofa\Microblog\Events\Post\MicroblogPostDeleted;
use Skybluesofa\Microblog\Events\Post\MicroblogPostShared;
use Skybluesofa\Microblog\Events\Post\MicroblogPostUnshared;
use Skybluesofa\Microblog\Model\Image;
use Skybluesofa\Microblog\Model\Journal;
use Skybluesofa\Microblog\Model\PostImage;
use Skybluesofa\Microblog\Model\Scope\OrderScope;
use Skybluesofa\Microblog\Model\Scope\Post\PrivacyScope;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Webpatser\Uuid\Uuid;

abstract class MicroblogPost extends Model
{
    use MicroblogCurrentUser;

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dispatchesEvents = [
        'created' => MicroblogPostCreated::class,
        'deleted' => MicroblogPostDeleted::class,
    ];

    /**
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('microblog.tables.microblog_posts');
        $this->incrementing = $this->getIncrementing();

        parent::__construct($attributes);
    }

    /**
     * @var array
     */
    protected $ignorePrivacy = false;

    public function journal()
    {
        return $this->belongsTo(Journal::class)->first();
    }

    public function images()
    {
        return $this->belongsToMany(
            Image::class,
            PostImage::class
        );
    }

    public function user()
    {
        return $this->journal()->user()->first();
    }

    public function userName()
    {
        return $this->user()->name;
    }

    public function publish(): self
    {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::PUBLISHED;
            $this->save();
        }

        return $this;
    }

    public function unpublish(): self
    {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::DRAFT;
            $this->save();
        }

        return $this;
    }

    public function hide(): self
    {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::PERSONAL;
            $this->save();
        }

        return $this;
    }

    public function share($onlyToFriends = true): self
    {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::PUBLISHED;
            $this->visibility = $onlyToFriends ? Visibility::SHARED : Visibility::UNIVERSAL;
            $this->save();
        }

        return $this;
    }

    public function belongsToCurrentUser(): bool
    {
        if (! $this->currentUser()) {
            return false;
        }

        return $this->journal()->first()->user()->first()->id == $this->currentUser()->id;
    }

    public function getPostsVisibleTo(Model $user)
    {
        $microblogFriends = $this->getBlogFriends($user);
    }

    private function getBlogFriends(Model $user): ?array
    {
        if (! method_exists($user, 'getBlogFriends')) {
            // If the method isn't available, then simply return NULL
            return null;
        }

        $microblogFriends = $user->getBlogFriends();

        if (is_null($microblogFriends)) {
            // If it's NULL, then simply return NULL
            return null;
        } elseif (is_array($microblogFriends)) {
            // If it's an array, then we will assume that it is a simple array of user ids
            return $microblogFriends;
        } elseif ($microblogFriends instanceof Collection) {
            // If it's a collection, we will assume that it is a collection of User models
            if ($microblogFriends->count() == 0) {
                return [];
            }
            $keyName = $microblogFriends->first()->getKeyName();

            return $microblogFriends->pluck($keyName);
        }
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
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // This is necessary because on \Illuminate\Database\Eloquent\Model::performInsert
            // will not check for $this->getIncrementing() but directly for $this->incrementing
            $model->incrementing = false;
            $uuidVersion = (! empty($model->uuidVersion) ? $model->uuidVersion : 4);   // defaults to 4
            $uuid = Uuid::generate($uuidVersion);
            $model->attributes[$model->getKeyName()] = $uuid->string;

            //$model->attributes['journal_id'] = MicroblogJournal::forUser(Auth::user())->first()->id;
            $model->attributes['status'] = isset($model->attributes['status'])
                ? $model->attributes['status']
                : Status::PUBLISHED;
            $model->attributes['visibility'] = isset($model->attributes['visibility'])
                ? $model->attributes['visibility']
                : Visibility::PERSONAL;
            $model->attributes['available_on'] = isset($model->attributes['available_on'])
                ? $model->attributes['available_on']
                : date('Y-m-d H:i:s');
        }, 0);

        static::updated(function ($post) {
            if (!$post->originalIsEquivalent('visibility')) {
                if ($post->visibility == Visibility::PERSONAL) {
                    MicroblogPostUnshared::dispatch($post);
                } elseif ($post->getOriginal('visibility') == Visibility::PERSONAL) {
                    MicroblogPostShared::dispatch($post);
                }
            }
        }, 0);

        static::addGlobalScope(new PrivacyScope);
        static::addGlobalScope(new OrderScope('available_on', 'desc'));
    }

    /**
     * @param $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUserIdIs($query, $userId): Builder
    {
        return $query->whereIn('journal_id', Journal::where('user_id', $userId)->pluck('id'));
    }

    /**
     * @param $query
     * @param  array  $userIds
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUserIdIn($query, $userIds): Builder
    {
        return $query->whereIn('journal_id', Journal::whereIn('user_id', $userIds)->pluck('id'));
    }

    /**
     * @param $query
     * @param  string  $journalId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereJournalIdIs($query, string $journalId): Builder
    {
        return $query->where('journal_id', $journalId);
    }

    /**
     * @param $query
     * @param  Carbon  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThan($query, Carbon $date): Builder
    {
        return $query->where('available_on', '<', $date);
    }

    /**
     * @param $query
     * @param  MicroblogPost  $microblogPost
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThanPost($query, MicroblogPost $microblogPost)
    {
        return $this->scopeWhereOlderThan($query, Carbon::parse($microblogPost->available_on));
    }

    /**
     * @param $query
     * @param  string  $microblogPostId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThanPostId($query, string $microblogPostId)
    {
        $post = MicroblogPost::find($microblogPostId);

        return $this->scopeWhereOlderThanPost($query, $post);
    }

    /**
     * @param $query
     * @param  Carbon  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThan($query, Carbon $date): Builder
    {
        return $query->where('available_on', '>', $date);
    }

    /**
     * @param $query
     * @param  MicroblogPost  $microblogPost
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThanPost($query, MicroblogPost $microblogPost): Builder
    {
        return $this->scopeWhereNewerThan($query, Carbon::parse($microblogPost->available_on));
    }

    /**
     * @param $query
     * @param  string  $microblogPostId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThanPostId($query, string $microblogPostId): Builder
    {
        $post = MicroblogPost::find($microblogPostId);

        return $this->scopeWhereNewerThanPost($query, $post);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePublished($query): Builder
    {
        return $query->where('status', Status::PUBLISHED);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUnpublished($query): Builder
    {
        return $query->where('status', Status::DRAFT);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePersonal($query): Builder
    {
        return $query->where('visibility', Visibility::PERSONAL);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereShared($query): Builder
    {
        return $query->where('visibility', Visibility::SHARED);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOnlySharedWithFriends($query): Builder
    {
        return $this->scopeWhereShared($query);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePublic($query): Builder
    {
        return $query->where('visibility', Visibility::UNIVERSAL);
    }
}
