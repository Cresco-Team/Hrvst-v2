<?php

namespace App\Http\Controllers\Dealer;

use App\Data\Vegetable\VegetableStabilityData;
use App\Data\Vegetable\VegetableWasteData;
use App\Enums\Billing\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use App\Services\Vegetable\VegetableWasteAnalyticsService;
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
        $hasAccess = Subscription::hasAccess($request->user(), SubscriptionFeature::DealerMarketIntel);

        return Inertia::render('dealer/Dashboard', [
            'topWastedSupply' => $hasAccess
                ? Inertia::defer(fn () => VegetableWasteData::collect($this->wasteAnalytics->topWastedSupply()))
                : null,
            'mostStableWastedSupply' => $hasAccess
                ? Inertia::defer(fn () => VegetableStabilityData::collect($this->wasteAnalytics->mostStableWastedSupply()))
                : null,
            'analyticsLocked' => ! $hasAccess,
            'upgradeFeatureLabel' => SubscriptionFeature::DealerMarketIntel->label(),
        ]);
    }
}
