<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CarrierController;
use App\Http\Controllers\Admin\CarrierDocumentController;
use App\Http\Controllers\Admin\BulkImportController;
use App\Http\Controllers\Admin\ContactSubmissionController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\MessagesController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\PlanRequestController;
use App\Http\Controllers\Admin\SafetyDataSystemController;
use App\Http\Controllers\Admin\TrainingDashboardController;
use App\Http\Controllers\Admin\TrainingAssignmentsController;
use App\Http\Controllers\Admin\TrainingsController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\HosController;
use App\Http\Controllers\Admin\HosDocumentController;
use App\Http\Controllers\Admin\AdminDriverHosController;
use App\Http\Controllers\Admin\UserCarrierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\Driver\AccidentsController;
use App\Http\Controllers\Admin\Driver\DriverAdminWizardController;
use App\Http\Controllers\Admin\Driver\ArchivedDriversController;
use App\Http\Controllers\Admin\Driver\CoursesController;
use App\Http\Controllers\Admin\Driver\DriverEmploymentController;
use App\Http\Controllers\Admin\Driver\DriverLicenseController;
use App\Http\Controllers\Admin\Driver\DriverListController;
use App\Http\Controllers\Admin\Driver\MedicalRecordsController;
use App\Http\Controllers\Admin\Driver\InspectionsController;
use App\Http\Controllers\Admin\Driver\TrafficConvictionsController;
use App\Http\Controllers\Admin\Driver\TrainingSchoolsController;
use App\Http\Controllers\Admin\Driver\DriverTestingController;
use App\Http\Controllers\Admin\Driver\EmploymentVerificationController;
use App\Http\Controllers\Admin\Driver\DriverMigrationController;
use App\Http\Controllers\Admin\Driver\DriverRecruitmentController;
use App\Http\Controllers\Admin\Vehicles\VehicleController;
use App\Http\Controllers\Admin\Vehicles\VehicleDashboardController;
use App\Http\Controllers\Admin\Vehicles\VehicleDocumentController;
use App\Http\Controllers\Admin\Vehicles\EmergencyRepairController;
use App\Http\Controllers\Admin\Vehicles\MaintenanceController;
use App\Http\Controllers\Admin\Vehicles\VehicleMakeController;
use App\Http\Controllers\Admin\Vehicles\VehicleTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Carriers Management
|--------------------------------------------------------------------------
*/
Route::resource('carriers', CarrierController::class);

Route::prefix('carriers/{carrier}')->name('carriers.')->group(function () {
    Route::get('documents', [CarrierController::class, 'documents'])->name('documents');
    Route::post('generate-missing-documents', [CarrierController::class, 'generateMissingDocuments'])->name('generate-missing-documents');
    Route::post('delete-photo', [CarrierController::class, 'deletePhoto'])->name('delete-photo');
    Route::post('generate-dot-policy', [CarrierController::class, 'regenerateDotPolicy'])->name('generate-dot-policy');

    // Banking
    Route::post('banking/store', [CarrierController::class, 'storeBanking'])->name('banking.store');
    Route::put('banking/update', [CarrierController::class, 'updateBanking'])->name('banking.update');
    Route::post('banking/approve', [CarrierController::class, 'approveBanking'])->name('banking.approve');
    Route::post('banking/reject', [CarrierController::class, 'rejectBanking'])->name('banking.reject');

    // User Carriers
    Route::post('user-carriers', [UserCarrierController::class, 'store'])->name('user-carriers.store');
    Route::put('user-carriers/{userCarrierDetail}', [UserCarrierController::class, 'update'])->name('user-carriers.update');
    Route::delete('user-carriers/{userCarrier}', [UserCarrierController::class, 'destroy'])->name('user-carriers.destroy');

    // Safety Data System
    Route::get('safety-data-system', [SafetyDataSystemController::class, 'edit'])->name('safety-data-system');
    Route::put('safety-data-system', [SafetyDataSystemController::class, 'update'])->name('safety-data-system.update');
    Route::post('safety-data-system/upload', [SafetyDataSystemController::class, 'uploadImage'])->name('safety-data-system.upload');
    Route::delete('safety-data-system/delete', [SafetyDataSystemController::class, 'deleteImage'])->name('safety-data-system.delete');
});

Route::get('carriers/{carrier}/users', [UserCarrierController::class, 'index'])->name('carriers.users.index');

Route::put('carrier-documents/{document}/update-status', [CarrierController::class, 'updateDocumentStatus'])->name('carrier-documents.update-status');

