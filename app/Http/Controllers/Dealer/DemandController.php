<?php

namespace App\Http\Controllers\Dealer;

use App\Actions\Demand\ArchiveDemandAction;
use App\Actions\Demand\CreateDemandAction;
use App\Actions\Demand\DeleteDemandAction;
use App\Actions\Demand\FulfillDemandAction;
use App\Actions\Demand\UpdateDemandAction;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\StoreDemandRequest;
use App\Http\Requests\Dealer\UpdateDemandRequest;
use App\Models\Marketplace\DealerDemand;
use App\Services\Dealer\DemandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DemandController extends Controller
{
    public function __construct (
        private DemandService $demandService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', DealerDemand::class);

        $dealerId = $request->user()->dealerProfile->id;
        $status = PostStatus::tryFrom($request->query('status', PostStatus::Ongoing->value));

        return Inertia::render('dealer/demands/Index', [
            'filters'           => ['status' => $status],
            'summary'           => Inertia::defer(fn() => $this->demandService->summary($dealerId)),
            'varietyOptions'    => Inertia::defer(fn() => $this->demandService->varietyOptions()),
            'demands'           => Inertia::defer(fn() => $this->demandService->paginated(dealerId: $dealerId, status: $status)),
        ]);
    }

    public function store(StoreDemandRequest $request, CreateDemandAction $createDemand): RedirectResponse
    {
        Gate::authorize('create', DealerDemand::class);

        $createDemand(
            dealer:     $request->user()->dealerProfile,
            validated:  $request->validated(),
        );

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request posted successfully!']);
    }

    public function update(UpdateDemandRequest $request, DealerDemand $demand, UpdateDemandAction $updateDemand): RedirectResponse
    {
        Gate::authorize('update', $demand);

        $updateDemand(
            demand: $demand,
            validated: $request->validated(),
        );

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post updated successfully!']);
    }

    public function archive(DealerDemand $demand, ArchiveDemandAction $archiveDemand): RedirectResponse
    {
        Gate::authorize('archive', $demand);

        $archiveDemand($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post archived.']);
    }

    public function fulfill(DealerDemand $demand, FulfillDemandAction $fulfillDemand): RedirectResponse
    {
        Gate::authorize('fulfill', $demand);

        $fulfillDemand($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post marked as fulfilled!']);
    }

    public function destroy(DealerDemand $demand, DeleteDemandAction $deleteDemand): RedirectResponse
    {
        Gate::authorize('delete', $demand);

        $deleteDemand($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Post deleted.']);
    }
}
