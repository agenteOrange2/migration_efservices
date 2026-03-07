<?php

namespace App\Models;

use App\Exceptions\ImmutableRecordException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * MigrationRecord Model
 * 
 * Represents an immutable audit record of a driver migration between carriers.
 * Used for compliance tracking and legal documentation (FMCSA, DOT).
 * 
 * @property int $id
 * @property int $driver_user_id
 * @property int $source_carrier_id
 * @property int $target_carrier_id
 * @property \Carbon\Carbon $migrated_at
 * @property int $migrated_by_user_id
 * @property string|null $reason
 * @property string|null $notes
 * @property array $driver_snapshot
 * @property string $status
 * @property \Carbon\Carbon|null $rolled_back_at
 * @property int|null $rolled_back_by_user_id
 * @property string|null $rollback_reason
 */
class MigrationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_user_id',
        'source_carrier_id',
        'target_carrier_id',
        'migrated_at',
        'migrated_by_user_id',
        'reason',
        'notes',
        'driver_snapshot',
        'status',
        'rolled_back_at',
        'rolled_back_by_user_id',
        'rollback_reason',
    ];

    protected $casts = [
        'migrated_at' => 'datetime',
        'rolled_back_at' => 'datetime',
        'driver_snapshot' => 'array',
    ];

    // Status constants
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    // Rollback grace period in hours
    public const ROLLBACK_GRACE_PERIOD_HOURS = 24;

    /**
     * Fields that are allowed to be updated (only rollback-related fields).
     */
    protected static array $allowedUpdateFields = [
        'status',
        'rolled_back_at',
        'rolled_back_by_user_id',
        'rollback_reason',
        'updated_at',
    ];

    /**
     * Boot the model.
     * Implements immutability protection - only rollback fields can be updated.
     */
    protected static function booted(): void
    {
        static::updating(function (MigrationRecord $record) {
            $changes = array_keys($record->getDirty());
            
            foreach ($changes as $field) {
                if (!in_array($field, self::$allowedUpdateFields)) {
                    throw new ImmutableRecordException(
                        "Migration records are immutable. Cannot modify field: {$field}"
                    );
                }
            }
        });
    }

    /**
     * Get the source carrier (where driver came from).
     */
    public function sourceCarrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'source_carrier_id');
    }

    /**
     * Get the target carrier (where driver went to).
     */
    public function targetCarrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'target_carrier_id');
    }

    /**
     * Get the driver user.
     */
    public function driverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    /**
     * Get the admin who performed the migration.
     */
    public function migratedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'migrated_by_user_id');
    }

    /**
     * Get the admin who performed the rollback.
     */
    public function rolledBackByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by_user_id');
    }

    /**
     * Get the associated driver archive.
     */
    public function driverArchive(): HasOne
    {
        return $this->hasOne(DriverArchive::class, 'migration_record_id');
    }

    /**
     * Check if this migration can be rolled back.
     * Rollback is only allowed within 24 hours of migration and if not already rolled back.
     */
    public function canRollback(): bool
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return false;
        }

        $hoursSinceMigration = $this->migrated_at->diffInHours(now());
        
        return $hoursSinceMigration <= self::ROLLBACK_GRACE_PERIOD_HOURS;
    }

    /**
     * Get the remaining time for rollback in hours.
     */
    public function getRollbackTimeRemainingAttribute(): ?int
    {
        if (!$this->canRollback()) {
            return null;
        }

        $hoursSinceMigration = $this->migrated_at->diffInHours(now());
        
        return self::ROLLBACK_GRACE_PERIOD_HOURS - $hoursSinceMigration;
    }

    /**
     * Check if this migration has been rolled back.
     */
    public function isRolledBack(): bool
    {
        return $this->status === self::STATUS_ROLLED_BACK;
    }

    /**
     * Scope to get only completed migrations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get only rolled back migrations.
     */
    public function scopeRolledBack($query)
    {
        return $query->where('status', self::STATUS_ROLLED_BACK);
    }

    /**
     * Scope to get migrations for a specific source carrier.
     */
    public function scopeFromCarrier($query, int $carrierId)
    {
        return $query->where('source_carrier_id', $carrierId);
    }

    /**
     * Scope to get migrations for a specific target carrier.
     */
    public function scopeToCarrier($query, int $carrierId)
    {
        return $query->where('target_carrier_id', $carrierId);
    }

    /**
     * Scope to get migrations within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('migrated_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get migrations that can still be rolled back.
     */
    public function scopeCanRollback($query)
    {
        $cutoffTime = now()->subHours(self::ROLLBACK_GRACE_PERIOD_HOURS);
        
        return $query->where('status', self::STATUS_COMPLETED)
            ->where('migrated_at', '>=', $cutoffTime);
    }
}
