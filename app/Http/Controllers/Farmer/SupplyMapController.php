<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\FarmerSupply;
use App\Services\Marketplace\SupplyMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplyMapController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', FarmerSupply::class);

        return Inertia::render('farmer/supply-map/Index', [
            'mapConfig'     => SupplyMapService::mapConfig(),
            'filterOptions' => Inertia::defer(fn () => SupplyMapService::filterOptions()),
        ]);
    }

    public function markers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', FarmerSupply::class);

        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'variety_id'  => 'nullable|integer|exists:varieties,id',
        ]);

        return response()->json([
            'markers' => SupplyMapService::markers(
                categoryId: $validated['category_id'] ?? null,
                varietyId:  $validated['variety_id'] ?? null,
            ),
        ]);
    }
}
