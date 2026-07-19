<?php

namespace App\Services\Product;

use App\DTOs\Product\VegetableAnalyticsDTO;
use App\DTOs\Product\VegetableForecastDTO;
use App\DTOs\Product\VegetableRecommendationDTO;
use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\RecommendationSeverity;
use App\Enums\Analytics\VegetableViewerRole;
use Illuminate\Support\Collection;

class VegetableAnalyticsService
{
    private const float OVERSUPPLY_THRESHOLD = 0.20;

    private const float UNDERSUPPLY_THRESHOLD = -0.20;

    private const float LOW_FULFILLMENT_THRESHOLD = 0.50;

    private const float SUPPLY_DECLINE_THRESHOLD = -20.0;

    private const float TREND_FLOOR = 0.60;

    private const float TREND_CEIL = 1.40;

    private const int MIN_MONTHS_FOR_TREND = 12;

    private const int MIN_MONTHS_FOR_FORECAST = 12;

    private const int CONFIDENCE_ESTABLISHED_MONTHS = 36;

    private const int CONFIDENCE_STRONG_MONTHS = 60;

    public function __construct(
        private PlatformActivityService $platformActivity,
    ) {}

    public function compute(
        array $monthlyActivity,
        VegetableViewerRole $role,
        array $extendedHistory = [],
    ): array {
        $completeMonths = array_slice($monthlyActivity, -4, 3);

        $ratio = $this->computeImbalanceRatio($completeMonths);
        $band = $this->classifyBand($ratio);
        $supplyFulfillment = $this->computeFulfillmentRate($completeMonths, 'supply');
        $demandFulfillment = $this->computeFulfillmentRate($completeMonths, 'demand');

        [$supplyMomPct, $demandMomPct] = $this->computeVolumeMonthOverMonth($monthlyActivity);

        $recommendations = $this->buildRecommendations(
            band: $band,
            supplyFulfillment: $supplyFulfillment,
            demandFulfillment: $demandFulfillment,
            supplyMomPct: $supplyMomPct,
            role: $role,
        );

        $forecastSource = $extendedHistory ?: $monthlyActivity;
        $monthsObserved = $this->countRealMonths($forecastSource);
        $forecast = $this->computeForecast($forecastSource);

        return [
            'analytics' => new VegetableAnalyticsDTO(
                supply_demand_ratio: $ratio,
                imbalance_band: $band,
                supply_fulfillment_rate: $supplyFulfillment,
                demand_fulfillment_rate: $demandFulfillment,
                supply_volume_mom_pct: $supplyMomPct,
                demand_volume_mom_pct: $demandMomPct,
                recommendations: $recommendations,
            ),
            'forecast' => new VegetableForecastDTO(
                months_of_history: $monthsObserved,
                forecast_confidence: $this->forecastConfidence($monthsObserved),
                forecast: $forecast,
            ),
        ];
    }

    private function countRealMonths(array $history): int
    {
        $currentMonthKey = now()->format('Y-m');

        return count(array_filter(
            $history,
            fn ($e) => $e['month'] !== $currentMonthKey && ($e['has_data'] ?? true),
        ));
    }

    private function forecastConfidence(int $monthsObserved): string
    {
        return match (true) {
            $monthsObserved < self::MIN_MONTHS_FOR_FORECAST => 'insufficient',
            $monthsObserved >= self::CONFIDENCE_STRONG_MONTHS => 'strong',
            $monthsObserved >= self::CONFIDENCE_ESTABLISHED_MONTHS => 'established',
            default => 'developing',
        };
    }

    // ── Forecast, normalized by active-user counts ──────────────────────────────────────────────────────────────

