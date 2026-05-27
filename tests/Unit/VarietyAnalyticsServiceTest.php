<?php

use App\Services\Product\VarietyAnalyticsService;
use App\Models\Product\Variety;
use App\Enums\Analytics\VarietyViewerRole;
use Carbon\Carbon;

test('computes analytics dto and recommendations', function () {
    $service = new VarietyAnalyticsService();

    $variety = new Variety();

    $prices = collect([
        (object) ['price_max' => 100.0, 'recorded_at' => Carbon::now()->subWeeks(3)],
        (object) ['price_max' => 105.0, 'recorded_at' => Carbon::now()->subWeeks(1)],
    ]);

    $variety->recentPrices = $prices;
    $variety->latestPrice = (object) ['price_max' => 105.0, 'recorded_at' => Carbon::now()->subDays(10)];

    $monthlyActivity = [];
    for ($i = 0; $i < 12; $i++) {
        $monthlyActivity[] = [
            'month' => Carbon::now()->subMonths(11 - $i)->format('Y-m'),
            'label' => Carbon::now()->subMonths(11 - $i)->format('M Y'),
            'supply_unsettled_kg' => 0.0,
            'supply_fulfilled_kg' => 10.0,
            'demand_unsettled_kg' => 0.0,
            'demand_fulfilled_kg' => 10.0,
        ];
    }

    $dto = $service->compute($variety, $monthlyActivity, VarietyViewerRole::Admin);

    expect($dto)->toBeInstanceOf(\App\DTOs\Product\VarietyAnalyticsDTO::class);
    expect($dto->imbalance_band->value)->toBe('balanced');
    expect(is_float($dto->supply_demand_ratio))->toBeTrue();
    expect(is_array($dto->recommendations))->toBeTrue();
});
