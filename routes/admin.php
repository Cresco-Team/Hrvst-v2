<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VegetableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::get('/vegetables', [VegetableController::class, 'index'])
        ->name('vegetables.index');
});