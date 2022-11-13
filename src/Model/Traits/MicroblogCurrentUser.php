<?php

namespace Skybluesofa\Microblog\Model\Traits;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;

trait MicroblogCurrentUser
{
    public function currentUser(): ?User
    {
        return Auth::user();
    }
}
