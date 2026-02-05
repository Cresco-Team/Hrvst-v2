<?php

use App\Http\Controllers\Dealer\MarketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'dealer'])->prefix('dealer')->name('dealer.')->group(function () {
    
    // Market Dashboard
    Route::get('/market', [MarketController::class, 'index'])
        ->name('market');
});
