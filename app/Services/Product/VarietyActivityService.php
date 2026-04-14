<?php

namespace App\Services\Product;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VarietyActivityService
{
    public function buildMonthlyActivity(int $varietyId): array
    {
        $start = now()->startOfMonth()->subMonths(11)->toDateString();
        $end = now()->startOfMonth()->toDateString();

        $rows = DB::table('variety_monthly_stats')
            ->where('variety_id', $varietyId)
            ->whereBetween('period_date', [$start, $end])
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->period_date)->format('Y-m'));

        $result = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $key = $date->format('Y-m');
            $row = $rows->get($key);

            $result[] = [
                'month' => $key,
                'label' => $date->format('M Y'),
                'supply_fulfilled_kg' => $row ? (float) $row->supply_fulfilled_kg : 0.0,
                'supply_archived_kg' => $row ? (float) $row->supply_archived_kg : 0.0,
                'demand_fulfilled_kg' => $row ? (float) $row->demand_fulfilled_kg : 0.0,
                'demand_archived_kg' => $row ? (float) $row->demand_archived_kg : 0.0,
            ];
        }

        return $result;
    }
}
