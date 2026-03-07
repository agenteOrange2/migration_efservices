<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Services\AuthenticationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthenticationService::class, function ($app) {
            return new AuthenticationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Unified authentication logic using AuthenticationService
        // This handles all user types: admin, carrier, driver
        // Validates user status BEFORE creating session
        Fortify::authenticateUsing(function (Request $request) {
            /** @var AuthenticationService $authService */
            $authService = app(AuthenticationService::class);
            
            $email = $request->input(Fortify::username());
            $password = $request->input('password');
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            
            // AuthenticationService handles:
            // 1. User lookup
            // 2. Password validation
            // 3. Status validation (throws ValidationException if inactive)
            // 4. Comprehensive logging
            return $authService->authenticate($email, $password, $ip, $userAgent);
        });

        // Vista personalizada para user_carrier
        Fortify::loginView(function (Request $request) {
            if ($request->is('user-carrier/*')) {
                return view('auth.user_carrier.login'); // Vista específica para user_carrier
            }

            if ($request->is('user-driver/*')) {
                return view('auth.user_driver.login'); // Vista específica para user_driver
            }

            return view('auth.login'); // Vista por defecto
        });

        // También puedes registrar vistas de registro similares si es necesario.
        Fortify::registerView(function (Request $request) {
            if ($request->is('user-carrier/*')) {
                return view('auth.user_carrier.register'); // Vista específica para user_carrier
            }

            if ($request->is('user-driver/*')) {
                return view('auth.user_driver.register'); // Vista específica para user_driver
            }

            return view('auth.register'); // Vista por defecto
        });

        // Configurar redirección después del login usando AuthenticationService
        Fortify::redirects('login', function (Request $request) {
            $user = $request->user();
            
            if (!$user) {
                Log::warning('AUTH_REDIRECT_NO_USER', [
                    'message' => 'No user found in request during redirect'
                ]);
                return '/';
            }
            
            /** @var AuthenticationService $authService */
            $authService = app(AuthenticationService::class);
            
            return $authService->determineRedirect($user);
        });

        // Configurar redirección después del registro usando AuthenticationService
        Fortify::redirects('register', function (Request $request) {
            $user = $request->user();
            
            if (!$user) {
                return '/';
            }
            
            /** @var AuthenticationService $authService */
            $authService = app(AuthenticationService::class);
            
            return $authService->determineRedirect($user);
        });
    }
}
