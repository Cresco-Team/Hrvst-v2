<?php

use App\Http\Controllers\Announcement\CommentController;
use App\Http\Controllers\Announcement\FlagController;
use App\Http\Controllers\Announcement\ReactionController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = Auth::user();
        if ($user->hasRole('admin')) return redirect()->route('admin.dashboard');

        return Inertia::render('Dashboard');
    })->name('dashboard');
});

/* Announcement System - Shared Routes */
Route::middleware(['auth', 'verified'])->group(function () {
    // Reactions (toggle)
    Route::post('/reactions/toggle', [ReactionController::class, 'toggle'])
        ->name('reactions.toggle');
    Route::get('/reactions', [ReactionController::class, 'show'])
        ->name('reactions.show');

    // Comments
    Route::get('/offerings/{farmerOffering}/comments', [CommentController::class, 'index'])
        ->name('comments.index');
    Route::post('/offerings/{farmerOffering}/comments', [CommentController::class, 'store'])
        ->name('comments.store');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])
        ->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('comments.destroy');

    // Flags
    Route::post('/flags', [FlagController::class, 'store'])
        ->name('flags.store');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('notifications.unread_count');
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.mark_as_read');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark_all_as_read');
    Route::post('/notifications/mark-multiple-as-read', [NotificationController::class, 'markMultipleAsRead'])
        ->name('notifications.mark_multiple_as_read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])
        ->name('notifications.destroy');
    Route::delete('/notifications/read/all', [NotificationController::class, 'destroyRead'])
        ->name('notifications.destroy_read');
});

/* Development only */
if (app()->environment('local', 'development')) {
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::get('login/admin', function () {
            Auth::loginUsingId(1);
            return redirect()->route('admin.dashboard');
        })->name('login.admin');

        Route::get('login/farmer', function () {
            Auth::loginUsingId(2);
            return redirect()->route('farmer.requests.index');
        })->name('login.farmer');

        Route::get('login/dealer', function () {
            Auth::loginUsingId(3);
            return redirect()->route('dealer.marketplace.index');
        })->name('login.dealer');
    });
}

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/farmer.php';
require __DIR__.'/dealer.php';
