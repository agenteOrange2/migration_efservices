<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\NotificationActionsController;
use App\Http\Controllers\QuickSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');
    Route::get('quick-search', QuickSearchController::class)->name('search.quick');
    Route::prefix('api/notifications')->name('notifications.api.')->group(function () {
        Route::post('read-all', [NotificationActionsController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('{notification}/read', [NotificationActionsController::class, 'markAsRead'])->name('mark-read');
        Route::post('{notification}/unread', [NotificationActionsController::class, 'markAsUnread'])->name('mark-unread');
        Route::delete('{notification}', [NotificationActionsController::class, 'destroy'])->name('destroy');
    });

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});
