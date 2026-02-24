<?php

namespace App\Http\Controllers\Farmer;

use App\Actions\Farmer\ArchiveSupplyAction;
use App\Actions\Farmer\CreateSupplyAction;
use App\Actions\Farmer\DeleteSupplyAction;
use App\Actions\Farmer\FulfillSupplyAction;
use App\Actions\Farmer\UpdateSupplyAction;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreSupplyRequest;
use App\Http\Requests\Farmer\UpdateSupplyRequest;
use App\Models\Marketplace\FarmerSupply;
use App\Services\Farmer\SupplyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplyController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', FarmerSupply::class);

        $farmerId = $request->user()->farmerProfile->id;
        $status = PostStatus::tryFrom($request->query('status', PostStatus::Ongoing->value));

        return Inertia::render('farmer/garden/Index', [
            'filters'           => ['status' => $status],
            'summary'           => Inertia::defer(fn() => SupplyService::summary($farmerId)),
            'varietyOptions'    => Inertia::defer(fn() => SupplyService::varietyOptions()),
            'supply'            => Inertia::defer(fn() => SupplyService::paginated(farmerId: $farmerId, status: $status)),
        ]);
    }

    public function store(StoreSupplyRequest $request, CreateSupplyAction $createSupply): RedirectResponse
    {
        Gate::authorize('create', FarmerSupply::class);

        $createSupply(
            farmer: $request->user()->farmerProfile,
            validated: $request->validated(),
            image: $request->file('image')
        );

        return redirect()->route('farmer.garden.index')
            ->with('flash', ['type' => 'success', 'message' => 'Supply posted successfully!']);
    }

    public function update(UpdateSupplyRequest $request, FarmerSupply $supply, UpdateSupplyAction $updateSupply): RedirectResponse
    {
        Gate::authorize('update', $supply);

        $updateSupply(
            supply: $supply,
            validated: $request->validated(),
            image: $request->file('image')
        );

        return redirect()->route('farmer.garden.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post updated successfully!']);
    }

    public function archive(FarmerSupply $offering, ArchiveSupplyAction $archiveSupply): RedirectResponse
    {
        Gate::authorize('archive', $offering);

        $archiveSupply($offering);

        return redirect()->route('farmer.garden.index')
            ->with('flash', ['type' => 'success', 'message' => 'Offering archived.']);
    }

    public function fulfill(FarmerSupply $supply, FulfillSupplyAction $fulfillSupply): RedirectResponse
    {
        Gate::authorize('fulfill', $supply);

        $fulfillSupply($supply);

        return redirect()->route('farmer.garden.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post marked as fulfilled!']);
    }

    public function destroy(FarmerSupply $supply, DeleteSupplyAction $deleteSupply): RedirectResponse
    {
        Gate::authorize('delete', $supply);

        $deleteSupply($supply);

        return redirect()->route('farmer.garden.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post deleted.']);
    }
}
