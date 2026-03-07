<?php

namespace App\Models\Hos;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HosEntryAuditLog extends Model
{
    use HasFactory;

    // Action constants
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';

    public const ACTIONS = [
        self::ACTION_CREATED,
        self::ACTION_UPDATED,
        self::ACTION_DELETED,
    ];

    protected $fillable = [
        'hos_entry_id',
        'modified_by',
        'action',
        'original_values',
        'new_values',
        'reason',
    ];

    protected $casts = [
        'original_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the HOS entry this audit log belongs to.
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(HosEntry::class, 'hos_entry_id');
    }

    /**
     * Get the user who made the modification.
     */
    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    /**
     * Get human-readable action name.
     */
    public function getActionNameAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Created',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_DELETED => 'Deleted',
            default => 'Unknown',
        };
    }

    /**
     * Get the changes made in this audit log.
     */
    public function getChangesAttribute(): array
    {
        if ($this->action !== self::ACTION_UPDATED) {
            return [];
        }

        $changes = [];
        $original = $this->original_values ?? [];
        $new = $this->new_values ?? [];

        foreach ($new as $key => $value) {
            if (!isset($original[$key]) || $original[$key] !== $value) {
                $changes[$key] = [
                    'from' => $original[$key] ?? null,
                    'to' => $value,
                ];
            }
        }

        return $changes;
    }

    /**
     * Create an audit log for entry creation.
     */
    public static function logCreation(HosEntry $entry, int $userId, ?string $reason = null): self
    {
        return self::create([
            'hos_entry_id' => $entry->id,
            'modified_by' => $userId,
            'action' => self::ACTION_CREATED,
            'original_values' => null,
            'new_values' => $entry->toArray(),
            'reason' => $reason,
        ]);
    }

    /**
     * Create an audit log for entry update.
     */
    public static function logUpdate(HosEntry $entry, array $originalValues, int $userId, string $reason): self
    {
        return self::create([
            'hos_entry_id' => $entry->id,
            'modified_by' => $userId,
            'action' => self::ACTION_UPDATED,
            'original_values' => $originalValues,
            'new_values' => $entry->fresh()->toArray(),
            'reason' => $reason,
        ]);
    }

    /**
     * Create an audit log for entry deletion.
     */
    public static function logDeletion(HosEntry $entry, int $userId, string $reason): self
    {
        return self::create([
            'hos_entry_id' => $entry->id,
            'modified_by' => $userId,
            'action' => self::ACTION_DELETED,
            'original_values' => $entry->toArray(),
            'new_values' => null,
            'reason' => $reason,
        ]);
    }
}
