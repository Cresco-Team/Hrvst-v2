<?php

namespace App\Http\Controllers\Concerns;

use App\Data\Vegetable\VegetableDetailData;
use App\Enums\Analytics\VegetableViewerRole;
use App\Enums\Billing\SubscriptionFeature;
use App\Models\Billing\Subscription;
use App\Models\Product\Vegetable;
use App\Services\Product\VegetableDetailService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

trait RendersVegetableShow
{
    private function renderVegetableShow(
        Request $request,
        Vegetable $vegetable,
        VegetableDetailService $vegetableDetailService,
    ): Response {
        $vegetable->loadMissing('category');

        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2035'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);
        $user = $request->user();

        [$role, $gateFeature] = match (true) {
            $user->hasRole('admin') => [VegetableViewerRole::Admin, SubscriptionFeature::AdminAnalytics],
            $user->hasRole('farmer') => [VegetableViewerRole::Farmer, SubscriptionFeature::FarmerForecasts],
            $user->hasRole('dealer') => [VegetableViewerRole::Dealer, SubscriptionFeature::DealerMarketIntel],
            default => throw new \RuntimeException('User has no recognized role for variety viewer context.'),
        };

        $hasAnalyticsAccess = $gateFeature === null || Subscription::hasAccess($user, $gateFeature);

        return Inertia::render('shared/vegetables/Show', [
            'vegetable' => Inertia::defer(function () use ($vegetableDetailService, $vegetable, $year, $month, $role, $hasAnalyticsAccess, $gateFeature) {
                $detail = $vegetableDetailService->show($vegetable, $year, $month, $role);

                if (! $hasAnalyticsAccess) {
                    $detail->analytics = null;
                    $detail->monthly_activity = array_slice($detail->monthly_activity, -3);
                }

                $detail->analytics_locked = ! $hasAnalyticsAccess;
                $detail->upgrade_feature = $gateFeature?->value;

                return VegetableDetailData::fromModel($detail);
            }),
            'calendarFilters' => ['year' => $year, 'month' => $month],
            'meta' => [
                'vegetableId' => $vegetable->id,
                'vegetableLabel' => $vegetable->display_name,
                'categoryName' => $vegetable->category->name,
                'categorySlug' => $vegetable->category->slug,
            ],
        ]);
    }
}
