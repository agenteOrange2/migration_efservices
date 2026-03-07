<?php

namespace App\Services\Hos;

use App\Models\UserDriverDetail;
use App\Models\Hos\HosViolation;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class DriverAvailabilityService
{
    public function __construct(
        protected HosWeeklyCycleService $weeklyCycleService,
        protected HosFMCSAService $fmcsaService
    ) {}

    /**
     * Check availability for all drivers of a carrier
     */
    public function getAvailableDrivers(int $carrierId, ?int $tripDurationMinutes = null): Collection
    {
        $drivers = UserDriverDetail::where('carrier_id', $carrierId)
            ->with(['user', 'hosConfiguration'])
            ->get();

        return $drivers->map(function ($driver) use ($tripDurationMinutes) {
            return $this->getDriverAvailability($driver, $tripDurationMinutes);
        });
    }

    /**
     * Get detailed availability for a specific driver
     */
    public function getDriverAvailability(UserDriverDetail $driver, ?int $tripDurationMinutes = null): array
    {
        $weeklyStatus = $this->weeklyCycleService->getWeeklyCycleStatus($driver->id);
        $fmcsaStatus = $this->fmcsaService->getDriverFMCSAStatus($driver->id);
        $hasBlockingPenalty = $this->fmcsaService->hasBlockingPenalty($driver->id);
        
        $remainingDailyMinutes = $this->calculateRemainingDailyMinutes($driver->id);
        $remainingWeeklyMinutes = $weeklyStatus['remaining_minutes'] ?? 0;

        $canDrive = !$hasBlockingPenalty && $remainingDailyMinutes > 0 && $remainingWeeklyMinutes > 0;
        $canCompleteTripDuration = true;
        
        if ($tripDurationMinutes !== null) {
            $canCompleteTripDuration = $remainingDailyMinutes >= $tripDurationMinutes 
                && $remainingWeeklyMinutes >= $tripDurationMinutes;
        }

        $activePenalty = $this->getActivePenalty($driver->id);

        return [
            'driver_id' => $driver->id,
            'driver_name' => $driver->user->name ?? 'Unknown',
            'current_status' => $fmcsaStatus['current_status'] ?? 'unknown',
            'remaining_daily_hours' => round($remainingDailyMinutes / 60, 1),
            'remaining_daily_minutes' => $remainingDailyMinutes,
            'remaining_weekly_hours' => round($remainingWeeklyMinutes / 60, 1),
            'remaining_weekly_minutes' => $remainingWeeklyMinutes,
            'weekly_cycle_type' => $weeklyStatus['cycle_type'] ?? '70_8',
            'weekly_percentage_used' => $weeklyStatus['percentage_used'] ?? 0,
            'can_drive' => $canDrive,
            'can_complete_trip' => $canCompleteTripDuration && $canDrive,
            'has_blocking_penalty' => $hasBlockingPenalty,
            'active_penalty' => $activePenalty,
            'next_available_at' => $this->calculateNextAvailableTime($driver->id, $hasBlockingPenalty, $activePenalty),
        ];
    }

    /**
     * Check if a driver can complete a trip of given duration
     */
    public function checkAvailability(int $driverId, int $tripDurationMinutes): array
    {
        $driver = UserDriverDetail::find($driverId);
        if (!$driver) {
            return [
                'available' => false,
                'reason' => 'Driver not found',
            ];
        }

        $availability = $this->getDriverAvailability($driver, $tripDurationMinutes);

        if ($availability['has_blocking_penalty']) {
            return [
                'available' => false,
                'reason' => 'Driver has active penalty: ' . ($availability['active_penalty']['type'] ?? 'unknown'),
                'penalty_expires_at' => $availability['active_penalty']['expires_at'] ?? null,
            ];
        }

        if ($availability['remaining_daily_minutes'] < $tripDurationMinutes) {
            return [
                'available' => false,
                'reason' => 'Insufficient daily hours remaining',
                'remaining_minutes' => $availability['remaining_daily_minutes'],
                'required_minutes' => $tripDurationMinutes,
            ];
        }

        if ($availability['remaining_weekly_minutes'] < $tripDurationMinutes) {
            return [
                'available' => false,
                'reason' => 'Insufficient weekly hours remaining',
                'remaining_minutes' => $availability['remaining_weekly_minutes'],
                'required_minutes' => $tripDurationMinutes,
            ];
        }

        return [
            'available' => true,
            'remaining_daily_minutes' => $availability['remaining_daily_minutes'],
            'remaining_weekly_minutes' => $availability['remaining_weekly_minutes'],
        ];
    }

    /**
     * Get drivers available for a specific trip duration
     */
    public function getDriversForTripDuration(int $carrierId, int $tripDurationMinutes): Collection
    {
        return $this->getAvailableDrivers($carrierId, $tripDurationMinutes)
            ->filter(fn($driver) => $driver['can_complete_trip']);
    }

    protected function calculateRemainingDailyMinutes(int $driverId): int
    {
        $maxDrivingMinutes = 720; // 12 hours
        $todayDrivingMinutes = $this->getTodayDrivingMinutes($driverId);
        return max(0, $maxDrivingMinutes - $todayDrivingMinutes);
    }

    protected function getTodayDrivingMinutes(int $driverId): int
    {
        return \App\Models\Hos\HosEntry::where('user_driver_detail_id', $driverId)
            ->whereDate('start_time', Carbon::today())
            ->where('status', 'on_duty_driving')
            ->sum(\DB::raw('TIMESTAMPDIFF(MINUTE, start_time, COALESCE(end_time, NOW()))'));
    }

    protected function getActivePenalty(int $driverId): ?array
    {
        $violation = HosViolation::where('user_driver_detail_id', $driverId)
            ->where('has_penalty', true)
            ->whereNotIn('penalty_type', ['none', 'warning'])
            ->where(function ($query) {
                $query->whereNull('penalty_end')
                    ->orWhere('penalty_end', '>', Carbon::now());
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$violation) {
            return null;
        }

        return [
            'type' => $violation->penalty_type,
            'expires_at' => $violation->penalty_end?->toIso8601String(),
            'violation_type' => $violation->violation_type,
        ];
    }

    protected function calculateNextAvailableTime(int $driverId, bool $hasBlockingPenalty, ?array $activePenalty): ?string
    {
        if (!$hasBlockingPenalty) {
            return null;
        }

        if ($activePenalty && isset($activePenalty['expires_at'])) {
            return $activePenalty['expires_at'];
        }

        return null;
    }
}
