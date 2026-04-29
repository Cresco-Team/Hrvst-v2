<?php

namespace App\Services\Farmer;

use App\DTOs\Farmer\FarmerDashboardRecommendationDTO;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Support\Collection;

class FarmerDashboardService
{
    /**
     * Ongoing supplies with scheduled_date within the next 3 days (inclusive of today).
     */
    public function expiringSupplies(FarmerProfile $profile): Collection
    {
        return $profile->posts()
            ->supply()
            ->ongoing()
            ->whereNotNull('scheduled_date')
            ->whereBetween('scheduled_date', [
                now()->toDateString(),
                now()->addDays(3)->toDateString(),
            ])
            ->with(['vegetable.category', 'media'])
            ->orderBy('scheduled_date')
            ->get();
    }

    /**
     * Prescriptive, actionable recommendations ordered by severity.
     *
     * @return FarmerDashboardRecommendationDTO[]
     */
    public function recommendations(FarmerProfile $profile): array
    {
        $recs = [];

        $ongoingPosts = $profile->posts()
            ->supply()
            ->ongoing()
            ->with(['vegetable'])
            ->get();

        $allPosts = $profile->posts()
            ->supply()
            ->whereIn('status', ['Archived', 'Fulfilled'])
            ->get();

        // ── 1. Expiring tomorrow (critical) ──────────────────────────────────

        $tomorrow = now()->addDay()->toDateString();
        $expiringTomorrow = $ongoingPosts
            ->filter(fn ($p) => $p->scheduled_date?->toDateString() === $tomorrow);

        foreach ($expiringTomorrow as $post) {
            $name = $post->vegetable?->name ?? 'Unknown';
            $recs[] = new FarmerDashboardRecommendationDTO(
                severity: 'critical',
                type: 'expiring_tomorrow',
                title: "Your {$name} listing expires tomorrow",
                body: "This supply ({$post->quantity_kg}kg) will auto-archive in less than 24 hours. Open the listing and either set a new scheduled date to extend it, or archive it manually to keep your record clean.",
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
            $recs[] = new FarmerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'expiring_soon',
                title: "{$count} ".str('supply listing')->plural($count).' expire within 3 days',
                body: "Review {$names} — either set a new delivery date, fulfill them if the deal is closed, or archive them before they auto-expire.",
            );
        }

        // ── 3. Ongoing supplies with no scheduled date (warning) ─────────────

        $unscheduled = $ongoingPosts->filter(fn ($p) => $p->scheduled_date === null);

        if ($unscheduled->isNotEmpty()) {
            $count = $unscheduled->count();
            $names = $unscheduled->map(fn ($p) => $p->vegetable?->name)->filter()->unique()->implode(', ');
            $recs[] = new FarmerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'no_scheduled_date',
                title: "{$count} active ".str('listing')->plural($count).' have no delivery date',
                body: "Your {$names} ".str('listing')->plural($count)." won't appear in dealer date-filtered searches. Add a scheduled date so dealers planning their pickups can find you.",
            );
        }

        // ── 4. No active supplies (info) ──────────────────────────────────────

        if ($ongoingPosts->isEmpty()) {
            $recs[] = new FarmerDashboardRecommendationDTO(
                severity: 'info',
                type: 'no_active_supplies',
                title: 'You have no active supply listings',
                body: 'Post your available harvest in My Supplies to appear in dealer searches and the Marketplace.',
            );
        }

        // ── 5. Low fulfillment rate (info) ────────────────────────────────────

        $closed = $allPosts->count();

        if ($closed >= 5) {
            $fulfilled = $allPosts->where('status', 'Fulfilled')->count();
            $rate = $fulfilled / $closed;

            if ($rate < 0.5) {
                $pct = round($rate * 100);
                $recs[] = new FarmerDashboardRecommendationDTO(
                    severity: 'info',
                    type: 'low_fulfillment_rate',
                    title: "Your fulfillment rate is {$pct}%",
                    body: "Only {$fulfilled} of your {$closed} closed listings were fulfilled — the rest were archived. Only post supplies you're committed to delivering on the stated date.",
                );
            }
        }

        $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($recs, fn ($a, $b) => $order[$a->severity] <=> $order[$b->severity]);

        return $recs;
    }
}
