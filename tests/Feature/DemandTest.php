<?php

use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function dealerWithProfile(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_pin' => false,
    ]);
    $user->roles()->attach(Role::firstOrCreate(['name' => 'dealer']));
    DealerProfile::create(['user_id' => $user->id]);

    return $user;
}

function demandVegetableAndVarieties(): array
{
    $category = Category::create(['name' => 'Cat '.uniqid()]);
    $vegetable = Vegetable::create(['category_id' => $category->id, 'name' => 'Veg '.uniqid()]);
    $variety1 = Variety::create(['vegetable_id' => $vegetable->id, 'name' => 'Var A '.uniqid()]);
    $variety2 = Variety::create(['vegetable_id' => $vegetable->id, 'name' => 'Var B '.uniqid()]);

    return [$vegetable, $variety1, $variety2];
}

function validDemandPayload(Vegetable $vegetable, Variety $variety1, Variety $variety2, ?string $scheduledDate = null): array
{
    return [
        'vegetable_id' => $vegetable->id,
        'scheduled_date' => $scheduledDate ?? now()->addDays(5)->toDateString(),
        'time_slot' => 'morning',
        'items' => [
            ['variety_id' => $variety1->id, 'quantity_kg' => 100],
            ['variety_id' => $variety2->id, 'quantity_kg' => 50],
        ],
    ];
}

/**
 * Creates a demand post + one item through the route so user_id is set by the
 * auth system — guarantees the policy ownership check passes in lifecycle tests.
 * Returns the created PostItem.
 */
function createDemandViaRoute(User $dealer, Variety $variety): PostItem
{
    actingAs($dealer)->post(route('dealer.demands.store'), [
        'vegetable_id' => $variety->vegetable_id,
        'scheduled_date' => now()->addDays(5)->toDateString(),
        'time_slot' => 'morning',
        'items' => [
            ['variety_id' => $variety->id, 'quantity_kg' => 50],
        ],
    ]);

    return PostItem::latest('id')->firstOrFail();
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
            ->and($post->type)->toBe(PostType::Demand)
            ->and($post->vegetable_id)->toBe($vegetable->id)
            ->and($post->time_slot->value)->toBe('morning')
            ->and($post->postItems)->toHaveCount(2);

        $item1 = $post->postItems->firstWhere('variety_id', $variety1->id);
        expect((float) $item1->quantity_kg)->toBe(100.0);

        $item2 = $post->postItems->firstWhere('variety_id', $variety2->id);
        expect((float) $item2->quantity_kg)->toBe(50.0);
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
                    ['variety_id' => 99999, 'quantity_kg' => 10],
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
                    ['variety_id' => $variety1->id, 'quantity_kg' => 0],
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
        $user->roles()->attach(Role::firstOrCreate(['name' => 'dealer']));
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
        $item = createDemandViaRoute($dealer, $variety1);
        $post = $item->post;

        $newDate = now()->addDays(10)->toDateString();

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), ['scheduled_date' => $newDate])
            ->assertRedirect(route('dealer.demands.index'));

        expect($post->fresh()->scheduled_date->toDateString())->toBe($newDate);
    });

    it('updating items replaces all existing ongoing items', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1, $variety2] = demandVegetableAndVarieties();
        $item = createDemandViaRoute($dealer, $variety1);
        $post = $item->post;

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), [
                'items' => [
                    ['variety_id' => $variety2->id, 'quantity_kg' => 200],
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
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $item = createDemandViaRoute($other, $variety1);
        $post = $item->post;

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), ['scheduled_date' => now()->addDays(2)->toDateString()])
            ->assertForbidden();
    });

});

// ─── Demand Item Lifecycle ────────────────────────────────────────────────────

describe('DemandLifecycle', function () {

    it('dealer can archive a fulfilled demand item', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $item = createDemandViaRoute($dealer, $variety1);

        // Bypass Eloquent to set status without triggering observers
        DB::table('post_items')->where('id', $item->id)->update(['status' => 'fulfilled']);

        actingAs($dealer)
            ->post(route('dealer.post-items.archive', $item))
            ->assertRedirect();

        expect($item->fresh()->status)->toBe(PostItemStatus::Unsettled);
    });

    it('dealer can fulfill an unsettled demand item', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $item = createDemandViaRoute($dealer, $variety1);

        DB::table('post_items')->where('id', $item->id)->update(['status' => 'unsettled']);

        actingAs($dealer)
            ->post(route('dealer.post-items.fulfill', $item))
            ->assertRedirect();

        expect($item->fresh()->status)->toBe(PostItemStatus::Fulfilled);
    });

    it('dealer cannot delete a demand with ongoing items', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $item = createDemandViaRoute($dealer, $variety1);
        $post = $item->post;

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertForbidden();
    });

    it('dealer can delete a demand with no ongoing items', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $item = createDemandViaRoute($dealer, $variety1);
        $post = $item->post;

        DB::table('post_items')->where('id', $item->id)->update(['status' => 'unsettled']);

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull();
    });

    it('deleting a demand soft-deletes the post record', function () {
        $dealer = dealerWithProfile();
        [$vegetable, $variety1] = demandVegetableAndVarieties();
        $item = createDemandViaRoute($dealer, $variety1);
        $post = $item->post;

        DB::table('post_items')->where('id', $item->id)->update(['status' => 'fulfilled']);

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
        $farmer->roles()->attach(Role::firstOrCreate(['name' => 'farmer']));

        actingAs($farmer)
            ->post(route('dealer.demands.store'), [])
            ->assertForbidden();
    });

    it('unauthenticated user is redirected from supply and demand routes', function () {
        $this->post(route('farmer.supplies.store'), [])->assertRedirect(route('login'));
        $this->post(route('dealer.demands.store'), [])->assertRedirect(route('login'));
    });

});
