<?php

use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Models\User;
use App\Models\Vegetable\Category;
use App\Models\Vegetable\Vegetable;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->category = Category::create(['name' => 'Leafy Greens']);
    $this->vegetable = Vegetable::create([
        'category_id' => $this->category->id,
        'vegetable_name' => 'Pechay',
    ]);
});

function subscribeFarmer(User $farmer): void
{
    Subscription::create([
        'user_id' => $farmer->id,
        'feature' => SubscriptionFeature::FarmerForecasts,
        'plan' => SubscriptionPlan::Monthly,
        'status' => SubscriptionStatus::Active,
        'amount_cents' => 9_900,
        'currency' => 'PHP',
        'payment_gateway' => 'mock',
        'payment_reference' => 'mock_'.uniqid(),
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
}

it('exports vegetable activity as csv for a subscribed user', function () {
    $farmer = createFarmerUser();
    subscribeFarmer($farmer);

    actingAs($farmer)
        ->get(route('vegetables.export', $this->vegetable))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('blocks an unsubscribed user with 403', function () {
    actingAs(createFarmerUser())
        ->get(route('vegetables.export', $this->vegetable))
        ->assertForbidden();
});

it('redirects guests to login', function () {
    $this->get(route('vegetables.export', $this->vegetable))
        ->assertRedirect(route('login'));
});

it('returns 404 for a nonexistent vegetable', function () {
    $farmer = createFarmerUser();
    subscribeFarmer($farmer);

    actingAs($farmer)
        ->get(route('vegetables.export', 999999))
        ->assertNotFound();
});
