<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsFarmer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('farmer')) {
            abort(403, 'Access denied. Farmers only.');
        }

        // Ensure farmer has an approved profile
        if (!$user->farmerProfile || !$user->farmerProfile->is_approved) {
            abort(403, 'Your farmer profile is pending approval.');
        }

        return $next($request);
    }
}
