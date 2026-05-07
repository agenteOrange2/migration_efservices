<?php

use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\EmploymentVerificationController;
use App\Http\Controllers\PrivateMediaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::middleware('throttle:5,1')->post('/contact', [LandingPageController::class, 'submitContact'])->name('landing.contact.store');
Route::middleware('throttle:5,1')->post('/plan-request', [LandingPageController::class, 'submitPlanRequest'])->name('landing.plan-request.store');

Route::inertia('/terms', 'legal/Terms')->name('terms');
Route::inertia('/privacy', 'legal/Privacy')->name('privacy');

// Public employment verification (accessed via emailed link, no auth required)
Route::prefix('employment-verification')->name('employment-verification.')->group(function () {
    Route::get('/thank-you', [EmploymentVerificationController::class, 'thankYou'])->name('thank-you');
    Route::get('/expired',   [EmploymentVerificationController::class, 'expired'])->name('expired');
    Route::get('/error',     [EmploymentVerificationController::class, 'error'])->name('error');
    Route::middleware('throttle:20,1')->get('/{token}',  [EmploymentVerificationController::class, 'showVerificationForm'])->name('form');
    Route::middleware('throttle:5,1')->post('/{token}/process', [EmploymentVerificationController::class, 'processVerification'])->name('process');
});

// Authenticated route to serve private media files (PII documents, licenses, etc.)
Route::middleware('auth')->get('/private-media/{mediaId}', [PrivateMediaController::class, 'serve'])
    ->where('mediaId', '[0-9]+')
    ->name('private-media.serve');

require __DIR__.'/settings.php';
