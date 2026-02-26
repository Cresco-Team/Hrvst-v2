<?php

use App\Http\Controllers\Dealer\DemandController;
use App\Http\Controllers\Dealer\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'dealer'])->prefix('dealer')->name('dealer.')->group(function () {
    
    // Dealer Requests (Manage own demands)
    Route::prefix('demands')->name('demands.')->group(function () {
        Route::get('/', [DemandController::class, 'index'])->name('index');
        Route::post('/', [DemandController::class, 'store'])->name('store');
        Route::put('/{demand}', [DemandController::class, 'update'])->name('update');
        Route::post('/{demand}/fulfill', [DemandController::class, 'fulfill'])->name('fulfill');
        Route::delete('/{demand}', [DemandController::class, 'destroy'])->name('destroy');
    });

    // Marketplace (Browse farmer offerings)
    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->name('index');
        Route::get('/{supply}', [MarketplaceController::class, 'show'])->name('show');
    });
});
