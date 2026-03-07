<?php

namespace App\Models\Hos;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Trip;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HosViolation extends Model
{
    use HasFactory;

    // Violation type constants
    public const TYPE_DRIVING_LIMIT_EXCEEDED = 'driving_limit_exceeded';
    public const TYPE_DUTY_LIMIT_EXCEEDED = 'duty_limit_exceeded';
    public const TYPE_DUTY_PERIOD_EXCEEDED = 'duty_period_exceeded';
    public const TYPE_WEEKLY_CYCLE_EXCEEDED = 'weekly_cycle_exceeded';
    public const TYPE_MISSING_REQUIRED_BREAK = 'missing_required_break';
    public const TYPE_FORGOT_TO_CLOSE_TRIP = 'forgot_to_close_trip';

    public const VIOLATION_TYPES = [
        self::TYPE_DRIVING_LIMIT_EXCEEDED,
        self::TYPE_DUTY_LIMIT_EXCEEDED,
        self::TYPE_DUTY_PERIOD_EXCEEDED,
        self::TYPE_WEEKLY_CYCLE_EXCEEDED,
        self::TYPE_MISSING_REQUIRED_BREAK,
        self::TYPE_FORGOT_TO_CLOSE_TRIP,
    ];

    // Severity constants
    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITIES = [
        self::SEVERITY_MINOR,
        self::SEVERITY_MODERATE,
        self::SEVERITY_CRITICAL,
    ];

    // Penalty type constants
    public const PENALTY_WARNING = 'warning';
    public const PENALTY_SUSPENSION = 'suspension';
    public const PENALTY_MANDATORY_REST = 'mandatory_rest';
    public const PENALTY_NONE = 'none';

    public const PENALTY_TYPES = [
        self::PENALTY_WARNING,
        self::PENALTY_SUSPENSION,
        self::PENALTY_MANDATORY_REST,
        self::PENALTY_NONE,
    ];

    protected $fillable = [
        'user_driver_detail_id',
        'carrier_id',
        'vehicle_id',
        'violation_type',
        'violation_severity',
        'fmcsa_rule_reference',
        'violation_date',
        'hours_exceeded',
        'hos_entry_id',
        'trip_id',
        'acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'has_penalty',
        'penalty_type',
        'penalty_hours',
        'penalty_start',
        'penalty_end',
        'penalty_notes',
        // Forgiveness fields
        'is_forgiven',
        'forgiven_by',
        'forgiven_at',
        'forgiveness_reason',
        'original_trip_end_time',
        'adjusted_trip_end_time',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'hours_exceeded' => 'decimal:2',
        'acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'has_penalty' => 'boolean',
        'penalty_start' => 'datetime',
        'penalty_end' => 'datetime',
        // Forgiveness casts
        'is_forgiven' => 'boolean',
        'forgiven_at' => 'datetime',
        'original_trip_end_time' => 'datetime',
        'adjusted_trip_end_time' => 'datetime',
    ];

