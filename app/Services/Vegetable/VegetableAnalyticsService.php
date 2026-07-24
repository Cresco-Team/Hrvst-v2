<?php

namespace App\Services\Vegetable;

use App\DTOs\Product\VegetableAnalyticsDTO;
use App\DTOs\Product\VegetableForecastDTO;
use App\DTOs\Product\VegetableRecommendationDTO;
use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\RecommendationSeverity;
use App\Enums\Analytics\VegetableViewerRole;
use Carbon\Carbon;
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

    /** Trailing window used when same-month-last-year data doesn't exist. */
    private const int EXPECTED_BALANCE_TRAILING_WINDOW = 3;

    /**
     * Schmitt-trigger margin for the watch-alert outlook classification.
     * Without this, a ratio oscillating around the 0.20 threshold (e.g.
     * 0.19 <-> 0.21 week to week) would flip Balanced <-> Oversupply on
     * every evaluation run even though nothing real changed. Applied only
     * relative to the *previous* band, so a genuinely new signal (Balanced
     * crossing into Oversupply for the first time) still fires immediately —
     * only an already-alerted band is sticky.
     */
    private const float HYSTERESIS_MARGIN = 0.05;

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

        $forecastDto = $this->computeForecastOnly($monthlyActivity, $extendedHistory);

        return [
            'analytics' => new VegetableAnalyticsDTO(
                supply_demand_ratio: $ratio,
                imbalance_band: $band,
                supply_fulfillment_rate: $supplyFulfillment,
                demand_fulfillment_rate: $demandFulfillment,
                supply_volume_mom_pct: $supplyMomPct,
                demand_volume_mom_pct: $demandMomPct,
                recommendations: $recommendations,
                expected_balance: $this->computeExpectedBalance($extendedHistory ?: $monthlyActivity),
            ),
            'forecast' => $forecastDto,
        ];
    }

    /**
     * Forecast + confidence only, no recommendations. Use this anywhere you
     * need role-neutral forecast numbers (batch jobs, watch evaluation).
     * Passing a throwaway VegetableViewerRole into compute() just to reach
     * this data is a smell: it silently builds and discards a
     * recommendations array that the role parameter exists to vary.
     */
    public function computeForecastOnly(array $monthlyActivity, array $extendedHistory = []): VegetableForecastDTO
    {
        $forecastSource = $extendedHistory ?: $monthlyActivity;
        $monthsObserved = $this->countRealMonths($forecastSource);
        $forecast = $this->computeForecast($forecastSource);

        return new VegetableForecastDTO(
            months_of_history: $monthsObserved,
            forecast_confidence: $this->forecastConfidence($monthsObserved),
            forecast: $forecast,
        );
    }

    /**
     * Replaces the old "last 3 complete months, current month invisible"
     * Market Balance card with a forward estimate for the current month:
     *
     * 1. Same calendar month last year, if it has real recorded data.
     * 2. Otherwise, the trailing 3 complete months' average.
     * 3. If neither exists, report Balanced with an explanation that says so
     *    — never fabricate a band from nothing.
     *
     * @return array{band: string, explanation: string}
     */
    private function computeExpectedBalance(array $extendedHistory): array
    {
        if (empty($extendedHistory)) {
            return $this->balanceResult(ImbalanceBand::Balanced, 'Not enough data yet.');
        }

        $now = now()->startOfMonth();
        $currentMonthKey = $now->format('Y-m');
        $lastYearKey = $now->copy()->subYear()->format('Y-m');
        $lastYearRow = collect($extendedHistory)->firstWhere('month', $lastYearKey);

        if ($lastYearRow && ($lastYearRow['has_data'] ?? true)) {
            $supplyKg = $lastYearRow['supply_fulfilled_kg'] + $lastYearRow['supply_expired_kg'];
            $demandKg = $lastYearRow['demand_fulfilled_kg'] + $lastYearRow['demand_expired_kg'];
            $sourceLabel = Carbon::createFromFormat('Y-m', $lastYearKey)->format('M Y');

            return $this->balanceResult(
                $this->classifyBalance($supplyKg, $demandKg),
                "Estimated from {$sourceLabel} last year.",
                $supplyKg,
                $demandKg,
                $sourceLabel,
            );
        }

        $trailing = collect($extendedHistory)
            ->filter(fn ($row) => $row['month'] !== $currentMonthKey && ($row['has_data'] ?? true))
            ->sortByDesc('month')
            ->take(self::EXPECTED_BALANCE_TRAILING_WINDOW)
            ->sortBy('month')
            ->values();

        if ($trailing->isEmpty()) {
            return $this->balanceResult(ImbalanceBand::Balanced, 'Not enough data yet.');
        }

        $avgSupply = $trailing->avg(fn ($r) => $r['supply_fulfilled_kg'] + $r['supply_expired_kg']);
        $avgDemand = $trailing->avg(fn ($r) => $r['demand_fulfilled_kg'] + $r['demand_expired_kg']);

        $monthLabels = $trailing->pluck('month')
            ->map(fn ($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'))
            ->implode(', ');

        return $this->balanceResult(
            $this->classifyBalance($avgSupply, $avgDemand),
            "Estimated from the average of {$monthLabels}.",
            $avgSupply,
            $avgDemand,
            $monthLabels,
        );
    }

    /**
     * Single source of truth for the expected_balance array shape. Both the
     * "same month last year" and "trailing average" branches call this instead
     * of hand-rolling their own array literal — keeps the contract in sync and
     * gives the frontend raw supply/demand numbers to render on hover, instead
     * of just a pre-baked sentence.
     *
     * @return array{band: string, explanation: string, computation: array{source_label: string, supply_kg: float, demand_kg: float, diff_pct: ?float}|null}
     */
    private function balanceResult(
        ImbalanceBand $band,
        string $explanation,
        ?float $supplyKg = null,
        ?float $demandKg = null,
        ?string $sourceLabel = null,
    ): array {
        return [
            'band' => $band->value,
            'explanation' => $explanation,
            'computation' => $supplyKg !== null && $demandKg !== null
                ? [
                    'source_label' => $sourceLabel,
                    'supply_kg' => round($supplyKg, 2),
                    'demand_kg' => round($demandKg, 2),
                    'diff_pct' => $demandKg > 0.0
                        ? round((($supplyKg - $demandKg) / $demandKg) * 100, 1)
                        : null,
                ]
                : null,
        ];
    }

    private function classifyBalance(float $supplyKg, float $demandKg): ImbalanceBand
    {
        $ratio = round(($supplyKg - $demandKg) / max($demandKg, 1.0), 4);

        return $this->classifyBand($ratio);
    }

    /**
     * Watch-alert outlook: classifies the *near-term forecast* (not the
     * trailing-3-month historical band used elsewhere) and, when it signals
     * an imbalance, reports how many months out it starts and how long the
     * run of matching months lasts — giving "in the next 2 months, lasting
     * about 3 months" instead of a vague "sometime soon."
     *
     * @param  array<int, array{supply_fulfilled_kg: float, supply_expired_kg: float, demand_fulfilled_kg: float, demand_expired_kg: float}>  $forecast
     * @return array{band: ImbalanceBand, starts_in_months?: int, duration_months?: int, label?: string}|null
     *                                                                                                        null means: do not alert (forecast confidence too low to trust).
     */
    public function forecastOutlook(array $forecast, string $forecastConfidence, ?ImbalanceBand $previousBand): ?array
    {
        if ($forecastConfidence === 'insufficient' || empty($forecast)) {
            return null;
        }

        $ratios = array_map(fn (array $month) => $this->monthRatio($month), $forecast);

        $band = $this->classifyWithHysteresis($ratios[0], $previousBand);

        if ($band === ImbalanceBand::Balanced) {
            return ['band' => $band];
        }

        $startsInMonths = 1;
        $durationMonths = 0;

        foreach ($ratios as $ratio) {
            if ($this->classifyWithHysteresis($ratio, $band) !== $band) {
                break;
            }
            $durationMonths++;
        }

        return [
            'band' => $band,
            'starts_in_months' => $startsInMonths,
            'duration_months' => $durationMonths,
            'label' => $this->outlookLabel($band, $startsInMonths, $durationMonths),
        ];
    }

    private function monthRatio(array $month): float
    {
        $supply = $month['supply_fulfilled_kg'] + $month['supply_expired_kg'];
        $demand = $month['demand_fulfilled_kg'] + $month['demand_expired_kg'];

        return ($supply - $demand) / max($demand, 1.0);
    }

    private function classifyWithHysteresis(float $ratio, ?ImbalanceBand $previous): ImbalanceBand
    {
        $upper = self::OVERSUPPLY_THRESHOLD - ($previous === ImbalanceBand::Oversupply ? self::HYSTERESIS_MARGIN : 0.0);
        $lower = self::UNDERSUPPLY_THRESHOLD + ($previous === ImbalanceBand::Undersupply ? self::HYSTERESIS_MARGIN : 0.0);

        return match (true) {
            $ratio > $upper => ImbalanceBand::Oversupply,
            $ratio < $lower => ImbalanceBand::Undersupply,
            default => ImbalanceBand::Balanced,
        };
    }

    private function outlookLabel(ImbalanceBand $band, int $startsIn, int $duration): string
    {
        $when = $startsIn === 1 ? 'next month' : "in the next {$startsIn} months";
        $span = $duration > 1 ? " for about {$duration} months" : '';

        return $band === ImbalanceBand::Oversupply
            ? "Expected to be oversupplied {$when}{$span}."
            : "Expected shortage {$when}{$span}.";
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
                VegetableViewerRole::Admin => 'Supply is currently exceeding dealer demand. Consider highlighting this vegetable to dealers or slowing farmer intake.',
                VegetableViewerRole::Farmer => 'This vegetable is currently oversupplied. Consider delaying your next harvest posting or choosing an under-demanded slot.',
                VegetableViewerRole::Dealer => 'There is surplus supply for this vegetable right now — a good time to increase your order to help absorb it before it expires.',
            };

            $recs[] = new VegetableRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'oversupply_opportunity',
                title: 'Unmatched Farmer Supply',
                body: $body,
            );
        }

        if ($band === ImbalanceBand::Undersupply) {
            $body = match ($role) {
                VegetableViewerRole::Admin => 'Dealer demand is outpacing available supply. Consider prompting more farmers to post.',
                VegetableViewerRole::Farmer => 'Buyers are actively looking for this vegetable. Good time to schedule your available harvest.',
                VegetableViewerRole::Dealer => 'Supply is currently scarce for this vegetable. Expect longer wait times, or consider adjusting your demanded quantity.',
            };

            $recs[] = new VegetableRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'supply_opportunity',
                title: 'Unfulfilled Dealer Demand',
                body: $body,
            );
        }

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
