<?php

use App\Enums\PostItemStatus;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function dealerWithProfile(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'dealer')->firstOrCreate(['name' => 'dealer']));
    DealerProfile::factory()->for($user)->create();

    return $user;
}

function demandVegetableAndVarieties(): array
{
    $category = Category::factory()->create();
    $vegetable = Vegetable::factory()->for($category)->create();
    $variety1 = Variety::factory()->for($vegetable)->create();
    $variety2 = Variety::factory()->for($vegetable)->create();

    return [$vegetable, $variety1, $variety2];
}

function validDemandPayload(Vegetable $vegetable, Variety $variety1, Variety $variety2, ?string $scheduledDate = null): array
{
    return [
        'vegetable_id' => $vegetable->id,
        'scheduled_date' => $scheduledDate ?? now()->addDays(5)->toDateString(),
        'time_slot' => 'morning',
        'items' => [
            [
                'variety_id' => $variety1->id,
                'quantity_kg' => 100,
                'unit_price' => 20.00,
            ],
            [
                'variety_id' => $variety2->id,
                'quantity_kg' => 50,
                'unit_price' => null,
            ],
        ],
    ];
}

// ─── Create Demand ────────────────────────────────────────────────────────────

describe('CreateDemand', function () {

    it('dealer can create a demand with items in a single request', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), validDemandPayload($vegetable, $variety1, $variety2))
            ->assertRedirect(route('dealer.demands.index'));

        $post = Post::first();
        expect($post)->not->toBeNull()
            ->and($post->status)->toBe(PostStatus::Harvested)
            ->and($post->type)->toBe(PostType::Demand)
            ->and($post->vegetable_id)->toBe($vegetable->id)
            ->and($post->time_slot->value)->toBe('morning')
            ->and($post->postItems)->toHaveCount(2);

        $item1 = $post->postItems->firstWhere('variety_id', $variety1->id);
        expect((float) $item1->quantity_kg)->toBe(100.0)
            ->and((float) $item1->unit_price)->toBe(20.0);

        $item2 = $post->postItems->firstWhere('variety_id', $variety2->id);
        expect($item2->unit_price)->toBeNull();
    });

    it('demand creation is atomic — no post exists if items fail', function () {
        $dealer = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), [
                'vegetable_id' => $vegetable->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [
                    ['variety_id' => 99999, 'quantity_kg' => 10, 'unit_price' => null],
                ],
            ])
            ->assertSessionHasErrors('items.0.variety_id');

        expect(Post::count())->toBe(0)
            ->and(PostItem::count())->toBe(0);
    });

    it('rejects missing vegetable_id', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();
        $payload = validDemandPayload($vegetable, $variety1, $variety2);
        unset($payload['vegetable_id']);

        actingAs($dealer)
            ->post(route('dealer.demands.store'), $payload)
            ->assertSessionHasErrors('vegetable_id');
    });

    it('rejects past scheduled_date', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), validDemandPayload($vegetable, $variety1, $variety2, now()->subDay()->toDateString()))
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects scheduled_date beyond 3 months', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), validDemandPayload($vegetable, $variety1, $variety2, now()->addMonths(4)->toDateString()))
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects empty items array', function () {
        $dealer = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), [
                'vegetable_id' => $vegetable->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [],
            ])
            ->assertSessionHasErrors('items');
    });

    it('rejects item with zero quantity', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), [
                'vegetable_id' => $vegetable->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [
                    ['variety_id' => $variety1->id, 'quantity_kg' => 0, 'unit_price' => null],
                ],
            ])
            ->assertSessionHasErrors('items.0.quantity_kg');
    });

    it('non-dealer cannot create a demand', function () {
        $user = User::factory()->create();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();

        actingAs($user)
            ->post(route('dealer.demands.store'), validDemandPayload($vegetable, $variety1, $variety2))
            ->assertForbidden();
    });

    it('dealer without profile cannot create a demand', function () {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'dealer')->firstOrCreate(['name' => 'dealer']));
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();

        actingAs($user)
            ->post(route('dealer.demands.store'), validDemandPayload($vegetable, $variety1, $variety2))
            ->assertForbidden();
    });

});

