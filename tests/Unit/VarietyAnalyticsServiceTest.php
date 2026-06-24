<?php

use App\DTOs\Product\VarietyAnalyticsDTO;
use App\Enums\Analytics\VarietyViewerRole;
use App\Models\Product\Variety;
use App\Services\Product\VarietyAnalyticsService;
use Carbon\Carbon;

test('computes analytics dto and recommendations', function () {
    $service = new VarietyAnalyticsService;

    $variety = new Variety;

    $monthlyActivity = [];
    for ($i = 0; $i < 12; $i++) {
        $monthlyActivity[] = [
            'month' => Carbon::now()->subMonths(11 - $i)->format('Y-m'),
            'label' => Carbon::now()->subMonths(11 - $i)->format('M Y'),
            'supply_expired_kg' => 0.0,
            'supply_fulfilled_kg' => 10.0,
            'demand_expired_kg' => 0.0,
            'demand_fulfilled_kg' => 10.0,
        ];
    }

    $dto = $service->compute($monthlyActivity, VarietyViewerRole::Admin);

    expect($dto)->toBeInstanceOf(VarietyAnalyticsDTO::class);
    expect($dto->imbalance_band->value)->toBe('balanced');
    expect(is_float($dto->supply_demand_ratio))->toBeTrue();
    expect(is_array($dto->recommendations))->toBeTrue();
});
