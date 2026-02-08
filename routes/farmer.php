<?php

use App\Http\Controllers\Farmer\FarmerOfferingController;
use App\Http\Controllers\Farmer\PlantingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'farmer'])->prefix('farmer')->name('farmer.')->group(function () {
    
    // Garden (Plantings Dashboard)
    Route::prefix('garden')->name('garden.')->group(function () {
        Route::get('/', [PlantingController::class, 'index'])
            ->name('index');
        Route::post('/', [PlantingController::class, 'store'])
            ->name('store');
        Route::put('/{planting}', [PlantingController::class, 'update'])
            ->name('update');
        Route::delete('/{planting}', [PlantingController::class, 'destroy'])
            ->name('destroy');
        
        // Custom actions
        Route::post('/{planting}/harvest', [PlantingController::class, 'harvest'])
            ->name('harvest');
        Route::post('/{planting}/cancel', [PlantingController::class, 'cancel'])
            ->name('cancel');
    });

    Route::prefix('offerings')->name('offerings.')->group(function () {
        Route::get('/', [FarmerOfferingController::class, 'index'])->name('index');
        Route::post('/', [FarmerOfferingController::class, 'store'])->name('store');
        Route::put('/{farmerOffering}', [FarmerOfferingController::class, 'update'])->name('update');
        Route::post('/{farmerOffering}/archive', [FarmerOfferingController::class, 'archive'])->name('archive');
        Route::delete('/{farmerOffering}', [FarmerOfferingController::class, 'destroy'])->name('destroy');
    });
});
