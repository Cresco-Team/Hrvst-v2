<?php

namespace App\Observers;

use App\Models\Product\Vegetable;
use Illuminate\Support\Facades\Cache;

class VegetableObserver
{
    public function saved(Vegetable $vegetable): void
    {
        $this->bustCaches();
    }

    public function deleted(Vegetable $vegetable): void
    {
        $this->bustCaches();
    }

    private function bustCaches(): void
    {
        Cache::forget('vegetable_options');
        Cache::forget('category_options');
        Cache::forget('dealer_demand_variety_options');
        Cache::forget('farmer_supply_variety_options');
    }
}