// ─── Update Demand ────────────────────────────────────────────────────────────

describe('UpdateDemand', function () {

    it('dealer can update scheduled_date on an active demand', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Harvested,
            'scheduled_date' => now()->addDays(5),
        ]);
        PostItem::factory()->for($post)->for($variety1)->create();

        $newDate = now()->addDays(10)->toDateString();

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), ['scheduled_date' => $newDate])
            ->assertRedirect(route('dealer.demands.index'));

        expect($post->fresh()->scheduled_date->toDateString())->toBe($newDate);
    });

    it('updating items replaces all existing ongoing items', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Harvested,
            'scheduled_date' => now()->addDays(5),
        ]);
        PostItem::factory()->for($post)->for($variety1)->create(['quantity_kg' => 50]);

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), [
                'items' => [
                    ['variety_id' => $variety2->id, 'quantity_kg' => 200, 'unit_price' => 15],
                ],
            ])
            ->assertRedirect();

        $post->refresh();
        expect($post->postItems)->toHaveCount(1)
            ->and($post->postItems->first()->variety_id)->toBe($variety2->id)
            ->and((float) $post->postItems->first()->quantity_kg)->toBe(200.0);
    });

    it('dealer cannot update another dealer\'s demand', function () {
        $dealer = dealerWithProfile();
        $other = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();
        $post = Post::factory()->for($other)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Harvested,
            'scheduled_date' => now()->addDay(),
        ]);

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), ['scheduled_date' => now()->addDays(2)->toDateString()])
            ->assertForbidden();
    });

});

// ─── Demand Item Lifecycle ────────────────────────────────────────────────────

describe('DemandLifecycle', function () {

    it('dealer can archive an ongoing demand item', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Harvested,
        ]);
        $item = PostItem::factory()->for($post)->for($variety1)->create([
            'status' => PostItemStatus::Ongoing,
        ]);

        actingAs($dealer)
            ->post(route('dealer.post-items.archive', $item))
            ->assertRedirect();

        expect($item->fresh()->status)->toBe(PostItemStatus::Archived);
    });

    it('dealer can fulfill an ongoing demand item', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Harvested,
        ]);
        $item = PostItem::factory()->for($post)->for($variety1)->create([
            'status' => PostItemStatus::Ongoing,
        ]);

        actingAs($dealer)
            ->post(route('dealer.post-items.fulfill', $item))
            ->assertRedirect();

        expect($item->fresh()->status)->toBe(PostItemStatus::Fulfilled);
    });

    it('dealer cannot delete a demand with ongoing items', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Harvested,
        ]);
        PostItem::factory()->for($post)->for($variety1)->create([
            'status' => PostItemStatus::Ongoing,
        ]);

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertForbidden();
    });

    it('dealer can delete a demand with no ongoing items', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Harvested,
        ]);
        PostItem::factory()->for($post)->for($variety1)->create([
            'status' => PostItemStatus::Archived,
        ]);

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull();
    });

    it('deleting a demand soft-deletes the post record', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Harvested,
        ]);
        PostItem::factory()->for($post)->for($variety1)->create([
            'status' => PostItemStatus::Fulfilled,
        ]);

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull()
            ->and(Post::withTrashed()->find($post->id))->not->toBeNull();
    });

});

// ─── Cross-role access ────────────────────────────────────────────────────────

describe('CrossRoleAccess', function () {

    it('farmer cannot access dealer demand routes', function () {
        $farmer = User::factory()->create();
        $farmer->roles()->attach(Role::where('name', 'farmer')->firstOrCreate(['name' => 'farmer']));

        actingAs($farmer)
            ->post(route('dealer.demands.store'), [])
            ->assertForbidden();
    });

    it('unauthenticated user is redirected from supply and demand routes', function () {
        $this->post(route('farmer.supplies.store'), [])->assertRedirect(route('login'));
        $this->post(route('dealer.demands.store'), [])->assertRedirect(route('login'));
    });

});
