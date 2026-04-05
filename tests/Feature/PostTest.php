<?php

use App\Enums\PostStatus;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Address\Province;
use App\Models\Marketplace\Post;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

// ─── Note ─────────────────────────────────────────────────────────────────────
// Global helper functions in PinAuthTest (makeAddress, makeFarmer, makeDealer,
// makeAdmin) are declared at the global PHP scope and will conflict if reused
// here. Shared helpers should live in tests/Pest.php. Using distinct names until
// that refactor is done.
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Province → Municipality → Barangay chain required by FarmerProfile.
 * RefreshDatabase wipes these; there are no factories for them.
 */
function seedBarangay(): Barangay
{
    $province = Province::create(['name' => 'Benguet']);
    $municipality = Municipality::create([
        'province_id' => $province->id,
        'name' => 'La Trinidad',
        'latitude' => 16.4617,
        'longitude' => 120.5885,
    ]);

    return Barangay::create([
        'municipality_id' => $municipality->id,
        'name' => 'Poblacion',
    ]);
}

function verifiedFarmer(array $overrides = []): User
{
    $barangay = seedBarangay();

    $user = User::factory()->create(array_merge([
        'must_change_pin' => false,
        'email_verified_at' => now(),
    ], $overrides));

    $user->roles()->attach(Role::where('name', 'farmer')->first());

    FarmerProfile::create([
        'user_id' => $user->id,
        'province_id' => $barangay->municipality->province_id,
        'municipality_id' => $barangay->municipality_id,
        'barangay_id' => $barangay->id,
        'latitude' => 16.4137,
        'longitude' => 120.5960,
    ]);

    return $user;
}

function verifiedDealer(array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'must_change_pin' => false,
        'email_verified_at' => now(),
    ], $overrides));

    $user->roles()->attach(Role::where('name', 'dealer')->first());
    DealerProfile::create(['user_id' => $user->id]);

    return $user;
}

function verifiedAdmin(array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'must_change_pin' => false,
        'email_verified_at' => now(),
    ], $overrides));

    $user->roles()->attach(Role::where('name', 'admin')->first());

    return $user;
}

function seedVariety(): Variety
{
    $category = Category::create(['name' => 'Test Category']);
    $vegetable = Vegetable::create(['category_id' => $category->id, 'name' => 'Test Vegetable']);

    return Variety::create(['vegetable_id' => $vegetable->id, 'name' => 'Test Variety']);
}

function createSupply(User $farmer, Variety $variety, array $overrides = []): Post
{
    return Post::create(array_merge([
        'user_id' => $farmer->id,
        'variety_id' => $variety->id,
        'type' => PostType::Supply,
        'status' => PostStatus::Ongoing,
        'quantity_kg' => 100,
        'offered_price' => 50.00,
        'price_flag' => 'Fair',
        'scheduled_date' => now()->addDays(7)->toDateString(),
        'time_slot' => PostTimeSlot::Morning,
    ], $overrides));
}

function createDemand(User $dealer, Variety $variety, array $overrides = []): Post
{
    return Post::create(array_merge([
        'user_id' => $dealer->id,
        'variety_id' => $variety->id,
        'type' => PostType::Demand,
        'status' => PostStatus::Ongoing,
        'quantity_kg' => 50,
        'offered_price' => 45.00,
        'price_flag' => 'Fair',
        'scheduled_date' => now()->addDays(5)->toDateString(),
        'time_slot' => PostTimeSlot::Afternoon,
    ], $overrides));
}

function supplyPayload(Variety $variety): array
{
    return [
        'variety_id' => $variety->id,
        'quantity_kg' => 100,
        'offered_price' => 50.00,
        'scheduled_date' => now()->addDays(7)->toDateString(),
        'time_slot' => PostTimeSlot::Morning->value,
        'image' => UploadedFile::fake()->image('supply.jpg'),
    ];
}

