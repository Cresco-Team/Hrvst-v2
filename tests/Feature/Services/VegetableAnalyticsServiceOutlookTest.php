<?php

use App\Enums\Analytics\ImbalanceBand;
use App\Services\Product\PlatformActivityService;
use App\Services\Product\VegetableAnalyticsService;

beforeEach(function () {
    $this->instance(
        PlatformActivityService::class,
        Mockery::mock(PlatformActivityService::class, function ($mock) {
            $mock->shouldReceive('monthlyActiveCounts')->andReturn(collect());
        }),
    );
});

function forecastMonth(float $supplyKg, float $demandKg): array
{
    return [
        'supply_fulfilled_kg' => $supplyKg,
        'supply_expired_kg' => 0.0,
        'demand_fulfilled_kg' => $demandKg,
        'demand_expired_kg' => 0.0,
    ];
}

it('does not flip bands when the ratio hovers near the threshold — hysteresis holds', function () {
    $service = app(VegetableAnalyticsService::class);

    // ratio = (supply - demand) / demand. 0.20 is the oversupply threshold.
    // 122/100 -> ratio 0.22, just over threshold — should classify Oversupply.
    $justOverForecast = [forecastMonth(122, 100), forecastMonth(122, 100), forecastMonth(122, 100)];

    $first = $service->forecastOutlook($justOverForecast, 'established', previousBand: null);
    expect($first['band'])->toBe(ImbalanceBand::Oversupply);

    // Ratio drifts back to 0.21 — inside the hysteresis margin of the Oversupply
    // band it's already in. Must NOT flip to Balanced.
    $driftedForecast = [forecastMonth(121, 100), forecastMonth(121, 100), forecastMonth(121, 100)];

    $second = $service->forecastOutlook($driftedForecast, 'established', previousBand: ImbalanceBand::Oversupply);
    expect($second['band'])->toBe(ImbalanceBand::Oversupply);
});

it('flips to balanced once the ratio genuinely clears the hysteresis margin', function () {
    $service = app(VegetableAnalyticsService::class);

    // Previous band Oversupply requires ratio to drop below 0.20 - 0.05 = 0.15
    // to flip back. 105/100 -> ratio 0.05, well clear of that floor.
    $balancedForecast = [forecastMonth(105, 100), forecastMonth(105, 100), forecastMonth(105, 100)];

    $result = $service->forecastOutlook($balancedForecast, 'established', previousBand: ImbalanceBand::Oversupply);

    expect($result['band'])->toBe(ImbalanceBand::Balanced);
});

it('reacts immediately when a currently-Balanced vegetable crosses into a new band', function () {
    $service = app(VegetableAnalyticsService::class);

    // No previous band (or Balanced) — margin is added AGAINST the threshold,
    // not subtracted, so this must NOT lag behind a genuinely new signal.
    $newlyOversupplied = [forecastMonth(150, 100), forecastMonth(150, 100), forecastMonth(150, 100)];

    $result = $service->forecastOutlook($newlyOversupplied, 'established', previousBand: ImbalanceBand::Balanced);

    expect($result['band'])->toBe(ImbalanceBand::Oversupply);
});

it('returns null for an insufficient-confidence forecast — never alert on unreliable data', function () {
    $service = app(VegetableAnalyticsService::class);

    $result = $service->forecastOutlook([forecastMonth(200, 100)], 'insufficient', previousBand: null);

    expect($result)->toBeNull();
});

it('returns null for an empty forecast array', function () {
    $service = app(VegetableAnalyticsService::class);

    $result = $service->forecastOutlook([], 'established', previousBand: null);

    expect($result)->toBeNull();
});

it('reports how many consecutive forecast months share the same band', function () {
    $service = app(VegetableAnalyticsService::class);

    $forecast = [
        forecastMonth(150, 100), // oversupply
        forecastMonth(150, 100), // oversupply
        forecastMonth(102, 100), // balanced — run breaks here
    ];

    $result = $service->forecastOutlook($forecast, 'established', previousBand: null);

    expect($result['band'])->toBe(ImbalanceBand::Oversupply)
        ->and($result['starts_in_months'])->toBe(1)
        ->and($result['duration_months'])->toBe(2)
        ->and($result['label'])->toContain('next month')
        ->and($result['label'])->toContain('2 months');
});

it('produces a singular "next month" label without a duration clause when the run is only 1 month', function () {
    $service = app(VegetableAnalyticsService::class);

    $forecast = [
        forecastMonth(150, 100), // oversupply
        forecastMonth(102, 100), // balanced — run breaks after 1 month
    ];

    $result = $service->forecastOutlook($forecast, 'established', previousBand: null);

    expect($result['duration_months'])->toBe(1)
        ->and($result['label'])->toBe('Expected to be oversupplied next month.');
});

it('classifies undersupply with shortage wording, distinct from oversupply', function () {
    $service = app(VegetableAnalyticsService::class);

    // 60/100 -> ratio -0.40, well past the -0.20 undersupply threshold.
    $forecast = [forecastMonth(60, 100), forecastMonth(60, 100)];

    $result = $service->forecastOutlook($forecast, 'established', previousBand: null);

    expect($result['band'])->toBe(ImbalanceBand::Undersupply)
        ->and($result['label'])->toContain('shortage');
});
