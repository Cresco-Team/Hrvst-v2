<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profiles\DealerProfile;
use App\Services\Admin\DealerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DealerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/dealers/Index', [
            'dealers' => DealerService::paginated(),
            'summary' => DealerService::summary(),
        ]);
    }

    public function show(int $id): Response
    {
        $dealer = DealerService::find($id);

        if (!$dealer) {
            abort(404, 'Dealer not found');
        }

        return Inertia::render('admin/dealers/Show', [
            'dealer' => $dealer,
        ]);
    }

    public function destroy(DealerProfile $dealerProfile)
    {
        // For future implementation if needed
    }
}
