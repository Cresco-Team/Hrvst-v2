<?php

namespace App\Http\Controllers\Dealer;

use App\Data\Vegetable\VegetableWasteData;
use App\Http\Controllers\Controller;
use App\Services\Product\VegetableWasteAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly VegetableWasteAnalyticsService $wasteForecast,
    ) {}

    public function index(): Response
    {
        return Inertia::render('dealer/Dashboard', [
            'topWastedSupply' => Inertia::defer(
                fn () => VegetableWasteData::collect($this->wasteForecast->topWastedSupply())
            ),
            'mostStableWastedSupply' => Inertia::defer(
                fn () => VegetableWasteData::collect($this->wasteForecast->mostStableWastedSupply())
            ),
        ]);
    }
}
