<?php

namespace App\Services\Product;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VarietyCalendarService
{
    public function buildForMonth(int $varietyId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $aggregates = $this->fetchAggregates($varietyId, $start, $end);
        $items = $this->fetchItems($varietyId, $start, $end);

        return $this->mergeIntoSchedule($aggregates, $items);
    }

    private function fetchAggregates(int $varietyId, string $start, string $end): Collection
    {
        return PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->selectRaw('DATE(posts.scheduled_date) as date')
            ->selectRaw("COALESCE(posts.time_slot, 'unscheduled') as slot")
            ->selectRaw('posts.type')
            ->selectRaw('SUM(post_items.quantity_kg) as total_kg')
            ->selectRaw('COUNT(DISTINCT posts.id) as posts_count')
            ->where('post_items.variety_id', $varietyId)
            ->whereBetween('posts.scheduled_date', [$start, $end])
            ->whereNull('posts.deleted_at')
            ->whereIn('post_items.status', [PostItemStatus::Ongoing->value, PostItemStatus::Fulfilled->value])
            ->groupByRaw("DATE(posts.scheduled_date), COALESCE(posts.time_slot, 'unscheduled'), posts.type")
            ->orderByRaw('date, slot, posts.type')
            ->get();
    }

    private function fetchItems(int $varietyId, string $start, string $end): Collection
    {
        return PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->join('varieties', 'varieties.id', '=', 'post_items.variety_id')
            ->select([
                'post_items.id',
                'post_items.post_id',
                'post_items.variety_id',
                'post_items.quantity_kg',
                'post_items.unit_price',
                'post_items.price_flag',
                'post_items.status',
                'posts.type',
            ])
            ->selectRaw("COALESCE(posts.time_slot, 'unscheduled') as slot")
            ->selectRaw('DATE(posts.scheduled_date) as date')
            ->selectRaw('varieties.name as variety_name')
            ->where('post_items.variety_id', $varietyId)
            ->whereBetween('posts.scheduled_date', [$start, $end])
            ->whereNull('posts.deleted_at')
            ->whereIn('post_items.status', [PostItemStatus::Ongoing->value, PostItemStatus::Fulfilled->value])
            ->orderByRaw('date, slot, posts.type')
            ->get();
    }

    private function mergeIntoSchedule(Collection $aggregates, Collection $items): array
    {
        $schedule = [];

        foreach ($aggregates as $row) {
            [$date, $slot, $type] = [$row->date, $row->slot, $row->type];

            $schedule[$date][$slot] ??= $this->emptySlot();

            $schedule[$date][$slot]["{$type}_kg"] = (float) $row->total_kg;
            $schedule[$date][$slot]["{$type}_posts_count"] = (int) $row->posts_count;
        }

        foreach ($schedule as &$slots) {
            foreach ($slots as &$slot) {
                $slot['net_kg'] = round($slot['supply_kg'] - $slot['demand_kg'], 2);
            }
        }
        unset($slots, $slot);

        foreach ($items as $item) {
            [$date, $slot] = [$item->date, $item->slot];

            if (! isset($schedule[$date][$slot])) {
                continue;
            }

            $schedule[$date][$slot]['items'][] = [
                'post_id' => $item->post_id,
                'type' => $item->type,
                'variety_name' => $item->variety_name,
                'quantity_kg' => (float) $item->quantity_kg,
                'unit_price' => $item->unit_price !== null ? (float) $item->unit_price : null,
                'price_flag' => $item->price_flag?->value ?? null,
                'status' => $item->status?->value ?? $item->status,
            ];
        }

        return $schedule;
    }

    private function emptySlot(): array
    {
        return [
            'supply_kg' => 0.0,
            'demand_kg' => 0.0,
            'net_kg' => 0.0,
            'supply_posts_count' => 0,
            'demand_posts_count' => 0,
            'items' => [],
        ];
    }
}
