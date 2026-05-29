<?php

namespace Database\Seeders\Product;

use App\Models\Product\PriceHistory;
use App\Models\Product\Variety;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class PriceHistorySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $weeks = 12;
        $start = now()->startOfWeek()->subWeeks($weeks);
        $rows = [];

        foreach (Variety::pluck('id') as $varietyId) {
            for ($i = 0; $i < $weeks; $i++) {
                $min = $faker->randomFloat(2, 20, 50);

                $rows[] = [
                    'variety_id' => $varietyId,
                    'price_min' => $min,
                    'price_max' => fake()->randomFloat(2, $min + 5, 90),
                    'recorded_at' => $start->copy()->addWeeks($i),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        collect($rows)->chunk(500)->each(function ($chunk) {
            PriceHistory::upsert(
                $chunk->toArray(),
                ['variety_id', 'recorded_at'],
                ['price_min', 'price_max', 'updated_at'],
            );
        });

        $this->command->info('✓ Price history seeded');
    }
}
