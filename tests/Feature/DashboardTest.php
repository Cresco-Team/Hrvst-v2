<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('admin is redirected to admin dashboard', function () {
    actingAs(createAdminUser())
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

describe('farmer redirect', function () {
    it('goes to supplies index when onboarding is incomplete', function () {
        $farmer = createFarmerUser();

        actingAs($farmer)
            ->get(route('dashboard'))
            ->assertRedirect(route('farmer.supplies.index'));
    });

    it('goes to farmer dashboard once onboarding is complete', function () {
        $farmer = createFarmerUser();
        $farmer->forceFill(['onboarding_completed_at' => now()])->save();

        actingAs($farmer)
            ->get(route('dashboard'))
            ->assertRedirect(route('farmer.dashboard'));
    });
});

describe('dealer redirect', function () {
    it('goes to demands index when onboarding is incomplete', function () {
        $dealer = createDealerUser();

        actingAs($dealer)
            ->get(route('dashboard'))
            ->assertRedirect(route('dealer.demands.index'));
    });

    it('goes to dealer dashboard once onboarding is complete', function () {
        $dealer = createDealerUser();
        $dealer->forceFill(['onboarding_completed_at' => now()])->save();

        actingAs($dealer)
            ->get(route('dashboard'))
            ->assertRedirect(route('dealer.dashboard'));
    });
});
