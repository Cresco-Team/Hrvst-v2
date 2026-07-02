<?php

namespace Database\Seeders;

use App\Models\Product\Category;
use App\Models\Product\Vegetable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ── Categories ────────────────────────────────────────────────────
            $categoryNames = [
                'Leafy Vegetables',
                'Root Vegetables',
                'Fruiting Vegetables',
                'Bean Vegetables',
            ];

            $categories = collect($categoryNames)
                ->mapWithKeys(fn ($name) => [
                    $name => Category::firstOrCreate(['name' => $name]),
                ]);

            // ── Vegetables + varieties (flat) ────────────────────────────────
            $catalog = [
                'Leafy Vegetables' => [
                    'Lettuce' => ['Iceberg', 'Green Ice', 'Romaine'],
                    'Cabbage' => ['Scorpio', 'Wonderball', 'Rareball', 'Red', 'Chinese'],
                    'Celery' => ['Celery'],
                    'Broccoli' => ['Brocolli'],
                    'Onion Leeks' => ['Onion Leeks'],
                ],
                'Root Vegetables' => [
                    'Carrot' => ['Carrort'],
                    'Potato' => ['Granola', 'LBR'],
                    'Radish' => ['Long'],
                ],
                'Fruiting Vegetables' => [
                    'Tomato' => ['Tomato'],
                    'Cucumber' => ['Cucumber'],
                    'Zucchini' => ['Zucchini'],
                    'Bell Pepper' => ['California (Open Field)', 'California (Greenhouse)', 'Sultan', 'Dongxin'],
                    'Chayote' => ['Chayote'],
                ],
                'Bean Vegetables' => [
                    'Snap Beans' => ['Snap Beans'],
                    'Garden Peas' => ['Garden Peas'],
                ],
            ];

            foreach ($catalog as $categoryName => $vegetables) {
                foreach ($vegetables as $vegetableName => $varietyNames) {
                    foreach ($varietyNames as $varietyName) {
                        // Self-named "variety" (legacy of the old FK model) means
                        // no real variety distinction — normalize to null.
                        $normalizedVariety = $varietyName === $vegetableName ? null : $varietyName;

                        Vegetable::firstOrCreate([
                            'category_id' => $categories[$categoryName]->id,
                            'vegetable_name' => $vegetableName,
                            'variety_name' => $normalizedVariety,
                        ]);
                    }
                }
            }

        });

        $this->command->info('✓ Products seeded');
    }
}
