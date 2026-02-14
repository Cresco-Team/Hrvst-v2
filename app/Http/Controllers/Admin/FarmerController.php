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
