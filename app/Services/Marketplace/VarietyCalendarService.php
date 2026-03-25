<?php

namespace App\Services\Marketplace;

use Illuminate\Support\Facades\DB;

class VarietyCalendarService
{
    /**
     * Returns a date-keyed schedule map for the given variety and month.
     *
     * All post statuses are included (Ongoing, Fulfilled, Archived) —
     * the calendar is both forward-looking (planning) and
     * backward-looking (historical reference).
     *
     * Uses DB::table intentionally — SoftDeletes is an Eloquent concern only.
     * Manual whereNull('posts.deleted_at') is required here.
     *
     * Return shape:
     * [
     *   '2026-03-10' => [
     *     'morning'     => [
     *       ['type' => 'supply', 'total_kg' => 500.0, 'posts_count' => 3],
     *       ['type' => 'demand', 'total_kg' => 200.0, 'posts_count' => 1],
     *     ],
     *     'afternoon'   => [...],
     *     'evening'     => [...],
     *     'unscheduled' => [...],  // time_slot IS NULL bucket — must not silently disappear
     *   ],
     * ]
     *
     * @return array<string, array<string, list<array{type: string, total_kg: float, posts_count: int}>>>
     */
    public function forMonth(int $varietyId, int $year, int $month): array
    {
        $rows = DB::table('posts')
            ->select([
                DB::raw('DATE(scheduled_date) as date'),
                DB::raw('COALESCE(time_slot, \'unscheduled\') as slot'),
                'type',
                DB::raw('SUM(quantity_kg) as total_kg'),
                DB::raw('COUNT(id) as posts_count'),
            ])
            ->where('variety_id', $varietyId)
            ->whereYear('scheduled_date', $year)
            ->whereMonth('scheduled_date', $month)
            ->whereNull('deleted_at')
            ->groupBy(
                DB::raw('DATE(scheduled_date)'),
                DB::raw('COALESCE(time_slot, \'unscheduled\')'),
                'type',
            )
            ->orderBy('date')
            ->orderBy('slot')
            ->orderBy('type')
            ->get();

        $schedule = [];

        foreach ($rows as $row) {
            $schedule[$row->date][$row->slot][] = [
                'type' => $row->type,
                'total_kg' => (float) $row->total_kg,
                'posts_count' => (int) $row->posts_count,
            ];
        }

        return $schedule;
    }

    /**
     * Returns a lightweight summary for the given month.
     * Used to populate stat badges above the calendar.
     *
     * @return array{
     *   total_supply_kg: float,
     *   total_demand_kg: float,
     *   active_days: int,
     *   total_posts: int,
     * }
     */
    public function summaryForMonth(int $varietyId, int $year, int $month): array
    {
        $base = DB::table('posts')
            ->where('variety_id', $varietyId)
            ->whereYear('scheduled_date', $year)
            ->whereMonth('scheduled_date', $month)
            ->whereNull('deleted_at');

        return [
            'total_supply_kg' => (float) (clone $base)->where('type', 'supply')->sum('quantity_kg'),
            'total_demand_kg' => (float) (clone $base)->where('type', 'demand')->sum('quantity_kg'),
            'active_days' => (clone $base)->distinct()->count(DB::raw('DATE(scheduled_date)')),
            'total_posts' => (clone $base)->count(),
        ];
    }
}
