<?php

namespace App\Http\Controllers\Admin\Vegetable;

use App\Actions\Product\CreateVegetableAction;
use App\Actions\Product\UpdateVegetableAction;
use App\Data\Vegetable\VegetableIndexData;
use App\Http\Controllers\Concerns\RendersVegetableShow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vegetable\StoreVegetableRequest;
use App\Http\Requests\Vegetable\UpdateVegetableRequest;
use App\Models\Vegetable\Category;
use App\Models\Vegetable\Vegetable;
use App\Services\Product\VegetableDetailService;
use App\Services\Product\VegetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VegetableController extends Controller
{
    use RendersVegetableShow;

    public function __construct(
        private VegetableService $vegetableService,
        private VegetableDetailService $vegetableDetailService,
    ) {}

    public function category(): Response
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('admin/vegetables/Categories', ['categories' => $categories]);
    }

    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->filled('category')) {
            return redirect()->route('admin.categories.index');
        }

        $slug = $request->query('category');
        $category = Category::where('slug', $slug)->firstOrFail();

        return Inertia::render('admin/vegetables/Index', [
            'vegetables' => Inertia::defer(fn () => VegetableIndexData::collect(
                $this->vegetableService->paginated(
                    search: $request->query('search'),
                    categoryId: $category->id,
                )->paginate(20)->withQueryString(),
            )),
            'filters' => ['search' => $request->query('search', null)],
            'category' => $category,
        ]);
    }

    public function show(Request $request, Vegetable $vegetable): Response
    {
        return $this->renderVegetableShow($request, $vegetable, $this->vegetableDetailService);
    }

    public function store(StoreVegetableRequest $request, CreateVegetableAction $createVegetable): RedirectResponse
    {
        Gate::authorize('create', Vegetable::class);

        $createVegetable->handle(
            validated: $request->safe()->except('image'),
            image: $request->file('image'),
        );

        return redirect()->back()->with('flash', ['type' => 'success', 'message' => 'Vegetable created successfully.']);
    }

    public function update(UpdateVegetableRequest $request, Vegetable $vegetable, UpdateVegetableAction $updateVegetable): RedirectResponse
    {
        Gate::authorize('update', $vegetable);

        $updateVegetable->handle(
            vegetable: $vegetable,
            validated: $request->safe()->except('image'),
            image: $request->file('image'),
        );

        return redirect()->back()->with('flash', ['type' => 'success', 'message' => 'Vegetable updated successfully.']);
    }

    public function destroy(Vegetable $vegetable): RedirectResponse
    {
        Gate::authorize('delete', $vegetable);
        $vegetable->delete();

        return redirect()->back()->with('flash', ['type' => 'success', 'message' => 'Vegetable deleted successfully.']);
    }
}
