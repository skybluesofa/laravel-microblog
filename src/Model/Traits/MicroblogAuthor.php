<?php
namespace Skybluesofa\Microblog\Model\Traits;

use Skybluesofa\Microblog\Model\Contract\MicroblogPost;
use Skybluesofa\Microblog\Model\Contract\MicroblogJournal;

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

    public function journal() {
        MicroblogJournal::getOrCreate($this);
        return $this->hasOne('Skybluesofa\Microblog\Model\Journal');
    }

    public function journalId() {
        return $this->journal()->first()->id;
    }

    public function savePost(MicroblogPost $post) {
        $this->journal()->first()->posts()->save($post);
    }
}
