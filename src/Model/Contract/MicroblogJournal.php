<?php
namespace Skybluesofa\Microblog\Model\Contract;

use Skybluesofa\Microblog\Status;
use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Model\Scope\Journal\PrivacyScope as JournalPrivacyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Webpatser\Uuid\Uuid;
use Auth;

/**
 * Class MicroblogJournal
 * @package Skybluesofa\Microblog\Model\Contract\MicroblogJournal
 */
class MicroblogJournal extends Model
{
    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function posts() {
        return $this->hasMany('Skybluesofa\Microblog\Model\Post');
    }

    public static function forUser(Model $user) {
        return MicroblogJournal::where('user_id', $user->id);
    }

    public static function getOrCreate(Model $user) {
        $microblogJournal = MicroblogJournal::withoutGlobalScope(JournalPrivacyScope::class)->where('user_id', $user->id);
        if (!$microblogJournal->count()) {
            $microblogJournal = new MicroblogJournal;
            $microblogJournal->user_id = $user->id;
            $microblogJournal->save();
        } else {
            $microblogJournal = $microblogJournal->get();
        };
        return $microblogJournal;
    }

    public function hide() {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::PERSONAL;
            $this->save();
        }
    }

    public function shareWithFriends() {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::SHARED;
            $this->save();
        }
    }

    public function shareWithEveryone() {
        if ($this->belongsToCurrentUser()) {
            $this->visibility = Visibility::UNIVERSAL;
            $this->save();
        }
    }

    public function belongsToCurrentUser() {
        return $this->user_id == Auth::user()->id;
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

            $model->attributes['visibility'] = isset($model->attributes['visibility']) ? $model->attributes['visibility'] : Visibility::UNIVERSAL;
        }, 0);

        static::addGlobalScope(new JournalPrivacyScope);
    }
}
