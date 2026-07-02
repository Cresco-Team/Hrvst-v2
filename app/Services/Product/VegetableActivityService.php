<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\DB;

class VegetableActivityService
{
    /**
     * Build monthly activity data for a given number of past months.
     *
     * Pass months: 36 from VarietyService to get 3-year history in a single
     * query for forecasting; the chart uses array_slice(-12) of that result.
     *
     * `has_data` flags months that have an actual DB row vs zero-padded gaps.
     * The chart renders both identically (zero bars for gaps), but
     * computeForecast() must skip padded months to avoid phantom-zero dilution
     * of seasonal baselines and trend ratios — the root cause of the
     * demand > supply inversion seen in the forecast.
     *
     * @return array<int, array{
     *     month: string,
     *     label: string,
     *     has_data: bool,
     *     supply_fulfilled_kg: float,
     *     supply_expired_kg: float,
     *     demand_fulfilled_kg: float,
     *     demand_expired_kg: float,
     * }>
     */
    public function buildMonthlyActivity(int $vegetableId, int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1)->toDateString();

        $rows = DB::table('vegetable_monthly_stats')
            ->where('vegetable_id', $vegetableId)
            ->where('period_date', '>=', $start)
            ->select([
                DB::raw("TO_CHAR(period_date, 'YYYY-MM') as period"),
                DB::raw('SUM(supply_fulfilled_kg) as supply_fulfilled_kg'),
                DB::raw('SUM(supply_expired_kg)   as supply_expired_kg'),
                DB::raw('SUM(demand_fulfilled_kg) as demand_fulfilled_kg'),
                DB::raw('SUM(demand_expired_kg)   as demand_expired_kg'),
            ])
            ->groupBy(DB::raw("TO_CHAR(period_date, 'YYYY-MM')"))
            ->get()
            ->keyBy('period');

        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $key  = $date->format('Y-m');
            $row  = $rows->get($key);

            $result[] = [
                'month'               => $key,
                'label'               => $date->format('M Y'),
                'has_data'            => $row !== null,
                'supply_fulfilled_kg' => (float) ($row?->supply_fulfilled_kg ?? 0),
                'supply_expired_kg'   => (float) ($row?->supply_expired_kg ?? 0),
                'demand_fulfilled_kg' => (float) ($row?->demand_fulfilled_kg ?? 0),
                'demand_expired_kg'   => (float) ($row?->demand_expired_kg ?? 0),
            ];
        }

        return $result;
    }
}
