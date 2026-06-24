<?php

namespace App\Actions\Product;

use App\Models\Product\Variety;

class CreateVarietyAction
{
    public function handle(array $validated): Variety
    {
        $variety = Variety::create($validated);

        return $variety;
    }
}
