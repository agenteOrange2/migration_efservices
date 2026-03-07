<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
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
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'check.role.access'         => \App\Http\Middleware\CheckRoleAccess::class,
            'check.admin.status'        => \App\Http\Middleware\CheckAdminStatus::class,
            'check.carrier.status'      => \App\Http\Middleware\CheckCarrierStatus::class,
            'check.driver.status'       => \App\Http\Middleware\CheckDriverStatus::class,
            'check.permission'          => \App\Http\Middleware\CheckPermission::class,
            'check.user.status'         => \App\Http\Middleware\CheckUserStatus::class,
            'ensure.carrier.registered' => \App\Http\Middleware\EnsureCarrierRegistered::class,
            'api.rate.limit'            => \App\Http\Middleware\ApiRateLimit::class,
            'json.response'             => \App\Http\Middleware\JsonResponseMiddleware::class,
            'log.archive.access'        => \App\Http\Middleware\LogArchiveAccess::class,
            'prevent.mass.assignment'   => \App\Http\Middleware\PreventMassAssignment::class,
            'validate.upload.session'   => \App\Http\Middleware\ValidateUploadSession::class,
            'role'                      => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'                => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'        => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
