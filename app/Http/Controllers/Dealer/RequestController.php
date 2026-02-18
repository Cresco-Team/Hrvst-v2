<?php

namespace App\Http\Controllers\Dealer;

use App\DealerRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\StoreDealerRequestRequest;
use App\Http\Requests\Dealer\UpdateDealerRequestRequest;
use App\Models\Marketplace\DealerRequest;
use App\Services\Dealer\RequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends Controller
{
    public function __construct(
        private RequestService $service
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user()->load('dealerProfile');
        $dealerId = $user->dealerProfile->id;
        $status = DealerRequestStatus::tryFrom($request->query('status', DealerRequestStatus::Open->value));

        return Inertia::render('dealer/requests/Index', [
            'summary' => Inertia::defer(fn() => RequestService::summary($dealerId)),
            'varietyOptions' => Inertia::defer(fn() => RequestService::varietyOptions()),
            'filters' => ['status' => $status],
            'requests' => Inertia::defer(fn() => RequestService::paginated(
                dealerId: $dealerId,
                status: $status
            )),
            
            
        ]);
    }

    public function store(StoreDealerRequestRequest $request): RedirectResponse
    {
        $dealerId = $request->user()->dealerProfile->id;
        
        $this->service->create(
            dealerId: $dealerId,
            validated: $request->validated()
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
            validated: $request->validated(),
        );

        return redirect()->route('dealer.requests.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request updated successfully!']);
    }

    public function expire(DealerRequest $request): RedirectResponse
    {
        Gate::authorize('expire', $request);

        $this->service->expire($request);

        return redirect()->route('dealer.requests.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request expired.']);
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
