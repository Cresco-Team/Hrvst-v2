<?php

use App\Http\Controllers\Farmer\DashboardController;
use App\Http\Controllers\Farmer\MarketplaceController;
use App\Http\Controllers\Farmer\PostItemController;
use App\Http\Controllers\Farmer\SupplyController;
use App\Http\Controllers\Farmer\SupplyMapController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'farmer'])->prefix('farmer')->name('farmer.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('supplies')->name('supplies.')->group(function () {
        Route::get('/', [SupplyController::class, 'index'])->name('index');
        Route::post('/', [SupplyController::class, 'store'])->name('store');
        Route::put('/{supply}', [SupplyController::class, 'update'])->name('update');
        Route::post('/{supply}/harvest', [SupplyController::class, 'harvest'])->name('harvest');
        Route::delete('/{supply}', [SupplyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('post-items')->name('post-items.')->group(function () {
        Route::post('/{postItem}/fulfill', [PostItemController::class, 'fulfill'])->name('fulfill');
        Route::post('/{postItem}/archive', [PostItemController::class, 'archive'])->name('archive');
        Route::delete('/{postItem}', [PostItemController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('supply-map')->name('supply-map.')->group(function () {
        Route::get('/', [SupplyMapController::class, 'index'])->name('index');
        Route::get('/api/markers', [SupplyMapController::class, 'markers'])->name('markers');
    });

    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->name('index');
    });
});
