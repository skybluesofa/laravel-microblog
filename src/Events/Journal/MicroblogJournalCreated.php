<?php

namespace Skybluesofa\Microblog\Events\Journal;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skybluesofa\Microblog\Model\Contract\MicroblogJournal;

class MicroblogJournalCreated implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public MicroblogJournal $journal;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(MicroblogJournal $journal)
    {
        $this->journal = $journal;
    }
}
