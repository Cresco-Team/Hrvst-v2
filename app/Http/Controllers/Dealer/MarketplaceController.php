<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\FarmerSupply;
use App\Services\Dealer\MarketplaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FarmerSupply::class);

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'variety_id' => 'nullable|exists:varieties,id',
            'municipality_id' => 'nullable|exists:municipalities,id',
        ]);

        return Inertia::render('dealer/marketplace/Index', [
            'filters' => $validated,
            'supplies' => Inertia::defer(fn() => MarketplaceService::paginated($validated)),
            'categoryOptions' => Inertia::defer(fn() => MarketplaceService::categoryOptions()),
        ]);
    }

    public function show(FarmerSupply $supply): Response
    {
        Gate::authorize('view', $supply);

        return Inertia::render('dealer/marketplace/Show', [
            'supply' => MarketplaceService::detailed($supply),
        ]);
    }
}
