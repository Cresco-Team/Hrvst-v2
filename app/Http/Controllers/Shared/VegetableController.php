<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Product\Category;
use App\Services\Product\VarietyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VegetableController extends Controller
{
    public function __construct(
        private VarietyService $varietyService,
    ) {}

    public function index(Request $request, string $category): Response
    {
        $categoryId = $request->integer('category_id') ?: null;
        $category = $categoryId ? Category::find($categoryId, ['id', 'name']) : null;

        return Inertia::render('shared/vegetables/Index', [
            'category' => $category,
            'filters' => [
                'search' => $request->query('search', null),
            ],
            'vegetables' => Inertia::defer(fn () => $this->varietyService->table(
                search: $request->query('search', null),
                categoryId: $categoryId,
                userId: $request->user()->id,
                perPage: 12,
            )),
        ]);
    }
}
