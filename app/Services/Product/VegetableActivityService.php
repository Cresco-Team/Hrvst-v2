<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\DB;

class VegetableActivityService
{
    public function buildMonthlyActivity(int $vegetableId): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $end = now()->endOfMonth();

        $rows = DB::table('post_items')
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->join('varieties', 'varieties.id', '=', 'post_items.variety_id')
            ->select([
                DB::raw("TO_CHAR(posts.created_at, 'YYYY-MM') as period"),
                'posts.type',
                'post_items.status',
                DB::raw('SUM(post_items.quantity_kg) as total_kg'),
            ])
            ->where('varieties.vegetable_id', $vegetableId)
            ->whereBetween('posts.created_at', [$start, $end])
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->whereIn('post_items.status', ['expired', 'fulfilled'])
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
            $supplyExpired = 0.0;
            $demandFulfilled = 0.0;
            $demandExpired = 0.0;

            foreach ($periodRows as $row) {
                $kg = (float) $row->total_kg;
                match (true) {
                    $row->type === 'supply' && $row->status === 'fulfilled' => $supplyFulfilled += $kg,
                    $row->type === 'supply' && $row->status === 'expired' => $supplyExpired += $kg,
                    $row->type === 'demand' && $row->status === 'fulfilled' => $demandFulfilled += $kg,
                    $row->type === 'demand' && $row->status === 'expired' => $demandExpired += $kg,
                    default => null,
                };
            }

            $result[] = [
                'month' => $key,
                'label' => $date->format('M Y'),
                'supply_fulfilled_kg' => $supplyFulfilled,
                'supply_expired_kg' => $supplyExpired,
                'demand_fulfilled_kg' => $demandFulfilled,
                'demand_expired_kg' => $demandExpired,
            ];
        }

        return $result;
    }
}
