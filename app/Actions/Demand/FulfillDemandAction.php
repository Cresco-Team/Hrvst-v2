<?php

namespace App\Actions\Demand;

use App\Models\Marketplace\Post;

final class FulfillDemandAction
{
    public function handle(Post $post): void
    {
        $post->markAsFulfilled();
    }
}
