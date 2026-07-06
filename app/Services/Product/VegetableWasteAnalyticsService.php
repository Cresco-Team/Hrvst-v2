<?php

namespace App\Services\Product;

use App\Models\Product\Vegetable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VegetableWasteAnalyticsService
{
    private const int FORECAST_LIMIT = 3;
    private const int STABILITY_LIMIT = 10;

    /** This month + 3 coming months. */
    private const int FORECAST_MONTHS = 4;

    /** How far back to pull history for both the forecast and stability calculations. */
    private const int HISTORY_YEARS = 5;

    private const float TREND_FLOOR = 0.60;
    private const float TREND_CEIL = 1.40;

    /** Below this, a coefficient of variation is a coin flip, not a signal. */
    private const int MIN_MONTHS_FOR_STABILITY = 24;

    /** "Quite high" = top quartile of mean expired_kg among vegetables with enough history to trust. */
    private const float STABILITY_QUARTILE = 0.75;

    private const int CACHE_TTL_SECONDS = 43200; // 12h — neither of these needs to be live

    private const array ALLOWED_COLUMNS = ['demand_expired_kg', 'supply_expired_kg'];

    // ── Seasonal forecast ─────────────────────────────────────────────────────

    /** Forecasted unmet dealer demand — signals farmers to supply more of this variety soon. */
    public function topWastedDemand(int $limit = self::FORECAST_LIMIT): array
    {
        return $this->forecastByColumn('demand_expired_kg', $limit);
    }

    /** Forecasted unclaimed farmer supply — signals dealers there's surplus coming soon. */
    public function topWastedSupply(int $limit = self::FORECAST_LIMIT): array
    {
        return $this->forecastByColumn('supply_expired_kg', $limit);
    }

    private function forecastByColumn(string $column, int $limit): array
    {
        $this->assertAllowedColumn($column);

        return Cache::remember(
            "top_forecast_waste:{$column}:{$limit}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->resolveForecast($column, $limit),
        );
    }

    private function resolveForecast(string $column, int $limit): array
    {
        $byVegetable = $this->fetchHistoryByColumn($column);

        $forecasts = $byVegetable
            ->map(fn (Collection $history) => $this->forecastColumn($history, $column))
            ->filter(fn (float $value) => $value > 0.0)
            ->sortDesc()
            ->take($limit);

        return $this->hydrate($forecasts);
    }

    /**
     * Seasonal-weighted average (most recent 3 same-calendar-month occurrences,
     * weighted 3/2/1) compounded by a year-over-year trend factor, summed
     * across the forecast window.
     */
    private function forecastColumn(Collection $history, string $column): float
    {
        if ($history->count() < 3) {
            return 0.0;
        }

        $sorted = $history->sortBy('period_date')->values();

        $byCalendarMonth = [];
        foreach ($sorted as $row) {
            $date = Carbon::parse($row->period_date);
            $byCalendarMonth[$date->month][] = [
                'year' => $date->year,
                'value' => (float) $row->{$column},
            ];
        }

        $recent12 = $sorted->slice(-12);
        $prior12 = $sorted->slice(-24, 12);
        $priorSum = (float) $prior12->sum($column);

        $monthlyGrowth = 1.0;
        if ($prior12->count() >= 6 && $priorSum > 0.0) {
            $recentSum = (float) $recent12->sum($column);
            $trend = max(self::TREND_FLOOR, min(self::TREND_CEIL, $recentSum / $priorSum));
            $monthlyGrowth = $trend ** (1 / 12);
        }

        $total = 0.0;

        for ($i = 0; $i < self::FORECAST_MONTHS; $i++) {
            $targetMonth = now()->startOfMonth()->addMonths($i)->month;
            $entries = $byCalendarMonth[$targetMonth] ?? [];

            if (empty($entries)) {
                continue;
            }

            usort($entries, fn ($a, $b) => $b['year'] <=> $a['year']);

            $weights = [3, 2, 1];
            $weightedSum = 0.0;
            $totalWeight = 0;

            foreach (array_slice($entries, 0, 3) as $index => $entry) {
                $w = $weights[$index] ?? 1;
                $weightedSum += $entry['value'] * $w;
                $totalWeight += $w;
            }

            $seasonalBase = $weightedSum / $totalWeight;
            $total += max(0.0, $seasonalBase * ($monthlyGrowth ** $i));
        }

        return round($total, 2);
    }

    // ── Year-round stability ──────────────────────────────────────────────────

    /** High, steady unmet demand with no strong seasonal pattern — a chronic gap, not a timing play. */
    public function mostStableWastedDemand(int $limit = self::STABILITY_LIMIT): array
    {
        return $this->stabilityByColumn('demand_expired_kg', $limit);
    }

    /** High, steady unclaimed supply with no strong seasonal pattern. */
    public function mostStableWastedSupply(int $limit = self::STABILITY_LIMIT): array
    {
        return $this->stabilityByColumn('supply_expired_kg', $limit);
    }

    private function stabilityByColumn(string $column, int $limit): array
    {
        $this->assertAllowedColumn($column);

        return Cache::remember(
            "most_stable_waste:{$column}:{$limit}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->resolveStability($column, $limit),
        );
    }

    private function resolveStability(string $column, int $limit): array
    {
        $stats = $this->fetchHistoryByColumn($column)
            ->map(fn (Collection $history) => $this->computeStability($history))
            ->filter();

        // Need a real population to compute a meaningful quartile from —
        // 3 vegetables don't have a "top 25%," they have an opinion.
        if ($stats->count() < 4) {
            return [];
        }

        $meanFloor = $this->percentile($stats->pluck('mean'), self::STABILITY_QUARTILE);

        $candidates = $stats
            ->filter(fn (array $s) => $s['mean'] >= $meanFloor)
            ->sortBy('cv')
            ->take($limit);

        return $this->hydrate($candidates->map(fn (array $s) => $s['mean']));
    }

    /**
     * Coefficient of variation (stddev / mean) of the monthly column across
     * full history. Lower = flatter across the year = less seasonal.
     * Requires MIN_MONTHS_FOR_STABILITY real data points — claiming a
     * vegetable is "stable all year" from 4 months of data is a guess
     * wearing a statistic's clothes.
     */
    private function computeStability(Collection $history, string $column): ?array
    {
        if ($history->count() < self::MIN_MONTHS_FOR_STABILITY) {
            return null;
        }

        $values = $history->pluck($column)->map(fn ($v) => (float) $v);
        $mean = $values->avg();

        if ($mean === null || $mean <= 0.0) {
            return null;
        }

        $variance = $values->reduce(
            fn (float $carry, float $v) => $carry + ($v - $mean) ** 2,
            0.0,
        ) / ($values->count() - 1);

        return ['mean' => $mean, 'cv' => sqrt($variance) / $mean];
    }

    /** Nearest-rank percentile — fine for this population size, no need for a stats library. */
    private function percentile(Collection $values, float $p): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return 0.0;
        }

        $index = max(0, min($count - 1, (int) ceil($p * $count) - 1));

        return (float) $sorted[$index];
    }

    private function hydrate(Collection $wastedKgByVegetableId): array
    {
        if ($wastedKgByVegetableId->isEmpty()) {
            return [];
        }

        return Vegetable::whereIn('id', $wastedKgByVegetableId->keys())
            ->get()
            ->sortByDesc(fn (Vegetable $v) => $wastedKgByVegetableId[$v->id])
            ->map(fn (Vegetable $v) => [
                'id' => $v->id,
                'display_name' => $v->display_name,
                'image_url' => $v->image_url,
                'wasted_kg' => round($wastedKgByVegetableId[$v->id], 2),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Excludes the current month — it's partial, and letting a half-recorded
     * month into either the seasonal weighting or the variance calc corrupts
     * both. This was a latent bug in the forecast method before this widget
     * existed; fixing it here, upstream of both consumers, in one place.
     */
    private function fetchHistoryByColumn(string $column): Collection
    {
        $start = now()->startOfMonth()->subYears(self::HISTORY_YEARS)->toDateString();
        $currentMonthStart = now()->startOfMonth()->toDateString();

        return DB::table('vegetable_monthly_stats')
            ->where('period_date', '>=', $start)
            ->where('period_date', '<', $currentMonthStart)
            ->select(['vegetable_id', 'period_date', $column])
            ->get()
            ->groupBy('vegetable_id');
    }

    private function assertAllowedColumn(string $column): void
    {
        if (! in_array($column, self::ALLOWED_COLUMNS, true)) {
            throw new InvalidArgumentException("Unsupported waste column: {$column}");
        }
    }
}
