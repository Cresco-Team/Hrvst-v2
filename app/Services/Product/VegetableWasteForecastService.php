<?php

namespace App\Services\Product;

use App\Models\Product\Vegetable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VegetableWasteForecastService
{
    private const int DEFAULT_LIMIT = 3;

    /** This month + 3 coming months. */
    private const int FORECAST_MONTHS = 4;

    /** How far back to pull history for the seasonal + trend calculation. */
    private const int HISTORY_YEARS = 5;

    private const float TREND_FLOOR = 0.60;
    private const float TREND_CEIL = 1.40;

    private const int CACHE_TTL_SECONDS = 43200; // 12h — a forecast doesn't need to be live

    private const array ALLOWED_COLUMNS = ['demand_expired_kg', 'supply_expired_kg'];

    /** Forecasted unmet dealer demand — signals farmers to supply more of this variety. */
    public function topWastedDemand(int $limit = self::DEFAULT_LIMIT): array
    {
        return $this->topForecastedByColumn('demand_expired_kg', $limit);
    }

    /** Forecasted unclaimed farmer supply — signals dealers there's surplus coming. */
    public function topWastedSupply(int $limit = self::DEFAULT_LIMIT): array
    {
        return $this->topForecastedByColumn('supply_expired_kg', $limit);
    }

    private function topForecastedByColumn(string $column, int $limit): array
    {
        if (! in_array($column, self::ALLOWED_COLUMNS, true)) {
            throw new InvalidArgumentException("Unsupported waste column: {$column}");
        }

        return Cache::remember(
            "top_forecast_waste:{$column}:{$limit}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->resolve($column, $limit),
        );
    }

    /**
     * One query for every vegetable's 5-year history, grouped in memory.
     * Deliberately NOT using VegetableActivityService::buildMonthlyActivity()
     * here — that method runs one query per vegetable, and looping it across
     * the whole catalog just to rank the top 3 is N+1 for no reason. This
     * also skips its zero-padding: forecastColumn() needs real occurrences
     * of each calendar month, not a dense padded series.
     */
    private function resolve(string $column, int $limit): array
    {
        $start = now()->startOfMonth()->subYears(self::HISTORY_YEARS)->toDateString();

        $byVegetable = DB::table('vegetable_monthly_stats')
            ->where('period_date', '>=', $start)
            ->select(['vegetable_id', 'period_date', $column])
            ->get()
            ->groupBy('vegetable_id');

        $forecasts = $byVegetable
            ->map(fn (Collection $history) => $this->forecastColumn($history, $column))
            ->filter(fn (float $value) => $value > 0.0)
            ->sortDesc()
            ->take($limit);

        if ($forecasts->isEmpty()) {
            return [];
        }

        return Vegetable::whereIn('id', $forecasts->keys())
            ->get()
            ->sortByDesc(fn (Vegetable $v) => $forecasts[$v->id])
            ->map(fn (Vegetable $v) => [
                'id' => $v->id,
                'display_name' => $v->display_name,
                'image_url' => $v->image_url,
                'wasted_kg' => $forecasts[$v->id],
            ])
            ->values()
            ->toArray();
    }

    /**
     * Seasonal-weighted average (most recent 3 same-calendar-month
     * occurrences across the 5-year window, weighted 3/2/1) compounded by a
     * year-over-year trend factor, summed across the forecast window. Same
     * technique as VarietyAnalyticsService::computeForecast(), adapted to
     * forecast a single named column directly instead of a blended
     * fulfilled+expired volume total.
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
}
