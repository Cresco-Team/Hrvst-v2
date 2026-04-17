<?php

use App\Http\Controllers\Shared\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['can:not-admin'])->group(function () {
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
    });
});
