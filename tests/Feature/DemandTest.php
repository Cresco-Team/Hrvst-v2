<?php

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

function validDemandPayload(Vegetable $vegetable, Variety $variety1, Variety $variety2, ?string $scheduledAt = null): array
{
    return [
        'vegetable_id' => $vegetable->id,
        'scheduled_at' => $scheduledAt ?? now()->addDays(5)->toDateString(),
        'items' => [
            [
                'variety_id' => $variety1->id,
                'quantity_kg' => 100,
                'unit_price' => 20.00,
                'time_slot' => 'morning',
            ],
            [
                'variety_id' => $variety2->id,
                'quantity_kg' => 50,
                'unit_price' => null,
                'time_slot' => 'afternoon',
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
            ->and($post->status)->toBe(PostStatus::Ongoing)
            ->and($post->type)->toBe(PostType::Demand)
            ->and($post->vegetable_id)->toBe($vegetable->id)
            ->and($post->postItems)->toHaveCount(2);

        $item1 = $post->postItems->firstWhere('variety_id', $variety1->id);
        expect((float) $item1->quantity_kg)->toBe(100.0)
            ->and((float) $item1->unit_price)->toBe(20.0)
            ->and($item1->time_slot->value)->toBe('morning');

        $item2 = $post->postItems->firstWhere('variety_id', $variety2->id);
        expect($item2->unit_price)->toBeNull()
            ->and($item2->time_slot->value)->toBe('afternoon');
    });

    it('demand creation is atomic — no post exists if items fail', function () {
        $dealer = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), [
                'vegetable_id' => $vegetable->id,
                'scheduled_at' => now()->addDay()->toDateString(),
                'items' => [
                    ['variety_id' => 99999, 'quantity_kg' => 10, 'unit_price' => null, 'time_slot' => 'morning'],
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

    it('rejects past scheduled_at', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), validDemandPayload($vegetable, $variety1, $variety2, now()->subDay()->toDateString()))
            ->assertSessionHasErrors('scheduled_at');
    });

    it('rejects scheduled_at beyond 3 months', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), validDemandPayload($vegetable, $variety1, $variety2, now()->addMonths(4)->toDateString()))
            ->assertSessionHasErrors('scheduled_at');
    });

    it('rejects empty items array', function () {
        $dealer = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), [
                'vegetable_id' => $vegetable->id,
                'scheduled_at' => now()->addDay()->toDateString(),
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
                'scheduled_at' => now()->addDay()->toDateString(),
                'items' => [
                    ['variety_id' => $variety1->id, 'quantity_kg' => 0, 'unit_price' => null, 'time_slot' => 'morning'],
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

    it('dealer can update scheduled_at on an ongoing demand', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDays(5),
        ]);
        PostItem::factory()->for($post)->for($variety1)->create();

        $newDate = now()->addDays(10)->toDateString();

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), ['scheduled_at' => $newDate])
            ->assertRedirect(route('dealer.demands.index'));

        expect($post->fresh()->scheduled_at->toDateString())->toBe($newDate);
    });

    it('updating items replaces all existing items', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDays(5),
        ]);
        PostItem::factory()->for($post)->for($variety1)->create(['quantity_kg' => 50]);

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), [
                'items' => [
                    ['variety_id' => $variety2->id, 'quantity_kg' => 200, 'unit_price' => 15, 'time_slot' => 'evening'],
                ],
            ])
            ->assertRedirect();

        $post->refresh();
        expect($post->postItems)->toHaveCount(1)
            ->and($post->postItems->first()->variety_id)->toBe($variety2->id)
            ->and((float) $post->postItems->first()->quantity_kg)->toBe(200.0);
    });

    it('dealer cannot update an archived demand', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Archived,
            'scheduled_at' => now()->subDay(),
        ]);

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), ['scheduled_at' => now()->addDay()->toDateString()])
            ->assertForbidden();
    });

    it('dealer cannot update another dealer\'s demand', function () {
        $dealer = dealerWithProfile();
        $other = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();
        $post = Post::factory()->for($other)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDay(),
        ]);

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), ['scheduled_at' => now()->addDays(2)->toDateString()])
            ->assertForbidden();
    });

});

// ─── Archive / Fulfill / Delete Demand ───────────────────────────────────────

describe('DemandLifecycle', function () {

    it('dealer can archive an ongoing demand', function () {
        $dealer = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDay(),
        ]);

        actingAs($dealer)
            ->post(route('dealer.demands.archive', $post))
            ->assertRedirect();

        expect($post->fresh()->status)->toBe(PostStatus::Archived);
    });

    it('dealer can fulfill an ongoing demand', function () {
        $dealer = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDay(),
        ]);

        actingAs($dealer)
            ->post(route('dealer.demands.fulfill', $post))
            ->assertRedirect();

        expect($post->fresh()->status)->toBe(PostStatus::Fulfilled);
    });

    it('dealer cannot delete an ongoing demand', function () {
        $dealer = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Ongoing,
            'scheduled_at' => now()->addDay(),
        ]);

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertForbidden();
    });

    it('dealer can delete an archived demand', function () {
        $dealer = dealerWithProfile();
        [$vegetable] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Archived,
            'scheduled_at' => now()->subDay(),
        ]);

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull();
    });

    it('deleting a demand soft-deletes its items', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $post = Post::factory()->for($dealer)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Archived,
        ]);
        $item = PostItem::factory()->for($post)->for($variety1)->create();

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertRedirect();

        expect(PostItem::find($item->id))->toBeNull()
            ->and(PostItem::withTrashed()->find($item->id))->not->toBeNull();
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

    it('unauthenticated user is redirected from supply routes', function () {
        $this->post(route('farmer.supplies.store'), [])->assertRedirect(route('login'));
        $this->post(route('dealer.demands.store'), [])->assertRedirect(route('login'));
    });

});
