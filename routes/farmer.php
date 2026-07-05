<?php

use App\Http\Controllers\Farmer\DashboardController;
use App\Http\Controllers\Farmer\PostItemController;
use App\Http\Controllers\Farmer\SupplyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'farmer'])->prefix('farmer')->name('farmer.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('supplies')->name('supplies.')->group(function () {
        Route::get('/', [SupplyController::class, 'index'])->name('index');
        Route::get('/archived', [SupplyController::class, 'archived'])->name('archived');
        Route::post('/', [SupplyController::class, 'store'])->name('store');
        Route::put('/{supply}', [SupplyController::class, 'update'])->name('update');
        Route::delete('/{supply}', [SupplyController::class, 'destroy'])->name('destroy');

        Route::prefix('items')->name('items.')->group(function () {
            Route::post('/{postItem}/fulfill', [PostItemController::class, 'fulfill'])->name('fulfill');
            Route::post('/{postItem}/expire', [PostItemController::class, 'expire'])->name('expire');
            Route::delete('/{postItem}', [PostItemController::class, 'destroy'])->name('destroy');
        });
    });
});
