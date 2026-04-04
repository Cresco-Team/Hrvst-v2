<?php

use App\Models\Product\Category;
use App\Models\Product\PriceHistory;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

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

    // Seed a price so VarietyResource and show() queries have data to work with
    $this->variety->prices()->create([
        'price_min' => 20.00,
        'price_max' => 30.00,
        'recorded_at' => now()->startOfWeek(),
    ]);
});

// ─── Index ────────────────────────────────────────────────────────────────────

describe('index', function () {
    it('renders the index page for an admin', function () {
        actingAs($this->admin)
            ->get(route('admin.vegetables.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/vegetables/Index'));
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
            ->get(route('admin.vegetables.show', $this->variety))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/vegetables/Show'));
    });

    it('redirects guests to login', function () {
        get(route('admin.vegetables.show', $this->variety))
            ->assertRedirect(route('login'));
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->get(route('admin.vegetables.show', $this->variety))
            ->assertForbidden();
    });
});

// ─── Details (JSON) ───────────────────────────────────────────────────────────

describe('details', function () {
    it('returns a json response for an admin', function () {
        actingAs($this->admin)
            ->get(route('admin.vegetables.details', $this->variety))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->variety->id,
                'name' => $this->variety->name,
            ]);
    });

    it('redirects guests to login', function () {
        get(route('admin.vegetables.details', $this->variety))
            ->assertRedirect(route('login'));
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->get(route('admin.vegetables.details', $this->variety))
            ->assertForbidden();
    });
});

// ─── Store ────────────────────────────────────────────────────────────────────

describe('store', function () {
    it('creates a variety and seeds its first price history', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'image' => UploadedFile::fake()->image('pechay.jpg'),
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertRedirect(route('admin.vegetables.index'))
            ->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('varieties', [
            'vegetable_id' => $this->vegetable->id,
            'name' => 'Green Pechay',
        ]);

        $created = Variety::where('name', 'Green Pechay')->firstOrFail();

        assertDatabaseHas('price_histories', [
            'variety_id' => $created->id,
            'price_min' => '15.00',
            'price_max' => '25.00',
        ]);
    });

    it('rejects missing vegetable_id', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'name' => 'Green Pechay',
                'image' => UploadedFile::fake()->image('pechay.jpg'),
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertSessionHasErrors('vegetable_id');
    });

    it('rejects a vegetable_id that does not exist', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'vegetable_id' => 9999,
                'name' => 'Green Pechay',
                'image' => UploadedFile::fake()->image('pechay.jpg'),
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertSessionHasErrors('vegetable_id');
    });

    it('rejects missing name', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'vegetable_id' => $this->vegetable->id,
                'image' => UploadedFile::fake()->image('pechay.jpg'),
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertSessionHasErrors('name');
    });

    it('rejects missing image', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertSessionHasErrors('image');
    });

    it('rejects a non-image file upload', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'image' => UploadedFile::fake()->create('document.pdf', 512, 'application/pdf'),
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertSessionHasErrors('image');
    });

    it('rejects price_max below price_min', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'image' => UploadedFile::fake()->image('pechay.jpg'),
                'price_min' => '50.00',
                'price_max' => '10.00',
            ])
            ->assertSessionHasErrors('price_max');
    });

    it('rejects prices that exceed the maximum allowed value', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'image' => UploadedFile::fake()->image('pechay.jpg'),
                'price_min' => '10000.00',
                'price_max' => '10001.00',
            ])
            ->assertSessionHasErrors(['price_min', 'price_max']);
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->post(route('admin.vegetables.store'), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Green Pechay',
                'image' => UploadedFile::fake()->image('pechay.jpg'),
                'price_min' => '15.00',
                'price_max' => '25.00',
            ])
            ->assertForbidden();
    });

    it('redirects guests to login', function () {
        post(route('admin.vegetables.store'), [])
            ->assertRedirect(route('login'));
    });
});

// ─── Update ───────────────────────────────────────────────────────────────────

