<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Driver\StepController;
use App\Http\Controllers\Driver\StatusController;
use App\Livewire\Driver\CarrierSelectionComponent;
use App\Livewire\Driver\DriverRegistrationManager;
use App\Http\Controllers\Driver\TempUploadController;
use App\Http\Controllers\Driver\DashboardController;
use App\Http\Controllers\Driver\DriverDashboardController;
use App\Http\Controllers\Driver\RegistrationController;
use App\Http\Controllers\Auth\DriverRegistrationController;


// Rutas para el módulo de entrenamientos del conductor (requieren verificación de estado)
Route::middleware(['auth', 'check.driver.status'])->prefix('trainings')->name('trainings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Driver\DriverTrainingController::class, 'index'])->name('index');
    Route::get('/{driverTraining}', [\App\Http\Controllers\Driver\DriverTrainingController::class, 'show'])->name('show');
    Route::post('/{driverTraining}/complete', [\App\Http\Controllers\Driver\DriverTrainingController::class, 'complete'])->name('complete');
    Route::post('/{driverTraining}/start-progress', [\App\Http\Controllers\Driver\DriverTrainingController::class, 'startProgress'])->name('start-progress');
    Route::get('/documents/{media}/preview', [\App\Http\Controllers\Driver\DriverTrainingController::class, 'previewDocument'])->name('documents.preview');
});

// Rutas públicas (no requieren autenticación)
Route::middleware('guest')->group(function () {
    // Registro por referencia de carrier
    Route::get('driver-register/{carrier:slug}', [DriverRegistrationController::class, 'showRegistrationForm'])
        ->name('driver.register')
        ->where('carrier', '[a-z0-9-]+')
        ->whereUuid('token'); // Add token validation

    // Application routes
    // Route::prefix('application')->name('application.')->group(function () {
    //     Route::get('/step/{step}', [StepController::class, 'showStep'])->name('step');
    //     Route::post('/step/{step}', [StepController::class, 'processStep'])->name('process');
    //     Route::get('/success', [StepController::class, 'success'])->name('success');
    // });

    Route::get('/complete-registration', [DriverRegistrationController::class, 'showCompleteRegistration'])
        ->name('complete_registration');

    Route::post('driver-register/{carrier:slug}', [DriverRegistrationController::class, 'register'])
        ->name('driver.register.submit');

    // Rutas de error y estado (necesitan ser públicas)
    Route::get('error', function () {
        return view('auth.user_driver.error');
    })->name('register.error');

    Route::get('quota-exceeded', function () {
        return view('auth.user_driver.quota-exceeded');
    })->name('quota-exceeded');

    Route::get('driver-status', function () {
        return view('auth.user_driver.driver-status');
    })->name('status');

    // Confirmación de email
    Route::get('confirm/{token}', [DriverRegistrationController::class, 'confirmEmail'])
        ->name('confirm');

    Route::get('registration/success', function () {
        $carrierName = session('carrier_name', 'the carrier');
        return view('auth.user_driver.success', ['carrierName' => $carrierName]);
    })->name('registration.success');
});


// Ruta para mostrar la selección de carriers para registro independiente
Route::get('/register', [DriverRegistrationController::class, 'showIndependentCarrierSelection'])
    ->name('register');


// Modificar esta ruta para incluir el carrier_slug como parámetro de ruta

Route::get('/register/form/{carrier_slug}', [DriverRegistrationController::class, 'showIndependentRegistrationForm'])
    ->name('register.form');

// Ruta para procesar el registro independiente
Route::post('/register', [DriverRegistrationController::class, 'registerIndependent'])
    ->name('register.submit');

// Ruta para registro con referencia
Route::get('/register/{carrier:slug}', [DriverRegistrationController::class, 'showRegistrationForm'])
    ->name('register.referred');


// Ruta para registro con token (referencia)
Route::get('/register/{carrier:slug}/token/{token}', App\Livewire\Driver\DriverRegistrationManager::class)
    ->name('referred.registration');


