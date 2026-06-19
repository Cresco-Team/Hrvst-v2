<?php

namespace App\Services\Farmer;

use App\DTOs\Farmer\FarmerDashboardRecommendationDTO;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FarmerDashboardService
{
    private function baseItemQuery(int $userId): Builder
    {
        return PostItem::whereHas(
            'post', fn (Builder $q) => $q->supply()->where('user_id', $userId)
        );
    }

    public function summary(int $userId): array
    {
        $itemQuery = $this->baseItemQuery($userId);

        return [
            'total_growing' => Post::supply()->growing()->where('user_id', $userId)->count(),
            'total_ongoing' => (clone $itemQuery)->ongoing()->count(),
            'total_fulfilled' => (clone $itemQuery)->fulfilled()->count(),
            'total_unsettled' => (clone $itemQuery)->unsettled()->count(),
        ];
    }

    public function expiringSupplies(int $userId): Collection
    {
        return Post::supply()
            ->ready()
            ->where('user_id', $userId)
            ->whereBetween('scheduled_date', [now()->startOfDay(), now()->addDays(3)->endOfDay()])
            ->with(['vegetable.category', 'media', 'postItems.variety'])
            ->orderBy('scheduled_date')
            ->get();
    }

    /** @return FarmerDashboardRecommendationDTO[] */
    public function recommendations(int $userId): array
    {
        $recs = [];

        $itemQuery = $this->baseItemQuery($userId);

        $ongoing = (clone $itemQuery)->ongoing()->count();
        $growing = Post::supply()->growing()->where('user_id', $userId)->count();
        $unsettled = (clone $itemQuery)->unsettled()->count();

        if ($growing === 0 && $ongoing === 0) {
            $recs[] = new FarmerDashboardRecommendationDTO(
                severity: 'info',
                type: 'no_active_supply',
                title: 'No Active Supply',
                body: 'You have no growing or ongoing supply posts. Register a new crop to get started.',
            );
        }

        if ($unsettled > 0) {
            $recs[] = new FarmerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'unsettled_items',
                title: 'Unsettled Items',
                body: "{$unsettled} supply item(s) were unsettled without being fulfilled. Review your pricing or delivery timing.",
            );
        }

        return $recs;
    }
}
