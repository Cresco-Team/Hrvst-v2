<?php

namespace App\Services\Farmer;

use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FarmerDashboardService
{
    public function summary(int $userId): array
    {
        $itemQuery = PostItem::whereHas(
            'post', fn (Builder $q) => $q->supply()->where('user_id', $userId)
        );

        return [
            'total_growing' => Post::supply()->growing()->where('user_id', $userId)->count(),
            'total_ongoing' => (clone $itemQuery)->ongoing()->count(),
            'total_fulfilled' => (clone $itemQuery)->fulfilled()->count(),
            'total_archived' => (clone $itemQuery)->archived()->count(),
        ];
    }

    public function expiringSupplies(int $userId): Collection
    {
        // Ongoing PostItems whose delivery date is within the next 3 days
        return PostItem::with(['variety', 'post.vegetable'])
            ->ongoing()
            ->whereHas('post', fn (Builder $q) => $q
                ->supply()
                ->harvested()
                ->where('user_id', $userId)
                ->whereBetween('scheduled_date', [now()->startOfDay(), now()->addDays(3)->endOfDay()])
            )
            ->get();
    }

    public function recommendations(int $userId): array
    {
        $itemQuery = PostItem::whereHas(
            'post', fn (Builder $q) => $q->supply()->where('user_id', $userId)
        );

        $ongoing = (clone $itemQuery)->ongoing()->count();
        $growing = Post::supply()->growing()->where('user_id', $userId)->count();
        $archived = (clone $itemQuery)->archived()->count();

        $recs = [];

        if ($growing === 0 && $ongoing === 0) {
            $recs[] = [
                'severity' => 'info',
                'type' => 'no_active_supply',
                'title' => 'No Active Supply',
                'body' => 'You have no growing or ongoing supply posts. Register a new crop to get started.',
            ];
        }

        if ($archived > 0) {
            $recs[] = [
                'severity' => 'warning',
                'type' => 'archived_items',
                'title' => 'Archived Items',
                'body' => "{$archived} supply item(s) were archived without being fulfilled. Review your pricing or delivery timing.",
            ];
        }

        return $recs;
    }
}
