<?php
namespace Skybluesofa\Microblog\Model\Traits;

use Auth;
use Illuminate\Foundation\Auth\User;

/**
 * Class MicroblogAuthor
 * @package Skybluesofa\Microblog\Traits
 *
 * Applying this trait to a User model will allow you to implement your own
 * getBlogFriends() method for your special use case.
 */
trait MicroblogCurrentUser
{
    public function currentUser() : ?User
    {
        return Auth::user();
    }
}
