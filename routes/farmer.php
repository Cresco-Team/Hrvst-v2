<?php

use App\Http\Controllers\Farmer\DealerDemandController;
use App\Http\Controllers\Farmer\OfferingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'farmer'])->prefix('farmer')->name('farmer.')->group(function () {
    
    Route::prefix('garden')->name('garden.')->group(function () {
        Route::get('/', [OfferingController::class, 'index'])->name('index');
        Route::post('/', [OfferingController::class, 'store'])->name('store');
        Route::put('/{offering}', [OfferingController::class, 'update'])->name('update');
        Route::post('/{offering}/archive', [OfferingController::class, 'archive'])->name('archive');
        Route::delete('/{offering}', [OfferingController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [DealerDemandController::class, 'index'])->name('index');
        Route::get('/{demand}', [DealerDemandController::class, 'show'])->name('show');
    });
});
