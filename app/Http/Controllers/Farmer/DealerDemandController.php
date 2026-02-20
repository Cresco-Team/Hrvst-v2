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
    /**
     * Browse dealer requests (opportunities)
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'variety_id' => 'nullable|exists:varieties,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        return Inertia::render('farmer/requests/Index', [
            // Synchronous (immediate load)
            'filters' => $validated,
            
            // Deferred (load after page renders)
            'requests' => Inertia::defer(fn() => 
                DealerDemandService::paginated($validated)
            ),
            
            'filterOptions' => Inertia::defer(fn() => [
                'categories' => DealerDemandService::categoryOptions(),
            ]),
        ]);
    }

    /**
     * View single dealer request details
     */
    public function show(DealerDemand $dealerDemand): Response
    {
        Gate::authorize('view', $dealerDemand);

        return Inertia::render('farmer/requests/Show', [
            'request' => DealerDemandService::detailed($dealerDemand),
        ]);
    }
}
