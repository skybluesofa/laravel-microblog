<?php

namespace Skybluesofa\Microblog\Model\Traits;

trait MicroblogFriends
{
    // Return null to get all users
    //return null;

    // Return an array to get specific user ids
    // return [1,2,3];

    // Return an empty array to get no user ids
    //return [];
    abstract public function getBlogFriends(): ?array;

    abstract public function setBlogFriends(?array $blogFriends = null): self;
}
