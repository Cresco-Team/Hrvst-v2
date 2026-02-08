<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\StoreDealerRequestRequest;
use App\Http\Requests\Dealer\UpdateDealerRequestRequest;
use App\Models\Announcement\DealerRequest;
use App\Services\Dealer\DealerRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DealerRequestController extends Controller
{
    public function __construct(
        private DealerRequestService $service
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load('dealerProfile');
        $dealerId = $user->dealerProfile->id;
        $status = $request->query('status', 'all');

        return Inertia::render('dealer/Requests', [
            'filters' => ['status' => $status],
            
            'requests' => Inertia::defer(fn() => DealerRequestService::paginated(
                dealerId: $dealerId,
                status: $status
            )),
            
            'summary' => Inertia::defer(fn() => DealerRequestService::summary($dealerId)),
            'varietyOptions' => Inertia::defer(fn() => DealerRequestService::varietyOptions()),
        ]);
    }

    public function store(StoreDealerRequestRequest $request): RedirectResponse
    {
        $dealerId = $request->user()->dealerProfile->id;
        
        $this->service->create(
            dealerId: $dealerId,
            requestData: $request->only('transaction_date'),
            items: $request->input('items')
        );

        return redirect()->route('dealer.requests.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request posted successfully!']);
    }

    public function update(UpdateDealerRequestRequest $request, DealerRequest $dealerRequest): RedirectResponse
    {
        $request->user()->load('dealerProfile');
        
        Gate::authorize('update', $dealerRequest);

        $this->service->update(
            request: $dealerRequest,
            requestData: $request->only('transaction_date'),
            items: $request->input('items')
        );

        return redirect()->route('dealer.requests.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request updated successfully!']);
    }

    public function fulfill(DealerRequest $dealerRequest): RedirectResponse
    {
        Gate::authorize('markAsFulfilled', $dealerRequest);

        $this->service->markAsFulfilled($dealerRequest);

        return redirect()->route('dealer.requests.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request marked as fulfilled!']);
    }

    public function destroy(DealerRequest $dealerRequest): RedirectResponse
    {
        Gate::authorize('delete', $dealerRequest);

        $this->service->delete($dealerRequest);

        return redirect()->route('dealer.requests.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request deleted.']);
    }
}
