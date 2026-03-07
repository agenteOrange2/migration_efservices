<?php

namespace App\Models\Hos;

use App\Models\User;
use App\Models\Trip;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class HosEntry extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_ON_DUTY_NOT_DRIVING = 'on_duty_not_driving';
    public const STATUS_ON_DUTY_DRIVING = 'on_duty_driving';
    public const STATUS_OFF_DUTY = 'off_duty';

    public const STATUSES = [
        self::STATUS_ON_DUTY_NOT_DRIVING,
        self::STATUS_ON_DUTY_DRIVING,
        self::STATUS_OFF_DUTY,
    ];

    protected $fillable = [
        'user_driver_detail_id',
        'vehicle_id',
        'carrier_id',
        'trip_id',
        'status',
        'start_time',
        'end_time',
        'latitude',
        'longitude',
        'formatted_address',
        'location_available',
        'is_manual_entry',
        'manual_entry_reason',
        'is_ghost_log',
        'ghost_log_reason',
        'created_by',
        'date',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'location_available' => 'boolean',
        'is_manual_entry' => 'boolean',
        'is_ghost_log' => 'boolean',
        'date' => 'date',
    ];

    /**
     * Get the driver that owns this entry.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriverDetail::class, 'user_driver_detail_id');
    }

    /**
     * Get the vehicle associated with this entry.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the carrier associated with this entry.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Get the user who created this entry (for manual entries).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the audit logs for this entry.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(HosEntryAuditLog::class);
    }

    /**
     * Get the trip associated with this entry.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Check if this is a ghost log entry.
     */
    public function isGhostLog(): bool
    {
        return $this->is_ghost_log === true;
    }

    /**
     * Mark this entry as a ghost log.
     */
    public function markAsGhostLog(string $reason): void
    {
        $this->update([
            'is_ghost_log' => true,
            'ghost_log_reason' => $reason,
        ]);
    }

    /**
     * Get the duration in minutes.
     */
    public function getDurationMinutesAttribute(): int
    {
        if (!$this->end_time) {
            return (int) $this->start_time->diffInMinutes(now());
        }
        return (int) $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Get formatted duration string.
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = $this->duration_minutes;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}h {$mins}m";
    }

    /**
     * Get human-readable status name.
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ON_DUTY_NOT_DRIVING => 'On Duty - Not Driving',
            self::STATUS_ON_DUTY_DRIVING => 'On Duty - Driving',
            self::STATUS_OFF_DUTY => 'Off Duty',
            default => 'Unknown',
        };
    }

    /**
     * Get location display string.
     */
    public function getLocationDisplayAttribute(): string
    {
        if (!$this->location_available) {
            return 'Location unavailable';
        }
        
        if ($this->formatted_address) {
            return $this->formatted_address;
        }
        
        if ($this->latitude && $this->longitude) {
            return "{$this->latitude}, {$this->longitude}";
        }
        
        return 'No location data';
    }

    /**
     * Check if this entry is currently open (no end time).
     */
    public function isOpen(): bool
    {
        return is_null($this->end_time);
    }

    /**
     * Check if this is a driving status.
     */
    public function isDriving(): bool
    {
        return $this->status === self::STATUS_ON_DUTY_DRIVING;
    }

    /**
     * Check if this is an on-duty status (driving or not driving).
     */
    public function isOnDuty(): bool
    {
        return in_array($this->status, [
            self::STATUS_ON_DUTY_DRIVING,
            self::STATUS_ON_DUTY_NOT_DRIVING,
        ]);
    }

    /**
     * Scope to filter open entries.
     */
    public function scopeOpen($query)
    {
        return $query->whereNull('end_time');
    }

    /**
     * Scope to filter by date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope to filter by driver.
     */
    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('user_driver_detail_id', $driverId);
    }

    /**
     * Convert model to array for JSON serialization.
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['duration_minutes'] = $this->duration_minutes;
        $array['formatted_duration'] = $this->formatted_duration;
        $array['status_name'] = $this->status_name;
        $array['location_display'] = $this->location_display;
        return $array;
    }
}
