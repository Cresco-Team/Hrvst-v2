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

        // Bug #1 fix: always load post to avoid lazy-loading exception
        $postItem->loadMissing('post');

        $newStatus = $postItem->status;
        $newColumn = $this->resolveColumn($postItem->post->type, $newStatus);

        if ($newColumn !== null) {
            $periodDate = $postItem->post->created_at->startOfMonth()->toDateString();
            $this->upsertRow($postItem->post->vegetable_id, $periodDate);

            DB::table('vegetable_monthly_stats')
                ->where('vegetable_id', $postItem->post->vegetable_id)
                ->where('period_date', $periodDate)
                ->increment($newColumn, (float) $postItem->quantity_kg);
        }

        $rawOldStatus = $postItem->getOriginal('status');
        $oldStatus = $rawOldStatus instanceof PostItemStatus
            ? $rawOldStatus
            : PostItemStatus::tryFrom($rawOldStatus);

        if ($oldStatus !== null) {
            $oldColumn = $this->resolveColumn($postItem->post->type, $oldStatus);

            if ($oldColumn !== null) {
                $periodDate = $postItem->post->created_at->startOfMonth()->toDateString();

                DB::table('vegetable_monthly_stats')
                    ->where('vegetable_id', $postItem->post->vegetable_id)
                    ->where('period_date', $periodDate)
                    ->update([
                        $oldColumn => DB::raw(
                            "CASE WHEN {$oldColumn} > {$postItem->quantity_kg}
                             THEN {$oldColumn} - {$postItem->quantity_kg}
                             ELSE 0
                             END"
                        ),
                    ]);
            }
        }
    }

    public function deleted(PostItem $postItem): void
    {
        // Bug #1 fix: always load post to avoid lazy-loading exception
        $postItem->loadMissing('post');

        $column = $this->resolveColumn($postItem->post->type, $postItem->status);

        if ($column === null) {
            return;
        }

        $periodDate = $postItem->post->created_at->startOfMonth()->toDateString();

        $exists = DB::table('vegetable_monthly_stats')
            ->where('vegetable_id', $postItem->post->vegetable_id)
            ->where('period_date', $periodDate)
            ->exists();

        if (! $exists) {
            return;
        }

        DB::table('vegetable_monthly_stats')
            ->where('vegetable_id', $postItem->post->vegetable_id)
            ->where('period_date', $periodDate)
            ->update([
                $column => DB::raw(
                    "CASE WHEN {$column} > {$postItem->quantity_kg}
                     THEN {$column} - {$postItem->quantity_kg}
                     ELSE 0
                     END"
                ),
            ]);
    }

    private function resolveColumn(PostType $type, PostItemStatus $status): ?string
    {
        return match (true) {
            $type === PostType::Supply && $status === PostItemStatus::Archived => 'supply_archived_kg',
            $type === PostType::Supply && $status === PostItemStatus::Fulfilled => 'supply_fulfilled_kg',
            $type === PostType::Demand && $status === PostItemStatus::Archived => 'demand_archived_kg',
            $type === PostType::Demand && $status === PostItemStatus::Fulfilled => 'demand_fulfilled_kg',
            default => null,
        };
    }

    private function upsertRow(int $vegetableId, string $periodDate): void
    {
        DB::table('vegetable_monthly_stats')->upsert(
            [[
                'vegetable_id' => $vegetableId,
                'period_date' => $periodDate,
                'supply_archived_kg' => 0,
                'supply_fulfilled_kg' => 0,
                'demand_archived_kg' => 0,
                'demand_fulfilled_kg' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['vegetable_id', 'period_date'],
            ['updated_at'],
        );
    }
}
