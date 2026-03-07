<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Driver\DriverLicensesController;

/*
|--------------------------------------------------------------------------
| Debug Routes
|--------------------------------------------------------------------------
|
| Estas rutas solo están disponibles en entornos de desarrollo local.
| NUNCA deben estar activas en producción.
|
*/

// Solo cargar estas rutas en entorno local
if (app()->environment('local')) {
    
    // Debug de licencias de conductor
    Route::get('/debug-license/{license}', function($licenseId) {
        $license = \App\Models\Admin\Driver\DriverLicense::with('driverDetail.carrier')->find($licenseId);
        
        if (!$license) {
            return response()->json(['error' => 'License not found'], 404);
        }
        
        return response()->json([
            'license_id' => $license->id,
            'is_cdl' => $license->is_cdl,
            'is_cdl_type' => gettype($license->is_cdl),
            'is_cdl_raw' => $license->getRawOriginal('is_cdl'),
            'endorsements' => [
                'endorsement_n' => [
                    'value' => $license->endorsement_n,
                    'type' => gettype($license->endorsement_n),
                    'raw' => $license->getRawOriginal('endorsement_n')
                ],
                'endorsement_h' => [
                    'value' => $license->endorsement_h,
                    'type' => gettype($license->endorsement_h),
                    'raw' => $license->getRawOriginal('endorsement_h')
                ],
                'endorsement_x' => [
                    'value' => $license->endorsement_x,
                    'type' => gettype($license->endorsement_x),
                    'raw' => $license->getRawOriginal('endorsement_x')
                ],
                'endorsement_t' => [
                    'value' => $license->endorsement_t,
                    'type' => gettype($license->endorsement_t),
                    'raw' => $license->getRawOriginal('endorsement_t')
                ],
                'endorsement_p' => [
                    'value' => $license->endorsement_p,
                    'type' => gettype($license->endorsement_p),
                    'raw' => $license->getRawOriginal('endorsement_p')
                ],
                'endorsement_s' => [
                    'value' => $license->endorsement_s,
                    'type' => gettype($license->endorsement_s),
                    'raw' => $license->getRawOriginal('endorsement_s')
                ]
            ],
            'old_simulation' => [
                'is_cdl' => old('is_cdl', $license->is_cdl),
                'endorsement_n' => old('endorsement_n', $license->endorsement_n),
                'endorsement_h' => old('endorsement_h', $license->endorsement_h),
                'endorsement_x' => old('endorsement_x', $license->endorsement_x),
                'endorsement_t' => old('endorsement_t', $license->endorsement_t),
                'endorsement_p' => old('endorsement_p', $license->endorsement_p),
                'endorsement_s' => old('endorsement_s', $license->endorsement_s)
            ]
        ]);
    })->name('debug.license');

    // Debug de calendario
    Route::get('/debug-calendar', function() {
        return view('debug.calendar');
    })->name('debug.calendar');

    // Test de formulario de driver
    Route::get('/test-driver-form', function () {
        return view('test-driver-form');
    })->name('debug.driver-form');

    // Debug de endorsements de licencias (admin)
    Route::middleware(['auth', 'role:superadmin|admin'])->group(function () {
        Route::get('/admin/licenses/{license}/debug-endorsements', [DriverLicensesController::class, 'debugEndorsements'])
            ->name('debug.licenses.endorsements');
    });
}
