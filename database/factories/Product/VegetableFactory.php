<?php

namespace Database\Factories\Product;

use App\Models\Product\Category;
use App\Models\Product\Vegetable;
use Illuminate\Database\Eloquent\Factories\Factory;

class VegetableFactory extends Factory
{
    protected $model = Vegetable::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::inRandomOrder()->first()->id,
            'vegetable_name' => fake()->unique()->word(),
            'variety_name' => null,
            'local_name' => null,
        ];
    }

    public function withVariety(): static
    {
        return $this->state(fn () => [
            'variety_name' => fake()->word(),
        ]);
    }
}