    /**
     * Get the driver associated with this violation.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriverDetail::class, 'user_driver_detail_id');
    }

    /**
     * Get the carrier associated with this violation.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Get the vehicle associated with this violation.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the HOS entry that caused this violation.
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(HosEntry::class, 'hos_entry_id');
    }

    /**
     * Get the user who acknowledged this violation.
     */
    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the user who forgave this violation.
     */
    public function forgivenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forgiven_by');
    }

    /**
     * Get the trip associated with this violation.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get human-readable violation type name.
     */
    public function getViolationTypeNameAttribute(): string
    {
        return match ($this->violation_type) {
            self::TYPE_DRIVING_LIMIT_EXCEEDED => 'Driving Limit Exceeded (12h)',
            self::TYPE_DUTY_LIMIT_EXCEEDED => 'Duty Limit Exceeded',
            self::TYPE_DUTY_PERIOD_EXCEEDED => 'Duty Period Exceeded (14h)',
            self::TYPE_WEEKLY_CYCLE_EXCEEDED => 'Weekly Cycle Exceeded (60/70h)',
            self::TYPE_MISSING_REQUIRED_BREAK => 'Missing Required Break (30min)',
            self::TYPE_FORGOT_TO_CLOSE_TRIP => 'Forgot to Close Trip (Ghost Log)',
            default => 'Unknown Violation',
        };
    }

    /**
     * Get severity display name.
     */
    public function getSeverityNameAttribute(): string
    {
        return match ($this->violation_severity) {
            self::SEVERITY_MINOR => 'Minor',
            self::SEVERITY_MODERATE => 'Moderate',
            self::SEVERITY_CRITICAL => 'Critical',
            default => 'Unknown',
        };
    }

    /**
     * Get severity color for UI.
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->violation_severity) {
            self::SEVERITY_MINOR => 'yellow',
            self::SEVERITY_MODERATE => 'orange',
            self::SEVERITY_CRITICAL => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if this violation has a blocking penalty.
     */
    public function isBlocking(): bool
    {
        // Forgiven violations are never blocking
        if ($this->is_forgiven) {
            return false;
        }

        if (!$this->has_penalty) {
            return false;
        }

        // Check if penalty is still active
        if ($this->penalty_end && $this->penalty_end->isPast()) {
            return false;
        }

        return in_array($this->penalty_type, [
            self::PENALTY_SUSPENSION,
            self::PENALTY_MANDATORY_REST,
        ]);
    }

    /**
     * Check if penalty is expired.
     */
    public function isPenaltyExpired(): bool
    {
        if (!$this->has_penalty || !$this->penalty_end) {
            return true;
        }
        return $this->penalty_end->isPast();
    }

    /**
     * Get remaining penalty hours.
     */
    public function getRemainingPenaltyHoursAttribute(): ?float
    {
        if (!$this->has_penalty || !$this->penalty_end) {
            return null;
        }
        
        if ($this->penalty_end->isPast()) {
            return 0;
        }

        return round(now()->diffInMinutes($this->penalty_end) / 60, 2);
    }

    /**
     * Get formatted hours exceeded.
     */
    public function getFormattedHoursExceededAttribute(): string
    {
        $hours = floor($this->hours_exceeded);
        $minutes = round(($this->hours_exceeded - $hours) * 60);
        return "{$hours}h {$minutes}m";
    }

    /**
     * Acknowledge this violation.
     */
    public function acknowledge(int $userId): void
    {
        $this->update([
            'acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Scope to filter unacknowledged violations.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->where('acknowledged', false);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('violation_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by carrier.
     */
    public function scopeForCarrier($query, int $carrierId)
    {
        return $query->where('carrier_id', $carrierId);
    }

    /**
     * Scope to filter by driver.
     */
    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('user_driver_detail_id', $driverId);
    }

    /**
     * Scope to filter forgiven violations.
     */
    public function scopeForgiven($query)
    {
        return $query->where('is_forgiven', true);
    }

    /**
     * Scope to filter not forgiven violations.
     */
    public function scopeNotForgiven($query)
    {
        return $query->where('is_forgiven', false);
    }

    /**
     * Scope to filter blocking violations (active penalty, not forgiven).
     */
    public function scopeBlocking($query)
    {
        return $query->where('is_forgiven', false)
            ->where('has_penalty', true)
            ->where(function ($q) {
                $q->whereNull('penalty_end')
                  ->orWhere('penalty_end', '>', now());
            })
            ->whereIn('penalty_type', [
                self::PENALTY_SUSPENSION,
                self::PENALTY_MANDATORY_REST,
            ]);
    }

    /**
     * Check if violation is forgiven.
     */
    public function isForgiven(): bool
    {
        return $this->is_forgiven === true;
    }

    /**
     * Check if violation can be forgiven.
     */
    public function canBeForgiven(): bool
    {
        // Already forgiven violations cannot be forgiven again
        if ($this->is_forgiven) {
            return false;
        }

        // Must have a penalty to forgive
        return $this->has_penalty;
    }

    /**
     * Forgive this violation.
     */
    public function forgive(int $userId, string $reason, ?\Carbon\Carbon $adjustedEndTime = null): void
    {
        $updateData = [
            'is_forgiven' => true,
            'forgiven_by' => $userId,
            'forgiven_at' => now(),
            'forgiveness_reason' => $reason,
            'has_penalty' => false,
            'penalty_end' => null,
        ];

        // If adjusting trip end time
        if ($adjustedEndTime && $this->trip) {
            $updateData['original_trip_end_time'] = $this->trip->actual_end_time ?? $this->trip->auto_stopped_at;
            $updateData['adjusted_trip_end_time'] = $adjustedEndTime;
        }

        $this->update($updateData);
    }

    /**
     * Get the forgiveness status display.
     */
    public function getForgivenessStatusAttribute(): string
    {
        if (!$this->is_forgiven) {
            return 'Not Forgiven';
        }

        return 'Forgiven on ' . $this->forgiven_at?->format('M d, Y h:i A');
    }
}
