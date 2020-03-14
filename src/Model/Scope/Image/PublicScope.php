<?php
namespace Skybluesofa\Microblog\Model\Scope\Image;

use App\User;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Skybluesofa\Microblog\Model\Contract\MicroblogJournal;
use Carbon\Carbon;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Status;

class PublicScope implements Scope
{
    use MicroblogCurrentUser;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $currentUser = $this->currentUser();

        if ($currentUser) {
            return $this->forLoggedInUser($builder, $currentUser);
        }

        return $this->forGuest($builder);
    }

    private function forGuest(Builder $builder)
    {
        return $builder
            ->orWhere(function ($query) {
                $query
                    ->where('available_on', '<=', Carbon::now())
                    ->where(function ($q) {
                        $q->where('visibility', Visibility::UNIVERSAL);
                    });
            });
    }

    private function forLoggedInUser(Builder $builder, User $currentUser)
    {
        return $builder->orWhere(function ($query) use ($currentUser) {
            $query->where('journal_id', $currentUser->journalId());
        })->orWhere(function ($query) use ($currentUser) {
            $query
                ->where('available_on', '<=', Carbon::now())
                ->where(function ($q) {
                    $q->where('visibility', Visibility::UNIVERSAL);
                });
            if ($currentUser && method_exists($currentUser, 'getBlogFriends')) {
                $blogFriendIds = $currentUser->getBlogFriends();
                if (!is_null($blogFriendIds)) {
                    $query->orWhere(function ($q) use ($blogFriendIds) {
                        $q->whereIn('journal_id', MicroblogJournal::whereIn('user_id', $blogFriendIds)->pluck('id'));
                        $q->where('visibility', Visibility::SHARED);
                    });
                }
            }
        });
    }
}
