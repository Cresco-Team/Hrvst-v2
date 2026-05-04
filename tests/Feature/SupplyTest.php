<?php

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
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
    $category = Category::factory()->create();

    return Vegetable::factory()->for($category)->create();
}

function makeVariety(Vegetable $vegetable): Variety
{
    return Variety::factory()->for($vegetable)->create();
}

// ─── Create Supply ────────────────────────────────────────────────────────────

describe('CreateSupply', function () {

    it('farmer can create a supply post in growing status', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [
                'vegetable_id' => $vegetable->id,
                'target_month' => now()->format('Y-m'),
                'estimated_total_weight' => 500,
            ])
            ->assertRedirect(route('farmer.supplies.index'));

        $post = Post::first();
        expect($post)->not->toBeNull()
            ->and($post->status)->toBe(PostStatus::Growing)
            ->and($post->type)->toBe(PostType::Supply)
            ->and($post->vegetable_id)->toBe($vegetable->id)
            ->and($post->target_month)->toBe(now()->format('Y-m'))
            ->and((float) $post->estimated_total_weight)->toBe(500.0)
            ->and($post->scheduled_at)->toBeNull()
            ->and($post->postItems)->toHaveCount(0);
    });

    it('farmer can upload an image when creating a supply', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [
                'vegetable_id' => $vegetable->id,
                'target_month' => now()->format('Y-m'),
                'estimated_total_weight' => 200,
                'image' => UploadedFile::fake()->image('crop.jpg'),
            ])
            ->assertRedirect();

        expect(Post::first()->getFirstMedia('post_image'))->not->toBeNull();
    });

    it('rejects a past target_month', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [
                'vegetable_id' => $vegetable->id,
                'target_month' => now()->subMonth()->format('Y-m'),
                'estimated_total_weight' => 100,
            ])
            ->assertSessionHasErrors('target_month');
    });

    it('rejects missing required fields', function () {
        $farmer = farmerWithProfile();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [])
            ->assertSessionHasErrors(['vegetable_id', 'target_month', 'estimated_total_weight']);
    });

    it('non-farmer cannot create a supply', function () {
        $dealer = User::factory()->create();
        $dealer->roles()->attach(Role::where('name', 'dealer')->firstOrCreate(['name' => 'dealer']));
        $vegetable = makeVegetable();

        actingAs($dealer)
            ->post(route('farmer.supplies.store'), [
                'vegetable_id' => $vegetable->id,
                'target_month' => now()->format('Y-m'),
                'estimated_total_weight' => 100,
            ])
            ->assertForbidden();
    });

    it('farmer without profile cannot create a supply', function () {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'farmer')->firstOrCreate(['name' => 'farmer']));
        $vegetable = makeVegetable();

        actingAs($user)
            ->post(route('farmer.supplies.store'), [
                'vegetable_id' => $vegetable->id,
                'target_month' => now()->format('Y-m'),
                'estimated_total_weight' => 100,
            ])
            ->assertForbidden();
    });

});

// ─── Update Supply ────────────────────────────────────────────────────────────

describe('UpdateSupply', function () {

    it('farmer can update a growing supply', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Growing,
            'target_month' => now()->format('Y-m'),
            'estimated_total_weight' => 100,
        ]);

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $post), [
                'estimated_total_weight' => 350,
            ])
            ->assertRedirect(route('farmer.supplies.index'));

        expect($post->fresh()->estimated_total_weight)->toBe('350.00');
    });

    it('farmer cannot update an ongoing supply', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDay(),
        ]);

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $post), [
                'estimated_total_weight' => 999,
            ])
            ->assertForbidden();
    });

    it('farmer cannot update another farmer\'s supply', function () {
        $farmer = farmerWithProfile();
        $other = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($other)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Growing,
        ]);

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $post), [
                'estimated_total_weight' => 999,
            ])
            ->assertForbidden();
    });

});

// ─── Harvest Supply ───────────────────────────────────────────────────────────