// Rutas protegidas (requieren autenticación y verificación de estado del usuario)
Route::middleware(['auth', 'check.driver.status'])->group(function () {
    Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');
    Route::get('profile', [DriverDashboardController::class, 'profile'])->name('profile');
    
    // Notifications
    Route::get('notifications', function () {
        return view('driver.notifications.index', ['activeTheme' => session('activeTheme', 'raze')]);
    })->name('notifications.index');
    Route::get('notifications/preferences', function () {
        return view('driver.notifications.preferences', ['activeTheme' => session('activeTheme', 'raze')]);
    })->name('notifications.preferences');
    Route::get('profile/download-documents', [DriverDashboardController::class, 'downloadDocuments'])->name('profile.download-documents');
    
    // Driver Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/edit', [\App\Http\Controllers\Driver\DriverProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [\App\Http\Controllers\Driver\DriverProfileController::class, 'update'])->name('update');
        Route::post('/update-photo', [\App\Http\Controllers\Driver\DriverProfileController::class, 'updatePhoto'])->name('update-photo');
        Route::delete('/delete-photo', [\App\Http\Controllers\Driver\DriverProfileController::class, 'deletePhoto'])->name('delete-photo');
        Route::put('/update-password', [\App\Http\Controllers\Driver\DriverProfileController::class, 'updatePassword'])->name('update-password');
    });
    
    // Driver Licenses Management (Read-only - editing managed by carrier/admin)
    Route::get('licenses', [\App\Http\Controllers\Driver\DriverLicenseController::class, 'index'])->name('licenses.index');
    Route::get('licenses/{license}', [\App\Http\Controllers\Driver\DriverLicenseController::class, 'show'])->name('licenses.show');
    // Create, edit, update, delete routes disabled - license info is managed by carrier/admin
    // Route::resource('licenses', \App\Http\Controllers\Driver\DriverLicenseController::class)->names('licenses');
    // Route::delete('licenses/{license}/document/{media}', [\App\Http\Controllers\Driver\DriverLicenseController::class, 'deleteDocument'])->name('licenses.delete-document');
    
    // Driver Medical Management (Read-only - editing managed by carrier/admin)
    Route::prefix('medical')->name('medical.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Driver\DriverMedicalController::class, 'index'])->name('index');
        // Edit, update, upload and delete routes disabled - medical info is managed by carrier/admin
        // Route::get('/edit', [\App\Http\Controllers\Driver\DriverMedicalController::class, 'edit'])->name('edit');
        // Route::put('/update', [\App\Http\Controllers\Driver\DriverMedicalController::class, 'update'])->name('update');
        // Route::post('/upload', [\App\Http\Controllers\Driver\DriverMedicalController::class, 'uploadDocument'])->name('upload');
        // Route::delete('/document/{mediaId}', [\App\Http\Controllers\Driver\DriverMedicalController::class, 'deleteDocument'])->name('document.delete');
    });
    
    
    // Driver Vehicles Management
    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Driver\DriverVehicleController::class, 'index'])->name('index');
        Route::get('/{vehicle}', [\App\Http\Controllers\Driver\DriverVehicleController::class, 'show'])->name('show');
        // Vehicle document routes for driver access
        Route::get('/{vehicle}/documents/{document}/download', [\App\Http\Controllers\Driver\DriverVehicleController::class, 'downloadDocument'])->name('documents.download');
        Route::get('/{vehicle}/documents/{document}/preview', [\App\Http\Controllers\Driver\DriverVehicleController::class, 'previewDocument'])->name('documents.preview');
    });
    
    // Driver Maintenance Management
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'store'])->name('store');
        Route::get('/{maintenance}', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'show'])->name('show');
        Route::get('/{maintenance}/edit', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'edit'])->name('edit');
        Route::put('/{maintenance}', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'update'])->name('update');
        Route::delete('/{maintenance}', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'destroy'])->name('destroy');
        Route::post('/{maintenance}/complete', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'complete'])->name('complete');
        Route::post('/{maintenance}/upload-document', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'uploadDocument'])->name('upload-document');
        Route::delete('/{maintenance}/document/{mediaId}', [\App\Http\Controllers\Driver\DriverMaintenanceController::class, 'deleteDocument'])->name('delete-document');
    });
    
    // Driver Emergency Repairs Management
    Route::prefix('emergency-repairs')->name('emergency-repairs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Driver\DriverEmergencyRepairController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Driver\DriverEmergencyRepairController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Driver\DriverEmergencyRepairController::class, 'store'])->name('store');
        Route::get('/{repair}', [\App\Http\Controllers\Driver\DriverEmergencyRepairController::class, 'show'])->name('show');
        Route::get('/{repair}/edit', [\App\Http\Controllers\Driver\DriverEmergencyRepairController::class, 'edit'])->name('edit');
        Route::put('/{repair}', [\App\Http\Controllers\Driver\DriverEmergencyRepairController::class, 'update'])->name('update');
        Route::delete('/{repair}', [\App\Http\Controllers\Driver\DriverEmergencyRepairController::class, 'destroy'])->name('destroy');
        Route::post('/{repair}/upload-document', [\App\Http\Controllers\Driver\DriverEmergencyRepairController::class, 'uploadDocument'])->name('upload-document');
        Route::delete('/{repair}/document/{mediaId}', [\App\Http\Controllers\Driver\DriverEmergencyRepairController::class, 'deleteDocument'])->name('delete-document');
    });
    
    // Driver Testing Management
    Route::prefix('testing')->name('testing.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Driver\DriverTestingController::class, 'index'])->name('index');
        Route::get('/{testing}', [\App\Http\Controllers\Driver\DriverTestingController::class, 'show'])->name('show');
        Route::post('/{testing}/upload-results', [\App\Http\Controllers\Driver\DriverTestingController::class, 'uploadResults'])->name('upload-results');
    });
    
    // Driver Inspections Management
    Route::prefix('inspections')->name('inspections.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Driver\DriverInspectionController::class, 'index'])->name('index');
        Route::get('/{inspection}', [\App\Http\Controllers\Driver\DriverInspectionController::class, 'show'])->name('show');
    });
    
    // Driver Documents Management
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Driver\DriverDocumentController::class, 'index'])->name('index');
        Route::get('/upload', [\App\Http\Controllers\Driver\DriverDocumentController::class, 'create'])->name('create');
        Route::post('/upload', [\App\Http\Controllers\Driver\DriverDocumentController::class, 'store'])->name('store');
        Route::get('/download-all', [\App\Http\Controllers\Driver\DriverDocumentController::class, 'downloadAll'])->name('download-all');
        Route::delete('/{media}', [\App\Http\Controllers\Driver\DriverDocumentController::class, 'destroy'])->name('destroy');
    });
    
    // Estados de aplicación
    Route::get('pending', [StatusController::class, 'pending'])->name('pending');
    Route::get('rejected', [StatusController::class, 'rejected'])->name('rejected');
    Route::get('documents-pending', [StatusController::class, 'documentsPending'])->name('documents.pending');
    
    // Registro continuo
    Route::get('registration/continue/{step?}', [RegistrationController::class, 'continue'])->name('registration.continue');
    Route::post('registration/complete', [RegistrationController::class, 'complete'])->name('registration.complete');
    
    // Selección de transportista
    Route::get('/select-carrier', [DriverRegistrationController::class, 'showSelectCarrier'])->name('select_carrier');
    Route::post('/select-carrier', [DriverRegistrationController::class, 'selectCarrier'])->name('select_carrier.submit');

    // Status routes
    Route::get('/carrier-status', function () {
        return view('driver.status.carrier');
    })->name('carrier.status');

    // Messages Routes for drivers
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Driver\MessagesController::class, 'index'])->name('index');
        Route::get('/{recipient}', [\App\Http\Controllers\Driver\MessagesController::class, 'show'])->name('show');
        Route::post('/{recipient}/reply', [\App\Http\Controllers\Driver\MessagesController::class, 'reply'])->name('reply');
    });
});

// Ruta de carga temporal (sin CSRF para uploads AJAX)
Route::post('/temp-upload', [TempUploadController::class, 'upload'])
    ->name('driver.temp.upload')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);