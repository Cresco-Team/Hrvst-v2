<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Farmer\ApproveFarmerAction;
use App\Actions\Farmer\RejectFarmerAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Profile\FarmerResource;
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
        private FarmerService $farmerService,
        private FarmerMapService $farmerMapService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        $view = $request->query('view', 'list');

        return Inertia::render('admin/farmers/Index', [
            'view' => $view,
            'filters' => [
                'search' => $request->query('search', null),
                'municipalities' => $this->farmerMapService->getMunicipalityOptions(),
                'supplies' => $this->farmerMapService->getSupplyOptions(),
            ],
            'mapConfig' => [
                'center' => [
                    'lat' => 16.4137,
                    'lng' => 120.5896,
                ],
                'defaultZoom' => 13,
            ],
            'farmers' => $view === 'list'
                ? Inertia::defer(fn () => FarmerResource::collection(
                    $this->farmerService->paginated(
                        perPage: 20,
                        search: $request->query('search', null),
                    )
                ))
                : null,
            'summary' => Inertia::defer(fn () => $this->farmerService->summary()),
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

    public function details(FarmerProfile $farmer): JsonResponse
    {
        Gate::authorize('view', $farmer);

        return response()->json(
            (new FarmerResource($this->farmerService->details($farmer)))->resolve()
        );
    }

    public function show(FarmerProfile $farmer): Response
    {
        Gate::authorize('view', $farmer);

        return Inertia::render('admin/farmers/Show', [
            'farmer' => Inertia::defer(fn () => (new FarmerResource($this->farmerService->show($farmer)))->resolve()
            ),
        ]);
    }

    public function pending(): JsonResponse
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        return response()->json(
            FarmerResource::collection($this->farmerService->pending())->resolve()
        );
    }

    public function approve(FarmerProfile $farmer, ApproveFarmerAction $approveFarmer): RedirectResponse
    {
        Gate::authorize('approve', FarmerProfile::class);

        $approveFarmer($farmer);

        return back()
            ->with('flash', [
                'type' => 'success',
                'message' => 'Farmer Approved.',
            ]);
    }

    public function reject(FarmerProfile $farmer, RejectFarmerAction $rejectFarmer): RedirectResponse
    {
        Gate::authorize('reject', FarmerProfile::class);

        $rejectFarmer($farmer);

        return back()
            ->with('flash', [
                'type' => 'success',
                'message' => 'Farmer Rejected and Deleted.',
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
