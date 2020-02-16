<?php

use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\Journal;
use App\User;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;

class MicroblogJournalTest extends TestCase
{
    use MicroblogCurrentUser;

    public function test_create_a_journal_if_one_does_not_exist_for_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        Journal::getOrCreate($user);

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
}
