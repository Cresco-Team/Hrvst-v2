<?php

namespace App\Actions\PostItem;

use App\Models\Marketplace\PostItem;

class ExpirePostItemAction
{
    public function handle(PostItem $postItem): void
    {
        $postItem->markAsExpired();
    }
}
