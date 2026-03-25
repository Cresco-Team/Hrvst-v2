<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\VarietyResource;
use App\Models\Product\Variety;
use App\Services\Marketplace\VarietyCalendarService;
use App\Services\Product\VarietyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VegetableController extends Controller
{
    public function __construct(
        private VarietyService $varietyService,
        private VarietyCalendarService $calendarService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('shared/vegetables/Index', [
            'filters' => [
                'search' => $request->query('search'),
                'category_id' => $request->integer('category_id') ?: null,
            ],
            'varieties' => Inertia::defer(fn () => VarietyResource::collection(
                $this->varietyService->forCatalog(
                    perPage: 20,
                    search: $request->query('search'),
                    categoryId: $request->integer('category_id') ?: null,
                    userId: $request->user()->id,
                )
            )),
            'categoryOptions' => Inertia::defer(fn () => $this->varietyService->categoryOptions()),
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

        return Inertia::render($this->resolveView($request), [
            'variety' => Inertia::defer(
                fn () => new VarietyResource($variety->load([
                    'vegetable.category',
                    'media',
                    'latestPrice',
                    'prices',
                ]))
            ),

            'varietyCalendar' => Inertia::defer(
                fn () => $this->calendarService->forMonth(
                    varietyId: $variety->id,
                    year: $year,
                    month: $month,
                ),
                group: 'calendar',
            ),

            'calendarSummary' => Inertia::defer(
                fn () => $this->calendarService->summaryForMonth(
                    varietyId: $variety->id,
                    year: $year,
                    month: $month,
                ),
                group: 'calendar',
            ),

            'calendarFilters' => [
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }

    private function resolveView(Request $request): string
    {
        $user = $request->user();

        if ($user->hasRole('farmer')) {
            return 'farmer/vegetables/Show';
        }

        if ($user->hasRole('dealer')) {
            return 'dealer/vegetables/Show';
        }

        // Admin fallback — adjust if you have a dedicated admin view
        return 'shared/vegetables/Show';
    }
}
