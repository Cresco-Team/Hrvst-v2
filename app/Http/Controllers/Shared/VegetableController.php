<?php

namespace App\Http\Controllers\Shared;

use App\Enums\Analytics\VarietyViewerRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\VarietyDetailResource;
use App\Http\Resources\Product\VegetableSharedResource;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Services\Product\VarietyService;
use App\Services\Product\VegetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('shared/vegetables/Categories', [
            'categories' => $categories,
        ]);
    }

    public function index(Request $request): Response|RedirectResponse
    {
        if (! $request->filled('category')) {
            return redirect()->route('categories');
        }

        $slug = $request->query('category');
        $category = Category::where('slug', $slug)->first();

        return Inertia::render('shared/vegetables/Index', [
            'vegetables' => Inertia::defer(
                function () use ($request, $category) {
                    $query = $this->vegetableService->paginated(
                        search: $request->query('search', null),
                        categoryId: $category->id,
                        userId: $request->user()->id,
                    )->paginate(12)
                        ->withQueryString();

                    return VegetableSharedResource::collection($query);
                }
            ),
            'category' => $category,
            'filters' => [
                'search' => $request->query('search', null),
            ],
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

        return Inertia::render('shared/vegetables/Show', [
            'variety' => Inertia::defer(
                fn () => (new VarietyDetailResource(
                    $this->varietyService->show($variety, $year, $month, VarietyViewerRole::Marketplace)
                ))->resolve()
            ),

            'calendarFilters' => [
                'year' => $year,
                'month' => $month,
            ],
            'meta' => [
                'varietyId' => $variety->id,
                'varietyLabel' => "{$variety->vegetable->name} {$variety->name}",
                'categoryName' => $variety->vegetable->category->name,
                'categorySlug' => $variety->vegetable->category->slug,
            ],
        ]);
    }
}
