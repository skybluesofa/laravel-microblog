<?php

namespace Skybluesofa\Microblog\Events\User;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skybluesofa\Microblog\Model\Contract\MicroblogUser;

class MicroblogUserCreated implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public MicroblogUser $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(MicroblogUser $user)
    {
        $this->user = $user;
    }
}
