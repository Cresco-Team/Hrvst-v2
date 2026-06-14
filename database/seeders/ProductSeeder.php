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
                    'Cabbage: Scorpio',
                    'Cabbage: Wonderball',
                    'Cabbage: Rareball',
                    'Cabbage: Red',
                    'Celery',
                    'Broccoli',
                    'Onion Leeks',
                ],
                'Root Vegetables' => [
                    'Carrot',
                    'Potato: Granola',
                    'Potato: LBR',
                    'Radish: Long',
                ],
                'Fruiting Vegetables' => [
                    'Tomato: Green',
                    'Tomato: Half-Ripe',
                    'Cucumber',
                    'Zucchini',
                    'Bell Pepper: California (Open Field)',
                    'Bell Pepper: California (Greenhouse)',
                    'Bell Pepper: Sultan',
                    'Bell Pepper: Dongxin (Green)',
                    'Bell Pepper: Dongxin (Red)',
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
                'Cabbage: Scorpio' => ['1st Class', '2nd Class'],
                'Cabbage: Wonderball' => ['1st Class', '2nd Class'],
                'Cabbage: Rareball' => ['1st Class', '2nd Class'],
                'Cabbage: Red' => ['Good', 'Semi'],
                'Celery' => ['All'],
                'Broccoli' => ['Good', 'Semi'],
                'Onion Leeks' => ['All'],

                // Root Vegetables
                'Carrot' => ['Big', 'ML', 'Medium'],
                'Potato: Granola' => ['SXL', 'XL', '3XL', 'Extra', 'Marble'],
                'Potato: LBR' => ['SXL', 'XL', '3XL', 'Extra', 'Marble'],
                'Radish: Long' => ['Good', 'Good-semi', 'Big'],

                // Fruiting Vegetables
                'Tomato: Green' => ['Big', 'Medium', 'Small'],
                'Tomato: Half-Ripe' => ['Big', 'Medium', 'Small'],
                'Cucumber' => ['Good', 'Semi'],
                'Zucchini' => ['Good', 'Semi'],
                'Bell Pepper: California (Open Field)' => ['Big'],
                'Bell Pepper: California (Greenhouse)' => ['Big', 'Medium', 'Small'],
                'Bell Pepper: Sultan' => ['Big', 'Medium'],
                'Bell Pepper: Dongxin (Green)' => ['Big'],
                'Bell Pepper: Dongxin (Red)' => ['Big', 'Medium'],
                'Chayote' => ['1st Class', '2nd Class', 'Prickly Green'],

                // Bean Vegetables
                'Snap Beans' => ['1st Class', '2nd Class'],
                'Garden Peas' => ['All'],
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
