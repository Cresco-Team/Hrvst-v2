<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDealer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('dealer')) {
            abort(403, 'Access denied. Dealers only.');
        }

        // Ensure dealer has an approved profile
        if (!$user->dealerProfile || !$user->dealerProfile->is_approved) {
            abort(403, 'Your dealer profile is pending approval.');
        }

        return $next($request);
    }
}
