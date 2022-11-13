<?php

namespace Skybluesofa\Microblog\Model\Traits;

use Skybluesofa\Microblog\Model\Contract\MicroblogImage;
use Skybluesofa\Microblog\Model\Contract\MicroblogJournal;
use Skybluesofa\Microblog\Model\Contract\MicroblogPost;
use Skybluesofa\Microblog\Model\Journal;

trait MicroblogAuthor
{
    protected string $type = 'author';

    public function journal()
    {
        MicroblogJournal::getOrCreate($this);

        return $this->hasOne(Journal::class)->first();
    }

    public function journalId(): string
    {
        return $this->journal()->id;
    }

    public function savePost(MicroblogPost $microblogPost): void
    {
        $this->journal()->posts()->save($microblogPost);
    }

    public function saveImage(MicroblogImage $microblogImage): void
    {
        $this->journal()->images()->save($microblogImage);
    }
}
