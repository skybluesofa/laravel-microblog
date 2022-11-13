<?php

namespace Skybluesofa\Microblog\Model\Traits;

trait MicroblogFriendsEveryone
{
    public function getBlogFriends(): ?array
    {
        // Return null to get all users
        return null;
    }
}