/*
|--------------------------------------------------------------------------
| Carriers Documents (Global View)
|--------------------------------------------------------------------------
*/
Route::get('carriers-documents', [CarrierDocumentController::class, 'listCarriersForDocuments'])->name('carriers-documents.index');
Route::get('carriers-documents/export/pdf', [CarrierDocumentController::class, 'exportPdf'])->name('carriers-documents.export-pdf');
Route::get('carriers-documents/{carrier}', [CarrierDocumentController::class, 'index'])->name('carriers-documents.carrier');
Route::post('carriers-documents/{carrier}/upload/{documentType}', [CarrierDocumentController::class, 'upload'])->name('carriers-documents.upload');
Route::put('carriers-documents/{carrier}/document/{document}', [CarrierDocumentController::class, 'update'])->name('carriers-documents.update-doc');
Route::delete('carriers-documents/{carrier}/document/{document}', [CarrierDocumentController::class, 'destroy'])->name('carriers-documents.delete-doc');

/*
|--------------------------------------------------------------------------
| Document Types
|--------------------------------------------------------------------------
*/
Route::get('document-types/default-policy', [DocumentTypeController::class, 'showDefaultPolicy'])->name('document-types.default-policy');
Route::post('document-types/default-policy', [DocumentTypeController::class, 'uploadDefaultPolicy'])->name('document-types.upload-default-policy');
Route::delete('document-types/default-policy', [DocumentTypeController::class, 'deleteDefaultPolicy'])->name('document-types.delete-default-policy');
Route::resource('document-types', DocumentTypeController::class)->except(['show']);

