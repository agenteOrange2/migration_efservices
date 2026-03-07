<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Driver\HosController as DriverHosController;
use App\Http\Controllers\Driver\TripController as DriverTripController;
use App\Http\Controllers\Driver\DriverHosCycleController;
use App\Http\Controllers\Carrier\HosController as CarrierHosController;
use App\Http\Controllers\Carrier\TripController as CarrierTripController;
use App\Http\Controllers\Carrier\CarrierDriverHosController;
use App\Http\Controllers\Admin\HosController as AdminHosController;
use App\Http\Controllers\Admin\TripController as AdminTripController;

/*
|--------------------------------------------------------------------------
| HOS (Hours of Service) Routes
|--------------------------------------------------------------------------
|
| Routes for the HOS module for local drivers (non-interstate).
| Separated by role: Driver, Carrier, and Admin.
|
*/

// Driver HOS Routes
Route::middleware(['auth', 'role:user_driver'])->prefix('driver/hos')->name('driver.hos.')->group(function () {
    Route::get('/', [DriverHosController::class, 'index'])->name('dashboard');
    Route::post('/status', [DriverHosController::class, 'changeStatus'])->name('status.change');
    Route::get('/history', [DriverHosController::class, 'history'])->name('history');
    Route::get('/entries', [DriverHosController::class, 'getEntriesForDate'])->name('entries');
    Route::get('/current', [DriverHosController::class, 'getCurrentStatus'])->name('current');
    Route::post('/correction', [DriverHosController::class, 'requestCorrection'])->name('correction');
    Route::get('/report/daily', [DriverHosController::class, 'dailyReport'])->name('report.daily');
    Route::get('/report/monthly', [DriverHosController::class, 'monthlyReport'])->name('report.monthly');
    Route::put('/entry/{entry}', [DriverHosController::class, 'updateEntry'])->name('entry.update');
    Route::delete('/entry/{entry}', [DriverHosController::class, 'deleteEntry'])->name('entry.delete');
    Route::post('/entries/bulk-delete', [DriverHosController::class, 'bulkDeleteEntries'])->name('entries.bulk-delete');
    
    // HOS Cycle Settings (60/70 hours)
    Route::get('/cycle', [DriverHosCycleController::class, 'index'])->name('cycle.index');
    Route::post('/cycle/request', [DriverHosCycleController::class, 'requestChange'])->name('cycle.request');
    Route::post('/cycle/cancel', [DriverHosCycleController::class, 'cancelRequest'])->name('cycle.cancel');
    
    // HOS Documents
    Route::get('/documents', [\App\Http\Controllers\Driver\HosDocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{media}/download', [\App\Http\Controllers\Driver\HosDocumentController::class, 'download'])->name('documents.download');
    Route::post('/documents/daily-log', [\App\Http\Controllers\Driver\HosDocumentController::class, 'generateDailyLog'])->name('documents.daily-log');
    Route::post('/documents/monthly-summary', [\App\Http\Controllers\Driver\HosDocumentController::class, 'generateMonthlySummary'])->name('documents.monthly-summary');
    Route::post('/documents/document-monthly', [\App\Http\Controllers\Driver\HosDocumentController::class, 'generateDocumentMonthly'])->name('documents.document-monthly');
});

// Carrier HOS Routes
Route::middleware(['auth', 'role:user_carrier'])->prefix('carrier/hos')->name('carrier.hos.')->group(function () {
    Route::get('/', [CarrierHosController::class, 'index'])->name('dashboard');
    Route::get('/driver/{driverId}', [CarrierHosController::class, 'driverLog'])->name('driver.log');
    Route::put('/entry/{entryId}', [CarrierHosController::class, 'updateEntry'])->name('entry.update');
    Route::put('/entry/{entry}/form', [CarrierHosController::class, 'updateEntryForm'])->name('entry.update.form');
    Route::delete('/entry/{entry}', [CarrierHosController::class, 'deleteEntry'])->name('entry.delete');
    Route::post('/entries/bulk-delete', [CarrierHosController::class, 'bulkDeleteEntries'])->name('entries.bulk-delete');
    Route::post('/entry/manual', [CarrierHosController::class, 'createManualEntry'])->name('entry.manual');
    Route::get('/configuration', [CarrierHosController::class, 'configuration'])->name('configuration');
    Route::put('/configuration', [CarrierHosController::class, 'updateConfiguration'])->name('configuration.update');
    Route::get('/driver/{driverId}/report/daily', [CarrierHosController::class, 'dailyReport'])->name('report.daily');
    Route::get('/driver/{driverId}/report/monthly', [CarrierHosController::class, 'monthlyReport'])->name('report.monthly');
    Route::get('/violations', [CarrierHosController::class, 'violations'])->name('violations');
    
    // HOS Documents
    Route::get('/documents', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{media}/download', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{media}', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/documents/daily-log', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'generateDailyLog'])->name('documents.daily-log');
    Route::post('/documents/monthly-summary', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'generateMonthlySummary'])->name('documents.monthly-summary');
    Route::post('/documents/document-monthly', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'generateDocumentMonthly'])->name('documents.document-monthly');
    Route::post('/documents/bulk-download', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'bulkDownload'])->name('documents.bulk-download');

});

