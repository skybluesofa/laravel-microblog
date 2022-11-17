<?php

use App\User;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Model\Journal;
use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Skybluesofa\Microblog\Tests\Testcase;
use Skybluesofa\Microblog\Events\User\MicroblogUserCreated;

class MicroblogUserTest extends TestCase
{
    use MicroblogCurrentUser;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake([
            MicroblogUserCreated::class,
        ]);
    }

    public function test_microblog_user_created_event_fires()
    {
        $user = factory(User::class)->create();

        Event::assertDispatched(MicroblogUserCreated::class, 1);
    }
}
