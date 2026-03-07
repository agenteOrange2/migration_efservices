<?php

namespace App\Models\Hos;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HosWeeklyCycle extends Model
{
    use HasFactory;

    // Cycle type constants
    public const CYCLE_60_7 = '60_7';  // 60 hours in 7 days
    public const CYCLE_70_8 = '70_8';  // 70 hours in 8 days

    public const CYCLE_TYPES = [
        self::CYCLE_60_7,
        self::CYCLE_70_8,
    ];

    // Cycle limits in minutes
    public const LIMIT_60_HOURS = 3600;  // 60 * 60
    public const LIMIT_70_HOURS = 4200;  // 70 * 60

    protected $fillable = [
        'user_driver_detail_id',
        'carrier_id',
        'week_start_date',
        'week_end_date',
        'year',
        'week_number',
        'total_driving_minutes',
        'total_on_duty_minutes',
        'total_duty_minutes',
        'cycle_type',
        'cycle_limit_minutes',
        'has_24_hour_reset',
        'reset_started_at',
        'reset_completed_at',
        'is_over_limit',
        'is_current_week',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'reset_started_at' => 'datetime',
        'reset_completed_at' => 'datetime',
        'has_24_hour_reset' => 'boolean',
        'is_over_limit' => 'boolean',
        'is_current_week' => 'boolean',
    ];

    /**
     * Get the driver associated with this cycle.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriverDetail::class, 'user_driver_detail_id');
    }

    /**
     * Get the carrier associated with this cycle.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Get remaining minutes in the cycle.
     */
    public function getRemainingMinutesAttribute(): int
    {
        $remaining = $this->cycle_limit_minutes - $this->total_duty_minutes;
        return max(0, $remaining);
    }

    /**
     * Get remaining hours in the cycle.
     */
    public function getRemainingHoursAttribute(): float
    {
        return round($this->remaining_minutes / 60, 2);
    }

    /**
     * Get used hours in the cycle.
     */
    public function getUsedHoursAttribute(): float
    {
        return round($this->total_duty_minutes / 60, 2);
    }

    /**
     * Get percentage of cycle used.
     */
    public function getPercentageUsedAttribute(): float
    {
        if ($this->cycle_limit_minutes == 0) {
            return 0;
        }
        return round(($this->total_duty_minutes / $this->cycle_limit_minutes) * 100, 1);
    }

    /**
     * Get cycle type display name.
     */
    public function getCycleTypeNameAttribute(): string
    {
        return match ($this->cycle_type) {
            self::CYCLE_60_7 => '60 hours / 7 days',
            self::CYCLE_70_8 => '70 hours / 8 days',
            default => 'Unknown',
        };
    }

    /**
     * Get limit hours based on cycle type.
     */
    public function getLimitHoursAttribute(): int
    {
        return match ($this->cycle_type) {
            self::CYCLE_60_7 => 60,
            self::CYCLE_70_8 => 70,
            default => 60,
        };
    }

    /**
     * Get days in cycle based on type.
     */
    public function getDaysInCycleAttribute(): int
    {
        return match ($this->cycle_type) {
            self::CYCLE_60_7 => 7,
            self::CYCLE_70_8 => 8,
            default => 7,
        };
    }

    /**
     * Check if cycle is approaching limit (90% or more).
     */
    public function isApproachingLimit(): bool
    {
        return $this->percentage_used >= 90;
    }

    /**
     * Check if cycle is at warning level (75-90%).
     */
    public function isAtWarningLevel(): bool
    {
        return $this->percentage_used >= 75 && $this->percentage_used < 90;
    }

    /**
     * Get status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_over_limit || $this->percentage_used >= 100) {
            return 'red';
        }
        if ($this->percentage_used >= 90) {
            return 'red';
        }
        if ($this->percentage_used >= 75) {
            return 'yellow';
        }
        return 'green';
    }

    /**
     * Reset the cycle (after 24h or 34h rest).
     */
    public function resetCycle(): void
    {
        $this->update([
            'total_driving_minutes' => 0,
            'total_on_duty_minutes' => 0,
            'total_duty_minutes' => 0,
            'is_over_limit' => false,
            'has_24_hour_reset' => true,
            'reset_completed_at' => now(),
        ]);
    }

    /**
     * Add duty minutes to the cycle.
     */
    public function addDutyMinutes(int $drivingMinutes, int $onDutyMinutes): void
    {
        $this->increment('total_driving_minutes', $drivingMinutes);
        $this->increment('total_on_duty_minutes', $onDutyMinutes);
        $this->increment('total_duty_minutes', $drivingMinutes + $onDutyMinutes);
        
        // Check if over limit
        if ($this->total_duty_minutes >= $this->cycle_limit_minutes) {
            $this->update(['is_over_limit' => true]);
        }
    }

    /**
     * Scope to get current week cycle.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current_week', true);
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
     * Get or create current week cycle for a driver.
     */
    public static function getCurrentForDriver(int $driverId, int $carrierId, string $cycleType = self::CYCLE_60_7): self
    {
        $now = now();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();

        // Mark previous weeks as not current
        self::where('user_driver_detail_id', $driverId)
            ->where('is_current_week', true)
            ->where('week_start_date', '<', $weekStart)
            ->update(['is_current_week' => false]);

        $cycleLimit = $cycleType === self::CYCLE_60_7 ? self::LIMIT_60_HOURS : self::LIMIT_70_HOURS;

        return self::firstOrCreate(
            [
                'user_driver_detail_id' => $driverId,
                'week_start_date' => $weekStart,
            ],
            [
                'carrier_id' => $carrierId,
                'week_end_date' => $weekEnd,
                'year' => $now->year,
                'week_number' => $now->weekOfYear,
                'total_driving_minutes' => 0,
                'total_on_duty_minutes' => 0,
                'total_duty_minutes' => 0,
                'cycle_type' => $cycleType,
                'cycle_limit_minutes' => $cycleLimit,
                'has_24_hour_reset' => false,
                'is_over_limit' => false,
                'is_current_week' => true,
            ]
        );
    }
}
