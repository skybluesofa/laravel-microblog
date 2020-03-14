<?php

use Skybluesofa\Microblog\Status;
use Skybluesofa\Microblog\Visibility;
use Skybluesofa\Microblog\Model\Post;
use Skybluesofa\Microblog\Model\Journal;
use App\User;
use Skybluesofa\Microblog\Model\User as MicroblogUser;
use Carbon\Carbon;

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

        $this->assertInstanceOf(Post::class, $post->unPublish());
        $this->assertEquals(Status::DRAFT, $post->status);

        $user2 = factory(User::class)->create();
        $this->be($user2);

        $post = Post::find($post->id);

        $this->assertNull($post);
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
    }

    public function test_get_posts_whereJournalIdIs()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $post = factory(Post::class)->make();
        $user->savePost($post);

        $posts = Post::whereJournalIdIs($user->journalId())->get();

        $this->assertCount(1, $posts);
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
    }
}
