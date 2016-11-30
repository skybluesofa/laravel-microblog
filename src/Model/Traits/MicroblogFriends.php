<?php
namespace Skybluesofa\Microblog\Model\Traits;

/**
 * Class BlogFriends
 * @package Skybluesofa\Microblog\Traits
 *
 * Applying this trait to a User model will allow you to implement your own
 * getBlogFriends() method for your special use case.
 */
trait MicroblogFriends
{
    // Return null to get all users
    //return null;

    // Return an array to get specific user ids
    // return [1,2,3];

    // Return an empty array to get no user ids
    //return [];
    abstract function getBlogFriends();
}
