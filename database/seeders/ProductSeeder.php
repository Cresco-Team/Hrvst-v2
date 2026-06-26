<?php

namespace Database\Seeders;

use App\Models\Product\Category;
use App\Models\Product\Variety;
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

            // ── Vegetables ────────────────────────────────────────────────────
            $vegetablesByCategory = [
                'Leafy Vegetables' => [
                    'Lettuce',
                    'Cabbage',
                    'Celery',
                    'Broccoli',
                    'Onion Leeks',
                ],
                'Root Vegetables' => [
                    'Carrot',
                    'Potato',
                    'Radish',
                ],
                'Fruiting Vegetables' => [
                    'Tomato',
                    'Cucumber',
                    'Zucchini',
                    'Bell Pepper',
                    'Chayote',
                ],
                'Bean Vegetables' => [
                    'Snap Beans',
                    'Garden Peas',
                ],
            ];

            foreach ($vegetablesByCategory as $categoryName => $vegetableNames) {
                foreach ($vegetableNames as $vegetableName) {
                    Vegetable::firstOrCreate([
                        'category_id' => $categories[$categoryName]->id,
                        'name' => $vegetableName,
                    ]);
                }
            }

            // Cache all vegetables keyed by name — avoids N+1 in varieties loop
            $vegetables = Vegetable::all()->keyBy('name');

            // ── Varieties ─────────────────────────────────────────────────────
            $varietiesByVegetable = [
                // Leafy Vegetables
                'Lettuce' => ['Iceberg', 'Green Ice', 'Romaine'],
                'Cabbage' => ['Scorpio', 'Wonderball', 'Rareball', 'Red', 'Chinese'],
                'Celery' => ['Celery'],
                'Broccoli' => ['Brocolli'],
                'Onion Leeks' => ['Onion Leeks'],

                // Root Vegetables
                'Carrot' => ['Carrort'],
                'Potato' => ['Granola', 'LBR'],
                'Radish' => ['Long'],

                // Fruiting Vegetables
                'Tomato' => ['Tomato'],
                'Cucumber' => ['Cucumber'],
                'Zucchini' => ['Zucchini'],
                'Bell Pepper' => ['California (Open Field)', 'California (Greenhouse)', 'Sultan', 'Dongxin'],
                'Chayote' => ['Chayote'],

                // Bean Vegetables
                'Snap Beans' => ['Snap Beans'],
                'Garden Peas' => ['Garden Peas'],
            ];

            foreach ($varietiesByVegetable as $vegetableName => $varietyNames) {
                $vegetable = $vegetables->get($vegetableName)
                    ?? throw new \RuntimeException("Vegetable not found in DB: '{$vegetableName}'");

                foreach ($varietyNames as $varietyName) {
                    Variety::firstOrCreate([
                        'vegetable_id' => $vegetable->id,
                        'name' => $varietyName,
                    ]);
                }
            }

        });

        $this->command->info('✓ Products seeded');
    }
}
