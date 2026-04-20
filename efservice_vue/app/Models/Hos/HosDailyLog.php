<?php

namespace App\Models\Hos;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HosDailyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_driver_detail_id',
        'carrier_id',
        'vehicle_id',
        'date',
        'total_driving_minutes',
        'total_on_duty_minutes',
        'total_off_duty_minutes',
        'has_violations',
        'driver_signature',
        'signed_at',
        // FMCSA fields
        'duty_period_start',
        'duty_period_end',
        'duty_period_minutes',
        'break_minutes',
        'thirty_minute_break_taken',
        'last_10_hour_reset_at',
        'consecutive_off_duty_minutes',
    ];

    protected $casts = [
        'date' => 'date',
        'total_driving_minutes' => 'integer',
        'total_on_duty_minutes' => 'integer',
        'total_off_duty_minutes' => 'integer',
        'has_violations' => 'boolean',
        'signed_at' => 'datetime',
        // FMCSA casts
        'duty_period_start' => 'datetime',
        'duty_period_end' => 'datetime',
        'duty_period_minutes' => 'integer',
        'break_minutes' => 'integer',
        'thirty_minute_break_taken' => 'boolean',
        'last_10_hour_reset_at' => 'datetime',
        'consecutive_off_duty_minutes' => 'integer',
    ];

    /**
     * Get the driver associated with this daily log.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriverDetail::class, 'user_driver_detail_id');
    }

    /**
     * Get the carrier associated with this daily log.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Get the vehicle associated with this daily log.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get total driving hours as decimal.
     */
    public function getTotalDrivingHoursAttribute(): float
    {
        return round($this->total_driving_minutes / 60, 2);
    }

    /**
     * Get total on-duty hours as decimal.
     */
    public function getTotalOnDutyHoursAttribute(): float
    {
        return round($this->total_on_duty_minutes / 60, 2);
    }

    /**
     * Get total off-duty hours as decimal.
     */
    public function getTotalOffDutyHoursAttribute(): float
    {
        return round($this->total_off_duty_minutes / 60, 2);
    }

    /**
     * Get formatted driving time.
     */
    public function getFormattedDrivingTimeAttribute(): string
    {
        return $this->formatMinutes($this->total_driving_minutes);
    }

    /**
     * Get formatted on-duty time.
     */
    public function getFormattedOnDutyTimeAttribute(): string
    {
        return $this->formatMinutes($this->total_on_duty_minutes);
    }

    /**
     * Get formatted off-duty time.
     */
    public function getFormattedOffDutyTimeAttribute(): string
    {
        return $this->formatMinutes($this->total_off_duty_minutes);
    }

    /**
     * Get total minutes for the day.
     */
    public function getTotalMinutesAttribute(): int
    {
        return $this->total_driving_minutes + 
               $this->total_on_duty_minutes + 
               $this->total_off_duty_minutes;
    }

    /**
     * Get duty period elapsed minutes.
     */
    public function getDutyPeriodElapsedMinutesAttribute(): int
    {
        if (!$this->duty_period_start) {
            return 0;
        }
        
        $end = $this->duty_period_end ?? now();
        return (int) $this->duty_period_start->diffInMinutes($end);
    }

    /**
     * Get remaining duty period minutes (14h window).
     */
    public function getRemainingDutyPeriodMinutesAttribute(): int
    {
        $maxDutyPeriod = 14 * 60; // 14 hours in minutes
        return max(0, $maxDutyPeriod - $this->duty_period_elapsed_minutes);
    }

    /**
     * Check if duty period is active.
     */
    public function isDutyPeriodActive(): bool
    {
        return $this->duty_period_start !== null && $this->duty_period_end === null;
    }

    /**
     * Check if 30-minute break has been taken.
     */
    public function hasCompletedRequiredBreak(): bool
    {
        return $this->thirty_minute_break_taken === true;
    }

    /**
     * Start duty period.
     */
    public function startDutyPeriod(): void
    {
        if (!$this->duty_period_start) {
            $this->update([
                'duty_period_start' => now(),
            ]);
        }
    }

    /**
     * End duty period.
     */
    public function endDutyPeriod(): void
    {
        if ($this->duty_period_start && !$this->duty_period_end) {
            $this->update([
                'duty_period_end' => now(),
                'duty_period_minutes' => $this->duty_period_elapsed_minutes,
            ]);
        }
    }

    /**
     * Record a break.
     */
    public function recordBreak(int $minutes): void
    {
        $this->increment('break_minutes', $minutes);
        
        if ($this->break_minutes >= 30) {
            $this->update(['thirty_minute_break_taken' => true]);
        }
    }

    /**
     * Record 10-hour reset.
     */
    public function record10HourReset(): void
    {
        $this->update([
            'last_10_hour_reset_at' => now(),
            'consecutive_off_duty_minutes' => 0,
        ]);
    }

    /**
     * Check if the log is signed.
     */
    public function isSigned(): bool
    {
        return !is_null($this->driver_signature) && !is_null($this->signed_at);
    }

    /**
     * Sign the daily log.
     */
    public function sign(string $signature): void
    {
        $this->update([
            'driver_signature' => $signature,
            'signed_at' => now(),
        ]);
    }

    /**
     * Format minutes to "Xh Ym" format.
     */
    protected function formatMinutes(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}h {$mins}m";
    }

    /**
     * Get or create daily log for a driver on a specific date.
     */
    public static function getOrCreateForDate(int $driverId, int $carrierId, ?int $vehicleId, $date): self
    {
        $dateOnly = $date instanceof \Carbon\Carbon ? $date->toDateString() : substr((string) $date, 0, 10);

        return self::firstOrCreate(
            [
                'user_driver_detail_id' => $driverId,
                'date' => $dateOnly,
            ],
            [
                'carrier_id' => $carrierId,
                'vehicle_id' => $vehicleId,
                'total_driving_minutes' => 0,
                'total_on_duty_minutes' => 0,
                'total_off_duty_minutes' => 0,
                'has_violations' => false,
            ]
        );
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by driver.
     */
    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('user_driver_detail_id', $driverId);
    }

    /**
     * Scope to filter by carrier.
     */
    public function scopeForCarrier($query, int $carrierId)
    {
        return $query->where('carrier_id', $carrierId);
    }

    /**
     * Scope to filter logs with violations.
     */
    public function scopeWithViolations($query)
    {
        return $query->where('has_violations', true);
    }
}
