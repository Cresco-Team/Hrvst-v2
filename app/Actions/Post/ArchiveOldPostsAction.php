<?php

namespace App\Actions\Post;

use App\Enums\PostStatus;
use App\Models\Marketplace\Post;

final class ArchiveOldPostsAction
{
    public function __invoke(): int
    {
        return Post::where('status', PostStatus::Ongoing)
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '<', now())
            ->update(['status' => PostStatus::Archived]);
    }
}
