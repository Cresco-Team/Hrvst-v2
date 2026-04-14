<?php

namespace Database\Seeders;

use App\Models\Product\Variety;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VarietyMonthlyStatsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('variety_monthly_stats')->truncate();

        $varieties = Variety::pluck('id');

        if ($varieties->isEmpty()) {
            $this->command->warn('No varieties found. Run ProductSeeder first.');

            return;
        }

        $archetypes = [
            'oversupply_steady',
            'undersupply_demand',
            'balanced_declining',
            'post_seasonal_crash',
        ];

        $rows = [];

        foreach ($varieties as $index => $varietyId) {
            $archetype = $archetypes[$index % count($archetypes)];

            for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
                $date = now()->startOfMonth()->subMonths($monthsAgo);

                [$supplyFulfilled, $supplyArchived, $demandFulfilled, $demandArchived]
                    = $this->volumesFor($archetype, $monthsAgo);

                $rows[] = [
                    'variety_id' => $varietyId,
                    'period_date' => $date->toDateString(),
                    'supply_fulfilled_kg' => $supplyFulfilled,
                    'supply_archived_kg' => $supplyArchived,
                    'demand_fulfilled_kg' => $demandFulfilled,
                    'demand_archived_kg' => $demandArchived,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('variety_monthly_stats')->insert($chunk);
        }

        $this->command->info(sprintf(
            'Seeded %d rows across %d varieties (4 archetypes, ~%d each).',
            count($rows),
            $varieties->count(),
            (int) ceil($varieties->count() / 4),
        ));
    }

    // ─── Archetype volume generators ──────────────────────────────────────────

    private function volumesFor(string $archetype, int $monthsAgo): array
    {
        return match ($archetype) {
            'oversupply_steady' => $this->oversupplySteady($monthsAgo),
            'undersupply_demand' => $this->undersupplyDemand($monthsAgo),
            'balanced_declining' => $this->balancedDeclining($monthsAgo),
            'post_seasonal_crash' => $this->postSeasonalCrash($monthsAgo),
        };
    }

    /**
     * Supply is ~3× demand throughout. Archive rate on supply is fixed at 0.62,
     * Fires: high_supply_archive_rate (guaranteed)
     */
    private function oversupplySteady(int $monthsAgo): array
    {
        // Supply grows slightly toward present — represents ongoing saturation.
        $supplyBase = 8_000 + (11 - $monthsAgo) * 150;
        $demandBase = 2_600;

        // Fixed ABOVE threshold → fulfillment 0.38 < 0.50
        return $this->split($supplyBase, 0.62, $demandBase, 0.15);
    }

    /**
     * Demand is ~3× supply. Demand archive rate fixed at 0.60 — buyers can't
     * find enough supply, posts expire unfulfilled.
     *
     * Fires: supply_opportunity (band always Undersupply)
     *      + high_demand_archive_rate (demand fulfillment 0.40 < 0.50, guaranteed)
     */
    private function undersupplyDemand(int $monthsAgo): array
    {
        $supplyBase = 2_000;
        // Demand grows month by month — worsening shortage.
        $demandBase = 5_500 + (11 - $monthsAgo) * 200;

        // Supply sells fast (low archive rate).
        // Demand archive fixed ABOVE threshold → fulfillment 0.40 < 0.50.
        return $this->split($supplyBase, 0.12, $demandBase, 0.60);
    }

    /**
     * Supply ≈ demand with healthy fulfillment. However, supply crashes in
     * the most recent month, creating a large negative MoM.
     *
     * Volume pattern:
     *   months 11–2 ago → stable ~4 000 kg supply
     *   month  1  ago   → crashes to ~1 600 kg  (MoM = −60%, below −20% threshold)
     *
     * Fires: declining_supply_volume (guaranteed)
     */
    private function balancedDeclining(int $monthsAgo): array
    {
        $supplyBase = $monthsAgo === 1 ? 1_600 : 4_000;
        $demandBase = 3_800;

        return $this->split($supplyBase, 0.15, $demandBase, 0.17);
    }

    /**
     * Seasonal peak in months 5–7 ago, elevated tail in month 2, sharp crash
     * in month 1 producing an extreme negative MoM.
     *
     * Volume pattern:
     *   months 5–7 ago → peak  ~9 000 kg supply, ~7 000 kg demand
     *   month  2  ago  → tail  ~3 500 kg supply
     *   month  1  ago  → crash   ~525 kg supply  (MoM ≈ −85%, below −20% threshold)
     *   other months   → quiet  ~1 200 kg supply
     *
     * Fires: declining_supply_volume (guaranteed)
     *
     * @return array{float, float, float, float}
     */
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

    // ─── Helper ───────────────────────────────────────────────────────────────

    /**
     * @return array{float, float, float, float}
     */
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
