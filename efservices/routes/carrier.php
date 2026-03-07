<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\CustomLoginController;
use App\Http\Controllers\Auth\CarrierRegistrationController;
use App\Http\Controllers\Auth\CarrierOnboardingController;
use App\Http\Controllers\Auth\CarrierAuthController;
use App\Http\Controllers\Auth\CarrierStatusController;
use App\Http\Controllers\Auth\CarrierDocumentController;
use App\Http\Controllers\Auth\CarrierWizardController;
use App\Http\Controllers\Carrier\DocumentController;
use App\Http\Controllers\Carrier\CarrierDriverController;
use App\Http\Controllers\Carrier\CarrierProfileController;
use App\Http\Controllers\Carrier\CarrierDriverManagementController;
use App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController;
use App\Http\Controllers\Carrier\CarrierVehicleController;
use App\Http\Controllers\Carrier\CarrierDriverAccidentsController;
use App\Http\Controllers\Carrier\CarrierDriverTestingsController;
use App\Http\Controllers\Carrier\CarrierDriverInspectionsController;
use App\Http\Controllers\Carrier\CarrierDashboardController;
use App\Http\Controllers\Carrier\CarrierDriverLicensesController;
use App\Http\Controllers\Carrier\CarrierMedicalRecordsController;
use App\Http\Controllers\Carrier\CarrierTrafficController;
use App\Http\Controllers\Carrier\CarrierTrainingSchoolsController;
use App\Http\Controllers\Carrier\CarrierCoursesController;
use App\Http\Controllers\Carrier\CarrierTrainingsController;
use App\Http\Controllers\Carrier\CarrierTrainingAssignmentsController;
use App\Http\Controllers\Carrier\VehicleMakeController;
use App\Http\Controllers\Carrier\VehicleTypeController;
use App\Http\Controllers\Carrier\CarrierEmergencyRepairController;
use App\Http\Controllers\Carrier\CarrierVehicleDocumentController;
use App\Http\Controllers\Carrier\CarrierVehicleDocumentsOverviewController;
use App\Http\Controllers\Carrier\CarrierVehicleMaintenanceController;
use App\Http\Controllers\Carrier\CarrierReportsController;
use App\Http\Controllers\Admin\UserCarrierDocumentController;

// Rutas públicas para registro multi-paso
Route::middleware('guest')->group(function () {
    // Wizard multi-paso
    Route::prefix('wizard')->name('wizard.')->group(function () {
        // Paso 1: Información básica
        Route::get('/step1', [CarrierWizardController::class, 'showStep1'])->name('step1');
        Route::post('/step1', [CarrierWizardController::class, 'processStep1'])->name('step1.process');
        // Route removed: step1.success - now redirects directly to login after registration
        
        // AJAX endpoints for wizard
        Route::post('/check-uniqueness', [CarrierWizardController::class, 'checkUniqueness'])
            ->name('check.uniqueness')
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    });
    
    // Rutas de compatibilidad (redirigen al wizard)
    Route::get('/register', function () {
        return redirect()->route('carrier.wizard.step1');
    })->name('register');
});

// Ruta de confirmación de email
Route::get('/confirm/{token}', [CarrierRegistrationController::class, 'confirmEmail'])->name('confirm');

