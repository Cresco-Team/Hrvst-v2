<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\FarmerController;
use App\Http\Controllers\Admin\FlagController;
use App\Http\Controllers\Admin\VegetableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::prefix('vegetables')->name('vegetables.')->group(function () {
        Route::get('/', [VegetableController::class, 'index'])->name('index');
        Route::get('/{vegetable}/details', [VegetableController::class, 'details'])->name('details');
        Route::post('/', [VegetableController::class, 'store'])->name('store');
        Route::put('/{vegetable}', [VegetableController::class, 'update'])->name('update');
        Route::delete('/{vegetable}', [VegetableController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('farmers')->name('farmers.')->group(function () {
        Route::get('/', [FarmerController::class, 'index'])->name('index');
        Route::get('/{farmer}', [FarmerController::class, 'show'])->name('show');
        Route::delete('{farmer}', [FarmerController::class, 'destroy'])->name('destroy');

        // API Route (AJAX)
        Route::prefix('api')->name('api.')->group(function () {
            // Map
            Route::get('/markers', [FarmerController::class, 'markers'])->name('markers');
            Route::get('/{farmer}/details', [FarmerController::class, 'details'])->name('details');
            // Pending
            Route::get('/pending', [FarmerController::class, 'pending'])->name('pending');
        });

        // Pending Farmers
        Route::post('/{farmer}/approve', [FarmerController::class, 'approve'])->name('approve');
        Route::post('/{farmer}/reject', [FarmerController::class, 'reject'])->name('reject');
    });
    
    Route::prefix('dealers')->name('dealers.')->group(function () {
        Route::get('/', [DealerController::class, 'index'])->name('index');
        Route::get('/{dealer}', [DealerController::class, 'show'])->name('show');
        Route::delete('/{dealer}', [DealerController::class, 'destroy'])->name('destroy');
        Route::get('/{dealer}/document', [DealerController::class, 'document'])->name('document');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/{dealer}/details', [DealerController::class, 'details'])->name('details');
            Route::get('/pending', [DealerController::class, 'pending'])->name('pending');
        });
        
        // Pending Dealers
        Route::post('/{dealer}/approve', [DealerController::class, 'approve'])->name('approve');
        Route::post('/{dealer}/reject', [DealerController::class, 'reject'])->name('reject');
    });

    // Content Moderation (Flags)
    Route::prefix('flags')->name('flags.')->group(function () {
        Route::get('/', [FlagController::class, 'index'])->name('index');
        Route::post('/{flag}/review', [FlagController::class, 'review'])->name('review');
        Route::post('/{flag}/dismiss', [FlagController::class, 'dismiss'])->name('dismiss');
        Route::delete('/{flag}/content', [FlagController::class, 'destroyContent'])->name('destroy-content');
    });
});
