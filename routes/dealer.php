<?php

use App\Http\Controllers\Dealer\DemandController;
use App\Http\Controllers\Dealer\MarketplaceController;
use App\Http\Controllers\Dealer\SupplyMapController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'dealer'])->prefix('dealer')->name('dealer.')->group(function () {

    Route::prefix('demands')->name('demands.')->group(function () {
        Route::get('/', [DemandController::class, 'index'])->name('index');
        Route::post('/', [DemandController::class, 'store'])->name('store');
        Route::put('/{demand}', [DemandController::class, 'update'])->name('update');
        Route::post('/{demand}/archive', [DemandController::class, 'archive'])->name('archive');
        Route::post('/{demand}/fulfill', [DemandController::class, 'fulfill'])->name('fulfill');
        Route::delete('/{demand}', [DemandController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('supply-map')->name('supply-map.')->group(function () {
        Route::get('/', [SupplyMapController::class, 'index'])->name('index');
        Route::get('/api/markers', [SupplyMapController::class, 'markers'])->name('markers');
    });

    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->name('index');
    });
});
