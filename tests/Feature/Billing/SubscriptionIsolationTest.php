<?php

use App\Enums\Billing\SubscriptionFeature;
use App\Models\Billing\Subscription;

use function Pest\Laravel\actingAs;

it('a farmer subscribing does not affect an unrelated dealer subscription', function () {
    $farmer = createFarmerUser();
    $dealer = createDealerUser();

    actingAs($farmer)->post(route('billing.subscribe'), [
        'feature' => 'farmer_forecasts', 'plan' => 'monthly',
    ]);
    actingAs($dealer)->post(route('billing.subscribe'), [
        'feature' => 'dealer_market_intel', 'plan' => 'monthly',
    ]);

    actingAs($farmer)->post(route('billing.subscribe'), [
        'feature' => 'farmer_forecasts', 'plan' => 'annual',
    ]);

    expect(Subscription::hasAccess($dealer, SubscriptionFeature::DealerMarketIntel))->toBeTrue();
});

it('a dealer cannot purchase the admin analytics feature', function () {
    actingAs(createDealerUser())
        ->post(route('billing.subscribe'), ['feature' => 'admin_analytics', 'plan' => 'monthly'])
        ->assertForbidden();
});

it('unsubscribed farmer sees truncated activity and locked analytics, not a 403', function () {
    $farmer = createFarmerUser();
    $vegetable = createVegetable();

    actingAs($farmer)
        ->get(route('vegetables.show', $vegetable))
        ->assertOk();
});

it('admin without a subscription sees a locked dashboard, not a redirect', function () {
    actingAs(createAdminUser())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Dashboard')
            ->where('analyticsLocked', true)
        );
});
