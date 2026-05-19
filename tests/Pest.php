<?php

use App\Enums\PostItemStatus;
use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Address\Province;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Global Hooks
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Storage::fake('public');
    config(['inertia.ssr.enabled' => false]);
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Shared Helpers
|--------------------------------------------------------------------------
*/

// ─── Address ─────────────────────────────────────────────────────────────────

function createBarangay(
    string $provinceName = 'Benguet',
    string $municipalityName = 'La Trinidad',
    string $barangayName = 'Poblacion',
): Barangay {
    $province = Province::create(['name' => $provinceName]);
    $municipality = Municipality::create([
        'province_id' => $province->id,
        'name'        => $municipalityName,
        'latitude'    => 16.4617,
        'longitude'   => 120.5885,
    ]);

    return Barangay::create([
        'municipality_id' => $municipality->id,
        'name'            => $barangayName,
    ]);
}

// ─── Users ───────────────────────────────────────────────────────────────────

function createAdminUser(array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'must_change_pin'   => false,
    ], $overrides));

    $user->roles()->attach(Role::firstOrCreate(['name' => 'admin']));

    return $user;
}

function createFarmerUser(?Barangay $barangay = null, array $overrides = []): User
{
    $barangay ??= createBarangay();

    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'must_change_pin'   => false,
    ], $overrides));

    $user->roles()->attach(Role::firstOrCreate(['name' => 'farmer']));

    FarmerProfile::create([
        'user_id'         => $user->id,
        'province_id'     => $barangay->municipality->province_id,
        'municipality_id' => $barangay->municipality_id,
        'barangay_id'     => $barangay->id,
        'latitude'        => 16.4137,
        'longitude'       => 120.5960,
    ]);

    return $user;
}

function createDealerUser(array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'must_change_pin'   => false,
    ], $overrides));

    $user->roles()->attach(Role::firstOrCreate(['name' => 'dealer']));
    DealerProfile::create(['user_id' => $user->id]);

    return $user;
}

// ─── Products ────────────────────────────────────────────────────────────────

function createVariety(
    string $categoryName = 'Test Category',
    string $vegetableName = 'Test Vegetable',
    string $varietyName = 'Test Variety',
): Variety {
    $category  = Category::create(['name' => $categoryName]);
    $vegetable = Vegetable::create(['category_id' => $category->id, 'name' => $vegetableName]);

    return Variety::create(['vegetable_id' => $vegetable->id, 'name' => $varietyName]);
}

// ─── Posts ───────────────────────────────────────────────────────────────────

/**
 * Creates a Harvested supply Post with one Ongoing PostItem for the given variety.
 * Pass post-level overrides in $overrides (e.g. ['scheduled_date' => '2025-01-01']).
 */
function createSupplyPost(User $farmer, Variety $variety, array $overrides = []): Post
{
    $post = Post::create(array_merge([
        'user_id'                => $farmer->id,
        'vegetable_id'           => $variety->vegetable_id,
        'type'                   => PostType::Supply,
        'status'                 => PostStatus::Harvested,
        'scheduled_date'         => now()->addDays(7)->toDateString(),
        'time_slot'              => PostTimeSlot::Morning,
        'estimated_total_weight' => 100,
    ], $overrides));

    PostItem::create([
        'post_id'     => $post->id,
        'variety_id'  => $variety->id,
        'quantity_kg' => 100,
        'unit_price'  => 50.00,
        'price_flag'  => PostPriceFlag::Fair,
        'status'      => PostItemStatus::Ongoing,
    ]);

    return $post;
}

function createDemandPost(User $dealer, Variety $variety, array $overrides = []): Post
{
    $itemStatus = $overrides['item_status'] ?? PostItemStatus::Ongoing;
    unset($overrides['item_status']);

    $post = Post::create(array_merge([
        'user_id'        => $dealer->id,
        'vegetable_id'   => $variety->vegetable_id,
        'type'           => PostType::Demand,
        'status'         => PostStatus::Harvested,
        'scheduled_date' => now()->addDays(5)->toDateString(),
        'time_slot'      => PostTimeSlot::Afternoon,
    ], $overrides));

    PostItem::create([
        'post_id'     => $post->id,
        'variety_id'  => $variety->id,
        'quantity_kg' => 50,
        'unit_price'  => 45.00,
        'price_flag'  => PostPriceFlag::Fair,
        'status'      => $itemStatus,
    ]);

    return $post;
}
