<?php

namespace Database\Seeders\Product;

use App\Models\Product\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
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
    }
}