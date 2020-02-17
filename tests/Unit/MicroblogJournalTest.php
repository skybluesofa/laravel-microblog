<?php

use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\Journal;
use App\User;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Skybluesofa\Microblog\Visibility;

class MicroblogJournalTest extends TestCase
{
    use MicroblogCurrentUser;

    public function test_create_a_journal_if_one_does_not_exist_for_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        Journal::forUser($user);

        $this->assertCount(1, Journal::where('user_id', $user->id)->pluck('id'));
    }

    public function test_create_a_journal_on_first_post_creation_if_one_does_not_exist_for_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $postCount = 2;
        $testPostCount = $postCount;
        while ($postCount) {
            --$postCount;
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
    }
}
