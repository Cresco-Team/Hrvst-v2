<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Product\Planting;
use App\Services\Dealer\MarketplaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'variety_id' => 'nullable|exists:varieties,id',
            'municipality_id' => 'nullable|exists:municipalities,id',
        ]);

        return Inertia::render('dealer/marketplace/Index', [
            // Synchronous (immediate load)
            'filters' => $validated,
            
            // Deferred (load after page renders)
            'offerings' => Inertia::defer(fn() => 
                MarketplaceService::paginated($validated)
            ),
            
            'filterOptions' => Inertia::defer(fn() => [
                'categories' => MarketplaceService::categoryOptions(),
                'municipalities' => MarketplaceService::municipalityOptions(),
            ]),
        ]);
    }

    public function show(Planting $planting): Response
    {
        Gate::authorize('view', $planting);

        return Inertia::render('dealer/marketplace/Show', [
            'offering' => MarketplaceService::detailed($planting),
        ]);
    }
}
