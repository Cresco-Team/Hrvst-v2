<?php

use App\Enums\PostItemStatus;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Interaction\PostHeart;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function vegetableWithVariety(): array
{
    $category = Category::firstOrCreate(['name' => 'Leafy Greens']);
    $vegetable = Vegetable::firstOrCreate(['category_id' => $category->id, 'name' => 'Pechay']);
    $variety = Variety::create([
        'vegetable_id' => $vegetable->id,
        'name' => 'Variety '.uniqid(),
        'hearts_count' => 0,
    ]);

    return [$vegetable, $variety];
}

function statsRow(int $vegetableId, string $periodDate): ?object
{
    return DB::table('vegetable_monthly_stats')
        ->where('vegetable_id', $vegetableId)
        ->where('period_date', $periodDate)
        ->first();
}

// ─── PostItemObserver ─────────────────────────────────────────────────────────

describe('PostItemObserver', function () {

    it('increments supply_fulfilled_kg when a supply item is fulfilled', function () {
        [$vegetable, $variety] = vegetableWithVariety();
        $user = User::factory()->create();

        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);
        $item = PostItem::factory()->for($post)->for($variety)->create([
            'quantity_kg' => 150,
            'status' => PostItemStatus::Ongoing,
        ]);

        $item->markAsFulfilled();

        $periodDate = $post->created_at->startOfMonth()->toDateString();
        $row = statsRow($vegetable->id, $periodDate);

        expect($row)->not->toBeNull()
            ->and((float) $row->supply_fulfilled_kg)->toBe(150.0);
    });

    it('increments supply_unsettled_kg when a supply item is unsettled', function () {
        [$vegetable, $variety] = vegetableWithVariety();
        $user = User::factory()->create();

        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);
        $item = PostItem::factory()->for($post)->for($variety)->create([
            'quantity_kg' => 75,
            'status' => PostItemStatus::Ongoing,
        ]);

        $item->markAsUnsettled();

        $periodDate = $post->created_at->startOfMonth()->toDateString();
        $row = statsRow($vegetable->id, $periodDate);

        expect((float) $row->supply_unsettled_kg)->toBe(75.0);
    });

    it('increments demand_fulfilled_kg when a demand item is fulfilled', function () {
        [$vegetable, $variety] = vegetableWithVariety();
        $user = User::factory()->create();

        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Demand,
            'status' => PostStatus::Harvested,
        ]);
        $item = PostItem::factory()->for($post)->for($variety)->create([
            'quantity_kg' => 200,
            'status' => PostItemStatus::Ongoing,
        ]);

        $item->markAsFulfilled();

        $periodDate = $post->created_at->startOfMonth()->toDateString();
        $row = statsRow($vegetable->id, $periodDate);

        expect((float) $row->demand_fulfilled_kg)->toBe(200.0);
    });

    it('does not create a stats row for items that stay ongoing', function () {
        [$vegetable, $variety] = vegetableWithVariety();
        $user = User::factory()->create();

        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);

        PostItem::factory()->for($post)->for($variety)->create([
            'quantity_kg' => 75,
            'status' => PostItemStatus::Ongoing,
        ]);

        $periodDate = $post->created_at->startOfMonth()->toDateString();

        expect(statsRow($vegetable->id, $periodDate))->toBeNull();
    });

    it('decrements stat when a fulfilled supply item is deleted', function () {
        [$vegetable, $variety] = vegetableWithVariety();
        $user = User::factory()->create();

        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);
        $item = PostItem::factory()->for($post)->for($variety)->create([
            'quantity_kg' => 100,
            'status' => PostItemStatus::Fulfilled,
        ]);

        $periodDate = $post->created_at->startOfMonth()->toDateString();

        DB::table('vegetable_monthly_stats')->upsert([[
            'vegetable_id' => $vegetable->id,
            'period_date' => $periodDate,
            'supply_unsettled_kg' => 0,
            'supply_fulfilled_kg' => 100,
            'demand_unsettled_kg' => 0,
            'demand_fulfilled_kg' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['vegetable_id', 'period_date'], ['updated_at']);

        $item->delete();

        $row = statsRow($vegetable->id, $periodDate);
        expect((float) $row->supply_fulfilled_kg)->toBe(0.0);
    });

    it('accumulates across multiple items in the same period', function () {
        [$vegetable, $variety] = vegetableWithVariety();
        $user = User::factory()->create();

        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);

        $item1 = PostItem::factory()->for($post)->for($variety)->create([
            'quantity_kg' => 100,
            'status' => PostItemStatus::Ongoing,
        ]);
        $item2 = PostItem::factory()->for($post)->for($variety)->create([
            'quantity_kg' => 50,
            'status' => PostItemStatus::Ongoing,
        ]);

        $item1->markAsFulfilled();
        $item2->markAsFulfilled();

        $periodDate = $post->created_at->startOfMonth()->toDateString();
        $row = statsRow($vegetable->id, $periodDate);

        expect((float) $row->supply_fulfilled_kg)->toBe(150.0);
    });

});

