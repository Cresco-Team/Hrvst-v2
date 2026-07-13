<?php

namespace App\Http\Middleware;

use App\Enums\Billing\SubscriptionFeature;
use App\Models\Billing\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $feature = SubscriptionFeature::from($feature);

        if (! Subscription::hasAccess($request->user(), $feature)) {
            return redirect()->route('billing.show')->with('flash', [
                'type' => 'warning',
                'message' => 'This feature requires an active subscription.',
            ]);
        }

        return $next($request);
    }
}
