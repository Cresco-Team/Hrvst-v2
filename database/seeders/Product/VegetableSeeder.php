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
                'Cabbage Scorpio',
                'Cabbage Wonderball',
                'Cabbage Rareball',
                'Pechay',
                'Celery',
                'Broccoli',
                'Cauliflower',
                'Spinach',
            ],
            'Root Vegetables' => [
                'Carrot',
                'Potato Granola',
                'Potato LBR',
                'Radish Long',
                'Sayote',
                'Turnip',
                'Beet',
            ],
            'Fruiting Vegetables' => [
                'Tomato',
                'Eggplant',
                'Cucumber',
                'Bell Pepper',
                'Lemon',
                'Chayote',
            ],
            'Bean Vegetables' => [
                'Snap Beans',
                'Garden Peas',
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
