<?php

use App\Models\Product\Category;
use App\Models\Product\PriceHistory;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

// ─── Setup ────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::where('name', 'admin')->first());

    $this->farmer = User::factory()->create();
    $this->farmer->roles()->attach(Role::where('name', 'farmer')->first());

    $this->category = Category::create(['name' => 'Leafy Greens']);

    $this->vegetable = Vegetable::create([
        'category_id' => $this->category->id,
        'name' => 'Pechay',
    ]);

    $this->variety = Variety::create([
        'vegetable_id' => $this->vegetable->id,
        'name' => 'White Pechay',
    ]);

    $this->variety->prices()->create([
        'price_min' => 20.00,
        'price_max' => 30.00,
        'recorded_at' => now()->startOfWeek(),
    ]);
});

// ─── Category ─────────────────────────────────────────────────────────────────

describe('category', function () {
    it('renders the category page for an admin', function () {
        actingAs($this->admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/vegetables/Categories'));
    });
});

// ─── Index ────────────────────────────────────────────────────────────────────

describe('index', function () {
    it('renders the index page for an admin when a category slug is provided', function () {
        actingAs($this->admin)
            ->get(route('admin.vegetables.index', ['category' => $this->category->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/vegetables/Index'));
    });

    it('redirects to categories when no category filter is provided', function () {
        actingAs($this->admin)
            ->get(route('admin.vegetables.index'))
            ->assertRedirect(route('admin.categories.index'));
    });

    it('redirects guests to login', function () {
        get(route('admin.vegetables.index'))
            ->assertRedirect(route('login'));
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->get(route('admin.vegetables.index'))
            ->assertForbidden();
    });
});

// ─── Show ─────────────────────────────────────────────────────────────────────

describe('show', function () {
    it('renders the show page for an admin', function () {
        actingAs($this->admin)
            ->get(route('admin.vegetables.varieties.show', $this->variety))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/vegetables/Show'));
    });

    it('redirects guests to login', function () {
        get(route('admin.vegetables.varieties.show', $this->variety))
            ->assertRedirect(route('login'));
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->get(route('admin.vegetables.varieties.show', $this->variety))
            ->assertForbidden();
    });
});

// ─── Vegetable Store ──────────────────────────────────────────────────────────

describe('vegetable store', function () {
    it('creates a vegetable without an image', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'category_id' => $this->category->id,
                'name' => 'Kangkong',
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('vegetables', ['name' => 'Kangkong']);
    });

    it('creates a vegetable with an image', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'category_id' => $this->category->id,
                'name' => 'Kangkong',
                'image' => UploadedFile::fake()->image('kangkong.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        $vegetable = Vegetable::where('name', 'Kangkong')->firstOrFail();
        expect($vegetable->getFirstMedia('vegetable_image'))->not->toBeNull();
    });

    it('rejects a non-image file', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'category_id' => $this->category->id,
                'name' => 'Kangkong',
                'image' => UploadedFile::fake()->create('doc.pdf', 512, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');
    });

    it('rejects a duplicate name', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'category_id' => $this->category->id,
                'name' => 'Pechay', // already exists in beforeEach
            ])
            ->assertSessionHasErrors('name');
    });

    it('rejects missing category_id', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), ['name' => 'Kangkong'])
            ->assertSessionHasErrors('category_id');
    });

    it('rejects missing name', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), ['category_id' => $this->category->id])
            ->assertSessionHasErrors('name');
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->post(route('admin.vegetables.store'), [
                'category_id' => $this->category->id,
                'name' => 'Kangkong',
            ])
            ->assertForbidden();
    });

    it('redirects guests to login', function () {
        post(route('admin.vegetables.store'), [])->assertRedirect(route('login'));
    });
});

// ─── Vegetable Update ─────────────────────────────────────────────────────────

describe('vegetable update', function () {
    it('updates a vegetable name without an image', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
                'name' => 'Updated Pechay',
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        expect($this->vegetable->fresh()->name)->toBe('Updated Pechay');
    });

    it('updates a vegetable with a new image', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
                'name' => 'Pechay',
                'image' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        expect($this->vegetable->fresh()->getFirstMedia('vegetable_image'))->not->toBeNull();
    });

    it('rejects a non-image file on update', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
                'name' => 'Pechay',
                'image' => UploadedFile::fake()->create('doc.pdf', 512, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');
    });

    it('rejects missing name', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
            ])
            ->assertSessionHasErrors('name');
    });

    it('allows saving with the same name (ignore self in unique check)', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
                'name' => 'Pechay',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    });

    it('returns 403 for non-admin users and leaves the record intact', function () {
        actingAs($this->farmer)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
                'name' => 'Sneaky Update',
            ])
            ->assertForbidden();

        expect($this->vegetable->fresh()->name)->toBe('Pechay');
    });

    it('redirects guests to login', function () {
        put(route('admin.vegetables.update', $this->vegetable), [])
            ->assertRedirect(route('login'));
    });
});

// ─── Vegetable Destroy ────────────────────────────────────────────────────────

describe('vegetable destroy', function () {
    it('deletes a vegetable with no varieties', function () {
        $empty = Vegetable::create(['category_id' => $this->category->id, 'name' => 'Empty Veg']);

        actingAs($this->admin)
            ->delete(route('admin.vegetables.destroy', $empty))
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        expect(Vegetable::find($empty->id))->toBeNull();
    });

    it('returns 403 for non-admin users and leaves the record intact', function () {
        actingAs($this->farmer)
            ->delete(route('admin.vegetables.destroy', $this->vegetable))
            ->assertForbidden();

        expect(Vegetable::find($this->vegetable->id))->not->toBeNull();
    });

    it('redirects guests to login', function () {
        delete(route('admin.vegetables.destroy', $this->vegetable))
            ->assertRedirect(route('login'));
    });
});

