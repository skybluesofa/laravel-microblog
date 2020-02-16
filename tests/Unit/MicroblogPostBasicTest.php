<?php

use Skybluesofa\Microblog\Status;
use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Model\Post;
use App\User;

class MicroblogPostBasicTest extends TestCase
{
    public function test_user_can_create_a_new_blog_post()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $this->assertCount(1, Post::withoutGlobalScopes()->where('journal_id', $user->journalId())->pluck('id'));
    }

    public function test_user_can_delete_a_blog_post()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $this->assertCount(1, Post::withoutGlobalScopes()->where('journal_id', $user->journalId())->pluck('id'));

        $post->delete();

        $this->assertCount(0, Post::withoutGlobalScopes()->where('journal_id', $user->journalId())->pluck('id'));
    }

    public function test_user_can_publish_unpublish_and_republish_a_blog_post()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->publish();
        $this->assertEquals(Status::PUBLISHED, $post->status);

        $post->unpublish();
        $this->assertEquals(Status::DRAFT, $post->status);

        $post->publish();
        $this->assertEquals(Status::PUBLISHED, $post->status);
    }

    public function test_user_can_make_a_blog_post_personal()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->hide();

        $this->assertEquals(Visibility::PERSONAL, $post->visibility);
    }

    public function test_blog_post_belongs_to_current_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $this->assertTrue($post->belongsToCurrentUser());
    }

    public function test_blog_post_does_not_belong_to_current_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $user2 = factory(User::class)->create();
        $this->be($user2);

        $this->assertFalse($post->belongsToCurrentUser());
    }

    public function test_draft_blog_post_cannot_be_viewed_by_anyone_else()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->unpublish();

        $this->assertEquals(Status::DRAFT, $post->status);

        $user2 = factory(User::class)->create();
        $this->be($user2);

        $post = Post::find($post->id);

        $this->assertNull($post);
    }

}
