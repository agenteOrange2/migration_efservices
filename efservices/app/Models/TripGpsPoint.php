<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripGpsPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'formatted_address',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get the trip this GPS point belongs to.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get coordinates as array.
     */
    public function getCoordinatesAttribute(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Get location display string.
     */
    public function getLocationDisplayAttribute(): string
    {
        if ($this->formatted_address) {
            return $this->formatted_address;
        }
        return "{$this->latitude}, {$this->longitude}";
    }

    /**
     * Get speed in mph formatted.
     */
    public function getFormattedSpeedAttribute(): string
    {
        return $this->speed ? round($this->speed, 1) . ' mph' : 'N/A';
    }

    /**
     * Check if vehicle is stationary (speed = 0).
     */
    public function isStationary(): bool
    {
        return $this->speed !== null && $this->speed == 0;
    }

    /**
     * Scope to get points for a trip ordered by time.
     */
    public function scopeForTrip($query, int $tripId)
    {
        return $query->where('trip_id', $tripId)->orderBy('recorded_at');
    }

    /**
     * Scope to get stationary points.
     */
    public function scopeStationary($query)
    {
        return $query->where('speed', 0);
    }

    /**
     * Scope to get points within time range.
     */
    public function scopeBetween($query, $startTime, $endTime)
    {
        return $query->whereBetween('recorded_at', [$startTime, $endTime]);
    }
}
