<?php

namespace App\Http\Controllers\Farmer;

use App\Data\Vegetable\VegetableWasteData;
use App\Http\Controllers\Controller;
use App\Services\Product\VegetableWasteForecastService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly VegetableWasteForecastService $wasteForecast,
    ) {}

    public function index(): Response
    {
        return Inertia::render('farmer/Dashboard', [
            'topWastedDemand' => Inertia::defer(
                fn () => VegetableWasteData::collect($this->wasteForecast->topWastedDemand())
            ),
        ]);
    }
}
