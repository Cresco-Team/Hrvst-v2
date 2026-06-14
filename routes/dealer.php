<?php

use App\Http\Controllers\Dealer\DashboardController;
use App\Http\Controllers\Dealer\DemandController;
use App\Http\Controllers\Dealer\PostItemController;
use App\Http\Controllers\Dealer\SupplyMapController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'dealer'])->prefix('dealer')->name('dealer.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('demands')->name('demands.')->group(function () {
        Route::get('/', [DemandController::class, 'index'])->name('index');
        Route::post('/', [DemandController::class, 'store'])->name('store');
        Route::put('/{demand}', [DemandController::class, 'update'])->name('update');
        Route::delete('/{demand}', [DemandController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('post-items')->name('post-items.')->group(function () {
        Route::put('/{postItem}', [PostItemController::class, 'update'])->name('update');
        Route::post('/{postItem}/fulfill', [PostItemController::class, 'fulfill'])->name('fulfill');
        Route::post('/{postItem}/archive', [PostItemController::class, 'archive'])->name('archive');
        Route::delete('/{postItem}', [PostItemController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('supply-map')->name('supply-map.')->group(function () {
        Route::get('/', [SupplyMapController::class, 'index'])->name('index');
        Route::get('/api/markers', [SupplyMapController::class, 'markers'])->name('markers');
    });
});