// ─── Variety Store ────────────────────────────────────────────────────────────

describe('variety store', function () {
    it('creates a variety and seeds its first price history', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        $created = Variety::where('name', 'Green Pechay')->firstOrFail();

        assertDatabaseHas('price_histories', [
            'variety_id' => $created->id,
            'price_min' => '15.00',
            'price_max' => '25.00',
        ]);
    });

    it('rejects missing vegetable_id', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.store'), [
                'name' => 'Green Pechay',
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertSessionHasErrors('vegetable_id');
    });

    it('rejects a vegetable_id that does not exist', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.store'), [
                'vegetable_id' => 9999,
                'name' => 'Green Pechay',
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertSessionHasErrors('vegetable_id');
    });

    it('rejects missing name', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.store'), [
                'vegetable_id' => $this->vegetable->id,
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertSessionHasErrors('name');
    });

    it('rejects price_max below price_min', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'price_min' => '50.00',
                'price_max' => '10.00',
            ])
            ->assertSessionHasErrors('price_max');
    });

    it('rejects prices that exceed the maximum allowed value', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'price_min' => '10000.00',
                'price_max' => '10001.00',
            ])
            ->assertSessionHasErrors(['price_min', 'price_max']);
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->post(route('admin.vegetables.varieties.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertForbidden();
    });

    it('redirects guests to login', function () {
        post(route('admin.vegetables.varieties.store'), [])
            ->assertRedirect(route('login'));
    });
});

// ─── Variety Update ───────────────────────────────────────────────────────────

describe('variety update', function () {
    it('updates the variety name', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.varieties.update', $this->variety), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Updated Pechay',
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        expect($this->variety->fresh()->name)->toBe('Updated Pechay');
    });

    it('rejects missing vegetable_id', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.varieties.update', $this->variety), [
                'name' => 'Updated Pechay',
            ])
            ->assertSessionHasErrors('vegetable_id');
    });

    it('rejects missing name', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.varieties.update', $this->variety), [
                'vegetable_id' => $this->vegetable->id,
            ])
            ->assertSessionHasErrors('name');
    });

    it('returns 403 for non-admin users and leaves the record intact', function () {
        actingAs($this->farmer)
            ->put(route('admin.vegetables.varieties.update', $this->variety), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Sneaky Update',
            ])
            ->assertForbidden();

        expect($this->variety->fresh()->name)->toBe('White Pechay');
    });

    it('redirects guests to login', function () {
        put(route('admin.vegetables.varieties.update', $this->variety), [])
            ->assertRedirect(route('login'));
    });
});

// ─── Variety Destroy ──────────────────────────────────────────────────────────

describe('variety destroy', function () {
    it('deletes the variety', function () {
        actingAs($this->admin)
            ->delete(route('admin.vegetables.varieties.destroy', $this->variety))
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        expect(Variety::find($this->variety->id))->toBeNull();
    });

    it('returns 403 for non-admin users and leaves the record intact', function () {
        actingAs($this->farmer)
            ->delete(route('admin.vegetables.varieties.destroy', $this->variety))
            ->assertForbidden();

        expect(Variety::find($this->variety->id))->not->toBeNull();
    });

    it('redirects guests to login', function () {
        delete(route('admin.vegetables.varieties.destroy', $this->variety))
            ->assertRedirect(route('login'));
    });
});

// ─── Price Store ──────────────────────────────────────────────────────────────

describe('price store', function () {
    it('creates a price for the current week', function () {
        PriceHistory::where('variety_id', $this->variety->id)->delete();

        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.prices.store', $this->variety), [
                'price_min' => '25.00',
                'price_max' => '40.00',
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('price_histories', [
            'variety_id' => $this->variety->id,
            'price_min' => '25.00',
            'price_max' => '40.00',
        ]);
    });

    it('updates the existing record instead of creating a duplicate for the same week', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.prices.store', $this->variety), [
                'price_min' => '18.00',
                'price_max' => '28.00',
            ]);

        assertDatabaseCount('price_histories', 1);

        assertDatabaseHas('price_histories', [
            'variety_id' => $this->variety->id,
            'price_min' => '18.00',
            'price_max' => '28.00',
        ]);
    });

    it('rejects missing price_min', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.prices.store', $this->variety), [
                'price_max' => '40.00',
            ])
            ->assertSessionHasErrors('price_min');
    });

    it('rejects missing price_max', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.prices.store', $this->variety), [
                'price_min' => '20.00',
            ])
            ->assertSessionHasErrors('price_max');
    });

    it('rejects price_max below price_min', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.prices.store', $this->variety), [
                'price_min' => '50.00',
                'price_max' => '10.00',
            ])
            ->assertSessionHasErrors('price_max');
    });

    it('rejects negative prices', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.varieties.prices.store', $this->variety), [
                'price_min' => '-1.00',
                'price_max' => '10.00',
            ])
            ->assertSessionHasErrors('price_min');
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->post(route('admin.vegetables.varieties.prices.store', $this->variety), [
                'price_min' => '20.00',
                'price_max' => '30.00',
            ])
            ->assertForbidden();
    });

    it('redirects guests to login', function () {
        post(route('admin.vegetables.varieties.prices.store', $this->variety), [])
            ->assertRedirect(route('login'));
    });
});
