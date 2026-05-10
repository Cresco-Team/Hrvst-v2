<?php

namespace App\Actions\PostItem;

use App\Models\Marketplace\PostItem;

final class FulfillPostItemAction
{
    public function handle(PostItem $postItem): void
    {
        $postItem->markAsFulfilled();
    }
}
