<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Middleware\CheckUserStatus;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Providers\EventServiceProvider;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web', 'auth', 'check.role.access:superadmin', 'check.admin.status')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware(['web', 'check.role.access:user_carrier'])
                ->prefix('carrier')
                ->name('carrier.')
                ->group(base_path('routes/carrier.php'));

            Route::middleware(['web', 'check.role.access:user_driver'])
                ->prefix('driver')
                ->name('driver.')
                ->group(base_path('routes/driver.php'));

            // Cargar rutas de debug solo en entorno local
            if (app()->environment('local')) {
                Route::middleware('web')
                    ->group(base_path('routes/debug.php'));
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registrar alias de middleware
        $middleware->alias([
            // Specific status middlewares
            'check.carrier.status' => \App\Http\Middleware\CheckCarrierStatus::class,
            'check.driver.status' => \App\Http\Middleware\CheckDriverStatus::class,
            'check.admin.status' => \App\Http\Middleware\CheckAdminStatus::class,

            // Other middlewares
            'check.role.access' => \App\Http\Middleware\CheckRoleAccess::class,
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimit::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'prevent.mass.assignment' => \App\Http\Middleware\PreventMassAssignment::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'validate.upload.session' => \App\Http\Middleware\ValidateUploadSession::class
        ]);

        // Middleware web group
        $middleware->web([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SecurityHeaders::class,
            
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ...existing code...
    })
    ->create();