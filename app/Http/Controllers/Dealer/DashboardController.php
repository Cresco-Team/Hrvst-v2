<?php

namespace App\Http\Controllers\Dealer;

use App\Actions\PostItem\ExpirePostItemAction;
use App\Actions\PostItem\FulfillPostItemAction;
use App\Data\Dealer\DealerExpiringDemandData;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\PostItem;
use App\Services\Dealer\DealerDashboardService;
use App\Services\Dealer\DemandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DemandService $demandService,
        private readonly DealerDashboardService $dashboardService,
    ) {}

    public function index(Request $request): Response
    {
        $profile = $request->user()->dealerProfile;

        return Inertia::render('dealer/Dashboard', [
            'expiringDemands' => Inertia::defer(
                fn () => DealerExpiringDemandData::collect($this->dashboardService->expiringDemands($profile->user_id))
            ),

            'recommendations' => Inertia::defer(
                fn () => array_map(
                    fn ($rec) => $rec->toArray(),
                    $this->dashboardService->recommendations($profile->user_id)
                )
            ),
        ]);
    }

    public function fulfillItem(PostItem $postItem, FulfillPostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('fulfill', $postItem);

        abort_if($postItem->post->type !== PostType::Demand, 403);

        $action->handle($postItem);

        return back(fallback: route('dealer.dashboard'))
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as fulfilled.']);
    }

    public function expireItem(PostItem $postItem, ExpirePostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('expire', $postItem);

        abort_if($postItem->post->type !== PostType::Demand, 403);

        $action->handle($postItem);

        return back(fallback: route('dealer.dashboard'))
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as expired.']);
    }
}
