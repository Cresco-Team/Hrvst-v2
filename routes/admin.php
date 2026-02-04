<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VarietyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::get('/vegetables-varieties', [VarietyController::class, 'index'])
        ->name('vegetables_varieties.index');
    Route::post('/vegetables_varieties', [VarietyController::class, 'store'])
        ->name('vegetables_varieties.store');
    Route::put('/vegetables-varieties/{variety}', [VarietyController::class, 'update'])
        ->name('vegetables_varieties.update');
    Route::delete('/vegetables-varieties/{variety}', [VarietyController::class, 'destroy'])
        ->name('vegetables_varieties.destroy');
});