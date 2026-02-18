<?php

use App\Http\Controllers\Dealer\RequestController;
use App\Http\Controllers\Dealer\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'dealer'])->prefix('dealer')->name('dealer.')->group(function () {
    
    // Dealer Requests (Manage own requests)
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [RequestController::class, 'index'])->name('index');
        Route::post('/', [RequestController::class, 'store'])->name('store');
        Route::put('/{dealerRequest}', [RequestController::class, 'update'])->name('update');
        Route::post('/{dealerRequest}/fulfill', [RequestController::class, 'fulfill'])->name('fulfill');
        Route::delete('/{dealerRequest}', [RequestController::class, 'destroy'])->name('destroy');
    });

    // Marketplace (Browse farmer offerings)
    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->name('index');
        Route::get('/{planting}', [MarketplaceController::class, 'show'])->name('show');
    });
});
