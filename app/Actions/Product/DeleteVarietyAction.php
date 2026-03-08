<?php

namespace App\Actions\Product;

use App\Models\Product\Variety;

class DeleteVarietyAction
{
    public function handle(Variety $variety): void
    {
        $variety->delete();
    }
}