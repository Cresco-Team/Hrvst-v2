<?php

namespace App\Observers;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use Illuminate\Support\Facades\DB;

class PostObserver
{
    public function updated(Post $post): void
    {
        if (! $post->wasChanged('status')) {
            return;
        }

        $newStatus = $post->status;
        $rawOldStatus = $post->getOriginal('status');
        $oldStatus = $rawOldStatus instanceof PostStatus
            ? $rawOldStatus
            : PostStatus::tryFrom($rawOldStatus);

        if ($newStatus === PostStatus::Ongoing) {
            return;
        }

        $newColumn = $this->resolveColumn($post->type, $newStatus);

        if ($newColumn === null) {
            return;
        }

        $periodDate = $post->created_at->startOfMonth()->toDateString();

        $this->upsertRow($post->vegetable_id, $periodDate);

        DB::table('vegetable_monthly_stats')
            ->where('vegetable_id', $post->vegetable_id)
            ->where('period_date', $periodDate)
            ->increment($newColumn, (float) $post->quantity_kg);

        if ($oldStatus !== null && $oldStatus !== PostStatus::Ongoing) {
            $oldColumn = $this->resolveColumn($post->type, $oldStatus);

            if ($oldColumn !== null) {
                DB::table('vegetable_monthly_stats')
                    ->where('vegetable_id', $post->vegetable_id)
                    ->where('period_date', $periodDate)
                    ->update([
                        $oldColumn => DB::raw(
                            "CASE WHEN {$oldColumn} > {$post->quantity_kg}
                             THEN {$oldColumn} - {$post->quantity_kg}
                             ELSE 0
                             END"
                        ),
                    ]);
            }
        }
    }

    public function deleted(Post $post): void
    {
        if ($post->status === PostStatus::Ongoing) {
            return;
        }

        $column = $this->resolveColumn($post->type, $post->status);

        if ($column === null) {
            return;
        }

        $periodDate = $post->created_at->startOfMonth()->toDateString();

        $exists = DB::table('vegetable_monthly_stats')
            ->where('vegetable_id', $post->vegetable_id)
            ->where('period_date', $periodDate)
            ->exists();

        if (! $exists) {
            return;
        }

        DB::table('vegetable_monthly_stats')
            ->where('vegetable_id', $post->vegetable_id)
            ->where('period_date', $periodDate)
            ->update([
                $column => DB::raw(
                    "CASE WHEN {$column} > {$post->quantity_kg}
                     THEN {$column} - {$post->quantity_kg}
                     ELSE 0
                     END"
                ),
            ]);
    }

    private function resolveColumn(PostType $type, PostStatus $status): ?string
    {
        return match (true) {
            $type === PostType::Supply && $status === PostStatus::Archived => 'supply_archived_kg',
            $type === PostType::Supply && $status === PostStatus::Fulfilled => 'supply_fulfilled_kg',
            $type === PostType::Demand && $status === PostStatus::Archived => 'demand_archived_kg',
            $type === PostType::Demand && $status === PostStatus::Fulfilled => 'demand_fulfilled_kg',
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
