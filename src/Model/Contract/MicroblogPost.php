<?php
namespace Skybluesofa\Microblog\Model\Contract;

use Skybluesofa\Microblog\Status;
use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Skybluesofa\Microblog\Model\Scope\Post\PrivacyScope;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Webpatser\Uuid\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Skybluesofa\Microblog\Model\Scope\OrderScope;
use Skybluesofa\Microblog\Model\Journal;

/**
 * Class MicroblogPost
 * @package Skybluesofa\StatusPosts\Models
 */
abstract class MicroblogPost extends Model
{
    use MicroblogCurrentUser;

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var array
     */
    protected $ignorePrivacy = false;

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function publish() : bool
    {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::PUBLISHED;
            return $this->save();
        }
        return false;
    }

    public function unpublish() : bool
    {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::DRAFT;
            return $this->save();
        }
        return false;
    }

    public function hide() : bool
    {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::PERSONAL;
            return $this->save();
        }
        return false;
    }

    public function share($onlyToFriends = true) : bool
    {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::PUBLISHED;
            $this->visibility = $onlyToFriends ? Visibility::SHARED : Visibility::UNIVERSAL;
            return $this->save();
        }
        return false;
    }

    public function belongsToCurrentUser() : bool
    {
        return $this->journal()->first()->user()->first()->id == $this->currentUser()->id;
    }

    public function getPostsVisibleTo(Model $user)
    {
        $microblogFriends = $this->getBlogFriends($user);
    }

    private function getBlogFriends(Model $user) : ?Array
    {
        if (!method_exists($user, 'getBlogFriends')) {
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
            if ($microblogFriends->count()==0) {
                return [];
            }
            $keyName = $microblogFriends->first()->getKeyName();
            return $microblogFriends->pluck($keyName);
        }
    }

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->table = config('microblog.tables.microblog_posts');
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
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // This is necessary because on \Illuminate\Database\Eloquent\Model::performInsert
            // will not check for $this->getIncrementing() but directly for $this->incrementing
            $model->incrementing = false;
            $uuidVersion = (!empty($model->uuidVersion) ? $model->uuidVersion : 4);   // defaults to 4
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

        static::addGlobalScope(new PrivacyScope);
        static::addGlobalScope(new OrderScope('available_on'));
    }

    /**
     * @param $query
     * @param Int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUserIdIs($query, $userId) : Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param $query
     * @param Model $collection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUserIdIn($query, $collection) : Builder
    {
        return $query->whereIn('user_id', $collection->toArray());
    }

    /**
     * @param $query
     * @param Uuid $journalId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereJournalIdIs($query, Uuid $journalId) : Builder
    {
        return $query->where('journal_id', $journalId);
    }

    /**
     * @param $query
     * @param Carbon $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThan($query, Carbon $date) : Builder
    {
        return $query->where('available_on', '<', $date);
    }

    /**
     * @param $query
     * @param Uuid $microblogPostId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThanPost($query, Uuid $microblogPostId)
    {
        $post = MicroblogPost::find($microblogPostId);
        return $this->scopeWhereOlderThan($query, Carbon::parse($post->available_on));
    }

    /**
     * @param $query
     * @param Carbon $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThan($query, Carbon $date) : Builder
    {
        return $query->where('available_on', '>', $date);
    }

    /**
     * @param $query
     * @param Uuid $microblogPostId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThanPost($query, Uuid $microblogPostId) : Builder
    {
        $post = MicroblogPost::find($microblogPostId);
        return $this->scopeWhereNewerThan($query, Carbon::parse($post->available_on));
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePublished($query) : Builder
    {
        return $query->where('status', Status::PUBLISHED);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUnpublished($query) : Builder
    {
        return $query->where('status', Status::DRAFT);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePersonal($query) : Builder
    {
        return $query->where('visibility', Visibility::PERSONAL);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereShared($query) : Builder
    {
        return $query->where('visibility', Visibility::SHARED);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOnlySharedWithFriends($query) : Builder
    {
        return $query->where('visibility', Visibility::SHARED);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePublic($query) : Builder
    {
        return $query->where('visibility', Visibility::UNIVERSAL);
    }
}
