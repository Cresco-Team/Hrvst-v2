<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreFarmerOfferingRequest;
use App\Http\Requests\Farmer\UpdateFarmerOfferingRequest;
use App\Models\Announcement\FarmerOffering;
use App\Services\Farmer\FarmerOfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FarmerOfferingController extends Controller
{
    public function __construct(
        private FarmerOfferingService $service
    ) {}

    public function index(Request $request): Response
    {
        $farmerId = $request->user()->farmerProfile->id;
        $status = $request->query('status', 'all');

        return Inertia::render('farmer/offerings/Index', [
            'filters' => ['status' => $status],
            
            'offerings' => Inertia::defer(fn() => FarmerOfferingService::paginated(
                farmerId: $farmerId,
                status: $status
            )),
            
            'varietyOptions' => Inertia::defer(fn() => FarmerOfferingService::varietyOptions()),
        ]);
    }

    public function store(StoreFarmerOfferingRequest $request): RedirectResponse
    {
        $farmerId = $request->user()->farmerProfile->id;

        $this->service->create(
            farmerId: $farmerId,
            validated: $request->validated(),
            image: $request->file('image')
        );

        return redirect()->route('farmer.offerings.index')
            ->with('flash', ['type' => 'success', 'message' => 'Offering posted successfully!']);
    }

    public function update(UpdateFarmerOfferingRequest $request, FarmerOffering $farmerOffering): RedirectResponse
    {
        Gate::authorize('update', $farmerOffering);

        $this->service->update(
            offering: $farmerOffering,
            validated: $request->validated(),
            image: $request->file('image')
        );

        return redirect()->route('farmer.offerings.index')
            ->with('flash', ['type' => 'success', 'message' => 'Offering updated!']);
    }

    public function archive(FarmerOffering $farmerOffering): RedirectResponse
    {
        Gate::authorize('archive', $farmerOffering);

        $this->service->archive($farmerOffering);

        return redirect()->route('farmer.offerings.index')
            ->with('flash', ['type' => 'success', 'message' => 'Offering archived.']);
    }

    public function destroy(FarmerOffering $farmerOffering): RedirectResponse
    {
        Gate::authorize('delete', $farmerOffering);

        $this->service->delete($farmerOffering);

        return redirect()->route('farmer.offerings.index')
            ->with('flash', ['type' => 'success', 'message' => 'Offering deleted.']);
    }
}
