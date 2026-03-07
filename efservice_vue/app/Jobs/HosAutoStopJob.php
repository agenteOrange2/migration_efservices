<?php

namespace App\Jobs;

use App\Services\Hos\HosAutoStopService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HosAutoStopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Execute the job.
     */
    public function handle(HosAutoStopService $autoStopService): void
    {
        Log::info('HOS Auto-Stop Job started');

        try {
            $results = $autoStopService->checkAndAutoStopActiveTrips();

            Log::info('HOS Auto-Stop Job completed', [
                'trips_checked' => $results['checked'],
                'warnings_sent' => $results['warnings_sent'],
                'auto_stopped' => $results['auto_stopped'],
                'errors' => $results['errors'],
            ]);

            // Log details if any action was taken
            if (!empty($results['details'])) {
                foreach ($results['details'] as $detail) {
                    Log::info('HOS Auto-Stop action taken', $detail);
                }
            }
        } catch (\Exception $e) {
            Log::error('HOS Auto-Stop Job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('HOS Auto-Stop Job failed completely', [
            'error' => $exception->getMessage(),
        ]);
    }
}
