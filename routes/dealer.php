<?php

use App\Http\Controllers\Dealer\DashboardController;
use App\Http\Controllers\Dealer\DemandController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'dealer'])->prefix('dealer')->name('dealer.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::prefix('items')->name('items.')->group(function () {
            Route::post('/{postItem}/fulfill', [DashboardController::class, 'fulfillItem'])->name('fulfill');
            Route::post('/{postItem}/expire', [DashboardController::class, 'expireItem'])->name('expire');
        });
    });

    Route::prefix('demands')->name('demands.')->group(function () {
        Route::get('/', [DemandController::class, 'index'])->name('index');
        Route::get('/archived', [DemandController::class, 'archived'])->name('archived'); // must be before /{demand}
        Route::post('/', [DemandController::class, 'store'])->name('store');
        Route::put('/{demand}', [DemandController::class, 'update'])->name('update');
        Route::delete('/{demand}', [DemandController::class, 'destroy'])->name('destroy');
    });
});
