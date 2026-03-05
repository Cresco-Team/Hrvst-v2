<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Services\Product\VarietyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VegetablesController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('farmer/vegetables/Index', [
            'filters' => [
                'search' => $request->query('search'),
                'category_id' => $request->integer('category_id') ?: null,
            ],
            'varieties' => Inertia::defer(fn () => VarietyService::forCatalog(
                perPage: 20,
                search: $request->query('search'),
                categoryId: $request->integer('category_id') ?: null,
            )),
            'categoryOptions' => Inertia::defer(fn () => VarietyService::categoryOptions()),
        ]);
    }
}
