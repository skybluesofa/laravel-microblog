<?php
namespace Skybluesofa\Microblog\Model\Traits;

/**
 * Class MicroblogFriendsNone
 * @package Skybluesofa\Microblog\Traits
 *
 * Adding this trait to User models will effectively disable the ability to view
 * other User blog posts.
 *
 * If you wish to implement your own getBlogFriends() method for your special use
 * case, you can apply the \Skybluesofa\Microblog\Traits\MicroblogFriends trait
 * to the User model and then implement the getBlogFriends() method yourself.
 */
trait MicroblogFriendsNone
{
    public function getBlogFriends() : ?Array
    {
        // Return an empty array to get no user ids
        return [];
    }
}
