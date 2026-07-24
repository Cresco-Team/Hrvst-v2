<?php

namespace App\Actions\Post;

use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;

class ExpirePostItemsAction
{
    public function __invoke(): int
    {
        $count = 0;

        PostItem::ongoing()
            ->whereHas('post', fn ($q) => $q
                ->where('scheduled_date', '<=', today()->subDays(Post::ACTION_WINDOW_DAYS))
                ->whereNull('deleted_at')
            )
            ->with('post')
            ->chunkById(200, function ($items) use (&$count) {
                $items->each(fn (PostItem $item) => $item->markAsExpired());
                $count += $items->count();
            });

        return $count;
    }
}
