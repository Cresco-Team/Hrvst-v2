<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreFarmerOfferingRequest;
use App\Http\Requests\Farmer\UpdateFarmerOfferingRequest;
use App\Models\Marketplace\FarmerOffering;
use App\Services\Farmer\OfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OfferingController extends Controller
{
    public function __construct(
        private OfferingService $service
    ) {}

    public function index(Request $request): Response
    {
        $farmerId = $request->user()->farmerProfile->id;
        $status = $request->query('status', 'available');

        return Inertia::render('farmer/garden/Index', [
            'summary' => Inertia::defer(fn() => OfferingService::summary(
                farmerId: $farmerId,
            )),
            'filters' => ['status' => $status],
            
            'offerings' => Inertia::defer(fn() => OfferingService::paginated(
                farmerId: $farmerId,
                status: $status
            )),
            
            'varietyOptions' => Inertia::defer(fn() => OfferingService::varietyOptions()),
        ]);
    }

    public function store(StoreFarmerOfferingRequest $request): RedirectResponse
    {
        Gate::authorize('create', FarmerOffering::class);

        $farmerId = $request->user()->farmerProfile->id;

        $this->service->create(
            farmerId: $farmerId,
            validated: $request->validated(),
            image: $request->file('image')
        );

        return redirect()->route('farmer.garden.index')
            ->with('flash', ['type' => 'success', 'message' => 'Offering posted successfully!']);
    }

    public function update(UpdateFarmerOfferingRequest $request, FarmerOffering $offering): RedirectResponse
    {
        Gate::authorize('update', $offering);

        $this->service->update(
            offering: $offering,
            validated: $request->validated(),
            image: $request->file('image')
        );

        return redirect()->route('farmer.garden.index')
            ->with('flash', ['type' => 'success', 'message' => 'Offering updated!']);
    }

    public function archive(FarmerOffering $offering): RedirectResponse
    {
        Gate::authorize('archive', $offering);

        $this->service->archive($offering);

        return redirect()->route('farmer.garden.index')
            ->with('flash', ['type' => 'success', 'message' => 'Offering archived.']);
    }

    public function destroy(FarmerOffering $offering): RedirectResponse
    {
        Gate::authorize('delete', $offering);

        $this->service->delete($offering);

        return redirect()->route('farmer.garden.index')
            ->with('flash', ['type' => 'success', 'message' => 'Offering deleted.']);
    }
}
