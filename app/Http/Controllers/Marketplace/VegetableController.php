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
        return Inertia::render('shared/vegetables/Show', [
            'variety' => Inertia::defer(
                fn () => (new VarietyResource($this->varietyService->show($variety)))->resolve()
            ),
        ]);
    }
}
