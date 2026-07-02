<?php

namespace Database\Seeders;

use App\Models\Product\Vegetable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VegetableMonthlyStatsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('vegetable_monthly_stats')->truncate();

        $vegetables = Vegetable::pluck('id');

        if ($vegetables->isEmpty()) {
            $this->command->warn('No vegetables found. Run ProductSeeder first.');

            return;
        }

        $archetypes = [
            'oversupply_steady',
            'undersupply_demand',
            'balanced_declining',
            'post_seasonal_crash',
        ];

        $rows = [];

        foreach ($vegetables as $index => $vegetableId) {
            $archetype = $archetypes[$index % count($archetypes)];

            for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
                $date = now()->startOfMonth()->subMonths($monthsAgo);

                [$supplyFulfilled, $supplyEpired, $demandFulfilled, $demandExpired]
                    = $this->volumesFor($archetype, $monthsAgo);

                $rows[] = [
                    'vegetable_id' => $vegetableId,
                    'period_date' => $date->toDateString(),
                    'supply_fulfilled_kg' => $supplyFulfilled,
                    'supply_expired_kg' => $supplyEpired,
                    'demand_fulfilled_kg' => $demandFulfilled,
                    'demand_expired_kg' => $demandExpired,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('vegetable_monthly_stats')->insert($chunk);
        }

        $this->command->info(sprintf(
            'Seeded %d rows across %d vegetables (4 archetypes, ~%d each).',
            count($rows),
            $vegetables->count(),
            (int) ceil($vegetables->count() / 4),
        ));
    }

    private function volumesFor(string $archetype, int $monthsAgo): array
    {
        return match ($archetype) {
            'oversupply_steady' => $this->oversupplySteady($monthsAgo),
            'undersupply_demand' => $this->undersupplyDemand($monthsAgo),
            'balanced_declining' => $this->balancedDeclining($monthsAgo),
            'post_seasonal_crash' => $this->postSeasonalCrash($monthsAgo),
        };
    }

    private function oversupplySteady(int $monthsAgo): array
    {
        $supplyBase = 8_000 + (11 - $monthsAgo) * 150;
        $demandBase = 2_600;

        return $this->split($supplyBase, 0.62, $demandBase, 0.15);
    }

    private function undersupplyDemand(int $monthsAgo): array
    {
        $supplyBase = 2_000;
        $demandBase = 5_500 + (11 - $monthsAgo) * 200;

        return $this->split($supplyBase, 0.12, $demandBase, 0.60);
    }

    private function balancedDeclining(int $monthsAgo): array
    {
        $supplyBase = $monthsAgo === 1 ? 1_600 : 4_000;
        $demandBase = 3_800;

        return $this->split($supplyBase, 0.15, $demandBase, 0.17);
    }

    private function postSeasonalCrash(int $monthsAgo): array
    {
        $supplyBase = match (true) {
            $monthsAgo >= 5 && $monthsAgo <= 7 => 9_000,
            $monthsAgo === 2 => 3_500,
            $monthsAgo === 1 => 525,
            default => 1_200,
        };

        $demandBase = ($monthsAgo >= 5 && $monthsAgo <= 7) ? 7_000 : 900;

        return $this->split($supplyBase, 0.28, $demandBase, 0.25);
    }

    private function split(
        float $supplyBase,
        float $supplyArchiveRate,
        float $demandBase,
        float $demandArchiveRate,
    ): array {
        return [
            round($supplyBase * (1 - $supplyArchiveRate), 2),
            round($supplyBase * $supplyArchiveRate, 2),
            round($demandBase * (1 - $demandArchiveRate), 2),
            round($demandBase * $demandArchiveRate, 2),
        ];
    }
}
