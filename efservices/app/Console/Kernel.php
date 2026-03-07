<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SendMaintenanceDueNotifications::class,
        Commands\CheckDriverExpirations::class,
        Commands\CheckVehicleExpirations::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Vehicle Maintenance Due Notifications - daily at 7:30 AM
        // Notifies drivers, carriers, and admins at 30, 15, and 7 days before due date
        $schedule->command('maintenance:send-notifications --days=30,15,7')->dailyAt('07:30');

        // FMCSA HOS Jobs
        // ===========================================
        
        // HOS Auto-Stop Check - EVERY MINUTE (Critical for safety compliance)
        // Checks active trips and auto-stops if HOS limits are exceeded
        $schedule->job(new \App\Jobs\HosAutoStopJob)
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        
        // Ghost log detection - every 5 minutes
        // Detects when driver is marked as "driving" but GPS shows no movement
        $schedule->job(new \App\Jobs\GhostLogDetectionJob)
            ->everyFiveMinutes()
            ->withoutOverlapping();
        
        // Weekly cycle calculation - daily at midnight
        // Recalculates weekly hours for all drivers
        $schedule->job(new \App\Jobs\WeeklyCycleCalculationJob)->dailyAt('00:00');
        
        // Reset detection - hourly
        // Detects 10h/24h/34h reset periods for drivers
        $schedule->job(new \App\Jobs\ResetDetectionJob)->hourly();
        
        // HOS Documents cleanup - monthly on the 1st at 2:00 AM (7-year retention policy)
        $schedule->job(new \App\Jobs\CleanupOldHosDocuments)->monthlyOn(1, '02:00');

        // Driver License & Medical Card Expiration Check - daily at 7:00 AM
        // Notifies drivers, carriers, and admins at 30, 15, and 7 days before expiration
        $schedule->command('drivers:check-expirations --days=30,15,7')->dailyAt('07:00');

        // Vehicle Registration, Inspection & Document Expiration Check - daily at 7:15 AM
        $schedule->command('vehicles:check-expirations --days=30,15,7')->dailyAt('07:15');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
