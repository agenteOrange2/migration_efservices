<?php

namespace App\Services\Hos;

use Carbon\Carbon;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosConfiguration;
use Illuminate\Support\Collection;

class HosCalculationService
{
    /**
     * Calculate daily totals for a driver on a specific date.
     *
     * @param int $driverId
     * @param Carbon|string $date
     * @return array
     */
    public function calculateDailyTotals(int $driverId, $date): array
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateString = $date->format('Y-m-d');
        
        $totals = [
            'driving_minutes' => 0,
            'on_duty_minutes' => 0,
            'off_duty_minutes' => 0,
            'total_minutes' => 0,
        ];

        // Get entries for this date
        $entries = HosEntry::forDriver($driverId)
            ->forDate($date)
            ->orderBy('start_time')
            ->get();

        foreach ($entries as $entry) {
            $duration = $this->calculateEntryDurationForDate($entry, $date);
            
            switch ($entry->status) {
                case HosEntry::STATUS_ON_DUTY_DRIVING:
                    $totals['driving_minutes'] += $duration;
                    break;
                case HosEntry::STATUS_ON_DUTY_NOT_DRIVING:
                    $totals['on_duty_minutes'] += $duration;
                    break;
                case HosEntry::STATUS_OFF_DUTY:
                    $totals['off_duty_minutes'] += $duration;
                    break;
            }
        }

        // Also check for entries from previous day that span into this date
        $previousDayEntries = HosEntry::forDriver($driverId)
            ->whereDate('date', $date->copy()->subDay())
            ->whereNotNull('end_time')
            ->where('end_time', '>', $date->copy()->startOfDay())
            ->get();

        foreach ($previousDayEntries as $entry) {
            $duration = $this->calculateEntryDurationForDate($entry, $date);
            
            switch ($entry->status) {
                case HosEntry::STATUS_ON_DUTY_DRIVING:
                    $totals['driving_minutes'] += $duration;
                    break;
                case HosEntry::STATUS_ON_DUTY_NOT_DRIVING:
                    $totals['on_duty_minutes'] += $duration;
                    break;
                case HosEntry::STATUS_OFF_DUTY:
                    $totals['off_duty_minutes'] += $duration;
                    break;
            }
        }

        $totals['total_minutes'] = $totals['driving_minutes'] + 
                                   $totals['on_duty_minutes'] + 
                                   $totals['off_duty_minutes'];

        // Add formatted versions
        $totals['driving_formatted'] = HosTimeFormatter::formatTime($totals['driving_minutes']);
        $totals['on_duty_formatted'] = HosTimeFormatter::formatTime($totals['on_duty_minutes']);
        $totals['off_duty_formatted'] = HosTimeFormatter::formatTime($totals['off_duty_minutes']);
        $totals['total_formatted'] = HosTimeFormatter::formatTime($totals['total_minutes']);

