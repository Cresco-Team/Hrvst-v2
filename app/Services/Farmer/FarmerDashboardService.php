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
        return PostItem::whereHas('post', fn (Builder $q) => $q->supply()->where('user_id', $userId));
    }

    public function expiringSupplies(int $userId): Collection
    {
        return Post::supply()
            ->where('user_id', $userId)
            ->whereHas('postItems', fn (Builder $q) => $q->ongoing())
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
        $expired = (clone $itemQuery)->expired()->count();

        if ($ongoing === 0) {
            $recs[] = new FarmerDashboardRecommendationDTO(
                severity: 'info',
                type: 'no_active_supply',
                title: 'No Active Supply',
                body: 'You have no scheduled supplies. Post a new supply to get started.',
            );
        }

        if ($expired > 0) {
            $recs[] = new FarmerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'expired_items',
                title: 'expired Items',
                body: "{$expired} supply item(s) expired without being fulfilled. Review your pricing or delivery timing.",
            );
        }

        return $recs;
    }
}
