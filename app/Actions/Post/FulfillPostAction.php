<?php

namespace App\Actions\Post;

use App\Models\Marketplace\Post;

final class FulfillPostAction
{
    public function handle(Post $post): void
    {
        $post->markAsFulfilled();
    }
}
