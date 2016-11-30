<?php
namespace Skybluesofa\Microblog\Model\Scope\Journal;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Auth;

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
            ->where('user_id', Auth::user()->id)
            ->orWhere(function($q) {
              if (method_exists(Auth::user(), 'getBlogFriends')) {
                if (!is_null($blogFriendIds = Auth::user()->getBlogFriends())) {
                    $q->whereIn('user_id', $blogFriendIds);
                }
              }
              $q->where('visibility', 1);
            })
            ->orWhere(function($q) {
                $q->where('visibility', 2);
            });
    }
}
