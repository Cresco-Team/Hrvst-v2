<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            // Immediate load - critical KPIs
            'kpis' => DashboardService::getKPIs(),
            
            // Deferred load - chart data (heavy aggregations)
            'charts' => Inertia::defer(fn () => DashboardService::getChartData()),
        ]);
    }
}
