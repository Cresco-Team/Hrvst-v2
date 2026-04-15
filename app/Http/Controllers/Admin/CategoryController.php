<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->withCount('vegetables')
            ->addSelect([
                'varieties_count' => Variety::query()
                    ->join('vegetables', 'varieties.vegetable_id', '=', 'vegetables.id')
                    ->whereColumn('vegetables.category_id', 'categories.id')
                    ->selectRaw('COUNT(*)'),
            ])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/vegetables/Categories', [
            'categories' => $categories,
        ]);
    }
}
