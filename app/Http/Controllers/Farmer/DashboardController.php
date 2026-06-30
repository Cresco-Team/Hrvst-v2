<?php

namespace App\Http\Controllers\Farmer;

use App\Actions\PostItem\ExpirePostItemAction;
use App\Actions\PostItem\FulfillPostItemAction;
use App\Data\Farmer\FarmerExpiringSupplyData;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\PostItem;
use App\Services\Farmer\FarmerDashboardService;
use App\Services\Farmer\SupplyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SupplyService $supplyService,
        private readonly FarmerDashboardService $dashboardService,
    ) {}

    public function index(Request $request): Response
    {
        $profile = $request->user()->farmerProfile;

        return Inertia::render('farmer/Dashboard', [
            'summary' => Inertia::defer(
                fn () => $this->supplyService->summary($profile->user_id)
            ),

            'expiringSupplies' => Inertia::defer(
                fn () => FarmerExpiringSupplyData::collect($this->dashboardService->expiringSupplies($profile->user_id))
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

        abort_if($postItem->post->type !== PostType::Supply, 403);

        $action->handle($postItem);

        return back(fallback: route('farmer.dashboard'))
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as fulfilled.']);
    }

    public function expireItem(PostItem $postItem, ExpirePostItemAction $action): RedirectResponse
    {
        $postItem->load('post');
        Gate::authorize('expire', $postItem);

        abort_if($postItem->post->type !== PostType::Supply, 403);

        $action->handle($postItem);

        return back(fallback: route('farmer.dashboard'))
            ->with('flash', ['type' => 'success', 'message' => 'Item marked as expired.']);
    }
}
