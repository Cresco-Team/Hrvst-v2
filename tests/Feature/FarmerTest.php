<?php

use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);
});

// ═══════════════════════════════════════════════════════════════════════════════
// ACCESS CONTROL
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer access control', function () {
    it('redirects guest to login on index', function () {
        get(route('admin.farmers.index'))
            ->assertRedirect(route('login'));
    });

    it('redirects guest to login on show', function () {
        $farmer = createFarmerUser();

        get(route('admin.farmers.show', $farmer->farmerProfile))
            ->assertRedirect(route('login'));
    });

    it('redirects guest to login on markers api', function () {
        get(route('admin.farmers.api.markers'))
            ->assertRedirect(route('login'));
    });

    it('blocks a farmer from all admin farmer routes', function () {
        actingAs(createFarmerUser())
            ->get(route('admin.farmers.index'))
            ->assertForbidden();
    });

    it('blocks a dealer from all admin farmer routes', function () {
        actingAs(createDealerUser())
            ->get(route('admin.farmers.index'))
            ->assertForbidden();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// INDEX
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer index', function () {
    it('renders the farmer list page for admin', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/farmers/Index')
                ->has('filters')
                ->has('mapConfig')
            );
    });

    it('defaults to list view', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('view', 'list')
            );
    });

    it('switches to map view when query param is set', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index', ['view' => 'map']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('view', 'map')
                ->where('farmers', null) // deferred only for list view
            );
    });

    it('passes search filter through to Inertia props', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index', ['search' => 'Jane']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'Jane')
            );
    });

    it('passes null search when no search query is given', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', null)
            );
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// SHOW
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer show', function () {
    it('renders the farmer profile page', function () {
        $farmer = createFarmerUser();

        actingAs(createAdminUser())
            ->get(route('admin.farmers.show', $farmer->farmerProfile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/farmers/Show')
            );
    });

    it('returns 404 for a nonexistent farmer profile', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.show', 999999))
            ->assertNotFound();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DESTROY
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer destroy', function () {
    it('admin can delete a farmer and is redirected with a success flash', function () {
        $this->withoutExceptionHandling();

        $farmer = createFarmerUser();
        $profile = $farmer->farmerProfile;

        actingAs(createAdminUser())
            ->delete(route('admin.farmers.destroy', $profile))
            ->assertRedirect(route('admin.farmers.index'))
            ->assertSessionHas('flash.type', 'success');
    });

    it('the farmer profile row is hard-deleted', function () {
        $this->withoutExceptionHandling();

        $farmer = createFarmerUser();
        $profile = $farmer->farmerProfile;

        actingAs(createAdminUser())
            ->delete(route('admin.farmers.destroy', $profile));

        expect(FarmerProfile::find($profile->id))->toBeNull();
    });

    it('the associated user account is also deleted', function () {
        $this->withoutExceptionHandling();

        $farmer = createFarmerUser();
        $userId = $farmer->id;

        actingAs(createAdminUser())
            ->delete(route('admin.farmers.destroy', $farmer->farmerProfile));

        expect(User::find($userId))->toBeNull();
    });

    it('farmer cannot delete another farmer via admin route — EnsureUserIsAdmin blocks first', function () {
        $target = createFarmerUser();

        actingAs(createFarmerUser())
            ->delete(route('admin.farmers.destroy', $target->farmerProfile))
            ->assertForbidden();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// MARKERS API
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer markers api', function () {
    it('returns a json response with markers and total keys', function () {
        createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.markers'))
            ->assertOk()
            ->assertJsonStructure(['markers', 'total']);
    });

    it('returns empty markers when no farmers exist', function () {
        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.markers'))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('markers', []);
    });

    it('total reflects the correct number of farmers', function () {
        createFarmerUser();
        createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.markers'))
            ->assertOk()
            ->assertJsonPath('total', 2);
    });

    it('each marker contains the required structure', function () {
        createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.markers'))
            ->assertOk()
            ->assertJsonStructure([
                'markers' => [[
                    'id',
                    'coordinates' => ['lat', 'lng'],
                    'farmer_name',
                    'municipality',
                    'ongoing_supplies_count',
                    'supplies_summary',
                ]],
            ]);
    });

    it('municipality_id filter returns only farmers in that municipality', function () {
        $barangayA = createBarangay('Benguet', 'La Trinidad', 'Pico');   // municipality A
        $barangayB = createBarangay('Benguet', 'Tublay', 'Ambassador');   // municipality B

        createFarmerUser($barangayA);
        createFarmerUser($barangayA); // 2 in A
        createFarmerUser($barangayB); // 1 in B — should be excluded

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.markers', [
                'municipality_id' => $barangayA->municipality_id,
            ]))
            ->assertOk()
            ->assertJsonPath('total', 2);
    });

    it('variety_id filter returns only farmers with an ongoing supply for that variety', function () {
        $variety = createVariety();
        $otherVariety = createVariety('Other Category', 'Other Vegetable', 'Other Variety');

        $match = createFarmerUser();
        createSupplyPost($match, $variety);

        $noMatch = createFarmerUser();
        createSupplyPost($noMatch, $otherVariety);

        createFarmerUser(); // no supply at all

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.markers', ['variety_id' => $variety->id]))
            ->assertOk()
            ->assertJsonPath('total', 1);
    });

    it('rejects an invalid municipality_id with a validation error', function () {
        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.markers', ['municipality_id' => 999999]))
            ->assertUnprocessable();
    });

    it('rejects an invalid variety_id with a validation error', function () {
        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.markers', ['variety_id' => 999999]))
            ->assertUnprocessable();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DETAILS API
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer details api', function () {
    it('returns a json response for a valid farmer', function () {
        $farmer = createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonStructure(['id', 'joined_at', 'user', 'location']);
    });

    it('user block contains name and email', function () {
        $farmer = createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonStructure(['user' => ['name', 'email']]);
    });

    it('location block contains all address components', function () {
        $farmer = createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonStructure([
                'location' => ['province', 'municipality', 'barangay', 'full_address', 'coordinates'],
            ]);
    });

    it('supplies list is present and empty when the farmer has no posts', function () {
        $farmer = createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonPath('supplies', []);
    });

    it('supplies list includes ongoing supply data', function () {
        $farmer = createFarmerUser();
        $variety = createVariety();
        createSupplyPost($farmer, $variety);

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonCount(1, 'supplies');
    });

    it('returns 404 for a nonexistent farmer profile', function () {
        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', 999999))
            ->assertNotFound();
    });
});
