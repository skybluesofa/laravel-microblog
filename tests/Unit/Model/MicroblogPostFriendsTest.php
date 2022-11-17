<?php

use App\User;
use Skybluesofa\Microblog\Enums\Status;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Events\Post\MicroblogPostCreated;
use Skybluesofa\Microblog\Events\Post\MicroblogPostDeleted;
use Skybluesofa\Microblog\Events\Post\MicroblogPostShared;
use Skybluesofa\Microblog\Events\Post\MicroblogPostUnshared;
use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Tests\Testcase;

class MicroblogPostFriendsTest extends TestCase
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

    public function test_user_can_share_a_blog_post_with_only_friends()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->share(true);

        $this->assertEquals(Status::PUBLISHED, $post->status);
        $this->assertEquals(Visibility::SHARED, $post->visibility);

        $user2 = factory(User::class)->create();
        //$user2->setBlogFriends([$user->id]);
        $this->be($user2);

        $this->assertCount(0, Post::pluck('id'));

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 1);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_user_can_share_a_blog_post_with_only_friends_as_default_share()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->share();

        $this->assertEquals(Status::PUBLISHED, $post->status);
        $this->assertEquals(Visibility::SHARED, $post->visibility);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 1);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_published_blog_post_shared_with_friends_can_only_be_viewed_by_friends()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $post->share();

        $this->assertEquals(Status::PUBLISHED, $post->status);
        $this->assertEquals(Visibility::SHARED, $post->visibility);

        $user2 = factory(User::class)->create();
        $this->be($user2);
        $user2->setBlogFriends([$user->id]);

        $post = Post::find($post->id);

        $this->assertInstanceOf(Skybluesofa\Microblog\Model\Contract\MicroblogPost::class, $post);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 1);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }
}
