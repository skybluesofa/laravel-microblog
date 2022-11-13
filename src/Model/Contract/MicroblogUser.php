<?php

namespace Skybluesofa\Microblog\Model\Contract;

use Illuminate\Foundation\Auth\User;
use Skybluesofa\Microblog\Model\Traits\MicroblogAuthor;

abstract class MicroblogUser extends User
{
    use MicroblogAuthor;
}
