<?php

namespace App\Jobs;

use App\Models\UserDriverDetail;
use App\Services\Hos\HosWeeklyCycleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResetDetectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(HosWeeklyCycleService $cycleService): void
    {
        $drivers = UserDriverDetail::with('hosConfiguration')->get();

        foreach ($drivers as $driver) {
            try {
                $resetType = $cycleService->checkForReset($driver->id);

                if ($resetType) {
                    $cycleService->applyReset($driver->id, $resetType);

                    Log::info('Reset applied to driver', [
                        'driver_id' => $driver->id,
                        'reset_type' => $resetType,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error detecting/applying reset', [
                    'driver_id' => $driver->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