        return $totals;
    }

    /**
     * Calculate entry duration for a specific date (handles midnight crossing).
     *
     * @param HosEntry $entry
     * @param Carbon $date
     * @return int Minutes
     */
    protected function calculateEntryDurationForDate(HosEntry $entry, Carbon $date): int
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();
        
        $entryStart = $entry->start_time;
        $entryEnd = $entry->end_time ?? Carbon::now();

        // Clamp to the day boundaries
        $effectiveStart = $entryStart->lt($dayStart) ? $dayStart : $entryStart;
        $effectiveEnd = $entryEnd->gt($dayEnd) ? $dayEnd : $entryEnd;

        // If entry doesn't overlap with this day
        if ($effectiveStart->gte($effectiveEnd)) {
            return 0;
        }

        return (int) $effectiveStart->diffInMinutes($effectiveEnd);
    }

    /**
     * Calculate remaining hours for a driver.
     *
     * @param int $driverId
     * @param Carbon|string $date
     * @param HosConfiguration|null $config
     * @return array
     */
    public function calculateRemainingHours(int $driverId, $date, ?HosConfiguration $config = null): array
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $totals = $this->calculateDailyTotals($driverId, $date);
        
        // Get configuration or use defaults
        if (!$config) {
            $driver = \App\Models\UserDriverDetail::find($driverId);
            $config = ($driver && $driver->carrier_id) ? HosConfiguration::getForCarrier($driver->carrier_id) : null;
        }

        $maxDrivingMinutes = $config 
            ? $config->max_driving_minutes 
            : HosTimeFormatter::hoursToMinutes(HosConfiguration::DEFAULT_MAX_DRIVING_HOURS);
        
        $maxDutyMinutes = $config 
            ? $config->max_duty_minutes 
            : HosTimeFormatter::hoursToMinutes(HosConfiguration::DEFAULT_MAX_DUTY_HOURS);

        $totalDutyMinutes = $totals['driving_minutes'] + $totals['on_duty_minutes'];

        $remainingDriving = max(0, $maxDrivingMinutes - $totals['driving_minutes']);
        $remainingDuty = max(0, $maxDutyMinutes - $totalDutyMinutes);

        return [
            'remaining_driving_minutes' => $remainingDriving,
            'remaining_duty_minutes' => $remainingDuty,
            'remaining_driving_formatted' => HosTimeFormatter::formatTime($remainingDriving),
            'remaining_duty_formatted' => HosTimeFormatter::formatTime($remainingDuty),
            'used_driving_minutes' => $totals['driving_minutes'],
            'used_duty_minutes' => $totalDutyMinutes,
            'max_driving_minutes' => $maxDrivingMinutes,
            'max_duty_minutes' => $maxDutyMinutes,
            'is_driving_exceeded' => $totals['driving_minutes'] > $maxDrivingMinutes,
            'is_duty_exceeded' => $totalDutyMinutes > $maxDutyMinutes,
        ];
    }

    /**
     * Calculate monthly totals for a driver.
     *
     * @param int $driverId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function calculateMonthlyTotals(int $driverId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        $dailyLogs = HosDailyLog::forDriver($driverId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('date')
            ->get();

        $totals = [
            'driving_minutes' => 0,
            'on_duty_minutes' => 0,
            'off_duty_minutes' => 0,
            'total_minutes' => 0,
            'days_worked' => 0,
            'days_with_violations' => 0,
            'daily_breakdown' => [],
        ];

        foreach ($dailyLogs as $log) {
            $totals['driving_minutes'] += $log->total_driving_minutes;
            $totals['on_duty_minutes'] += $log->total_on_duty_minutes;
            $totals['off_duty_minutes'] += $log->total_off_duty_minutes;
            
            if ($log->total_driving_minutes > 0 || $log->total_on_duty_minutes > 0) {
                $totals['days_worked']++;
            }
            
            if ($log->has_violations) {
                $totals['days_with_violations']++;
            }

            $totals['daily_breakdown'][] = [
                'date' => $log->date->format('Y-m-d'),
                'driving_minutes' => $log->total_driving_minutes,
                'on_duty_minutes' => $log->total_on_duty_minutes,
                'off_duty_minutes' => $log->total_off_duty_minutes,
                'has_violations' => $log->has_violations,
            ];
        }

        $totals['total_minutes'] = $totals['driving_minutes'] + 
                                   $totals['on_duty_minutes'] + 
                                   $totals['off_duty_minutes'];

        // Add formatted versions
        $totals['driving_formatted'] = HosTimeFormatter::formatTime($totals['driving_minutes']);
        $totals['on_duty_formatted'] = HosTimeFormatter::formatTime($totals['on_duty_minutes']);
        $totals['off_duty_formatted'] = HosTimeFormatter::formatTime($totals['off_duty_minutes']);
        $totals['total_formatted'] = HosTimeFormatter::formatTime($totals['total_minutes']);

        return $totals;
    }

    /**
     * Recalculate and update the daily log for a driver.
     *
     * @param int $driverId
     * @param Carbon|string $date
     * @return HosDailyLog
     */
    public function recalculateDailyLog(int $driverId, $date): HosDailyLog
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $totals = $this->calculateDailyTotals($driverId, $date);

        $driver = \App\Models\UserDriverDetail::find($driverId);
        
        // Check for violations
        $remaining = $this->calculateRemainingHours($driverId, $date);
        $hasViolations = $remaining['is_driving_exceeded'] || $remaining['is_duty_exceeded'];

        // Get the vehicle from the most recent entry
        $latestEntry = HosEntry::forDriver($driverId)
            ->forDate($date)
            ->orderBy('start_time', 'desc')
            ->first();

        $dailyLog = HosDailyLog::updateOrCreate(
            [
                'user_driver_detail_id' => $driverId,
                'date' => $date->format('Y-m-d'),
            ],
            [
                'carrier_id' => $driver->carrier_id,
                'vehicle_id' => $latestEntry?->vehicle_id,
                'total_driving_minutes' => $totals['driving_minutes'],
                'total_on_duty_minutes' => $totals['on_duty_minutes'],
                'total_off_duty_minutes' => $totals['off_duty_minutes'],
                'has_violations' => $hasViolations,
            ]
        );

        return $dailyLog;
    }
}
