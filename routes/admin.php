<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\FarmerController;
use App\Http\Controllers\Admin\FlagController;
use App\Http\Controllers\Admin\VarietyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::get('/vegetables-varieties', [VarietyController::class, 'index'])
        ->name('vegetables_varieties.index');
    Route::post('/vegetables-varieties', [VarietyController::class, 'store'])
        ->name('vegetables_varieties.store');
    Route::put('/vegetables-varieties/{variety}', [VarietyController::class, 'update'])
        ->name('vegetables_varieties.update');
    Route::delete('/vegetables-varieties/{variety}', [VarietyController::class, 'destroy'])
        ->name('vegetables_varieties.destroy');

    // Farmer Routes
    Route::get('/farmers', [FarmerController::class, 'index'])
        ->name('farmers.index');
    Route::get('/farmers/{farmer}', [FarmerController::class, 'show'])
        ->name('farmers.show');
    Route::delete('/farmers/{farmer}', [FarmerController::class, 'destroy'])
        ->name('farmers.destroy');
    
    // Farmer API Routes (AJAX)
    Route::get('/farmers/api/markers', [FarmerController::class, 'markers'])
        ->name('farmers.api.markers');
    Route::get('/farmers/api/{id}/details', [FarmerController::class, 'details'])
        ->name('farmers.api.details');
    
    // Farmer Approval Routes
    Route::post('/farmers/{id}/approve', [FarmerController::class, 'approve'])
        ->name('farmers.approve');
    Route::delete('/farmers/{id}/reject', [FarmerController::class, 'reject'])
        ->name('farmers.reject');

    Route::get('/dealers', [DealerController::class, 'index'])
        ->name('dealers.index');
    Route::get('/dealers/{dealer}', [DealerController::class, 'show'])
        ->name('dealers.show');
    Route::delete('/dealers/{dealer}', [DealerController::class, 'destroy'])
        ->name('dealers.destroy');

    // Content Moderation (Flags)
    Route::prefix('flags')->name('flags.')->group(function () {
        Route::get('/', [FlagController::class, 'index'])->name('index');
        Route::post('/{flag}/review', [FlagController::class, 'review'])->name('review');
        Route::post('/{flag}/dismiss', [FlagController::class, 'dismiss'])->name('dismiss');
        Route::delete('/{flag}/content', [FlagController::class, 'destroyContent'])->name('destroy-content');
    });
});
