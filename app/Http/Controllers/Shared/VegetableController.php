<?php

namespace App\Http\Controllers\Shared;

use App\Enums\Analytics\VarietyViewerRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\VarietyDetailResource;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Services\Product\VarietyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VegetableController extends Controller
{
    public function __construct(
        private VarietyService $varietyService,
    ) {}

    public function index(Request $request, Category $category): Response
    {
        return Inertia::render('shared/vegetables/Index', [
            'category' => $category->only(['id', 'name', 'slug']),
            'filters' => [
                'search' => $request->query('search', null),
            ],
            'vegetables' => Inertia::defer(fn () => $this->varietyService->table(
                search: $request->query('search', null),
                categoryId: $category->id,
                userId: $request->user()->id,
                perPage: 12,
            )),
        ]);
    }

    public function show(Request $request, string $category, Variety $variety): Response
    {
        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2020', 'max:2035'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        return Inertia::render('shared/vegetables/Show', [
            'category' => $category,
            'variety' => Inertia::defer(
                fn () => (new VarietyDetailResource(
                    $this->varietyService->show($variety, $year, $month, VarietyViewerRole::Marketplace)
                ))->resolve()
            ),

            'calendarFilters' => [
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }
}
