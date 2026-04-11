<?php

namespace Database\Seeders;

use App\Models\Product\Variety;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VarietyMonthlyStatsSeeder extends Seeder
{
    /**
     * Generates 12 months of rolling variety_monthly_stats rows per variety.
     *
     * Each variety is randomly assigned one of four market archetypes that
     * control how supply and demand volumes behave over time. This produces
     * a dataset rich enough to exercise every analytics rule in
     * VarietyAnalyticsService without hand-crafting individual rows.
     *
     * Archetypes:
     *   oversupply_steady    — supply consistently outpaces demand (saturation risk)
     *   undersupply_demand   — demand exceeds supply (opportunity signal)
     *   balanced_healthy     — supply ≈ demand, high fulfillment both sides
     *   seasonal_spike       — large volume in months 3–5 (mid-window), quiet otherwise
     */
    public function run(): void
    {
        DB::table('variety_monthly_stats')->truncate();

        $varieties = Variety::pluck('id');

        if ($varieties->isEmpty()) {
            $this->command->warn('No varieties found. Run VegetableSeeder first.');

            return;
        }

        $archetypes = [
            'oversupply_steady',
            'undersupply_demand',
            'balanced_healthy',
            'seasonal_spike',
        ];

        $rows = [];

        foreach ($varieties as $varietyId) {
            $archetype = $archetypes[array_rand($archetypes)];

            for ($i = 11; $i >= 0; $i--) {
                $date = now()->startOfMonth()->subMonths($i);

                [$supplyFulfilled, $supplyArchived, $demandFulfilled, $demandArchived]
                    = $this->generateVolumes($archetype, $i);

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

        // Insert in chunks — avoids hitting DB placeholder limits on large variety sets
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('variety_monthly_stats')->insert($chunk);
        }

        $this->command->info(
            sprintf(
                'Seeded %d variety_monthly_stats rows across %d varieties.',
                count($rows),
                $varieties->count(),
            )
        );
    }

    /**
     * Returns [supplyFulfilled, supplyArchived, demandFulfilled, demandArchived].
     *
     * $monthsAgo goes from 11 (oldest) → 0 (most recent), allowing archetypes
     * to express trends over time (e.g. rising demand, declining supply).
     *
     * @return array{float, float, float, float}
     */
    private function generateVolumes(string $archetype, int $monthsAgo): array
    {
        return match ($archetype) {
            // ── Oversupply: supply ~3x demand, archive rate high on supply side
            'oversupply_steady' => $this->oversupplyVolumes($monthsAgo),

            // ── Undersupply: demand outpaces supply, demand archive rate high
            'undersupply_demand' => $this->undersupplyVolumes($monthsAgo),

            // ── Balanced: supply ≈ demand, both sides fulfilling well (>70%)
            'balanced_healthy' => $this->balancedVolumes($monthsAgo),

            // ── Seasonal: spike in the middle of the window, quiet at edges
            'seasonal_spike' => $this->seasonalVolumes($monthsAgo),
        };
    }

    // ─── Archetype generators ─────────────────────────────────────────────────

    /** @return array{float, float, float, float} */
    private function oversupplyVolumes(int $monthsAgo): array
    {
        // Supply is 2.5×–4× demand. Farmers flooding market.
        // Supply archive rate: 40–65% (bad). Demand archive rate: 10–25% (ok, dealers find supply).
        $baseSupply = $this->jitter(8_000, 0.25);
        $baseDemand = $this->jitter(2_500, 0.20);

        // Slight downward price pressure trend: supply grows over recent months
        $supplyMultiplier = 1 + (0.02 * (11 - $monthsAgo));  // grows toward present
        $baseSupply = (int) ($baseSupply * $supplyMultiplier);

        $supplyArchiveRate = $this->jitter(0.52, 0.15);
        $demandArchiveRate = $this->jitter(0.17, 0.10);

        return $this->split($baseSupply, $supplyArchiveRate, $baseDemand, $demandArchiveRate);
    }

    /** @return array{float, float, float, float} */
    private function undersupplyVolumes(int $monthsAgo): array
    {
        // Demand is 2×–3× supply. Dealers can't find enough.
        // Demand archive rate: 45–70% (bad). Supply archive rate: 8–20% (good, sells fast).
        $baseSupply = $this->jitter(2_000, 0.20);
        $baseDemand = $this->jitter(5_500, 0.25);

        // Demand is growing over time (getting worse)
        $demandMultiplier = 1 + (0.015 * (11 - $monthsAgo));
        $baseDemand = (int) ($baseDemand * $demandMultiplier);

        $supplyArchiveRate = $this->jitter(0.14, 0.08);
        $demandArchiveRate = $this->jitter(0.57, 0.15);

        return $this->split($baseSupply, $supplyArchiveRate, $baseDemand, $demandArchiveRate);
    }

    /** @return array{float, float, float, float} */
    private function balancedVolumes(int $monthsAgo): array
    {
        // Supply ≈ demand with mild fluctuation.
        // Both fulfillment rates high: 70–90%. This is the healthy market signal.
        $base = $this->jitter(4_000, 0.20);

        // Slight demand growth trend — triggers "strong market signal" rec in recent months
        $demandMultiplier = 1 + (0.01 * (11 - $monthsAgo));
        $baseDemand = (int) ($base * $demandMultiplier);

        $supplyArchiveRate = $this->jitter(0.15, 0.07);
        $demandArchiveRate = $this->jitter(0.18, 0.07);

        return $this->split($base, $supplyArchiveRate, $baseDemand, $demandArchiveRate);
    }

    /** @return array{float, float, float, float} */
    private function seasonalVolumes(int $monthsAgo): array
    {
        // High volume in the middle of the 12-month window (months 4–7 ago).
        // Quiet at both edges. Archive rates are moderate throughout.
        $isSpike = $monthsAgo >= 4 && $monthsAgo <= 7;

        $baseSupply = $isSpike ? $this->jitter(9_000, 0.20) : $this->jitter(1_200, 0.30);
        $baseDemand = $isSpike ? $this->jitter(7_000, 0.20) : $this->jitter(1_000, 0.30);

        $supplyArchiveRate = $this->jitter(0.30, 0.12);
        $demandArchiveRate = $this->jitter(0.28, 0.12);

        return $this->split($baseSupply, $supplyArchiveRate, $baseDemand, $demandArchiveRate);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Splits a total volume into [fulfilled, archived] using the given archive rate.
     * Returns four rounded floats.
     *
     * @return array{float, float, float, float}
     */
    private function split(
        float $supplyBase,
        float $supplyArchiveRate,
        float $demandBase,
        float $demandArchiveRate,
    ): array {
        $supplyArchiveRate = max(0.0, min(1.0, $supplyArchiveRate));
        $demandArchiveRate = max(0.0, min(1.0, $demandArchiveRate));

        return [
            round($supplyBase * (1 - $supplyArchiveRate), 2),  // supply_fulfilled
            round($supplyBase * $supplyArchiveRate, 2),         // supply_archived
            round($demandBase * (1 - $demandArchiveRate), 2),  // demand_fulfilled
            round($demandBase * $demandArchiveRate, 2),         // demand_archived
        ];
    }

    /**
     * Applies a gaussian-like jitter to a base value using a uniformly sampled
     * deviation. Cheaper than GMP-based normal distribution for seeding purposes.
     *
     * $spread controls the ± range as a fraction of $base.
     */
    private function jitter(float $base, float $spread): float
    {
        $deviation = $base * $spread;

        return $base + (lcg_value() * 2 - 1) * $deviation;
    }
}
