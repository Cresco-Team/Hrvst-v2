<?php

namespace App\Observers;

use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Models\Marketplace\PostItem;
use Illuminate\Support\Facades\DB;

class PostItemObserver
{
    public function updated(PostItem $postItem): void
    {
        if (! $postItem->wasChanged('status')) {
            return;
        }

        $postItem->loadMissing('post');

        $varietyId  = $postItem->variety_id;
        $periodDate = $postItem->post->created_at->startOfMonth()->toDateString();

        $newStatus = $postItem->status;
        $newColumn = $this->resolveColumn($postItem->post->type, $newStatus);

        if ($newColumn !== null) {
            $this->upsertRow($varietyId, $periodDate);

            DB::table('variety_monthly_stats')
                ->where('variety_id', $varietyId)
                ->where('period_date', $periodDate)
                ->increment($newColumn, (float) $postItem->quantity_kg);
        }

        $rawOldStatus = $postItem->getOriginal('status');
        $oldStatus    = $rawOldStatus instanceof PostItemStatus
            ? $rawOldStatus
            : PostItemStatus::tryFrom($rawOldStatus);

        if ($oldStatus !== null) {
            $oldColumn = $this->resolveColumn($postItem->post->type, $oldStatus);

            if ($oldColumn !== null) {
                $qty = (float) $postItem->quantity_kg;

                DB::table('variety_monthly_stats')
                    ->where('variety_id', $varietyId)
                    ->where('period_date', $periodDate)
                    ->update([
                        $oldColumn => DB::raw("GREATEST({$oldColumn} - {$qty}, 0)"),
                    ]);
            }
        }
    }

    public function deleted(PostItem $postItem): void
    {
        $postItem->loadMissing('post');

        $column = $this->resolveColumn($postItem->post->type, $postItem->status);

        if ($column === null) {
            return;
        }

        $qty        = (float) $postItem->quantity_kg;
        $varietyId  = $postItem->variety_id;
        $periodDate = $postItem->post->created_at->startOfMonth()->toDateString();

        DB::table('variety_monthly_stats')
            ->where('variety_id', $varietyId)
            ->where('period_date', $periodDate)
            ->update([
                $column => DB::raw("GREATEST({$column} - {$qty}, 0)"),
            ]);
    }

    private function resolveColumn(PostType $type, PostItemStatus $status): ?string
    {
        return match (true) {
            $type === PostType::Supply && $status === PostItemStatus::Expired   => 'supply_expired_kg',
            $type === PostType::Supply && $status === PostItemStatus::Fulfilled => 'supply_fulfilled_kg',
            $type === PostType::Demand && $status === PostItemStatus::Expired   => 'demand_expired_kg',
            $type === PostType::Demand && $status === PostItemStatus::Fulfilled => 'demand_fulfilled_kg',
            default => null,
        };
    }

    private function upsertRow(int $varietyId, string $periodDate): void
    {
        DB::table('variety_monthly_stats')->upsert(
            [[
                'variety_id'          => $varietyId,
                'period_date'         => $periodDate,
                'supply_expired_kg'   => 0,
                'supply_fulfilled_kg' => 0,
                'demand_expired_kg'   => 0,
                'demand_fulfilled_kg' => 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]],
            ['variety_id', 'period_date'],
            ['updated_at'],
        );
    }
}
