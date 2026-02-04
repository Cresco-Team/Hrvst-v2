<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\FarmerMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FarmerMapController extends Controller
{
    public function __construct(
        private FarmerMapService $farmerMapService
    ) {}

    /**
     * Display the farmer map view
     */
    public function index(): Response
    {
        return Inertia::render('admin/farmers/Map', [
            'filters' => [
                'municipalities' => $this->farmerMapService->getMunicipalityOptions(),
                'plantings' => $this->farmerMapService->getPlantingOptions(),
            ],
            'mapCenter' => [
                'lat' => 16.4137,  // La Trinidad, Benguet
                'lng' => 120.5896,
            ],
            'defaultZoom' => 13,
        ]);
    }

    /**
     * Get farmers data for map markers (API endpoint)
     */
    public function markers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'municipality_id' => 'nullable|exists:municipalities,id',
            'variety_id' => 'nullable|exists:varieties,id',
            'bounds' => 'nullable|array',
            'bounds.north' => 'required_with:bounds|numeric',
            'bounds.south' => 'required_with:bounds|numeric',
            'bounds.east' => 'required_with:bounds|numeric',
            'bounds.west' => 'required_with:bounds|numeric',
        ]);

        $farmers = $this->farmerMapService->getFarmersForMap(
            municipalityId: $validated['municipality_id'] ?? null,
            varietyId: $validated['variety_id'] ?? null,
            bounds: $validated['bounds'] ?? null
        );

        return response()->json([
            'markers' => $farmers,
            'total' => count($farmers),
        ]);
    }

    /**
     * Get detailed farmer information for sidebar
     */
    public function show(int $id): JsonResponse
    {
        $farmer = $this->farmerMapService->getFarmerDetails($id);

        if (!$farmer) {
            return response()->json([
                'error' => 'Farmer not found',
            ], 404);
        }

        return response()->json($farmer);
    }
}
