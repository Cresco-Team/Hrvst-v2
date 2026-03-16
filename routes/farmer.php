<?php

use App\Http\Controllers\Farmer\MarketplaceController;
use App\Http\Controllers\Farmer\SupplyController;
use App\Http\Controllers\Farmer\SupplyMapController;
use App\Http\Controllers\Farmer\VegetablesController;
use Illuminate\Support\Facades\Route;

Route::post('/farmer/garden-test', function () {
    return response()->json(['hit' => true]);
});

Route::middleware(['auth', 'verified', 'farmer'])->prefix('farmer')->name('farmer.')->group(function () {
    
    Route::get('/vegetables', [VegetablesController::class, 'index'])->name('vegetables.index');

    Route::prefix('garden')->name('garden.')->group(function () {
        Route::get('/', [SupplyController::class, 'index'])->name('index');
        Route::post('/', [SupplyController::class, 'store'])->name('store');
        Route::put('/{supply}', [SupplyController::class, 'update'])->name('update');
        Route::post('/{supply}/archive', [SupplyController::class, 'archive'])->name('archive');
        Route::post('/{supply}/fulfill', [SupplyController::class, 'fulfill'])->name('fulfill');
        Route::delete('/{supply}', [SupplyController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('supply-map')->name('supply-map.')->group(function () {
        Route::get('/', [SupplyMapController::class, 'index'])->name('index');
        Route::get('/api/markers', [SupplyMapController::class, 'markers'])->name('markers');        
    });

    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->name('index');
    });
});
