<?php

namespace App\Services\Product;

use App\DTOs\Product\VarietyAnalyticsDTO;
use App\DTOs\Product\VarietyRecommendationDTO;
use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\RecommendationSeverity;
use App\Enums\Analytics\VarietyViewerRole;
use App\Models\Product\Variety;

class VarietyAnalyticsService
{
    /**
     * Ratio thresholds for supply/demand imbalance classification.
     * Positive = oversupply, negative = undersupply.
     */
    private const float OVERSUPPLY_THRESHOLD = 0.20;

    private const float UNDERSUPPLY_THRESHOLD = -0.20;

    /**
     * Fulfillment rate below which we flag a mismatch warning.
     */
    private const float LOW_FULFILLMENT_THRESHOLD = 0.50;

    /**
     * Price momentum % bounds for signal rules.
     */
    private const float PRICE_SURGE_THRESHOLD = 5.0;

    private const float PRICE_DROP_THRESHOLD = -5.0;

    /**
     * High fulfillment rate used in conjunction with positive price momentum
     * to fire the "strong market signal" info recommendation.
     */
    private const float HIGH_FULFILLMENT_THRESHOLD = 0.70;

    /**
     * Price staleness threshold in weeks.
     */
    private const float STALE_WEEKS_THRESHOLD = 4.0;

    /**
     * MoM decline percentage that triggers a declining-volume notice.
     */
    private const float SUPPLY_DECLINE_THRESHOLD = -20.0;

    // -------------------------------------------------------------------------

    /**
     * Entry point. All inputs are already resolved by VarietyService::show().
     * No DB calls here — pure computation only.
     *
     * @param  array<int, array{
     *     month: string,
     *     label: string,
     *     supply_archived_kg: float,
     *     supply_fulfilled_kg: float,
     *     demand_archived_kg: float,
     *     demand_fulfilled_kg: float,
     * }> $monthlyActivity  12-entry rolling array from VarietyActivityService
     */
    public function compute(
        Variety $variety,
        array $monthlyActivity,
        VarietyViewerRole $role,
    ): VarietyAnalyticsDTO {
        // Last 3 complete months — skip index 11 (current, potentially in-progress).
        // array_slice offset -4 length 3 → indices 8, 9, 10 of 12 total.
        $completeMonths = array_slice($monthlyActivity, -4, 3);

        $ratio = $this->computeImbalanceRatio($completeMonths);
        $band = $this->classifyBand($ratio);
        $supplyFulfillment = $this->computeFulfillmentRate($completeMonths, 'supply');
        $demandFulfillment = $this->computeFulfillmentRate($completeMonths, 'demand');

        [$priceMomentumPct, $priceWeeksStale] = $this->computePriceMetrics($variety);
        [$supplyMomPct, $demandMomPct] = $this->computeVolumeMonthOverMonth($monthlyActivity);

        $recommendations = $this->buildRecommendations(
            band: $band,
            supplyFulfillment: $supplyFulfillment,
            demandFulfillment: $demandFulfillment,
            priceMomentumPct: $priceMomentumPct,
            priceWeeksStale: $priceWeeksStale,
            supplyMomPct: $supplyMomPct,
            role: $role,
        );

        return new VarietyAnalyticsDTO(
            supply_demand_ratio: $ratio,
            imbalance_band: $band,
            supply_fulfillment_rate: $supplyFulfillment,
            demand_fulfillment_rate: $demandFulfillment,
            price_momentum_pct: $priceMomentumPct,
            price_weeks_stale: $priceWeeksStale,
            supply_volume_mom_pct: $supplyMomPct,
            demand_volume_mom_pct: $demandMomPct,
            recommendations: $recommendations,
        );
    }

    // -------------------------------------------------------------------------
    // Metric computation — private, individually unit-testable if extracted
    // -------------------------------------------------------------------------

    /**
     * Computes the supply/demand ratio over the given month windows.
     *
     * ratio = (avg_supply_kg - avg_demand_kg) / max(avg_demand_kg, 1)
     *
     * > 0 → more supply than demand
     * < 0 → more demand than supply
     */
    private function computeImbalanceRatio(array $months): float
    {
        if (empty($months)) {
            return 0.0;
        }

        $totalSupply = array_sum(array_map(
            fn (array $m) => $m['supply_fulfilled_kg'] + $m['supply_archived_kg'],
            $months,
        ));

        $totalDemand = array_sum(array_map(
            fn (array $m) => $m['demand_fulfilled_kg'] + $m['demand_archived_kg'],
            $months,
        ));

        $count = count($months);
        $avgSupply = $totalSupply / $count;
        $avgDemand = $totalDemand / $count;

        return round(($avgSupply - $avgDemand) / max($avgDemand, 1.0), 4);
    }

