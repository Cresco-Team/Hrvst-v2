<?php

namespace App\Http\Controllers\Shared;

use App\Data\Vegetable\VegetableDetailData;
use App\Data\Vegetable\VegetableSharedData;
use App\Enums\Analytics\VarietyViewerRole;
use App\Http\Controllers\Controller;
use App\Models\Product\Category;
use App\Models\Product\Vegetable;
use App\Services\Product\VegetableDetailService;
use App\Services\Product\VegetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VegetableController extends Controller
{
    public function __construct(
        private VegetableService $vegetableService,
        private VegetableDetailService $vegetableDetailService,
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
            'vegetables' => Inertia::defer(function () use ($request, $category) {
                return VegetableSharedData::collect(
                    $this->vegetableService->paginated(
                        search: $request->query('search', null),
                        categoryId: $category->id,
                    )->paginate(12)->withQueryString(),
                );
        }),
            'category' => $category,
            'filters' => [
                'search' => $request->query('search', null),
            ],
        ]);
    }

    public function show(Request $request, Vegetable $vegetable): Response
    {
        $vegetable->loadMissing('category');

        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2035'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        return Inertia::render('shared/vegetables/Show', [
            'variety' => Inertia::defer(
                fn () => VegetableDetailData::fromModel($this->vegetableDetailService->show($vegetable, $year, $month, VarietyViewerRole::Marketplace))
            ),
            'calendarFilters' => [
                'year' => $year,
                'month' => $month,
            ],
            'meta' => [
                'varietyId' => $vegetable->id,
                'varietyLabel' => $vegetable->variety_name
                    ? "{$vegetable->vegetable_name}: {$vegetable->variety_name}"
                    : $vegetable->vegetable_name,
                'categoryName' => $vegetable->category->name,
                'categorySlug' => $vegetable->category->slug,
            ],
        ]);
    }
}
