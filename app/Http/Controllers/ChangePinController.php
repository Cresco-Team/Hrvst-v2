<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePinRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChangePinController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('auth/ChangePin');
    }

    public function update(ChangePinRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->pin,
            'must_change_pin' => false,
        ]);

        return redirect()->intended(route('dashboard'));
    }
}
