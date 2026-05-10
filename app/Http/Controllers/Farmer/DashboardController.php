<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Marketplace\FarmerSupplyResource;
use App\Services\Farmer\FarmerDashboardService;
use App\Services\Farmer\SupplyService;
use Illuminate\Http\Request;
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
                fn () => FarmerSupplyResource::collection(
                    $this->dashboardService->expiringSupplies($profile->user_id)
                )
            ),

            'recommendations' => Inertia::defer(
                fn () => array_map(
                    fn ($rec) => $rec->toArray(),
                    $this->dashboardService->recommendations($profile->user_id)
                )
            ),
        ]);
    }
}
