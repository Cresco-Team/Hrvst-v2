<?php

namespace App\Http\Controllers;

use App\Actions\RegistrationRequest\CreateRegistrationRequestAction;
use App\Http\Requests\StoreRegistrationRequestRequest;
use App\Models\Address\Municipality;
use Illuminate\Http\RedirectResponse;
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

    public function store(StoreRegistrationRequestRequest $request, CreateRegistrationRequestAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('home')->with('flash', [
            'type' => 'success',
            'message' => 'Request submitted.',
        ]);
    }
}
