<?php

namespace App\Services\Product;

use App\DTOs\Product\VarietyAnalyticsDTO;
use App\DTOs\Product\VarietyRecommendationDTO;
use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\RecommendationSeverity;
use App\Enums\Analytics\VegetableViewerRole;

class VarietyAnalyticsService
{
    private const float OVERSUPPLY_THRESHOLD  = 0.20;
    private const float UNDERSUPPLY_THRESHOLD = -0.20;
    private const float LOW_FULFILLMENT_THRESHOLD = 0.50;
    private const float SUPPLY_DECLINE_THRESHOLD  = -20.0;

    private const float TREND_FLOOR = 0.60;
    private const float TREND_CEIL  = 1.40;

    private const int MIN_MONTHS_FOR_TREND = 12;

    public function compute(
        array $monthlyActivity,
        VegetableViewerRole $role,
        array $extendedHistory = [],
    ): VarietyAnalyticsDTO {
        $completeMonths = array_slice($monthlyActivity, -4, 3);

        $ratio             = $this->computeImbalanceRatio($completeMonths);
        $band              = $this->classifyBand($ratio);
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

        $forecast = $this->computeForecast($extendedHistory ?: $monthlyActivity);

        return new VarietyAnalyticsDTO(
            supply_demand_ratio:     $ratio,
            imbalance_band:          $band,
            supply_fulfillment_rate: $supplyFulfillment,
            demand_fulfillment_rate: $demandFulfillment,
            supply_volume_mom_pct:   $supplyMomPct,
            demand_volume_mom_pct:   $demandMomPct,
            recommendations:         $recommendations,
            forecast:                $forecast,
        );
    }

    // ── Forecast ──────────────────────────────────────────────────────────────

    /**
     * 6-month forward forecast derived from 3-year seasonal history.
     *
     * @var array<int, array{
     *     month: string,
     *     label: string,
     *     supply_fulfilled_kg: float,
     *     supply_expired_kg: float,
     *     demand_fulfilled_kg: float,
     *     demand_expired_kg: float,
     * }>
     */
    private function computeForecast(array $history): array
    {
        $currentMonthKey = now()->format('Y-m');

        $realHistory = array_values(array_filter(
            $history,
            fn ($e) => $e['month'] !== $currentMonthKey && ($e['has_data'] ?? true),
        ));

        $realCount = count($realHistory);

        if ($realCount < 3) {
            return [];
        }

        // ── Seasonal baseline — keep fulfilled/expired split, don't collapse them ──
        $byCalendarMonth = [];

        foreach ($realHistory as $entry) {
            $calMonth = (int) substr($entry['month'], 5, 2);
            $year     = (int) substr($entry['month'], 0, 4);

            $byCalendarMonth[$calMonth][] = [
                'year'                => $year,
                'supply_fulfilled_kg' => $entry['supply_fulfilled_kg'],
                'supply_expired_kg'   => $entry['supply_expired_kg'],
                'demand_fulfilled_kg' => $entry['demand_fulfilled_kg'],
                'demand_expired_kg'   => $entry['demand_expired_kg'],
            ];
        }

        // ── Trend ratio — unchanged, still computed on totals from raw history ────
        $supplyMonthlyGrowth = 1.0;
        $demandMonthlyGrowth = 1.0;

        if ($realCount >= self::MIN_MONTHS_FOR_TREND) {
            $recentSlice = array_slice($realHistory, -6);
            $priorSlice  = array_slice($realHistory, $realCount - 12, 6);

            $sumVolume = static function (array $slice, string $type): float {
                return (float) array_sum(array_map(
                    fn ($m) => $m["{$type}_fulfilled_kg"] + $m["{$type}_expired_kg"],
                    $slice,
                ));
            };

            $recentSupply = $sumVolume($recentSlice, 'supply');
            $priorSupply  = $sumVolume($priorSlice, 'supply');
            $recentDemand = $sumVolume($recentSlice, 'demand');
            $priorDemand  = $sumVolume($priorSlice, 'demand');

            $supplyTrend = $priorSupply > 0.0
                ? max(self::TREND_FLOOR, min(self::TREND_CEIL, $recentSupply / $priorSupply))
                : 1.0;
            $demandTrend = $priorDemand > 0.0
                ? max(self::TREND_FLOOR, min(self::TREND_CEIL, $recentDemand / $priorDemand))
                : 1.0;

            $supplyMonthlyGrowth = $supplyTrend ** (1 / 6);
            $demandMonthlyGrowth = $demandTrend ** (1 / 6);
        }

        // ── Generate 6 forecast months, now carrying the fulfilled/expired split ──
        $forecast = [];
        $weights  = [3, 2, 1];

        for ($i = 1; $i <= 6; $i++) {
            $futureDate = now()->startOfMonth()->addMonths($i);
            $calMonth   = (int) $futureDate->month;

            $entries = $byCalendarMonth[$calMonth] ?? [];

            if (empty($entries)) {
                continue;
            }

            usort($entries, fn ($a, $b) => $b['year'] <=> $a['year']);

            $totalWeight        = 0;
            $supplyFulfilledSum = 0.0;
            $supplyExpiredSum   = 0.0;
            $demandFulfilledSum = 0.0;
            $demandExpiredSum   = 0.0;

            foreach (array_slice($entries, 0, 3) as $idx => $entry) {
                $w = $weights[$idx] ?? 1;
                $totalWeight        += $w;
                $supplyFulfilledSum += $entry['supply_fulfilled_kg'] * $w;
                $supplyExpiredSum   += $entry['supply_expired_kg'] * $w;
                $demandFulfilledSum += $entry['demand_fulfilled_kg'] * $w;
                $demandExpiredSum   += $entry['demand_expired_kg'] * $w;
            }

            $supplyFactor = $supplyMonthlyGrowth ** $i;
            $demandFactor = $demandMonthlyGrowth ** $i;

            $forecast[] = [
                'month'               => $futureDate->format('Y-m'),
                'label'               => $futureDate->format('M Y'),
                'supply_fulfilled_kg' => max(0.0, round(($supplyFulfilledSum / $totalWeight) * $supplyFactor, 2)),
                'supply_expired_kg'   => max(0.0, round(($supplyExpiredSum / $totalWeight) * $supplyFactor, 2)),
                'demand_fulfilled_kg' => max(0.0, round(($demandFulfilledSum / $totalWeight) * $demandFactor, 2)),
                'demand_expired_kg'   => max(0.0, round(($demandExpiredSum / $totalWeight) * $demandFactor, 2)),
            ];
        }

        return $forecast;
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

        $count     = count($months);
        $avgSupply = $totalSupply / $count;
        $avgDemand = $totalDemand / $count;

        return round(($avgSupply - $avgDemand) / max($avgDemand, 1.0), 4);
    }

