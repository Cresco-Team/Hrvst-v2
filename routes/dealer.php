<?php

use App\Http\Controllers\Dealer\DealerRequestController;
use App\Http\Controllers\Dealer\MarketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'dealer'])->prefix('dealer')->name('dealer.')->group(function () {
    
    // Market Dashboard
    Route::get('/market', [MarketController::class, 'index'])
        ->name('market');

    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [DealerRequestController::class, 'index'])->name('index');
        Route::post('/', [DealerRequestController::class, 'store'])->name('store');
        Route::put('/{dealerRequest}', [DealerRequestController::class, 'update'])->name('update');
        Route::post('/{dealerRequest}/fulfill', [DealerRequestController::class, 'fulfill'])->name('fulfill');
        Route::delete('/{dealerRequest}', [DealerRequestController::class, 'destroy'])->name('destroy');
    });
});
