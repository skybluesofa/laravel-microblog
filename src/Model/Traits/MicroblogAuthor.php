<?php
namespace Skybluesofa\Microblog\Model\Traits;

use Skybluesofa\Microblog\Model\Contract\MicroblogPost;
use Skybluesofa\Microblog\Model\Contract\MicroblogImage;
use Skybluesofa\Microblog\Model\Contract\MicroblogJournal;
use Skybluesofa\Microblog\Model\Journal;

/**
 * Class MicroblogAuthor
 * @package Skybluesofa\Microblog\Traits
 *
 * Applying this trait to a User model will allow you to implement your own
 * getBlogFriends() method for your special use case.
 */
trait MicroblogAuthor
{
    protected $type = 'author';

    public function journal()
    {
        MicroblogJournal::getOrCreate($this);
        return $this->hasOne(Journal::class)->first();
    }

    public function journalId() : string
    {
        return $this->journal()->id;
    }

    public function savePost(MicroblogPost $microblogPost) : void
    {
        $this->journal()->posts()->save($microblogPost);
    }

    public function saveImage(MicroblogImage $microblogImage) : void
    {
        $this->journal()->images()->save($microblogImage);
    }
}
