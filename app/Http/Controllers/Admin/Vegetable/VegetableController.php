<?php

namespace App\Http\Controllers\Admin\Vegetable;

use App\Enums\Analytics\VarietyViewerRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vegetable\StoreVegetableRequest;
use App\Http\Requests\Vegetable\UpdateVegetableRequest;
use App\Http\Resources\Product\VarietyDetailResource;
use App\Http\Resources\Product\VegetableResource;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Services\Product\VarietyService;
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
        private VarietyService $varietyService,
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

    public function index(Request $request): Response
    {
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
            ],
            'category' => $category,
            'vegetableOptions' => Inertia::defer(fn () => $this->varietyService->vegetableOptions()),
        ]);
    }

    public function show(Request $request, Variety $variety): Response
    {
        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2035'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        $variety->loadMissing(['vegetable.category']);

        return Inertia::render('admin/vegetables/Show', [
            'variety' => Inertia::defer(
                fn () => (new VarietyDetailResource(
                    $this->varietyService->show($variety, $year, $month, VarietyViewerRole::Admin)
                ))->resolve()
            ),
            'calendarFilters' => [
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }

    public function store(StoreVegetableRequest $request): RedirectResponse
    {
        Gate::authorize('create', Vegetable::class);

        Vegetable::create($request->validated());

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable created successfully.']);
    }

    public function update(UpdateVegetableRequest $request, Vegetable $vegetable): RedirectResponse
    {
        Gate::authorize('update', $vegetable);

        $vegetable->update($request->validated());

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable updated successfully.']);
    }

    public function destroy(Vegetable $vegetable): RedirectResponse
    {
        Gate::authorize('delete', $vegetable);

        $vegetable->delete();

        return redirect()->route('admin.vegetables.index')
            ->with('flash', ['type' => 'success', 'message' => 'Vegetable deleted successfully.']);
    }
}
