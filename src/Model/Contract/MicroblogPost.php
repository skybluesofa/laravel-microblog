<?php
namespace Skybluesofa\Microblog\Model\Contract;

use Skybluesofa\Microblog\Status;
use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Model\Journal;
use Skybluesofa\Microblog\Model\Scope\Post\PrivacyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Webpatser\Uuid\Uuid;
use Auth;

/**
 * Class MicroblogPost
 * @package Skybluesofa\StatusPosts\Models
 */
abstract class MicroblogPost extends Model
{
    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var array
     */
    protected $ignorePrivacy = false;

    public function journal() {
        return $this->belongsTo('Skybluesofa\Microblog\Model\Journal');
    }

    public function publish() {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::PUBLISHED;
            return $this->save();
        }
        return false;
    }

    public function unpublish() {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::DRAFT;
            return $this->save();
        }
        return false;
    }

    public function hide() {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::PERSONAL;
            return $this->save();
        }
        return false;
    }

    public function share($onlyToFriends = true) {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::PUBLISHED;
            $this->visibility = $onlyToFriends ? Visibility::SHARED : Visibility::UNIVERSAL;
            return $this->save();
        }
        return false;
    }

    public function belongsToCurrentUser() {
        return $this->journal()->first()->user()->first()->id == Auth::user()->id;
    }

    public function getPostsVisibleTo(Model $user) {
        $microblogFriends = $this->getBlogFriends($user);
    }

    private function getBlogFriends(Model $user) {
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
        } elseif ($microblogFriends instanceof Illuminate\Database\Eloquent\Collection) {
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

    public function getIncrementing()
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
            $model->attributes['status'] = isset($model->attributes['status']) ? $model->attributes['status'] : Status::PUBLISHED;
            $model->attributes['visibility'] = isset($model->attributes['visibility']) ? $model->attributes['visibility'] : Visibility::PERSONAL;
            $model->attributes['available_on'] = isset($model->attributes['available_on']) ? $model->attributes['available_on'] : date('Y-m-d H:i:s');
        }, 0);

        static::addGlobalScope(new PrivacyScope);
    }

    /**
     * @param $query
     * @param Int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUserIdIs($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param $query
     * @param Model $collection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUserIdIn($query, $collection)
    {
        return $query->whereIn('user_id', $collection->toArray());
    }

    /**
     * @param $query
     * @param Model $collection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThan($query, Carbon $date)
    {
        return $query->where('available_on', '<', $date);
    }

    /**
     * @param $query
     * @param Model $collection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThanPost($query, $microblogPostId)
    {
        $post = MicroblogPost::find($microblogPostId);
        return $this->scopeWhereOlderThan($query, Carbon::parse($post->available_on));
    }

    /**
     * @param $query
     * @param Model $collection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThan($query, Carbon $date)
    {
        return $query->where('available_on', '>', $date);
    }

    /**
     * @param $query
     * @param Model $collection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThanPost($query, $microblogPostId)
    {
        $post = MicroblogPost::find($microblogPostId);
        return $this->scopeWhereNewerThan($query, Carbon::parse($post->available_on));
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePublished($query)
    {
        return $query->where('status', Status::PUBLISHED);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUnpublished($query)
    {
        return $query->where('status', Status::DRAFT);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePersonal($query)
    {
        return $query->where('visibility', Visibility::PERSONAL);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereShared($query)
    {
        return $query->where('visibility', Visibility::SHARED);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOnlySharedWithFriends($query)
    {
        return $query->where('visibility', Visibility::SHARED);
    }

    /**
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePublic($query)
    {
        return $query->where('visibility', Visibility::UNIVERSAL);
    }
}