/*
|--------------------------------------------------------------------------
| Memberships
|--------------------------------------------------------------------------
*/
Route::resource('memberships', MembershipController::class);
Route::get('vehicles/dashboard', [VehicleDashboardController::class, 'index'])->name('vehicles.dashboard');
Route::prefix('maintenance')->name('maintenance.')->group(function () {
    Route::get('/', [MaintenanceController::class, 'index'])->name('index');
    Route::get('create', [MaintenanceController::class, 'create'])->name('create');
    Route::post('/', [MaintenanceController::class, 'store'])->name('store');
    Route::get('calendar', [MaintenanceController::class, 'calendar'])->name('calendar');
    Route::get('reports', [MaintenanceController::class, 'reports'])->name('reports');
    Route::get('{maintenance}', [MaintenanceController::class, 'show'])->name('show');
    Route::get('{maintenance}/edit', [MaintenanceController::class, 'edit'])->name('edit');
    Route::put('{maintenance}', [MaintenanceController::class, 'update'])->name('update');
    Route::delete('{maintenance}', [MaintenanceController::class, 'destroy'])->name('destroy');
    Route::put('{maintenance}/toggle-status', [MaintenanceController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('{maintenance}/reschedule', [MaintenanceController::class, 'reschedule'])->name('reschedule');
    Route::post('{maintenance}/attachments', [MaintenanceController::class, 'storeDocuments'])->name('attachments.store');
    Route::delete('{maintenance}/attachments/{media}', [MaintenanceController::class, 'deleteDocument'])->name('attachments.destroy');
    Route::post('{maintenance}/generate-report', [MaintenanceController::class, 'generateReport'])->name('generate-report');
    Route::delete('{maintenance}/reports/{document}', [MaintenanceController::class, 'deleteReport'])->name('delete-report');
});
Route::prefix('vehicles/emergency-repairs')->name('vehicles.emergency-repairs.')->group(function () {
    Route::get('/', [EmergencyRepairController::class, 'index'])->name('index');
    Route::get('create', [EmergencyRepairController::class, 'create'])->name('create');
    Route::post('/', [EmergencyRepairController::class, 'store'])->name('store');
    Route::get('{emergencyRepair}', [EmergencyRepairController::class, 'show'])->name('show');
    Route::get('{emergencyRepair}/edit', [EmergencyRepairController::class, 'edit'])->name('edit');
    Route::put('{emergencyRepair}', [EmergencyRepairController::class, 'update'])->name('update');
    Route::delete('{emergencyRepair}', [EmergencyRepairController::class, 'destroy'])->name('destroy');
    Route::post('{emergencyRepair}/attachments', [EmergencyRepairController::class, 'uploadDocument'])->name('attachments.store');
    Route::delete('{emergencyRepair}/attachments/{media}', [EmergencyRepairController::class, 'deleteFile'])->name('attachments.destroy');
    Route::post('{emergencyRepair}/generate-report', [EmergencyRepairController::class, 'generateSingleReport'])->name('generate-report');
    Route::delete('{emergencyRepair}/reports/{document}', [EmergencyRepairController::class, 'deleteRepairReport'])->name('delete-report');
});
Route::prefix('vehicles')->name('vehicles.')->group(function () {
    Route::get('/', [VehicleController::class, 'index'])->name('index');
    Route::get('create', [VehicleController::class, 'create'])->name('create');
    Route::post('/', [VehicleController::class, 'store'])->name('store');
    Route::get('unassigned', [VehicleController::class, 'unassigned'])->name('unassigned');
    Route::get('{vehicle}/maintenance', [MaintenanceController::class, 'vehicleIndex'])->name('maintenance.index');
    Route::get('{vehicle}/maintenance/create', [MaintenanceController::class, 'createForVehicle'])->name('maintenance.create');
    Route::get('{vehicle}/repairs', [EmergencyRepairController::class, 'vehicleIndex'])->name('repairs.index');
    Route::get('{vehicle}/repairs/create', [EmergencyRepairController::class, 'createForVehicle'])->name('repairs.create');
    Route::get('{vehicle}/driver-assignment-history', [VehicleController::class, 'driverAssignmentHistory'])->name('driver-assignment-history');
    Route::get('{vehicle}/documents', [VehicleDocumentController::class, 'index'])->name('documents.index');
    Route::post('{vehicle}/documents', [VehicleDocumentController::class, 'store'])->name('documents.store');
    Route::put('{vehicle}/documents/{document}', [VehicleDocumentController::class, 'update'])->name('documents.update');
    Route::delete('{vehicle}/documents/{document}', [VehicleDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('{vehicle}/edit', [VehicleController::class, 'edit'])->name('edit');
    Route::get('{vehicle}', [VehicleController::class, 'show'])->name('show');
    Route::put('{vehicle}', [VehicleController::class, 'update'])->name('update');
    Route::delete('{vehicle}', [VehicleController::class, 'destroy'])->name('destroy');
});
Route::get('vehicles-documents', [VehicleDocumentController::class, 'overview'])->name('vehicles-documents.index');
Route::resource('vehicle-makes', VehicleMakeController::class)->only(['index', 'store', 'update', 'destroy'])->names('vehicle-makes');
Route::resource('vehicle-types', VehicleTypeController::class)->only(['index', 'store', 'update', 'destroy'])->names('vehicle-types');
Route::get('training-dashboard', [TrainingDashboardController::class, 'index'])->name('training-dashboard.index');
Route::get('training-dashboard/export', [TrainingDashboardController::class, 'export'])->name('training-dashboard.export');
Route::prefix('trips')->name('trips.')->group(function () {
    Route::get('/', [TripController::class, 'index'])->name('index');
    Route::get('statistics', [TripController::class, 'statistics'])->name('statistics');
    Route::get('create', [TripController::class, 'create'])->name('create');
    Route::post('/', [TripController::class, 'store'])->name('store');
    Route::get('carrier-data', [TripController::class, 'getCarrierData'])->name('carrier.data');
    Route::get('{trip}', [TripController::class, 'show'])->name('show');
    Route::get('{trip}/edit', [TripController::class, 'edit'])->name('edit');
    Route::put('{trip}', [TripController::class, 'update'])->name('update');
    Route::delete('{trip}', [TripController::class, 'destroy'])->name('destroy');
    Route::post('{trip}/force-start', [TripController::class, 'forceStart'])->name('force-start');
    Route::post('{trip}/force-pause', [TripController::class, 'forcePause'])->name('force-pause');
    Route::post('{trip}/force-resume', [TripController::class, 'forceResume'])->name('force-resume');
    Route::post('{trip}/force-end', [TripController::class, 'forceEnd'])->name('force-end');
});
Route::prefix('hos')->name('hos.')->group(function () {
    Route::get('/', [HosController::class, 'index'])->name('dashboard');
    Route::get('carrier/{carrier}', [HosController::class, 'carrierDetail'])->name('carrier.detail');
    Route::get('driver/{driver}', [HosController::class, 'driverLog'])->name('driver.log');
    Route::put('entries/{entry}', [HosController::class, 'updateEntry'])->name('entries.update');
    Route::delete('entries/{entry}', [HosController::class, 'deleteEntry'])->name('entries.destroy');
    Route::post('entries/bulk-delete', [HosController::class, 'bulkDeleteEntries'])->name('entries.bulk-destroy');
    Route::get('violations', [HosController::class, 'violations'])->name('violations');
    Route::get('violations/{violation}', [HosController::class, 'violationShow'])->name('violations.show');
    Route::post('violations/{violation}/acknowledge', [HosController::class, 'violationAcknowledge'])->name('violations.acknowledge');
    Route::post('violations/{violation}/forgive', [HosController::class, 'violationForgive'])->name('violations.forgive');
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [HosDocumentController::class, 'index'])->name('index');
        Route::post('generate-daily-log', [HosDocumentController::class, 'generateDailyLog'])->name('generate-daily-log');
        Route::post('generate-monthly-summary', [HosDocumentController::class, 'generateMonthlySummary'])->name('generate-monthly-summary');
        Route::post('generate-fmcsa-monthly', [HosDocumentController::class, 'generateDocumentMonthly'])->name('generate-fmcsa-monthly');
        Route::get('bulk-download', [HosDocumentController::class, 'bulkDownload'])->name('bulk-download');
        Route::post('bulk-destroy', [HosDocumentController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::get('{media}/download', [HosDocumentController::class, 'download'])->name('download');
        Route::get('{media}/preview', [HosDocumentController::class, 'preview'])->name('preview');
        Route::delete('{media}', [HosDocumentController::class, 'destroy'])->name('destroy');
    });
});
Route::prefix('drivers/hos')->name('drivers.hos.')->group(function () {
    Route::get('/', [AdminDriverHosController::class, 'index'])->name('index');
    Route::put('{driver}', [AdminDriverHosController::class, 'update'])->name('update');
    Route::post('{driver}/approve', [AdminDriverHosController::class, 'approveRequest'])->name('approve');
    Route::post('{driver}/reject', [AdminDriverHosController::class, 'rejectRequest'])->name('reject');
});
Route::prefix('contact-submissions')->name('contact-submissions.')->group(function () {
    Route::get('/', [ContactSubmissionController::class, 'index'])->name('index');
    Route::get('{contactSubmission}', [ContactSubmissionController::class, 'show'])->name('show');
    Route::put('{contactSubmission}', [ContactSubmissionController::class, 'update'])->name('update');
    Route::delete('{contactSubmission}', [ContactSubmissionController::class, 'destroy'])->name('destroy');
});
Route::prefix('plan-requests')->name('plan-requests.')->group(function () {
    Route::get('/', [PlanRequestController::class, 'index'])->name('index');
    Route::get('{planRequest}', [PlanRequestController::class, 'show'])->name('show');
    Route::put('{planRequest}', [PlanRequestController::class, 'update'])->name('update');
    Route::delete('{planRequest}', [PlanRequestController::class, 'destroy'])->name('destroy');
});
Route::prefix('trainings')->name('trainings.')->group(function () {
    Route::delete('media/{media}', [TrainingsController::class, 'destroyMedia'])->name('media.destroy');
    Route::get('{training}/assign', [TrainingAssignmentsController::class, 'createForTraining'])->name('assign.form');
    Route::post('{training}/assign', [TrainingAssignmentsController::class, 'storeForTraining'])->name('assign');
});
Route::resource('trainings', TrainingsController::class);
Route::prefix('training-assignments')->name('training-assignments.')->group(function () {
    Route::post('{assignment}/mark-complete', [TrainingAssignmentsController::class, 'markComplete'])->name('mark-complete');
});
Route::resource('training-assignments', TrainingAssignmentsController::class)->parameters([
    'training-assignments' => 'training_assignment',
]);

/*
|--------------------------------------------------------------------------
| Users Management
|--------------------------------------------------------------------------
*/
Route::resource('users', UserController::class);
Route::post('users/{user}/delete-photo', [UserController::class, 'deletePhoto'])->name('users.delete-photo');

/*
|--------------------------------------------------------------------------
| Admin Drivers (Fase 3.4)
|--------------------------------------------------------------------------
*/
Route::prefix('licenses')->name('licenses.')->group(function () {
    Route::get('documents', [DriverLicenseController::class, 'documents'])->name('documents.index');
    Route::get('{license}/documents', [DriverLicenseController::class, 'showDocuments'])->name('documents.show');
    Route::delete('media/{media}', [DriverLicenseController::class, 'destroyMedia'])->name('media.destroy');
});
Route::resource('licenses', DriverLicenseController::class)->except(['show']);

Route::prefix('medical-records')->name('medical-records.')->group(function () {
    Route::get('{medical_record}/documents', [MedicalRecordsController::class, 'showDocuments'])->name('documents.show');
    Route::delete('media/{media}', [MedicalRecordsController::class, 'destroyMedia'])->name('media.destroy');
});
Route::resource('medical-records', MedicalRecordsController::class)->except(['show']);
Route::prefix('training-schools')->name('training-schools.')->group(function () {
    Route::get('documents', [TrainingSchoolsController::class, 'documents'])->name('documents.index');
    Route::get('{training_school}/documents', [TrainingSchoolsController::class, 'showDocuments'])->name('documents.show');
    Route::delete('media/{media}', [TrainingSchoolsController::class, 'destroyMedia'])->name('media.destroy');
});
Route::resource('training-schools', TrainingSchoolsController::class);
Route::prefix('courses')->name('courses.')->group(function () {
    Route::get('all/documents', [CoursesController::class, 'getAllDocuments'])->name('all-documents');
    Route::get('{course}/documents', [CoursesController::class, 'getFiles'])->name('documents');
    Route::delete('document/{document}', [CoursesController::class, 'destroyMedia'])->name('document.delete');
});
Route::resource('courses', CoursesController::class)->except(['show']);
Route::get('drivers/{driver}/course-history', [CoursesController::class, 'driverHistory'])->name('drivers.course-history');
Route::prefix('traffic')->name('traffic.')->group(function () {
    Route::get('/', [TrafficConvictionsController::class, 'index'])->name('index');
    Route::get('create', [TrafficConvictionsController::class, 'create'])->name('create');
    Route::post('/', [TrafficConvictionsController::class, 'store'])->name('store');
    Route::get('driver/{driver}/history', [TrafficConvictionsController::class, 'driverHistory'])->name('driver-history');
    Route::get('{conviction}/edit', [TrafficConvictionsController::class, 'edit'])->name('edit');
    Route::put('{conviction}', [TrafficConvictionsController::class, 'update'])->name('update');
    Route::delete('{conviction}', [TrafficConvictionsController::class, 'destroy'])->name('destroy');
    Route::get('{conviction}/documents', [TrafficConvictionsController::class, 'showDocuments'])->name('documents.show');
    Route::delete('media/{media}', [TrafficConvictionsController::class, 'destroyMedia'])->name('media.destroy');
});
Route::prefix('inspections')->name('inspections.')->group(function () {
    Route::get('/', [InspectionsController::class, 'index'])->name('index');
    Route::get('create', [InspectionsController::class, 'create'])->name('create');
    Route::post('/', [InspectionsController::class, 'store'])->name('store');
    Route::get('documents', [InspectionsController::class, 'documents'])->name('documents.index');
    Route::get('driver/{driver}/history', [InspectionsController::class, 'driverHistory'])->name('driver-history');
    Route::get('driver/{driver}/documents', [InspectionsController::class, 'driverDocuments'])->name('driver-documents');
    Route::get('{inspection}/edit', [InspectionsController::class, 'edit'])->name('edit');
    Route::put('{inspection}', [InspectionsController::class, 'update'])->name('update');
    Route::delete('{inspection}', [InspectionsController::class, 'destroy'])->name('destroy');
    Route::delete('media/{media}', [InspectionsController::class, 'destroyMedia'])->name('media.destroy');
});
Route::prefix('accidents')->name('accidents.')->group(function () {
    Route::get('/', [AccidentsController::class, 'index'])->name('index');
    Route::get('create', [AccidentsController::class, 'create'])->name('create');
    Route::post('/', [AccidentsController::class, 'store'])->name('store');
    Route::get('documents', [AccidentsController::class, 'documents'])->name('documents.index');
    Route::get('driver/{driver}/history', [AccidentsController::class, 'driverHistory'])->name('driver-history');
    Route::get('carrier/{carrier}/history', [AccidentsController::class, 'carrierHistory'])->name('carrier-history');
    Route::get('{accident}/edit', [AccidentsController::class, 'edit'])->name('edit');
    Route::put('{accident}', [AccidentsController::class, 'update'])->name('update');
    Route::delete('{accident}', [AccidentsController::class, 'destroy'])->name('destroy');
    Route::get('{accident}/documents', [AccidentsController::class, 'showDocuments'])->name('documents.show');
    Route::delete('documents/{document}', [AccidentsController::class, 'destroyDocument'])->name('documents.destroy');
    Route::delete('media/{mediaId}', [AccidentsController::class, 'destroyMedia'])->name('media.destroy');
});

/*
|--------------------------------------------------------------------------
| Driver Testings – Global Index
|--------------------------------------------------------------------------
*/
Route::prefix('driver-testings')->name('driver-testings.')->group(function () {
    Route::get('/', [DriverTestingController::class, 'index'])->name('index');
    Route::get('{testing}', [DriverTestingController::class, 'show'])->name('show');
    Route::get('{testing}/download-pdf', [DriverTestingController::class, 'downloadPdf'])->name('download-pdf');
    Route::post('{testing}/regenerate-pdf', [DriverTestingController::class, 'regeneratePdf'])->name('regenerate-pdf');
    Route::post('{testing}/attachments', [DriverTestingController::class, 'uploadAttachment'])->name('upload-attachment');
    Route::delete('{testing}/attachments/{media}', [DriverTestingController::class, 'deleteAttachment'])->name('delete-attachment');
    Route::delete('{testing}', [DriverTestingController::class, 'destroyGlobal'])->name('destroy');
});

Route::prefix('drivers')->name('drivers.')->group(function () {
    Route::get('/', [DriverListController::class, 'index'])->name('index');
    Route::get('archived', [ArchivedDriversController::class, 'index'])->name('archived.index');
    Route::get('archived/{archive}', [ArchivedDriversController::class, 'show'])->name('archived.show');

    // Admin Driver Registration Wizard (Phase 3.6)
    // NOTE: wizard/create must appear before {driver} pattern
    Route::get('wizard/create', [DriverAdminWizardController::class, 'create'])->name('wizard.create');
    Route::post('wizard', [DriverAdminWizardController::class, 'store'])->name('wizard.store');
    Route::get('{driver}/wizard', [DriverAdminWizardController::class, 'edit'])->name('wizard.edit');
    Route::put('{driver}/wizard/{step}', [DriverAdminWizardController::class, 'updateStep'])->name('wizard.update-step');

    // Employment verification (wizard-level, per driver)
    Route::get('employment/search-companies', [DriverEmploymentController::class, 'searchCompanies'])->name('employment.search-companies');
    Route::post('{driver}/employment/{company}/send-email', [DriverEmploymentController::class, 'sendEmail'])->name('employment.send-email');
    Route::post('{driver}/employment/{company}/resend-email', [DriverEmploymentController::class, 'resendEmail'])->name('employment.resend-email');
    Route::post('{driver}/employment/{company}/mark-email-status', [DriverEmploymentController::class, 'markEmailStatus'])->name('employment.mark-email-status');

    // Employment Verification Management page (static prefix — must come before {driver} wildcard)
    Route::prefix('employment-verification')->name('employment-verification.')->group(function () {
        Route::get('/', [EmploymentVerificationController::class, 'index'])->name('index');
        Route::get('new', [EmploymentVerificationController::class, 'create'])->name('new');
        Route::post('new', [EmploymentVerificationController::class, 'store'])->name('store');
        Route::get('{id}', [EmploymentVerificationController::class, 'show'])->name('show');
        Route::post('{id}/resend', [EmploymentVerificationController::class, 'resend'])->name('resend');
        Route::post('{id}/toggle-email-flag', [EmploymentVerificationController::class, 'toggleEmailFlag'])->name('toggle-email-flag');
        Route::post('{id}/mark-verified', [EmploymentVerificationController::class, 'markVerified'])->name('mark-verified');
        Route::post('{id}/mark-rejected', [EmploymentVerificationController::class, 'markRejected'])->name('mark-rejected');
        Route::post('{id}/upload-document', [EmploymentVerificationController::class, 'uploadDocument'])->name('upload-document');
        Route::delete('{id}/documents/{mediaId}', [EmploymentVerificationController::class, 'deleteDocument'])->name('delete-document');
        Route::delete('{id}/tokens/{tokenId}', [EmploymentVerificationController::class, 'deleteToken'])->name('delete-token');
    });

    Route::get('{driver}/documents/download', [DriverListController::class, 'downloadDocuments'])->name('documents.download');

    // Migration wizard (dedicated Inertia page)
    Route::get('{driver}/migration', [DriverMigrationController::class, 'wizard'])->name('migration.wizard');
    Route::post('{driver}/migration', [DriverMigrationController::class, 'execute'])->name('migration.execute');

    Route::get('{driver}', [DriverListController::class, 'show'])->name('show');
    Route::put('{driver}/activate', [DriverListController::class, 'activate'])->name('activate');
    Route::put('{driver}/deactivate', [DriverListController::class, 'deactivate'])->name('deactivate');
    Route::delete('{driver}', [DriverListController::class, 'destroy'])->name('destroy');

    // Drug/Alcohol Testing CRUD
    Route::prefix('{driver}/testings')->name('testings.')->group(function () {
        Route::get('create', [DriverTestingController::class, 'create'])->name('create');
        Route::post('/', [DriverTestingController::class, 'store'])->name('store');
        Route::get('{testing}/edit', [DriverTestingController::class, 'edit'])->name('edit');
        Route::put('{testing}', [DriverTestingController::class, 'update'])->name('update');
        Route::delete('{testing}', [DriverTestingController::class, 'destroy'])->name('destroy');
    });
});

// Migration rollback (keyed by MigrationRecord, separate from driver prefix)
Route::post('migration-records/{record}/rollback', [DriverMigrationController::class, 'rollback'])->name('migration-records.rollback');

/*
|--------------------------------------------------------------------------
| Driver Recruitment Management
|--------------------------------------------------------------------------
*/
Route::prefix('driver-recruitment')->name('driver-recruitment.')->group(function () {
    Route::get('/', [DriverRecruitmentController::class, 'index'])->name('index');
    Route::get('{driver}', [DriverRecruitmentController::class, 'show'])->name('show');
    Route::post('{driver}/checklist', [DriverRecruitmentController::class, 'updateChecklist'])->name('checklist.update');
    Route::post('{driver}/approve', [DriverRecruitmentController::class, 'approve'])->name('approve');
    Route::post('{driver}/reject', [DriverRecruitmentController::class, 'reject'])->name('reject');
    // Image uploads
    Route::post('{driver}/licenses/{license}/upload-image', [DriverRecruitmentController::class, 'uploadLicenseImage'])->name('licenses.upload-image');
    Route::post('{driver}/upload-medical-image', [DriverRecruitmentController::class, 'uploadMedicalImage'])->name('upload-medical-image');
    Route::post('{driver}/upload-document', [DriverRecruitmentController::class, 'uploadDocument'])->name('upload-document');
    Route::delete('{driver}/documents/{media}', [DriverRecruitmentController::class, 'destroyDocument'])->name('documents.destroy');
    // Store / Update / Delete records
    Route::post('{driver}/training-schools', [DriverRecruitmentController::class, 'storeTrainingSchool'])->name('training-schools.store');
    Route::post('{driver}/training-schools/{school}', [DriverRecruitmentController::class, 'updateTrainingSchool'])->name('training-schools.update');
    Route::delete('{driver}/training-schools/{school}', [DriverRecruitmentController::class, 'destroyTrainingSchool'])->name('training-schools.destroy');
    Route::post('{driver}/courses', [DriverRecruitmentController::class, 'storeCourse'])->name('courses.store');
    Route::post('{driver}/courses/{course}', [DriverRecruitmentController::class, 'updateCourse'])->name('courses.update');
    Route::delete('{driver}/courses/{course}', [DriverRecruitmentController::class, 'destroyCourse'])->name('courses.destroy');
    Route::post('{driver}/traffic-convictions', [DriverRecruitmentController::class, 'storeTrafficConviction'])->name('traffic-convictions.store');
    Route::post('{driver}/traffic-convictions/{conviction}', [DriverRecruitmentController::class, 'updateTrafficConviction'])->name('traffic-convictions.update');
    Route::delete('{driver}/traffic-convictions/{conviction}', [DriverRecruitmentController::class, 'destroyTrafficConviction'])->name('traffic-convictions.destroy');
    Route::post('{driver}/accidents', [DriverRecruitmentController::class, 'storeAccident'])->name('accidents.store');
    Route::post('{driver}/accidents/{accident}', [DriverRecruitmentController::class, 'updateAccident'])->name('accidents.update');
    Route::delete('{driver}/accidents/{accident}', [DriverRecruitmentController::class, 'destroyAccident'])->name('accidents.destroy');
});

/*
|--------------------------------------------------------------------------
| Master Companies
|--------------------------------------------------------------------------
*/
Route::prefix('companies')->name('companies.')->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('index');
    Route::post('/', [CompanyController::class, 'store'])->name('store');
    Route::get('{company}', [CompanyController::class, 'show'])->name('show');
    Route::put('{company}', [CompanyController::class, 'update'])->name('update');
    Route::delete('{company}', [CompanyController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| Reports
|--------------------------------------------------------------------------
*/
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('index');
    Route::get('active-drivers', [ReportsController::class, 'activeDrivers'])->name('active-drivers');
    Route::get('active-drivers/pdf', [ReportsController::class, 'activeDriversPdf'])->name('active-drivers-pdf');
    Route::get('inactive-drivers', [ReportsController::class, 'inactiveDrivers'])->name('inactive-drivers');
    Route::get('inactive-drivers/pdf', [ReportsController::class, 'inactiveDriversPdf'])->name('inactive-drivers-pdf');
    Route::get('driver-prospects', [ReportsController::class, 'driverProspects'])->name('driver-prospects');
    Route::get('driver-prospects/pdf', [ReportsController::class, 'driverProspectsPdf'])->name('driver-prospects-pdf');
    Route::get('equipment-list', [ReportsController::class, 'equipmentList'])->name('equipment-list');
    Route::get('equipment-list/pdf', [ReportsController::class, 'equipmentListPdf'])->name('equipment-list-pdf');
    Route::get('carrier-documents', [ReportsController::class, 'carrierDocuments'])->name('carrier-documents');
    Route::get('carrier-documents/pdf', [ReportsController::class, 'carrierDocumentsPdf'])->name('carrier-documents-pdf');
    Route::get('download-carrier-documents/{carrier}', [ReportsController::class, 'downloadCarrierDocuments'])->name('download-carrier-documents');
    Route::get('accidents', [ReportsController::class, 'accidents'])->name('accidents');
    Route::get('register-accident', [ReportsController::class, 'registerAccident'])->name('register-accident');
    Route::post('store-accident', [ReportsController::class, 'storeAccident'])->name('store-accident');
    Route::get('accidents-list', [ReportsController::class, 'accidentsList'])->name('accidents-list');
    Route::get('maintenances', [ReportsController::class, 'maintenances'])->name('maintenances');
    Route::get('maintenances/pdf', [ReportsController::class, 'maintenancesPdf'])->name('maintenances-pdf');
    Route::get('emergency-repairs', [ReportsController::class, 'emergencyRepairs'])->name('emergency-repairs');
    Route::get('emergency-repairs/pdf', [ReportsController::class, 'emergencyRepairsPdf'])->name('emergency-repairs-pdf');
    Route::get('trainings', [ReportsController::class, 'trainings'])->name('trainings');
    Route::get('trainings/pdf', [ReportsController::class, 'trainingsPdf'])->name('trainings-pdf');
    Route::get('trips', [ReportsController::class, 'trips'])->name('trips');
    Route::get('trips/pdf', [ReportsController::class, 'tripsPdf'])->name('trips-pdf');
    Route::get('trips/{trip}', [ReportsController::class, 'tripDetails'])->name('trip-details');
    Route::get('hos', [ReportsController::class, 'hos'])->name('hos');
    Route::get('hos/pdf', [ReportsController::class, 'hosPdf'])->name('hos-pdf');
    Route::get('hos/{driver}', [ReportsController::class, 'hosDetails'])->name('hos-details');
    Route::get('violations', [ReportsController::class, 'violations'])->name('violations');
    Route::get('violations/pdf', [ReportsController::class, 'violationsPdf'])->name('violations-pdf');
    Route::get('violations/{violation}', [ReportsController::class, 'violationDetails'])->name('violation-details');
    Route::get('migrations', [ReportsController::class, 'migrations'])->name('migrations');
});
Route::get('api/active-drivers-by-carrier/{carrierId}', [ReportsController::class, 'getActiveDriversByCarrier'])->name('api.active-drivers-by-carrier');

/*
|--------------------------------------------------------------------------
| Bulk Imports
|--------------------------------------------------------------------------
*/
Route::prefix('imports')->name('imports.')->group(function () {
    Route::get('/', [BulkImportController::class, 'index'])->name('index');
    Route::get('template/{type}', [BulkImportController::class, 'downloadTemplate'])->name('template');
    Route::post('preview', [BulkImportController::class, 'preview'])->name('preview');
    Route::post('execute', [BulkImportController::class, 'execute'])->name('execute');
});

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/
Route::prefix('messages')->name('messages.')->group(function () {
    Route::get('dashboard', [MessagesController::class, 'dashboard'])->name('dashboard');
    Route::get('create', [MessagesController::class, 'create'])->name('create');
    Route::post('/', [MessagesController::class, 'store'])->name('store');
    Route::get('/', [MessagesController::class, 'index'])->name('index');
    Route::get('{message}', [MessagesController::class, 'show'])->name('show');
    Route::get('{message}/edit', [MessagesController::class, 'edit'])->name('edit');
    Route::put('{message}', [MessagesController::class, 'update'])->name('update');
    Route::delete('{message}', [MessagesController::class, 'destroy'])->name('destroy');
    Route::post('{message}/duplicate', [MessagesController::class, 'duplicate'])->name('duplicate');
    Route::post('{message}/resend', [MessagesController::class, 'resend'])->name('resend');
    Route::delete('{message}/recipients/{recipient}', [MessagesController::class, 'removeRecipient'])->name('remove-recipient');
});

/*
|--------------------------------------------------------------------------
| Admin Management (will be migrated in later phases)
|--------------------------------------------------------------------------
*/
// TODO: Messages, Roles, etc.
