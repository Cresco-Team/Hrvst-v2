<?php

use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    Storage::fake('public');
});

describe('needsOnboarding computed flag', function () {
    it('is true for a fresh farmer', function () {
        actingAs(createFarmerUser())
            ->get(route('farmer.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('needsOnboarding', true));
    });

    it('is true for a fresh dealer', function () {
        actingAs(createDealerUser())
            ->get(route('dealer.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('needsOnboarding', true));
    });

    it('is false for admin regardless of onboarding_completed_at', function () {
        actingAs(createAdminUser())
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('needsOnboarding', false));
    });

    it('is false after completion', function () {
        $farmer = createFarmerUser();
        actingAs($farmer)->post(route('onboarding.complete'));

        actingAs($farmer->fresh())
            ->get(route('farmer.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('needsOnboarding', false));
    });
});

describe('onboarding completion endpoint', function () {
    it('sets onboarding_completed_at for the authenticated user', function () {
        $farmer = createFarmerUser();

        actingAs($farmer)
            ->post(route('onboarding.complete'))
            ->assertRedirect();

        expect($farmer->fresh()->onboarding_completed_at)->not->toBeNull();
    });

    it('is idempotent — calling twice does not error', function () {
        $farmer = createFarmerUser();

        actingAs($farmer)->post(route('onboarding.complete'));
        actingAs($farmer)->post(route('onboarding.complete'))
            ->assertRedirect();

        expect($farmer->fresh()->onboarding_completed_at)->not->toBeNull();
    });

    it('rejects guests', function () {
        post(route('onboarding.complete'))->assertRedirect(route('login'));
    });
});
