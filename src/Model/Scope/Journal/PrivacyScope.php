<?php
namespace Skybluesofa\Microblog\Model\Scope\Journal;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
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

        if (!$currentUser) {
            return $this->journalsVisibleToGuest($builder);
        }

        return $this->journalsVisibleToLoggedInUser($builder, $currentUser);
    }

    private function journalsVisibleToGuest(Builder $builder)
    {
        return $builder
            ->orWhere(function ($q) {
                $q->where('visibility', Visibility::UNIVERSAL);
            });
    }

    private function journalsVisibleToLoggedInUser(
        Builder $builder,
        User $currentUser
    ) {
        return $builder->where('user_id', $currentUser->id)
            ->orWhere(function ($q) use ($currentUser) {
                if ($currentUser && method_exists($currentUser, 'getBlogFriends')) {
                    $blogFriendIds = $currentUser->getBlogFriends();
                    if (!is_null($blogFriendIds)) {
                        $q->whereIn('user_id', $blogFriendIds);
                    }
                }
                $q->where('visibility', Visibility::SHARED);
            })
            ->orWhere(function ($q) {
                $q->where('visibility', Visibility::UNIVERSAL);
            });
    }
}
