<?php

use Skybluesofa\Microblog\Status;
use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Model\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MicroblogPostFriendsTest extends TestCase
{
    use DatabaseTransactions, DatabaseMigrations;

    public function test_user_can_share_a_blog_post_with_only_friends()
    {
        $user = factory(App\User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->share(true);

        $this->assertEquals(Status::PUBLISHED, $post->status);
        $this->assertEquals(Visibility::SHARED, $post->visibility);

        $user2 = factory(App\User::class)->create();
        //$user2->setBlogFriends([$user->id]);
        $this->be($user2);

        $this->assertCount(0, Post::pluck('id'));
    }

    public function test_user_can_share_a_blog_post_with_only_friends_as_default_share()
    {
        $user = factory(App\User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->share();

        $this->assertEquals(Status::PUBLISHED, $post->status);
        $this->assertEquals(Visibility::SHARED, $post->visibility);
    }

    public function test_published_blog_post_shared_with_friends_can_only_be_viewed_by_friends()
    {
        $user = factory(App\User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->share();

        $this->assertEquals(Status::PUBLISHED, $post->status);
        $this->assertEquals(Visibility::SHARED, $post->visibility);

        $user2 = factory(App\User::class)->create();
        $this->be($user2);
        $user2->setBlogFriends([$user->id]);

        $post = Post::find($post->id);

        $this->assertInstanceOf(Skybluesofa\Microblog\Model\Contract\MicroblogPost::class, $post);
    }

}