// Rutas que requieren autenticación
Route::middleware(['auth'])->group(function () {
    // Wizard multi-paso (pasos que requieren autenticación)
    Route::prefix('wizard')->name('wizard.')->group(function () {
        // Paso 2: Información de la empresa
        Route::get('/step2', [CarrierWizardController::class, 'showStep2'])->name('step2');
        Route::post('/step2', [CarrierWizardController::class, 'processStep2'])->name('step2.process');
        
        // Paso 3: Selección de membresía
        Route::get('/step3', [CarrierWizardController::class, 'showStep3'])->name('step3');
        Route::post('/step3', [CarrierWizardController::class, 'processStep3'])->name('step3.process');
        
        // Paso 4: Información bancaria
        Route::get('/step4', [CarrierWizardController::class, 'showStep4'])->name('step4');
        Route::post('/step4', [CarrierWizardController::class, 'processStep4'])->name('step4.process');
        
        // AJAX endpoints for authenticated wizard steps
        Route::get('/check-verification', [CarrierWizardController::class, 'checkVerification'])->name('check.verification');
    });
    
    // Rutas de compatibilidad
    Route::get('/complete-registration', function () {
        return redirect()->route('carrier.wizard.step2');
    })->name('complete_registration');
    
    // Rutas de estado y confirmación
    Route::get('/confirmation', [CarrierStatusController::class, 'showConfirmation'])->name('confirmation');
    Route::get('/pending', [CarrierStatusController::class, 'showPending'])->name('pending');
    Route::get('/pending-validation', [CarrierStatusController::class, 'pendingValidation'])->name('pending.validation');
    Route::get('/inactive', [CarrierStatusController::class, 'showInactive'])->name('inactive');
    Route::get('/banking-rejected', [CarrierStatusController::class, 'showBankingRejected'])->name('banking.rejected');
    Route::get('/payment-validated', [CarrierStatusController::class, 'showPaymentValidated'])->name('payment-validated');
    Route::post('/request-reactivation', [CarrierStatusController::class, 'requestReactivation'])->name('request.reactivation');
    Route::get('/status', [CarrierStatusController::class, 'getRegistrationStatus'])->name('status');
    Route::get('/support', [CarrierStatusController::class, 'showSupport'])->name('support');
    Route::post('/support', [CarrierStatusController::class, 'submitSupportRequest']);

    // Dashboard y otras rutas protegidas (con verificación de estado)
    Route::middleware(['check.carrier.status'])->group(function () {
        Route::get('/dashboard', [CarrierDashboardController::class, 'index'])->name('dashboard');

        // Rutas para gestión de infracciones de tráfico de conductores (ANTES de {carrierSlug})
        Route::prefix('traffic')->name('traffic.')->group(function () {
            Route::get('/', [CarrierTrafficController::class, 'index'])->name('index');
            Route::get('/create', [CarrierTrafficController::class, 'create'])->name('create');
            Route::post('/', [CarrierTrafficController::class, 'store'])->name('store');
            Route::get('/{conviction}/edit', [CarrierTrafficController::class, 'edit'])->name('edit');
            Route::put('/{conviction}', [CarrierTrafficController::class, 'update'])->name('update');
            Route::delete('/{conviction}', [CarrierTrafficController::class, 'destroy'])->name('destroy');
            Route::get('/{conviction}/documents', [CarrierTrafficController::class, 'showDocuments'])->name('documents');
            Route::post('/{conviction}/documents', [CarrierTrafficController::class, 'storeDocuments'])->name('documents.store');
            Route::get('/driver/{driver}/history', [CarrierTrafficController::class, 'driverHistory'])->name('driver.history');
            Route::delete('/documents/{mediaId}', [CarrierTrafficController::class, 'destroyDocument'])->name('documents.delete');
            Route::get('/documents/{mediaId}/preview', [CarrierTrafficController::class, 'previewDocument'])->name('documents.preview');
        });

        // Rutas para documentos usando el nuevo controlador especializado
        Route::group([
            'prefix' => '{carrierSlug}'
        ], function () {
            Route::get('/documents', [CarrierDocumentController::class, 'index'])->name('documents.index');
            Route::post('/documents/upload/{documentType}', [CarrierDocumentController::class, 'upload'])->name('documents.upload');
            Route::post('/documents/toggle-default/{documentType}', [CarrierDocumentController::class, 'toggleDefaultDocument'])
                ->name('documents.toggle-default');
            Route::post('/documents/accept-default/{documentType}', [CarrierDocumentController::class, 'toggleDefaultDocument'])
                ->name('documents.accept-default');
            Route::delete('/documents/{documentType}', [CarrierDocumentController::class, 'deleteDocument'])
                ->name('documents.delete');
            Route::get('/documents/progress', [CarrierDocumentController::class, 'getDocumentProgress'])
                ->name('documents.progress');
            Route::get('/documents/{document}/view', [CarrierDocumentController::class, 'viewDocument'])
                ->name('documents.view');
            Route::get('/documents/skip', [CarrierDocumentController::class, 'skipDocuments'])
                ->name('documents.skip');
        });

        // La vista principal del perfil
        Route::get('/profile', [CarrierProfileController::class, 'index'])->name('profile');
        // Vista de edición del perfil
        Route::get('/profile/edit', [CarrierProfileController::class, 'edit'])->name('profile.edit');
        // Actualizar perfil
        Route::put('/profile/update', [CarrierProfileController::class, 'update'])->name('profile.update');

        // Rutas para gestión de conductores
        // IMPORTANTE: Las rutas específicas deben ir ANTES del resource para evitar que {driver} capture "archived"
        // Note: Archived drivers routes removed - carriers now use "inactive drivers" instead
        // See .kiro/specs/carrier-inactive-drivers-archive for new implementation
        
        // Inactive Drivers Archive routes (must be before resource to avoid route conflicts)
        Route::prefix('drivers/inactive')->name('drivers.inactive.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Carrier\InactiveDriversController::class, 'index'])->name('index');
            Route::get('/{archive}', [\App\Http\Controllers\Carrier\InactiveDriversController::class, 'show'])
                ->name('show')
                ->middleware(\App\Http\Middleware\LogArchiveAccess::class);
            Route::get('/{archive}/download', [\App\Http\Controllers\Carrier\InactiveDriversController::class, 'download'])
                ->name('download')
                ->middleware(\App\Http\Middleware\LogArchiveAccess::class);
        });
        
        // Gestión de conductores (ruta original)
        Route::resource('drivers', CarrierDriverController::class);

        // Rutas para descarga de documentos de conductores
        Route::prefix('drivers')->name('drivers.')->group(function () {
            Route::get('/{driver}/documents/download', [\App\Http\Controllers\Carrier\CarrierDriverDocumentsController::class, 'downloadAll'])->name('documents.download');
            Route::post('/{driver}/documents/download-all', [\App\Http\Controllers\Carrier\CarrierDriverDocumentsController::class, 'downloadAll'])->name('documents.download-all');
            Route::post('/{driver}/documents/download-selected', [\App\Http\Controllers\Carrier\CarrierDriverDocumentsController::class, 'downloadSelected'])->name('documents.download-selected');
        });

        // Nuevas rutas para gestión de conductores
        Route::prefix('carrier-driver-management')->name('driver-management.')->group(function () {
            Route::get('/', [CarrierDriverManagementController::class, 'index'])->name('index');
            Route::get('/create', [CarrierDriverManagementController::class, 'create'])->name('create');
            Route::post('/', [CarrierDriverManagementController::class, 'store'])->name('store');
            Route::get('/{driver}', [CarrierDriverManagementController::class, 'show'])->name('show');
            Route::get('/{driver}/edit', [CarrierDriverManagementController::class, 'edit'])->name('edit');
            Route::put('/{driver}', [CarrierDriverManagementController::class, 'update'])->name('update');
            Route::delete('/{driver}', [CarrierDriverManagementController::class, 'destroy'])->name('destroy');
            Route::delete('/{driver}/photo', [CarrierDriverManagementController::class, 'deletePhoto'])->name('delete-photo');
        });
        
        // Rutas para gestión de conductores y vehículos (asignaciones)
        Route::prefix('driver-vehicle-management')->name('driver-vehicle-management.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'index'])->name('index');
            Route::get('/{driver}/show', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'show'])->name('show');
            Route::get('/{driver}/assign-vehicle', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'assignVehicle'])->name('assign-vehicle');
            Route::post('/{driver}/assign-vehicle', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'storeVehicleAssignment'])->name('store-vehicle-assignment');
            Route::get('/{driver}/edit-assignment', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'editAssignment'])->name('edit-assignment');
            Route::put('/{driver}/update-assignment', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'updateAssignment'])->name('update-assignment');
            Route::post('/{driver}/cancel-assignment', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'cancelAssignment'])->name('cancel-assignment');
            Route::get('/{driver}/assignment-history', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'assignmentHistory'])->name('assignment-history');
            Route::get('/{driver}/contact', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'contact'])->name('contact');
            Route::post('/{driver}/contact', [\App\Http\Controllers\Carrier\CarrierDriverVehicleManagementController::class, 'sendContact'])->name('send-contact');
        });
        
        // Rutas para accidentes de conductores
        Route::prefix('carrier-driver-accidents')->name('drivers.accidents.')->group(function () {
            Route::get('/', [CarrierDriverAccidentsController::class, 'index'])->name('index');
            Route::get('/create', [CarrierDriverAccidentsController::class, 'create'])->name('create');
            Route::post('/', [CarrierDriverAccidentsController::class, 'store'])->name('store');
            Route::get('/{accident}/edit', [CarrierDriverAccidentsController::class, 'edit'])->name('edit');
            Route::put('/{accident}', [CarrierDriverAccidentsController::class, 'update'])->name('update');
            Route::delete('/{accident}', [CarrierDriverAccidentsController::class, 'destroy'])->name('destroy');
            Route::get('/driver/{driver}', [CarrierDriverAccidentsController::class, 'driverHistory'])->name('driver_history');
            
            // Rutas para gestión de documentos de accidentes
            Route::get('/documents', [CarrierDriverAccidentsController::class, 'documents'])->name('documents.index');
            Route::get('/{accident}/documents', [CarrierDriverAccidentsController::class, 'showDocuments'])->name('documents.show');
            Route::post('/{accident}/documents', [CarrierDriverAccidentsController::class, 'storeDocuments'])->name('documents.store');
            Route::delete('/documents/{document}', [CarrierDriverAccidentsController::class, 'destroyDocument'])->name('documents.destroy');
            Route::get('/documents/{document}/preview', [CarrierDriverAccidentsController::class, 'previewDocument'])->name('document.preview');
            Route::delete('/ajax-destroy-media/{media}', [CarrierDriverAccidentsController::class, 'ajaxDestroyMedia'])->name('ajax-destroy-media');
        });
        
        // Rutas para pruebas de conductores
        Route::prefix('carrier-driver-testings')->name('drivers.testings.')->group(function () {
            Route::get('/', [CarrierDriverTestingsController::class, 'index'])->name('index');
            Route::get('/create', [CarrierDriverTestingsController::class, 'create'])->name('create');
            Route::post('/', [CarrierDriverTestingsController::class, 'store'])->name('store');
            
            // API endpoint for loading drivers (must be before dynamic routes)
            Route::get('/api/drivers', [CarrierDriverTestingsController::class, 'getActiveDrivers'])->name('api.drivers');
            
            Route::get('/driver/{driver}', [CarrierDriverTestingsController::class, 'driverHistory'])->name('driver_history');
            Route::get('/{testing}', [CarrierDriverTestingsController::class, 'show'])->name('show');
            Route::get('/{testing}/edit', [CarrierDriverTestingsController::class, 'edit'])->name('edit');
            Route::put('/{testing}', [CarrierDriverTestingsController::class, 'update'])->name('update');
            Route::delete('/{testing}', [CarrierDriverTestingsController::class, 'destroy'])->name('destroy');
            Route::get('/{testing}/download-pdf', [CarrierDriverTestingsController::class, 'downloadPdf'])->name('download_pdf');
            Route::post('/{testing}/regenerate-pdf', [CarrierDriverTestingsController::class, 'regeneratePdf'])->name('regenerate_pdf');
            Route::post('/{testing}/upload-results', [CarrierDriverTestingsController::class, 'uploadResults'])->name('upload-results');
        });
        
        // Rutas para inspecciones de conductores
        Route::prefix('carrier-driver-inspections')->name('drivers.inspections.')->group(function () {
            Route::get('/', [CarrierDriverInspectionsController::class, 'index'])->name('index');
            Route::get('/create', [CarrierDriverInspectionsController::class, 'create'])->name('create');
            Route::post('/', [CarrierDriverInspectionsController::class, 'store'])->name('store');
            Route::get('/{inspection}/edit', [CarrierDriverInspectionsController::class, 'edit'])->name('edit');
            Route::put('/{inspection}', [CarrierDriverInspectionsController::class, 'update'])->name('update');
            Route::delete('/{inspection}', [CarrierDriverInspectionsController::class, 'destroy'])->name('destroy');
            Route::get('/driver/{driver}', [CarrierDriverInspectionsController::class, 'driverHistory'])->name('driver_history');
            Route::delete('/{inspection}/files/{mediaId}', [CarrierDriverInspectionsController::class, 'deleteFile'])->name('delete-file');
            Route::get('/{inspection}/files', [CarrierDriverInspectionsController::class, 'getFiles'])->name('files');
            Route::post('/document/delete', [CarrierDriverInspectionsController::class, 'ajaxDestroyDocument'])->name('document.delete.ajax');
            Route::get('/documents', [CarrierDriverInspectionsController::class, 'allDocuments'])->name('documents');
            Route::get('/driver/{driver}/documents', [CarrierDriverInspectionsController::class, 'driverDocuments'])->name('driver.documents');
            Route::get('/driver/{driver}/vehicles', [CarrierDriverInspectionsController::class, 'getVehiclesByDriver'])->name('vehicles.by.driver');
        });
        
        // Rutas para gestión de vehículos
        Route::prefix('vehicles')->name('vehicles.')->group(function () {
            Route::get('/', [CarrierVehicleController::class, 'index'])->name('index');
            Route::get('/create', [CarrierVehicleController::class, 'create'])->name('create');
            Route::post('/', [CarrierVehicleController::class, 'store'])->name('store');
            
            // Enhanced functionality routes (must be before parameterized routes)
            Route::get('/filter-options', [CarrierVehicleController::class, 'getFilterOptions'])->name('filter-options');
            Route::get('/export/pdf', [CarrierVehicleController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/csv', [CarrierVehicleController::class, 'exportCsv'])->name('export.csv');
            Route::get('/statistics', [CarrierVehicleController::class, 'getStatistics'])->name('statistics');
            Route::get('/document-expiration-stats', [CarrierVehicleController::class, 'getDocumentExpirationStats'])->name('document-expiration-stats');
            
            // Dynamic reference data creation
            Route::post('/makes', [CarrierVehicleController::class, 'createMake'])->name('makes.create');
            Route::post('/types', [CarrierVehicleController::class, 'createType'])->name('types.create');
            
            // Driver type assignment routes (must be before parameterized routes)
            Route::get('/{vehicle}/assign-driver-type', [CarrierVehicleController::class, 'assignDriverType'])->name('assign-driver-type');
            Route::post('/{vehicle}/assign-driver-type', [CarrierVehicleController::class, 'storeDriverType'])->name('store-driver-type');
            Route::get('/{vehicle}/get-driver-data', [CarrierVehicleController::class, 'getDriverData'])->name('get-driver-data');
            
            // Parameterized routes (must be after specific routes)
            Route::get('/{vehicle}', [CarrierVehicleController::class, 'show'])->name('show');
            Route::get('/{vehicle}/edit', [CarrierVehicleController::class, 'edit'])->name('edit');
            Route::put('/{vehicle}', [CarrierVehicleController::class, 'update'])->name('update');
            Route::delete('/{vehicle}', [CarrierVehicleController::class, 'destroy'])->name('destroy');
            
            // Vehicle status management
            Route::put('/{vehicle}/status', [CarrierVehicleController::class, 'updateStatus'])->name('update-status');
            
            // Maintenance and assignment management
            Route::get('/{vehicle}/maintenance/calendar', [CarrierVehicleController::class, 'maintenanceCalendar'])->name('maintenance.calendar');
            Route::get('/{vehicle}/driver-assignment-history', [CarrierVehicleController::class, 'driverAssignmentHistory'])->name('driver-assignment-history');
            Route::get('/{vehicle}/assignments/history', [CarrierVehicleController::class, 'assignmentHistory'])->name('assignments.history');
            Route::put('/{vehicle}/assignments', [CarrierVehicleController::class, 'updateAssignment'])->name('assignments.update');
            
            // Rutas para documentos de vehículos
            Route::get('/{vehicle}/documents', [CarrierVehicleDocumentController::class, 'index'])->name('documents.index');
            Route::get('/{vehicle}/documents/create', [CarrierVehicleDocumentController::class, 'create'])->name('documents.create');
            Route::post('/{vehicle}/documents', [CarrierVehicleDocumentController::class, 'store'])->name('documents.store');
            Route::get('/{vehicle}/documents/{document}', [CarrierVehicleDocumentController::class, 'show'])->name('documents.show');
            Route::get('/{vehicle}/documents/{document}/edit', [CarrierVehicleDocumentController::class, 'edit'])->name('documents.edit');
            Route::put('/{vehicle}/documents/{document}', [CarrierVehicleDocumentController::class, 'update'])->name('documents.update');
            Route::delete('/{vehicle}/documents/{document}', [CarrierVehicleDocumentController::class, 'destroy'])->name('documents.destroy');
            Route::get('/{vehicle}/documents/{document}/download', [CarrierVehicleDocumentController::class, 'download'])->name('documents.download');
            Route::get('/{vehicle}/documents/{document}/preview', [CarrierVehicleDocumentController::class, 'preview'])->name('documents.preview');
            
            // Rutas para items de servicio de vehículos
            Route::get('/{vehicle}/service-items', [CarrierVehicleController::class, 'serviceItems'])->name('service-items');
            Route::get('/{vehicle}/service-items/create', [CarrierVehicleController::class, 'createServiceItem'])->name('service-items.create');
            Route::post('/{vehicle}/service-items', [CarrierVehicleController::class, 'storeServiceItem'])->name('service-items.store');
            Route::get('/{vehicle}/service-items/{serviceItem}/edit', [CarrierVehicleController::class, 'editServiceItem'])->name('service-items.edit');
            Route::put('/{vehicle}/service-items/{serviceItem}', [CarrierVehicleController::class, 'updateServiceItem'])->name('service-items.update');
            Route::delete('/{vehicle}/service-items/{serviceItem}', [CarrierVehicleController::class, 'destroyServiceItem'])->name('service-items.destroy');
            Route::put('/{vehicle}/service-items/{serviceItem}/toggle-status', [CarrierVehicleController::class, 'toggleServiceItemStatus'])->name('service-items.toggle-status');
            
            // VEHICLE-SPECIFIC MAINTENANCE ROUTES
            // Routes for managing maintenance for a specific vehicle only
            Route::prefix('/{vehicle}/maintenance')->name('maintenance.')->group(function () {
                Route::get('/', [CarrierVehicleMaintenanceController::class, 'indexForVehicle'])->name('index');
                Route::get('/create', [CarrierVehicleMaintenanceController::class, 'createForVehicle'])->name('create');
                Route::post('/', [CarrierVehicleMaintenanceController::class, 'storeForVehicle'])->name('store');
                Route::get('/{id}', [CarrierVehicleMaintenanceController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [CarrierVehicleMaintenanceController::class, 'edit'])->name('edit');
                Route::put('/{id}', [CarrierVehicleMaintenanceController::class, 'update'])->name('update');
                Route::post('/{id}/toggle-status', [CarrierVehicleMaintenanceController::class, 'toggleStatus'])->name('toggle-status');
                Route::post('/{id}/reschedule', [CarrierVehicleMaintenanceController::class, 'reschedule'])->name('reschedule');
                Route::delete('/{id}', [CarrierVehicleMaintenanceController::class, 'destroy'])->name('destroy');
                Route::delete('/documents/{mediaId}', [CarrierVehicleMaintenanceController::class, 'ajaxDeleteDocument'])->name('ajax-delete-document');
                Route::post('/{maintenance}/generate-report', [CarrierVehicleMaintenanceController::class, 'generateReport'])->name('generate-report');
                Route::post('/{maintenance}/documents/upload', [CarrierVehicleMaintenanceController::class, 'storeDocumentsFromShow'])->name('store-documents');
                Route::delete('/{maintenance}/report/{report}', [CarrierVehicleMaintenanceController::class, 'deleteReport'])->name('delete-report');
            });
        });
        
        // Vehicle Documents Overview Route
        Route::get('vehicles-documents', [App\Http\Controllers\Carrier\CarrierVehicleDocumentsOverviewController::class, 'index'])
            ->name('vehicles-documents.index');
        
        // GENERAL MAINTENANCE ROUTES (not vehicle-specific)
        // Routes for managing ALL maintenance across carrier's fleet
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [CarrierVehicleMaintenanceController::class, 'index'])->name('index');
            Route::get('/create', [CarrierVehicleMaintenanceController::class, 'create'])->name('create');
            Route::post('/', [CarrierVehicleMaintenanceController::class, 'store'])->name('store');
            Route::get('/calendar', [CarrierVehicleMaintenanceController::class, 'calendar'])->name('calendar');
            Route::get('/reports', [CarrierVehicleMaintenanceController::class, 'reports'])->name('reports');
            Route::get('/{id}', [CarrierVehicleMaintenanceController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [CarrierVehicleMaintenanceController::class, 'edit'])->name('edit');
            Route::put('/{id}', [CarrierVehicleMaintenanceController::class, 'update'])->name('update');
            Route::post('/{id}/toggle-status', [CarrierVehicleMaintenanceController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/reschedule', [CarrierVehicleMaintenanceController::class, 'reschedule'])->name('reschedule');
            Route::delete('/{id}', [CarrierVehicleMaintenanceController::class, 'destroy'])->name('destroy');
            Route::delete('/documents/{mediaId}', [CarrierVehicleMaintenanceController::class, 'ajaxDeleteDocument'])->name('ajax-delete-document');
            Route::post('/{maintenance}/generate-report', [CarrierVehicleMaintenanceController::class, 'generateReport'])->name('generate-report');
            Route::post('/{maintenance}/documents/upload', [CarrierVehicleMaintenanceController::class, 'storeDocumentsFromShow'])->name('store-documents');
            Route::delete('/{maintenance}/report/{report}', [CarrierVehicleMaintenanceController::class, 'deleteReport'])->name('delete-report');
        });
        
        // Rutas para gestión de reparaciones de emergencia
        Route::prefix('emergency-repairs')->name('emergency-repairs.')->group(function () {
            Route::get('/', [CarrierEmergencyRepairController::class, 'index'])->name('index');
            Route::get('/create', [CarrierEmergencyRepairController::class, 'create'])->name('create');
            Route::post('/', [CarrierEmergencyRepairController::class, 'store'])->name('store');
            Route::get('/vehicle/{vehicleId}/details', [CarrierEmergencyRepairController::class, 'getVehicleDetails'])->name('vehicle-details');
            Route::get('/{emergencyRepair}', [CarrierEmergencyRepairController::class, 'show'])->name('show');
            Route::get('/{emergencyRepair}/edit', [CarrierEmergencyRepairController::class, 'edit'])->name('edit');
            Route::put('/{emergencyRepair}', [CarrierEmergencyRepairController::class, 'update'])->name('update');
            Route::delete('/{emergencyRepair}', [CarrierEmergencyRepairController::class, 'destroy'])->name('destroy');
            Route::delete('/{emergencyRepair}/files/{mediaId}', [CarrierEmergencyRepairController::class, 'deleteFile'])->name('delete-file');
            Route::post('/vehicle/{vehicle}/generate-report', [CarrierEmergencyRepairController::class, 'generateRepairReport'])->name('generate-report');
            Route::delete('/vehicle/{vehicle}/report/{report}', [CarrierEmergencyRepairController::class, 'deleteRepairReport'])->name('delete-report');
        });
        
        // Rutas para gestión de licencias de conductores
        Route::prefix('licenses')->name('licenses.')->group(function () {
            // All documents view
            Route::get('all/documents', [CarrierDriverLicensesController::class, 'documents'])
                ->name('docs.all');
            
            // License-specific documents
            Route::get('{license}/documents', [CarrierDriverLicensesController::class, 'showDocuments'])
                ->name('docs.show');
            Route::post('{license}/upload-documents', [CarrierDriverLicensesController::class, 'uploadDocument'])
                ->name('upload.documents');
            
            // Document operations
            Route::get('document/{id}/preview', [CarrierDriverLicensesController::class, 'previewDocument'])
                ->name('doc.preview');
            Route::get('documents/{id}/preview', [CarrierDriverLicensesController::class, 'previewDocument'])
                ->name('docs.preview');
            Route::delete('documents/{id}', [CarrierDriverLicensesController::class, 'destroyDocument'])
                ->name('docs.delete');
            Route::delete('document/{id}', [CarrierDriverLicensesController::class, 'destroyDocument'])
                ->name('doc.delete');
            Route::delete('document/{id}/ajax', [CarrierDriverLicensesController::class, 'ajaxDestroyDocument'])
                ->name('doc.ajax-delete');
        });
        
        // Main resource routes for licenses
        Route::resource('licenses', CarrierDriverLicensesController::class);
        
        // Rutas para gestión de registros médicos de conductores
        Route::prefix('medical-records')->name('medical-records.')->group(function () {
            // Main resource routes
            Route::get('/', [CarrierMedicalRecordsController::class, 'index'])->name('index');
            Route::get('/create', [CarrierMedicalRecordsController::class, 'create'])->name('create');
            Route::post('/', [CarrierMedicalRecordsController::class, 'store'])->name('store');
            Route::get('/{medicalRecord}', [CarrierMedicalRecordsController::class, 'show'])->name('show');
            Route::get('/{medicalRecord}/edit', [CarrierMedicalRecordsController::class, 'edit'])->name('edit');
            Route::put('/{medicalRecord}', [CarrierMedicalRecordsController::class, 'update'])->name('update');
            Route::delete('/{medicalRecord}', [CarrierMedicalRecordsController::class, 'destroy'])->name('destroy');
            
            // Document management routes
            Route::get('/{medicalRecord}/documents', [CarrierMedicalRecordsController::class, 'showDocuments'])->name('docs.show');
            Route::post('/{medicalRecord}/upload-documents', [CarrierMedicalRecordsController::class, 'uploadDocument'])->name('upload.documents');
            Route::get('/document/{id}/preview', [CarrierMedicalRecordsController::class, 'previewDocument'])->name('doc.preview');
            Route::delete('/documents/{id}', [CarrierMedicalRecordsController::class, 'destroyDocument'])->name('docs.delete');
            Route::delete('/document/{id}/ajax', [CarrierMedicalRecordsController::class, 'ajaxDestroyDocument'])->name('doc.ajax-delete');
        });
                
        
        // Rutas para gestión de escuelas de entrenamiento de conductores
        Route::prefix('training-schools')->name('training-schools.')->group(function () {
            // Document routes (must be defined before resource routes)
            Route::get('all/documents', [CarrierTrainingSchoolsController::class, 'documents'])
                ->name('docs.all');
            Route::get('{school}/documents', [CarrierTrainingSchoolsController::class, 'showDocuments'])
                ->name('docs.show');
            Route::delete('documents/{document}', [CarrierTrainingSchoolsController::class, 'destroyDocument'])
                ->name('documents.delete');
            Route::delete('document/{id}/ajax', [CarrierTrainingSchoolsController::class, 'ajaxDestroyDocument'])
                ->name('documents.ajax-delete');
            
            // Resource routes with custom parameter
            Route::get('/', [CarrierTrainingSchoolsController::class, 'index'])->name('index');
            Route::get('/create', [CarrierTrainingSchoolsController::class, 'create'])->name('create');
            Route::post('/', [CarrierTrainingSchoolsController::class, 'store'])->name('store');
            Route::get('/{trainingSchool}', [CarrierTrainingSchoolsController::class, 'show'])->name('show');
            Route::get('/{trainingSchool}/edit', [CarrierTrainingSchoolsController::class, 'edit'])->name('edit');
            Route::put('/{trainingSchool}', [CarrierTrainingSchoolsController::class, 'update'])->name('update');
            Route::delete('/{trainingSchool}', [CarrierTrainingSchoolsController::class, 'destroy'])->name('destroy');
        });
        
        // Rutas para gestión de cursos profesionales de conductores
        Route::prefix('courses')->name('courses.')->group(function () {
            // Document routes (must be defined before resource routes)
            Route::get('/{course}/documents', [CarrierCoursesController::class, 'getFiles'])
                ->name('documents');
            Route::delete('/documents/{id}', [CarrierCoursesController::class, 'ajaxDestroyDocument'])
                ->name('documents.delete');
            Route::get('/documents/{id}/preview', [CarrierCoursesController::class, 'previewDocument'])
                ->name('documents.preview');
            
            // Resource routes
            Route::get('/', [CarrierCoursesController::class, 'index'])->name('index');
            Route::get('/create', [CarrierCoursesController::class, 'create'])->name('create');
            Route::post('/', [CarrierCoursesController::class, 'store'])->name('store');
            Route::get('/{course}/edit', [CarrierCoursesController::class, 'edit'])->name('edit');
            Route::put('/{course}', [CarrierCoursesController::class, 'update'])->name('update');
            Route::delete('/{course}', [CarrierCoursesController::class, 'destroy'])->name('destroy');
        });
        
        // Rutas para gestión de entrenamientos de conductores
        Route::prefix('trainings')->name('trainings.')->group(function () {
            // Document routes (must be defined before resource routes)
            Route::delete('/documents/{document}', [CarrierTrainingsController::class, 'destroyDocument'])
                ->name('documents.delete');
            Route::get('/documents/{document}/preview', [CarrierTrainingsController::class, 'previewDocument'])
                ->name('documents.preview');
            
            // Assignment routes
            Route::get('/select-for-assignment', [CarrierTrainingAssignmentsController::class, 'assignSelect'])
                ->name('assign.select');
            Route::get('/{training}/assign', [CarrierTrainingAssignmentsController::class, 'showAssignForm'])
                ->name('assign.form');
            Route::post('/{training}/assign', [CarrierTrainingAssignmentsController::class, 'assign'])
                ->name('assign');
            Route::get('/carrier/{carrier}/drivers', [CarrierTrainingAssignmentsController::class, 'getDrivers'])
                ->name('drivers.by.carrier');
            
            // Resource routes
            Route::get('/', [CarrierTrainingsController::class, 'index'])->name('index');
            Route::get('/create', [CarrierTrainingsController::class, 'create'])->name('create');
            Route::post('/', [CarrierTrainingsController::class, 'store'])->name('store');
            Route::get('/{training}', [CarrierTrainingsController::class, 'show'])->name('show');
            Route::get('/{training}/edit', [CarrierTrainingsController::class, 'edit'])->name('edit');
            Route::put('/{training}', [CarrierTrainingsController::class, 'update'])->name('update');
            Route::delete('/{training}', [CarrierTrainingsController::class, 'destroy'])->name('destroy');
        });
        
        // Rutas para gestión de marcas de vehículos
        Route::prefix('vehicle-makes')->name('vehicle-makes.')->group(function () {
            Route::get('/', [App\Http\Controllers\Carrier\VehicleMakeController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Carrier\VehicleMakeController::class, 'store'])->name('store');
            Route::put('/{vehicleMake}', [App\Http\Controllers\Carrier\VehicleMakeController::class, 'update'])->name('update');
            Route::delete('/{vehicleMake}', [App\Http\Controllers\Carrier\VehicleMakeController::class, 'destroy'])->name('destroy');
            Route::get('/search', [App\Http\Controllers\Carrier\VehicleMakeController::class, 'search'])->name('search');
        });
        
        // Rutas para gestión de tipos de vehículos
        Route::prefix('vehicle-types')->name('vehicle-types.')->group(function () {
            Route::get('/', [App\Http\Controllers\Carrier\VehicleTypeController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Carrier\VehicleTypeController::class, 'store'])->name('store');
            Route::put('/{vehicleType}', [App\Http\Controllers\Carrier\VehicleTypeController::class, 'update'])->name('update');
            Route::delete('/{vehicleType}', [App\Http\Controllers\Carrier\VehicleTypeController::class, 'destroy'])->name('destroy');
            Route::get('/search', [App\Http\Controllers\Carrier\VehicleTypeController::class, 'search'])->name('search');
        });
        
        // Rutas para reportes del carrier
        Route::prefix('reports')->name('reports.')->group(function () {
            // Dashboard de reportes
            Route::get('/', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'index'])->name('index');
            
            // Reportes de conductores
            Route::get('/drivers', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'drivers'])->name('drivers');
            Route::get('/drivers/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'driversExportPdf'])->name('drivers.export-pdf');
            
            // Reportes de vehículos
            Route::get('/vehicles', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'vehicles'])->name('vehicles');
            Route::get('/vehicles/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'vehiclesExportPdf'])->name('vehicles.export-pdf');
            
            // Reportes de accidentes
            Route::get('/accidents', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'accidents'])->name('accidents');
            Route::get('/accidents/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'accidentsExportPdf'])->name('accidents.export-pdf');
            
            // Reportes de registros médicos
            Route::get('/medical-records', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'medicalRecords'])->name('medical-records');
            Route::get('/medical-records/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'medicalRecordsExportPdf'])->name('medical-records.export-pdf');
            
            // Reportes de licencias
            Route::get('/licenses', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'licenses'])->name('licenses');
            Route::get('/licenses/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'licensesExportPdf'])->name('licenses.export-pdf');
            
            // Reportes de mantenimiento
            Route::get('/maintenance', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'maintenance'])->name('maintenance');
            Route::get('/maintenance/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'maintenanceExportPdf'])->name('maintenance.export-pdf');
            
            // Reportes de reparaciones
            Route::get('/repairs', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'repairs'])->name('repairs');
            Route::get('/repairs/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'repairsExportPdf'])->name('repairs.export-pdf');
            
            // Resumen mensual
            Route::get('/monthly', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'monthly'])->name('monthly');
            Route::get('/monthly/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'monthlyExportPdf'])->name('monthly.export-pdf');
            
            // Reportes de Trips (HOS)
            Route::get('/trips', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'trips'])->name('trips');
            Route::get('/trips/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'tripsExportPdf'])->name('trips.export-pdf');
            
            // Reportes de HOS (Hours of Service)
            Route::get('/hos', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'hos'])->name('hos');
            Route::get('/hos/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'hosExportPdf'])->name('hos.export-pdf');
            
            // Reportes de Violaciones HOS
            Route::get('/violations', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'violations'])->name('violations');
            Route::get('/violations/export-pdf', [App\Http\Controllers\Carrier\CarrierReportsController::class, 'violationsExportPdf'])->name('violations.export-pdf');
        });

        // HOS Documents Routes
        Route::prefix('hos/documents')->name('hos.documents.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'index'])->name('index');
            Route::post('/daily-log', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'generateDailyLog'])->name('daily-log');
            Route::post('/monthly-summary', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'generateMonthlySummary'])->name('monthly-summary');
            Route::post('/document-monthly', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'generateDocumentMonthly'])->name('document-monthly');
            Route::get('/{media}/download', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'download'])->name('download');
            Route::delete('/{media}', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-download', [\App\Http\Controllers\Carrier\HosDocumentController::class, 'bulkDownload'])->name('bulk-download');
        });

        // Messages Routes
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Carrier\MessagesController::class, 'index'])->name('index');
            Route::get('/dashboard', [\App\Http\Controllers\Carrier\MessagesController::class, 'dashboard'])->name('dashboard');
            Route::get('/create', [\App\Http\Controllers\Carrier\MessagesController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Carrier\MessagesController::class, 'store'])->name('store');
            Route::get('/{message}', [\App\Http\Controllers\Carrier\MessagesController::class, 'show'])->name('show');
        });

        // Notifications Routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', function () {
                return view('carrier.notifications.index', ['activeTheme' => session('activeTheme', 'raze')]);
            })->name('index');
            Route::get('/preferences', function () {
                return view('carrier.notifications.preferences', ['activeTheme' => session('activeTheme', 'raze')]);
            })->name('preferences');
        });
    });
});
