<?php

namespace App\Actions\PostItem;

use App\Models\Marketplace\PostItem;

final class ArchivePostItemAction
{
    public function handle(PostItem $postItem): void
    {
        $postItem->markAsUnsettled();
    }
}
