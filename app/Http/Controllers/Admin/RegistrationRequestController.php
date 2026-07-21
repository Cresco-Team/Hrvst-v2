<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RegistrationRequest\ApproveRegistrationRequestAction;
use App\Actions\RegistrationRequest\RejectRegistrationRequestAction;
use App\Enums\RegistrationRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\RegistrationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationRequestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/registration-requests/Index', [
            'requests' => Inertia::defer(fn () => RegistrationRequest::query()
                ->where('status', RegistrationRequestStatus::Pending)
                ->with(['municipality', 'barangay'])
                ->latest()
                ->get()
            ),
        ]);
    }

    public function approve(RegistrationRequest $registrationRequest, ApproveRegistrationRequestAction $action, Request $request): RedirectResponse
    {
        $action->handle($registrationRequest, $request->user());

        return back()->with('flash', ['type' => 'success', 'message' => 'Applicant approved and account created.']);
    }

    public function reject(RegistrationRequest $registrationRequest, RejectRegistrationRequestAction $action, Request $request): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;

        $action->handle($registrationRequest, $request->user(), $reason);

        return back()->with('flash', ['type' => 'success', 'message' => 'Request rejected.']);
    }
}
