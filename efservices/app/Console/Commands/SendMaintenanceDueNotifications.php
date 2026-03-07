<?php

namespace App\Console\Commands;

use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\User;
use App\Notifications\Admin\Vehicle\MaintenanceDueNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendMaintenanceDueNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:send-notifications {--days=30,15,7 : Comma-separated days before due date to notify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for upcoming vehicle maintenance services';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $daysThresholds = collect(explode(',', $this->option('days')))->map(fn($d) => (int) trim($d))->sort()->values();
        $maxDays = $daysThresholds->max();

        $this->info("Checking maintenance due within {$maxDays} days (thresholds: {$daysThresholds->join(', ')})...");
        
        // Get pending maintenances with vehicle and carrier data
        $maintenanceItems = VehicleMaintenance::upcoming($maxDays)
            ->with(['vehicle.carrier.userCarriers.user', 'vehicle.driver.user'])
            ->get();
            
        $this->info("Found {$maintenanceItems->count()} upcoming maintenance items.");
        
        if ($maintenanceItems->isEmpty()) {
            return 0;
        }
        
        // Get superadmin users
        $admins = User::role('superadmin')->get();
        
        $notificationCount = 0;
        
        foreach ($maintenanceItems as $maintenance) {
            if (!$maintenance->next_service_date) {
                continue;
            }

            $daysRemaining = (int) Carbon::today()->diffInDays($maintenance->next_service_date, false);
            $vehicle = $maintenance->vehicle;
            
            if (!$vehicle || $daysRemaining < 0) {
                continue;
            }

            // Notify only on exact threshold days
            if (!$daysThresholds->contains($daysRemaining)) {
                continue;
            }

            $unitLabel = $vehicle->company_unit_number ?? $vehicle->id;
            $usersToNotify = collect();
            
            // 1. Superadmins
            $usersToNotify = $usersToNotify->merge($admins);
            
            // 2. Carrier users
            if ($vehicle->carrier) {
                foreach ($vehicle->carrier->userCarriers as $carrierDetail) {
                    if ($carrierDetail->user) {
                        $usersToNotify->push($carrierDetail->user);
                    }
                }
            }
            
            // 3. Assigned driver
            if ($vehicle->driver && $vehicle->driver->user) {
                $usersToNotify->push($vehicle->driver->user);
            }
            
            // Remove duplicates
            $usersToNotify = $usersToNotify->unique('id');
            
            // Send notifications
            foreach ($usersToNotify as $user) {
                $user->notify(new MaintenanceDueNotification($maintenance, $daysRemaining));
                $notificationCount++;
            }
            
            $this->info("Maintenance ID: {$maintenance->id}, Vehicle: Unit #{$unitLabel}, Days: {$daysRemaining}, Users: {$usersToNotify->count()}");
        }
        
        $this->info("Total notifications sent: {$notificationCount}");
        Log::info('Maintenance notifications check completed', [
            'notifications_sent' => $notificationCount,
        ]);
        
        return 0;
    }
}
