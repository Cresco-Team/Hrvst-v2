<?php

namespace Database\Seeders;

use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Leafy Vegetables',
            'Root Vegetables',
            'Fruiting Vegetables',
            'Bean Vegetables',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        $data = [
            'Leafy Vegetables' => [
                'Lettuce',
                'Cabbage Scorpio',
                'Cabbage Wonderball',
                'Cabbage Rareball',
                'Celery',
                'Broccoli',
            ],
            'Root Vegetables' => [
                'Carrot',
                'Potato Granola',
                'Potato LBR',
                'Radish Long',
                'Sayote',
            ],
            'Fruiting Vegetables' => [
                'Tomato',
                'Cucumber',
                'Bell Pepper',
                'Chayote',
            ],
            'Bean Vegetables' => [
                'Snap Beans',
                'Garden Peas',
            ],
        ];

        foreach ($data as $categoryName => $vegetables) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            foreach ($vegetables as $vegetableName) {
                Vegetable::firstOrCreate([
                    'category_id' => $category->id,
                    'name' => $vegetableName,
                ]);
            }
        }

        $data = [
            // ── Leafy Vegetables ─────────────────────────────────────────────
            'Lettuce' => [
                ['name' => 'Iceberg'],
                ['name' => 'Green Ice'],
                ['name' => 'Romaine'],
            ],
            'Cabbage Scorpio' => [
                ['name' => '1st Class'],
                ['name' => '2nd Class'],
            ],
            'Cabbage Wonderball' => [
                ['name' => '1st Class'],
                ['name' => '2nd Class'],
                ['name' => 'Big Size'],
            ],
            'Cabbage Rareball' => [
                ['name' => '1st Class'],
                ['name' => '2nd Class'],
            ],
            'Celery' => [
                ['name' => 'Good'],
                ['name' => 'Semi'],
            ],
            'Broccoli' => [
                ['name' => 'Good'],
                ['name' => 'Semi'],
            ],

            // ── Root Vegetables ───────────────────────────────────────────────
            'Carrot' => [
                ['name' => 'Big'],
                ['name' => 'ML'],
                ['name' => 'Medium'],
            ],
            'Potato Granola' => [
                ['name' => 'SXL'],
                ['name' => 'XL'],
                ['name' => '3XL'],
                ['name' => 'Extra'],
                ['name' => 'Marble'],
            ],
            'Potato LBR' => [
                ['name' => 'SXL'],
                ['name' => 'XL'],
                ['name' => '3XL'],
                ['name' => 'Extra'],
                ['name' => 'Marble'],
            ],
            'Radish Long' => [
                ['name' => 'Good'],
                ['name' => 'Good-semi'],
                ['name' => 'Big size'],
                ['name' => 'Putol'],
            ],
            'Sayote' => [
                ['name' => 'Green Prickly'],
                ['name' => 'White Smooth'],
                ['name' => 'Spineless Green'],
            ],

            // ── Fruiting Vegetables ───────────────────────────────────────────
            'Tomato' => [
                ['name' => 'Green Big-Jumbo'],
                ['name' => 'Green Medium'],
                ['name' => 'Half Ripe Big-Jumbo'],
            ],
            'Cucumber' => [
                ['name' => 'Good'],
                ['name' => 'Semi'],
            ],
            'Bell Pepper' => [
                ['name' => 'California Big'],
                ['name' => 'California Medium'],
                ['name' => 'California Small'],
                ['name' => 'Dongxin (Green) Big'],
                ['name' => 'Dongxin (Red) Big'],
                ['name' => 'Dongxin (Red) Medium'],
                ['name' => 'Sultan Big'],
                ['name' => 'Sultan Medium'],
            ],
            'Chayote' => [
                ['name' => '1st Class'],
                ['name' => '2nd Class'],
                ['name' => 'Prickly Green'],
            ],

            // ── Bean Vegetables ───────────────────────────────────────────────
            'Snap Beans' => [
                ['name' => 'Good'],
                ['name' => 'Semi'],
            ],
            'Garden Peas' => [
                ['name' => 'Chinese'],
            ],
        ];

        foreach ($data as $vegetableName => $varieties) {
            $vegetable = Vegetable::where('name', $vegetableName)->firstOrFail();

            foreach ($varieties as $variety) {
                Variety::firstOrCreate(
                    ['vegetable_id' => $vegetable->id, 'name' => $variety['name']],
                );
            }
        }

        $this->command->info('✓ Products seeded');
    }
}
