<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'farmer'])->prefix('farmer')->name('farmer.')->group(function () {
    //
});