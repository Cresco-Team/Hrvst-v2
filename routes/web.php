<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\ChangePinController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/address/barangays', [AddressController::class, 'barangays'])->name('address.barangays');

/* ---------- change PIN (must be accessible before is_verified check) ---------- */

Route::middleware(['auth'])->group(function () {
    Route::get('change-pin', [ChangePinController::class, 'show'])->name('change-pin.show');
    Route::post('change-pin', [ChangePinController::class, 'update'])->name('change-pin.update');
});

/* ---------- authenticated & verified ---------- */

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', function () {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('farmer')) {
            return redirect()->route('farmer.dashboard');
        }

        if ($user->hasRole('dealer')) {
            return redirect()->route('dealer.dashboard');
        }

        return redirect()->route('categories');
    })->name('dashboard');
});

/* ---------- development only ---------- */

if (app()->environment('local', 'development')) {
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::get('login/admin', function () {
            Auth::loginUsingId(1);

            return redirect()->route('dashboard');
        })->name('login.admin');

        Route::get('login/farmer', function () {
            Auth::loginUsingId(2);

            return redirect()->route('dashboard');
        })->name('login.farmer');

        Route::get('login/dealer', function () {
            Auth::loginUsingId(3);

            return redirect()->route('dashboard');
        })->name('login.dealer');
    });
}

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/farmer.php';
require __DIR__.'/dealer.php';
require __DIR__.'/shared.php';
require __DIR__.'/api-web.php';
