<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Marketplace\DealerDemandResource;
use App\Services\Farmer\MarketplaceService;
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
            'category_id' => 'nullable|exists:categories,id',
            'variety_id' => 'nullable|exists:varieties,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        return Inertia::render('farmer/marketplace/Index', [
            'filters' => $validated,
            'categoryOptions' => Inertia::defer(fn () => $this->marketplaceService->categoryOptions(), 'options'),
            'demands' => Inertia::defer(fn () => DealerDemandResource::collection($this->marketplaceService->paginated($validated)), 'demands'),
        ]);
    }
}
