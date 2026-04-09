<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\DB;

class VarietyCalendarService
{
    public function buildForMonth(int $varietyId, int $year, int $month): array
    {
        $rows = DB::table('posts')
            ->select([
                DB::raw('DATE(scheduled_date) as date'),
                DB::raw("COALESCE(time_slot, 'unscheduled') as slot"),
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
                DB::raw("COALESCE(time_slot, 'unscheduled')"),
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
}
