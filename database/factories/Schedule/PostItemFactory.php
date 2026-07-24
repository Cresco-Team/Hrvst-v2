<?php

namespace Database\Factories\Marketplace;

use App\Enums\PostItemStatus;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use App\Models\Vegetable\Vegetable;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostItemFactory extends Factory
{
    protected $model = PostItem::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'vegetable_id' => Vegetable::factory(),
            'quantity_kg' => fake()->randomFloat(2, 10, 500),
            'status' => PostItemStatus::Ongoing,
        ];
    }
}
