<?php

namespace Skybluesofa\Microblog\Events\Post;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Skybluesofa\Microblog\Model\Contract\MicroblogPost;

class MicroblogPostCreated implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public MicroblogPost $post;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(MicroblogPost $post)
    {
        $this->post = $post;
    }
}
