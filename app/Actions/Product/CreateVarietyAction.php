<?php

namespace App\Actions\Product;

use App\Models\Product\Variety;

class CreateVarietyAction
{
    public function handle(array $validated): Variety
    {
        $priceMin = $validated['price_min'];
        $priceMax = $validated['price_max'];
        unset($validated['price_min'], $validated['price_max']);

        $variety = Variety::create($validated);

        $variety->prices()->create([
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'recorded_at' => now()->startOfWeek(),
        ]);

        return $variety->load('latestPrice');
    }
}
