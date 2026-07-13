<?php

use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Address\Province;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

// ─── Seed roles before every test ────────────────────────────────────────────
// Actions (CreateFarmerAction, CreateDealerAction) call Role::firstOrFail() —
// they must find all three roles. We can't control action internals from here,
// so we ensure the roles always exist.

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Creates Province → Municipality → Barangay.
 * Must be called before any FarmerProfile is created — RefreshDatabase wipes
 * address tables and there are no factories for them (data comes from CSV seeders
 * in production).
 */
function makeAddress(): Barangay
{
    $province = Province::create(['name' => 'Benguet']);

    $municipality = Municipality::create([
        'province_id' => $province->id,
        'name' => 'Baguio City',
        'latitude' => 16.4137,
        'longitude' => 120.5896,
    ]);

    return Barangay::create([
        'municipality_id' => $municipality->id,
        'name' => 'Test Barangay',
    ]);
}

function makeAdmin(array $overrides = []): User
{
    $user = User::factory()->create(array_merge(['must_change_pin' => false], $overrides));
    $user->roles()->attach(Role::where('name', 'admin')->first());

    return $user;
}

function makeFarmer(array $overrides = []): User
{
    $barangay = makeAddress();

    $user = User::factory()->create(array_merge(['must_change_pin' => false], $overrides));
    $user->roles()->attach(Role::where('name', 'farmer')->first());

    FarmerProfile::create([
        'user_id' => $user->id,
        'province_id' => $barangay->municipality->province_id,
        'municipality_id' => $barangay->municipality_id,
        'barangay_id' => $barangay->id,
        'latitude' => 16.4137,
        'longitude' => 120.5896,
    ]);

    return $user;
}

function makeDealer(array $overrides = []): User
{
    $user = User::factory()->create(array_merge(['must_change_pin' => false], $overrides));
    $user->roles()->attach(Role::where('name', 'dealer')->first());
    DealerProfile::create(['user_id' => $user->id]);

    return $user;
}

// ─── Login ────────────────────────────────────────────────────────────────────

describe('login', function () {
    it('renders the login page', function () {
        get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/Login'));
    });

    it('authenticates a user with phone_number and correct PIN', function () {
        $user = makeFarmer();
        $user->update(['password' => 'correctpin']);

        post('/login', [
            'phone_number' => $user->phone_number,
            'password' => 'correctpin',
        ])->assertRedirect();

        expect(Auth::user()->id)->toBe($user->id);
    });

    it('rejects login with wrong PIN', function () {
        $user = makeFarmer();
        $user->update(['password' => '1234']);

        post('/login', [
            'phone_number' => $user->phone_number,
            'password' => 'wrongpin',
        ])->assertSessionHasErrors('phone_number');

        expect(Auth::check())->toBeFalse();
    });

    it('rejects login with unknown phone number', function () {
        post('/login', [
            'phone_number' => '09199999999',
            'password' => '1234',
        ])->assertSessionHasErrors('phone_number');

        expect(Auth::check())->toBeFalse();
    });

    it('rate limits login after 5 failed attempts', function () {
        $user = makeFarmer();

        for ($i = 0; $i < 5; $i++) {
            post('/login', [
                'phone_number' => $user->phone_number,
                'password' => 'badpin',
            ]);
        }

        post('/login', [
            'phone_number' => $user->phone_number,
            'password' => 'badpin',
        ])->assertStatus(429);
    });
});

// ─── EnsurePinChanged middleware ──────────────────────────────────────────────

describe('must_change_pin middleware', function () {
    it('redirects to change-pin when must_change_pin is true', function () {
        actingAs(makeFarmer(['must_change_pin' => true]))
            ->get('/dashboard')
            ->assertRedirect(route('change-pin.show'));
    });

    it('allows access to change-pin route when must_change_pin is true', function () {
        actingAs(makeFarmer(['must_change_pin' => true]))
            ->get(route('change-pin.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/ChangePin'));
    });

    it('allows logout regardless of must_change_pin flag', function () {
        actingAs(makeFarmer(['must_change_pin' => true]))
            ->post('/logout')
            ->assertRedirect('/');
    });

    it('does not redirect to change-pin when flag is false', function () {
        actingAs(makeFarmer(['must_change_pin' => false]))
            ->get('/dashboard')
            ->assertRedirectContains('/farmer/supplies');
    });
});

// ─── Change PIN ───────────────────────────────────────────────────────────────

