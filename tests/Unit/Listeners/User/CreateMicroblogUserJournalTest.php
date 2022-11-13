<?php

use App\User;
use Skybluesofa\Microblog\Events\User\MicroblogUserCreated;
use Skybluesofa\Microblog\Listeners\User\CreateMicroblogUserJournal;
use Skybluesofa\Microblog\Model\Journal;
use Skybluesofa\Microblog\Tests\Testcase;

class CreateMicroblogUserJournalTest extends TestCase
{
    public function test_is_attached_to_event()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );

        // TODO: Get the event listener working at the package level.
        // Because of a hack in the MicroblogServiceProvider::activateListeners() method,
        //  it DOES work at the application level, however.
        Event::fake();
        Event::assertListening(MicroblogUserCreated::class, CreateMicroblogUserJournal::class);
    }

    public function test_it_creates_journal()
    {
        $user = factory(User::class)->create();

        $event = new MicroblogUserCreated($user);
        $listener = new CreateMicroblogUserJournal();
        $listener->handle($event);

        $this->assertCount(1, Journal::where('user_id', $user->id)->get());
    }
}
