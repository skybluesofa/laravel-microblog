<?php

use Skybluesofa\Microblog\Status;
use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Model\Post;
use App\User;

class MicroblogPostUniversalTest extends TestCase
{
    public function test_user_can_share_a_blog_post_to_anyone_with_url()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->share(false);

        $this->assertEquals(Status::PUBLISHED, $post->status);
        $this->assertEquals(Visibility::UNIVERSAL, $post->visibility);
    }

    public function test_published_universal_blog_post_can_be_viewed_by_anyone()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->share(false);

        $this->assertEquals(Status::PUBLISHED, $post->status);
        $this->assertEquals(Visibility::UNIVERSAL, $post->visibility);

        $user2 = factory(User::class)->create();
        $this->be($user2);

        $post = Post::find($post->id);

        $this->assertInstanceOf(Post::class, $post);
    }
}