describe('change PIN', function () {
    it('renders the change pin page', function () {
        actingAs(makeFarmer(['must_change_pin' => true]))
            ->get(route('change-pin.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/ChangePin'));
    });

    it('changes the PIN and clears the must_change_pin flag', function () {
        $user = makeFarmer(['must_change_pin' => true]);
        $user->update(['password' => '0000']);

        actingAs($user)
            ->post(route('change-pin.update'), [
                'pin' => '123456',
                'pin_confirmation' => '123456',
            ])
            ->assertRedirect();

        $user->refresh();

        expect($user->must_change_pin)->toBeFalse()
            ->and(Hash::check('123456', $user->password))->toBeTrue();
    });

    it('rejects a PIN shorter than 4 digits', function () {
        $user = makeFarmer(['must_change_pin' => true]);

        actingAs($user)
            ->post(route('change-pin.update'), [
                'pin' => '12',
                'pin_confirmation' => '12',
            ])
            ->assertSessionHasErrors('pin');

        expect($user->fresh()->must_change_pin)->toBeTrue();
    });

    it('rejects mismatching PIN confirmation', function () {
        actingAs(makeFarmer(['must_change_pin' => true]))
            ->post(route('change-pin.update'), [
                'pin' => '1234',
                'pin_confirmation' => '123456',
            ])
            ->assertSessionHasErrors('pin');
    });

    it('rejects a non-numeric PIN', function () {
        actingAs(makeFarmer(['must_change_pin' => true]))
            ->post(route('change-pin.update'), [
                'pin' => 'abcd',
                'pin_confirmation' => 'abcd',
            ])
            ->assertSessionHasErrors('pin');
    });

    it('guest cannot access change-pin routes', function () {
        get(route('change-pin.show'))->assertRedirect('/login');

        post(route('change-pin.update'), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ])->assertRedirect('/login');
    });
});

// ─── Admin: Create Farmer ─────────────────────────────────────────────────────

describe('admin create farmer', function () {
    it('renders the create farmer form for admins', function () {
        actingAs(makeAdmin())
            ->get(route('admin.users.farmers.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/users/CreateFarmer'));
    });

    it('creates a farmer user with correct profile and must_change_pin flag', function () {
        $barangay = makeAddress();

        actingAs(makeAdmin())
            ->post(route('admin.users.farmers.store'), [
                'name' => 'Test Farmer',
                'phone_number' => '09181234567',
                'municipality_id' => $barangay->municipality_id,
                'barangay_id' => $barangay->id,
                'latitude' => 16.4137,   // required — NOT NULL column
                'longitude' => 120.5896,
            ]);

        assertDatabaseHas('users', [
            'name' => 'Test Farmer',
            'phone_number' => '09181234567',
            'must_change_pin' => true,
        ]);

        $user = User::where('phone_number', '09181234567')->firstOrFail();

        expect($user->hasRole('farmer'))->toBeTrue()
            ->and($user->farmerProfile)->not->toBeNull()
            ->and($user->farmerProfile->province_id)->toBe($barangay->municipality->province_id)
            ->and($user->farmerProfile->municipality_id)->toBe($barangay->municipality_id)
            ->and($user->farmerProfile->barangay_id)->toBe($barangay->id);
    });

    it('returns a 6-digit plain PIN in flash after farmer creation', function () {
        $barangay = makeAddress();

        actingAs(makeAdmin())
            ->post(route('admin.users.farmers.store'), [
                'name' => 'Pin Farmer',
                'phone_number' => '09181234568',
                'municipality_id' => $barangay->municipality_id,
                'barangay_id' => $barangay->id,
                'latitude' => 16.4137,
                'longitude' => 120.5896,
            ]);

        expect(session('flash.pin'))
            ->toBeString()
            ->toMatch('/^\d{6}$/');
    });

    it('rejects duplicate phone number', function () {
        $barangay = makeAddress();
        makeFarmer(['phone_number' => '09189999999']);

        actingAs(makeAdmin())
            ->post(route('admin.users.farmers.store'), [
                'name' => 'Duplicate',
                'phone_number' => '09189999999',
                'municipality_id' => $barangay->municipality_id,
                'barangay_id' => $barangay->id,
            ])
            ->assertSessionHasErrors('phone_number');
    });

    it('rejects an invalid PH phone number format', function () {
        $barangay = makeAddress();

        actingAs(makeAdmin())
            ->post(route('admin.users.farmers.store'), [
                'name' => 'Bad Phone',
                'phone_number' => '12345',
                'municipality_id' => $barangay->municipality_id,
                'barangay_id' => $barangay->id,
            ])
            ->assertSessionHasErrors('phone_number');
    });

    it('denies non-admin users access to create farmer', function () {
        actingAs(makeFarmer())
            ->get(route('admin.users.farmers.create'))
            ->assertForbidden();
    });

    it('denies guests access to create farmer', function () {
        get(route('admin.users.farmers.create'))->assertRedirect('/login');
        post(route('admin.users.farmers.store'), [])->assertRedirect('/login');
    });
});

