<?php

namespace App\Http\Controllers\Dealer;

use App\Data\Vegetable\VegetableWasteData;
use App\Http\Controllers\Controller;
use App\Services\Product\VegetableWasteAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly VegetableWasteAnalyticsService $wasteAnalytics,
    ) {}

    public function index(): Response
    {
        return Inertia::render('dealer/Dashboard', [
            'topWastedSupply' => Inertia::defer(
                fn () => VegetableWasteData::collect($this->wasteAnalytics->topWastedSupply())
            ),
        ]);
    }
}
