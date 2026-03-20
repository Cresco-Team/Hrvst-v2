<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Enums\PostType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VarietyMonthlyStatsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Backfilling variety_monthly_stats from existing posts…');

        DB::table('posts')
            ->whereIn('status', [PostStatus::Archived->value, PostStatus::Fulfilled->value])
            ->select([
                'variety_id',
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                'type',
                'status',
                DB::raw('SUM(quantity_kg) as total_kg'),
            ])
            ->groupBy('variety_id', DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'), 'type', 'status')
            ->orderBy('variety_id')
            ->chunk(500, function ($rows) {
                $grouped = [];

                foreach ($rows as $row) {
                    $key = "{$row->variety_id}_{$row->year}_{$row->month}";

                    if (! isset($grouped[$key])) {
                        $grouped[$key] = [
                            'variety_id' => $row->variety_id,
                            'year' => (int) $row->year,
                            'month' => (int) $row->month,
                            'supply_archived_kg' => 0,
                            'supply_fulfilled_kg' => 0,
                            'demand_archived_kg' => 0,
                            'demand_fulfilled_kg' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    $column = $this->resolveColumn($row->type, $row->status);

                    if ($column !== null) {
                        $grouped[$key][$column] += (float) $row->total_kg;
                    }
                }

                DB::table('variety_monthly_stats')->upsert(
                    array_values($grouped),
                    ['variety_id', 'year', 'month'],
                    ['supply_archived_kg', 'supply_fulfilled_kg', 'demand_archived_kg', 'demand_fulfilled_kg', 'updated_at'],
                );
            });

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
