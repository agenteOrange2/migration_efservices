<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Archive Access Log Model
 * 
 * Tracks all access to driver archives for audit and compliance purposes.
 * Records both view and download actions with full context.
 */
class ArchiveAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_archive_id',
        'user_id',
        'carrier_id',
        'action_type',
        'ip_address',
        'user_agent',
        'metadata',
        'accessed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'accessed_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the driver archive that was accessed.
     */
    public function driverArchive(): BelongsTo
    {
        return $this->belongsTo(DriverArchive::class, 'driver_archive_id');
    }

    /**
     * Get the user who accessed the archive.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the carrier context for the access.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeByActionType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('accessed_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by carrier.
     */
    public function scopeForCarrier($query, int $carrierId)
    {
        return $query->where('carrier_id', $carrierId);
    }

    /**
     * Scope to get recent access logs.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('accessed_at', '>=', now()->subDays($days));
    }

    /**
     * Check if this is a download action.
     */
    public function isDownload(): bool
    {
        return $this->action_type === 'download';
    }

    /**
     * Check if this is a view action.
     */
    public function isView(): bool
    {
        return $this->action_type === 'view';
    }

    /**
     * Get formatted file size from metadata (for downloads).
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->isDownload() || !isset($this->metadata['file_size'])) {
            return null;
        }

        $bytes = $this->metadata['file_size'];
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
