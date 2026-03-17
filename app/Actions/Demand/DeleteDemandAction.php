<?php

namespace App\Actions\Demand;

use App\Models\Marketplace\Post;

final class DeleteDemandAction
{
    public function handle(Post $post): void
    {
        $post->delete();
    }
}
