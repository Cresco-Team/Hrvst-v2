<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\VarietyResource;
use App\Models\Product\Variety;
use App\Services\Product\VarietyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VegetableController extends Controller
{
    public function __construct(
        private VarietyService $varietyService
    ) {}

    public function index(Request $request): Response
    {
        ['backHref' => $backHref, 'backLabel' => $backLabel, 'indexHref' => $indexHref] = $this->resolveHrefs($request);

        return Inertia::render('shared/vegetables/Index', [
            'backHref' => $backHref,
            'backLabel' => $backLabel,
            'indexHref' => $indexHref,
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
        ['backHref' => $backHref, 'backLabel' => $backLabel, 'indexHref' => $indexHref] = $this->resolveHrefs($request);

        return Inertia::render('shared/vegetables/Show', [
            'backHref' => $backHref,
            'backLabel' => $backLabel,
            'indexHref' => $indexHref,
            'variety' => Inertia::defer(
                fn () => (new VarietyResource($this->varietyService->show($variety)))->resolve()
            ),
        ]);
    }

    private function resolveHrefs(Request $request): array
    {
        if ($request->user()->hasRole('farmer')) {
            return [
                'backHref' => route('farmer.supplies.index'),
                'backLabel' => 'Farmer',
                'indexHref' => route('farmer.vegetables.index'),
            ];
        }

        return [
            'backHref' => route('dealer.demands.index'),
            'backLabel' => 'Dealer',
            'indexHref' => route('dealer.vegetables.index'),
        ];
    }
}
