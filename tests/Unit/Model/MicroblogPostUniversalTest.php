<?php

use App\User;
use Skybluesofa\Microblog\Enums\Status;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Tests\Testcase;
use Skybluesofa\Microblog\Events\Post\MicroblogPostCreated;
use Skybluesofa\Microblog\Events\Post\MicroblogPostShared;
use Skybluesofa\Microblog\Events\Post\MicroblogPostUnshared;
use Skybluesofa\Microblog\Events\Post\MicroblogPostDeleted;

class MicroblogPostUniversalTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake([
            MicroblogPostCreated::class,
            MicroblogPostShared::class,
            MicroblogPostUnshared::class,
            MicroblogPostDeleted::class,
        ]);
    }

    public function test_user_can_share_a_blog_post_to_anyone_with_url()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->share(false);

        $this->assertEquals(Status::PUBLISHED, $post->status);
        $this->assertEquals(Visibility::UNIVERSAL, $post->visibility);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 1);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
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

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 1);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }
}
