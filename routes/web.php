<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\ChangePinController;
use App\Http\Controllers\PostHeartController;
use App\Http\Controllers\VarietyHeartController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Laravel\Fortify\Http\Controllers\ProfileInformationController;

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
    Route::post('varieties/{variety}/heart', [VarietyHeartController::class, 'toggle'])->name('varieties.heart.toggle');
    Route::post('posts/{post}/heart', [PostHeartController::class, 'toggle'])->name('posts.heart.toggle');

    Route::get('dashboard', function () {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('categories.index');
    })->name('dashboard');
});

/* ---------- development only ---------- */

if (app()->environment('local', 'development')) {
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::get('login/admin', function () {
            Auth::loginUsingId(1);

            return redirect()->route('admin.dashboard');
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

Route::put('/user/password', [PasswordController::class, 'update'])
    ->name('user-password.update-password');

Route::put('/user/profile-information', [ProfileInformationController::class, 'update'])
    ->name('user-profile.update-info');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/farmer.php';
require __DIR__.'/dealer.php';
require __DIR__.'/shared.php';
