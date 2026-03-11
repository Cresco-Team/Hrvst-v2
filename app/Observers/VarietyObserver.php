<?php

namespace App\Observers;

use App\Models\Product\Variety;
use Illuminate\Support\Facades\Cache;

class VarietyObserver
{
    /**
     * Handle the Variety "created" event.
     */
    public function created(Variety $variety): void
    {
        Cache::forget('vegetable_options');
        Cache::forget('category_options');
        Cache::forget('dealer_demand_variety_options');
        Cache::forget('farmer_supply_variety_options');
    }

    public function updated(Variety $variety): void
    {
        Cache::forget('vegetable_options');
        Cache::forget('category_options');
        Cache::forget('dealer_demand_variety_options');
        Cache::forget('farmer_supply_variety_options');
    }

    public function saved(Variety $variety): void
    {
        Cache::forget('vegetable_options');
        Cache::forget('category_options');
        Cache::forget('dealer_demand_variety_options');
        Cache::forget('farmer_supply_variety_options');
    }

    /**
     * Handle the Variety "deleted" event.
     */
    public function deleted(Variety $variety): void
    {
        Cache::forget('vegetable_options');
        Cache::forget('category_options');
        Cache::forget('dealer_demand_variety_options');
        Cache::forget('farmer_supply_variety_options');
    }

    /**
     * Handle the Variety "restored" event.
     */
    public function restored(Variety $variety): void
    {
        //
    }

    /**
     * Handle the Variety "force deleted" event.
     */
    public function forceDeleted(Variety $variety): void
    {
        //
    }
}
