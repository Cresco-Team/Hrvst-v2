<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\VarietyHeartController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/address/barangays', [AddressController::class, 'barangays'])->name('address.barangays');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('varieties/{variety}/heart', [VarietyHeartController::class, 'toggle'])->name('varieties.heart.toggle');

    Route::get('dashboard', function () {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('dealer') && $user->dealerProfile?->is_approved === true) {
            return redirect()->route('dealer.marketplace.index');
        }

        if ($user->hasRole('farmer') && $user->farmerProfile?->is_approved === true) {
            return redirect()->route('farmer.marketplace.index');
        }

        return Inertia::render('Welcome');
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

            return redirect()->route('farmer.supplies.index');
        })->name('login.farmer');

        Route::get('login/dealer', function () {
            Auth::loginUsingId(3);

            return redirect()->route('dealer.marketplace.index');
        })->name('login.dealer');
    });
}

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/farmer.php';
require __DIR__.'/dealer.php';
