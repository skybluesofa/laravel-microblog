<?php

namespace Skybluesofa\Microblog\Model\Scope\Post;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Skybluesofa\Microblog\Enums\Status;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Model\Contract\MicroblogJournal;
use Skybluesofa\Microblog\Model\Contract\MicroblogUser;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;

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

        if (! $currentUser || ! method_exists($currentUser, 'getBlogFriends')) {
            return $this->postsVisibleToGuest($builder);
        }

        return $this->postsVisibleToLoggedInUser($builder, $currentUser);
    }

    private function postsVisibleToGuest(Builder $builder)
    {
        return $builder->where(function ($query) {
            $query->where('available_on', '<=', Carbon::now())
                ->where(function ($q) {
                    $q->where('status', Status::PUBLISHED);
                    $q->where('visibility', Visibility::UNIVERSAL);
                });
        });
    }

    private function postsVisibleToLoggedInUser(
        Builder $builder,
        MicroblogUser $currentUser
    ) {
        return $builder->where('journal_id', $currentUser->journalId())
            ->orWhere(function ($query) use ($currentUser) {
                $query->where('available_on', '<=', Carbon::now())
                    ->where(function ($q) {
                        $q->where('status', Status::PUBLISHED);
                        $q->where('visibility', Visibility::UNIVERSAL);
                    });
                $blogFriendIds = $currentUser->getBlogFriends();
                if (! is_null($blogFriendIds)) {
                    $query->orWhere(function ($q) use ($blogFriendIds) {
                        $q->whereIn('journal_id', MicroblogJournal::whereIn('user_id', $blogFriendIds)->pluck('id'));
                        $q->where('status', Status::PUBLISHED);
                        $q->where('visibility', Visibility::SHARED);
                    });
                }
            });
    }
}
