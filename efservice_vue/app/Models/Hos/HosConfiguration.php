<?php

namespace App\Models\Hos;

use App\Models\Carrier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HosConfiguration extends Model
{
    use HasFactory;

    // Default values
    public const DEFAULT_MAX_DRIVING_HOURS = 12.00;
    public const DEFAULT_MAX_DUTY_HOURS = 14.00;
    public const DEFAULT_WARNING_THRESHOLD_MINUTES = 60;
    public const DEFAULT_VIOLATION_THRESHOLD_MINUTES = 0;

    protected $fillable = [
        'carrier_id',
        'max_driving_hours',
        'max_duty_hours',
        'warning_threshold_minutes',
        'violation_threshold_minutes',
        'is_active',
        // FMCSA Texas Intrastate fields
        'fmcsa_texas_mode',
        'allow_24_hour_reset',
        'require_30_min_break',
        'break_after_hours',
        'weekly_limit_60_minutes',
        'weekly_limit_70_minutes',
        'enable_ghost_log_detection',
        'ghost_log_threshold_minutes',
    ];

    protected $casts = [
        'max_driving_hours' => 'decimal:2',
        'max_duty_hours' => 'decimal:2',
        'warning_threshold_minutes' => 'integer',
        'violation_threshold_minutes' => 'integer',
        'is_active' => 'boolean',
        'fmcsa_texas_mode' => 'boolean',
        'allow_24_hour_reset' => 'boolean',
        'require_30_min_break' => 'boolean',
        'break_after_hours' => 'integer',
        'weekly_limit_60_minutes' => 'integer',
        'weekly_limit_70_minutes' => 'integer',
        'enable_ghost_log_detection' => 'boolean',
        'ghost_log_threshold_minutes' => 'integer',
    ];

    /**
     * Get the carrier that owns this configuration.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Get max driving hours in minutes.
     */
    public function getMaxDrivingMinutesAttribute(): int
    {
        return (int) ($this->max_driving_hours * 60);
    }

    /**
     * Get max duty hours in minutes.
     */
    public function getMaxDutyMinutesAttribute(): int
    {
        return (int) ($this->max_duty_hours * 60);
    }

    /**
     * Get default configuration values.
     */
    public static function getDefaults(): array
    {
        return [
            'max_driving_hours' => self::DEFAULT_MAX_DRIVING_HOURS,
            'max_duty_hours' => self::DEFAULT_MAX_DUTY_HOURS,
            'warning_threshold_minutes' => self::DEFAULT_WARNING_THRESHOLD_MINUTES,
            'violation_threshold_minutes' => self::DEFAULT_VIOLATION_THRESHOLD_MINUTES,
            'is_active' => true,
            // FMCSA defaults
            'fmcsa_texas_mode' => true,
            'allow_24_hour_reset' => true,
            'require_30_min_break' => true,
            'break_after_hours' => 8,
            'weekly_limit_60_minutes' => 3600, // 60 hours
            'weekly_limit_70_minutes' => 4200, // 70 hours
            'enable_ghost_log_detection' => true,
            'ghost_log_threshold_minutes' => 30,
        ];
    }

    /**
     * Check if FMCSA Texas mode is enabled.
     */
    public function isFmcsaTexasMode(): bool
    {
        return $this->fmcsa_texas_mode === true;
    }

    /**
     * Check if 24-hour reset is allowed.
     */
    public function allows24HourReset(): bool
    {
        return $this->allow_24_hour_reset === true;
    }

    /**
     * Check if 30-minute break is required.
     */
    public function requires30MinBreak(): bool
    {
        return $this->require_30_min_break === true;
    }

    /**
     * Check if ghost log detection is enabled.
     */
    public function isGhostLogDetectionEnabled(): bool
    {
        return $this->enable_ghost_log_detection === true;
    }

    /**
     * Get weekly limit based on cycle type.
     */
    public function getWeeklyLimitMinutes(string $cycleType): int
    {
        return $cycleType === '60_7' 
            ? ($this->weekly_limit_60_minutes ?? 3600)
            : ($this->weekly_limit_70_minutes ?? 4200);
    }

    /**
     * Get or create configuration for a carrier.
     */
    public static function getForCarrier(int $carrierId): self
    {
        return self::firstOrCreate(
            ['carrier_id' => $carrierId],
            self::getDefaults()
        );
    }

    /**
     * Validate that driving hours don't exceed duty hours.
     */
    public static function validateLimits(float $drivingHours, float $dutyHours): bool
    {
        return $drivingHours <= $dutyHours;
    }
}
