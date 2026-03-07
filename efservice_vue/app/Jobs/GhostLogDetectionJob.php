<?php

namespace App\Jobs;

use App\Models\Hos\HosEntry;
use App\Models\Trip;
use App\Services\Hos\HosGhostLogDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GhostLogDetectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(HosGhostLogDetectionService $ghostLogService): void
    {
        // Get all active driving entries
        $activeEntries = HosEntry::where('status', 'on_duty_driving')
            ->whereNull('end_time')
            ->where('is_ghost_log', false)
            ->with(['driver.hosConfiguration', 'trip'])
            ->get();

        foreach ($activeEntries as $entry) {
            try {
                $trip = $entry->trip ?? Trip::where('user_driver_detail_id', $entry->user_driver_detail_id)
                    ->where('status', 'in_progress')
                    ->first();

                if (!$trip) {
                    continue;
                }

                $isGhostLog = $ghostLogService->checkForGhostLog($entry->user_driver_detail_id, $trip);

                if ($isGhostLog) {
                    $ghostLogService->processGhostLog(
                        $entry->user_driver_detail_id,
                        $entry,
                        'Zero speed detected for threshold period'
                    );

                    Log::info('Ghost log detected and processed', [
                        'driver_id' => $entry->user_driver_detail_id,
                        'entry_id' => $entry->id,
                        'trip_id' => $trip->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error processing ghost log detection', [
                    'entry_id' => $entry->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
