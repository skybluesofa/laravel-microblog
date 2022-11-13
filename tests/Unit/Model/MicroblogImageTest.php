<?php

use App\User;
use Carbon\Carbon;
use Skybluesofa\Microblog\Enums\Visibility;
use Skybluesofa\Microblog\Model\Image;
use Skybluesofa\Microblog\Model\Journal;
use Skybluesofa\Microblog\Model\User as MicroblogUser;
use Skybluesofa\Microblog\Tests\Testcase;

class MicroblogImageTest extends TestCase
{
    public function test_user_can_add_a_new_image()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image = factory(Image::class)->make();
        $user->saveImage($image);

        $this->assertCount(1, Image::withoutGlobalScopes()->where('journal_id', $user->journalId())->pluck('id'));
    }

    public function test_get_image_journal_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image = factory(Image::class)->make();
        $user->saveImage($image);

        $journal = $image->journal();
        $imageUser = $image->user();
        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertInstanceOf(MicroblogUser::class, $imageUser);
        $this->assertEquals($user->id, $imageUser->id);

        $this->assertEquals($user->name, $image->userName());
    }

    public function test_user_can_delete_an_image()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image = factory(Image::class)->make();
        $user->saveImage($image);

        $this->assertCount(1, Image::withoutGlobalScopes()->where('journal_id', $user->journalId())->pluck('id'));

        $image->delete();

        $this->assertCount(0, Image::withoutGlobalScopes()->where('journal_id', $user->journalId())->pluck('id'));
    }

    public function test_user_can_make_a_image_personal()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image = factory(Image::class)->make();
        $user->saveImage($image);

        $this->assertInstanceOf(Image::class, $image->hide());
        $this->assertEquals(Visibility::PERSONAL, $image->visibility);
        $personalImages = Image::wherePersonal()->get();
        $sharedImages = Image::whereOnlySharedWithFriends()->get();
        $publicImages = Image::wherePublic()->get();
        $this->assertCount(1, $personalImages);
        $this->assertCount(0, $sharedImages);
        $this->assertCount(0, $publicImages);

        $this->assertInstanceOf(Image::class, $image->share());
        $this->assertEquals(Visibility::SHARED, $image->visibility);
        $personalImages = Image::wherePersonal()->get();
        $sharedImages = Image::whereOnlySharedWithFriends()->get();
        $publicImages = Image::wherePublic()->get();
        $this->assertCount(0, $personalImages);
        $this->assertCount(1, $sharedImages);
        $this->assertCount(0, $publicImages);

        $this->assertInstanceOf(Image::class, $image->share(false));
        $this->assertEquals(Visibility::UNIVERSAL, $image->visibility);
        $personalImages = Image::wherePersonal()->get();
        $sharedImages = Image::whereOnlySharedWithFriends()->get();
        $publicImages = Image::wherePublic()->get();
        $this->assertCount(0, $personalImages);
        $this->assertCount(0, $sharedImages);
        $this->assertCount(1, $publicImages);
    }

    public function test_image_belongs_to_current_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image = factory(Image::class)->make();
        $user->saveImage($image);

        $this->assertTrue($image->belongsToCurrentUser());
    }

    public function test_image_does_not_belong_to_current_user()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image = factory(Image::class)->make();
        $user->saveImage($image);

        $user2 = factory(User::class)->create();
        $this->be($user2);

        $this->assertFalse($image->belongsToCurrentUser());
    }

    public function test_get_images_whereUserIdIs()
    {
        $user1 = factory(User::class)->create();
        $this->be($user1);
        $image = factory(Image::class)->make();
        $user1->saveImage($image);

        $images = Image::whereUserIdIs($user1->id)->get();
        $this->assertCount(1, $images);

        $user2 = factory(User::class)->create();
        $this->be($user2);
        $image = factory(Image::class)->make();
        $user2->saveImage($image);

        $images = Image::whereUserIdIs($user2->id)->get();
        $this->assertCount(1, $images); // User2 can't see user1's images
    }

    public function test_get_images_whereUserIdIn()
    {
        $user1 = factory(User::class)->create();
        $this->be($user1);
        $image1 = factory(Image::class)->make();
        $user1->saveImage($image1);
        $image1->share(false);

        $images = Image::whereUserIdIn([$user1->id])->get();
        $this->assertCount(1, $images);

        $user2 = factory(User::class)->create();
        $this->be($user2);
        $image2 = factory(Image::class)->make();
        $user2->saveImage($image2);

        $images = Image::whereUserIdIn([$user2->id])->get();
        $this->assertCount(1, $images);

        $images = Image::whereUserIdIn([$user1->id, $user2->id])->get();
        $this->assertCount(2, $images); // The count is 2 because user1's image was shared with the world

        $this->be($user1);
        $image1->hide(); // hide from everyone except the author

        $this->be($user2);

        $images = Image::whereUserIdIn([$user1->id, $user2->id])->get();
        $this->assertCount(1, $images); // The count is 1 because user1's image is now hidden
    }

    public function test_get_images_whereJournalIdIs()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image = factory(Image::class)->make();
        $user->saveImage($image);

        $images = Image::whereJournalIdIs($user->journalId())->get();

        $this->assertCount(1, $images);
    }

    public function test_get_images_whereOlderThan()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image = factory(Image::class)->make();
        $user->saveImage($image);

        $date = new Carbon('+1 day');
        $images = Image::whereOlderThan($date)->get();

        $this->assertCount(1, $images);
    }

    public function test_get_images_whereNewerThan()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image = factory(Image::class)->make();
        $user->saveImage($image);

        $date = new Carbon('-1 day');
        $images = Image::whereNewerThan($date)->get();

        $this->assertCount(1, $images);
    }

    public function test_get_images_whereOlderThanImage()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image1 = factory(Image::class)->make();
        $user->saveImage($image1);

        sleep(1);

        $image2 = factory(Image::class)->make();
        $user->saveImage($image2);

        $images = Image::whereOlderThanImageId($image2->id)->get();

        $this->assertCount(1, $images);
    }

    public function test_get_images_whereNewerThanImage()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $image1 = factory(Image::class)->make();
        $user->saveImage($image1);

        sleep(1);

        $image2 = factory(Image::class)->make();
        $user->saveImage($image2);

        $images = Image::whereNewerThanImageId($image1->id)->get();

        $this->assertCount(1, $images);
    }
}
