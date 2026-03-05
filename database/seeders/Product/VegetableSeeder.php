<?php

namespace Database\Seeders\Product;

use App\Models\Product\Category;
use App\Models\Product\Vegetable;
use Illuminate\Database\Seeder;

class VegetableSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Leafy Vegetables' => [
                'Lettuce',
                'Cabbage',
                'Pechay',
                'Celery',
                'Broccoli',
                'Cauliflower',
                'Chinese Cabbage',
                'Spinach',
            ],
            'Root Vegetables' => [
                'Carrot',
                'Potato',
                'Radish',
                'Sayote',
                'Turnip',
                'Beet',
            ],
            'Fruiting Vegetables' => [
                'Tomato',
                'Eggplant',
                'Bitter Melon',
                'Bell Pepper',
                'Squash',
                'Chayote',
            ],
            'Bean Vegetables' => [
                'Snow Peas',
                'String Beans',
                'Green Beans',
                'Sugar Snap Peas',
                'Chicharo',
                'Broad Beans',
            ],
        ];

        foreach ($data as $categoryName => $vegetables) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            foreach ($vegetables as $vegetableName) {
                Vegetable::firstOrCreate([
                    'category_id' => $category->id,
                    'name'        => $vegetableName,
                ]);
            }
        }
    }
}
