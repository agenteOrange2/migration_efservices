<?php

use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\EmploymentVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::post('/contact', [LandingPageController::class, 'submitContact'])->name('landing.contact.store');
Route::post('/plan-request', [LandingPageController::class, 'submitPlanRequest'])->name('landing.plan-request.store');

Route::inertia('/terms', 'legal/Terms')->name('terms');
Route::inertia('/privacy', 'legal/Privacy')->name('privacy');

// Public employment verification (accessed via emailed link, no auth required)
Route::prefix('employment-verification')->name('employment-verification.')->group(function () {
    Route::get('/thank-you', [EmploymentVerificationController::class, 'thankYou'])->name('thank-you');
    Route::get('/expired',   [EmploymentVerificationController::class, 'expired'])->name('expired');
    Route::get('/error',     [EmploymentVerificationController::class, 'error'])->name('error');
    Route::get('/{token}',   [EmploymentVerificationController::class, 'showVerificationForm'])->name('form');
    Route::post('/{token}/process', [EmploymentVerificationController::class, 'processVerification'])->name('process');
});

require __DIR__.'/settings.php';
