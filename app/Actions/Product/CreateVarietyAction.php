<?php

namespace App\Actions\Product;

use App\Models\Product\Variety;
use Illuminate\Http\UploadedFile;

class CreateVarietyAction
{
    public function handle(array $validated, ?UploadedFile $image = null): Variety
    {
        $priceMin = $validated['price_min'];
        $priceMax = $validated['price_max'];
        unset($validated['price_min'], $validated['price_max']);

        $variety = Variety::create($validated);

        if ($image !== null) {
            $variety->addMedia($image)->toMediaCollection('variety_image');
        };

        $variety->prices()->create([
            'price_min'   => $priceMin,
            'price_max'   => $priceMax,
            'recorded_at' => now()->startOfWeek(),
        ]);

        return $variety->load('latestPrice');
    }
}