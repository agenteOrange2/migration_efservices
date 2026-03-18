<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CarrierController;
use App\Http\Controllers\Admin\CarrierDocumentController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\SafetyDataSystemController;
use App\Http\Controllers\Admin\UserCarrierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\Driver\DriverAdminWizardController;
use App\Http\Controllers\Admin\Driver\DriverEmploymentController;
use App\Http\Controllers\Admin\Driver\DriverListController;
use App\Http\Controllers\Admin\Driver\DriverTestingController;
use App\Http\Controllers\Admin\Driver\EmploymentVerificationController;
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
Route::prefix('drivers')->name('drivers.')->group(function () {
    Route::get('/', [DriverListController::class, 'index'])->name('index');

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
| Admin Management (will be migrated in later phases)
|--------------------------------------------------------------------------
*/
// TODO: Vehicles, HOS, Messages, Reports, Roles, etc.
