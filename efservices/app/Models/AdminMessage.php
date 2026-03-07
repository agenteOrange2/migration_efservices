<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminMessage extends Model
{
    protected $fillable = [
        'sender_type',
        'sender_id',
        'subject',
        'message',
        'priority',
        'status',
        'sent_at',
        'context_type',
        'context_id'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the sender (polymorphic relationship)
     */
    public function sender(): MorphTo
    {
        return $this->morphTo('sender', 'sender_type', 'sender_id');
    }

    /**
     * Get all recipients for this message
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class, 'message_id');
    }

    /**
     * Get all status logs for this message
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(MessageStatusLog::class, 'message_id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to search by subject or message content
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
              ->orWhere('message', 'like', "%{$search}%");
        });
    }

    /**
     * Get priority badge color
     */
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'high' => 'red',
            'normal' => 'blue',
            'low' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'sent' => 'green',
            'delivered' => 'blue',
            'failed' => 'red',
            'draft' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Get sender name based on sender type
     */
    public function getSenderNameAttribute(): string
    {
        if (!$this->sender) return 'Unknown';
        
        return match($this->sender_type) {
            'App\\Models\\User' => $this->sender->name ?? 'Admin',
            'App\\Models\\Carrier' => $this->sender->name ?? 'Carrier',
            'App\\Models\\UserDriverDetail' => $this->sender->user->name ?? 'Driver',
            default => 'System'
        };
    }

    /**
     * Get sender email based on sender type
     */
    public function getSenderEmailAttribute(): ?string
    {
        if (!$this->sender) return null;
        
        try {
            return match($this->sender_type) {
                'App\\Models\\User' => $this->sender->email ?? null,
                'App\\Models\\Carrier' => optional($this->sender->users()->first())->email ?? null,
                'App\\Models\\UserDriverDetail' => optional($this->sender->user)->email ?? null,
                default => null
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get sender type badge label
     */
    public function getSenderTypeLabelAttribute(): string
    {
        return match($this->sender_type) {
            'App\\Models\\User' => 'Admin',
            'App\\Models\\Carrier' => 'Carrier',
            'App\\Models\\UserDriverDetail' => 'Driver',
            default => 'System'
        };
    }

    /**
     * Scope to filter by sender type
     */
    public function scopeBySenderType($query, $type)
    {
        return $query->where('sender_type', $type);
    }

    /**
     * Scope to get messages for a specific sender
     */
    public function scopeForSender($query, $senderType, $senderId)
    {
        return $query->where('sender_type', $senderType)
                    ->where('sender_id', $senderId);
    }

    /**
     * Scope to get messages where user is recipient
     */
    public function scopeForRecipient($query, $recipientType, $recipientId)
    {
        return $query->whereHas('recipients', function($q) use ($recipientType, $recipientId) {
            $q->where('recipient_type', $recipientType)
              ->where('recipient_id', $recipientId);
        });
    }
}
