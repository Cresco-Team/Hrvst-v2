<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Marketplace\FarmerSupplyResource;
use App\Models\Marketplace\Post;
use App\Services\Dealer\MarketplaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function __construct(
        private MarketplaceService $marketplaceService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'variety_id' => 'nullable|exists:varieties,id',
            'municipality_id' => 'nullable|exists:municipalities,id',
        ]);

        return Inertia::render('dealer/marketplace/Index', [
            'filters' => $validated,
            'supplies' => Inertia::defer(fn () => FarmerSupplyResource::collection(
                $this->marketplaceService->paginated($validated)
            )),
            'categoryOptions' => Inertia::defer(fn () => $this->marketplaceService->categoryOptions()),
        ]);
    }
}
