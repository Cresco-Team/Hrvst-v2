<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Marketplace\PostItemResource;
use App\Services\Dealer\MarketplaceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function __construct(
        private MarketplaceService $marketplaceService
    ) {}

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'variety_id' => 'nullable|exists:varieties,id',
            'municipality_id' => 'nullable|exists:municipalities,id',
        ]);

        return Inertia::render('dealer/marketplace/Index', [
            'filters' => $validated,
            'supplies' => Inertia::defer(fn () => PostItemResource::collection(
                $this->marketplaceService->paginated(
                    filters: $validated,
                    userId: $request->user()->id,
                )
            )),
            'categoryOptions' => Inertia::defer(fn () => $this->marketplaceService->categoryOptions()),
        ]);
    }
}