describe('HarvestSupply', function () {

    it('farmer can harvest a growing supply, creating items and transitioning to ongoing', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $variety1 = makeVariety($vegetable);
        $variety2 = makeVariety($vegetable);

        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Growing,
            'estimated_total_weight' => 200,
        ]);

        $scheduledAt = now()->addDays(3)->toDateString();

        actingAs($farmer)
            ->post(route('farmer.supplies.harvest', $post), [
                'scheduled_at' => $scheduledAt,
                'items' => [
                    [
                        'variety_id' => $variety1->id,
                        'quantity_kg' => 120,
                        'unit_price' => 25.00,
                        'time_slot' => 'morning',
                    ],
                    [
                        'variety_id' => $variety2->id,
                        'quantity_kg' => 80,
                        'unit_price' => 30.00,
                        'time_slot' => 'afternoon',
                    ],
                ],
            ])
            ->assertRedirect(route('farmer.supplies.index'));

        $post->refresh();

        expect($post->status)->toBe(PostStatus::Ongoing)
            ->and($post->scheduled_at->toDateString())->toBe($scheduledAt)
            ->and($post->postItems)->toHaveCount(2);

        $item1 = $post->postItems->firstWhere('variety_id', $variety1->id);
        expect((float) $item1->quantity_kg)->toBe(120.0)
            ->and((float) $item1->unit_price)->toBe(25.0)
            ->and($item1->time_slot->value)->toBe('morning');
    });

    it('harvest is atomic — rolls back on failure', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Growing,
        ]);

        // Non-existent variety_id should fail validation before DB, but let's
        // verify no partial items exist if something slips through.
        actingAs($farmer)
            ->post(route('farmer.supplies.harvest', $post), [
                'scheduled_at' => now()->addDay()->toDateString(),
                'items' => [
                    ['variety_id' => 99999, 'quantity_kg' => 10, 'unit_price' => 5, 'time_slot' => 'morning'],
                ],
            ])
            ->assertSessionHasErrors('items.0.variety_id');

        expect(PostItem::count())->toBe(0)
            ->and($post->fresh()->status)->toBe(PostStatus::Growing);
    });

    it('cannot harvest a supply that is already ongoing', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDay(),
        ]);

        actingAs($farmer)
            ->post(route('farmer.supplies.harvest', $post), [
                'scheduled_at' => now()->addDay()->toDateString(),
                'items' => [],
            ])
            ->assertForbidden();
    });

    it('harvest requires at least one item', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Growing,
        ]);

        actingAs($farmer)
            ->post(route('farmer.supplies.harvest', $post), [
                'scheduled_at' => now()->addDay()->toDateString(),
                'items' => [],
            ])
            ->assertSessionHasErrors('items');
    });

    it('harvest rejects a past scheduled_at', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $variety = makeVariety($vegetable);
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Growing,
        ]);

        actingAs($farmer)
            ->post(route('farmer.supplies.harvest', $post), [
                'scheduled_at' => now()->subDay()->toDateString(),
                'items' => [
                    ['variety_id' => $variety->id, 'quantity_kg' => 10, 'unit_price' => 5, 'time_slot' => 'morning'],
                ],
            ])
            ->assertSessionHasErrors('scheduled_at');
    });

    it('cannot harvest another farmer\'s supply', function () {
        $farmer = farmerWithProfile();
        $other = farmerWithProfile();
        $vegetable = makeVegetable();
        $variety = makeVariety($vegetable);
        $post = Post::factory()->for($other)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Growing,
        ]);

        actingAs($farmer)
            ->post(route('farmer.supplies.harvest', $post), [
                'scheduled_at' => now()->addDay()->toDateString(),
                'items' => [
                    ['variety_id' => $variety->id, 'quantity_kg' => 10, 'unit_price' => 5, 'time_slot' => 'morning'],
                ],
            ])
            ->assertForbidden();
    });

});

// ─── Archive / Fulfill / Delete Supply ───────────────────────────────────────

describe('SupplyLifecycle', function () {

    it('farmer can archive a growing supply', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Growing,
        ]);

        actingAs($farmer)
            ->post(route('farmer.supplies.archive', $post))
            ->assertRedirect();

        expect($post->fresh()->status)->toBe(PostStatus::Archived);
    });

    it('farmer can archive an ongoing supply', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDay(),
        ]);

        actingAs($farmer)
            ->post(route('farmer.supplies.archive', $post))
            ->assertRedirect();

        expect($post->fresh()->status)->toBe(PostStatus::Archived);
    });

    it('farmer can fulfill an ongoing supply', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDay(),
        ]);

        actingAs($farmer)
            ->post(route('farmer.supplies.fulfill', $post))
            ->assertRedirect();

        expect($post->fresh()->status)->toBe(PostStatus::Fulfilled);
    });

    it('farmer can delete any of their own supplies', function () {
        $farmer = farmerWithProfile();
        $vegetable = makeVegetable();
        $post = Post::factory()->for($farmer)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Growing,
        ]);

        actingAs($farmer)
            ->delete(route('farmer.supplies.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull();
    });

});
