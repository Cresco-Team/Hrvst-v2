<?php

namespace App\Actions\PostItem;

use App\Models\Schedule\PostItem;

class ExpirePostItemAction
{
    public function handle(PostItem $postItem): void
    {
        $postItem->markAsExpired();
    }
}
