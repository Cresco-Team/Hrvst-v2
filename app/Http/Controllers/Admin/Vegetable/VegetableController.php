<?php

namespace App\Http\Controllers\Admin\Vegetable;

use App\Actions\Product\CreateVegetableAction;
use App\Actions\Product\UpdateVegetableAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vegetable\StoreVegetableRequest;
use App\Http\Requests\Vegetable\UpdateVegetableRequest;
use App\Http\Resources\Product\VegetableResource;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Services\Product\VegetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VegetableController extends Controller
{
    public function __construct(
        private VegetableService $vegetableService,
    ) {}

    public function category(): Response
    {
        $categories = Category::query()
            ->withCount('vegetables')
            ->addSelect([
                'varieties_count' => Variety::query()
                    ->join('vegetables', 'varieties.vegetable_id', '=', 'vegetables.id')
                    ->whereColumn('vegetables.category_id', 'categories.id')
                    ->selectRaw('COUNT(*)'),
            ])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/vegetables/Categories', [
            'categories' => $categories,
        ]);
    }

    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->filled('category')) {
            return redirect()->route('admin.categories.index');
        }

        $slug = $request->query('category');
        $category = Category::where('slug', $slug)->first();

        return Inertia::render('admin/vegetables/Index', [
            'vegetables' => Inertia::defer(
                function () use ($request, $category) {
                    $query = $this->vegetableService->paginated(
                        search: $request->query('search', null),
                        priceFilter: $request->query('price_filter', null),
                        categoryId: $category?->id,
                    )->paginate(12)
                        ->withQueryString();

                    return VegetableResource::collection($query);
                }
            ),
            'summary' => Inertia::defer(fn () => $this->vegetableService->summary()),
            'filters' => [
                'price_filter' => $request->query('price_filter', null),
                'search' => $request->query('search', null),
                'category' => $request->query('category', null),
            ],
            'category' => $category,
        ]);
    }

    public function store(StoreVegetableRequest $request, CreateVegetableAction $createVegetable): RedirectResponse
    {
        Gate::authorize('create', Vegetable::class);

        $createVegetable->handle(
            validated: $request->safe()->except('image'),
            image: $request->file('image')
        );

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable created successfully.']);
    }

    public function update(UpdateVegetableRequest $request, Vegetable $vegetable, UpdateVegetableAction $updateVegetable): RedirectResponse
    {
        Gate::authorize('update', $vegetable);

        $updateVegetable->handle(
            vegetable: $vegetable,
            validated: $request->safe()->except('image'),
            image: $request->file('image')
        );

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable updated successfully.']);
    }

    public function destroy(Vegetable $vegetable): RedirectResponse
    {
        Gate::authorize('delete', $vegetable);

        $vegetable->delete();

        return redirect()->back()
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable deleted successfully.']);
    }
}
