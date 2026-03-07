<?php

use App\Models\Carrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TempUploadController;
use App\Http\Controllers\Admin\UserDriverController;
use App\Http\Controllers\Api\UserDriverApiController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\TempUploadController as ApiTempUploadController;
use App\Http\Controllers\Api\ZipCodeController;

// Ruta pública para eliminar documentos de manera segura (solo requiere CSRF)
// Esta ruta es necesaria para el funcionamiento del modal de eliminación de documentos
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('documents/delete', [DocumentController::class, 'safeDeletePost'])->name('api.documents.delete.post');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Ruta para eliminar documentos de manera segura (evitando la eliminación en cascada)
    Route::delete('documents/{mediaId}', [DocumentController::class, 'safeDelete'])->name('api.documents.delete');
    
    // Traffic Convictions API
    Route::put('/traffic/convictions/{conviction}', [\App\Http\Controllers\Admin\Driver\TrafficConvictionsController::class, 'apiUpdate']);
    
    // Driver Testing API Routes
    Route::prefix('drivers/testing')->name('api.drivers.testing.')->group(function () {
        Route::get('search-carriers', [\App\Http\Controllers\Admin\Driver\DriverTestingController::class, 'searchCarriers'])->name('search-carriers');
        Route::get('get-drivers/{carrier}', [\App\Http\Controllers\Admin\Driver\DriverTestingController::class, 'getDriversByCarrier'])->name('get-drivers');
        Route::get('by-carrier/{carrier}', [\App\Http\Controllers\Admin\Driver\DriverTestingController::class, 'getDriversByCarrier'])->name('by-carrier');
        Route::get('driver-details/{driverDetail}', [\App\Http\Controllers\Admin\Driver\DriverTestingController::class, 'getDriverDetails'])->name('driver-details');
    });
});

// La ruta para obtener conductores filtrados por transportista se ha movido a admin.php

// Ruta para obtener conductores activos por carrier
Route::get('/active-drivers-by-carrier/{carrierId}', [\App\Http\Controllers\Admin\ReportsController::class, 'getActiveDriversByCarrier']);

// Ruta para obtener todos los carriers activos
Route::get('/active-carriers', function () {
    $carriers = \App\Models\Carrier::where('status', 'active')
        ->orderBy('name')
        ->get(['id', 'name', 'dot_number']);
    return response()->json($carriers);
});

// Rutas API para sistema de carga temporal de licencias de conductor
Route::prefix('driver')->middleware(['validate.upload.session', 'throttle:120,1'])->group(function () {
    // Endpoint principal para carga temporal de licencias
    Route::post('/upload-license-temp', [ApiTempUploadController::class, 'uploadLicense'])
        ->name('api.driver.upload-license-temp');
    
    // Obtener preview de licencia temporal
    Route::get('/temp-license/{id}/preview', [ApiTempUploadController::class, 'previewLicense'])
        ->name('api.driver.temp-license.preview');
    
    // Eliminar archivo temporal
    Route::delete('/temp-license/{id}', [ApiTempUploadController::class, 'deleteTempLicense'])
        ->name('api.driver.temp-license.delete');
    
    // Validar contenido de licencia (OCR opcional)
    Route::post('/validate-license', [ApiTempUploadController::class, 'validateLicense'])
        ->name('api.driver.validate-license');
    
    // Obtener todos los uploads de una sesión
    Route::get('/session-uploads', [ApiTempUploadController::class, 'getSessionUploads'])
        ->name('api.driver.session-uploads');
});

// Rutas API para gestión de documentos
Route::prefix('documents')->middleware(['throttle:120,1'])->group(function () {
    // Ruta para carga temporal de archivos
    Route::post('/upload', [UploadController::class, 'upload']);
    
    // Ruta para upload directo de licencias
    Route::post('/upload-license-direct', [UploadController::class, 'uploadLicenseDirect']);
    
    // Ruta para upload directo de certificados
    Route::post('/upload-certificate-direct', [UploadController::class, 'uploadCertificateDirect']);
    
    // Rutas para guardar documentos permanentes en diferentes colecciones
    Route::post('/store', [UploadController::class, 'storeDocument']);
    
    // Ruta para eliminar documentos
    Route::delete('/{id}', [UploadController::class, 'deleteDocument']);
    
    // Ruta para eliminar media de una colección específica
    Route::post('/delete-media', [UploadController::class, 'deleteMedia']);
    
    // Ruta para obtener documentos de un modelo
    Route::get('/model/{type}/{id}', [UploadController::class, 'getDocuments']);
});

// API Routes for Vehicle Management
Route::prefix('vehicles')->middleware(['throttle:60,1'])->group(function () {
    // Vehicle Makes API
    Route::post('/makes', [\App\Http\Controllers\Admin\Vehicles\VehicleController::class, 'apiCreateMake'])
        ->name('api.vehicles.makes.store');
    Route::get('/makes', [\App\Http\Controllers\Admin\Vehicles\VehicleController::class, 'apiGetMakes'])
        ->name('api.vehicles.makes.index');
    
    // Vehicle Types API
    Route::post('/types', [\App\Http\Controllers\Admin\Vehicles\VehicleController::class, 'apiCreateType'])
        ->name('api.vehicles.types.store');
    Route::get('/types', [\App\Http\Controllers\Admin\Vehicles\VehicleController::class, 'apiGetTypes'])
        ->name('api.vehicles.types.index');
});

// ZIP Code Validation API
Route::post('/validate-zip-state', [ZipCodeController::class, 'validateZipForState'])
    ->name('api.validate-zip-state');

// Quick Search API Routes
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('search')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\SearchController::class, 'search'])->name('api.search');
    Route::get('/navigation', [\App\Http\Controllers\Api\SearchController::class, 'navigation'])->name('api.search.navigation');
    Route::get('/quick-actions', [\App\Http\Controllers\Api\SearchController::class, 'quickActions'])->name('api.search.quick-actions');
});

// Carrier API Routes (usando Repository Pattern)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('carriers')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\CarrierApiController::class, 'index'])->name('api.carriers.index');
    Route::get('/active', [\App\Http\Controllers\Api\CarrierApiController::class, 'active'])->name('api.carriers.active');
    Route::get('/pending-validation', [\App\Http\Controllers\Api\CarrierApiController::class, 'pendingValidation'])->name('api.carriers.pending-validation');
    Route::get('/search', [\App\Http\Controllers\Api\CarrierApiController::class, 'search'])->name('api.carriers.search');
    Route::get('/{slug}', [\App\Http\Controllers\Api\CarrierApiController::class, 'show'])->name('api.carriers.show');
    Route::get('/{slug}/limits', [\App\Http\Controllers\Api\CarrierApiController::class, 'limits'])->name('api.carriers.limits');
    Route::get('/{slug}/can-add-driver', [\App\Http\Controllers\Api\CarrierApiController::class, 'canAddDriver'])->name('api.carriers.can-add-driver');
    Route::get('/{slug}/can-add-vehicle', [\App\Http\Controllers\Api\CarrierApiController::class, 'canAddVehicle'])->name('api.carriers.can-add-vehicle');
});