<?php

namespace App\Console\Commands;

use App\Models\Hos\HosEntry;
use App\Services\Hos\HosCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Backfill / repair the hos_daily_logs table for ranges that were imported
 * before the import wired up recalculation. Run after a CSV import or any
 * time you need to re-aggregate entries into daily totals.
 *
 * Examples:
 *   php artisan hos:recalculate-daily-logs --driver=171
 *   php artisan hos:recalculate-daily-logs --driver=171 --from=2025-12-20 --to=2025-12-31
 *   php artisan hos:recalculate-daily-logs --carrier=4
 *   php artisan hos:recalculate-daily-logs            (every driver, every date with entries)
 */
class RecalculateHosDailyLogs extends Command
{
    protected $signature = 'hos:recalculate-daily-logs
                            {--driver= : Recalculate only this user_driver_detail_id}
                            {--carrier= : Recalculate every driver under this carrier_id}
                            {--from= : Earliest date (Y-m-d), defaults to the earliest entry}
                            {--to= : Latest date (Y-m-d), defaults to today}';

    protected $description = 'Recalculate hos_daily_logs totals from hos_entries (use after a CSV import).';

    public function handle(HosCalculationService $service): int
    {
        $driverId  = $this->option('driver') ? (int) $this->option('driver') : null;
        $carrierId = $this->option('carrier') ? (int) $this->option('carrier') : null;
        $from      = $this->option('from') ? Carbon::parse($this->option('from'))->startOfDay() : null;
        $to        = $this->option('to') ? Carbon::parse($this->option('to'))->endOfDay() : Carbon::now()->endOfDay();

        $query = HosEntry::query();

        if ($driverId !== null) {
            $query->where('user_driver_detail_id', $driverId);
        }
        if ($carrierId !== null) {
            $query->where('carrier_id', $carrierId);
        }
        if ($from !== null) {
            $query->where('date', '>=', $from->format('Y-m-d'));
        }
        if ($to !== null) {
            $query->where('date', '<=', $to->format('Y-m-d'));
        }

        $pairs = $query->select('user_driver_detail_id', 'date')
            ->groupBy('user_driver_detail_id', 'date')
            ->get();

        if ($pairs->isEmpty()) {
            $this->warn('No HOS entries match the given filters — nothing to recalculate.');
            return self::SUCCESS;
        }

        $this->info("Recalculating {$pairs->count()} (driver, date) daily logs...");
        $bar = $this->output->createProgressBar($pairs->count());
        $bar->start();

        $errors = 0;

        foreach ($pairs as $pair) {
            try {
                $service->recalculateDailyLog(
                    (int) $pair->user_driver_detail_id,
                    Carbon::parse($pair->date),
                );
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('hos:recalculate-daily-logs failed', [
                    'driver_id' => $pair->user_driver_detail_id,
                    'date'      => $pair->date,
                    'error'     => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($errors > 0) {
            $this->warn("Finished with {$errors} failures (see logs).");
            return self::FAILURE;
        }

        $this->info('All daily logs recalculated successfully.');
        return self::SUCCESS;
    }
}
