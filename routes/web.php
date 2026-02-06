<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('admin')) return redirect()->route('admin.dashboard');

        return Inertia::render('Dashboard');
    })->name('dashboard');
});

/* Development only */
if (app()->environment('local', 'development')) {
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::get('login/admin', function () {
            Auth::loginUsingId(1);
            return redirect()->route('admin.dashboard');
        })->name('login.admin');

        Route::get('login/farmer', function () {
            Auth::loginUsingId(2);
            return redirect()->route('farmer.garden.index');
        })->name('login.farmer');

        Route::get('login/dealer', function () {
            Auth::loginUsingId(3);
            return redirect()->route('dealer.market');
        })->name('login.dealer');
    });
}

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/farmer.php';
require __DIR__.'/dealer.php';