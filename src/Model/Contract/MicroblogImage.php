<?php
namespace Skybluesofa\Microblog\Model\Contract;

use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Status;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Webpatser\Uuid\Uuid;
use Skybluesofa\Microblog\Model\Scope\OrderScope;
use Skybluesofa\Microblog\Model\Journal;
use Skybluesofa\Microblog\Model\PostImage;
use Skybluesofa\Microblog\Model\Post;

/**
 * Class MicroblogImage
 * @package Skybluesofa\Models\Contract
 */
abstract class MicroblogImage extends Model
{
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
        return $this->belongsTo(Journal::class)->first();
    }

    public function posts()
    {
        return $this->belongsToMany(
            Post::class,
            PostImage::class
        );
    }

    public function user()
    {
        if (empty($this->journal())) {
            return json_decode('{"name":"John Doe"}');
        }
        return $this->journal()->user()->first();
    }

    public function userName() : string
    {
        return $this->user()->name;
    }

    public function hide() : self
    {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::PERSONAL;
            $this->save();
        }

        return $this;
    }

    public function share($onlyToFriends = true) : self
    {
        if ($this->belongsToCurrentUser()) {
            $this->status = Status::PUBLISHED;
            $this->visibility = $onlyToFriends ? Visibility::SHARED : Visibility::UNIVERSAL;
            $this->save();
        }

        return $this;
    }

    public function belongsToCurrentUser() : bool
    {
        return $this->journal()->first()->user()->first()->id == $this->currentUser()->id;
    }

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->table = config('microblog.tables.microblog_images');
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
            $model->attributes['visibility'] = isset($model->attributes['visibility'])
                ? $model->attributes['visibility']
                : Visibility::PERSONAL;
        }, 0);

        static::addGlobalScope(new OrderScope('created_at', 'desc'));
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
        return $query->where('created_at', '<', $date);
    }

    /**
     * @param $query
     * @param Uuid $microblogImageId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThanImage($query, Uuid $microblogImageId)
    {
        $image = MicroblogImage::find($microblogImageId);
        return $this->scopeWhereOlderThan($query, Carbon::parse($image->created_at));
    }

    /**
     * @param $query
     * @param Carbon $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThan($query, Carbon $date) : Builder
    {
        return $query->where('created_at', '>', $date);
    }

    /**
     * @param $query
     * @param Uuid $microblogImageId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThanImage($query, Uuid $microblogImageId) : Builder
    {
        $image = MicroblogImage::find($microblogImageId);
        return $this->scopeWhereNewerThan($query, Carbon::parse($image->created_at));
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
