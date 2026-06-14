<?php

namespace Database\Factories\Marketplace;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostItemFactory extends Factory
{
    protected $model = PostItem::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'variety_id' => Variety::factory(),
            'quantity_kg' => fake()->randomFloat(2, 10, 500),
            'status' => PostItemStatus::Ongoing,
        ];
    }
}
