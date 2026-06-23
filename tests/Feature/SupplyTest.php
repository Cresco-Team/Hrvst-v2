<?php

use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Database\Seeders\AddressSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(AddressSeeder::class);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function farmerWithProfile(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'farmer')->firstOrCreate(['name' => 'farmer']));
    FarmerProfile::factory()->for($user)->create();

    return $user;
}

function makeVegetable(): Vegetable
{
    $category = Category::firstOrCreate(['name' => 'Leafy Greens']);

    return Vegetable::create([
        'category_id' => $category->id,
        'name' => 'Vegetable '.uniqid(),
    ]);
}

function makeVariety(Vegetable $vegetable): Variety
{
    return Variety::create([
        'vegetable_id' => $vegetable->id,
        'name' => 'Variety '.uniqid(),
    ]);
}

function validSupplyPayload(Vegetable $vegetable, array $varieties, ?string $scheduledDate = null): array
{
    return [
        'vegetable_id' => $vegetable->id,
        'scheduled_date' => $scheduledDate ?? now()->addDays(3)->toDateString(),
        'time_slot' => 'morning',
        'items' => collect($varieties)->map(fn (Variety $v) => [
            'variety_id' => $v->id,
            'quantity_kg' => 100,
        ])->all(),
    ];
}

/**
 * Creates a supply post + one item through the route so user_id is set by the
 * auth system — guarantees the policy ownership check passes in lifecycle tests.
 * Returns the created PostItem.
 */
function createSupplyViaRoute(User $farmer, Variety $variety, ?string $scheduledDate = null): PostItem
{
    actingAs($farmer)->post(route('farmer.supplies.store'), [
        'vegetable_id' => $variety->vegetable_id,
        'scheduled_date' => $scheduledDate ?? now()->addDays(3)->toDateString(),
        'time_slot' => 'morning',
        'items' => [['variety_id' => $variety->id, 'quantity_kg' => 100]],
    ]);

    return PostItem::latest('id')->firstOrFail();
}

// ─── Create Supply ────────────────────────────────────────────────────────────

describe('CreateSupply', function () {

    it('farmer can create a supply with items in a single request', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $variety1 = makeVariety($vegetable);
        $variety2 = makeVariety($vegetable);

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), validSupplyPayload($vegetable, [$variety1, $variety2]))
            ->assertRedirect(route('farmer.supplies.index'));

        $post = Post::first();
        expect($post)->not->toBeNull()
            ->and($post->type)->toBe(PostType::Supply)
            ->and($post->vegetable_id)->toBe($vegetable->id)
            ->and($post->time_slot->value)->toBe('morning')
            ->and($post->postItems)->toHaveCount(2);

        expect((float) $post->postItems->firstWhere('variety_id', $variety1->id)->quantity_kg)->toBe(100.0);
    });

    it('creation is atomic — no post exists if items fail', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [
                'vegetable_id' => $vegetable->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [['variety_id' => 99999, 'quantity_kg' => 10]],
            ])
            ->assertSessionHasErrors('items.0.variety_id');

        expect(Post::count())->toBe(0)
            ->and(PostItem::count())->toBe(0);
    });

    it('rejects a past scheduled_date', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $variety = makeVariety($vegetable);

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), validSupplyPayload($vegetable, [$variety], now()->subDay()->toDateString()))
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects missing required fields', function () {
        $farmer = farmerWithProfile();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [])
            ->assertSessionHasErrors(['vegetable_id', 'scheduled_date', 'time_slot', 'items']);
    });

    it('rejects a variety that does not belong to the vegetable', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $wrongVariety = makeVariety(makeVegetable());

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [
                'vegetable_id' => $vegetable->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [['variety_id' => $wrongVariety->id, 'quantity_kg' => 100]],
            ])
            ->assertSessionHasErrors('items.0.variety_id');
    });

    it('rejects an empty items array', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [
                'vegetable_id' => $vegetable->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [],
            ])
            ->assertSessionHasErrors('items');
    });

    it('non-farmer cannot create a supply', function () {
        $dealer = User::factory()->create();
        $dealer->roles()->attach(Role::where('name', 'dealer')->firstOrCreate(['name' => 'dealer']));
        $vegetable = makeVegetable();
        $variety = makeVariety($vegetable);

        actingAs($dealer)
            ->post(route('farmer.supplies.store'), validSupplyPayload($vegetable, [$variety]))
            ->assertForbidden();
    });

    it('farmer without profile cannot create a supply', function () {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'farmer')->firstOrCreate(['name' => 'farmer']));
        $vegetable = makeVegetable();
        $variety = makeVariety($vegetable);

        actingAs($user)
            ->post(route('farmer.supplies.store'), validSupplyPayload($vegetable, [$variety]))
            ->assertForbidden();
    });

});

