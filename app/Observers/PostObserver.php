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
        $oldStatus = PostStatus::tryFrom($post->getOriginal('status'));

        if ($newStatus === PostStatus::Ongoing) {
            return;
        }

        $newColumn = $this->resolveColumn($post->type, $newStatus);

        if ($newColumn === null) {
            return;
        }

        $this->upsertRow($post->variety_id, (int) $post->created_at->year, (int) $post->created_at->month);

        DB::table('variety_monthly_stats')
            ->where('variety_id', $post->variety_id)
            ->where('year', $post->created_at->year)
            ->where('month', $post->created_at->month)
            ->increment($newColumn, (float) $post->quantity_kg);

        if ($oldStatus !== null && $oldStatus !== PostStatus::Ongoing) {
            $oldColumn = $this->resolveColumn($post->type, $oldStatus);

            if ($oldColumn !== null) {
                DB::table('variety_monthly_stats')
                    ->where('variety_id', $post->variety_id)
                    ->where('year', $post->created_at->year)
                    ->where('month', $post->created_at->month)
                    ->update([
                        $oldColumn => DB::raw("GREATEST(0, {$oldColumn} - {$post->quantity_kg})"),
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

        $exists = DB::table('variety_monthly_stats')
            ->where('variety_id', $post->variety_id)
            ->where('year', $post->created_at->year)
            ->where('month', $post->created_at->month)
            ->exists();

        if (! $exists) {
            return;
        }

        DB::table('variety_monthly_stats')
            ->where('variety_id', $post->variety_id)
            ->where('year', $post->created_at->year)
            ->where('month', $post->created_at->month)
            ->update([
                $column => DB::raw("GREATEST(0, {$column} - {$post->quantity_kg})"),
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

    private function upsertRow(int $varietyId, int $year, int $month): void
    {
        DB::table('variety_monthly_stats')->upsert(
            [[
                'variety_id' => $varietyId,
                'year' => $year,
                'month' => $month,
                'supply_archived_kg' => 0,
                'supply_fulfilled_kg' => 0,
                'demand_archived_kg' => 0,
                'demand_fulfilled_kg' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['variety_id', 'year', 'month'],
            ['updated_at'],
        );
    }
}
