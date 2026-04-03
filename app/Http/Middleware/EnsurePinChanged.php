<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePinChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->must_change_pin
            && ! $request->routeIs('change-pin.*')
            && ! $request->routeIs('logout')
        ) {
            return redirect()->route('change-pin.show');
        }

        return $next($request);
    }
}
