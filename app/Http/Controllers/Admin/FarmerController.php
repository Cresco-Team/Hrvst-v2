<?php

namespace App\Http\Controllers\Admin;

use App\FarmerOfferingStatus;
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

    public function index(Request $request): Response
    {
        $view = $request->query('view', 'list'); // Default to 'list'

        return Inertia::render('admin/farmers/Index', [
            'view' => $view,
            'filters' => [
                'municipalities' => $this->farmerMapService->getMunicipalityOptions(),
                'offerings' => $this->farmerMapService->getOfferingOptions(),
            ],
            'mapConfig' => [
                'center' => [
                    'lat' => 16.4137,
                    'lng' => 120.5896,
                ],
                'defaultZoom' => 13,
            ],
            'farmers' => $view === 'list' 
                ? Inertia::defer(fn () => FarmerService::paginated())
                : null,
            
            'summary' => Inertia::defer(fn () => FarmerService::summary()),
            
            'pendingFarmers' => FarmerService::pending(),
        ]);
    }

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

    public function pending(): JsonResponse
    {
        return response()->json(FarmerService::pending());
    }

    public function approve(int $farmer): RedirectResponse
    {
        abort_if(! FarmerService::approve($farmer), 404);

        return back();
    }

    public function reject(int $farmer): RedirectResponse
    {
        abort_if(! FarmerService::reject($farmer), 404);

        return back();
    }

    public function destroy(FarmerProfile $farmerProfile): RedirectResponse
    {
        // Check if farmer has active plantings
        if ($farmerProfile->offerings()->where('status', FarmerOfferingStatus::Available)->exists()) {
            return redirect()->route('admin.farmers.index')
                ->with('flash', [
                    'type' => 'error',
                    'message' => 'Cannot delete farmer with offering posts.',
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