// ─── PostHeart Toggle ─────────────────────────────────────────────────────────

describe('PostHeartToggle', function () {

    it('user can heart a post', function () {
        $user = User::factory()->create();
        [$vegetable] = vegetableWithVariety();
        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);

        actingAs($user)
            ->postJson(route('posts.heart.toggle', $post))
            ->assertOk()
            ->assertJson(['hearted' => true, 'hearts_count' => 1]);

        expect(PostHeart::where('user_id', $user->id)->where('post_id', $post->id)->exists())->toBeTrue()
            ->and($post->fresh()->hearts_count)->toBe(1);
    });

    it('user can un-heart a post', function () {
        $user = User::factory()->create();
        [$vegetable] = vegetableWithVariety();

        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);
        $post->forceFill(['hearts_count' => 1])->save();

        PostHeart::create(['user_id' => $user->id, 'post_id' => $post->id]);

        actingAs($user)
            ->postJson(route('posts.heart.toggle', $post))
            ->assertOk()
            ->assertJson(['hearted' => false, 'hearts_count' => 0]);

        expect(PostHeart::where('user_id', $user->id)->where('post_id', $post->id)->exists())->toBeFalse()
            ->and($post->fresh()->hearts_count)->toBe(0);
    });

    it('heart count never goes below zero', function () {
        $user = User::factory()->create();
        [$vegetable] = vegetableWithVariety();

        // hearts_count defaults to 0 — no forceFill needed
        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);
        PostHeart::create(['user_id' => $user->id, 'post_id' => $post->id]);

        // Force hearts_count to 0 via DB to simulate desync without touching $fillable
        DB::table('posts')->where('id', $post->id)->update(['hearts_count' => 0]);

        actingAs($user)
            ->postJson(route('posts.heart.toggle', $post))
            ->assertOk();

        expect($post->fresh()->hearts_count)->toBeGreaterThanOrEqual(0);
    });

    it('unauthenticated user cannot toggle heart', function () {
        [$vegetable] = vegetableWithVariety();
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);

        $this->postJson(route('posts.heart.toggle', $post))
            ->assertUnauthorized();
    });

    it('toggling is idempotent under sequential requests', function () {
        $user = User::factory()->create();
        [$vegetable] = vegetableWithVariety();
        $post = Post::factory()->for($user)->for($vegetable)->create([
            'type' => PostType::Supply,
            'status' => PostStatus::Harvested,
        ]);

        $this->actingAs($user)->postJson(route('posts.heart.toggle', $post));
        $this->actingAs($user)->postJson(route('posts.heart.toggle', $post));

        expect($post->fresh()->hearts_count)->toBe(0)
            ->and(PostHeart::where('post_id', $post->id)->count())->toBe(0);
    });

});
