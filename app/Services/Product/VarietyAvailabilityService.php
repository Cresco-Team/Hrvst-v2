<?php

namespace App\Services\Product;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;

class VarietyAvailabilityService
{
    /**
     * Returns the aggregate supply_kg, demand_kg, and net_kg for a variety
     * on a specific date. If $timeSlot is provided, the result is scoped to
     * that slot; otherwise it aggregates all slots for the day.
     *
     * Only Ongoing post items are counted — Fulfilled and Expired are excluded
     * because they no longer represent active market pressure.
     */
    public function slotSummary(int $varietyId, string $date, ?string $timeSlot): array
    {
        $row = PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->where('post_items.variety_id', $varietyId)
            ->whereDate('posts.scheduled_date', $date)
            ->when($timeSlot, fn ($q) => $q->where('posts.time_slot', $timeSlot))
            ->where('post_items.status', PostItemStatus::Ongoing->value)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN posts.type = 'supply' THEN post_items.quantity_kg ELSE 0 END), 0) AS supply_kg,
                COALESCE(SUM(CASE WHEN posts.type = 'demand' THEN post_items.quantity_kg ELSE 0 END), 0) AS demand_kg
            ")
            ->first();

        $supply = round((float) ($row->supply_kg ?? 0), 2);
        $demand = round((float) ($row->demand_kg ?? 0), 2);

        return [
            'supply_kg' => $supply,
            'demand_kg' => $demand,
            'net_kg'    => round($supply - $demand, 2),
        ];
    }
}
