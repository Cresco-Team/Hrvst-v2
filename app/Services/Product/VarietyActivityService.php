<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\DB;

class VarietyActivityService
{
    public function buildMonthlyActivity(int $varietyId): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $end = now()->endOfMonth();

        $rows = DB::table('post_items')
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->select([
                DB::raw("TO_CHAR(posts.created_at, 'YYYY-MM') as period"),
                'posts.type',
                'post_items.status',
                DB::raw('SUM(post_items.quantity_kg) as total_kg'),
            ])
            ->where('post_items.variety_id', $varietyId)
            ->whereBetween('posts.created_at', [$start, $end])
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->whereIn('post_items.status', ['unsettled', 'fulfilled'])
            ->groupBy(
                DB::raw("TO_CHAR(posts.created_at, 'YYYY-MM')"),
                'posts.type',
                'post_items.status',
            )
            ->get()
            ->groupBy('period');

        $result = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $key = $date->format('Y-m');
            $periodRows = $rows->get($key, collect());

            $supplyFulfilled = 0.0;
            $supplyUnsettled = 0.0;
            $demandFulfilled = 0.0;
            $demandUnsettled = 0.0;

            foreach ($periodRows as $row) {
                $kg = (float) $row->total_kg;
                match (true) {
                    $row->type === 'supply' && $row->status === 'fulfilled' => $supplyFulfilled += $kg,
                    $row->type === 'supply' && $row->status === 'unsettled' => $supplyUnsettled += $kg,
                    $row->type === 'demand' && $row->status === 'fulfilled' => $demandFulfilled += $kg,
                    $row->type === 'demand' && $row->status === 'unsettled' => $demandUnsettled += $kg,
                    default => null,
                };
            }

            $result[] = [
                'month' => $key,
                'label' => $date->format('M Y'),
                'supply_fulfilled_kg' => $supplyFulfilled,
                'supply_unsettled_kg' => $supplyUnsettled,
                'demand_fulfilled_kg' => $demandFulfilled,
                'demand_unsettled_kg' => $demandUnsettled,
            ];
        }

        return $result;
    }
}
