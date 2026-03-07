<?php

namespace App\Models;

use App\Models\Hos\HosEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripPause extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'started_at',
        'ended_at',
        'latitude',
        'longitude',
        'formatted_address',
        'reason',
        'forced_by',
        'hos_entry_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    /**
     * Get the trip that owns this pause.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who forced this pause (if any).
     */
    public function forcedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forced_by');
    }

    /**
     * Get the HOS entry associated with this pause.
     */
    public function hosEntry(): BelongsTo
    {
        return $this->belongsTo(HosEntry::class);
    }

    /**
     * Get the duration of the pause in minutes.
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->ended_at) {
            return $this->started_at->diffInMinutes(now());
        }
        return $this->started_at->diffInMinutes($this->ended_at);
    }

    /**
     * Get the formatted duration string.
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = $this->duration_minutes ?? 0;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
    }

    /**
     * Get coordinates as array.
     */
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->latitude && $this->longitude) {
            return ['lat' => (float)$this->latitude, 'lng' => (float)$this->longitude];
        }
        return null;
    }

    /**
     * Check if this pause is currently active (not ended).
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Check if this pause was forced by admin/carrier.
     */
    public function wasForced(): bool
    {
        return $this->forced_by !== null;
    }
}
