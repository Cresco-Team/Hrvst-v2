<?php

namespace App\Http\Controllers\Concerns;

use App\Data\Vegetable\VegetableDetailData;
use App\Enums\Analytics\VegetableViewerRole;
use App\Enums\Billing\SubscriptionFeature;
use App\Models\Billing\Subscription;
use App\Models\Vegetable\Vegetable;
use App\Services\Vegetable\VegetableActivityService;
use App\Services\Vegetable\VegetableDetailService;
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
            'activity_offset' => [
                'sometimes', 'integer', 'min:0', 'max:'.VegetableActivityService::MAX_OFFSET_MONTHS,
            ],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);
        $user = $request->user();

        [$role, $gateFeature] = match (true) {
            $user->hasRole('admin') => [VegetableViewerRole::Admin, SubscriptionFeature::AdminAnalytics],
            $user->hasRole('farmer') => [VegetableViewerRole::Farmer, SubscriptionFeature::FarmerForecasts],
            $user->hasRole('dealer') => [VegetableViewerRole::Dealer, SubscriptionFeature::DealerMarketIntel],
            default => throw new \RuntimeException('User has no recognized role for vegetable viewer context.'),
        };

        $hasForecastAccess = $gateFeature === null || Subscription::hasAccess($user, $gateFeature);

        $activityOffset = $hasForecastAccess ? (int) ($validated['activity_offset'] ?? 0) : 0;

        return Inertia::render('shared/vegetables/Show', [
            'vegetable' => Inertia::defer(function () use ($vegetableDetailService, $vegetable, $year, $month, $role, $hasForecastAccess, $gateFeature, $activityOffset) {
                $detail = $vegetableDetailService->show($vegetable, $year, $month, $role, $activityOffset);

                if (! $hasForecastAccess || $activityOffset !== 0) {
                    $detail->forecast = null;
                }

                $detail->forecast_locked = ! $hasForecastAccess;
                $detail->upgrade_feature = $gateFeature?->value;
                $detail->upgrade_feature_label = $gateFeature?->label();

                return VegetableDetailData::fromModel($detail);
            }),
            'calendarFilters' => ['year' => $year, 'month' => $month],
            'meta' => [
                'vegetableId' => $vegetable->id,
                'vegetableLabel' => $vegetable->display_name,
                'categoryName' => $vegetable->category->name,
            ],
            'isWatching' => $user->watches()->where('vegetable_id', $vegetable->id)->exists(),
        ]);
    }
}
