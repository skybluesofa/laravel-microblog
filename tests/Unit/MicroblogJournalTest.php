<?php

use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\Journal;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MicroblogJournalTest extends TestCase
{
    use DatabaseTransactions, DatabaseMigrations;

    public function test_create_a_journal_if_one_does_not_exist_for_user()
    {
        $user = factory(App\User::class)->create();
        $this->be($user);

        Journal::getOrCreate($user);

        $this->assertCount(1, Journal::where('user_id', $user->id)->pluck('id'));
    }

    public function test_create_a_journal_on_first_post_creation_if_one_does_not_exist_for_user()
    {
        $user = factory(App\User::class)->create();
        $this->be($user);

        $postCount = 2;
        $testPostCount = $postCount;
        while ($postCount) {
            --$postCount;
            $post = factory(Skybluesofa\Microblog\Model\Post::class)->make();
            Auth::user()->savePost($post);
        }

        $journalId = Journal::where('user_id', $user->id)->pluck('id');
        $this->assertCount(1, $journalId);
        $this->assertCount($testPostCount, Post::where('journal_id', $journalId[0])
            ->withoutGlobalScopes()
            ->pluck('id'));
    }
}
