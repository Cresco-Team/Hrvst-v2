<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Post;
use App\Services\Marketplace\SupplyMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplyMapController extends Controller
{
    public function __construct(
        private SupplyMapService $supplyMapService
    ) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Post::class);

        return Inertia::render('dealer/supply-map/Index', [
            'mapConfig' => $this->supplyMapService->mapConfig(),
            'filterOptions' => Inertia::defer(fn () => $this->supplyMapService->filterOptions()),
        ]);
    }

    public function markers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Post::class);

        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'variety_id' => 'nullable|integer|exists:varieties,id',
        ]);

        return response()->json([
            'markers' => $this->supplyMapService->markers(
                categoryId: $validated['category_id'] ?? null,
                varietyId: $validated['variety_id'] ?? null,
            ),
        ]);
    }
}
