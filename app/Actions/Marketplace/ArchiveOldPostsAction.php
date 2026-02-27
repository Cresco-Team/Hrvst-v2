<?php

namespace App\Actions\Marketplace;

use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Marketplace\FarmerSupply;
use App\Models\Marketplace\Post;

final class ArchiveOldPostsAction
{
    public function __invoke(): int
    {
        $offerings = Post::where('postable_type', FarmerSupply::class)
            ->where('status', PostStatus::Ongoing)
            ->whereHasMorph('postable', FarmerSupply::class, fn ($q) =>
                $q->whereNotNull('expiration_date')->where('expiration_date', '<', now())
            )
            ->update(['status' => PostStatus::Archived]);

        $demands = Post::where('postable_type', DealerDemand::class)
            ->where('status', PostStatus::Ongoing)
            ->whereHasMorph('postable', DealerDemand::class, fn ($q) =>
                $q->where('transaction_date', '<', now())
            )
            ->update(['status' => PostStatus::Archived]);

        return $offerings + $demands;
    }
}