    private function classifyBand(float $ratio): ImbalanceBand
    {
        return match (true) {
            $ratio > self::OVERSUPPLY_THRESHOLD => ImbalanceBand::Oversupply,
            $ratio < self::UNDERSUPPLY_THRESHOLD => ImbalanceBand::Undersupply,
            default => ImbalanceBand::Balanced,
        };
    }

    /**
     * Fulfillment rate = fulfilled_kg / (fulfilled_kg + archived_kg) over all months.
     * Returns null when there is no volume in the window (avoids division by zero noise).
     *
     * @param  'supply'|'demand'  $type
     */
    private function computeFulfillmentRate(array $months, string $type): ?float
    {
        $fulfilled = (float) array_sum(array_map(
            fn (array $m) => $m["{$type}_fulfilled_kg"],
            $months,
        ));

        $archived = (float) array_sum(array_map(
            fn (array $m) => $m["{$type}_archived_kg"],
            $months,
        ));

        $total = $fulfilled + $archived;

        return $total > 0.0 ? round($fulfilled / $total, 4) : null;
    }

    /**
     * Returns [priceMomentumPct, weeksStale].
     *
     * priceMomentumPct — % change from oldest to newest in recentPrices.
     *                    Null if fewer than 2 price records exist.
     * weeksStale       — weeks since latestPrice was recorded.
     *                    Null if no price has ever been set.
     *
     * @return array{?float, ?float}
     */
    private function computePriceMetrics(Variety $variety): array
    {
        $prices = $variety->recentPrices->sortBy('recorded_at')->values();

        $momentumPct = null;

        if ($prices->count() >= 2) {
            $oldest = (float) $prices->first()->price_max;
            $newest = (float) $prices->last()->price_max;

            if ($oldest > 0.0) {
                $momentumPct = round((($newest - $oldest) / $oldest) * 100, 2);
            }
        }

        $weeksStale = $variety->latestPrice !== null
            ? round($variety->latestPrice->recorded_at->diffInDays(now()) / 7, 1)
            : null;

        return [$momentumPct, $weeksStale];
    }

    /**
     * Month-over-month volume comparison.
     * Compares the last complete month (index -2) against the one before it (index -3).
     *
     * Returns [supplyMomPct, demandMomPct]. Either value is null when the
     * prior month had zero volume (no meaningful base to compute from).
     *
     * @return array{?float, ?float}
     */
    private function computeVolumeMonthOverMonth(array $monthlyActivity): array
    {
        $count = count($monthlyActivity);

        $lastMonth = $monthlyActivity[$count - 2] ?? null;
        $prevMonth = $monthlyActivity[$count - 3] ?? null;

        if ($lastMonth === null || $prevMonth === null) {
            return [null, null];
        }

        $lastSupply = $lastMonth['supply_fulfilled_kg'] + $lastMonth['supply_archived_kg'];
        $prevSupply = $prevMonth['supply_fulfilled_kg'] + $prevMonth['supply_archived_kg'];
        $lastDemand = $lastMonth['demand_fulfilled_kg'] + $lastMonth['demand_archived_kg'];
        $prevDemand = $prevMonth['demand_fulfilled_kg'] + $prevMonth['demand_archived_kg'];

        $supplyMom = $prevSupply > 0.0
            ? round((($lastSupply - $prevSupply) / $prevSupply) * 100, 2)
            : null;

        $demandMom = $prevDemand > 0.0
            ? round((($lastDemand - $prevDemand) / $prevDemand) * 100, 2)
            : null;

        return [$supplyMom, $demandMom];
    }

    // -------------------------------------------------------------------------
    // Recommendations — rule evaluation, role-aware, sorted by severity
    // -------------------------------------------------------------------------

