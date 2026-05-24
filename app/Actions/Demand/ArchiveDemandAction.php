<?php

namespace App\Actions\Demand;

use App\Models\Marketplace\Post;

final class ArchiveDemandAction
{
    public function handle(Post $post): void
    {
        $post->markAsUnsettled();
    }
}
