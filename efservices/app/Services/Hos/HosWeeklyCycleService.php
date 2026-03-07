<?php

namespace App\Services\Hos;

use Carbon\Carbon;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosWeeklyCycle;
use App\Models\Hos\HosConfiguration;
use App\Models\UserDriverDetail;
use Illuminate\Support\Collection;

class HosWeeklyCycleService
{
    /**
     * Calculate weekly hours for a driver using rolling period.
     */
    public function calculateWeeklyHours(int $driverId, ?string $cycleType = null): array
    {
        $config = $this->getDriverConfiguration($driverId);
        $cycleType = $cycleType ?? ($config ? '60_7' : '60_7');
        
        $days = $cycleType === '70_8' ? 8 : 7;
        $endDate = now()->endOfDay();
        $startDate = now()->subDays($days - 1)->startOfDay();

        // Get daily logs for the rolling period
        $dailyLogs = HosDailyLog::forDriver($driverId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalDrivingMinutes = $dailyLogs->sum('total_driving_minutes');
        $totalOnDutyMinutes = $dailyLogs->sum('total_on_duty_minutes');
        $totalDutyMinutes = $totalDrivingMinutes + $totalOnDutyMinutes;

        $limitMinutes = $cycleType === '70_8' 
            ? HosWeeklyCycle::LIMIT_70_HOURS 
            : HosWeeklyCycle::LIMIT_60_HOURS;

        return [
            'cycle_type' => $cycleType,
            'days_in_cycle' => $days,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_driving_minutes' => $totalDrivingMinutes,
            'total_on_duty_minutes' => $totalOnDutyMinutes,
            'total_duty_minutes' => $totalDutyMinutes,
            'limit_minutes' => $limitMinutes,
            'remaining_minutes' => max(0, $limitMinutes - $totalDutyMinutes),
            'percentage_used' => $limitMinutes > 0 
                ? round(($totalDutyMinutes / $limitMinutes) * 100, 1) 
                : 0,
            'is_over_limit' => $totalDutyMinutes >= $limitMinutes,
        ];
    }

    /**
     * Get remaining weekly hours for a driver.
     */
    public function getRemainingWeeklyHours(int $driverId, ?string $cycleType = null): int
    {
        $weeklyData = $this->calculateWeeklyHours($driverId, $cycleType);
        return $weeklyData['remaining_minutes'];
    }

    /**
     * Check for reset periods (8h, 24h, 34h).
     * Texas Intrastate: 8 hours off-duty resets daily driving limit
     */
    public function checkForReset(int $driverId): ?string
    {
        $consecutiveOffDuty = $this->getConsecutiveOffDutyMinutes($driverId);

        // Check for 34-hour reset (full weekly cycle reset)
        if ($consecutiveOffDuty >= 34 * 60) {
            return '34_hour';
        }

        // Check for 24-hour reset (Texas construction/oilfield)
        $config = $this->getDriverConfiguration($driverId);
        if ($config && $config->allows24HourReset() && $consecutiveOffDuty >= 24 * 60) {
            return '24_hour';
        }

        // Check for 8-hour reset (Texas Intrastate daily duty period reset)
        if ($consecutiveOffDuty >= 8 * 60) {
            return '8_hour';
        }

        return null;
    }

    /**
     * Apply a reset to the driver's cycle.
     */
    public function applyReset(int $driverId, string $resetType): void
    {
        $cycle = HosWeeklyCycle::forDriver($driverId)->current()->first();

        if (!$cycle) {
            return;
        }

        switch ($resetType) {
            case '34_hour':
            case '24_hour':
                // Full weekly cycle reset
                $cycle->resetCycle();
                $this->logReset($driverId, $resetType);
                break;

            case '8_hour':
                // Daily duty period reset (Texas Intrastate - 8h)
                $dailyLog = HosDailyLog::forDriver($driverId)
                    ->whereDate('date', today())
                    ->first();
                
                if ($dailyLog) {
                    $dailyLog->record10HourReset(); // Method name kept for compatibility
                }
                $this->logReset($driverId, $resetType);
                break;
        }
    }

    /**
     * Get weekly cycle status for display.
     * FIX: Now uses driver's individual cycle type instead of hardcoded value.
     */
    public function getWeeklyCycleStatus(int $driverId): array
    {
        // Get driver's effective cycle type (from driver settings or carrier default)
        $cycleType = $this->getDriverCycleType($driverId);

        $weeklyData = $this->calculateWeeklyHours($driverId, $cycleType);
        
        // Determine status color
        $statusColor = 'green';
        if ($weeklyData['percentage_used'] >= 90) {
            $statusColor = 'red';
        } elseif ($weeklyData['percentage_used'] >= 75) {
            $statusColor = 'yellow';
        }

        $hoursLimit = $cycleType === '70_8' ? 70 : 60;

        return [
            'cycle_type' => $weeklyData['cycle_type'],
            'cycle_type_name' => $cycleType === '70_8' ? '70 hours / 8 days' : '60 hours / 7 days',
            'hours_used' => round($weeklyData['total_duty_minutes'] / 60, 2),
            'hours_remaining' => round($weeklyData['remaining_minutes'] / 60, 2),
            'hours_limit' => $hoursLimit,
            'percentage_used' => $weeklyData['percentage_used'],
            'status_color' => $statusColor,
            'is_over_limit' => $weeklyData['is_over_limit'],
            'is_approaching_limit' => $weeklyData['percentage_used'] >= 90,
            'is_at_warning' => $weeklyData['percentage_used'] >= 75 && $weeklyData['percentage_used'] < 90,
        ];
    }

    /**
     * Get daily breakdown for the last N days.
     */
    public function getDailyBreakdown(int $driverId, int $days = 7): array
    {
        $endDate = now()->endOfDay();
        $startDate = now()->subDays($days - 1)->startOfDay();

        $dailyLogs = HosDailyLog::forDriver($driverId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $breakdown = [];
        $currentDate = $endDate->copy();

        for ($i = 0; $i < $days; $i++) {
            $dateStr = $currentDate->toDateString();
            $log = $dailyLogs->firstWhere('date', $currentDate->toDateString());

            $breakdown[] = [
                'date' => $dateStr,
                'day_name' => $currentDate->format('l'),
                'driving_minutes' => $log ? $log->total_driving_minutes : 0,
                'on_duty_minutes' => $log ? $log->total_on_duty_minutes : 0,
                'total_duty_minutes' => $log 
                    ? ($log->total_driving_minutes + $log->total_on_duty_minutes) 
                    : 0,
                'driving_hours' => $log ? round($log->total_driving_minutes / 60, 2) : 0,
                'on_duty_hours' => $log ? round($log->total_on_duty_minutes / 60, 2) : 0,
                'has_violations' => $log ? $log->has_violations : false,
            ];

            $currentDate->subDay();
        }

        return $breakdown;
    }

    /**
     * Get consecutive off-duty minutes for a driver.
     */
    public function getConsecutiveOffDutyMinutes(int $driverId): int
    {
        // Get the last non-off-duty entry
        $lastOnDutyEntry = HosEntry::forDriver($driverId)
            ->where('status', '!=', HosEntry::STATUS_OFF_DUTY)
            ->orderBy('end_time', 'desc')
            ->first();

        if (!$lastOnDutyEntry || !$lastOnDutyEntry->end_time) {
            // Check if there's any off-duty entry
            $firstOffDuty = HosEntry::forDriver($driverId)
                ->where('status', HosEntry::STATUS_OFF_DUTY)
                ->orderBy('start_time', 'asc')
                ->first();

            if ($firstOffDuty) {
                return (int) $firstOffDuty->start_time->diffInMinutes(now());
            }

            // NEW: If driver has NO HOS entries at all, they are considered fully rested
            // A new driver should be able to start their first trip
            $hasAnyEntry = HosEntry::forDriver($driverId)->exists();
            if (!$hasAnyEntry) {
                return 10 * 60; // Return 10 hours (600 minutes) - fully rested
            }

            return 0;
        }

        // Calculate minutes since last on-duty ended
        return (int) $lastOnDutyEntry->end_time->diffInMinutes(now());
    }

    /**
     * Check if driver has completed 8-hour reset (Texas Intrastate).
     */
    public function hasCompleted8HourReset(int $driverId): bool
    {
        return $this->getConsecutiveOffDutyMinutes($driverId) >= 8 * 60;
    }

    /**
     * Check if driver can start a new duty period.
     * Texas Intrastate: 8 hours off-duty required.
     */
    public function canStartNewDutyPeriod(int $driverId): array
    {
        $consecutiveOffDuty = $this->getConsecutiveOffDutyMinutes($driverId);
        $requiredMinutes = 8 * 60; // 8 hours (Texas Intrastate)

        $canStart = $consecutiveOffDuty >= $requiredMinutes;
        $minutesNeeded = max(0, $requiredMinutes - $consecutiveOffDuty);

        return [
            'can_start' => $canStart,
            'consecutive_off_duty_minutes' => $consecutiveOffDuty,
            'required_minutes' => $requiredMinutes,
            'minutes_needed' => $minutesNeeded,
            'hours_needed' => round($minutesNeeded / 60, 2),
        ];
    }

    /**
     * Get driver's HOS configuration.
     */
    protected function getDriverConfiguration(int $driverId): ?HosConfiguration
    {
        // Get carrier ID from driver's daily log or entry
        $dailyLog = HosDailyLog::forDriver($driverId)->latest('date')->first();
        
        if ($dailyLog && $dailyLog->carrier_id) {
            return HosConfiguration::getForCarrier($dailyLog->carrier_id);
        }

        return null;
    }

    /**
     * Get the driver's effective HOS cycle type.
     * Uses driver's individual setting, falling back to default.
     */
    protected function getDriverCycleType(int $driverId): string
    {
        $driver = UserDriverDetail::find($driverId);
        
        if ($driver) {
            return $driver->getEffectiveHosCycleType();
        }

        // Fallback: Texas Intrastate default is 70/8
        return '70_8';
    }

    /**
     * Log a reset event.
     */
    protected function logReset(int $driverId, string $resetType): void
    {
        $dailyLog = HosDailyLog::forDriver($driverId)
            ->whereDate('date', today())
            ->first();

        if ($dailyLog) {
            $dailyLog->update([
                'last_10_hour_reset_at' => now(),
                'consecutive_off_duty_minutes' => 0,
            ]);
        }

        // Update weekly cycle if it's a full reset
        if (in_array($resetType, ['24_hour', '34_hour'])) {
            $cycle = HosWeeklyCycle::forDriver($driverId)->current()->first();
            if ($cycle) {
                $cycle->update([
                    'reset_completed_at' => now(),
                    'has_24_hour_reset' => $resetType === '24_hour',
                ]);
            }
        }
    }
}