    /**
     * 6-month forward forecast derived from 5-year seasonal history.
     */
    private function computeForecast(array $history): array
    {
        $currentMonthKey = now()->format('Y-m');

        $realHistory = array_values(array_filter(
            $history,
            fn ($e) => $e['month'] !== $currentMonthKey && ($e['has_data'] ?? true),
        ));

        $realCount = count($realHistory);

        if ($realCount < self::MIN_MONTHS_FOR_FORECAST) {
            return [];
        }

        $activeCounts = $this->platformActivity->monthlyActiveCounts();

        // ── Seasonal baseline, converted to per-capita so headcount growth
        // between years doesn't inflate later years' weighting. ──────────────
        $byCalendarMonth = [];

        foreach ($realHistory as $row) {
            $calMonth = (int) substr($row['month'], 5, 2);
            $year = (int) substr($row['month'], 0, 4);

            [$farmers, $dealers] = $this->activeCountsFor($row['month'], $activeCounts);

            $byCalendarMonth[$calMonth][] = [
                'year' => $year,
                'supply_fulfilled_per_capita' => $row['supply_fulfilled_kg'] / $farmers,
                'supply_expired_per_capita' => $row['supply_expired_kg'] / $farmers,
                'demand_fulfilled_per_capita' => $row['demand_fulfilled_kg'] / $dealers,
                'demand_expired_per_capita' => $row['demand_expired_kg'] / $dealers,
            ];
        }

        // ── Trend, computed on per-capita sums — this is the actual fix.
        // A vegetable's genuine demand trend is isolated from "we signed up
        // 400 more farmers this quarter." ────────────────────────────────────
        $supplyMonthlyGrowth = 1.0;
        $demandMonthlyGrowth = 1.0;

        if ($realCount >= self::MIN_MONTHS_FOR_TREND) {
            $recentSlice = array_slice($realHistory, -6);
            $priorSlice = array_slice($realHistory, $realCount - 12, 6);

            $recentSupply = $this->perCapitaVolumeSum($recentSlice, 'supply', 'active_farmers', $activeCounts);
            $priorSupply = $this->perCapitaVolumeSum($priorSlice, 'supply', 'active_farmers', $activeCounts);
            $recentDemand = $this->perCapitaVolumeSum($recentSlice, 'demand', 'active_dealers', $activeCounts);
            $priorDemand = $this->perCapitaVolumeSum($priorSlice, 'demand', 'active_dealers', $activeCounts);

            $supplyTrend = $priorSupply > 0.0
                ? max(self::TREND_FLOOR, min(self::TREND_CEIL, $recentSupply / $priorSupply))
                : 1.0;
            $demandTrend = $priorDemand > 0.0
                ? max(self::TREND_FLOOR, min(self::TREND_CEIL, $recentDemand / $priorDemand))
                : 1.0;

            $supplyMonthlyGrowth = $supplyTrend ** (1 / 6);
            $demandMonthlyGrowth = $demandTrend ** (1 / 6);
        }

        // ── Snapshot of CURRENT active counts — used to convert the forecasted
        // per-capita figures back into absolute kg. We deliberately do NOT
        // compound future headcount growth here: for a 6-month horizon,
        // assuming activity stays near its current level is a far safer bet
        // than extrapolating whatever growth rate happened historically. ─────
        $latestMonth = end($realHistory)['month'] ?? null;
        [$currentFarmers, $currentDealers] = $latestMonth
            ? $this->activeCountsFor($latestMonth, $activeCounts)
            : [1, 1];

        $forecast = [];
        $weights = [3, 2, 1];

        for ($i = 1; $i <= 6; $i++) {
            $futureDate = now()->startOfMonth()->addMonths($i);
            $calMonth = (int) $futureDate->month;
            $entries = $byCalendarMonth[$calMonth] ?? [];

            if (empty($entries)) {
                continue;
            }

            usort($entries, fn ($a, $b) => $b['year'] <=> $a['year']);

            $totalWeight = 0;
            $supplyFulfilledPC = 0.0;
            $supplyExpiredPC = 0.0;
            $demandFulfilledPC = 0.0;
            $demandExpiredPC = 0.0;

            foreach (array_slice($entries, 0, 3) as $idx => $entry) {
                $w = $weights[$idx] ?? 1;
                $totalWeight += $w;
                $supplyFulfilledPC += $entry['supply_fulfilled_per_capita'] * $w;
                $supplyExpiredPC += $entry['supply_expired_per_capita'] * $w;
                $demandFulfilledPC += $entry['demand_fulfilled_per_capita'] * $w;
                $demandExpiredPC += $entry['demand_expired_per_capita'] * $w;
            }

            $supplyFactor = $supplyMonthlyGrowth ** $i;
            $demandFactor = $demandMonthlyGrowth ** $i;

            $forecast[] = [
                'month' => $futureDate->format('Y-m'),
                'label' => $futureDate->format('M Y'),
                'supply_fulfilled_kg' => max(0.0, round(($supplyFulfilledPC / $totalWeight) * $supplyFactor * $currentFarmers, 2)),
                'supply_expired_kg' => max(0.0, round(($supplyExpiredPC / $totalWeight) * $supplyFactor * $currentFarmers, 2)),
                'demand_fulfilled_kg' => max(0.0, round(($demandFulfilledPC / $totalWeight) * $demandFactor * $currentDealers, 2)),
                'demand_expired_kg' => max(0.0, round(($demandExpiredPC / $totalWeight) * $demandFactor * $currentDealers, 2)),
            ];
        }

        return $forecast;
    }

