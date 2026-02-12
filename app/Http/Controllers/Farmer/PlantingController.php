<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StorePlantingRequest;
use App\Http\Requests\Farmer\UpdatePlantingRequest;
use App\Models\Product\Planting;
use App\Services\Farmer\PlantingService;
use App\Services\Media\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlantingController extends Controller
{
    public function __construct(
        private PlantingService $plantingService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Planting::class);

        $farmer = $request->user()->farmerProfile;

        if (!$farmer) {
            abort(403, 'Farmer profile not found.');
        }

        $page = (int) $request->query('page', 1);
        $status = $request->query('status', 'available');

        return Inertia::render('farmer/garden/Index', [
            'filters' => [
                'status' => $status,
                'page' => $page,
            ],
            
            'plantings' => Inertia::defer(fn () => PlantingService::paginated(
                farmerId: $farmer->id,
                status: $status,
                perPage: 20,
            )),
            
            'summary' => Inertia::defer(fn () => PlantingService::summary($farmer->id)),
            
            'varietyOptions' => Inertia::defer(fn () => PlantingService::varietyOptionsForForm()),
        ]);
    }

    public function store(StorePlantingRequest $request): RedirectResponse
    {
        Gate::authorize('create', Planting::class);

        $farmer = $request->user()->farmerProfile;
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image_path'] = app(ImageUploadService::class)
                ->uploadVarietyImage($request->file('image'));
        }

        $this->plantingService->create(
            farmerId: $farmer->id,
            validated: $validated
        );

        return redirect()->route('farmer.garden.index')
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Planting added successfully!'
            ]);
    }

    public function update(UpdatePlantingRequest $request, Planting $planting): RedirectResponse
    {
        Gate::authorize('update', $planting);

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image_path'] = app(ImageUploadService::class)
                ->uploadVarietyImage($request->file('image'), $planting->image_path);
        }

        $this->plantingService->update($planting, $request->validated());

        return redirect()->route('farmer.garden.index')
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Planting updated successfully!'
            ]);
    }

    public function archive(Planting $planting): RedirectResponse
    {
        Gate::authorize('update', $planting);

        $this->plantingService->markAsArchived($planting);

        return redirect()->route('farmer.garden.index')
            ->with('flash', [
                'type' => 'success', 
                'message' => 'Planting archived successfully.'
            ]);
    }

    public function destroy(Planting $planting): RedirectResponse
    {
        Gate::authorize('delete', $planting);

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
