<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\CarrierRepositoryInterface;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use App\Repositories\CarrierRepository;
use App\Repositories\DriverRepository;
use App\Repositories\VehicleRepository;

/**
 * Repository Service Provider
 * 
 * Registra todos los repositorios del sistema.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind repository interfaces to implementations
        $this->app->bind(
            CarrierRepositoryInterface::class,
            CarrierRepository::class
        );

        $this->app->bind(
            DriverRepositoryInterface::class,
            DriverRepository::class
        );

        $this->app->bind(
            VehicleRepositoryInterface::class,
            VehicleRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            CarrierRepositoryInterface::class,
            DriverRepositoryInterface::class,
            VehicleRepositoryInterface::class,
        ];
    }
}
