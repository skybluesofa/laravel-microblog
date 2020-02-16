<?php
namespace Skybluesofa\Microblog\Model\Traits;

/**
 * Class MicroblogFriendsEveryone
 * @package Skybluesofa\Microblog\Traits
 *
 * Adding this trait to User models will effectively allow everyone to see
 * everyone elses blog posts.
 *
 * If you wish to implement your own getBlogFriends() method for your special use
 * case, you can apply the \Skybluesofa\Microblog\Traits\MicroblogFriends Traits
 * to the User model and then implement the getBlogFriends() method yourself.
 */
trait MicroblogFriendsEveryone
{
    public function getBlogFriends() : ?Array
    {
        // Return null to get all users
        return null;
    }
}
