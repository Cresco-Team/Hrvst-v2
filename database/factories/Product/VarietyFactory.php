<?php

namespace Database\Factories\Product;

use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Database\Eloquent\Factories\Factory;

class VarietyFactory extends Factory
{
    protected $model = Variety::class;

    public function definition(): array
    {
        return [
            'vegetable_id' => Vegetable::factory(),
            'name' => fake()->unique()->word().' Variety',
            'hearts_count' => 0,
        ];
    }
}
