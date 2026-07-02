<?php

use App\Http\Controllers\Shared\VegetableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['can:not-admin'])->group(function () {
    Route::get('/categories', [VegetableController::class, 'category'])->name('categories');

    Route::prefix('vegetables')->name('vegetables.')->group(function () {
        Route::get('/', [VegetableController::class, 'index'])->name('index');
        Route::get('/{vegetable}', [VegetableController::class, 'show'])->name('show');
    });
});
