<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomLoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\VehicleVerificationController;
use App\Http\Controllers\EmploymentVerificationController;
use App\Http\Controllers\Admin\NotificationRecipientsController;
use App\Http\Controllers\Admin\Driver\DriverLicensesController as AdminDriverLicensesController;


Route::redirect('/user-carrier/register', '/carrier/register');
// Ruta de confirmación
Route::get('/confirm/{token}', [CustomLoginController::class, 'confirmEmail'])->name('confirm');

// Rutas que requieren autenticación pero NO son de carrier (estas deben ir en carrier.php)
Route::middleware(['auth'])->group(function () {
    // Aquí solo rutas generales autenticadas que no sean de carrier
});

// Rutas para verificación de vehículos de terceros (sin autenticación)
Route::prefix('vehicle-verification')->name('vehicle.verification.')->group(function () {
    // Mostrar formulario de verificación
    Route::get('/{token}', [VehicleVerificationController::class, 'showVerificationForm'])
        ->name('form');
    
    // Procesar la verificación
    Route::post('/{token}/process', [VehicleVerificationController::class, 'processVerification'])
        ->name('process');
    
    // Página de agradecimiento
    Route::get('/{token}/thank-you', [VehicleVerificationController::class, 'showThankYou'])
        ->name('thank-you');
});

// Rutas para verificación de empleo (sin autenticación)
Route::prefix('employment-verification')->name('employment-verification.')->group(function () {
    // IMPORTANTE: Las rutas específicas deben ir ANTES de las rutas con parámetros
    
    // Página de agradecimiento
    Route::get('/thank-you', [EmploymentVerificationController::class, 'thankYou'])
        ->name('thank-you');
    
    // Página de token expirado
    Route::get('/expired', [EmploymentVerificationController::class, 'expired'])
        ->name('expired');
    
    // Página de error
    Route::get('/error', [EmploymentVerificationController::class, 'error'])
        ->name('error');
    
    // Mostrar formulario de verificación (debe ir después de las rutas específicas)
    Route::get('/{token}', [EmploymentVerificationController::class, 'showVerificationForm'])
        ->name('form');
    
    // Procesar la verificación
    Route::post('/{token}/process', [EmploymentVerificationController::class, 'processVerification'])
        ->name('process');
});

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Ruta dashboard genérica que redirige según el rol del usuario
Route::get('/dashboard', function () {
    $user = auth()->user();
    
    if (!$user) {
        return redirect()->route('login');
    }
    
    // Redirigir según el rol del usuario
    if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }
    
    if ($user->hasRole('user_carrier')) {
        return redirect()->route('carrier.dashboard');
    }
    
    if ($user->hasRole('user_driver')) {
        return redirect()->route('driver.dashboard');
    }
    
    // Por defecto, redirigir al login
    return redirect()->route('login');
})->middleware('auth')->name('dashboard');

// Ruta personalizada para cierre de sesión
Route::post('/custom-logout', [LogoutController::class, 'logout'])->name('custom.logout');

// Rutas de administración para destinatarios de notificaciones
// NOTA: Estas rutas están duplicadas y ya están definidas en routes/admin.php línea 1153-1159
// Se comentan para evitar conflictos
/*
Route::middleware(['auth', 'role:superadmin'])->prefix('admin')->group(function () {
    Route::get('/notification-recipients', [NotificationRecipientsController::class, 'index'])->name('admin.notification-recipients.index');
    Route::post('/notification-recipients', [NotificationRecipientsController::class, 'store'])->name('admin.notification-recipients.store');
    Route::delete('/notification-recipients/{recipient}', [NotificationRecipientsController::class, 'destroy'])->name('admin.notification-recipients.destroy');
    Route::patch('/notification-recipients/{recipient}/toggle', [NotificationRecipientsController::class, 'toggle'])->name('admin.notification-recipients.toggle');
    Route::get('/notification-recipients/users', [NotificationRecipientsController::class, 'getUsers'])->name('admin.notification-recipients.users');
});
*/
// Test route for toast notifications (remove in production)
Route::get('/test-toast-notifications', function () {
    return view('test-toast-notifications');
})->name('test.toast.notifications');


/*
|--------------------------------------------------------------------------
| HOS (Hours of Service) Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/hos.php';

/*
|--------------------------------------------------------------------------
| Bulk Import Routes
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\BulkImportController;

/*
|--------------------------------------------------------------------------
| Public Website Submission Routes
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\WebSubmissionController;

Route::post('/submit-contact', [WebSubmissionController::class, 'submitContact'])->name('submit.contact');
Route::post('/submit-plan-request', [WebSubmissionController::class, 'submitPlanRequest'])->name('submit.plan-request');

Route::middleware(['auth', 'role:superadmin'])->prefix('admin/imports')->name('admin.imports.')->group(function () {
    Route::get('/', [BulkImportController::class, 'index'])->name('index');
    Route::get('/template/{type}', [BulkImportController::class, 'downloadTemplate'])->name('template');
    Route::post('/preview', [BulkImportController::class, 'preview'])->name('preview');
    Route::post('/execute', [BulkImportController::class, 'execute'])->name('execute');
});
