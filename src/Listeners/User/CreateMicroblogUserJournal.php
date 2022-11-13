<?php

namespace Skybluesofa\Microblog\Listeners\User;

use Skybluesofa\Microblog\Events\User\MicroblogUserCreated;

class CreateMicroblogUserJournal
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\User\MicroblogUserCreated  $event
     * @return void
     */
    public function handle(MicroblogUserCreated $event)
    {
        $user = $event->user;

        $user->journal();
    }
}