    private function classifyBand(float $ratio): ImbalanceBand
    {
        return match (true) {
            $ratio > self::OVERSUPPLY_THRESHOLD  => ImbalanceBand::Oversupply,
            $ratio < self::UNDERSUPPLY_THRESHOLD => ImbalanceBand::Undersupply,
            default                               => ImbalanceBand::Balanced,
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

    /** @return VarietyRecommendationDTO[] */
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
                VegetableViewerRole::Admin  => 'Supply is currently exceeding dealer demand. Consider highlighting this variety to dealers or slowing farmer intake.',
                VegetableViewerRole::Farmer => 'This variety is currently oversupplied. Consider delaying your next harvest posting or choosing an under-demanded slot.',
                VegetableViewerRole::Dealer => 'There is surplus supply for this variety right now — a good time to increase your order to help absorb it before it expires.',
            };
        
            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type:     'oversupply_opportunity',
                title:    'Unmatched Farmer Supply',
                body:     $body,
            );
        }

        // ── Undersupply: actionable by everyone, but the action differs per role ──
        if ($band === ImbalanceBand::Undersupply) {
            $body = match ($role) {
                VegetableViewerRole::Admin  => 'Dealer demand is outpacing available supply. Consider prompting more farmers to post.',
                VegetableViewerRole::Farmer => 'Buyers are actively looking for this variety. Good time to post your available harvest.',
                VegetableViewerRole::Dealer => 'Supply is currently scarce for this variety. Expect longer wait times, or consider adjusting your requested quantity.',
            };

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type:     'supply_opportunity',
                title:    'Unfulfilled Dealer Demand',
                body:     $body,
            );
        }

        // ── Supply expiry: only Farmer and Admin can act on it. A dealer cannot
        // control how much of a farmer's harvest goes unfulfilled — showing them
        // this metric is noise, not insight. ──
        if (
            $supplyFulfillment !== null
            && $supplyFulfillment < self::LOW_FULFILLMENT_THRESHOLD
            && $role !== VegetableViewerRole::Dealer
        ) {
            $expiredPct = (int) round((1 - $supplyFulfillment) * 100);

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type:     'high_supply_expiry_rate',
                title:    'High Supply Expiry Rate',
                body:     "{$expiredPct}% of supply posts over the last 3 months expired without a match. "
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

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type:     'high_demand_expiry_rate',
                title:    'Low Demand Fulfillment',
                body:     "{$expiredPct}% of demand posts expired unfulfilled over the last 3 months. "
                    .'Dealers are not finding adequate supply to match their requirements.',
            );
        }

        // ── Declining supply volume: deliberately shown to ALL roles, unlike the
        // two blocks above. This is not an oversight — it's a different kind of
        // recommendation. The expiry-rate recs are "your posts aren't converting,"
        // which only makes sense to the party whose posts they are. This one is
        // forward-looking market intelligence: supply is shrinking. A dealer needs
        // that signal just as much as a farmer does — it tells them to expect
        // scarcity and adjust their requested quantity or timing *before* it bites
        // them, which is a real, distinct action from anything in the Undersupply
        // block above (that one reacts to a snapshot; this one reacts to a trend).
        // Don't collapse this into the same filtering rule as the other two just
        // for consistency — consistency isn't the goal, correctness per rec is.
        if ($supplyMomPct !== null && $supplyMomPct < self::SUPPLY_DECLINE_THRESHOLD) {
            $dropPct = (int) round(abs($supplyMomPct));

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Info,
                type:     'declining_supply_volume',
                title:    'Supply Volume Declining',
                body:     "Supply volume dropped {$dropPct}% compared to last month. "
                    .'Monitor whether this is seasonal or signals a structural reduction.',
            );
        }

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
            RecommendationSeverity::Warning  => 1,
            RecommendationSeverity::Info     => 2,
        };
    }
}
