<?php

namespace App\Http\Controllers\Farmer;

use App\Data\Vegetable\VegetableStabilityData;
use App\Data\Vegetable\VegetableWasteData;
use App\Http\Controllers\Controller;
use App\Services\Product\VegetableWasteAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly VegetableWasteAnalyticsService $wasteAnalytics,
    ) {}

    public function index(): Response
    {
        return Inertia::render('farmer/Dashboard', [
            'topWastedDemand' => Inertia::defer(
                fn () => VegetableWasteData::collect($this->wasteAnalytics->topWastedDemand())
            ),
            'mostStableWastedDemand' => Inertia::defer(
                fn () => VegetableStabilityData::collect($this->wasteAnalytics->mostStableWastedDemand())
            ),
        ]);
    }
}
