<?php

use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\VarietyViewerRole;
use App\Services\Product\VarietyAnalyticsService;

/**
 * Builds a 12-month activity array where months at index 8, 9, 10 are engineered
 * to simultaneously trigger all four recommendation types:
 *
 * - Undersupply band (avg demand > avg supply by >20%)
 * - high_supply_expiry_rate (<50% supply fulfillment)
 * - high_demand_expiry_rate (<50% demand fulfillment)
 * - declining_supply_volume (month-over-month supply drop >20%, using indices 9→10)
 *
 * All other months are zeroed — they exist only so the array-index math in
 * computeImbalanceRatio() / computeVolumeMonthOverMonth() (which slices from
 * the end of the array) lands on the engineered months.
 */
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

    // index 8 — part of the 3-month ratio/fulfillment window, not part of MoM calc
    $months[8]['supply_fulfilled_kg'] = 10.0;
    $months[8]['supply_expired_kg'] = 90.0;
    $months[8]['demand_fulfilled_kg'] = 10.0;
    $months[8]['demand_expired_kg'] = 190.0;

    // index 9 — prevMonth for MoM; supply total 200
    $months[9]['supply_fulfilled_kg'] = 50.0;
    $months[9]['supply_expired_kg'] = 150.0;
    $months[9]['demand_fulfilled_kg'] = 20.0;
    $months[9]['demand_expired_kg'] = 180.0;

    // index 10 — lastMonth for MoM; supply total 20 → -90% drop vs index 9
    $months[10]['supply_fulfilled_kg'] = 5.0;
    $months[10]['supply_expired_kg'] = 15.0;
    $months[10]['demand_fulfilled_kg'] = 20.0;
    $months[10]['demand_expired_kg'] = 180.0;

    return $months;
}

it('confirms the fixture actually produces an undersupply band with a supply decline', function () {
    // Guards the test itself — if a future change to the ratio/decline math shifts
    // which array indices matter, this fails loudly instead of the role assertions
    // below silently testing against zero recommendations.
    $dto = (new VarietyAnalyticsService())->compute(
        undersupplyMonthlyActivity(),
        VarietyViewerRole::Admin,
    );

    expect($dto->imbalance_band)->toBe(ImbalanceBand::Undersupply)
        ->and($dto->supply_volume_mom_pct)->toBeLessThan(-20.0);
});

it('filters recommendation types by viewer role', function (VarietyViewerRole $role, array $expectedTypes) {
    $dto = (new VarietyAnalyticsService())->compute(
        undersupplyMonthlyActivity(),
        $role,
    );

    $types = array_map(fn ($rec) => $rec->type, $dto->recommendations);

    expect($types)->toEqualCanonicalizing($expectedTypes);
})->with([
    'admin sees every recommendation type — nothing filtered' => [
        VarietyViewerRole::Admin,
        [
            'supply_opportunity',
            'high_supply_expiry_rate',
            'high_demand_expiry_rate',
            'declining_supply_volume',
        ],
    ],
    'farmer sees supply-side recs, not demand expiry — they cannot act on dealer demand' => [
        VarietyViewerRole::Farmer,
        [
            'supply_opportunity',
            'high_supply_expiry_rate',
            'declining_supply_volume',
        ],
    ],
    'dealer sees demand-side recs, not supply expiry — they cannot act on farmer supply' => [
        VarietyViewerRole::Dealer,
        [
            'supply_opportunity',
            'high_demand_expiry_rate',
            'declining_supply_volume',
        ],
    ],
]);

it('gives each role role-specific undersupply copy, not a shared generic sentence', function () {
    $admin = (new VarietyAnalyticsService())->compute(undersupplyMonthlyActivity(), VarietyViewerRole::Admin);
    $farmer = (new VarietyAnalyticsService())->compute(undersupplyMonthlyActivity(), VarietyViewerRole::Farmer);
    $dealer = (new VarietyAnalyticsService())->compute(undersupplyMonthlyActivity(), VarietyViewerRole::Dealer);

    $bodyFor = fn ($dto) => collect($dto->recommendations)
        ->firstWhere('type', 'supply_opportunity')
        ->body;

    expect($bodyFor($admin))->toContain('Consider prompting more farmers to post')
        ->and($bodyFor($farmer))->toContain('post your available harvest')
        ->and($bodyFor($dealer))->toContain('adjusting your requested quantity');

    // The whole point of the enum split: these three must not collapse to one string.
    expect($bodyFor($admin))->not->toBe($bodyFor($farmer))
        ->and($bodyFor($farmer))->not->toBe($bodyFor($dealer));
});
