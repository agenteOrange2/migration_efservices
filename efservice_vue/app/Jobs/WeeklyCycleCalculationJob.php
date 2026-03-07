<?php

namespace App\Jobs;

use App\Models\UserDriverDetail;
use App\Models\Hos\HosWeeklyCycle;
use App\Services\Hos\HosWeeklyCycleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WeeklyCycleCalculationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(HosWeeklyCycleService $cycleService): void
    {
        $drivers = UserDriverDetail::with('hosConfiguration')->get();

        foreach ($drivers as $driver) {
            try {
                $weeklyHours = $cycleService->calculateWeeklyHours($driver->id);
                
                HosWeeklyCycle::updateOrCreate(
                    [
                        'user_driver_detail_id' => $driver->id,
                        'is_active' => true,
                    ],
                    [
                        'carrier_id' => $driver->carrier_id,
                        'cycle_type' => $weeklyHours['cycle_type'] ?? '70_8',
                        'cycle_start_date' => Carbon::now()->subDays(
                            $weeklyHours['cycle_type'] === '60_7' ? 7 : 8
                        )->toDateString(),
                        'total_on_duty_minutes' => $weeklyHours['total_on_duty_minutes'] ?? 0,
                        'total_driving_minutes' => $weeklyHours['total_driving_minutes'] ?? 0,
                    ]
                );

                Log::debug('Weekly cycle calculated', [
                    'driver_id' => $driver->id,
                    'total_minutes' => $weeklyHours['total_on_duty_minutes'] ?? 0,
                ]);
            } catch (\Exception $e) {
                Log::error('Error calculating weekly cycle', [
                    'driver_id' => $driver->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