// ─── Admin: Create Dealer ─────────────────────────────────────────────────────

describe('admin create dealer', function () {
    it('renders the create dealer form for admins', function () {
        actingAs(makeAdmin())
            ->get(route('admin.users.dealers.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/users/CreateDealer'));
    });

    it('creates a dealer user with correct profile and must_change_pin flag', function () {
        actingAs(makeAdmin())
            ->post(route('admin.users.dealers.store'), [
                'name' => 'Test Dealer',
                'phone_number' => '09271234567',
            ])
            ->assertRedirect(route('admin.users.dealers.create'))
            ->assertSessionHas('flash.type', 'pin');

        assertDatabaseHas('users', [
            'name' => 'Test Dealer',
            'phone_number' => '09271234567',
            'must_change_pin' => true,
        ]);

        $user = User::where('phone_number', '09271234567')->firstOrFail();

        expect($user->hasRole('dealer'))->toBeTrue()
            ->and($user->dealerProfile)->not->toBeNull();
    });

    it('stores optional email when provided', function () {
        actingAs(makeAdmin())
            ->post(route('admin.users.dealers.store'), [
                'name' => 'Email Dealer',
                'phone_number' => '09271234568',
                'email' => 'dealer@example.com',
            ]);

        assertDatabaseHas('users', ['email' => 'dealer@example.com']);
    });

    it('creates dealer without email when omitted', function () {
        actingAs(makeAdmin())
            ->post(route('admin.users.dealers.store'), [
                'name' => 'No Email Dealer',
                'phone_number' => '09271234569',
            ]);

        assertDatabaseHas('users', [
            'phone_number' => '09271234569',
            'email' => null,
        ]);
    });

    it('denies non-admin users access to create dealer', function () {
        actingAs(makeDealer())
            ->post(route('admin.users.dealers.store'), [
                'name' => 'Sneaky',
                'phone_number' => '09271111111',
            ])
            ->assertForbidden();
    });

    it('denies guests access to create dealer', function () {
        get(route('admin.users.dealers.create'))->assertRedirect('/login');
        post(route('admin.users.dealers.store'), [])->assertRedirect('/login');
    });
});

// ─── Admin: Reset PIN ─────────────────────────────────────────────────────────

describe('admin reset PIN', function () {
    it('resets a user PIN and sets must_change_pin to true', function () {
        $farmer = makeFarmer(['must_change_pin' => false]);
        $farmer->update(['password' => '123456']);
        $oldHash = $farmer->fresh()->password;

        actingAs(makeAdmin())
            ->post(route('admin.users.reset-pin', $farmer))
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'pin');

        $farmer->refresh();

        expect($farmer->must_change_pin)->toBeTrue()
            ->and($farmer->password)->not->toBe($oldHash);
    });

    it('returns a 6-digit plain PIN in flash after reset', function () {
        actingAs(makeAdmin())
            ->post(route('admin.users.reset-pin', makeFarmer()));

        expect(session('flash.pin'))
            ->toBeString()
            ->toMatch('/^\d{6}$/');
    });

    it('the new plain PIN authenticates the user after reset', function () {
        $farmer = makeFarmer();
        $admin = makeAdmin();

        actingAs($admin)->post(route('admin.users.reset-pin', $farmer));
        $newPin = session('flash.pin');
        Auth::logout();

        post('/login', [
            'phone_number' => $farmer->phone_number,
            'password' => $newPin,
        ])->assertRedirect();

        expect(Auth::user()->id)->toBe($farmer->id);
    });

    it('denies a non-admin from resetting a PIN', function () {
        actingAs(makeDealer())
            ->post(route('admin.users.reset-pin', makeFarmer()))
            ->assertForbidden();
    });
});

// ─── Registration routes must not exist ───────────────────────────────────────

describe('registration is disabled', function () {
    it('returns 404 on GET /register', function () {
        get('/register')->assertNotFound();
    });

    it('returns 404 on POST /register', function () {
        post('/register', [])->assertNotFound();
    });

    it('returns 404 on GET /forgot-password', function () {
        get('/forgot-password')->assertNotFound();
    });

    it('returns 404 on POST /forgot-password', function () {
        post('/forgot-password', [])->assertNotFound();
    });

    it('returns 404 on GET /reset-password', function () {
        get('/reset-password/fake-token')->assertNotFound();
    });
});