describe('update', function () {
    it('updates the variety name without an image', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->variety), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Updated Pechay',
            ])
            ->assertRedirect(route('admin.vegetables.index'))
            ->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('varieties', [
            'id' => $this->variety->id,
            'name' => 'Updated Pechay',
        ]);
    });

    it('updates the variety with a new image', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->variety), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Updated With Image',
                'image' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertRedirect(route('admin.vegetables.index'));

        assertDatabaseHas('varieties', [
            'id' => $this->variety->id,
            'name' => 'Updated With Image',
        ]);
    });

    it('allows image to be omitted on update', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->variety), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'No Image Update',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.vegetables.index'));
    });

    it('rejects missing vegetable_id', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->variety), [
                'name' => 'Updated Pechay',
            ])
            ->assertSessionHasErrors('vegetable_id');
    });

    it('rejects missing name', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->variety), [
                'vegetable_id' => $this->vegetable->id,
            ])
            ->assertSessionHasErrors('name');
    });

    it('rejects a non-image file on update', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->variety), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Updated Pechay',
                'image' => UploadedFile::fake()->create('document.pdf', 512, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->put(route('admin.vegetables.update', $this->variety), [
                'vegetable_id' => $this->vegetable->id,
                'name' => 'Sneaky Update',
            ])
            ->assertForbidden();

        assertDatabaseHas('varieties', [
            'id' => $this->variety->id,
            'name' => 'White Pechay',
        ]);
    });

    it('redirects guests to login', function () {
        put(route('admin.vegetables.update', $this->variety), [])
            ->assertRedirect(route('login'));
    });
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

describe('destroy', function () {
    it('deletes the variety', function () {
        actingAs($this->admin)
            ->delete(route('admin.vegetables.destroy', $this->variety))
            ->assertRedirect(route('admin.vegetables.index'))
            ->assertSessionHas('flash.type', 'success');

        assertDatabaseMissing('varieties', ['id' => $this->variety->id]);
    });

    it('returns 403 for non-admin users and leaves the record intact', function () {
        actingAs($this->farmer)
            ->delete(route('admin.vegetables.destroy', $this->variety))
            ->assertForbidden();

        assertDatabaseHas('varieties', ['id' => $this->variety->id]);
    });

    it('redirects guests to login', function () {
        delete(route('admin.vegetables.destroy', $this->variety))
            ->assertRedirect(route('login'));
    });
});

// ─── Price Store ──────────────────────────────────────────────────────────────

describe('price store', function () {
    it('creates a price for the current week', function () {
        // Delete the seeded price so this test starts fresh
        PriceHistory::where('variety_id', $this->variety->id)->delete();

        actingAs($this->admin)
            ->post(route('admin.vegetables.prices.store', $this->variety), [
                'price_min' => '25.00',
                'price_max' => '40.00',
            ])
            ->assertRedirect(route('admin.vegetables.index'))
            ->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('price_histories', [
            'variety_id' => $this->variety->id,
            'price_min' => '25.00',
            'price_max' => '40.00',
        ]);
    });

    it('updates the existing record instead of creating a duplicate for the same week', function () {
        // The beforeEach already seeded one record for the current week
        actingAs($this->admin)
            ->post(route('admin.vegetables.prices.store', $this->variety), [
                'price_min' => '18.00',
                'price_max' => '28.00',
            ]);

        // updateOrCreate must keep exactly one row for this week
        assertDatabaseCount('price_histories', 1);

        assertDatabaseHas('price_histories', [
            'variety_id' => $this->variety->id,
            'price_min' => '18.00',
            'price_max' => '28.00',
        ]);
    });

    it('rejects missing price_min', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.prices.store', $this->variety), [
                'price_max' => '40.00',
            ])
            ->assertSessionHasErrors('price_min');
    });

    it('rejects missing price_max', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.prices.store', $this->variety), [
                'price_min' => '20.00',
            ])
            ->assertSessionHasErrors('price_max');
    });

    it('rejects price_max below price_min', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.prices.store', $this->variety), [
                'price_min' => '50.00',
                'price_max' => '10.00',
            ])
            ->assertSessionHasErrors('price_max');
    });

    it('rejects negative prices', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.prices.store', $this->variety), [
                'price_min' => '-1.00',
                'price_max' => '10.00',
            ])
            ->assertSessionHasErrors('price_min');
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->post(route('admin.vegetables.prices.store', $this->variety), [
                'price_min' => '20.00',
                'price_max' => '30.00',
            ])
            ->assertForbidden();
    });

    it('redirects guests to login', function () {
        post(route('admin.vegetables.prices.store', $this->variety), [])
            ->assertRedirect(route('login'));
    });
});
