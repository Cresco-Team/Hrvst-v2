<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FarmerController;
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

    Route::get('/farmers', [FarmerController::class, 'index'])
        ->name('farmers.index');
    Route::get('/farmers/{farmer}', [FarmerController::class, 'show'])
        ->name('farmers.show');
    Route::delete('/farmers/{farmer}', [FarmerController::class, 'destroy'])
        ->name('farmers.destroy');
});