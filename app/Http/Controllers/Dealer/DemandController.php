<?php

namespace App\Http\Controllers\Dealer;

use App\Enums\DealerDemandStatus;
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
    public function __construct(
        private DemandService $service
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load('dealerProfile');
        $dealerId = $user->dealerProfile->id;
        $status = DealerDemandStatus::tryFrom($request->query('status', DealerDemandStatus::Open->value));

        return Inertia::render('dealer/demands/Index', [
            'summary' => Inertia::defer(fn() => DemandService::summary($dealerId)),
            'varietyOptions' => Inertia::defer(fn() => DemandService::varietyOptions()),
            'filters' => ['status' => $status],
            'demands' => Inertia::defer(fn() => DemandService::paginated(
                dealerId: $dealerId,
                status: $status
            )),
        ]);
    }

    public function store(StoreDemandRequest $request): RedirectResponse
    {
        $dealerId = $request->user()->dealerProfile->id;
        
        $this->service->create(
            dealerId: $dealerId,
            validated: $request->validated()
        );

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request posted successfully!']);
    }

    public function update(UpdateDemandRequest $request, DealerDemand $dealerDemand): RedirectResponse
    {
        $request->user()->load('dealerProfile');
        
        Gate::authorize('update', $dealerDemand);

        $this->service->update(
            request: $dealerDemand,
            validated: $request->validated(),
        );

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request updated successfully!']);
    }

    public function expire(DealerDemand $request): RedirectResponse
    {
        Gate::authorize('expire', $request);

        $this->service->expire($request);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request expired.']);
    }

    public function fulfill(DealerDemand $dealerDemand): RedirectResponse
    {
        Gate::authorize('markAsFulfilled', $dealerDemand);

        $this->service->markAsFulfilled($dealerDemand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request marked as fulfilled!']);
    }

    public function destroy(Request $request, DealerDemand $demand): RedirectResponse
    {
        Gate::authorize('delete', $demand);

        $this->service->delete($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request deleted.']);
    }
}
