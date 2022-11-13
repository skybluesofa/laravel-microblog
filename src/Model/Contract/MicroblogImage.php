<?php

namespace Skybluesofa\Microblog\Model\Contract;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Model\Journal;
use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\PostImage;
use Skybluesofa\Microblog\Model\Scope\Image\PrivacyScope;
use Skybluesofa\Microblog\Model\Scope\OrderScope;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Webpatser\Uuid\Uuid;

abstract class MicroblogImage extends Model
{
    use MicroblogCurrentUser;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected bool $ignorePrivacy = false;

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
        return $this->journal()->user()->first();
    }

    public function userName(): string
    {
        return $this->user()->name;
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
            $this->visibility = $onlyToFriends ? Visibility::SHARED : Visibility::UNIVERSAL;
            $this->save();
        }

        return $this;
    }

    public function belongsToCurrentUser(): bool
    {
        return $this->journal()->first()->user()->first()->id == $this->currentUser()->id;
    }

    /**
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('microblog.tables.microblog_images');
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
            $model->attributes['visibility'] = isset($model->attributes['visibility'])
                ? $model->attributes['visibility']
                : Visibility::PERSONAL;
        }, 0);

        static::addGlobalScope(new PrivacyScope);
        static::addGlobalScope(new OrderScope('created_at', 'desc'));
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
        return $query->where('created_at', '<', $date);
    }

    /**
     * @param $query
     * @param  MicroblogImage  $microblogImage
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThanImage($query, MicroblogImage $microblogImage)
    {
        return $this->scopeWhereOlderThan($query, Carbon::parse($microblogImage->created_at));
    }

    /**
     * @param $query
     * @param  string  $microblogImageId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereOlderThanImageId($query, string $microblogImageId)
    {
        return $this->scopeWhereOlderThanImage($query, MicroblogImage::find($microblogImageId));
    }

    /**
     * @param $query
     * @param  Carbon  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThan($query, Carbon $date): Builder
    {
        return $query->where('created_at', '>', $date);
    }

    /**
     * @param $query
     * @param  MicroblogImage  $microblogImage
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThanImage($query, MicroblogImage $microblogImage): Builder
    {
        return $this->scopeWhereNewerThan($query, Carbon::parse($microblogImage->created_at));
    }

    /**
     * @param $query
     * @param  string  $microblogImageId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereNewerThanImageId($query, string $microblogImageId): Builder
    {
        return $this->scopeWhereNewerThanImage($query, MicroblogImage::find($microblogImageId));
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
