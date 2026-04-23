<?php

use App\Http\Controllers\Shared\CategoryController;
use App\Http\Controllers\Shared\VegetableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['can:not-admin'])->prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');

    Route::prefix('{category}')->name('vegetables.')->group(function () {
        Route::get('/', [VegetableController::class, 'index'])->name('index');
        Route::get('/{variety}', [VegetableController::class, 'show'])->name('show');
    });
});
