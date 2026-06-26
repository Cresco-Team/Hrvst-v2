<?php

namespace App\Http\Controllers\Dealer;

use App\Data\Dealer\DealerExpiringDemandData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Marketplace\DealerDemandResource;
use App\Services\Dealer\DealerDashboardService;
use App\Services\Dealer\DemandService;
use Illuminate\Http\Request;
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
            'summary' => Inertia::defer(
                fn () => $this->demandService->summary($profile->user_id)
            ),

            'expiringDemands' => Inertia::defer(
                fn () => DealerExpiringDemandData::collect($this->dashboardService->expiringDemands($profile))
            ),

            'recommendations' => Inertia::defer(
                fn () => array_map(
                    fn ($rec) => $rec->toArray(),
                    $this->dashboardService->recommendations($profile)
                )
            ),
        ]);
    }
}
