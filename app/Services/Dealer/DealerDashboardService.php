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
            ->with(['variety.vegetable.category', 'variety.latestPrice'])
            ->orderBy('scheduled_date')
            ->get();
    }

    /**
     * Latest price record for each distinct variety the dealer currently has ongoing demands in.
     */
    public function priceSnapshots(DealerProfile $profile): Collection
    {
        return $profile->posts()
            ->demand()
            ->ongoing()
            ->with(['variety.latestPrice', 'variety.vegetable.category'])
            ->get()
            ->pluck('variety')
            ->filter()
            ->unique('id')
            ->filter(fn ($variety) => $variety->latestPrice !== null)
            ->map(fn ($variety) => [
                'variety_id' => $variety->id,
                'variety_name' => $variety->name,
                'vegetable_name' => $variety->vegetable?->name ?? '—',
                'price_min' => (float) $variety->latestPrice->price_min,
                'price_max' => (float) $variety->latestPrice->price_max,
                'freshness' => $variety->latestPrice->freshness,
                'recorded_at' => $variety->latestPrice->recorded_at->format('M d, Y'),
                'weeks_stale' => (int) now()->diffInWeeks($variety->latestPrice->recorded_at),
            ])
            ->values();
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
            ->with(['variety.latestPrice', 'variety.vegetable'])
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
            $name = "{$post->variety?->vegetable?->name} {$post->variety?->name}";
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'critical',
                type: 'expiring_tomorrow',
                title: "Your {$name} demand expires tomorrow",
                body: "This demand ({$post->quantity_kg}kg @ ₱{$post->offered_price}/kg) will auto-archive in less than 24 hours with no farmer matched. Open the listing, extend the transaction date if you still need this variety, or archive it manually to keep your history clean.",
            );
        }

        // ── 2. Expiring in 2–3 days (warning) ────────────────────────────────

        $inThreeDays = $ongoingPosts->filter(function ($p) use ($tomorrow) {
            $date = $p->scheduled_date?->toDateString();

            return $date !== null && $date > $tomorrow && $date <= now()->addDays(3)->toDateString();
        });

        if ($inThreeDays->isNotEmpty()) {
            $count = $inThreeDays->count();
            $names = $inThreeDays->map(fn ($p) => $p->variety?->name)->filter()->implode(', ');
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'expiring_soon',
                title: "{$count} demand ".str('listing')->plural($count).' expire within 3 days',
                body: "Review {$names} — if you haven't sourced a farmer yet, extend the transaction date or post a higher offered price to attract more suppliers before the deadline.",
            );
        }

        // ── 3. Ongoing demands with no scheduled date (warning) ───────────────

        $unscheduled = $ongoingPosts->filter(fn ($p) => $p->scheduled_date === null);

        if ($unscheduled->isNotEmpty()) {
            $count = $unscheduled->count();
            $names = $unscheduled->map(fn ($p) => $p->variety?->name)->filter()->unique()->implode(', ');
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'warning',
                type: 'no_scheduled_date',
                title: "{$count} active ".str('listing')->plural($count).' have no transaction date',
                body: "Your {$names} ".str('demand')->plural($count)." won't appear in farmer date-filtered marketplace searches. Add a transaction date to each listing so farmers planning their deliveries can match with you.",
            );
        }

        // ── 4. Offered price significantly below market (warning) ─────────────

        foreach ($ongoingPosts as $post) {
            $latestPrice = $post->variety?->latestPrice;

            if ($latestPrice === null) {
                continue;
            }

            $marketMid = ((float) $latestPrice->price_min + (float) $latestPrice->price_max) / 2;
            $offeredPrice = (float) $post->offered_price;

            if ($marketMid > 0 && $offeredPrice < $marketMid * 0.8) {
                $name = "{$post->variety?->vegetable?->name} {$post->variety?->name}";
                $pctBelow = round((1 - $offeredPrice / $marketMid) * 100);
                $recs[] = new DealerDashboardRecommendationDTO(
                    severity: 'warning',
                    type: 'price_below_market',
                    title: "Your {$name} offer is {$pctBelow}% below market",
                    body: "You're offering ₱".number_format($offeredPrice, 2).'/kg but the current market midpoint is ₱'.number_format($marketMid, 2).'/kg. Farmers will prioritise higher-paying buyers. Consider raising your offered price to attract supply before your deadline.',
                );
            }
        }

        // ── 5. Stale market price on an active variety (info) ─────────────────

        $staleVarieties = $ongoingPosts
            ->pluck('variety')
            ->filter()
            ->unique('id')
            ->filter(fn ($v) => $v->latestPrice !== null && now()->diffInWeeks($v->latestPrice->recorded_at) > 4);

        foreach ($staleVarieties as $variety) {
            $weeks = (int) now()->diffInWeeks($variety->latestPrice->recorded_at);
            $name = "{$variety->vegetable?->name} {$variety->name}";
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'info',
                type: 'stale_price',
                title: "Market price for {$name} is {$weeks} weeks old",
                body: "The recorded market price for {$name} hasn't been updated in {$weeks} weeks. Your offered price may no longer reflect real market rates — check the Vegetables page for recent farmer supply listings to gauge actual supply-side prices before your demand expires.",
            );
        }

        // ── 6. No active demands (info) ───────────────────────────────────────

        if ($ongoingPosts->isEmpty()) {
            $recs[] = new DealerDashboardRecommendationDTO(
                severity: 'info',
                type: 'no_active_demands',
                title: 'You have no active demand listings',
                body: 'Post your sourcing needs in My Demands to become visible to farmers. Farmers search active dealer demands when planning their harvest schedule — without a listing, you depend entirely on farmers discovering you in the Marketplace.',
            );
        }

        // ── 7. Low fulfillment rate (info) ────────────────────────────────────

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
                    body: "Only {$fulfilled} of your {$closed} closed demands were fulfilled — the rest auto-archived. Farmers track buyer reliability; a low rate signals you're posting demands you don't follow through on. Only post when you have a confirmed pickup window.",
                );
            }
        }

        // Sort: critical → warning → info
        $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($recs, fn ($a, $b) => $order[$a->severity] <=> $order[$b->severity]);

        return $recs;
    }
}
