<?php
namespace Skybluesofa\Microblog\Model\Traits;

use Skybluesofa\Microblog\Model\Contract\MicroblogPost;
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
        return $this->hasOne(Journal::class);
    }

    public function journalId() : string
    {
        return $this->journal()->first()->id;
    }

    public function savePost(MicroblogPost $post) : void
    {
        $this->journal()->first()->posts()->save($post);
    }
}
