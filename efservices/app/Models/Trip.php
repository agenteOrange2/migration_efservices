<?php

namespace App\Models;

use App\Models\Hos\HosEntry;
use App\Models\Hos\HosViolation;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Trip extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_PAUSED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'carrier_id',
        'user_driver_detail_id',
        'vehicle_id',
        'created_by',
        'updated_by',
        'trip_number',
        'reference_number',
        // Legacy fields (original table)
        'destination',
        'start_time',
        'end_time',
        'estimated_duration',
        'total_duration',
        // New FMCSA fields
        'scheduled_start_date',
        'scheduled_end_date',
        'estimated_duration_minutes',
        'actual_start_time',
        'actual_end_time',
        'actual_duration_minutes',
        'origin_address',
        'origin_latitude',
        'origin_longitude',
        'destination_address',
        'destination_latitude',
        'destination_longitude',
        'status',
        'accepted_at',
        'started_at',
        'completed_at',
        'rejection_reason',
        'description',
        'notes',
        'driver_notes',
        'load_type',
        'load_weight',
        'load_unit',
        'pre_trip_inspection_completed',
        'pre_trip_inspection_at',
        'post_trip_inspection_completed',
        'post_trip_inspection_at',
        'gps_tracking_enabled',
        'gps_ping_interval_seconds',
        'has_violations',
        'forgot_to_close',
        'penalty_notes',
        'auto_stopped_at',
        'auto_stop_reason',
        'hos_penalty_end_time',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        // Inspection checklist fields
        'pre_trip_inspection_data',
        'post_trip_inspection_data',
        'has_trailer',
        'pre_trip_remarks',
        'post_trip_remarks',
        'pre_trip_defects_found',
        'post_trip_defects_found',
        'vehicle_condition_satisfactory',
        // Defects correction fields
        'pre_trip_defects_corrected',
        'pre_trip_defects_corrected_notes',
        'pre_trip_defects_not_need_correction',
        'pre_trip_defects_not_need_correction_notes',
        'post_trip_defects_corrected',
        'post_trip_defects_corrected_notes',
        'post_trip_defects_not_need_correction',
        'post_trip_defects_not_need_correction_notes',
        'pre_trip_driver_signature',
        'post_trip_driver_signature',
        // Quick Trip fields
        'is_quick_trip',
        'requires_completion',
        'completed_info_at',
        'completed_info_by',
    ];

    protected $casts = [
        // Legacy fields
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        // New FMCSA fields
        'scheduled_start_date' => 'datetime',
        'scheduled_end_date' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'origin_latitude' => 'decimal:6',
        'origin_longitude' => 'decimal:6',
        'destination_latitude' => 'decimal:6',
        'destination_longitude' => 'decimal:6',
        'load_weight' => 'decimal:2',
        'pre_trip_inspection_completed' => 'boolean',
        'pre_trip_inspection_at' => 'datetime',
        'post_trip_inspection_completed' => 'boolean',
        'post_trip_inspection_at' => 'datetime',
        'gps_tracking_enabled' => 'boolean',
        'has_violations' => 'boolean',
        'forgot_to_close' => 'boolean',
        'cancelled_at' => 'datetime',
        'auto_stopped_at' => 'datetime',
        'hos_penalty_end_time' => 'datetime',
        // Inspection checklist casts
        'pre_trip_inspection_data' => 'array',
        'post_trip_inspection_data' => 'array',
        'has_trailer' => 'boolean',
        'pre_trip_defects_found' => 'boolean',
        'post_trip_defects_found' => 'boolean',
        'vehicle_condition_satisfactory' => 'boolean',
        'pre_trip_defects_corrected' => 'boolean',
        'pre_trip_defects_not_need_correction' => 'boolean',
        'post_trip_defects_corrected' => 'boolean',
        'post_trip_defects_not_need_correction' => 'boolean',
        // Quick Trip casts
        'is_quick_trip' => 'boolean',
        'requires_completion' => 'boolean',
        'completed_info_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($trip) {
            if (empty($trip->trip_number)) {
                $trip->trip_number = self::generateTripNumber();
            }
        });
    }

    /**
     * Generate a unique trip number.
     * Note: For better concurrency, use TripService::createTrip() which generates
     * the trip_number inside a transaction with proper locking.
     */
    public static function generateTripNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "TRP-{$date}-";
        
        // Get the max sequence for today (including soft deleted)
        $lastTrip = self::withTrashed()
            ->where('trip_number', 'LIKE', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING(trip_number, -4) AS UNSIGNED) DESC")
            ->first();
        
        if ($lastTrip && preg_match('/TRP-\d{8}-(\d{4})$/', $lastTrip->trip_number, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        } else {
            $sequence = 1;
        }
        
        $tripNumber = sprintf('TRP-%s-%04d', $date, $sequence);
        
        // Ensure uniqueness - include soft deleted
        $attempts = 0;
        while (self::withTrashed()->where('trip_number', $tripNumber)->exists() && $attempts < 100) {
            $sequence++;
            $tripNumber = sprintf('TRP-%s-%04d', $date, $sequence);
            $attempts++;
        }
        
        return $tripNumber;
    }

    // Relationships

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriverDetail::class, 'user_driver_detail_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function gpsPoints(): HasMany
    {
        return $this->hasMany(TripGpsPoint::class)->orderBy('recorded_at');
    }

    public function hosEntries(): HasMany
    {
        return $this->hasMany(HosEntry::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(HosViolation::class);
    }

    /**
     * Get all pauses for this trip.
     */
    public function pauses(): HasMany
    {
        return $this->hasMany(TripPause::class)->orderBy('started_at');
    }

    /**
     * Get the currently active pause (if any).
     */
    public function activePause(): HasOne
    {
        return $this->hasOne(TripPause::class)
            ->whereNull('ended_at')
            ->latest('started_at');
    }

    // Accessors

    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACCEPTED => 'info',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_PAUSED => 'amber',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    public function getFormattedDurationAttribute(): string
    {
        $minutes = $this->actual_duration_minutes ?? $this->estimated_duration_minutes ?? 0;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}h {$mins}m";
    }

    public function getOriginCoordinatesAttribute(): ?array
    {
        if ($this->origin_latitude && $this->origin_longitude) {
            return [
                'lat' => (float) $this->origin_latitude,
                'lng' => (float) $this->origin_longitude,
            ];
        }
        return null;
    }

    public function getDestinationCoordinatesAttribute(): ?array
    {
        if ($this->destination_latitude && $this->destination_longitude) {
            return [
                'lat' => (float) $this->destination_latitude,
                'lng' => (float) $this->destination_longitude,
            ];
        }
        return null;
    }

    /**
     * Get total pause duration in minutes for this trip.
     */
    public function getTotalPauseDurationAttribute(): int
    {
        return $this->pauses()
            ->whereNotNull('ended_at')
            ->get()
            ->sum('duration_minutes');
    }

    // Status checks

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function isAutoStopped(): bool
    {
        return $this->auto_stopped_at !== null;
    }

    /**
     * Check if the driver can resume this trip.
     * Cannot resume if HOS penalty is still active.
     */
    public function canBeResumed(): bool
    {
        if ($this->status !== self::STATUS_PAUSED) {
            return false;
        }

        // If there's a penalty end time, check if it has passed
        if ($this->hos_penalty_end_time) {
            return now()->gte($this->hos_penalty_end_time);
        }

        return true;
    }

    /**
     * Get remaining penalty time in minutes.
     */
    public function getRemainingPenaltyMinutesAttribute(): int
    {
        if (!$this->hos_penalty_end_time) {
            return 0;
        }

        $remaining = (int) now()->diffInMinutes($this->hos_penalty_end_time, false);
        return max(0, $remaining);
    }

    /**
     * Get formatted remaining penalty time.
     */
    public function getFormattedRemainingPenaltyAttribute(): string
    {
        $minutes = $this->remaining_penalty_minutes;
        if ($minutes <= 0) {
            return 'Completed';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}h {$mins}m remaining";
    }

    public function canBeStarted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function canBeEnded(): bool
    {
        return in_array($this->status, [self::STATUS_IN_PROGRESS, self::STATUS_PAUSED]);
    }

    public function scopePaused($query)
    {
        return $query->where('status', self::STATUS_PAUSED);
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('user_driver_detail_id', $driverId);
    }

    public function scopeForCarrier($query, int $carrierId)
    {
        return $query->where('carrier_id', $carrierId);
    }

    public function scopeScheduledBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_start_date', [$startDate, $endDate]);
    }

    // Document Types for Trip
    public const DOC_TYPE_BOL = 'bol';
    public const DOC_TYPE_POD = 'pod';
    public const DOC_TYPE_FUEL_RECEIPT = 'fuel_receipt';
    public const DOC_TYPE_TOLL_RECEIPT = 'toll_receipt';
    public const DOC_TYPE_LOAD_PHOTOS = 'load_photos';
    public const DOC_TYPE_DELIVERY_PHOTOS = 'delivery_photos';
    public const DOC_TYPE_SCALE_TICKET = 'scale_ticket';
    public const DOC_TYPE_LUMPER_RECEIPT = 'lumper_receipt';
    public const DOC_TYPE_OTHER = 'other';

    public const DOCUMENT_TYPES = [
        self::DOC_TYPE_BOL => 'Bill of Lading (BOL)',
        self::DOC_TYPE_POD => 'Proof of Delivery (POD)',
        self::DOC_TYPE_FUEL_RECEIPT => 'Fuel Receipt',
        self::DOC_TYPE_TOLL_RECEIPT => 'Toll Receipt',
        self::DOC_TYPE_LOAD_PHOTOS => 'Load Photos',
        self::DOC_TYPE_DELIVERY_PHOTOS => 'Delivery Photos',
        self::DOC_TYPE_SCALE_TICKET => 'Scale Ticket',
        self::DOC_TYPE_LUMPER_RECEIPT => 'Lumper Receipt',
        self::DOC_TYPE_OTHER => 'Other Document',
    ];

    /**
     * Register media collections for trip documents.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('trip_documents')
            ->useDisk('public')
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
            ]);
    }

    /**
     * Register media conversions for trip documents.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(800)
            ->sharpen(10)
            ->nonQueued();
    }

    /**
     * Get all trip documents.
     */
    public function getTripDocuments()
    {
        return $this->getMedia('trip_documents')->sortByDesc('created_at');
    }

    /**
     * Get trip documents by type.
     */
    public function getTripDocumentsByType(string $type)
    {
        return $this->getMedia('trip_documents')
            ->filter(fn($media) => $media->getCustomProperty('document_type') === $type)
            ->sortByDesc('created_at');
    }

    /**
     * Get document type name.
     */
    public static function getDocumentTypeName(string $type): string
    {
        return self::DOCUMENT_TYPES[$type] ?? 'Unknown';
    }

    /**
     * Check if driver can upload documents to this trip.
     * Drivers can upload during in_progress, paused, and completed status.
     */
    public function canUploadDocuments(): bool
    {
        return in_array($this->status, [
            self::STATUS_IN_PROGRESS,
            self::STATUS_PAUSED,
            self::STATUS_COMPLETED,
        ]);
    }

    /**
     * Check if driver can delete documents from this trip.
     * Only allowed if trip is not completed or within 24 hours of completion.
     */
    public function canDeleteDocuments(): bool
    {
        if (!$this->isCompleted()) {
            return true;
        }

        // Allow deletion within 24 hours of completion
        if ($this->completed_at && $this->completed_at->diffInHours(now()) <= 24) {
            return true;
        }

        return false;
    }

    // ========================================
    // Inspection Checklist Methods
    // ========================================

    /**
     * Get pre-trip inspection tractor items with their status.
     */
    public function getPreTripTractorItems(): array
    {
        return $this->pre_trip_inspection_data['tractor'] ?? [];
    }

    /**
     * Get pre-trip inspection trailer items.
     */
    public function getPreTripTrailerItems(): array
    {
        return $this->has_trailer ? ($this->pre_trip_inspection_data['trailer'] ?? []) : [];
    }

    /**
     * Get post-trip inspection tractor items with their status.
     */
    public function getPostTripTractorItems(): array
    {
        return $this->post_trip_inspection_data['tractor'] ?? [];
    }

    /**
     * Get post-trip inspection trailer items.
     */
    public function getPostTripTrailerItems(): array
    {
        return $this->has_trailer ? ($this->post_trip_inspection_data['trailer'] ?? []) : [];
    }

    /**
     * Get all inspection items configuration.
     */
    public static function getInspectionConfig(): array
    {
        return config('inspection', []);
    }

    /**
     * Get tractor inspection items from config.
     */
    public static function getTractorInspectionItems(): array
    {
        return config('inspection.tractor_items', []);
    }

    /**
     * Get trailer inspection items from config.
     */
    public static function getTrailerInspectionItems(): array
    {
        return config('inspection.trailer_items', []);
    }

    /**
     * Get tractor items organized by columns.
     */
    public static function getTractorInspectionColumns(): array
    {
        return config('inspection.tractor_columns', []);
    }

    /**
     * Get trailer items organized by columns.
     */
    public static function getTrailerInspectionColumns(): array
    {
        return config('inspection.trailer_columns', []);
    }

    // ========================================
    // Quick Trip Methods
    // ========================================

    /**
     * Check if this is a quick trip.
     */
    public function isQuickTrip(): bool
    {
        return (bool) $this->is_quick_trip;
    }

    /**
     * Check if this trip needs completion (missing required info).
     */
    public function needsCompletion(): bool
    {
        return (bool) $this->requires_completion;
    }

    /**
     * Get missing fields for quick trip completion.
     */
    public function getMissingFields(): array
    {
        $missing = [];

        if (empty($this->origin_address)) {
            $missing['origin_address'] = 'Origin Address';
        }

        if (empty($this->destination_address)) {
            $missing['destination_address'] = 'Destination Address';
        }

        return $missing;
    }

    /**
     * Check if the quick trip has all required info.
     */
    public function hasCompleteInfo(): bool
    {
        return !empty($this->origin_address) && !empty($this->destination_address);
    }

    /**
     * Mark the quick trip as having complete info.
     */
    public function markInfoAsComplete(int $userId): void
    {
        $this->update([
            'requires_completion' => false,
            'completed_info_at' => now(),
            'completed_info_by' => $userId,
        ]);
    }

    /**
     * Get the user who completed the trip info.
     */
    public function completedInfoBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_info_by');
    }

    /**
     * Scope for quick trips that need completion.
     */
    public function scopeNeedsCompletion($query)
    {
        return $query->where('is_quick_trip', true)->where('requires_completion', true);
    }

    /**
     * Scope for quick trips.
     */
    public function scopeQuickTrips($query)
    {
        return $query->where('is_quick_trip', true);
    }

    /**
     * Get quick trip status label.
     */
    public function getQuickTripStatusAttribute(): ?string
    {
        if (!$this->is_quick_trip) {
            return null;
        }

        return $this->requires_completion ? 'Needs Info' : 'Complete';
    }

    /**
     * Get quick trip status color.
     */
    public function getQuickTripStatusColorAttribute(): ?string
    {
        if (!$this->is_quick_trip) {
            return null;
        }

        return $this->requires_completion ? 'warning' : 'success';
    }
}
