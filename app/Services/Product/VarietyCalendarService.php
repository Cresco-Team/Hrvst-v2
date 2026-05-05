<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\DB;

class VarietyCalendarService
{
    public function buildForMonth(int $varietyId, int $year, int $month): array
    {
        $rows = DB::table('post_items')
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->select([
                DB::raw('DATE(posts.scheduled_date) as date'),
                DB::raw("COALESCE(posts.time_slot, 'unscheduled') as slot"),
                'posts.type',
                DB::raw('SUM(post_items.quantity_kg) as total_kg'),
                DB::raw('COUNT(post_items.id) as posts_count'),
            ])
            ->where('post_items.variety_id', $varietyId)
            ->whereYear('posts.scheduled_date', $year)
            ->whereMonth('posts.scheduled_date', $month)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->whereIn('posts.status', ['ongoing', 'fulfilled'])
            ->groupBy(
                DB::raw('DATE(posts.scheduled_date)'),
                DB::raw("COALESCE(posts.time_slot, 'unscheduled')"),
                'posts.type',
            )
            ->orderBy('date')
            ->orderBy('slot')
            ->orderBy('posts.type')
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
}
