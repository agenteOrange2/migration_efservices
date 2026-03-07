<?php

namespace App\Models;

use App\Exceptions\ImmutableRecordException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * DriverArchive Model
 * 
 * Stores historical driver records when a driver migrates to a new carrier.
 * Contains complete snapshots of all driver data for audit and legal compliance.
 * 
 * @property int $id
 * @property int $original_user_driver_detail_id
 * @property int $user_id
 * @property int $carrier_id
 * @property int|null $migration_record_id
 * @property \Carbon\Carbon $archived_at
 * @property string $archive_reason
 * @property array $driver_data_snapshot
 * @property array|null $licenses_snapshot
 * @property array|null $medical_snapshot
 * @property array|null $certifications_snapshot
 * @property array|null $employment_history_snapshot
 * @property array|null $training_snapshot
 * @property array|null $testing_snapshot
 * @property array|null $accidents_snapshot
 * @property array|null $convictions_snapshot
 * @property array|null $inspections_snapshot
 * @property array|null $hos_snapshot
 * @property array|null $vehicle_assignments_snapshot
 * @property string $status
 */
class DriverArchive extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'original_user_driver_detail_id',
        'user_id',
        'carrier_id',
        'migration_record_id',
        'archived_at',
        'archive_reason',
        'driver_data_snapshot',
        'licenses_snapshot',
        'medical_snapshot',
        'certifications_snapshot',
        'employment_history_snapshot',
        'training_snapshot',
        'testing_snapshot',
        'accidents_snapshot',
        'convictions_snapshot',
        'inspections_snapshot',
        'hos_snapshot',
        'vehicle_assignments_snapshot',
        'status',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'original_user_driver_detail_id' => 'integer',
        'user_id' => 'integer',
        'carrier_id' => 'integer',
        'migration_record_id' => 'integer',
        'driver_data_snapshot' => 'array',
        'licenses_snapshot' => 'array',
        'medical_snapshot' => 'array',
        'certifications_snapshot' => 'array',
        'employment_history_snapshot' => 'array',
        'training_snapshot' => 'array',
        'testing_snapshot' => 'array',
        'accidents_snapshot' => 'array',
        'convictions_snapshot' => 'array',
        'inspections_snapshot' => 'array',
        'hos_snapshot' => 'array',
        'vehicle_assignments_snapshot' => 'array',
    ];

    // Status constants
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_RESTORED = 'restored';

    // Archive reason constants
    public const REASON_MIGRATION = 'migration';
    public const REASON_TERMINATION = 'termination';
    public const REASON_MANUAL = 'manual';

    /**
     * Snapshot fields that are immutable once archived.
     */
    protected static array $immutableSnapshotFields = [
        'driver_data_snapshot',
        'licenses_snapshot',
        'medical_snapshot',
        'certifications_snapshot',
        'employment_history_snapshot',
        'training_snapshot',
        'testing_snapshot',
        'accidents_snapshot',
        'convictions_snapshot',
        'inspections_snapshot',
        'hos_snapshot',
        'vehicle_assignments_snapshot',
    ];

    /**
     * Boot the model.
     * Implements immutability protection for snapshot fields when status is 'archived'.
     */
    protected static function booted(): void
    {
        static::updating(function (DriverArchive $archive) {
            // Only enforce immutability for archived records
            if ($archive->getOriginal('status') === self::STATUS_ARCHIVED) {
                $changes = array_keys($archive->getDirty());
                
                foreach ($changes as $field) {
                    if (in_array($field, self::$immutableSnapshotFields)) {
                        throw new ImmutableRecordException(
                            "Archived driver records are immutable. Cannot modify field: {$field}"
                        );
                    }
                }
            }
        });
    }

    /**
     * Get the carrier this archive belongs to.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Get the user (driver) this archive is for.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the migration record associated with this archive.
     */
    public function migrationRecord(): BelongsTo
    {
        return $this->belongsTo(MigrationRecord::class);
    }

    /**
     * Get the original driver detail record (may not exist if driver was deleted).
     */
    public function originalDriverDetail(): BelongsTo
    {
        return $this->belongsTo(UserDriverDetail::class, 'original_user_driver_detail_id');
    }

    /**
     * Register media collections for archived documents.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('archived_documents')
            ->useDisk('public');

        $this->addMediaCollection('archived_profile_photo')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('archived_licenses')
            ->useDisk('public');
    }

    /**
     * Get the full name from the driver data snapshot.
     */
    public function getFullNameAttribute(): string
    {
        $data = $this->driver_data_snapshot ?? [];
        $firstName = $data['name'] ?? '';
        $middleName = $data['middle_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        
        return trim("{$firstName} {$middleName} {$lastName}");
    }

    /**
     * Get the email from the driver data snapshot.
     */
    public function getEmailAttribute(): ?string
    {
        return $this->driver_data_snapshot['email'] ?? null;
    }

    /**
     * Get the phone from the driver data snapshot.
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->driver_data_snapshot['phone'] ?? null;
    }

    /**
     * Check if this archive is from a migration.
     */
    public function isFromMigration(): bool
    {
        return $this->archive_reason === self::REASON_MIGRATION 
            && $this->migration_record_id !== null;
    }

    /**
     * Check if this archive has been restored (rollback).
     */
    public function isRestored(): bool
    {
        return $this->status === self::STATUS_RESTORED;
    }

    /**
     * Scope to get only archived (not restored) records.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope to get only restored records.
     */
    public function scopeRestored($query)
    {
        return $query->where('status', self::STATUS_RESTORED);
    }

    /**
     * Scope to get archives for a specific carrier.
     */
    public function scopeForCarrier($query, int $carrierId)
    {
        return $query->where('carrier_id', $carrierId);
    }

    /**
     * Scope to get archives by reason.
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('archive_reason', $reason);
    }

    /**
     * Scope to search by driver name.
     */
    public function scopeSearchByName($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereRaw("JSON_EXTRACT(driver_data_snapshot, '$.name') LIKE ?", ["%{$search}%"])
              ->orWhereRaw("JSON_EXTRACT(driver_data_snapshot, '$.last_name') LIKE ?", ["%{$search}%"])
              ->orWhereRaw("JSON_EXTRACT(driver_data_snapshot, '$.middle_name') LIKE ?", ["%{$search}%"]);
        });
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('archived_at', [$startDate, $endDate]);
    }

    /**
     * Get all archived documents from Spatie Media Library.
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getDocumentsUpToArchiveDate()
    {
        $documents = collect();

        // Get archived documents from Spatie Media Library
        $archivedDocuments = $this->getMedia('archived_documents');
        foreach ($archivedDocuments as $media) {
            $documents->push([
                'category' => $media->getCustomProperty('category', 'General'),
                'name' => $media->file_name,
                'path' => $media->getPath(),
                'url' => $media->getUrl(),
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'created_at' => $media->created_at->toIso8601String(),
            ]);
        }

        // Get archived profile photo
        $profilePhoto = $this->getFirstMedia('archived_profile_photo');
        if ($profilePhoto) {
            $documents->push([
                'category' => 'Profile',
                'name' => $profilePhoto->file_name,
                'path' => $profilePhoto->getPath(),
                'url' => $profilePhoto->getUrl(),
                'size' => $profilePhoto->size,
                'mime_type' => $profilePhoto->mime_type,
                'created_at' => $profilePhoto->created_at->toIso8601String(),
            ]);
        }

        // Get archived licenses
        $licenses = $this->getMedia('archived_licenses');
        foreach ($licenses as $media) {
            $documents->push([
                'category' => 'Licenses',
                'name' => $media->file_name,
                'path' => $media->getPath(),
                'url' => $media->getUrl(),
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'created_at' => $media->created_at->toIso8601String(),
            ]);
        }

        return $documents;
    }

    /**
     * Get documents organized by category.
     * 
     * @return array
     */
    public function getDocumentsByCategory(): array
    {
        $documents = $this->getDocumentsUpToArchiveDate();
        
        return $documents->groupBy('category')->map(function ($docs, $category) {
            return [
                'category' => $category,
                'count' => $docs->count(),
                'documents' => $docs->values()->toArray(),
            ];
        })->toArray();
    }

    /**
     * Get total document count.
     * 
     * @return int
     */
    public function getDocumentCount(): int
    {
        return $this->getDocumentsUpToArchiveDate()->count();
    }

    /**
     * Get the archive filename for ZIP download.
     * 
     * @return string
     */
    public function getArchiveFileName(): string
    {
        $driverName = str_replace(' ', '_', $this->full_name);
        $date = $this->archived_at->format('Y-m-d');
        
        return "driver_archive_{$driverName}_{$date}.zip";
    }

    /**
     * Check if this archive can be accessed by a specific carrier.
     * 
     * @param int $carrierId
     * @return bool
     */
    public function canBeAccessedByCarrier(int $carrierId): bool
    {
        return (int) $this->carrier_id === $carrierId;
    }
}
