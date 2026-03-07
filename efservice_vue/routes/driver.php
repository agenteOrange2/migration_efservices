<?php

use App\Http\Controllers\Auth\DriverRegistrationController;
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
| Driver Dashboard & Operations (Authenticated + Driver Status)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'check.driver.status'])->group(function () {
    // TODO: Dashboard, HOS, trips, etc. - will be migrated in later phases
});
