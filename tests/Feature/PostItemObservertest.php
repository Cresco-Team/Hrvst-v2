<?php

use App\Actions\PostItem\FulfillPostItemAction;

it('records vegetable_monthly_stats when a post item is fulfilled', function () {
    $farmer = createFarmerUser();
    $vegetable = createVegetable();
    $post = createSupplyPost($farmer, $vegetable);
    $item = $post->postItems()->firstOrFail();

    (new FulfillPostItemAction)->handle($item);

    $this->assertDatabaseHas('vegetable_monthly_stats', [
        'vegetable_id' => $vegetable->id,
        'supply_fulfilled_kg' => (float) $item->quantity_kg,
    ]);
});
