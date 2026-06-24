<?php

namespace App\Actions\Post;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;

class ExpirePostItemsAction
{
    public function __invoke(): int
    {
        $count = 0;

        PostItem::ongoing()
            ->whereHas('post', fn ($q) => $q->where('scheduled_date', '<', today())
                ->whereNull('deleted_at')
            )
            ->chunkById(200, function ($items) use (&$count) {
                $items->toQuery()->update(['status' => PostItemStatus::Expired->value]);
                $count += $items->count();
            });

        return $count;
    }
}
