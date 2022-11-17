<?php

use App\User;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Events\Journal\MicroblogJournalCreated;
use Skybluesofa\Microblog\Events\Journal\MicroblogJournalShared;
use Skybluesofa\Microblog\Events\Journal\MicroblogJournalUnshared;
use Skybluesofa\Microblog\Model\Journal;
use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Skybluesofa\Microblog\Tests\Testcase;

class MicroblogJournalTest extends TestCase
{
    use MicroblogCurrentUser;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake([
            MicroblogJournalCreated::class,
            MicroblogJournalShared::class,
            MicroblogJournalUnshared::class,
        ]);
    }

    public function test_create_a_journal_if_one_does_not_exist_for_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        Journal::forUser($user);

        $this->assertCount(1, Journal::where('user_id', $user->id)->pluck('id'));

        Event::assertDispatched(MicroblogJournalCreated::class, 1);
        Event::assertDispatched(MicroblogJournalShared::class, 0);
        Event::assertDispatched(MicroblogJournalUnshared::class, 0);
    }

    public function test_create_a_journal_on_first_post_creation_if_one_does_not_exist_for_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $postCount = 2;
        $testPostCount = $postCount;
        while ($postCount) {
            $postCount--;
            $post = factory(Post::class)->make();
            $this->currentUser()->savePost($post);
        }

        $journalId = Journal::where('user_id', $user->id)->pluck('id');
        $this->assertCount(1, $journalId);
        $this->assertCount(
            $testPostCount,
            Post::where('journal_id', $journalId[0])
                ->withoutGlobalScopes()
                ->pluck('id')
        );

        Event::assertDispatched(MicroblogJournalCreated::class, 1);
        Event::assertDispatched(MicroblogJournalShared::class, 0);
        Event::assertDispatched(MicroblogJournalUnshared::class, 0);
    }

    public function test_journal_visibility()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $journal = Journal::forUser($user);
        $this->assertSame(Visibility::SHARED, $journal->visibility);

        $journal->hide();
        $this->assertSame(Visibility::PERSONAL, $journal->visibility);

        $journal->shareWithFriends();
        $this->assertSame(Visibility::SHARED, $journal->visibility);

        $journal->shareWithEveryone();
        $this->assertSame(Visibility::UNIVERSAL, $journal->visibility);

        Event::assertDispatched(MicroblogJournalCreated::class, 1);
        Event::assertDispatched(MicroblogJournalShared::class, 1);
        Event::assertDispatched(MicroblogJournalUnshared::class, 1);
    }

    public function test_journal_belongs_to_current_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $journal = Journal::forUser($user);
        $this->assertTrue($journal->belongsToCurrentUser());

        $user2 = factory(User::class)->create();
        $this->be($user2);

        $this->assertFalse($journal->belongsToCurrentUser());

        Event::assertDispatched(MicroblogJournalCreated::class, 2);
        Event::assertDispatched(MicroblogJournalShared::class, 0);
        Event::assertDispatched(MicroblogJournalUnshared::class, 0);
    }

    public function test_journal_does_not_belong_to_current_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);
        $post = factory(Post::class)->make();
        $user->savePost($post);
        $journal = Journal::forUser($user);
        $this->assertCount(1, $journal->posts()->get());

        $this->logout();

        $this->assertFalse($journal->belongsToCurrentUser());
        $this->assertCount(0, $journal->posts()->get());

        Event::assertDispatched(MicroblogJournalCreated::class, 1);
        Event::assertDispatched(MicroblogJournalShared::class, 0);
        Event::assertDispatched(MicroblogJournalUnshared::class, 0);
    }
}
