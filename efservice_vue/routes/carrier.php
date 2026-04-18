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
use App\Http\Controllers\Carrier\CarrierMaintenanceController;
use App\Http\Controllers\Carrier\CarrierEmergencyRepairController;
use App\Http\Controllers\Carrier\CarrierProfileController;
use App\Http\Controllers\Carrier\CarrierCoursesController;
use App\Http\Controllers\Carrier\CarrierDriverAccidentsController;
use App\Http\Controllers\Carrier\MessagesController;
use App\Http\Controllers\Carrier\CarrierTrainingSchoolsController;
use App\Http\Controllers\Carrier\CarrierTrainingsController;
use App\Http\Controllers\Carrier\CarrierTrainingAssignmentsController;
use App\Http\Controllers\Carrier\CarrierTrafficController;
use App\Http\Controllers\Carrier\CarrierDriverTestingsController;
use App\Http\Controllers\Carrier\CarrierDriverInspectionsController;
use App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController;
use App\Http\Controllers\Carrier\CarrierDriverHosController;
use App\Http\Controllers\Carrier\CarrierHosController;
use App\Http\Controllers\Carrier\CarrierHosDocumentController;
use App\Http\Controllers\Carrier\CarrierReportsController;
use App\Http\Controllers\Carrier\CarrierTripController;
use App\Http\Controllers\Carrier\CarrierVehicleController;
use App\Http\Controllers\Carrier\CarrierVehicleDocumentController;
use App\Http\Controllers\Carrier\CarrierVehicleMakeController;
use App\Http\Controllers\Carrier\CarrierVehicleTypeController;
use App\Http\Controllers\Carrier\NotificationsController as CarrierNotificationsController;
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
            ->name('check.uniqueness');
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
    Route::get('/profile', [CarrierProfileController::class, 'index'])->name('profile');
    Route::get('/profile/edit', [CarrierProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [CarrierProfileController::class, 'update'])->name('profile.update');

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

        Route::prefix('hos')->name('hos.')->group(function () {
            Route::get('/', [CarrierDriverHosController::class, 'index'])->name('index');
            Route::put('/{driver}', [CarrierDriverHosController::class, 'update'])->name('update');
            Route::post('/{driver}/approve', [CarrierDriverHosController::class, 'approveRequest'])->name('approve');
            Route::post('/{driver}/reject', [CarrierDriverHosController::class, 'rejectRequest'])->name('reject');
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

    Route::prefix('carrier-driver-accidents')->name('drivers.accidents.')->group(function () {
        Route::get('/', [CarrierDriverAccidentsController::class, 'index'])->name('index');
        Route::get('/create', [CarrierDriverAccidentsController::class, 'create'])->name('create');
        Route::post('/', [CarrierDriverAccidentsController::class, 'store'])->name('store');
        Route::get('/driver/{driver}', [CarrierDriverAccidentsController::class, 'driverHistory'])->name('driver_history');
        Route::get('/documents', [CarrierDriverAccidentsController::class, 'documents'])->name('documents.index');
        Route::get('/{accident}/documents', [CarrierDriverAccidentsController::class, 'showDocuments'])->name('documents.show');
        Route::delete('/documents/{document}', [CarrierDriverAccidentsController::class, 'destroyDocument'])->name('documents.destroy');
        Route::delete('/media/{mediaId}', [CarrierDriverAccidentsController::class, 'destroyMedia'])->name('media.destroy');
        Route::get('/{accident}/edit', [CarrierDriverAccidentsController::class, 'edit'])->name('edit');
        Route::put('/{accident}', [CarrierDriverAccidentsController::class, 'update'])->name('update');
        Route::delete('/{accident}', [CarrierDriverAccidentsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('traffic')->name('drivers.traffic.')->group(function () {
        Route::get('/', [CarrierTrafficController::class, 'index'])->name('index');
        Route::get('/create', [CarrierTrafficController::class, 'create'])->name('create');
        Route::post('/', [CarrierTrafficController::class, 'store'])->name('store');
        Route::get('/{conviction}/edit', [CarrierTrafficController::class, 'edit'])->name('edit');
        Route::put('/{conviction}', [CarrierTrafficController::class, 'update'])->name('update');
        Route::delete('/{conviction}', [CarrierTrafficController::class, 'destroy'])->name('destroy');
        Route::get('/{conviction}/documents', [CarrierTrafficController::class, 'showDocuments'])->name('documents');
        Route::post('/{conviction}/documents', [CarrierTrafficController::class, 'showDocuments'])->name('documents.store');
        Route::get('/driver/{driver}/history', [CarrierTrafficController::class, 'driverHistory'])->name('driver.history');
        Route::delete('/documents/{mediaId}', [CarrierTrafficController::class, 'destroyMedia'])->name('documents.delete');
    });

    Route::prefix('carrier-driver-testings')->name('drivers.testings.')->group(function () {
        Route::get('/', [CarrierDriverTestingsController::class, 'index'])->name('index');
        Route::get('/create', [CarrierDriverTestingsController::class, 'create'])->name('create');
        Route::post('/', [CarrierDriverTestingsController::class, 'store'])->name('store');
        Route::get('/driver/{driver}/history', [CarrierDriverTestingsController::class, 'driverHistory'])->name('driver-history');
        Route::get('/{testing}/download-pdf', [CarrierDriverTestingsController::class, 'downloadPdf'])->name('download-pdf');
        Route::post('/{testing}/regenerate-pdf', [CarrierDriverTestingsController::class, 'regeneratePdf'])->name('regenerate-pdf');
        Route::post('/{testing}/attachments', [CarrierDriverTestingsController::class, 'uploadAttachment'])->name('upload-attachment');
        Route::delete('/{testing}/attachments/{media}', [CarrierDriverTestingsController::class, 'deleteAttachment'])->name('delete-attachment');
        Route::get('/{testing}/edit', [CarrierDriverTestingsController::class, 'edit'])->name('edit');
        Route::put('/{testing}', [CarrierDriverTestingsController::class, 'update'])->name('update');
        Route::get('/{testing}', [CarrierDriverTestingsController::class, 'show'])->name('show');
        Route::delete('/{testing}', [CarrierDriverTestingsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('carrier-driver-inspections')->name('drivers.inspections.')->group(function () {
        Route::get('/', [CarrierDriverInspectionsController::class, 'index'])->name('index');
        Route::get('/create', [CarrierDriverInspectionsController::class, 'create'])->name('create');
        Route::post('/', [CarrierDriverInspectionsController::class, 'store'])->name('store');
        Route::get('/documents', [CarrierDriverInspectionsController::class, 'documents'])->name('documents.index');
        Route::get('/driver/{driver}/history', [CarrierDriverInspectionsController::class, 'driverHistory'])->name('driver-history');
        Route::get('/driver/{driver}/documents', [CarrierDriverInspectionsController::class, 'driverDocuments'])->name('driver-documents');
        Route::get('/driver/{driver}/vehicles', [CarrierDriverInspectionsController::class, 'getVehiclesByDriver'])->name('vehicles.by.driver');
        Route::get('/{inspection}/edit', [CarrierDriverInspectionsController::class, 'edit'])->name('edit');
        Route::put('/{inspection}', [CarrierDriverInspectionsController::class, 'update'])->name('update');
        Route::delete('/{inspection}', [CarrierDriverInspectionsController::class, 'destroy'])->name('destroy');
        Route::delete('/media/{media}', [CarrierDriverInspectionsController::class, 'destroyMedia'])->name('media.destroy');
    });

    Route::get('vehicles-documents', [CarrierVehicleDocumentController::class, 'overview'])->name('vehicles-documents.index');

    Route::prefix('driver-vehicle-management')->name('driver-vehicle-management.')->group(function () {
        Route::get('/', [CarrierDriverVehicleManagementController::class, 'index'])->name('index');
        Route::get('/{driver}', [CarrierDriverVehicleManagementController::class, 'show'])->name('show');
        Route::get('/{driver}/assign-vehicle', [CarrierDriverVehicleManagementController::class, 'assignVehicle'])->name('assign-vehicle');
        Route::post('/{driver}/assign-vehicle', [CarrierDriverVehicleManagementController::class, 'storeVehicleAssignment'])->name('store-vehicle-assignment');
        Route::get('/{driver}/edit-assignment', [CarrierDriverVehicleManagementController::class, 'editAssignment'])->name('edit-assignment');
        Route::put('/{driver}/edit-assignment', [CarrierDriverVehicleManagementController::class, 'updateAssignment'])->name('update-assignment');
        Route::post('/{driver}/cancel-assignment', [CarrierDriverVehicleManagementController::class, 'cancelAssignment'])->name('cancel-assignment');
        Route::get('/{driver}/assignment-history', [CarrierDriverVehicleManagementController::class, 'assignmentHistory'])->name('assignment-history');
        Route::get('/{driver}/contact', [CarrierDriverVehicleManagementController::class, 'contact'])->name('contact');
        Route::post('/{driver}/contact', [CarrierDriverVehicleManagementController::class, 'sendContact'])->name('send-contact');
    });

    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', [CarrierMaintenanceController::class, 'index'])->name('index');
        Route::get('/create', [CarrierMaintenanceController::class, 'create'])->name('create');
        Route::post('/', [CarrierMaintenanceController::class, 'store'])->name('store');
        Route::get('/calendar', [CarrierMaintenanceController::class, 'calendar'])->name('calendar');
        Route::get('/reports', [CarrierMaintenanceController::class, 'reports'])->name('reports');
        Route::get('/{maintenance}', [CarrierMaintenanceController::class, 'show'])->name('show');
        Route::get('/{maintenance}/edit', [CarrierMaintenanceController::class, 'edit'])->name('edit');
        Route::put('/{maintenance}', [CarrierMaintenanceController::class, 'update'])->name('update');
        Route::delete('/{maintenance}', [CarrierMaintenanceController::class, 'destroy'])->name('destroy');
        Route::put('/{maintenance}/toggle-status', [CarrierMaintenanceController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{maintenance}/reschedule', [CarrierMaintenanceController::class, 'reschedule'])->name('reschedule');
        Route::post('/{maintenance}/attachments', [CarrierMaintenanceController::class, 'storeDocuments'])->name('attachments.store');
        Route::delete('/{maintenance}/attachments/{media}', [CarrierMaintenanceController::class, 'deleteDocument'])->name('attachments.destroy');
        Route::post('/{maintenance}/generate-report', [CarrierMaintenanceController::class, 'generateReport'])->name('generate-report');
        Route::delete('/{maintenance}/generated-reports/{document}', [CarrierMaintenanceController::class, 'deleteReport'])->name('delete-report');
    });

    Route::prefix('emergency-repairs')->name('emergency-repairs.')->group(function () {
        Route::get('/', [CarrierEmergencyRepairController::class, 'index'])->name('index');
        Route::get('/create', [CarrierEmergencyRepairController::class, 'create'])->name('create');
        Route::post('/', [CarrierEmergencyRepairController::class, 'store'])->name('store');
        Route::get('/{emergencyRepair}', [CarrierEmergencyRepairController::class, 'show'])->name('show');
        Route::get('/{emergencyRepair}/edit', [CarrierEmergencyRepairController::class, 'edit'])->name('edit');
        Route::put('/{emergencyRepair}', [CarrierEmergencyRepairController::class, 'update'])->name('update');
        Route::delete('/{emergencyRepair}', [CarrierEmergencyRepairController::class, 'destroy'])->name('destroy');
        Route::post('/{emergencyRepair}/attachments', [CarrierEmergencyRepairController::class, 'uploadDocument'])->name('attachments.store');
        Route::delete('/{emergencyRepair}/attachments/{media}', [CarrierEmergencyRepairController::class, 'deleteFile'])->name('attachments.destroy');
        Route::post('/{emergencyRepair}/generate-report', [CarrierEmergencyRepairController::class, 'generateSingleReport'])->name('generate-report');
        Route::delete('/{emergencyRepair}/reports/{document}', [CarrierEmergencyRepairController::class, 'deleteRepairReport'])->name('delete-report');
    });

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessagesController::class, 'index'])->name('index');
        Route::get('/dashboard', [MessagesController::class, 'dashboard'])->name('dashboard');
        Route::get('/create', [MessagesController::class, 'create'])->name('create');
        Route::post('/', [MessagesController::class, 'store'])->name('store');
        Route::get('/{message}', [MessagesController::class, 'show'])->name('show');
        Route::get('/{message}/edit', [MessagesController::class, 'edit'])->name('edit');
        Route::put('/{message}', [MessagesController::class, 'update'])->name('update');
        Route::delete('/{message}', [MessagesController::class, 'destroy'])->name('destroy');
        Route::delete('/{message}/recipients/{recipient}', [MessagesController::class, 'removeRecipient'])->name('remove-recipient');
        Route::post('/{message}/duplicate', [MessagesController::class, 'duplicate'])->name('duplicate');
        Route::post('/{message}/resend', [MessagesController::class, 'resend'])->name('resend');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [CarrierNotificationsController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [CarrierNotificationsController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/', [CarrierNotificationsController::class, 'deleteAll'])->name('delete-all');
        Route::post('/{notification}/mark-as-read', [CarrierNotificationsController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/{notification}/mark-as-unread', [CarrierNotificationsController::class, 'markAsUnread'])->name('mark-as-unread');
        Route::delete('/{notification}', [CarrierNotificationsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('trips')->name('trips.')->group(function () {
        Route::get('/', [CarrierTripController::class, 'index'])->name('index');
        Route::get('/dashboard', [CarrierTripController::class, 'dashboard'])->name('dashboard');
        Route::get('/create', [CarrierTripController::class, 'create'])->name('create');
        Route::post('/', [CarrierTripController::class, 'store'])->name('store');
        Route::get('/carrier-data', [CarrierTripController::class, 'getCarrierData'])->name('carrier.data');
        Route::get('/{trip}', [CarrierTripController::class, 'show'])->name('show');
        Route::get('/{trip}/edit', [CarrierTripController::class, 'edit'])->name('edit');
        Route::put('/{trip}', [CarrierTripController::class, 'update'])->name('update');
        Route::delete('/{trip}', [CarrierTripController::class, 'destroy'])->name('destroy');
        Route::post('/{trip}/force-start', [CarrierTripController::class, 'forceStart'])->name('force-start');
        Route::post('/{trip}/force-pause', [CarrierTripController::class, 'forcePause'])->name('force-pause');
        Route::post('/{trip}/force-resume', [CarrierTripController::class, 'forceResume'])->name('force-resume');
        Route::post('/{trip}/force-end', [CarrierTripController::class, 'forceEnd'])->name('force-end');
    });

    Route::prefix('hos')->name('hos.')->group(function () {
        Route::get('/', [CarrierHosController::class, 'dashboard'])->name('dashboard');
        Route::get('/driver/{driver}', [CarrierHosController::class, 'driverLog'])->name('driver.log');
        Route::put('/entries/{entry}', [CarrierHosController::class, 'updateEntry'])->name('entries.update');
        Route::delete('/entries/{entry}', [CarrierHosController::class, 'deleteEntry'])->name('entries.destroy');
        Route::post('/entries/bulk-delete', [CarrierHosController::class, 'bulkDeleteEntries'])->name('entries.bulk-destroy');

        Route::prefix('fmcsa')->name('fmcsa.')->group(function () {
            Route::get('/configuration', [CarrierHosController::class, 'configuration'])->name('configuration');
            Route::put('/configuration', [CarrierHosController::class, 'updateConfiguration'])->name('configuration.update');
        });

        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [CarrierHosDocumentController::class, 'index'])->name('index');
            Route::post('/generate-daily-log', [CarrierHosDocumentController::class, 'generateDailyLog'])->name('generate-daily-log');
            Route::post('/generate-monthly-summary', [CarrierHosDocumentController::class, 'generateMonthlySummary'])->name('generate-monthly-summary');
            Route::post('/generate-fmcsa-monthly', [CarrierHosDocumentController::class, 'generateDocumentMonthly'])->name('generate-fmcsa-monthly');
            Route::get('/bulk-download', [CarrierHosDocumentController::class, 'bulkDownload'])->name('bulk-download');
            Route::post('/bulk-destroy', [CarrierHosDocumentController::class, 'bulkDestroy'])->name('bulk-destroy');
            Route::get('/{media}/download', [CarrierHosDocumentController::class, 'download'])->name('download');
            Route::get('/{media}/preview', [CarrierHosDocumentController::class, 'preview'])->name('preview');
            Route::delete('/{media}', [CarrierHosDocumentController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('violations')->name('violations.')->group(function () {
        Route::get('/', [CarrierHosController::class, 'violations'])->name('index');
        Route::get('/{violation}', [CarrierHosController::class, 'violationShow'])->name('show');
        Route::post('/{violation}/acknowledge', [CarrierHosController::class, 'violationAcknowledge'])->name('acknowledge');
        Route::post('/{violation}/forgive', [CarrierHosController::class, 'violationForgive'])->name('forgive');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [CarrierReportsController::class, 'index'])->name('index');
        Route::get('/drivers', [CarrierReportsController::class, 'drivers'])->name('drivers');
        Route::get('/drivers/export-pdf', [CarrierReportsController::class, 'driversExportPdf'])->name('drivers.export-pdf');
        Route::get('/vehicles', [CarrierReportsController::class, 'vehicles'])->name('vehicles');
        Route::get('/vehicles/export-pdf', [CarrierReportsController::class, 'vehiclesExportPdf'])->name('vehicles.export-pdf');
        Route::get('/accidents', [CarrierReportsController::class, 'accidents'])->name('accidents');
        Route::get('/accidents/export-pdf', [CarrierReportsController::class, 'accidentsExportPdf'])->name('accidents.export-pdf');
        Route::get('/medical-records', [CarrierReportsController::class, 'medicalRecords'])->name('medical-records');
        Route::get('/medical-records/export-pdf', [CarrierReportsController::class, 'medicalRecordsExportPdf'])->name('medical-records.export-pdf');
        Route::get('/licenses', [CarrierReportsController::class, 'licenses'])->name('licenses');
        Route::get('/licenses/export-pdf', [CarrierReportsController::class, 'licensesExportPdf'])->name('licenses.export-pdf');
        Route::get('/maintenance', [CarrierReportsController::class, 'maintenance'])->name('maintenance');
        Route::get('/maintenance/export-pdf', [CarrierReportsController::class, 'maintenanceExportPdf'])->name('maintenance.export-pdf');
        Route::get('/repairs', [CarrierReportsController::class, 'repairs'])->name('repairs');
        Route::get('/repairs/export-pdf', [CarrierReportsController::class, 'repairsExportPdf'])->name('repairs.export-pdf');
        Route::get('/monthly', [CarrierReportsController::class, 'monthly'])->name('monthly');
        Route::get('/monthly/export-pdf', [CarrierReportsController::class, 'monthlyExportPdf'])->name('monthly.export-pdf');
        Route::get('/trips', [CarrierReportsController::class, 'trips'])->name('trips');
        Route::get('/trips/export-pdf', [CarrierReportsController::class, 'tripsExportPdf'])->name('trips.export-pdf');
        Route::get('/hos', [CarrierReportsController::class, 'hos'])->name('hos');
        Route::get('/hos/export-pdf', [CarrierReportsController::class, 'hosExportPdf'])->name('hos.export-pdf');
        Route::get('/violations', [CarrierReportsController::class, 'violations'])->name('violations');
        Route::get('/violations/export-pdf', [CarrierReportsController::class, 'violationsExportPdf'])->name('violations.export-pdf');
    });

    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/', [CarrierVehicleController::class, 'index'])->name('index');
        Route::get('/create', [CarrierVehicleController::class, 'create'])->name('create');
        Route::post('/', [CarrierVehicleController::class, 'store'])->name('store');
        Route::get('/{vehicle}', [CarrierVehicleController::class, 'show'])->name('show');
        Route::get('/{vehicle}/edit', [CarrierVehicleController::class, 'edit'])->name('edit');
        Route::put('/{vehicle}', [CarrierVehicleController::class, 'update'])->name('update');
        Route::delete('/{vehicle}', [CarrierVehicleController::class, 'destroy'])->name('destroy');
        Route::get('/{vehicle}/driver-assignment-history', [CarrierVehicleController::class, 'driverAssignmentHistory'])->name('driver-assignment-history');
        Route::get('/{vehicle}/maintenance', [CarrierMaintenanceController::class, 'vehicleIndex'])->name('maintenance.index');
        Route::get('/{vehicle}/maintenance/create', [CarrierMaintenanceController::class, 'createForVehicle'])->name('maintenance.create');
        Route::get('/{vehicle}/repairs', [CarrierEmergencyRepairController::class, 'vehicleIndex'])->name('repairs.index');
        Route::get('/{vehicle}/repairs/create', [CarrierEmergencyRepairController::class, 'createForVehicle'])->name('repairs.create');

        Route::get('/{vehicle}/documents', [CarrierVehicleDocumentController::class, 'index'])->name('documents.index');
        Route::post('/{vehicle}/documents', [CarrierVehicleDocumentController::class, 'store'])->name('documents.store');
        Route::put('/{vehicle}/documents/{document}', [CarrierVehicleDocumentController::class, 'update'])->name('documents.update');
        Route::delete('/{vehicle}/documents/{document}', [CarrierVehicleDocumentController::class, 'destroy'])->name('documents.destroy');
    });

    Route::resource('vehicle-makes', CarrierVehicleMakeController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('vehicle-makes');

    Route::resource('vehicle-types', CarrierVehicleTypeController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('vehicle-types');
});
