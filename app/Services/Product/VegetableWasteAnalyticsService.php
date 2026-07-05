<?php

namespace App\Services\Product;

use App\Models\Product\Vegetable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VegetableWasteAnalyticsService
{
    private const int DEFAULT_LIMIT = 3;
    private const int DEFAULT_MONTHS = 3;

    private const array ALLOWED_COLUMNS = ['demand_expired_kg', 'supply_expired_kg'];

    public function topWastedDemand(int $limit = self::DEFAULT_LIMIT, int $months = self::DEFAULT_MONTHS): array
    {
        return $this->topByColumn('demand_expired_kg', $limit, $months);
    }

    public function topWastedSupply(int $limit = self::DEFAULT_LIMIT, int $months = self::DEFAULT_MONTHS): array
    {
        return $this->topByColumn('supply_expired_kg', $limit, $months);
    }

    private function topByColumn(string $column, int $limit, int $months): array
    {
        if (! in_array($column, self::ALLOWED_COLUMNS, true)) {
            throw new InvalidArgumentException("Unsupported waste column: {$column}");
        }

        return Cache::remember(
            "top_wasted:{$column}:{$limit}:{$months}",
            3600,
            fn () => $this->resolve($column, $limit, $months),
        );
    }

    private function resolve(string $column, int $limit, int $months): array
    {
        $start = now()->startOfMonth()->subMonths($months)->toDateString();
        $end = now()->startOfMonth()->toDateString();

        $totals = DB::table('vegetable_monthly_stats')
            ->where('period_date', '>=', $start)
            ->where('period_date', '<', $end)
            ->groupBy('vegetable_id')
            ->havingRaw("SUM({$column}) > 0")
            ->orderByDesc(DB::raw("SUM({$column})"))
            ->limit($limit)
            ->select(['vegetable_id', DB::raw("SUM({$column}) as wasted_kg")])
            ->get()
            ->keyBy('vegetable_id');

        if ($totals->isEmpty()) {
            return [];
        }

        return Vegetable::whereIn('id', $totals->keys())
            ->get()
            ->sortByDesc(fn (Vegetable $v) => (float) $totals[$v->id]->wasted_kg)
            ->map(fn (Vegetable $v) => [
                'id' => $v->id,
                'display_name' => $v->display_name,
                'image_url' => $v->image_url,
                'wasted_kg' => round((float) $totals[$v->id]->wasted_kg, 2),
            ])
            ->values()
            ->toArray();
    }
}
