<?php

namespace App\Actions\Supply;

use App\Models\Marketplace\Post;

final class ArchiveSupplyAction
{
    public function handle(Post $post): void
    {
        $post->markAsArchived();
    }
}
