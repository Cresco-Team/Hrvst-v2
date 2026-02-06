<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profiles\FarmerProfile;
use App\Services\Admin\FarmerMapService;
use App\Services\Admin\FarmerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FarmerController extends Controller
{
    public function __construct(
        private FarmerMapService $farmerMapService
    ) {}

    /**
     * Display farmers index with view toggle support
     * Supports both list and map views via ?view={list|map}
     */
    public function index(Request $request): Response
    {
        $view = $request->query('view', 'list'); // Default to 'list'

        return Inertia::render('admin/farmers/Index', [
            // Always loaded (synchronous)
            'view' => $view,
            'filters' => [
                'municipalities' => $this->farmerMapService->getMunicipalityOptions(),
                'plantings' => $this->farmerMapService->getPlantingOptions(),
            ],
            'mapConfig' => [
                'center' => [
                    'lat' => 16.4137,  // La Trinidad, Benguet
                    'lng' => 120.5896,
                ],
                'defaultZoom' => 13,
            ],
            
            // Deferred load (only if view === 'list')
            'farmers' => $view === 'list' 
                ? Inertia::defer(fn () => FarmerService::paginated())
                : null,
            
            'summary' => Inertia::defer(fn () => FarmerService::summary()),
            
            // Pending farmers for approval (always loaded for badge count)
            'pendingFarmers' => FarmerService::pending(),
        ]);
    }

    /**
     * API endpoint: Get farmer markers for map view
     * Called via AJAX from frontend
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
     * API endpoint: Get detailed farmer information for sidebar
     * Called via AJAX when user clicks a map marker
     */
    public function details(int $id): JsonResponse
    {
        $farmer = $this->farmerMapService->getFarmerDetails($id);

        if (!$farmer) {
            return response()->json([
                'error' => 'Farmer not found',
            ], 404);
        }

        return response()->json($farmer);
    }

    /**
     * Show individual farmer details page
     */
    public function show(int $id): Response
    {
        $farmer = FarmerService::find($id);

        if (!$farmer) {
            abort(404, 'Farmer not found');
        }

        return Inertia::render('admin/farmers/Show', [
            'farmer' => $farmer,
        ]);
    }

    /**
     * Approve a pending farmer
     */
    public function approve(int $id): RedirectResponse
    {
        $approved = FarmerService::approve($id);

        if (!$approved) {
            return redirect()->route('admin.farmers.index')
                ->with('flash', [
                    'type' => 'error',
                    'message' => 'Farmer not found or already approved.',
                ]);
        }

        return redirect()->route('admin.farmers.index')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Farmer approved successfully.',
            ]);
    }

    /**
     * Reject and delete a pending farmer
     */
    public function reject(int $id): RedirectResponse
    {
        $rejected = FarmerService::reject($id);

        if (!$rejected) {
            return redirect()->route('admin.farmers.index')
                ->with('flash', [
                    'type' => 'error',
                    'message' => 'Farmer not found or already processed.',
                ]);
        }

        return redirect()->route('admin.farmers.index')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Farmer rejected and account deleted.',
            ]);
    }

    /**
     * Delete farmer profile (for approved farmers)
     */
    public function destroy(FarmerProfile $farmerProfile): RedirectResponse
    {
        // Check if farmer has active plantings
        if ($farmerProfile->plantings()->where('status', 'active')->exists()) {
            return redirect()->route('admin.farmers.index')
                ->with('flash', [
                    'type' => 'error',
                    'message' => 'Cannot delete farmer with active plantings.',
                ]);
        }

        $user = $farmerProfile->user;
        $farmerProfile->delete();
        $user->delete();

        return redirect()->route('admin.farmers.index')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Farmer deleted successfully.',
            ]);
    }
}
