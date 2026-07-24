<?php

namespace App\Actions\PostItem;

use App\Models\Schedule\PostItem;

class UpdatePostItemAction
{
    public function handle(PostItem $postItem, array $validated): void
    {
        $postItem->update([
            'quantity_kg' => $validated['quantity_kg'],
        ]);
    }
}
