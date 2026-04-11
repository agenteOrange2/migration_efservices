<?php

use App\Http\Controllers\Auth\CarrierWizardController;
use App\Http\Controllers\Auth\CarrierStatusController;
use App\Http\Controllers\Admin\Driver\DriverAdminWizardController;
use App\Http\Controllers\Admin\Driver\DriverEmploymentController;
use App\Http\Controllers\Carrier\CarrierDashboardController;
use App\Http\Controllers\Carrier\CarrierDocumentController;
use App\Http\Controllers\Carrier\CarrierDriverController;
use App\Http\Controllers\Carrier\CarrierInactiveDriversController;
use App\Http\Controllers\Carrier\CarrierLicenseController;
use App\Http\Controllers\Carrier\CarrierMedicalRecordsController;
use App\Http\Controllers\Carrier\CarrierCoursesController;
use App\Http\Controllers\Carrier\CarrierTrainingSchoolsController;
use App\Http\Controllers\Carrier\CarrierTrainingsController;
use App\Http\Controllers\Carrier\CarrierTrainingAssignmentsController;
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
    Route::get('/dashboard', [CarrierDashboardController::class, 'index'])->name('dashboard');

    // Documents
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [CarrierDocumentController::class, 'index'])->name('index');
        Route::post('/upload/{documentTypeId}', [CarrierDocumentController::class, 'upload'])->name('upload');
        Route::delete('/delete/{documentTypeId}', [CarrierDocumentController::class, 'deleteDocument'])->name('delete');
    });

    Route::prefix('drivers')->name('drivers.')->group(function () {
        Route::prefix('inactive')->name('inactive.')->group(function () {
            Route::get('/', [CarrierInactiveDriversController::class, 'index'])->name('index');
            Route::get('/{archive}', [CarrierInactiveDriversController::class, 'show'])->name('show')->middleware('log.archive.access');
            Route::get('/{archive}/download', [CarrierInactiveDriversController::class, 'download'])->name('download')->middleware('log.archive.access');
        });

        Route::get('/', [CarrierDriverController::class, 'index'])->name('index');
        Route::get('/create', [DriverAdminWizardController::class, 'create'])->name('create');
        Route::post('/', [DriverAdminWizardController::class, 'store'])->name('store');

        Route::get('/employment/search-companies', [DriverEmploymentController::class, 'searchCompanies'])->name('employment.search-companies');
        Route::post('/{driver}/employment/{company}/send-email', [DriverEmploymentController::class, 'sendEmail'])->name('employment.send-email');
        Route::post('/{driver}/employment/{company}/resend-email', [DriverEmploymentController::class, 'resendEmail'])->name('employment.resend-email');
        Route::post('/{driver}/employment/{company}/mark-email-status', [DriverEmploymentController::class, 'markEmailStatus'])->name('employment.mark-email-status');

        Route::get('/{driver}/documents/download', [CarrierDriverController::class, 'downloadDocuments'])->name('documents.download');
        Route::get('/{driver}/edit', [DriverAdminWizardController::class, 'edit'])->name('edit');
        Route::put('/{driver}/wizard/{step}', [DriverAdminWizardController::class, 'updateStep'])->name('wizard.update-step');
        Route::put('/{driver}/activate', [CarrierDriverController::class, 'activate'])->name('activate');
        Route::put('/{driver}/deactivate', [CarrierDriverController::class, 'deactivate'])->name('deactivate');
        Route::get('/{driver}', [CarrierDriverController::class, 'show'])->name('show');
        Route::delete('/{driver}', [CarrierDriverController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('licenses')->name('licenses.')->group(function () {
        Route::get('/', [CarrierLicenseController::class, 'index'])->name('index');
        Route::get('/create', [CarrierLicenseController::class, 'create'])->name('create');
        Route::post('/', [CarrierLicenseController::class, 'store'])->name('store');
        Route::get('/documents', [CarrierLicenseController::class, 'documents'])->name('documents.index');
        Route::delete('/media/{media}', [CarrierLicenseController::class, 'destroyMedia'])->name('media.destroy');
        Route::get('/{license}/documents', [CarrierLicenseController::class, 'showDocuments'])->name('documents.show');
        Route::get('/{license}/edit', [CarrierLicenseController::class, 'edit'])->name('edit');
        Route::put('/{license}', [CarrierLicenseController::class, 'update'])->name('update');
        Route::get('/{license}', [CarrierLicenseController::class, 'show'])->name('show');
        Route::delete('/{license}', [CarrierLicenseController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('medical-records')->name('medical-records.')->group(function () {
        Route::get('/', [CarrierMedicalRecordsController::class, 'index'])->name('index');
        Route::get('/create', [CarrierMedicalRecordsController::class, 'create'])->name('create');
        Route::post('/', [CarrierMedicalRecordsController::class, 'store'])->name('store');
        Route::delete('/media/{media}', [CarrierMedicalRecordsController::class, 'destroyMedia'])->name('media.destroy');
        Route::get('/{medical_record}/documents', [CarrierMedicalRecordsController::class, 'showDocuments'])->name('documents.show');
        Route::get('/{medical_record}/edit', [CarrierMedicalRecordsController::class, 'edit'])->name('edit');
        Route::put('/{medical_record}', [CarrierMedicalRecordsController::class, 'update'])->name('update');
        Route::get('/{medical_record}', [CarrierMedicalRecordsController::class, 'show'])->name('show');
        Route::delete('/{medical_record}', [CarrierMedicalRecordsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('training-schools')->name('training-schools.')->group(function () {
        Route::get('/', [CarrierTrainingSchoolsController::class, 'index'])->name('index');
        Route::get('/create', [CarrierTrainingSchoolsController::class, 'create'])->name('create');
        Route::post('/', [CarrierTrainingSchoolsController::class, 'store'])->name('store');
        Route::get('/documents', [CarrierTrainingSchoolsController::class, 'documents'])->name('documents.index');
        Route::delete('/media/{media}', [CarrierTrainingSchoolsController::class, 'destroyMedia'])->name('media.destroy');
        Route::get('/{training_school}/documents', [CarrierTrainingSchoolsController::class, 'showDocuments'])->name('documents.show');
        Route::get('/{training_school}/edit', [CarrierTrainingSchoolsController::class, 'edit'])->name('edit');
        Route::put('/{training_school}', [CarrierTrainingSchoolsController::class, 'update'])->name('update');
        Route::get('/{training_school}', [CarrierTrainingSchoolsController::class, 'show'])->name('show');
        Route::delete('/{training_school}', [CarrierTrainingSchoolsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [CarrierCoursesController::class, 'index'])->name('index');
        Route::get('/create', [CarrierCoursesController::class, 'create'])->name('create');
        Route::post('/', [CarrierCoursesController::class, 'store'])->name('store');
        Route::get('/documents', [CarrierCoursesController::class, 'documents'])->name('documents.index');
        Route::delete('/media/{media}', [CarrierCoursesController::class, 'destroyMedia'])->name('media.destroy');
        Route::get('/{course}/documents', [CarrierCoursesController::class, 'showDocuments'])->name('documents.show');
        Route::get('/{course}/edit', [CarrierCoursesController::class, 'edit'])->name('edit');
        Route::put('/{course}', [CarrierCoursesController::class, 'update'])->name('update');
        Route::delete('/{course}', [CarrierCoursesController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('trainings')->name('trainings.')->group(function () {
        Route::delete('/media/{media}', [CarrierTrainingsController::class, 'destroyMedia'])->name('media.destroy');
        Route::get('/select-for-assignment', [CarrierTrainingAssignmentsController::class, 'assignSelect'])->name('assign.select');
        Route::get('/{training}/assign', [CarrierTrainingAssignmentsController::class, 'createForTraining'])->name('assign.form');
        Route::post('/{training}/assign', [CarrierTrainingAssignmentsController::class, 'storeForTraining'])->name('assign');
        Route::get('/', [CarrierTrainingsController::class, 'index'])->name('index');
        Route::get('/create', [CarrierTrainingsController::class, 'create'])->name('create');
        Route::post('/', [CarrierTrainingsController::class, 'store'])->name('store');
        Route::get('/{training}', [CarrierTrainingsController::class, 'show'])->name('show');
        Route::get('/{training}/edit', [CarrierTrainingsController::class, 'edit'])->name('edit');
        Route::put('/{training}', [CarrierTrainingsController::class, 'update'])->name('update');
        Route::delete('/{training}', [CarrierTrainingsController::class, 'destroy'])->name('destroy');
    });
});
