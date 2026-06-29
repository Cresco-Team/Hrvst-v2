<?php

use App\Http\Controllers\Api\VarietyAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('api')
    ->name('api.')
    ->group(function () {
        Route::get('/varieties/{variety}/slot-summary', [VarietyAvailabilityController::class, 'slotSummary'])
            ->name('varieties.slot-summary');
    });
