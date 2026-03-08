<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboardService): Response
    {
        return Inertia::render('admin/Dashboard', [
            'kpis' => Inertia::defer(fn () => $dashboardService->getKPIs()),
        ]);
    }
}
