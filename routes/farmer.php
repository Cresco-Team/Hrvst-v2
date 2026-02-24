<?php

use App\Http\Controllers\Farmer\MarketplaceController;
use App\Http\Controllers\Farmer\SupplyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'farmer'])->prefix('farmer')->name('farmer.')->group(function () {
    
    Route::prefix('garden')->name('garden.')->group(function () {
        Route::get('/', [SupplyController::class, 'index'])->name('index');
        Route::post('/', [SupplyController::class, 'store'])->name('store');
        Route::put('/{offering}', [SupplyController::class, 'update'])->name('update');
        Route::post('/{offering}/archive', [SupplyController::class, 'archive'])->name('archive');
        Route::delete('/{offering}', [SupplyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->name('index');
        Route::get('/{demand}', [MarketplaceController::class, 'show'])->name('show');
    });
});
