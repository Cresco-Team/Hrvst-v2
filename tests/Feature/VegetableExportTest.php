<?php

use App\Models\Product\Category;
use App\Models\Product\Vegetable;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->category = Category::create(['name' => 'Leafy Greens']);
    $this->vegetable = Vegetable::create([
        'category_id' => $this->category->id,
        'vegetable_name' => 'Pechay',
    ]);
});

it('exports vegetable activity as csv', function () {
    actingAs(createFarmerUser())
        ->get(route('vegetables.export', $this->vegetable))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('redirects guests to login', function () {
    $this->get(route('vegetables.export', $this->vegetable))
        ->assertRedirect(route('login'));
});

it('returns 404 for a nonexistent vegetable', function () {
    actingAs(createFarmerUser())
        ->get(route('vegetables.export', 999999))
        ->assertNotFound();
});
