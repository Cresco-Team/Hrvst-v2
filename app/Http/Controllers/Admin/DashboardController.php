<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Billing\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use App\Services\Admin\DashboardService;
use App\Services\Admin\RegistrationTrendService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardService $dashboardService,
        RegistrationTrendService $registrationTrendService,
    ): Response {
        $hasAnalyticsAccess = Subscription::hasAccess($request->user(), SubscriptionFeature::AdminAnalytics);

        return Inertia::render('admin/Dashboard', [
            'kpis' => Inertia::defer(fn () => $dashboardService->getKPIs()),
            'registrationTrends' => $hasAnalyticsAccess
                ? Inertia::defer(fn () => $registrationTrendService->monthly())
                : null,
            'analyticsLocked' => ! $hasAnalyticsAccess,
            'upgradeFeatureLabel' => SubscriptionFeature::AdminAnalytics->label(),

        ]);
    }
}
