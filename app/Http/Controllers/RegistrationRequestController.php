<?php

namespace App\Http\Controllers;

use App\Actions\RegistrationRequest\ApproveRegistrationRequestAction;
use App\Actions\RegistrationRequest\CreateRegistrationRequestAction;
use App\Http\Requests\StoreRegistrationRequestRequest;
use App\Models\Address\Municipality;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register', [
            'municipalities' => Municipality::orderBy('name')
                ->get(['id', 'name', 'latitude', 'longitude']),
        ]);
    }

    public function store(StoreRegistrationRequestRequest $request, CreateRegistrationRequestAction $action, ApproveRegistrationRequestAction $approveRequest): RedirectResponse
    {
        $registrationRequest = $action->handle($request->validated());

        // TEMPORARY — testing bypass. See AUTO_APPROVE_REGISTRATIONS in .env.
        // Remove this branch (and the flag) before relying on real admin review.
        if (config('app.auto_approve_registrations')) {
            $user = $approveRequest->handle($registrationRequest, reviewer: null);

            Auth::login($user);

            return redirect()->route('dashboard');
        }

        return redirect()->route('home')->with('flash', [
            'type' => 'success',
            'message' => 'Request submitted.',
        ]);
    }
}
