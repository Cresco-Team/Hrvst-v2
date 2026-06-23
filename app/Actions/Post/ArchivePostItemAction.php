<?php

namespace App\Actions\Post;

use App\Models\Marketplace\PostItem;
use Illuminate\Database\Eloquent\Builder;

final class ArchivePostItemAction
{
    public function __invoke(): int
    {
        $count = 0;

        PostItem::ongoing()
            ->whereHas('post', fn (Builder $q) => $q
                ->whereNotNull('scheduled_date')
                ->where('scheduled_date', '<', yesterday())
            )
            ->with('post')
            ->chunkById(200, function ($items) use (&$count): void {
                foreach ($items as $item) {
                    $item->markAsUnsettled();
                    $count++;
                }
            });

        return $count;
    }
}
