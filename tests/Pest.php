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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
|
| A top-level beforeEach() is guaranteed to run before every Feature test.
| The chained ->beforeEach() on pest() has inconsistent execution order in
| some Pest 4 configurations — this form is definitive.
|
*/

beforeEach(function () {
    // Spatie MediaLibrary fires a model observer on every delete that
    // attempts to remove files from disk. Without faking both disks,
    // any test that deletes a HasMedia model will receive a 500.
    Storage::fake('public');
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
|
| All test helper functions live here so they are declared exactly once
| across the entire suite. Declaring them in individual test files causes
| PHP "Cannot redeclare function" errors when multiple files are loaded in
| the same process.
|
*/

// ─── Address ─────────────────────────────────────────────────────────────────

/**
 * Creates a fresh Province → Municipality → Barangay chain.
 * RefreshDatabase wipes these tables; there are no factories for them
 * because the data is normally loaded from CSV seeders in production.
 */
function createBarangay(
    string $provinceName = 'Benguet',
    string $municipalityName = 'La Trinidad',
    string $barangayName = 'Poblacion',
): Barangay {
    $province = Province::create(['name' => $provinceName]);
    $municipality = Municipality::create([
        'province_id' => $province->id,
        'name' => $municipalityName,
        'latitude' => 16.4617,
        'longitude' => 120.5885,
    ]);

    return Barangay::create([
        'municipality_id' => $municipality->id,
        'name' => $barangayName,
    ]);
}

// ─── Users ───────────────────────────────────────────────────────────────────

function createAdminUser(array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'must_change_pin' => false,
    ], $overrides));

    $user->roles()->attach(Role::firstOrCreate(['name' => 'admin']));

    return $user;
}

function createFarmerUser(?Barangay $barangay = null, array $overrides = []): User
{
    $barangay ??= createBarangay();

    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'must_change_pin' => false,
    ], $overrides));

    $user->roles()->attach(Role::firstOrCreate(['name' => 'farmer']));

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

function createDealerUser(array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'must_change_pin' => false,
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
    $category = Category::create(['name' => $categoryName]);
    $vegetable = Vegetable::create(['category_id' => $category->id, 'name' => $vegetableName]);

    return Variety::create(['vegetable_id' => $vegetable->id, 'name' => $varietyName]);
}

// ─── Posts ───────────────────────────────────────────────────────────────────

function createSupplyPost(User $farmer, Variety $variety, array $overrides = []): Post
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

function createDemandPost(User $dealer, Variety $variety, array $overrides = []): Post
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

// ─── Payloads ────────────────────────────────────────────────────────────────

function supplyPostPayload(Variety $variety): array
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

function demandPostPayload(Variety $variety): array
{
    return [
        'variety_id' => $variety->id,
        'quantity_kg' => 50,
        'offered_price' => 45.00,
        'scheduled_date' => now()->addDays(5)->toDateString(),
        'time_slot' => PostTimeSlot::Afternoon->value,
    ];
}