    /** @return array{0: int, 1: int} [active_farmers, active_dealers], floored at 1 to avoid divide-by-zero */
    private function activeCountsFor(string $monthKey, Collection $activeCounts): array
    {
        $row = $activeCounts->get($monthKey);

        return [
            max(1, (int) ($row->active_farmers ?? 1)),
            max(1, (int) ($row->active_dealers ?? 1)),
        ];
    }

    /** @param 'supply'|'demand' $role */
    private function perCapitaVolumeSum(array $slice, string $role, string $countColumn, Collection $activeCounts): float
    {
        $sum = 0.0;

        foreach ($slice as $row) {
            $count = max(1, (int) ($activeCounts->get($row['month'])->{$countColumn} ?? 1));
            $sum += ($row["{$role}_fulfilled_kg"] + $row["{$role}_expired_kg"]) / $count;
        }

        return $sum;
    }

    // ── Existing analytics (unchanged) ───────────────────────────────────────

    private function computeImbalanceRatio(array $months): float
    {
        if (empty($months)) {
            return 0.0;
        }

        $totalSupply = array_sum(array_map(
            fn (array $m) => $m['supply_fulfilled_kg'] + $m['supply_expired_kg'],
            $months,
        ));

        $totalDemand = array_sum(array_map(
            fn (array $m) => $m['demand_fulfilled_kg'] + $m['demand_expired_kg'],
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

    /** @param 'supply'|'demand' $type */
    private function computeFulfillmentRate(array $months, string $type): ?float
    {
        $fulfilled = (float) array_sum(array_map(
            fn (array $m) => $m["{$type}_fulfilled_kg"],
            $months,
        ));

        $expired = (float) array_sum(array_map(
            fn (array $m) => $m["{$type}_expired_kg"],
            $months,
        ));

        $total = $fulfilled + $expired;

        return $total > 0.0 ? round($fulfilled / $total, 4) : null;
    }

    /** @return array{?float, ?float} */
    private function computeVolumeMonthOverMonth(array $monthlyActivity): array
    {
        $count = count($monthlyActivity);

        $lastMonth = $monthlyActivity[$count - 2] ?? null;
        $prevMonth = $monthlyActivity[$count - 3] ?? null;

        if ($lastMonth === null || $prevMonth === null) {
            return [null, null];
        }

        $lastSupply = $lastMonth['supply_fulfilled_kg'] + $lastMonth['supply_expired_kg'];
        $prevSupply = $prevMonth['supply_fulfilled_kg'] + $prevMonth['supply_expired_kg'];
        $lastDemand = $lastMonth['demand_fulfilled_kg'] + $lastMonth['demand_expired_kg'];
        $prevDemand = $prevMonth['demand_fulfilled_kg'] + $prevMonth['demand_expired_kg'];

        $supplyMom = $prevSupply > 0.0
            ? round((($lastSupply - $prevSupply) / $prevSupply) * 100, 2)
            : null;

        $demandMom = $prevDemand > 0.0
            ? round((($lastDemand - $prevDemand) / $prevDemand) * 100, 2)
            : null;

        return [$supplyMom, $demandMom];
    }

    /** @return VegetableRecommendationDTO[] */
    private function buildRecommendations(
        ImbalanceBand $band,
        ?float $supplyFulfillment,
        ?float $demandFulfillment,
        ?float $supplyMomPct,
        VegetableViewerRole $role,
    ): array {
        $recs = [];

        if ($band === ImbalanceBand::Oversupply) {
            $body = match ($role) {
                VegetableViewerRole::Admin => 'Supply is currently exceeding dealer demand. Consider highlighting this variety to dealers or slowing farmer intake.',
                VegetableViewerRole::Farmer => 'This variety is currently oversupplied. Consider delaying your next harvest posting or choosing an under-demanded slot.',
                VegetableViewerRole::Dealer => 'There is surplus supply for this variety right now — a good time to increase your order to help absorb it before it expires.',
            };

            $recs[] = new VegetableRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'oversupply_opportunity',
                title: 'Unmatched Farmer Supply',
                body: $body,
            );
        }

        // ── Undersupply: actionable by everyone, but the action differs per role ──
        if ($band === ImbalanceBand::Undersupply) {
            $body = match ($role) {
                VegetableViewerRole::Admin => 'Dealer demand is outpacing available supply. Consider prompting more farmers to post.',
                VegetableViewerRole::Farmer => 'Buyers are actively looking for this variety. Good time to schedule your available harvest.',
                VegetableViewerRole::Dealer => 'Supply is currently scarce for this variety. Expect longer wait times, or consider adjusting your demanded quantity.',
            };

            $recs[] = new VegetableRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'supply_opportunity',
                title: 'Unfulfilled Dealer Demand',
                body: $body,
            );
        }

        // ── Supply expiry: only Farmer and Admin can act on it. A dealer cannot
        if (
            $supplyFulfillment !== null
            && $supplyFulfillment < self::LOW_FULFILLMENT_THRESHOLD
            && $role !== VegetableViewerRole::Dealer
        ) {
            $expiredPct = (int) round((1 - $supplyFulfillment) * 100);

            $recs[] = new VegetableRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'high_supply_expiry_rate',
                title: 'High Supply Expiry Rate',
                body: "{$expiredPct}% of supply posts over the last 3 months expired without a match. "
                    .'This typically indicates a delivery timing mismatch with buyers.',
            );
        }

        // ── Demand expiry: only Dealer and Admin can act on it. A farmer has no
        // lever over dealer demand posting — this is the mirror of the block above. ──
        if (
            $demandFulfillment !== null
            && $demandFulfillment < self::LOW_FULFILLMENT_THRESHOLD
            && $role !== VegetableViewerRole::Farmer
        ) {
            $expiredPct = (int) round((1 - $demandFulfillment) * 100);

            $recs[] = new VegetableRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'high_demand_expiry_rate',
                title: 'Low Demand Fulfillment',
                body: "{$expiredPct}% of demand posts expired unfulfilled over the last 3 months. "
                    .'Dealers are not finding adequate supply to match their requirements.',
            );
        }

        // ── Declining supply volume: deliberately shown to ALL roles, unlike the
        // two blocks above. This is not an oversight — it's a different kind of recommendation
        if ($supplyMomPct !== null && $supplyMomPct < self::SUPPLY_DECLINE_THRESHOLD) {
            $dropPct = (int) round(abs($supplyMomPct));

            $recs[] = new VegetableRecommendationDTO(
                severity: RecommendationSeverity::Info,
                type: 'declining_supply_volume',
                title: 'Supply Volume Declining',
                body: "Supply volume dropped {$dropPct}% compared to last month. "
                    .'Monitor whether this is seasonal or signals a structural reduction.',
            );
        }

        usort(
            $recs,
            fn (VegetableRecommendationDTO $a, VegetableRecommendationDTO $b) => $this->severityOrder($a->severity) <=> $this->severityOrder($b->severity),
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
