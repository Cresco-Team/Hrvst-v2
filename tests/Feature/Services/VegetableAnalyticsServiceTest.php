<?php

use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\VegetableViewerRole;
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

function undersupplyMonthlyActivity(): array
{
    $months = [];

    for ($i = 0; $i < 12; $i++) {
        $months[] = [
            'month' => sprintf('2099-%02d', $i + 1),
            'label' => sprintf('Month %d', $i + 1),
            'has_data' => true,
            'supply_fulfilled_kg' => 0.0,
            'supply_expired_kg' => 0.0,
            'demand_fulfilled_kg' => 0.0,
            'demand_expired_kg' => 0.0,
        ];
    }

    $months[8]['supply_fulfilled_kg'] = 10.0;
    $months[8]['supply_expired_kg'] = 90.0;
    $months[8]['demand_fulfilled_kg'] = 10.0;
    $months[8]['demand_expired_kg'] = 190.0;

    $months[9]['supply_fulfilled_kg'] = 50.0;
    $months[9]['supply_expired_kg'] = 150.0;
    $months[9]['demand_fulfilled_kg'] = 20.0;
    $months[9]['demand_expired_kg'] = 180.0;

    $months[10]['supply_fulfilled_kg'] = 5.0;
    $months[10]['supply_expired_kg'] = 15.0;
    $months[10]['demand_fulfilled_kg'] = 20.0;
    $months[10]['demand_expired_kg'] = 180.0;

    return $months;
}

it('confirms the fixture actually produces an undersupply band with a supply decline', function () {
    $dto = app(VegetableAnalyticsService::class)->compute(
        undersupplyMonthlyActivity(),
        VegetableViewerRole::Admin,
    )['analytics'];

    expect($dto->imbalance_band)->toBe(ImbalanceBand::Undersupply)
        ->and($dto->supply_volume_mom_pct)->toBeLessThan(-20.0);
});

it('filters recommendation types by viewer role', function (VegetableViewerRole $role, array $expectedTypes) {
    $dto = app(VegetableAnalyticsService::class)->compute(
        undersupplyMonthlyActivity(),
        $role,
    )['analytics'];

    $types = array_map(fn ($rec) => $rec->type, $dto->recommendations);

    expect($types)->toEqualCanonicalizing($expectedTypes);
})->with([
    'admin sees every recommendation type — nothing filtered' => [
        VegetableViewerRole::Admin,
        [
            'supply_opportunity',
            'high_supply_expiry_rate',
            'high_demand_expiry_rate',
            'declining_supply_volume',
        ],
    ],
    'farmer sees supply-side recs, not demand expiry — they cannot act on dealer demand' => [
        VegetableViewerRole::Farmer,
        [
            'supply_opportunity',
            'high_supply_expiry_rate',
            'declining_supply_volume',
        ],
    ],
    'dealer sees demand-side recs, not supply expiry — they cannot act on farmer supply' => [
        VegetableViewerRole::Dealer,
        [
            'supply_opportunity',
            'high_demand_expiry_rate',
            'declining_supply_volume',
        ],
    ],
]);

it('gives each role role-specific undersupply copy, not a shared generic sentence', function () {
    $admin = app(VegetableAnalyticsService::class)->compute(undersupplyMonthlyActivity(), VegetableViewerRole::Admin)['analytics'];
    $farmer = app(VegetableAnalyticsService::class)->compute(undersupplyMonthlyActivity(), VegetableViewerRole::Farmer)['analytics'];
    $dealer = app(VegetableAnalyticsService::class)->compute(undersupplyMonthlyActivity(), VegetableViewerRole::Dealer)['analytics'];

    $bodyFor = fn ($dto) => collect($dto->recommendations)
        ->firstWhere('type', 'supply_opportunity')
        ->body;

    expect($bodyFor($admin))->toContain('Consider prompting more farmers to post')
        ->and($bodyFor($farmer))->toContain('post your available harvest')
        ->and($bodyFor($dealer))->toContain('adjusting your requested quantity');

    expect($bodyFor($admin))->not->toBe($bodyFor($farmer))
        ->and($bodyFor($farmer))->not->toBe($bodyFor($dealer));
});