// ─── Update Supply ────────────────────────────────────────────────────────────

describe('UpdateSupply', function () {

    it('farmer can update scheduled_date', function () {
        $farmer = farmerWithProfile();
        $variety = makeVariety(makeVegetable());
        $item = createSupplyViaRoute($farmer, $variety);
        $post = $item->post;

        $newDate = now()->addDays(10)->toDateString();

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $post), ['scheduled_date' => $newDate])
            ->assertRedirect(route('farmer.supplies.index'));

        expect($post->fresh()->scheduled_date->toDateString())->toBe($newDate);
    });

    it('farmer can update items — replaces existing non-fulfilled items', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $variety1 = makeVariety($vegetable);
        $variety2 = makeVariety($vegetable);
        $item = createSupplyViaRoute($farmer, $variety1);
        $post = $item->post;

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $post), [
                'items' => [['variety_id' => $variety2->id, 'quantity_kg' => 200]],
            ])
            ->assertRedirect();

        $post->refresh();
        expect($post->postItems)->toHaveCount(1)
            ->and($post->postItems->first()->variety_id)->toBe($variety2->id)
            ->and((float) $post->postItems->first()->quantity_kg)->toBe(200.0);
    });

    it('farmer cannot update another farmer\'s supply', function () {
        $farmer = farmerWithProfile();
        $other = farmerWithProfile();
        $variety = makeVariety(makeVegetable());
        $item = createSupplyViaRoute($other, $variety);
        $post = $item->post;

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $post), [
                'scheduled_date' => now()->addDays(5)->toDateString(),
            ])
            ->assertForbidden();
    });

});

// ─── Supply Lifecycle ─────────────────────────────────────────────────────────

describe('SupplyLifecycle', function () {

    it('farmer can delete a supply', function () {
        $farmer = farmerWithProfile();
        $item = createSupplyViaRoute($farmer, makeVariety(makeVegetable()));
        $post = $item->post;

        actingAs($farmer)
            ->delete(route('farmer.supplies.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull();
    });

    it('deleting a supply soft-deletes the post record', function () {
        $farmer = farmerWithProfile();
        $item = createSupplyViaRoute($farmer, makeVariety(makeVegetable()));
        $post = $item->post;

        actingAs($farmer)
            ->delete(route('farmer.supplies.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull()
            ->and(Post::withTrashed()->find($post->id))->not->toBeNull();
    });

    it('farmer can archive a fulfilled supply item', function () {
        $farmer = farmerWithProfile();
        $item = createSupplyViaRoute($farmer, makeVariety(makeVegetable()));

        DB::table('post_items')->where('id', $item->id)->update(['status' => 'fulfilled']);

        actingAs($farmer)
            ->post(route('farmer.post-items.archive', $item))
            ->assertRedirect();

        expect($item->fresh()->status)->toBe(PostItemStatus::Unsettled);
    });

    it('farmer can fulfill an unsettled supply item', function () {
        $farmer = farmerWithProfile();
        $item = createSupplyViaRoute($farmer, makeVariety(makeVegetable()));

        DB::table('post_items')->where('id', $item->id)->update(['status' => 'unsettled']);

        actingAs($farmer)
            ->post(route('farmer.post-items.fulfill', $item))
            ->assertRedirect();

        expect($item->fresh()->status)->toBe(PostItemStatus::Fulfilled);
    });

});
