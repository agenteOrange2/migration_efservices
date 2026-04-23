<?php

namespace App\Console\Commands;

use App\Models\Admin\Vehicle\Vehicle;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoSuspendExpiredVehicles extends Command
{
    protected $signature = 'vehicles:auto-suspend-expired';

    protected $description = 'Automatically suspend active vehicles whose registration or annual inspection has expired';

    public function handle(): int
    {
        $today = Carbon::today();

        $expired = Vehicle::where('status', Vehicle::STATUS_ACTIVE)
            ->where(function ($q) use ($today) {
                $q->where('registration_expiration_date', '<', $today)
                  ->orWhere(function ($q2) use ($today) {
                      $q2->whereNotNull('annual_inspection_expiration_date')
                         ->where('annual_inspection_expiration_date', '<', $today);
                  });
            })
            ->get();

        $count = 0;

        foreach ($expired as $vehicle) {
            $vehicle->update([
                'status'         => Vehicle::STATUS_SUSPENDED,
                'suspended'      => true,
                'suspended_date' => $today,
            ]);

            Log::info('Vehicle auto-suspended due to expired documents', [
                'vehicle_id'                        => $vehicle->id,
                'registration_expiration_date'      => $vehicle->registration_expiration_date?->toDateString(),
                'annual_inspection_expiration_date' => $vehicle->annual_inspection_expiration_date?->toDateString(),
            ]);

            $count++;
        }

        $this->info("Auto-suspended {$count} vehicle(s) with expired registration or inspection.");

        return self::SUCCESS;
    }
}