function demandPayload(Variety $variety): array
{
    return [
        'variety_id' => $variety->id,
        'quantity_kg' => 50,
        'offered_price' => 45.00,
        'scheduled_date' => now()->addDays(5)->toDateString(),
        'time_slot' => PostTimeSlot::Afternoon->value,
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// SUPPLY — Store
// ═══════════════════════════════════════════════════════════════════════════════

describe('supply store', function () {
    it('farmer can post a supply', function () {
        $farmer = verifiedFarmer();
        $variety = seedVariety();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), supplyPayload($variety))
            ->assertRedirect(route('farmer.supplies.index'));

        assertDatabaseHas('posts', [
            'user_id' => $farmer->id,
            'variety_id' => $variety->id,
            'type' => PostType::Supply->value,
            'status' => PostStatus::Ongoing->value,
            'quantity_kg' => 100,
        ]);
    });

    it('guest is redirected to login', function () {
        post(route('farmer.supplies.store'), supplyPayload(seedVariety()))
            ->assertRedirect(route('login'));
    });

    it('dealer cannot post a supply — role gate', function () {
        actingAs(verifiedDealer())
            ->post(route('farmer.supplies.store'), supplyPayload(seedVariety()))
            ->assertForbidden();
    });

    it('farmer with no profile cannot post a supply — policy gate', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_pin' => false,
        ]);
        $user->roles()->attach(Role::where('name', 'farmer')->first());
        // No FarmerProfile created — PostPolicy::create denies this.

        actingAs($user)
            ->post(route('farmer.supplies.store'), supplyPayload(seedVariety()))
            ->assertForbidden();
    });

    it('rejects missing required fields', function () {
        actingAs(verifiedFarmer())
            ->post(route('farmer.supplies.store'), [])
            ->assertSessionHasErrors([
                'variety_id', 'quantity_kg', 'offered_price',
                'scheduled_date', 'time_slot', 'image',
            ]);
    });

    it('rejects a past scheduled_date', function () {
        $payload = supplyPayload(seedVariety());
        $payload['scheduled_date'] = now()->subDay()->toDateString();

        actingAs(verifiedFarmer())
            ->post(route('farmer.supplies.store'), $payload)
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects a scheduled_date more than 3 months away', function () {
        $payload = supplyPayload(seedVariety());
        $payload['scheduled_date'] = now()->addMonths(4)->toDateString();

        actingAs(verifiedFarmer())
            ->post(route('farmer.supplies.store'), $payload)
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects an invalid time_slot value', function () {
        $payload = supplyPayload(seedVariety());
        $payload['time_slot'] = 'midnight';

        actingAs(verifiedFarmer())
            ->post(route('farmer.supplies.store'), $payload)
            ->assertSessionHasErrors('time_slot');
    });

    it('rejects a non-image file', function () {
        $payload = supplyPayload(seedVariety());
        $payload['image'] = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        actingAs(verifiedFarmer())
            ->post(route('farmer.supplies.store'), $payload)
            ->assertSessionHasErrors('image');
    });

    it('rejects quantity_kg below minimum', function () {
        $payload = supplyPayload(seedVariety());
        $payload['quantity_kg'] = 0;

        actingAs(verifiedFarmer())
            ->post(route('farmer.supplies.store'), $payload)
            ->assertSessionHasErrors('quantity_kg');
    });

    it('assigns a price_flag on creation', function () {
        $farmer = verifiedFarmer();
        $variety = seedVariety();

        actingAs($farmer)->post(route('farmer.supplies.store'), supplyPayload($variety));

        $post = Post::where('user_id', $farmer->id)->sole();
        expect($post->price_flag)->not->toBeNull();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// SUPPLY — Update
// ═══════════════════════════════════════════════════════════════════════════════

describe('supply update', function () {
    it('farmer can update own ongoing supply', function () {
        $farmer = verifiedFarmer();
        $variety = seedVariety();
        $supply = createSupply($farmer, $variety);

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $supply), [
                'quantity_kg' => 200,
                'offered_price' => 60.00,
                'scheduled_date' => now()->addDays(10)->toDateString(),
                'time_slot' => PostTimeSlot::Evening->value,
            ])
            ->assertRedirect(route('farmer.supplies.index'));

        expect($supply->fresh()->quantity_kg)->toBe(200);
    });

    it('farmer cannot update another farmer\'s supply', function () {
        $owner = verifiedFarmer();
        $intruder = verifiedFarmer();
        $supply = createSupply($owner, seedVariety());

        actingAs($intruder)
            ->put(route('farmer.supplies.update', $supply), ['quantity_kg' => 999])
            ->assertForbidden();
    });

    it('farmer cannot update an archived supply', function () {
        $farmer = verifiedFarmer();
        $supply = createSupply($farmer, seedVariety(), ['status' => PostStatus::Archived]);

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $supply), ['quantity_kg' => 999])
            ->assertForbidden();
    });

    it('farmer cannot update a fulfilled supply', function () {
        $farmer = verifiedFarmer();
        $supply = createSupply($farmer, seedVariety(), ['status' => PostStatus::Fulfilled]);

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $supply), ['quantity_kg' => 999])
            ->assertForbidden();
    });

    it('guest cannot update a supply', function () {
        $farmer = verifiedFarmer();
        $supply = createSupply($farmer, seedVariety());

        put(route('farmer.supplies.update', $supply), ['quantity_kg' => 50])
            ->assertRedirect(route('login'));
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// SUPPLY — Archive
// ═══════════════════════════════════════════════════════════════════════════════

describe('supply archive', function () {
    it('farmer can archive own ongoing supply', function () {
        $farmer = verifiedFarmer();
        $supply = createSupply($farmer, seedVariety());

        actingAs($farmer)
            ->post(route('farmer.supplies.archive', $supply))
            ->assertRedirect(route('farmer.supplies.index'));

        expect($supply->fresh()->status)->toBe(PostStatus::Archived);
    });

    it('farmer cannot archive another farmer\'s supply', function () {
        $supply = createSupply(verifiedFarmer(), seedVariety());

        actingAs(verifiedFarmer())
            ->post(route('farmer.supplies.archive', $supply))
            ->assertForbidden();
    });

    it('farmer cannot archive an already archived supply — policy blocks non-ongoing', function () {
        $farmer = verifiedFarmer();
        $supply = createSupply($farmer, seedVariety(), ['status' => PostStatus::Archived]);

        // PostPolicy::archive on Supply delegates to update() which requires Ongoing.
        actingAs($farmer)
            ->post(route('farmer.supplies.archive', $supply))
            ->assertForbidden();
    });

    it('guest cannot archive a supply', function () {
        post(route('farmer.supplies.archive', createSupply(verifiedFarmer(), seedVariety())))
            ->assertRedirect(route('login'));
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// SUPPLY — Fulfill
// ═══════════════════════════════════════════════════════════════════════════════

describe('supply fulfill', function () {
    it('farmer can mark own supply as fulfilled', function () {
        $farmer = verifiedFarmer();
        $supply = createSupply($farmer, seedVariety());

        actingAs($farmer)
            ->post(route('farmer.supplies.fulfill', $supply))
            ->assertRedirect(route('farmer.supplies.index'));

        expect($supply->fresh()->status)->toBe(PostStatus::Fulfilled);
    });

    it('farmer cannot fulfill another farmer\'s supply', function () {
        $supply = createSupply(verifiedFarmer(), seedVariety());

        actingAs(verifiedFarmer())
            ->post(route('farmer.supplies.fulfill', $supply))
            ->assertForbidden();
    });

    it('farmer cannot fulfill an already fulfilled supply', function () {
        $farmer = verifiedFarmer();
        $supply = createSupply($farmer, seedVariety(), ['status' => PostStatus::Fulfilled]);

        actingAs($farmer)
            ->post(route('farmer.supplies.fulfill', $supply))
            ->assertForbidden();
    });

    it('guest cannot fulfill a supply', function () {
        post(route('farmer.supplies.fulfill', createSupply(verifiedFarmer(), seedVariety())))
            ->assertRedirect(route('login'));
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// SUPPLY — Destroy
// ═══════════════════════════════════════════════════════════════════════════════

describe('supply destroy', function () {
    it('farmer can delete own supply', function () {
        $farmer = verifiedFarmer();
        $supply = createSupply($farmer, seedVariety());

        actingAs($farmer)
            ->delete(route('farmer.supplies.destroy', $supply))
            ->assertRedirect(route('farmer.supplies.index'));

        // Soft delete — model is gone from default scope.
        expect($supply->fresh())->toBeNull();
    });

    it('admin is blocked by EnsureUserIsFarmer middleware on farmer routes — policy never reached', function () {
        // PostPolicy::delete allows admin for Supply, but the route sits behind
        // EnsureUserIsFarmer which checks the role, not the policy.
        // Admin would need a dedicated admin route to exercise that policy branch.
        $supply = createSupply(verifiedFarmer(), seedVariety());

        actingAs(verifiedAdmin())
            ->delete(route('farmer.supplies.destroy', $supply))
            ->assertForbidden();
    });

    it('farmer cannot delete another farmer\'s supply', function () {
        $supply = createSupply(verifiedFarmer(), seedVariety());

        actingAs(verifiedFarmer())
            ->delete(route('farmer.supplies.destroy', $supply))
            ->assertForbidden();
    });

    it('dealer cannot delete a supply — role gate blocks farmer routes', function () {
        $farmer = verifiedFarmer();
        $supply = createSupply($farmer, seedVariety());

        actingAs(verifiedDealer())
            ->delete(route('farmer.supplies.destroy', $supply))
            ->assertForbidden();
    });

    it('guest cannot delete a supply', function () {
        delete(route('farmer.supplies.destroy', createSupply(verifiedFarmer(), seedVariety())))
            ->assertRedirect(route('login'));
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DEMAND — Store
// ═══════════════════════════════════════════════════════════════════════════════

describe('demand store', function () {
    it('dealer can post a demand', function () {
        $dealer = verifiedDealer();
        $variety = seedVariety();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), demandPayload($variety))
            ->assertRedirect(route('dealer.demands.index'));

        assertDatabaseHas('posts', [
            'user_id' => $dealer->id,
            'variety_id' => $variety->id,
            'type' => PostType::Demand->value,
            'status' => PostStatus::Ongoing->value,
            'quantity_kg' => 50,
        ]);
    });

    it('guest is redirected to login', function () {
        post(route('dealer.demands.store'), demandPayload(seedVariety()))
            ->assertRedirect(route('login'));
    });

    it('farmer cannot post a demand — role gate', function () {
        actingAs(verifiedFarmer())
            ->post(route('dealer.demands.store'), demandPayload(seedVariety()))
            ->assertForbidden();
    });

    it('dealer with no profile cannot post a demand — policy gate', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_pin' => false,
        ]);
        $user->roles()->attach(Role::where('name', 'dealer')->first());
        // No DealerProfile — PostPolicy::create denies this.

        actingAs($user)
            ->post(route('dealer.demands.store'), demandPayload(seedVariety()))
            ->assertForbidden();
    });

    it('rejects missing required fields', function () {
        actingAs(verifiedDealer())
            ->post(route('dealer.demands.store'), [])
            ->assertSessionHasErrors(['variety_id', 'quantity_kg', 'scheduled_date', 'time_slot']);
    });

    it('allows demand creation without offered_price', function () {
        $dealer = verifiedDealer();
        $payload = demandPayload(seedVariety());
        unset($payload['offered_price']);

        actingAs($dealer)
            ->post(route('dealer.demands.store'), $payload)
            ->assertRedirect(route('dealer.demands.index'));

        assertDatabaseHas('posts', [
            'user_id' => $dealer->id,
            'offered_price' => null,
        ]);
    });

    it('rejects a past scheduled_date', function () {
        $payload = demandPayload(seedVariety());
        $payload['scheduled_date'] = now()->subDay()->toDateString();

        actingAs(verifiedDealer())
            ->post(route('dealer.demands.store'), $payload)
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects offered_price below zero', function () {
        $payload = demandPayload(seedVariety());
        $payload['offered_price'] = -1;

        actingAs(verifiedDealer())
            ->post(route('dealer.demands.store'), $payload)
            ->assertSessionHasErrors('offered_price');
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DEMAND — Update
// ═══════════════════════════════════════════════════════════════════════════════

describe('demand update', function () {
    it('dealer can update own ongoing demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety());

        actingAs($dealer)
            ->put(route('dealer.demands.update', $demand), ['quantity_kg' => 200])
            ->assertRedirect(route('dealer.demands.index'));

        expect($demand->fresh()->quantity_kg)->toBe(200);
    });

    it('dealer cannot update another dealer\'s demand', function () {
        $demand = createDemand(verifiedDealer(), seedVariety());

        actingAs(verifiedDealer())
            ->put(route('dealer.demands.update', $demand), ['quantity_kg' => 999])
            ->assertForbidden();
    });

    it('dealer cannot update an archived demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety(), ['status' => PostStatus::Archived]);

        actingAs($dealer)
            ->put(route('dealer.demands.update', $demand), ['quantity_kg' => 999])
            ->assertForbidden();
    });

    it('dealer cannot update a fulfilled demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety(), ['status' => PostStatus::Fulfilled]);

        actingAs($dealer)
            ->put(route('dealer.demands.update', $demand), ['quantity_kg' => 999])
            ->assertForbidden();
    });

    it('guest cannot update a demand', function () {
        $demand = createDemand(verifiedDealer(), seedVariety());

        put(route('dealer.demands.update', $demand), ['quantity_kg' => 50])
            ->assertRedirect(route('login'));
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DEMAND — Archive
// ═══════════════════════════════════════════════════════════════════════════════

describe('demand archive', function () {
    it('dealer can archive own ongoing demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety());

        actingAs($dealer)
            ->post(route('dealer.demands.archive', $demand))
            ->assertRedirect(route('dealer.demands.index'));

        expect($demand->fresh()->status)->toBe(PostStatus::Archived);
    });

    it('dealer can archive own fulfilled demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety(), ['status' => PostStatus::Fulfilled]);

        actingAs($dealer)
            ->post(route('dealer.demands.archive', $demand))
            ->assertRedirect(route('dealer.demands.index'));

        expect($demand->fresh()->status)->toBe(PostStatus::Archived);
    });

    it('dealer cannot archive an already archived demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety(), ['status' => PostStatus::Archived]);

        // PostPolicy::archive for Demand: status must not be Archived already.
        actingAs($dealer)
            ->post(route('dealer.demands.archive', $demand))
            ->assertForbidden();
    });

    it('dealer cannot archive another dealer\'s demand', function () {
        $demand = createDemand(verifiedDealer(), seedVariety());

        actingAs(verifiedDealer())
            ->post(route('dealer.demands.archive', $demand))
            ->assertForbidden();
    });

    it('guest cannot archive a demand', function () {
        post(route('dealer.demands.archive', createDemand(verifiedDealer(), seedVariety())))
            ->assertRedirect(route('login'));
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DEMAND — Fulfill
// ═══════════════════════════════════════════════════════════════════════════════

describe('demand fulfill', function () {
    it('dealer can fulfill own ongoing demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety());

        actingAs($dealer)
            ->post(route('dealer.demands.fulfill', $demand))
            ->assertRedirect(route('dealer.demands.index'));

        expect($demand->fresh()->status)->toBe(PostStatus::Fulfilled);
    });

    it('dealer can fulfill own archived demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety(), ['status' => PostStatus::Archived]);

        actingAs($dealer)
            ->post(route('dealer.demands.fulfill', $demand))
            ->assertRedirect(route('dealer.demands.index'));

        expect($demand->fresh()->status)->toBe(PostStatus::Fulfilled);
    });

    it('dealer cannot fulfill an already fulfilled demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety(), ['status' => PostStatus::Fulfilled]);

        actingAs($dealer)
            ->post(route('dealer.demands.fulfill', $demand))
            ->assertForbidden();
    });

    it('dealer cannot fulfill another dealer\'s demand', function () {
        $demand = createDemand(verifiedDealer(), seedVariety());

        actingAs(verifiedDealer())
            ->post(route('dealer.demands.fulfill', $demand))
            ->assertForbidden();
    });

    it('guest cannot fulfill a demand', function () {
        post(route('dealer.demands.fulfill', createDemand(verifiedDealer(), seedVariety())))
            ->assertRedirect(route('login'));
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DEMAND — Destroy
// ═══════════════════════════════════════════════════════════════════════════════

describe('demand destroy', function () {
    it('dealer can delete own archived demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety(), ['status' => PostStatus::Archived]);

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $demand))
            ->assertRedirect(route('dealer.demands.index'));

        expect($demand->fresh())->toBeNull();
    });

    it('dealer can delete own fulfilled demand', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety(), ['status' => PostStatus::Fulfilled]);

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $demand))
            ->assertRedirect(route('dealer.demands.index'));

        expect($demand->fresh())->toBeNull();
    });

    it('dealer cannot delete own ongoing demand — policy guards ongoing posts', function () {
        $dealer = verifiedDealer();
        $demand = createDemand($dealer, seedVariety());

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $demand))
            ->assertForbidden();
    });

    it('dealer cannot delete another dealer\'s demand', function () {
        $demand = createDemand(verifiedDealer(), seedVariety(), ['status' => PostStatus::Archived]);

        actingAs(verifiedDealer())
            ->delete(route('dealer.demands.destroy', $demand))
            ->assertForbidden();
    });

    it('farmer cannot delete a demand — role gate blocks dealer routes', function () {
        $demand = createDemand(verifiedDealer(), seedVariety(), ['status' => PostStatus::Archived]);

        actingAs(verifiedFarmer())
            ->delete(route('dealer.demands.destroy', $demand))
            ->assertForbidden();
    });

    it('guest cannot delete a demand', function () {
        $demand = createDemand(verifiedDealer(), seedVariety(), ['status' => PostStatus::Archived]);

        delete(route('dealer.demands.destroy', $demand))
            ->assertRedirect(route('login'));
    });
});
