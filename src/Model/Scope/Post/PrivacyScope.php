<?php
namespace Skybluesofa\Microblog\Model\Scope\Post;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Skybluesofa\Microblog\Model\Contract\MicroblogJournal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Skybluesofa\Microblog\Status;
use Skybluesofa\Microblog\Visibility;

class PrivacyScope implements Scope
{
    use MicroblogCurrentUser;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $currentUser = $this->currentUser();

        return $builder->where(function ($query) use ($currentUser) {
            if ($currentUser) {
                $query->where('journal_id', $currentUser->journalId());
            } else {
                $query->where('journal_id', null);
            }
        })->orWhere(function ($query) use ($currentUser) {
            $query
                ->where('available_on', '<=', Carbon::now())
                ->where(function ($q) {
                    $q->where('status', Status::PUBLISHED);
                    $q->where('visibility', Visibility::UNIVERSAL);
                });
            if ($currentUser && method_exists($currentUser, 'getBlogFriends')) {
                $blogFriendIds = $currentUser->getBlogFriends();
                if (!is_null($blogFriendIds)) {
                    $query->orWhere(function ($q) use ($blogFriendIds) {
                        $q->whereIn('journal_id', MicroblogJournal::whereIn('user_id', $blogFriendIds)->pluck('id'));
                        $q->where('status', Status::PUBLISHED);
                        $q->where('visibility', Visibility::SHARED);
                    });
                }
            }
        });
    }
}
