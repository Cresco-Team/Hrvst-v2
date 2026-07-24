<?php

namespace App\Actions\PostItem;

use App\Models\Schedule\PostItem;

final class FulfillPostItemAction
{
    public function handle(PostItem $postItem): void
    {
        $postItem->markAsFulfilled();
    }
}
