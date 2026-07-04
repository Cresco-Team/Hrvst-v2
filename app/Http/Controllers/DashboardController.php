<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('farmer')) {
            return $user->needsOnboarding()
                ? redirect()->route('farmer.supplies.index')
                : redirect()->route('farmer.dashboard');
        }

        if ($user->hasRole('dealer')) {
            return $user->needsOnboarding()
                ? redirect()->route('dealer.demands.index')
                : redirect()->route('dealer.dashboard');
        }

        return redirect()->route('categories');
    }
}
