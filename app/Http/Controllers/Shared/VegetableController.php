<?php

namespace App\Http\Controllers\Shared;

use App\Data\Vegetable\VegetableIndexData;
use App\Http\Controllers\Concerns\RendersVegetableShow;
use App\Http\Controllers\Controller;
use App\Models\Product\Category;
use App\Models\Vegetable\Vegetable;
use App\Services\Product\VegetableDetailService;
use App\Services\Product\VegetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                return VegetableIndexData::collect(
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
        return $this->renderVegetableShow($request, $vegetable, $this->vegetableDetailService);
    }
}
