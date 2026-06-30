<?php

namespace App\Services\Dealer;

use App\DTOs\Dealer\DealerDashboardRecommendationDTO;
use App\Enums\PostItemStatus;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Profiles\DealerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DealerDashboardService
{
    private function baseItemQuery(int $userId): Builder
    {
        return PostItem::whereHas('post', fn (Builder $q) => $q->demand()->where('user_id', $userId));
    }

    public function expiringDemands(int $userId): Collection
    {
        return Post::demand()
            ->where('user_id', $userId)
            ->whereHas('postItems', fn (Builder $q) => $q->ongoing())
            ->whereBetween('scheduled_date', [
                today()->subDays(Post::ACTION_WINDOW_DAYS - 1),
                today(),
            ])
            ->with(['postItems' => fn ($q) => $q->ongoing()->with('variety.vegetable')])
            ->orderBy('scheduled_date')
            ->get();
    }

    /**
     * @return DealerDashboardRecommendationDTO[]
     */
    public function recommendations(int $userId): array
    {
        $recs = [];
        $itemQuery = $this->baseItemQuery($userId);

        $ongoing = (clone $itemQuery)->ongoing()->count();
        $expired = (clone $itemQuery)->expired()->count();

        if ($ongoing === 0) {
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'info',
                type: 'no_active_supply',
                title: 'No Active Supply',
                body: 'You have no scheduled requests. Schedule a new request to get started.',
            );
        }

        if ($expired > 0) {
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'expired_items',
                title: 'expired Items',
                body: "{$expired} request item(s) expired without being fulfilled. Review your pricing or delivery timing.",
            );
        }

        return $recs;
    }
}
