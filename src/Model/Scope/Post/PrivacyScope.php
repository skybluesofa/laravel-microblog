<?php
namespace Skybluesofa\Microblog\Model\Scope\Post;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Auth;
use Skybluesofa\Microblog\Model\Contract\MicroblogJournal;
use Carbon\Carbon;

class PrivacyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {

        return $builder
            ->where('journal_id', Auth::user()->journalId())
            ->orWhere(function($query) {
                $query
                    ->where('available_on','<=',Carbon::now())
                    ->where(function($q) {
                        $q->where('status', 1);
                        $q->where('visibility', 2);
                    });
                if (method_exists(Auth::user(), 'getBlogFriends')) {
                  if (!is_null($blogFriendIds = Auth::user()->getBlogFriends())) {
                      $query->orWhere(function($q) use ($blogFriendIds) {
                          $q->whereIn('journal_id', MicroblogJournal::whereIn('user_id', $blogFriendIds)->pluck('id'));
                          $q->where('status', 1);
                          $q->where('visibility', 1);
                      });
                  }
                }
            });
    }
}
