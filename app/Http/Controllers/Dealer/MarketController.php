<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Services\Dealer\DealerMarketService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketController extends Controller
{
    /**
     * Display dealer market dashboard
     * Uses deferred props for instant navigation
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|integer|exists:categories,id',
        ]);

        return Inertia::render('dealer/Market', [
            // Synchronous (instant load)
            'filters' => [
                'search' => $validated['search'] ?? null,
                'category' => $validated['category'] ?? null,
                'categories' => DealerMarketService::categoryOptions(),
            ],
            
            // Deferred (load after page renders)
            'plantings' => Inertia::defer(fn () => DealerMarketService::paginated(
                perPage: 20,
                searchQuery: $validated['search'] ?? null,
                categoryId: $validated['category'] ?? null
            )),
            
            'insights' => Inertia::defer(fn () => DealerMarketService::insights()),
        ]);
    }
}