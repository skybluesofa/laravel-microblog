<?php

use App\User;
use Carbon\Carbon;
use Skybluesofa\Microblog\Enums\Status;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Events\Post\MicroblogPostCreated;
use Skybluesofa\Microblog\Events\Post\MicroblogPostDeleted;
use Skybluesofa\Microblog\Events\Post\MicroblogPostShared;
use Skybluesofa\Microblog\Events\Post\MicroblogPostUnshared;
use Skybluesofa\Microblog\Model\Journal;
use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\Traits\MicroblogCurrentUser;
use Skybluesofa\Microblog\Model\User as MicroblogUser;
use Skybluesofa\Microblog\Tests\Testcase;

class MicroblogPostBasicTest extends TestCase
{
    use MicroblogCurrentUser;

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

    public function test_create_a_journal_post_fires_correct_events()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $this->currentUser()->savePost($post);

        $journalId = Journal::where('user_id', $user->id)->pluck('id');
        $this->assertCount(
            1,
            Post::where('journal_id', $journalId[0])
                ->withoutGlobalScopes()
                ->pluck('id')
        );

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_user_can_create_a_new_blog_post()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $this->assertCount(1, Post::withoutGlobalScopes()->where('journal_id', $user->journalId())->pluck('id'));

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_get_post_journal_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $journal = $post->journal();
        $postUser = $post->user();
        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertInstanceOf(MicroblogUser::class, $postUser);
        $this->assertEquals($user->id, $postUser->id);

