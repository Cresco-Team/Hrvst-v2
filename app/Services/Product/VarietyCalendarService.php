<?php

namespace App\Services\Product;

use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Models\Marketplace\PostItem;
use Illuminate\Support\Facades\DB;

class VarietyCalendarService
{
    public function buildForMonth(int $varietyId, int $year, int $month): array
    {
        $aggregates = PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->selectRaw('DATE(posts.scheduled_date) as date')
            ->selectRaw("COALESCE(posts.time_slot, 'unscheduled') as slot")
            ->selectRaw('posts.type')
            ->selectRaw('SUM(post_items.quantity_kg) as total_kg')
            ->selectRaw('COUNT(DISTINCT posts.id) as posts_count')
            ->where('post_items.variety_id', $varietyId)
            ->whereYear('posts.scheduled_date', $year)
            ->whereMonth('posts.scheduled_date', $month)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->whereIn('post_items.status', [PostItemStatus::Ongoing->value, PostItemStatus::Fulfilled->value])
            ->groupByRaw("DATE(posts.scheduled_date), COALESCE(posts.time_slot, 'unscheduled'), posts.type")
            ->get();

        $items = PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->join('varieties', 'varieties.id', '=', 'post_items.variety_id')
            ->select([
                DB::raw('DATE(posts.scheduled_date) as date'),
                DB::raw("COALESCE(posts.time_slot, 'unscheduled') as slot"),
                'posts.id as post_id',
                'posts.type',
                'varieties.name as variety_name',
                'post_items.quantity_kg',
                'post_items.unit_price',
                'post_items.price_flag',
                'post_items.status',
            ])
            ->where('post_items.variety_id', $varietyId)
            ->whereYear('posts.scheduled_date', $year)
            ->whereMonth('posts.scheduled_date', $month)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->whereIn('post_items.status', [PostItemStatus::Ongoing->value, PostItemStatus::Fulfilled->value])
            ->orderByRaw('date, slot, posts.type')
            ->get();

        $schedule = [];

        foreach ($aggregates as $row) {
            $date = $row->date;
            $slot = $row->slot;

            if (! isset($schedule[$date][$slot])) {
                $schedule[$date][$slot] = [
                    'supply_kg'           => 0.0,
                    'demand_kg'           => 0.0,
                    'net_kg'              => 0.0,
                    'supply_posts_count'  => 0,
                    'demand_posts_count'  => 0,
                    'items'               => [],
                ];
            }

            if ($row->type === PostType::Supply->value) {
                $schedule[$date][$slot]['supply_kg']          = (float) $row->total_kg;
                $schedule[$date][$slot]['supply_posts_count'] = (int) $row->posts_count;
            } else {
                $schedule[$date][$slot]['demand_kg']          = (float) $row->total_kg;
                $schedule[$date][$slot]['demand_posts_count'] = (int) $row->posts_count;
            }
        }

        foreach ($schedule as &$slots) {
            foreach ($slots as &$data) {
                $data['net_kg'] = round($data['supply_kg'] - $data['demand_kg'], 2);
            }
        }
        unset($slots, $data);

        foreach ($items as $item) {
            $date = $item->date;
            $slot = $item->slot;

            if (! isset($schedule[$date][$slot])) {
                continue;
            }

            $schedule[$date][$slot]['items'][] = [
                'post_id'      => $item->post_id,
                'type'         => $item->type,
                'variety_name' => $item->variety_name,
                'quantity_kg'  => (float) $item->quantity_kg,
                'unit_price'   => $item->unit_price !== null ? (float) $item->unit_price : null,
                'price_flag'   => $item->price_flag,
                'status'       => $item->status,
            ];
        }

        return $schedule;
    }
}
