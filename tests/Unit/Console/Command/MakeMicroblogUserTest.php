<?php

use App\User;
use Skybluesofa\Microblog\Console\Commands\MakeMicroblogUser;
use Skybluesofa\Microblog\Events\User\MicroblogUserCreated;
use Skybluesofa\Microblog\Tests\Testcase;

class MakeMicroblogUserTest extends TestCase
{
    public function test_user_create_artisan_command()
    {
        Event::fake();

        $this->artisan('make:microblog-user "John Mark" Doe someone@example.com password')
            ->expectsOutput(MakeMicroblogUser::USER_CREATED_SUCCESSFULLY)
            ->assertSuccessful();

        $this->assertCount(1, User::where('email', 'someone@example.com')->get());

        Event::assertDispatched(MicroblogUserCreated::class, 1);
    }

    public function test_user_create_artisan_command_fails_because_user_exists()
    {
        Event::fake();

        $this->artisan('make:microblog-user John Doe someone@example.com password')
            ->expectsOutput(MakeMicroblogUser::USER_CREATED_SUCCESSFULLY)
            ->assertSuccessful();

        $this->artisan('make:microblog-user Jim Doe someone@example.com password')
            ->expectsOutput(MakeMicroblogUser::USER_EXISTS)
            ->assertFailed();

        $this->assertCount(1, User::where('email', 'someone@example.com')->get());

        Event::assertDispatched(MicroblogUserCreated::class, 1);
    }
}
