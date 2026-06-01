<?php

namespace App\Services\Product;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;

class VarietyCalendarService
{
    public function buildForMonth(int $varietyId, int $year, int $month): array
    {
        $rows = PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->selectRaw('DATE(posts.scheduled_date) as date')
            ->selectRaw("COALESCE(posts.time_slot, 'unscheduled') as slot")
            ->selectRaw('posts.type')
            ->selectRaw('SUM(post_items.quantity_kg) as total_kg')
            ->selectRaw('COUNT(post_items.id) as items_count')
            ->where('post_items.variety_id', $varietyId)
            ->whereYear('posts.scheduled_date', $year)
            ->whereMonth('posts.scheduled_date', $month)
            ->whereNull('posts.deleted_at')
            ->whereIn('post_items.status', [PostItemStatus::Ongoing->value, PostItemStatus::Fulfilled->value])
            ->groupByRaw("DATE(posts.scheduled_date), COALESCE(posts.time_slot, 'unscheduled'), posts.type")
            ->orderByRaw('date, slot, posts.type')
            ->get();

        $schedule = [];

        foreach ($rows as $row) {
            $schedule[$row->date][$row->slot][] = [
                'type'        => $row->type,
                'total_kg'    => (float) $row->total_kg,
                'items_count' => (int) $row->items_count,
            ];
        }

        return $schedule;
    }
}
