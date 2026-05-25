<?php

namespace App\Actions\Product;

use App\Models\Product\Variety;

class UpdateVarietyAction
{
    public function handle(Variety $variety, array $validated): Variety
    {
        $variety->update($validated);

        return $variety->load('latestPrice');
    }
}
