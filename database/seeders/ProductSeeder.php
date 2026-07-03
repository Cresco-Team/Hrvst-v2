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

            // ── Vegetables + varieties (flat), local_name applies at the
            // vegetable level — every variety under it shares the same
            // common/regional name. Override per-variety only if you have
            // a confirmed case where that's actually wrong.
            //
            // ⚠ Local names below are best-effort, not verified against a
            // Cordilleran/Ilocano linguistic source. Confirm with a local
            // before this reaches real farmer/dealer users.
            $catalog = [
                'Leafy Vegetables' => [
                    'Lettuce' => ['local_name' => 'Letsugas', 'varieties' => ['Iceberg', 'Green Ice', 'Romaine']],
                    'Cabbage' => ['local_name' => 'Repolyo', 'varieties' => ['Scorpio', 'Wonderball', 'Rareball', 'Red', 'Chinese']],
                    'Celery' => ['local_name' => null, 'varieties' => ['Celery']],
                    'Broccoli' => ['local_name' => null, 'varieties' => ['Brocolli']],
                    'Onion Leeks' => ['local_name' => null, 'varieties' => ['Onion Leeks']],
                ],
                'Root Vegetables' => [
                    'Carrot' => ['local_name' => 'Karot', 'varieties' => ['Carrort']],
                    'Potato' => ['local_name' => 'Patatas', 'varieties' => ['Granola', 'LBR']],
                    'Radish' => ['local_name' => 'Labanos', 'varieties' => ['Long']],
                ],
                'Fruiting Vegetables' => [
                    'Tomato' => ['local_name' => 'Kamatis', 'varieties' => ['Tomato']],
                    'Cucumber' => ['local_name' => 'Pipino', 'varieties' => ['Cucumber']],
                    'Zucchini' => ['local_name' => null, 'varieties' => ['Zucchini']],
                    'Bell Pepper' => ['local_name' => null, 'varieties' => ['California (Open Field)', 'California (Greenhouse)', 'Sultan', 'Dongxin']],
                    'Chayote' => ['local_name' => 'Sayote', 'varieties' => ['Chayote']],
                ],
                'Bean Vegetables' => [
                    'Snap Beans' => ['local_name' => null, 'varieties' => ['Snap Beans']],
                    'Garden Peas' => ['local_name' => 'Gisantes', 'varieties' => ['Garden Peas']],
                ],
            ];

            foreach ($catalog as $categoryName => $vegetables) {
                foreach ($vegetables as $vegetableName => $entry) {
                    $localName = $entry['local_name'];

                    foreach ($entry['varieties'] as $varietyName) {
                        $normalizedVariety = $varietyName === $vegetableName ? null : $varietyName;

                        Vegetable::firstOrCreate([
                            'category_id' => $categories[$categoryName]->id,
                            'vegetable_name' => $vegetableName,
                            'variety_name' => $normalizedVariety,
                        ], [
                            'local_name' => $localName,
                        ]);
                    }
                }
            }

        });

        $this->command->info('✓ Vegetable seeded');
    }
}