    /**
     * @return VarietyRecommendationDTO[]
     */
    private function buildRecommendations(
        ImbalanceBand $band,
        ?float $supplyFulfillment,
        ?float $demandFulfillment,
        ?float $priceMomentumPct,
        ?float $priceWeeksStale,
        ?float $supplyMomPct,
        VarietyViewerRole $role,
    ): array {
        $recs = [];

        // ── Critical ──────────────────────────────────────────────────────────

        // Oversupply compounding with falling prices → saturation risk
        if (
            $band === ImbalanceBand::Oversupply
            && $priceMomentumPct !== null
            && $priceMomentumPct < self::PRICE_DROP_THRESHOLD
        ) {
            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Critical,
                type: 'saturation_risk',
                title: 'Market Saturation Risk',
                body: 'Supply significantly exceeds demand while prices are falling. '
                    .'Coordinate delivery timing across farmers to avoid further price pressure.',
            );
        }

        // ── Warning ───────────────────────────────────────────────────────────

        // Undersupply → opportunity signal (role-differentiated messaging)
        if ($band === ImbalanceBand::Undersupply) {
            $body = match ($role) {
                VarietyViewerRole::Admin => 'Dealer demand is outpacing available supply. Consider prompting more farmers to post.',
                VarietyViewerRole::Marketplace => 'Buyers are actively looking for this variety. Good time to post your available harvest.',
            };

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'supply_opportunity',
                title: 'Unfulfilled Dealer Demand',
                body: $body,
            );
        }

        // High supply archive rate → price or timing mismatch
        if (
            $supplyFulfillment !== null
            && $supplyFulfillment < self::LOW_FULFILLMENT_THRESHOLD
        ) {
            $archivePct = (int) round((1 - $supplyFulfillment) * 100);

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'high_supply_archive_rate',
                title: 'High Supply Archive Rate',
                body: "{$archivePct}% of supply posts over the last 3 months archived without a match. "
                    .'This typically indicates a price or delivery timing mismatch with buyers.',
            );
        }

        // Low demand fulfillment → buyers not finding sufficient supply
        if (
            $demandFulfillment !== null
            && $demandFulfillment < self::LOW_FULFILLMENT_THRESHOLD
        ) {
            $archivePct = (int) round((1 - $demandFulfillment) * 100);

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'high_demand_archive_rate',
                title: 'Low Demand Fulfillment',
                body: "{$archivePct}% of demand posts expired unfulfilled over the last 3 months. "
                    .'Dealers are not finding adequate supply to match their requirements.',
            );
        }

        // Stale market price — admin only, surfacing an operational gap
        if (
            $role === VarietyViewerRole::Admin
            && $priceWeeksStale !== null
            && $priceWeeksStale > self::STALE_WEEKS_THRESHOLD
        ) {
            $daysStale = (int) round($priceWeeksStale * 7);

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'stale_price',
                title: 'Market Price Outdated',
                body: "The recorded market price has not been updated in {$daysStale} days. "
                    .'Current listings may not reflect actual trading value.',
            );
        }

        // ── Info ──────────────────────────────────────────────────────────────

        // Strong market signal — healthy fulfillment + rising price + no oversupply
        if (
            $band !== ImbalanceBand::Oversupply
            && $priceMomentumPct !== null
            && $priceMomentumPct > self::PRICE_SURGE_THRESHOLD
            && $supplyFulfillment !== null
            && $supplyFulfillment >= self::HIGH_FULFILLMENT_THRESHOLD
        ) {
            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Info,
                type: 'strong_market_signal',
                title: 'Strong Market Activity',
                body: 'High supply fulfillment rates combined with rising prices indicate healthy buyer interest and active trading.',
            );
        }

        // Declining supply volume MoM — early warning before it affects fulfillment
        if ($supplyMomPct !== null && $supplyMomPct < self::SUPPLY_DECLINE_THRESHOLD) {
            $dropPct = (int) round(abs($supplyMomPct));

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Info,
                type: 'declining_supply_volume',
                title: 'Supply Volume Declining',
                body: "Supply volume dropped {$dropPct}% compared to last month. "
                    .'Monitor whether this is seasonal or signals a structural reduction.',
            );
        }

        // Sort: critical → warning → info
        usort(
            $recs,
            fn (VarietyRecommendationDTO $a, VarietyRecommendationDTO $b) => $this->severityOrder($a->severity) <=> $this->severityOrder($b->severity),
        );

        return $recs;
    }

    private function severityOrder(RecommendationSeverity $severity): int
    {
        return match ($severity) {
            RecommendationSeverity::Critical => 0,
            RecommendationSeverity::Warning => 1,
            RecommendationSeverity::Info => 2,
        };
    }
}
