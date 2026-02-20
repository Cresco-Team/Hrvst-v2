<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\DealerDemand;
use App\Services\Farmer\DealerDemandService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DealerDemandController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'variety_id' => 'nullable|exists:varieties,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        return Inertia::render('farmer/marketplace/Index', [
            'filters' => $validated,
            'categoryOptions' => Inertia::defer(fn () => DealerDemandService::categoryOptions()),
            'demands' => Inertia::defer(fn () => DealerDemandService::paginated($validated)),
        ]);
    }

    public function show(DealerDemand $demand): Response
    {
        Gate::authorize('view', $demand);

        return Inertia::render('farmer/marketplace/Show', [
            'request' => DealerDemandService::detailed($demand),
        ]);
    }
}
