<?php

use App\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('billing')->name('billing.')->group(function () {
    Route::get('/', [BillingController::class, 'show'])->name('show');
    Route::post('/subscribe', [BillingController::class, 'subscribe'])->name('subscribe');
    Route::post('/cancel', [BillingController::class, 'cancel'])->name('cancel');
});
