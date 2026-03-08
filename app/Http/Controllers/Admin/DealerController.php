<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Dealer\ApproveDealerAction;
use App\Actions\Dealer\RejectDealerAction;
use App\Http\Controllers\Controller;
use App\Models\Profiles\DealerProfile;
use App\Services\Admin\DealerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class DealerController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', DealerProfile::class);

        return Inertia::render('admin/dealers/Index', [
            'summary' => Inertia::defer(fn () => DealerService::summary()),
            'dealers' => Inertia::defer(fn () => DealerService::paginated()),
        ]);
    }

    public function details(int $id, ): JsonResponse
    {
        $dealer = DealerService::details($id);

        if (! $dealer) {
            return response()->json(['error' => 'Dealer not found']);
        }

        $dealerProfile = DealerProfile::findOrFail($id);
        Gate::authorize('view', $dealerProfile);

        return response()->json($dealer);
    }

    public function show(int $id): Response
    {
        $dealerProfile = DealerProfile::findOrFail($id);
        Gate::authorize('view', $dealerProfile);

        $dealer = DealerService::show($id);

        if (!$dealer) {
            abort(404, 'Dealer not found');
        }

        return Inertia::render('admin/dealers/Show', [
            'dealer' => $dealer,
        ]);
    }

    public function pending(): JsonResponse
    {
        Gate::authorize('viewAny', DealerProfile::class);

        return response()->json(DealerService::pending());
    }

    public function approve(DealerProfile $dealer, ApproveDealerAction $approveDealer): RedirectResponse
    {
        Gate::authorize('approve', DealerProfile::class);

        $approveDealer($dealer);

        return back()
            ->with('flash', [
                'type' => 'success',
                'message' => 'Dealer Approved.'
            ]);
    }

    public function reject(DealerProfile $dealer, RejectDealerAction $rejectDealer): RedirectResponse
    {
        Gate::authorize('reject', DealerProfile::class);

        $rejectDealer($dealer);

        return back()
            ->with('flash', [
                'type' => 'success',
                'message' => 'Dealer Rejected and Deleted.'
            ]);
    }

    public function destroy(DealerProfile $dealerProfile): RedirectResponse
    {
        Gate::authorize('delete', $dealerProfile);

        $user = $dealerProfile->user;
        $dealerProfile->delete();
        $user->delete();

        return redirect()->route('admin.dealers.index')
            ->with('flash', [
                'type' => 'success',
                'message' => 'Dealer deleted successfully.',
            ]);
    }

    public function document(DealerProfile $dealer): HttpFoundationResponse
    {
        Gate::authorize('viewAny', DealerProfile::class);

        $media = $dealer->getFirstMedia('document');

        if (! $media) {
            abort(404, 'Document not found.');
        }

        return $media->toResponse(request());
    }
}
