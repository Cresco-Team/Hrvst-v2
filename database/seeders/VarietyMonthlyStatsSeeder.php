<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Enums\PostType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VarietyMonthlyStatsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Backfilling variety_monthly_stats from existing posts…');

        $posts = DB::table('posts')
            ->whereIn('status', [PostStatus::Archived->value, PostStatus::Fulfilled->value])
            ->select(['variety_id', 'type', 'status', 'quantity_kg', 'created_at'])
            ->get();

        $grouped = [];

        foreach ($posts as $post) {
            $periodDate = Carbon::parse($post->created_at)->startOfMonth()->toDateString();
            $key = "{$post->variety_id}_{$periodDate}";

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'variety_id' => $post->variety_id,
                    'period_date' => $periodDate,
                    'supply_archived_kg' => 0.0,
                    'supply_fulfilled_kg' => 0.0,
                    'demand_archived_kg' => 0.0,
                    'demand_fulfilled_kg' => 0.0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $column = $this->resolveColumn($post->type, $post->status);

            if ($column !== null) {
                $grouped[$key][$column] += (float) $post->quantity_kg;
            }
        }

        if (empty($grouped)) {
            $this->command->info('No archived/fulfilled posts found. Nothing to backfill.');

            return;
        }

        foreach (array_chunk(array_values($grouped), 500) as $chunk) {
            DB::table('variety_monthly_stats')->upsert(
                $chunk,
                ['variety_id', 'period_date'],
                ['supply_archived_kg', 'supply_fulfilled_kg', 'demand_archived_kg', 'demand_fulfilled_kg', 'updated_at'],
            );
        }

        $total = DB::table('variety_monthly_stats')->count();
        $this->command->info("Done. {$total} rows in variety_monthly_stats.");
    }

    private function resolveColumn(string $type, string $status): ?string
    {
        return match (true) {
            $type === PostType::Supply->value && $status === PostStatus::Archived->value => 'supply_archived_kg',
            $type === PostType::Supply->value && $status === PostStatus::Fulfilled->value => 'supply_fulfilled_kg',
            $type === PostType::Demand->value && $status === PostStatus::Archived->value => 'demand_archived_kg',
            $type === PostType::Demand->value && $status === PostStatus::Fulfilled->value => 'demand_fulfilled_kg',
            default => null,
        };
    }
}
