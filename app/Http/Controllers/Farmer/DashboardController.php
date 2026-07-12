<?php

namespace App\Http\Controllers\Farmer;

use App\Data\Vegetable\VegetableStabilityData;
use App\Data\Vegetable\VegetableWasteData;
use App\Enums\Billing\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use App\Services\Product\VegetableWasteAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly VegetableWasteAnalyticsService $wasteAnalytics,
    ) {}

    public function index(Request $request): Response
    {
        $hasAccess = Subscription::hasAccess($request->user(), SubscriptionFeature::FarmerForecasts);

        return Inertia::render('farmer/Dashboard', [
            'topWastedDemand' => $hasAccess
                ? Inertia::defer(fn () => VegetableWasteData::collect($this->wasteAnalytics->topWastedDemand()))
                : null,
            'mostStableWastedDemand' => $hasAccess
                ? Inertia::defer(fn () => VegetableStabilityData::collect($this->wasteAnalytics->mostStableWastedDemand()))
                : null,
            'analyticsLocked' => ! $hasAccess,
            'upgradeFeatureLabel' => SubscriptionFeature::FarmerForecasts->label(),
        ]);
    }
}
