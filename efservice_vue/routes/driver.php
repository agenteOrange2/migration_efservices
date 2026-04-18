<?php

use App\Http\Controllers\Auth\DriverRegistrationController;
use App\Http\Controllers\Driver\DriverApplicationWizardController;
use App\Http\Controllers\Driver\DriverDashboardController;
use App\Http\Controllers\Driver\DriverDocumentController;
use App\Http\Controllers\Driver\DriverEmergencyRepairController;
use App\Http\Controllers\Driver\DriverHosController;
use App\Http\Controllers\Driver\DriverHosCycleController;
use App\Http\Controllers\Driver\DriverHosDocumentController;
use App\Http\Controllers\Driver\DriverInspectionController;
use App\Http\Controllers\Driver\DriverLicenseController;
use App\Http\Controllers\Driver\DriverMaintenanceController;
use App\Http\Controllers\Driver\DriverMedicalController;
use App\Http\Controllers\Driver\MessagesController;
use App\Http\Controllers\Driver\DriverProfileController;
use App\Http\Controllers\Driver\DriverTestingController;
use App\Http\Controllers\Driver\DriverTripController;
use App\Http\Controllers\Driver\DriverTrainingController;
use App\Http\Controllers\Driver\DriverVehicleController;
use App\Http\Controllers\Driver\NotificationsController as DriverNotificationsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver Registration Routes (Guest)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('driver-register/{carrier:slug}', [DriverRegistrationController::class, 'showRegistrationForm'])
        ->name('driver.register');

    Route::post('driver-register/{carrier:slug}', [DriverRegistrationController::class, 'register'])
        ->name('driver.register.submit');

    Route::get('/register', [DriverRegistrationController::class, 'showIndependentCarrierSelection'])
        ->name('register.select');

    Route::get('/register/form/{carrier_slug}', [DriverRegistrationController::class, 'showIndependentRegistrationForm'])
        ->name('register.form');

    Route::post('/register', [DriverRegistrationController::class, 'registerIndependent'])
        ->name('register.independent');

    Route::get('confirm/{token}', [DriverRegistrationController::class, 'confirmEmail'])
        ->name('confirm');

    Route::get('registration/success', fn () => inertia('driver/registration/Success'))
        ->name('registration.success');

    Route::get('error', fn () => inertia('driver/registration/Error'))
        ->name('register.error');

    Route::get('quota-exceeded', fn () => inertia('driver/registration/QuotaExceeded'))
        ->name('quota-exceeded');

    Route::get('driver-status', fn () => inertia('driver/registration/DriverStatus'))
        ->name('status');
});

