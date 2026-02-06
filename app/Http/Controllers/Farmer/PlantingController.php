<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StorePlantingRequest;
use App\Http\Requests\Farmer\UpdatePlantingRequest;
use App\Models\Product\Planting;
use App\Services\Farmer\FarmerPlantingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlantingController extends Controller
{
    public function __construct(
        private FarmerPlantingService $plantingService
    ) {}

    /**
     * Display farmer's garden (plantings dashboard).
     * Uses deferred props for instant navigation.
     */
    public function index(Request $request): Response
    {
        $farmer = $request->user()->farmerProfile;

        if (!$farmer) {
            abort(403, 'Farmer profile not found.');
        }

        $page = (int) $request->query('page', 1);
        $status = $request->query('status', 'all');
        $search = $request->query('search');

        return Inertia::render('farmer/Garden', [
            // Instant load (synchronous)
            'filters' => [
                'status' => $status,
                'page' => $page,
                'search' => $search,
            ],
            
            // Deferred (load after page renders)
            'plantings' => Inertia::defer(fn () => FarmerPlantingService::paginated(
                farmerId: $farmer->id,
                perPage: 20,
                statusFilter: $status,
                searchQuery: $search,
                page: $page
            )),
            
            'summary' => Inertia::defer(fn () => FarmerPlantingService::summary($farmer->id)),
            
            'varietyOptions' => Inertia::defer(fn () => FarmerPlantingService::varietyOptionsForForm()),
        ]);
    }

    /**
     * Store a new planting.
     */
    public function store(StorePlantingRequest $request): RedirectResponse
    {
        $farmer = $request->user()->farmerProfile;

        $this->plantingService->create(
            farmerId: $farmer->id,
            validated: $request->validatedWithExpectedHarvest()
        );

        return redirect()->route('farmer.garden.index')
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Planting added successfully!'
            ]);
    }

    /**
     * Update an existing planting.
     */
    public function update(UpdatePlantingRequest $request, Planting $planting): RedirectResponse
    {
        $this->plantingService->update($planting, $request->validated());

        return redirect()->route('farmer.garden.index')
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Planting updated successfully!'
            ]);
    }

    /**
     * Mark planting as harvested.
     */
    public function harvest(Request $request, Planting $planting): RedirectResponse
    {
        $this->authorize('harvest', $planting);

        $validated = $request->validate([
            'actual_weight' => ['nullable', 'numeric', 'min:0.1', 'max:99999'],
        ]);

        $this->plantingService->markAsHarvested(
            $planting, 
            $validated['actual_weight'] ?? null
        );

        return redirect()->route('farmer.garden.index')
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Planting marked as harvested!'
            ]);
    }

    /**
     * Mark planting as cancelled.
     */
    public function cancel(Planting $planting): RedirectResponse
    {
        $this->authorize('cancel', $planting);

        $this->plantingService->markAsCancelled($planting);

        return redirect()->route('farmer.garden.index')
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Planting marked as cancelled.'
            ]);
    }

    /**
     * Delete a planting.
     */
    public function destroy(Planting $planting): RedirectResponse
    {
        $this->authorize('delete', $planting);

        $deleted = $this->plantingService->delete($planting);

        if (!$deleted) {
            return redirect()->route('farmer.garden.index')
                ->with('flash', [
                    'type' => 'error', 
                    'message' => 'Cannot delete planting with active dealer conversations.'
                ]);
        }

        return redirect()->route('farmer.garden.index')
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Planting deleted successfully.'
            ]);
    }
}
