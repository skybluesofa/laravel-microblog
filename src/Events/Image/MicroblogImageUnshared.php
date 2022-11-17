<?php

namespace Skybluesofa\Microblog\Events\Image;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skybluesofa\Microblog\Model\Contract\MicroblogImage;

class MicroblogImageUnshared implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public MicroblogImage $image;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(MicroblogImage $image)
    {
        $this->image = $image;
    }
}
