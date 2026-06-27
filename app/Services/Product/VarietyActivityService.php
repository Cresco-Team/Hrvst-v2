<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\DB;

class VarietyActivityService
{
    public function buildMonthlyActivity(int $varietyId): array
    {
        $start = now()->startOfMonth()->subMonths(11)->toDateString();

        $rows = DB::table('variety_monthly_stats')
            ->where('variety_id', $varietyId)
            ->where('period_date', '>=', $start)
            ->select([
                DB::raw("TO_CHAR(period_date, 'YYYY-MM') as period"),
                DB::raw('SUM(supply_fulfilled_kg) as supply_fulfilled_kg'),
                DB::raw('SUM(supply_expired_kg) as supply_expired_kg'),
                DB::raw('SUM(demand_fulfilled_kg) as demand_fulfilled_kg'),
                DB::raw('SUM(demand_expired_kg) as demand_expired_kg'),
            ])
            ->groupBy(DB::raw("TO_CHAR(period_date, 'YYYY-MM')"))
            ->get()
            ->keyBy('period');

        $result = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $key  = $date->format('Y-m');
            $row  = $rows->get($key);

            $result[] = [
                'month'               => $key,
                'label'               => $date->format('M Y'),
                'supply_fulfilled_kg' => (float) ($row?->supply_fulfilled_kg ?? 0),
                'supply_expired_kg'   => (float) ($row?->supply_expired_kg ?? 0),
                'demand_fulfilled_kg' => (float) ($row?->demand_fulfilled_kg ?? 0),
                'demand_expired_kg'   => (float) ($row?->demand_expired_kg ?? 0),
            ];
        }

        return $result;
    }
}
