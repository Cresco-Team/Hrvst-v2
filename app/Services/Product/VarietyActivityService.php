<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\DB;

class VarietyActivityService
{
    public function buildMonthlyActivity(int $varietyId): array
    {
        // variety_monthly_stats is now vegetable_monthly_stats (vegetable-level).
        // Variety-level monthly data must be aggregated from post_items → posts.
        // We bucket by posts.created_at month, not scheduled_date, to match
        // how the observer previously counted posts.
        $start = now()->startOfMonth()->subMonths(11);
        $end = now()->endOfMonth();

        $rows = DB::table('post_items')
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->select([
                DB::raw("TO_CHAR(posts.created_at, 'YYYY-MM') as period"),
                'posts.type',
                'posts.status',
                DB::raw('SUM(post_items.quantity_kg) as total_kg'),
            ])
            ->where('post_items.variety_id', $varietyId)
            ->whereBetween('posts.created_at', [$start, $end])
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->whereIn('posts.status', ['archived', 'fulfilled'])
            ->groupBy(
                DB::raw("TO_CHAR(posts.created_at, 'YYYY-MM')"),
                'posts.type',
                'posts.status',
            )
            ->get()
            ->groupBy('period');

        $result = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $key = $date->format('Y-m');
            $periodRows = $rows->get($key, collect());

            $supplyFulfilled = 0.0;
            $supplyArchived = 0.0;
            $demandFulfilled = 0.0;
            $demandArchived = 0.0;

            foreach ($periodRows as $row) {
                $kg = (float) $row->total_kg;
                match (true) {
                    $row->type === 'supply' && $row->status === 'fulfilled' => $supplyFulfilled += $kg,
                    $row->type === 'supply' && $row->status === 'archived' => $supplyArchived += $kg,
                    $row->type === 'demand' && $row->status === 'fulfilled' => $demandFulfilled += $kg,
                    $row->type === 'demand' && $row->status === 'archived' => $demandArchived += $kg,
                    default => null,
                };
            }

            $result[] = [
                'month' => $key,
                'label' => $date->format('M Y'),
                'supply_fulfilled_kg' => $supplyFulfilled,
                'supply_archived_kg' => $supplyArchived,
                'demand_fulfilled_kg' => $demandFulfilled,
                'demand_archived_kg' => $demandArchived,
            ];
        }

        return $result;
    }
}