// Carrier Driver HOS Cycle Management
Route::middleware(['auth', 'role:user_carrier'])->prefix('carrier/drivers/hos')->name('carrier.drivers.hos.')->group(function () {
    Route::get('/', [CarrierDriverHosController::class, 'index'])->name('index');
    Route::get('/{driver}', [CarrierDriverHosController::class, 'show'])->name('show');
    Route::put('/{driver}', [CarrierDriverHosController::class, 'update'])->name('update');
    Route::post('/{driver}/approve', [CarrierDriverHosController::class, 'approveRequest'])->name('approve');
    Route::post('/{driver}/reject', [CarrierDriverHosController::class, 'rejectRequest'])->name('reject');
});


// Admin HOS Routes
Route::middleware(['auth', 'role:superadmin'])->prefix('admin/hos')->name('admin.hos.')->group(function () {
    Route::get('/', [AdminHosController::class, 'index'])->name('dashboard');
    Route::get('/carrier/{carrierId}', [AdminHosController::class, 'carrierDetail'])->name('carrier.detail');
    Route::get('/violations', [AdminHosController::class, 'violations'])->name('violations');
    Route::get('/violations/{violation}', [AdminHosController::class, 'violationShow'])->name('violations.show');
    Route::post('/violations/{violation}/acknowledge', [AdminHosController::class, 'violationAcknowledge'])->name('violations.acknowledge');
    Route::get('/violations/{violation}/forgive', [AdminHosController::class, 'violationForgiveForm'])->name('violations.forgive.form');
    Route::post('/violations/{violation}/forgive', [AdminHosController::class, 'violationForgive'])->name('violations.forgive');
    Route::get('/driver/{driverId}', [AdminHosController::class, 'driverLog'])->name('driver.log');
    Route::post('/driver/{driverId}/report', [AdminHosController::class, 'generateReport'])->name('report.generate');
    Route::put('/entry/{entry}', [AdminHosController::class, 'updateEntry'])->name('entry.update');
    Route::delete('/entry/{entry}', [AdminHosController::class, 'deleteEntry'])->name('entry.delete');
    Route::post('/entries/bulk-delete', [AdminHosController::class, 'bulkDeleteEntries'])->name('entries.bulk-delete');
    
    // HOS Documents
    Route::get('/documents', [\App\Http\Controllers\Admin\HosDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents/daily-log', [\App\Http\Controllers\Admin\HosDocumentController::class, 'generateDailyLog'])->name('documents.daily-log');
    Route::post('/documents/monthly-summary', [\App\Http\Controllers\Admin\HosDocumentController::class, 'generateMonthlySummary'])->name('documents.monthly-summary');
    Route::post('/documents/document-monthly', [\App\Http\Controllers\Admin\HosDocumentController::class, 'generateDocumentMonthly'])->name('documents.document-monthly');
    Route::post('/documents/bulk-download', [\App\Http\Controllers\Admin\HosDocumentController::class, 'bulkDownload'])->name('documents.bulk-download');
    Route::delete('/documents/bulk-destroy', [\App\Http\Controllers\Admin\HosDocumentController::class, 'bulkDestroy'])->name('documents.bulk-destroy');
    // Routes with parameters MUST come AFTER specific routes
    Route::get('/documents/{media}/download', [\App\Http\Controllers\Admin\HosDocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{media}/preview', [\App\Http\Controllers\Admin\HosDocumentController::class, 'preview'])->name('documents.preview');
    Route::delete('/documents/{media}', [\App\Http\Controllers\Admin\HosDocumentController::class, 'destroy'])->name('documents.destroy');

});

// Admin Driver HOS Cycle Management
Route::middleware(['auth', 'role:superadmin'])->prefix('admin/drivers/hos')->name('admin.drivers.hos.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminDriverHosController::class, 'index'])->name('index');
    Route::put('/{driver}', [\App\Http\Controllers\Admin\AdminDriverHosController::class, 'update'])->name('update');
    Route::post('/{driver}/approve', [\App\Http\Controllers\Admin\AdminDriverHosController::class, 'approveRequest'])->name('approve');
    Route::post('/{driver}/reject', [\App\Http\Controllers\Admin\AdminDriverHosController::class, 'rejectRequest'])->name('reject');
});

/*
|--------------------------------------------------------------------------
| Trip Routes
|--------------------------------------------------------------------------
|
| Routes for the Trip management system integrated with FMCSA HOS.
|
*/

// Driver Trip Routes
Route::middleware(['auth', 'role:user_driver'])->prefix('driver/trips')->name('driver.trips.')->group(function () {
    Route::get('/', [DriverTripController::class, 'index'])->name('index');
    Route::get('/pending-count', [DriverTripController::class, 'pendingCount'])->name('pending.count');
    
    // Driver can create their own trips
    Route::get('/create', [DriverTripController::class, 'create'])->name('create');
    Route::post('/', [DriverTripController::class, 'store'])->name('store');
    Route::post('/quick', [DriverTripController::class, 'storeQuickTrip'])->name('quick-store');
    
    Route::get('/{trip}', [DriverTripController::class, 'show'])->name('show');
    Route::post('/{trip}/accept', [DriverTripController::class, 'accept'])->name('accept');
    Route::post('/{trip}/reject', [DriverTripController::class, 'reject'])->name('reject');
    Route::get('/{trip}/start', [DriverTripController::class, 'startForm'])->name('start.form');
    Route::post('/{trip}/start', [DriverTripController::class, 'start'])->name('start');
    Route::post('/{trip}/pause', [DriverTripController::class, 'pause'])->name('pause');
    Route::post('/{trip}/resume', [DriverTripController::class, 'resume'])->name('resume');
    Route::get('/{trip}/end', [DriverTripController::class, 'endForm'])->name('end.form');
    Route::post('/{trip}/end', [DriverTripController::class, 'end'])->name('end');
    
    // Trip Documents
    Route::post('/{trip}/documents', [DriverTripController::class, 'uploadDocuments'])->name('documents.upload');
    Route::delete('/{trip}/documents/{mediaId}', [DriverTripController::class, 'deleteDocument'])->name('documents.delete');
    Route::get('/{trip}/documents/{mediaId}/download', [DriverTripController::class, 'downloadDocument'])->name('documents.download');
    Route::get('/{trip}/documents/{mediaId}/preview', [DriverTripController::class, 'previewDocument'])->name('documents.preview');
});

// Carrier Trip Routes
Route::middleware(['auth', 'role:user_carrier'])->prefix('carrier/trips')->name('carrier.trips.')->group(function () {
    Route::get('/dashboard', [CarrierTripController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [CarrierTripController::class, 'index'])->name('index');
    Route::get('/create', [CarrierTripController::class, 'create'])->name('create');
    Route::post('/', [CarrierTripController::class, 'store'])->name('store');
    Route::get('/{trip}', [CarrierTripController::class, 'show'])->name('show');
    Route::get('/{trip}/edit', [CarrierTripController::class, 'edit'])->name('edit');
    Route::put('/{trip}', [CarrierTripController::class, 'update'])->name('update');
    Route::delete('/{trip}', [CarrierTripController::class, 'destroy'])->name('destroy');
    // Emergency trip control
    Route::post('/{trip}/force-start', [CarrierTripController::class, 'forceStart'])->name('force-start');
    Route::post('/{trip}/force-pause', [CarrierTripController::class, 'forcePause'])->name('force-pause');
    Route::post('/{trip}/force-resume', [CarrierTripController::class, 'forceResume'])->name('force-resume');
    Route::post('/{trip}/force-end', [CarrierTripController::class, 'forceEnd'])->name('force-end');
});

// Carrier Violations Routes
Route::middleware(['auth', 'role:user_carrier'])->prefix('carrier/violations')->name('carrier.violations.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Carrier\ViolationController::class, 'index'])->name('index');
    Route::get('/{violation}', [\App\Http\Controllers\Carrier\ViolationController::class, 'show'])->name('show');
    Route::post('/{violation}/acknowledge', [\App\Http\Controllers\Carrier\ViolationController::class, 'acknowledge'])->name('acknowledge');
    Route::get('/{violation}/forgive', [\App\Http\Controllers\Carrier\ViolationController::class, 'forgiveForm'])->name('forgive.form');
    Route::post('/{violation}/forgive', [\App\Http\Controllers\Carrier\ViolationController::class, 'forgive'])->name('forgive');
});

// Carrier FMCSA Configuration Routes
Route::middleware(['auth', 'role:user_carrier'])->prefix('carrier/hos/fmcsa')->name('carrier.hos.fmcsa.')->group(function () {
    Route::get('/configuration', [\App\Http\Controllers\Carrier\HosConfigurationController::class, 'edit'])->name('configuration');
    Route::put('/configuration', [\App\Http\Controllers\Carrier\HosConfigurationController::class, 'update'])->name('configuration.update');
});

// Admin Trip Routes
Route::middleware(['auth', 'role:superadmin'])->prefix('admin/trips')->name('admin.trips.')->group(function () {
    Route::get('/', [AdminTripController::class, 'index'])->name('index');
    Route::get('/statistics', [AdminTripController::class, 'statistics'])->name('statistics');
    Route::get('/create', [AdminTripController::class, 'create'])->name('create');
    Route::post('/', [AdminTripController::class, 'store'])->name('store');
    Route::get('/carrier-data', [AdminTripController::class, 'getCarrierData'])->name('carrier.data');
    Route::get('/{trip}', [AdminTripController::class, 'show'])->name('show');
    Route::get('/{trip}/edit', [AdminTripController::class, 'edit'])->name('edit');
    Route::put('/{trip}', [AdminTripController::class, 'update'])->name('update');
    Route::delete('/{trip}', [AdminTripController::class, 'destroy'])->name('destroy');
    // Emergency trip control
    Route::post('/{trip}/force-start', [AdminTripController::class, 'forceStart'])->name('force-start');
    Route::post('/{trip}/force-pause', [AdminTripController::class, 'forcePause'])->name('force-pause');
    Route::post('/{trip}/force-resume', [AdminTripController::class, 'forceResume'])->name('force-resume');
    Route::post('/{trip}/force-end', [AdminTripController::class, 'forceEnd'])->name('force-end');
});
