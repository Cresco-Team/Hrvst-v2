<?php

use App\Enums\PostItemStatus;
use App\Enums\PostStatus;
use App\Models\Profiles\DealerProfile;
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

describe('admin dealer access control', function () {
    it('redirects guest to login on index', function () {
        get(route('admin.dealers.index'))
            ->assertRedirect(route('login'));
    });

    it('redirects guest to login on show', function () {
        $dealer = createDealerUser();

        get(route('admin.dealers.show', $dealer->dealerProfile))
            ->assertRedirect(route('login'));
    });

    it('blocks a farmer from admin dealer routes', function () {
        actingAs(createFarmerUser())
            ->get(route('admin.dealers.index'))
            ->assertForbidden();
    });

    it('blocks a dealer from admin dealer routes — even their own data', function () {
        $dealer = createDealerUser();

        actingAs($dealer)
            ->get(route('admin.dealers.index'))
            ->assertForbidden();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// INDEX
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin dealer index', function () {
    it('renders the dealer list page for admin', function () {
        actingAs(createAdminUser())
            ->get(route('admin.dealers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dealers/Index')
                ->has('filters')
            );
    });

    it('passes null search when no search query is given', function () {
        actingAs(createAdminUser())
            ->get(route('admin.dealers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', null)
            );
    });

    it('passes search filter through to Inertia props', function () {
        actingAs(createAdminUser())
            ->get(route('admin.dealers.index', ['search' => 'John']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'John')
            );
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// SHOW
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin dealer show', function () {
    it('renders the dealer profile page', function () {
        $dealer = createDealerUser();

        actingAs(createAdminUser())
            ->get(route('admin.dealers.show', $dealer->dealerProfile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/dealers/Show')
            );
    });

    it('returns 404 for a nonexistent dealer profile', function () {
        actingAs(createAdminUser())
            ->get(route('admin.dealers.show', 999999))
            ->assertNotFound();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DESTROY
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin dealer destroy', function () {
    it('admin can delete a dealer and is redirected with a success flash', function () {
        $this->withoutExceptionHandling();

        $dealer = createDealerUser();
        $profile = $dealer->dealerProfile;

        actingAs(createAdminUser())
            ->delete(route('admin.dealers.destroy', $profile))
            ->assertRedirect(route('admin.dealers.index'))
            ->assertSessionHas('flash.type', 'success');
    });

    it('the dealer profile row is hard-deleted', function () {
        $this->withoutExceptionHandling();

        $dealer = createDealerUser();
        $profile = $dealer->dealerProfile;

        actingAs(createAdminUser())
            ->delete(route('admin.dealers.destroy', $profile));

        expect(DealerProfile::find($profile->id))->toBeNull();
    });

    it('the associated user account is also deleted', function () {
        $this->withoutExceptionHandling();

        $dealer = createDealerUser();
        $userId = $dealer->id;

        actingAs(createAdminUser())
            ->delete(route('admin.dealers.destroy', $dealer->dealerProfile));

        expect(User::find($userId))->toBeNull();
    });

    it('dealer cannot delete their own profile via admin route — EnsureUserIsAdmin blocks first', function () {
        $dealer = createDealerUser();

        actingAs($dealer)
            ->delete(route('admin.dealers.destroy', $dealer->dealerProfile))
            ->assertForbidden();
    });

    it('dealer cannot delete another dealer via admin route', function () {
        $target = createDealerUser();

        actingAs(createDealerUser())
            ->delete(route('admin.dealers.destroy', $target->dealerProfile))
            ->assertForbidden();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DETAILS API
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin dealer details api', function () {
    it('returns a json response for a valid dealer', function () {
        $dealer = createDealerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.dealers.api.details', $dealer->dealerProfile))
            ->assertOk()
            ->assertJsonStructure(['id', 'joined_at', 'user']);
    });

    it('user block contains name and email', function () {
        $dealer = createDealerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.dealers.api.details', $dealer->dealerProfile))
            ->assertOk()
            ->assertJsonStructure(['user' => ['name', 'email']]);
    });

    it('demands list is present and empty when the dealer has no posts', function () {
        $dealer = createDealerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.dealers.api.details', $dealer->dealerProfile))
            ->assertOk()
            ->assertJsonPath('demands', []);
    });

    it('demands list includes ongoing demand data', function () {
        $dealer = createDealerUser();
        $variety = createVariety();
        createDemandPost($dealer, $variety);

        actingAs(createAdminUser())
            ->getJson(route('admin.dealers.api.details', $dealer->dealerProfile))
            ->assertOk()
            ->assertJsonCount(1, 'demands');
    });

    it('demands list excludes archived posts', function () {
        $dealer = createDealerUser();
        $variety = createVariety();
        createDemandPost($dealer, $variety, ['item_status' => PostItemStatus::Archived]);

        // DealerService::details loads postItems scoped to ongoing() only.
        // A post whose only item is archived produces an empty postItems collection → demands = [].
        actingAs(createAdminUser())
            ->getJson(route('admin.dealers.api.details', $dealer->dealerProfile))
            ->assertOk()
            ->assertJsonPath('demands', []);
    });

    it('returns 404 for a nonexistent dealer profile', function () {
        actingAs(createAdminUser())
            ->getJson(route('admin.dealers.api.details', 999999))
            ->assertNotFound();
    });
});
