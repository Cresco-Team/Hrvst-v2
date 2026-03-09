<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Services\Product\VarietyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VegetablesController extends Controller
{
    public function __construct(
        private VarietyService $varietyService
    ){}

    public function index(Request $request): Response
    {
        return Inertia::render('dealer/vegetables/Index', [
            'filters' => [
                'search' => $request->query('search'),
                'category_id' => $request->integer('category_id') ?: null,
            ],
            'varieties' => Inertia::defer(fn () => $this->varietyService->forCatalog(
                perPage: 20,
                search: $request->query('search'),
                categoryId: $request->integer('category_id') ?: null,
            )),
            'categoryOptions' => Inertia::defer(fn () => $this->varietyService->categoryOptions()),
        ]);
    }
}
