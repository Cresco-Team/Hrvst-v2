<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            endpoint: $validated['endpoint'],
            publicKey: $validated['keys']['p256dh'],
            authToken: $validated['keys']['auth'],
        );

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $endpoint = $request->validate(['endpoint' => ['required', 'string']])['endpoint'];

        $request->user()->pushSubscriptions()->where('endpoint', $endpoint)->delete();

        return back();
    }
}
