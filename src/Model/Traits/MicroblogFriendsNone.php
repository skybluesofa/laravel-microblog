<?php

namespace Skybluesofa\Microblog\Model\Traits;

trait MicroblogFriendsNone
{
    public function getBlogFriends(): ?array
    {
        // Return an empty array to get no user ids
        return [];
    }
}