/*
|--------------------------------------------------------------------------
| Driver Status Pages (Authenticated, no driver-status check)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('pending', fn () => inertia('driver/registration/DriverStatus'))
        ->name('pending');

    Route::get('complete-registration', fn () => inertia('driver/registration/Register'))
        ->name('complete_registration');
});

/*
|--------------------------------------------------------------------------
| Driver Dashboard & Operations (Authenticated + Driver Status)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'check.driver.status'])->group(function () {
    Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');
    Route::get('profile', [DriverDashboardController::class, 'profile'])->name('profile');
    Route::get('profile/download-documents', [DriverDashboardController::class, 'downloadDocuments'])->name('profile.download-documents');

    /*
    |--------------------------------------------------------------------------
    | Driver Application Wizard
    |--------------------------------------------------------------------------
    | These routes allow the driver to fill their own application after
    | registration. They mirror the admin wizard but are scoped to the
    | authenticated driver only.
    |
    | Alias: registration/continue → application/wizard (used by middleware)
    */
    Route::get('registration/continue', [DriverApplicationWizardController::class, 'show'])
        ->name('registration.continue');

    Route::prefix('application')->name('application.')->group(function () {
        // GET – show wizard (driver resolved from auth, step from ?step=N)
        Route::get('wizard', [DriverApplicationWizardController::class, 'show'])
            ->name('wizard');

        // PUT|POST – save a single step (step 1 uses method-spoofing POST)
        Route::match(['put', 'post'], 'wizard/{driver}/{step}', [DriverApplicationWizardController::class, 'updateStep'])
            ->name('wizard.update-step');

        // Employment verification helpers
        Route::prefix('employment')->name('employment.')->group(function () {
            Route::get('search-companies', [DriverApplicationWizardController::class, 'employmentSearchCompanies'])
                ->name('search-companies');
            Route::post('{driver}/employment/{company}/send-email', [DriverApplicationWizardController::class, 'employmentSendEmail'])
                ->name('send-email');
            Route::post('{driver}/employment/{company}/resend-email', [DriverApplicationWizardController::class, 'employmentResendEmail'])
                ->name('resend-email');
            Route::post('{driver}/employment/{company}/mark-email-status', [DriverApplicationWizardController::class, 'employmentMarkEmailStatus'])
                ->name('mark-email-status');
        });
    });

    Route::prefix('licenses')->name('licenses.')->group(function () {
        Route::get('/', [DriverLicenseController::class, 'index'])->name('index');
        Route::get('/{license}', [DriverLicenseController::class, 'show'])->name('show');
    });

    Route::prefix('medical')->name('medical.')->group(function () {
        Route::get('/', [DriverMedicalController::class, 'index'])->name('index');
    });

    Route::prefix('testing')->name('testing.')->group(function () {
        Route::get('/', [DriverTestingController::class, 'index'])->name('index');
        Route::get('/{testing}', [DriverTestingController::class, 'show'])->name('show');
        Route::post('/{testing}/upload-results', [DriverTestingController::class, 'uploadResults'])->name('upload-results');
    });

    Route::prefix('inspections')->name('inspections.')->group(function () {
        Route::get('/', [DriverInspectionController::class, 'index'])->name('index');
        Route::get('/{inspection}', [DriverInspectionController::class, 'show'])->name('show');
    });

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessagesController::class, 'index'])->name('index');
        Route::get('/{messageRecipient}', [MessagesController::class, 'show'])->name('show');
        Route::post('/{messageRecipient}/reply', [MessagesController::class, 'reply'])->name('reply');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [DriverNotificationsController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [DriverNotificationsController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/', [DriverNotificationsController::class, 'deleteAll'])->name('delete-all');
        Route::post('/{notification}/mark-as-read', [DriverNotificationsController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/{notification}/mark-as-unread', [DriverNotificationsController::class, 'markAsUnread'])->name('mark-as-unread');
        Route::delete('/{notification}', [DriverNotificationsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DriverDocumentController::class, 'index'])->name('index');
        Route::get('/pending', [DriverDocumentController::class, 'pending'])->name('pending');
        Route::get('/upload', [DriverDocumentController::class, 'create'])->name('create');
        Route::post('/upload', [DriverDocumentController::class, 'store'])->name('store');
        Route::get('/download-all', [DriverDocumentController::class, 'downloadAll'])->name('download-all');
        Route::delete('/{media}', [DriverDocumentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('hos')->name('hos.')->group(function () {
        Route::get('/', [DriverHosController::class, 'dashboard'])->name('dashboard');
        Route::post('/status', [DriverHosController::class, 'changeStatus'])->name('status.change');
        Route::get('/history', [DriverHosController::class, 'history'])->name('history');
        Route::put('/entries/{entry}', [DriverHosController::class, 'updateEntry'])->name('entries.update');
        Route::delete('/entries/{entry}', [DriverHosController::class, 'deleteEntry'])->name('entries.destroy');
        Route::post('/entries/bulk-delete', [DriverHosController::class, 'bulkDeleteEntries'])->name('entries.bulk-destroy');

        Route::prefix('cycle')->name('cycle.')->group(function () {
            Route::get('/', [DriverHosCycleController::class, 'index'])->name('index');
            Route::post('/request', [DriverHosCycleController::class, 'requestChange'])->name('request');
            Route::post('/cancel', [DriverHosCycleController::class, 'cancelRequest'])->name('cancel');
        });

        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [DriverHosDocumentController::class, 'index'])->name('index');
            Route::post('/generate-daily-log', [DriverHosDocumentController::class, 'generateDailyLog'])->name('generate-daily-log');
            Route::post('/generate-monthly-summary', [DriverHosDocumentController::class, 'generateMonthlySummary'])->name('generate-monthly-summary');
            Route::post('/generate-fmcsa-monthly', [DriverHosDocumentController::class, 'generateFmcsaMonthly'])->name('generate-fmcsa-monthly');
            Route::get('/{media}/preview', [DriverHosDocumentController::class, 'preview'])->name('preview');
            Route::get('/{media}/download', [DriverHosDocumentController::class, 'download'])->name('download');
        });
    });

    Route::prefix('trainings')->name('trainings.')->group(function () {
        Route::get('/', [DriverTrainingController::class, 'index'])->name('index');
        Route::get('/documents/{media}', [DriverTrainingController::class, 'previewDocument'])->name('documents.preview');
        Route::post('/{assignment}/start-progress', [DriverTrainingController::class, 'startProgress'])->name('start-progress');
        Route::post('/{assignment}/complete', [DriverTrainingController::class, 'complete'])->name('complete');
        Route::get('/{assignment}', [DriverTrainingController::class, 'show'])->name('show');
    });

    Route::prefix('trips')->name('trips.')->group(function () {
        Route::get('/', [DriverTripController::class, 'index'])->name('index');
        Route::get('/pending-count', [DriverTripController::class, 'pendingCount'])->name('pending.count');
        Route::get('/create', [DriverTripController::class, 'create'])->name('create');
        Route::post('/', [DriverTripController::class, 'store'])->name('store');
        Route::post('/quick-store', [DriverTripController::class, 'storeQuickTrip'])->name('quick-store');
        Route::get('/{trip}', [DriverTripController::class, 'show'])->name('show');
        Route::post('/{trip}/accept', [DriverTripController::class, 'accept'])->name('accept');
        Route::post('/{trip}/reject', [DriverTripController::class, 'reject'])->name('reject');
        Route::get('/{trip}/start', [DriverTripController::class, 'startForm'])->name('start.form');
        Route::post('/{trip}/start', [DriverTripController::class, 'start'])->name('start');
        Route::post('/{trip}/pause', [DriverTripController::class, 'pause'])->name('pause');
        Route::post('/{trip}/resume', [DriverTripController::class, 'resume'])->name('resume');
        Route::get('/{trip}/end', [DriverTripController::class, 'endForm'])->name('end.form');
        Route::post('/{trip}/end', [DriverTripController::class, 'end'])->name('end');
        Route::post('/{trip}/documents', [DriverTripController::class, 'uploadDocuments'])->name('documents.upload');
        Route::delete('/{trip}/documents/{media}', [DriverTripController::class, 'deleteDocument'])->name('documents.delete');
        Route::get('/{trip}/documents/{media}/download', [DriverTripController::class, 'downloadDocument'])->name('documents.download');
        Route::get('/{trip}/documents/{media}/preview', [DriverTripController::class, 'previewDocument'])->name('documents.preview');
    });

    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/', [DriverVehicleController::class, 'index'])->name('index');
        Route::get('/{vehicle}', [DriverVehicleController::class, 'show'])->name('show');

        Route::get('/{vehicle}/maintenance', [DriverMaintenanceController::class, 'vehicleIndex'])->name('maintenance.index');
        Route::get('/{vehicle}/maintenance/create', [DriverMaintenanceController::class, 'createForVehicle'])->name('maintenance.create');
        Route::get('/{vehicle}/repairs', [DriverEmergencyRepairController::class, 'vehicleIndex'])->name('repairs.index');
        Route::get('/{vehicle}/repairs/create', [DriverEmergencyRepairController::class, 'createForVehicle'])->name('repairs.create');
    });

    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', [DriverMaintenanceController::class, 'index'])->name('index');
        Route::get('/create', [DriverMaintenanceController::class, 'create'])->name('create');
        Route::post('/', [DriverMaintenanceController::class, 'store'])->name('store');
        Route::get('/calendar', [DriverMaintenanceController::class, 'calendar'])->name('calendar');
        Route::get('/reports', [DriverMaintenanceController::class, 'reports'])->name('reports');
        Route::get('/{maintenance}', [DriverMaintenanceController::class, 'show'])->name('show');
        Route::get('/{maintenance}/edit', [DriverMaintenanceController::class, 'edit'])->name('edit');
        Route::put('/{maintenance}', [DriverMaintenanceController::class, 'update'])->name('update');
        Route::delete('/{maintenance}', [DriverMaintenanceController::class, 'destroy'])->name('destroy');
        Route::put('/{maintenance}/toggle-status', [DriverMaintenanceController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{maintenance}/reschedule', [DriverMaintenanceController::class, 'reschedule'])->name('reschedule');
        Route::post('/{maintenance}/generate-report', [DriverMaintenanceController::class, 'generateReport'])->name('generate-report');
        Route::delete('/{maintenance}/reports/{document}', [DriverMaintenanceController::class, 'deleteReport'])->name('delete-report');
        Route::post('/{maintenance}/attachments', [DriverMaintenanceController::class, 'storeDocuments'])->name('attachments.store');
        Route::delete('/{maintenance}/attachments/{media}', [DriverMaintenanceController::class, 'deleteDocument'])->name('attachments.destroy');
    });

    Route::prefix('emergency-repairs')->name('emergency-repairs.')->group(function () {
        Route::get('/', [DriverEmergencyRepairController::class, 'index'])->name('index');
        Route::get('/create', [DriverEmergencyRepairController::class, 'create'])->name('create');
        Route::post('/', [DriverEmergencyRepairController::class, 'store'])->name('store');
        Route::get('/{emergencyRepair}', [DriverEmergencyRepairController::class, 'show'])->name('show');
        Route::get('/{emergencyRepair}/edit', [DriverEmergencyRepairController::class, 'edit'])->name('edit');
        Route::put('/{emergencyRepair}', [DriverEmergencyRepairController::class, 'update'])->name('update');
        Route::delete('/{emergencyRepair}', [DriverEmergencyRepairController::class, 'destroy'])->name('destroy');
        Route::post('/{emergencyRepair}/generate-report', [DriverEmergencyRepairController::class, 'generateSingleReport'])->name('generate-report');
        Route::delete('/{emergencyRepair}/reports/{document}', [DriverEmergencyRepairController::class, 'deleteRepairReport'])->name('delete-report');
        Route::post('/{emergencyRepair}/attachments', [DriverEmergencyRepairController::class, 'uploadDocument'])->name('attachments.store');
        Route::delete('/{emergencyRepair}/attachments/{media}', [DriverEmergencyRepairController::class, 'deleteFile'])->name('attachments.destroy');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/edit', [DriverProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [DriverProfileController::class, 'update'])->name('update');
        Route::post('/update-photo', [DriverProfileController::class, 'updatePhoto'])->name('update-photo');
        Route::delete('/delete-photo', [DriverProfileController::class, 'deletePhoto'])->name('delete-photo');
        Route::put('/update-password', [DriverProfileController::class, 'updatePassword'])->name('update-password');
    });
});
