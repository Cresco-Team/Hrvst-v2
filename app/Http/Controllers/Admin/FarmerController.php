<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ApproveFarmerAction;
use App\Actions\Admin\RejectFarmerAction;
use App\Http\Controllers\Controller;
use App\Models\Profiles\FarmerProfile;
use App\Services\Admin\FarmerMapService;
use App\Services\Admin\FarmerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FarmerController extends Controller
{
    public function __construct(
        private FarmerMapService $farmerMapService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        $view = $request->query('view', 'list');

        return Inertia::render('admin/farmers/Index', [
            'view'      => $view,
            'filters'   => [
                'municipalities'    => $this->farmerMapService->getMunicipalityOptions(),
                'supplies'          => $this->farmerMapService->getSupplyOptions(),
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
        ]);
    }

    public function markers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', FarmerProfile::class);

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
        $farmerProfile = FarmerProfile::findOrFail($id);
        Gate::authorize('view', $farmerProfile);
        
        $farmer = $this->farmerMapService->getFarmerDetails($id);

        if (!$farmer) {
            return response()->json(['error' => 'Farmer not found'], 404);
        }


        return response()->json($farmer);
    }

    public function show(int $id): Response
    {
        $farmerProfile = FarmerProfile::findOrFail($id);
        Gate::authorize('view', $farmerProfile);

        $farmer = FarmerService::show($id);

        if (!$farmer) abort(404, 'Farmer not found');


        return Inertia::render('admin/farmers/Show', [
            'farmer' => $farmer,
        ]);
    }

    public function pending(): JsonResponse
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        return response()->json(FarmerService::pending());
    }

    public function approve(FarmerProfile $farmer, ApproveFarmerAction $approveFarmer): RedirectResponse
    {
        Gate::authorize('approve', FarmerProfile::class);

        $approveFarmer($farmer);

        return back()
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Farmer Approved.'
            ]);
    }

    public function reject(FarmerProfile $farmer, RejectFarmerAction $rejectFarmer): RedirectResponse
    {
        Gate::authorize('reject', FarmerProfile::class);

        $rejectFarmer($farmer);

        return back()
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Farmer Rejected and Deleted.'
            ]);
    }

    public function destroy(FarmerProfile $farmerProfile): RedirectResponse
    {
        Gate::authorize('delete', $farmerProfile);

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
