<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profiles\DealerProfile;
use App\Services\Admin\DealerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DealerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/dealers/Index', [
            'summary' => Inertia::defer(fn () => DealerService::summary()),
            'dealers' => Inertia::defer(fn () => DealerService::paginated()),
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

    public function pending(): JsonResponse
    {
        return response()->json(DealerService::pending());
    }

    public function approve(int $dealer): RedirectResponse
    {
        abort_if(! DealerService::approve($dealer), 404);

        return back();
    }

    public function reject(int $dealer): RedirectResponse
    {
        abort_if(! DealerService::reject($dealer), 404);

        return back();
    }

    public function destroy(int $dealer): RedirectResponse
    {
        $profile = \App\Models\Profiles\DealerProfile::where('is_approved', true)->findOrFail($dealer);
        $user = $profile->user;
        $profile->delete();
        $user->delete();

        return back();
    }
}
