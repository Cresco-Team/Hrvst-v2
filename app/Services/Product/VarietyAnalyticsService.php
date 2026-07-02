<?php

namespace App\Services\Product;

use App\DTOs\Product\VarietyAnalyticsDTO;
use App\DTOs\Product\VarietyRecommendationDTO;
use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\RecommendationSeverity;
use App\Enums\Analytics\VarietyViewerRole;

class VarietyAnalyticsService
{
    private const float OVERSUPPLY_THRESHOLD  = 0.20;
    private const float UNDERSUPPLY_THRESHOLD = -0.20;
    private const float LOW_FULFILLMENT_THRESHOLD = 0.50;
    private const float SUPPLY_DECLINE_THRESHOLD  = -20.0;

    // ±40% cap prevents runaway extrapolation from anomalous recent months
    private const float TREND_FLOOR = 0.60;
    private const float TREND_CEIL  = 1.40;

    // Minimum real (non-padded) months required before we trust a trend ratio.
    // Below this we fall back to a pure seasonal average (trend multiplier = 1.0).
    private const int MIN_MONTHS_FOR_TREND = 12;

    public function compute(
        array $monthlyActivity,
        VarietyViewerRole $role,
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
     * 6-month forward forecast using weighted seasonal average + compound trend.
     *
     * Root cause of the previous "demand > supply" inversion:
     * buildMonthlyActivity(months: 36) zero-pads the 24 months before the DB
     * history begins. Those phantom zeros contaminate both the seasonal
     * baseline (halving values via weighted average) and the trend ratio
     * (inflating it by treating zero months as legitimate low-activity months).
     *
     * Fix: filter the 36-month history to `has_data === true` entries only,
     * then build seasonal baselines and compute trend from real rows only.
     *
     * @return array<int, array{month: string, label: string, supply_kg: float, demand_kg: float}>
     */
    private function computeForecast(array $history): array
    {
        $currentMonthKey = now()->format('Y-m');

        // ── 1. Real months only — exclude current (partial) AND zero-padded gaps ─
        $realHistory = array_values(array_filter(
            $history,
            fn ($e) => $e['month'] !== $currentMonthKey && ($e['has_data'] ?? true),
        ));

        $realCount = count($realHistory);

        if ($realCount < 3) {
            return [];
        }

        // ── 2. Seasonal baseline — group real months by calendar month ──────────
        $byCalendarMonth = [];

        foreach ($realHistory as $entry) {
            $calMonth = (int) substr($entry['month'], 5, 2);
            $year     = (int) substr($entry['month'], 0, 4);

            $byCalendarMonth[$calMonth][] = [
                'year'      => $year,
                'supply_kg' => $entry['supply_fulfilled_kg'] + $entry['supply_expired_kg'],
                'demand_kg' => $entry['demand_fulfilled_kg'] + $entry['demand_expired_kg'],
            ];
        }

        // ── 3. Trend ratio — only apply when enough real history exists ─────────
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

        // ── 4. Generate 6 forecast months ──────────────────────────────────────
        $forecast = [];
        $weights  = [3, 2, 1]; // most-recent year → highest weight

        for ($i = 1; $i <= 6; $i++) {
            $futureDate = now()->startOfMonth()->addMonths($i);
            $calMonth   = (int) $futureDate->month;

            $entries = $byCalendarMonth[$calMonth] ?? [];

            if (empty($entries)) {
                continue; // no real historical data for this calendar month
            }

            usort($entries, fn ($a, $b) => $b['year'] <=> $a['year']);

            $totalWeight = 0;
            $supplySum   = 0.0;
            $demandSum   = 0.0;

            foreach (array_slice($entries, 0, 3) as $idx => $entry) {
                $w           = $weights[$idx] ?? 1;
                $totalWeight += $w;
                $supplySum   += $entry['supply_kg'] * $w;
                $demandSum   += $entry['demand_kg'] * $w;
            }

            $baseSupply = $supplySum / $totalWeight;
            $baseDemand = $demandSum / $totalWeight;

            $forecast[] = [
                'month'      => $futureDate->format('Y-m'),
                'label'      => $futureDate->format('M Y'),
                'supply_kg'  => max(0.0, round($baseSupply * ($supplyMonthlyGrowth ** $i), 2)),
                'demand_kg'  => max(0.0, round($baseDemand * ($demandMonthlyGrowth ** $i), 2)),
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
        VarietyViewerRole $role,
    ): array {
        $recs = [];

        if ($band === ImbalanceBand::Undersupply) {
            $body = match ($role) {
                VarietyViewerRole::Admin       => 'Dealer demand is outpacing available supply. Consider prompting more farmers to post.',
                VarietyViewerRole::Marketplace => 'Buyers are actively looking for this variety. Good time to post your available harvest.',
            };

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type:     'supply_opportunity',
                title:    'Unfulfilled Dealer Demand',
                body:     $body,
            );
        }

        if ($supplyFulfillment !== null && $supplyFulfillment < self::LOW_FULFILLMENT_THRESHOLD) {
            $expiredPct = (int) round((1 - $supplyFulfillment) * 100);

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type:     'high_supply_expiry_rate',
                title:    'High Supply Expiry Rate',
                body:     "{$expiredPct}% of supply posts over the last 3 months expired without a match. "
                    .'This typically indicates a delivery timing mismatch with buyers.',
            );
        }

        if ($demandFulfillment !== null && $demandFulfillment < self::LOW_FULFILLMENT_THRESHOLD) {
            $expiredPct = (int) round((1 - $demandFulfillment) * 100);

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type:     'high_demand_expiry_rate',
                title:    'Low Demand Fulfillment',
                body:     "{$expiredPct}% of demand posts expired unfulfilled over the last 3 months. "
                    .'Dealers are not finding adequate supply to match their requirements.',
            );
        }

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
