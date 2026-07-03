<?php

use App\Actions\PostItem\FulfillPostItemAction;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Vegetable;

it('records vegetable_monthly_stats when a post item is fulfilled', function () {
    $vegetable = Vegetable::factory()->create();
    $post = Post::factory()->create(['type' => PostType::Supply]);
    $item = PostItem::factory()->create([
        'post_id' => $post->id,
        'vegetable_id' => $vegetable->id,
        'quantity_kg' => 12.5,
    ]);

    (new FulfillPostItemAction)->handle($item);

    $this->assertDatabaseHas('vegetable_monthly_stats', [
        'vegetable_id' => $vegetable->id,
        'supply_fulfilled_kg' => 12.5,
    ]);
});
