<?php

namespace App\Actions\Product;

use App\Models\Product\PriceHistory;
use App\Models\Product\Variety;

class AddVarietyPriceAction
{
    public function handle(Variety $variety, float $priceMin, float $priceMax): PriceHistory
    {
        return PriceHistory::updateOrCreate([
            'variety_id' => $variety->id,
            'recorded_at' => now()->startOfWeek(),
        ], [
            'price_min' => $priceMin,
            'price_max' => $priceMax,
        ],
        );
    }
}
