<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\FarmerController;
use App\Http\Controllers\Admin\FlagController;
use App\Http\Controllers\Admin\VarietyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard Route
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    // Vegetables Routes
    Route::prefix('vegetable-varieties')->name('vegetable_varieties.')->group(function () {
        Route::get('/', [VarietyController::class, 'index'])->name('index');
        Route::post('/', [VarietyController::class, 'store'])->name('store');
        Route::put('/{variety}', [VarietyController::class, 'update'])->name('update');
        Route::delete('/{variety}', [VarietyController::class, 'destroy'])->name('destroy');
    });

    // Farmers Routes
    Route::prefix('farmers')->name('farmers.')->group(function () {
        Route::get('/', [FarmerController::class, 'index'])->name('index');
        Route::get('/{farmer}', [FarmerController::class, 'show'])->name('show');
        Route::delete('{farmer}', [FarmerController::class, 'destroy'])->name('destroy');

        // API Route (AJAX)
        Route::prefix('api')->name('api.')->group(function() {
            Route::get('/markers', [FarmerController::class, 'markers'])->name('markers');
            Route::get('/{id}/details', [FarmerController::class, 'details'])->name('details');
        });

        // Pending Farmers
        Route::post('/{farmer}/approve', [FarmerController::class, 'approve'])->name('approve');
        Route::post('/{farmer}/reject', [FarmerController::class, 'approve'])->name('reject');
    });
    
    // Dealers Route
    Route::prefix('dealers')->name('dealers.')->group(function () {
        Route::get('/', [DealerController::class, 'index'])->name('index');
        Route::get('/{dealer}', [DealerController::class, 'show'])->name('show');
        Route::delete('/{dealer}', [DealerController::class, 'destroy'])->name('destroy');
    });

    // Content Moderation (Flags)
    Route::prefix('flags')->name('flags.')->group(function () {
        Route::get('/', [FlagController::class, 'index'])->name('index');
        Route::post('/{flag}/review', [FlagController::class, 'review'])->name('review');
        Route::post('/{flag}/dismiss', [FlagController::class, 'dismiss'])->name('dismiss');
        Route::delete('/{flag}/content', [FlagController::class, 'destroyContent'])->name('destroy-content');
    });
});
