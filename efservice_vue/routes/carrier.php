<?php

use App\Http\Controllers\Auth\CarrierWizardController;
use App\Http\Controllers\Auth\CarrierStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Carrier Wizard Routes (Guest)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::prefix('wizard')->name('wizard.')->group(function () {
        Route::get('/step1', [CarrierWizardController::class, 'showStep1'])->name('step1');
        Route::post('/step1', [CarrierWizardController::class, 'processStep1'])->name('step1.process');

        Route::post('/check-uniqueness', [CarrierWizardController::class, 'checkUniqueness'])
            ->name('check.uniqueness')
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    });

    Route::get('/register', fn () => redirect()->route('carrier.wizard.step1'))->name('register');
});

/*
|--------------------------------------------------------------------------
| Carrier Wizard Routes (Authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::prefix('wizard')->name('wizard.')->group(function () {
        Route::get('/step2', [CarrierWizardController::class, 'showStep2'])->name('step2');
        Route::post('/step2', [CarrierWizardController::class, 'processStep2'])->name('step2.process');
        Route::get('/step3', [CarrierWizardController::class, 'showStep3'])->name('step3');
        Route::post('/step3', [CarrierWizardController::class, 'processStep3'])->name('step3.process');
        Route::get('/step4', [CarrierWizardController::class, 'showStep4'])->name('step4');
        Route::post('/step4', [CarrierWizardController::class, 'processStep4'])->name('step4.process');
        Route::get('/check-verification', [CarrierWizardController::class, 'checkVerification'])->name('check.verification');
    });

    Route::get('/complete-registration', fn () => redirect()->route('carrier.wizard.step2'))->name('complete_registration');

    // Status pages
    Route::get('/pending-validation', [CarrierStatusController::class, 'pendingValidation'])->name('pending.validation');
    Route::get('/confirmation', [CarrierStatusController::class, 'showConfirmation'])->name('confirmation');
    Route::get('/inactive', [CarrierStatusController::class, 'showInactive'])->name('inactive');
    Route::get('/banking-rejected', [CarrierStatusController::class, 'showBankingRejected'])->name('banking.rejected');
    Route::post('/request-reactivation', [CarrierStatusController::class, 'requestReactivation'])->name('request.reactivation');
});

/*
|--------------------------------------------------------------------------
| Carrier Dashboard & Operations (Authenticated + Carrier Status)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'check.carrier.status'])->group(function () {
    // TODO: Dashboard, drivers, vehicles, etc. - will be migrated in later phases
});
