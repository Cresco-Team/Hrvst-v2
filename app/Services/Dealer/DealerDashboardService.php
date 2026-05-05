<?php

namespace App\Services\Dealer;

use App\DTOs\Dealer\DealerDashboardRecommendationDTO;
use App\Models\Profiles\DealerProfile;
use Illuminate\Support\Collection;

class DealerDashboardService
{
    /**
     * Ongoing demands with scheduled_date within the next 3 days (inclusive of today).
     */
    public function expiringDemands(DealerProfile $profile): Collection
    {
        return $profile->posts()
            ->demand()
            ->ongoing()
            ->whereNotNull('scheduled_date')
            ->whereBetween('scheduled_date', [
                now()->toDateString(),
                now()->addDays(3)->toDateString(),
            ])
            ->with(['vegetable.category'])
            ->orderBy('scheduled_date')
            ->get();
    }

    /**
     * Prescriptive, actionable recommendations ordered by severity.
     *
     * @return DealerDashboardRecommendationDTO[]
     */
    public function recommendations(DealerProfile $profile): array
    {
        $recs = [];

        $ongoingPosts = $profile->posts()
            ->demand()
            ->ongoing()
            ->with(['vegetable'])
            ->get();

        $allPosts = $profile->posts()
            ->demand()
            ->whereIn('status', ['Archived', 'Fulfilled'])
            ->get();

        // ── 1. Expiring tomorrow (critical) ──────────────────────────────────

        $tomorrow = now()->addDay()->toDateString();
        $expiringTomorrow = $ongoingPosts
            ->filter(fn ($p) => $p->scheduled_date?->toDateString() === $tomorrow);

        foreach ($expiringTomorrow as $post) {
            $name = $post->vegetable?->name ?? 'Unknown';
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'critical',
                type: 'expiring_tomorrow',
                title: "Your {$name} demand expires tomorrow",
                body: "This demand ({$post->quantity_kg}kg) will auto-archive in less than 24 hours with no farmer matched. Extend the transaction date if you still need this variety, or archive it manually.",
            );
        }

        // ── 2. Expiring in 2–3 days (warning) ────────────────────────────────

        $inThreeDays = $ongoingPosts->filter(function ($p) use ($tomorrow) {
            $date = $p->scheduled_date?->toDateString();

            return $date !== null && $date > $tomorrow && $date <= now()->addDays(3)->toDateString();
        });

        if ($inThreeDays->isNotEmpty()) {
            $count = $inThreeDays->count();
            $names = $inThreeDays->map(fn ($p) => $p->vegetable?->name)->filter()->implode(', ');
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'expiring_soon',
                title: "{$count} demand ".str('listing')->plural($count).' expire within 3 days',
                body: "Review {$names} — if you haven't sourced a farmer yet, extend the transaction date before the deadline.",
            );
        }

        // ── 3. Ongoing demands with no scheduled date (warning) ───────────────

        $unscheduled = $ongoingPosts->filter(fn ($p) => $p->scheduled_date === null);

        if ($unscheduled->isNotEmpty()) {
            $count = $unscheduled->count();
            $names = $unscheduled->map(fn ($p) => $p->vegetable?->name)->filter()->unique()->implode(', ');
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'no_scheduled_date',
                title: "{$count} active ".str('listing')->plural($count).' have no transaction date',
                body: "Your {$names} ".str('demand')->plural($count)." won't appear in farmer date-filtered marketplace searches. Add a transaction date to each listing.",
            );
        }

        // ── 4. No active demands (info) ───────────────────────────────────────

        if ($ongoingPosts->isEmpty()) {
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'info',
                type: 'no_active_demands',
                title: 'You have no active demand listings',
                body: 'Post your sourcing needs in My Demands to become visible to farmers.',
            );
        }

        // ── 5. Low fulfillment rate (info) ────────────────────────────────────

        $closed = $allPosts->count();

        if ($closed >= 5) {
            $fulfilled = $allPosts->where('status', 'Fulfilled')->count();
            $rate = $fulfilled / $closed;

            if ($rate < 0.5) {
                $pct = round($rate * 100);
                $recs[] = new DealerDashboardRecommendationDTO(
                    severity: 'info',
                    type: 'low_fulfillment_rate',
                    title: "Your fulfillment rate is {$pct}%",
                    body: "Only {$fulfilled} of your {$closed} closed demands were fulfilled. Only post when you have a confirmed pickup window.",
                );
            }
        }

        $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($recs, fn ($a, $b) => $order[$a->severity] <=> $order[$b->severity]);

        return $recs;
    }
}
