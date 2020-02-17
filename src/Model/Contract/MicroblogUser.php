<?php
namespace Skybluesofa\Microblog\Model\Contract;

use Illuminate\Foundation\Auth\User;
use Skybluesofa\Microblog\Model\Traits\MicroblogAuthor;
use Skybluesofa\Microblog\Model\Traits\MicroblogFriends;

/**
 * Class MicroblogPost
 * @package Skybluesofa\StatusPosts\Models
 */
abstract class MicroblogUser extends User
{
    use MicroblogAuthor;
}
