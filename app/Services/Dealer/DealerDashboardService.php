<?php

namespace App\Services\Dealer;

use App\DTOs\Dealer\DealerDashboardRecommendationDTO;
use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;
use App\Models\Profiles\DealerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DealerDashboardService
{
    /**
     * Ongoing demand PostItems with scheduled_date within the next 3 days.
     */
    public function expiringDemands(DealerProfile $profile): Collection
    {
        return PostItem::with(['variety', 'post.vegetable'])
            ->ongoing()
            ->whereHas('post', fn (Builder $q) => $q
                ->demand()
                ->harvested()
                ->where('user_id', $profile->user_id)
                ->whereBetween('scheduled_date', [now()->startOfDay(), now()->addDays(3)->endOfDay()])
            )
            ->get();
    }

    /**
     * @return DealerDashboardRecommendationDTO[]
     */
    public function recommendations(DealerProfile $profile): array
    {
        $recs = [];

        $ongoingItems = PostItem::with(['post.vegetable'])
            ->ongoing()
            ->whereHas('post', fn (Builder $q) => $q
                ->demand()
                ->harvested()
                ->where('user_id', $profile->user_id)
            )
            ->get();

        $closedItems = PostItem::whereHas('post', fn (Builder $q) => $q
            ->demand()
            ->where('user_id', $profile->user_id)
        )
            ->whereIn('status', [PostItemStatus::Archived->value, PostItemStatus::Fulfilled->value])
            ->get();

        // ── 1. Expiring tomorrow (critical) ──────────────────────────────────

        $tomorrow = now()->addDay()->toDateString();

        $expiringTomorrow = $ongoingItems->filter(
            fn ($item) => $item->post->scheduled_date?->toDateString() === $tomorrow
        );

        foreach ($expiringTomorrow as $item) {
            $name = $item->post->vegetable?->name ?? 'Unknown';
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'critical',
                type: 'expiring_tomorrow',
                title: "Your {$name} demand expires tomorrow",
                body: "This item ({$item->quantity_kg}kg) will auto-archive in less than 24 hours with no farmer matched. Extend the transaction date or archive it manually.",
            );
        }

        // ── 2. Expiring in 2–3 days (warning) ────────────────────────────────

        $inThreeDays = $ongoingItems->filter(function ($item) use ($tomorrow) {
            $date = $item->post->scheduled_date?->toDateString();

            return $date !== null
                && $date > $tomorrow
                && $date <= now()->addDays(3)->toDateString();
        });

        if ($inThreeDays->isNotEmpty()) {
            $count = $inThreeDays->count();
            $names = $inThreeDays
                ->map(fn ($item) => $item->post->vegetable?->name)
                ->filter()
                ->unique()
                ->implode(', ');

            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'expiring_soon',
                title: "{$count} demand ".str('item')->plural($count).' expire within 3 days',
                body: "Review {$names} — extend the transaction date before the deadline if you haven't sourced a farmer yet.",
            );
        }

        // ── 3. Ongoing items with no scheduled date (warning) ─────────────────

        $unscheduled = $ongoingItems->filter(
            fn ($item) => $item->post->scheduled_date === null
        );

        if ($unscheduled->isNotEmpty()) {
            $count = $unscheduled->count();
            $names = $unscheduled
                ->map(fn ($item) => $item->post->vegetable?->name)
                ->filter()
                ->unique()
                ->implode(', ');

            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'no_scheduled_date',
                title: "{$count} active ".str('item')->plural($count).' have no transaction date',
                body: "Your {$names} ".str('demand')->plural($count)." won't appear in farmer date-filtered searches. Add a transaction date.",
            );
        }

        // ── 4. No active demands (info) ───────────────────────────────────────

        if ($ongoingItems->isEmpty()) {
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'info',
                type: 'no_active_demands',
                title: 'You have no active demand listings',
                body: 'Post your sourcing needs in My Demands to become visible to farmers.',
            );
        }

        // ── 5. Low fulfillment rate (info) ────────────────────────────────────

        $closed = $closedItems->count();

        if ($closed >= 5) {
            $fulfilled = $closedItems
                ->filter(fn ($item) => $item->status === PostItemStatus::Fulfilled)
                ->count();

            $rate = $fulfilled / $closed;

            if ($rate < 0.5) {
                $pct = round($rate * 100);
                $recs[] = new DealerDashboardRecommendationDTO(
                    severity: 'info',
                    type: 'low_fulfillment_rate',
                    title: "Your fulfillment rate is {$pct}%",
                    body: "Only {$fulfilled} of your {$closed} closed demand items were fulfilled. Only post when you have a confirmed pickup window.",
                );
            }
        }

        $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($recs, fn ($a, $b) => $order[$a->severity] <=> $order[$b->severity]);

        return $recs;
    }
}