        $this->assertEquals($user->name, $post->userName());

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
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

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 1);
    }

    public function test_user_can_publish_unpublish_and_republish_a_blog_post()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $this->assertInstanceOf(Post::class, $post->publish());
        $this->assertEquals(Status::PUBLISHED, $post->status);
        $publishedPosts = Post::wherePublished()->get();
        $unpublishedPosts = Post::whereUnpublished()->get();
        $this->assertCount(1, $publishedPosts);
        $this->assertCount(0, $unpublishedPosts);

        $this->assertInstanceOf(Post::class, $post->unPublish());
        $this->assertEquals(Status::DRAFT, $post->status);
        $publishedPosts = Post::wherePublished()->get();
        $unpublishedPosts = Post::whereUnpublished()->get();
        $this->assertCount(0, $publishedPosts);
        $this->assertCount(1, $unpublishedPosts);

        $this->assertInstanceOf(Post::class, $post->publish());
        $this->assertEquals(Status::PUBLISHED, $post->status);
        $publishedPosts = Post::wherePublished()->get();
        $unpublishedPosts = Post::whereUnpublished()->get();
        $this->assertCount(1, $publishedPosts);
        $this->assertCount(0, $unpublishedPosts);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_user_can_make_a_blog_post_personal()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $this->assertInstanceOf(Post::class, $post->hide());
        $this->assertEquals(Visibility::PERSONAL, $post->visibility);
        $personalPosts = Post::wherePersonal()->get();
        $sharedPosts = Post::whereOnlySharedWithFriends()->get();
        $publicPosts = Post::wherePublic()->get();
        $this->assertCount(1, $personalPosts);
        $this->assertCount(0, $sharedPosts);
        $this->assertCount(0, $publicPosts);

        $this->assertInstanceOf(Post::class, $post->share());
        $this->assertEquals(Visibility::SHARED, $post->visibility);
        $personalPosts = Post::wherePersonal()->get();
        $sharedPosts = Post::whereOnlySharedWithFriends()->get();
        $publicPosts = Post::wherePublic()->get();
        $this->assertCount(0, $personalPosts);
        $this->assertCount(1, $sharedPosts);
        $this->assertCount(0, $publicPosts);

        $this->assertInstanceOf(Post::class, $post->share(false));
        $this->assertEquals(Visibility::UNIVERSAL, $post->visibility);
        $personalPosts = Post::wherePersonal()->get();
        $sharedPosts = Post::whereOnlySharedWithFriends()->get();
        $publicPosts = Post::wherePublic()->get();
        $this->assertCount(0, $personalPosts);
        $this->assertCount(0, $sharedPosts);
        $this->assertCount(1, $publicPosts);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 1);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_blog_post_belongs_to_current_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $this->assertTrue($post->belongsToCurrentUser());

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
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

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_post_visibility()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $this->currentUser()->savePost($post);

        // Ensure the default visibility
        $this->assertSame(Visibility::PERSONAL, $post->visibility);

        $post->share();
        $this->assertSame(Visibility::SHARED, $post->visibility);

        $post->share(onlyToFriends: false);
        $this->assertSame(Visibility::UNIVERSAL, $post->visibility);

        $post->share(onlyToFriends: true);
        $this->assertSame(Visibility::SHARED, $post->visibility);

        $post->hide();
        $this->assertSame(Visibility::PERSONAL, $post->visibility);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 1);
        Event::assertDispatched(MicroblogPostUnshared::class, 1);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_post_status()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $this->currentUser()->savePost($post);

        // Ensure the default visibility and status
        $this->assertSame(Status::PUBLISHED, $post->status);
        $this->assertSame(Visibility::PERSONAL, $post->visibility);

        $post->share();
        $this->assertSame(Status::PUBLISHED, $post->status);
        $this->assertSame(Visibility::SHARED, $post->visibility);

        $post->share(onlyToFriends: false);
        $this->assertSame(Status::PUBLISHED, $post->status);
        $this->assertSame(Visibility::UNIVERSAL, $post->visibility);

        $post->share(onlyToFriends: true);
        $this->assertSame(Status::PUBLISHED, $post->status);
        $this->assertSame(Visibility::SHARED, $post->visibility);

        $post->hide();
        $this->assertSame(Status::PUBLISHED, $post->status);
        $this->assertSame(Visibility::PERSONAL, $post->visibility);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 1);
        Event::assertDispatched(MicroblogPostUnshared::class, 1);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_post_publishing()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $this->currentUser()->savePost($post);

        // Ensure the default visibility and status
        $this->assertSame(Status::PUBLISHED, $post->status);

        $post->unpublish();
        $this->assertSame(Status::DRAFT, $post->status);

        $post->publish();
        $this->assertSame(Status::PUBLISHED, $post->status);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_draft_blog_post_cannot_be_viewed_by_anyone_else()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $this->assertInstanceOf(Post::class, $post->unPublish());
        $this->assertEquals(Status::DRAFT, $post->status);

        $user2 = factory(User::class)->create();
        $this->be($user2);

        $post = Post::find($post->id);

        $this->assertNull($post);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_get_posts_whereUserIdIs()
    {
        $user1 = factory(User::class)->create();
        $this->be($user1);
        $post = factory(Post::class)->make();
        $user1->savePost($post);

        $posts = Post::whereUserIdIs($user1->id)->get();
        $this->assertCount(1, $posts);

        $user2 = factory(User::class)->create();
        $this->be($user2);
        $post = factory(Post::class)->make();
        $user2->savePost($post);

        $posts = Post::whereUserIdIs($user2->id)->get();
        $this->assertCount(1, $posts); // User2 can't see user1's post

        Event::assertDispatched(MicroblogPostCreated::class, 2);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_get_posts_whereUserIdIn()
    {
        $user1 = factory(User::class)->create();
        $this->be($user1);
        $post1 = factory(Post::class)->make();
        $user1->savePost($post1);
        $post1->share(false); // share with the world

        $posts = Post::whereUserIdIn([$user1->id])->get();
        $this->assertCount(1, $posts);

        $user2 = factory(User::class)->create();
        $this->be($user2);
        $post2 = factory(Post::class)->make();
        $user2->savePost($post2);

        $posts = Post::whereUserIdIn([$user2->id])->get();
        $this->assertCount(1, $posts);

        $posts = Post::whereUserIdIn([$user1->id, $user2->id])->get();
        $this->assertCount(2, $posts); // The count is 2 because user1's post was shared with the world

        $this->be($user1);
        $post1->hide(); // hide from everyone except the author
        $posts = Post::whereUserIdIn([$user1->id, $user2->id])->get();
        $this->assertCount(1, $posts); // The count is 1 because user1's post is now hidden

        Event::assertDispatched(MicroblogPostCreated::class, 2);
        Event::assertDispatched(MicroblogPostShared::class, 1);
        Event::assertDispatched(MicroblogPostUnshared::class, 1);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_get_posts_whereJournalIdIs()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $posts = Post::whereJournalIdIs($user->journalId())->get();

        $this->assertCount(1, $posts);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_get_posts_whereOlderThan()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $date = new Carbon('+1 day');
        $posts = Post::whereOlderThan($date)->get();

        $this->assertCount(1, $posts);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_get_posts_whereNewerThan()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $date = new Carbon('-1 day');
        $posts = Post::whereNewerThan($date)->get();

        $this->assertCount(1, $posts);

        Event::assertDispatched(MicroblogPostCreated::class, 1);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_get_posts_whereOlderThanPost()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post1 = factory(Post::class)->make();
        $user->savePost($post1);

        sleep(1);

        $post2 = factory(Post::class)->make();
        $user->savePost($post2);

        $posts = Post::whereOlderThanPostId($post2->id)->get();

        $this->assertCount(1, $posts);

        Event::assertDispatched(MicroblogPostCreated::class, 2);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }

    public function test_get_posts_whereNewerThanPost()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post1 = factory(Post::class)->make();
        $user->savePost($post1);

        sleep(1);

        $post2 = factory(Post::class)->make();
        $user->savePost($post2);

        $posts = Post::whereNewerThanPostId($post1->id)->get();

        $this->assertCount(1, $posts);

        Event::assertDispatched(MicroblogPostCreated::class, 2);
        Event::assertDispatched(MicroblogPostShared::class, 0);
        Event::assertDispatched(MicroblogPostUnshared::class, 0);
        Event::assertDispatched(MicroblogPostDeleted::class, 0);
    }
}
