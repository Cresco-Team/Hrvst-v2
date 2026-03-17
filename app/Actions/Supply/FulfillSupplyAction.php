<?php

namespace App\Actions\Supply;

use App\Models\Marketplace\Post;

final class FulfillSupplyAction
{
    public function handle(Post $post): void
    {
        $post->markAsFulfilled();
    }
}
