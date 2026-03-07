<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Carrier\CarrierRegistrationService;
use App\Services\Carrier\CarrierReportService;
use App\Services\Driver\DriverApplicationService;
use App\Services\Vehicle\VehicleAssignmentService;
use App\Services\Hos\HosTimeFormatter;
use App\Services\Hos\HosCalculationService;
use App\Services\Hos\HosAlertService;
use App\Services\Hos\HosService;
use App\Services\Hos\HosConfigurationService;
use App\Services\Hos\HosReportService;
use App\Services\Hos\HosWeeklyCycleService;
use App\Services\Hos\HosFMCSAService;
use App\Services\Hos\HosGhostLogDetectionService;
use App\Services\Hos\DriverAvailabilityService;
use App\Services\Trip\TripService;
use App\Services\Trip\TripGpsTrackingService;
use App\Services\Trip\TripPauseService;

/**
 * Service Layer Service Provider
 * 
 * Registra todos los servicios de la capa de negocio como singletons.
 */
class ServiceLayerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Carrier Services
        $this->app->singleton(CarrierRegistrationService::class, function ($app) {
            return new CarrierRegistrationService();
        });

        $this->app->singleton(CarrierReportService::class, function ($app) {
            return new CarrierReportService();
        });

        // Driver Services
        $this->app->singleton(DriverApplicationService::class, function ($app) {
            return new DriverApplicationService();
        });

        // Vehicle Services
        $this->app->singleton(VehicleAssignmentService::class, function ($app) {
            return new VehicleAssignmentService();
        });

        // HOS Services
        $this->app->singleton(HosCalculationService::class, function ($app) {
            return new HosCalculationService();
        });

        $this->app->singleton(HosAlertService::class, function ($app) {
            return new HosAlertService(
                $app->make(HosCalculationService::class)
            );
        });

        $this->app->singleton(HosService::class, function ($app) {
            return new HosService(
                $app->make(HosCalculationService::class),
                $app->make(HosAlertService::class)
            );
        });

        $this->app->singleton(HosConfigurationService::class, function ($app) {
            return new HosConfigurationService();
        });

        $this->app->singleton(HosReportService::class, function ($app) {
            return new HosReportService(
                $app->make(HosCalculationService::class),
                $app->make(HosAlertService::class)
            );
        });

        // FMCSA HOS Services
        $this->app->singleton(HosWeeklyCycleService::class, function ($app) {
            return new HosWeeklyCycleService();
        });

        $this->app->singleton(HosFMCSAService::class, function ($app) {
            return new HosFMCSAService(
                $app->make(HosWeeklyCycleService::class)
            );
        });

        $this->app->singleton(HosGhostLogDetectionService::class, function ($app) {
            return new HosGhostLogDetectionService();
        });

        $this->app->singleton(DriverAvailabilityService::class, function ($app) {
            return new DriverAvailabilityService(
                $app->make(HosWeeklyCycleService::class),
                $app->make(HosFMCSAService::class)
            );
        });

        // Trip Services
        $this->app->singleton(TripGpsTrackingService::class, function ($app) {
            return new TripGpsTrackingService();
        });

        $this->app->singleton(TripPauseService::class, function ($app) {
            return new TripPauseService();
        });

        $this->app->singleton(TripService::class, function ($app) {
            return new TripService(
                $app->make(HosFMCSAService::class),
                $app->make(HosWeeklyCycleService::class),
                $app->make(TripPauseService::class)
            );
        });
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
            CarrierRegistrationService::class,
            CarrierReportService::class,
            DriverApplicationService::class,
            VehicleAssignmentService::class,
            HosCalculationService::class,
            HosAlertService::class,
            HosService::class,
            HosConfigurationService::class,
            HosReportService::class,
            HosWeeklyCycleService::class,
            HosFMCSAService::class,
            HosGhostLogDetectionService::class,
            DriverAvailabilityService::class,
            TripService::class,
            TripGpsTrackingService::class,
            TripPauseService::class,
        ];
    }
}
