<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'company',
        'email',
        'phone',
        'plan_name',
        'plan_price',
        'status',
        'admin_notes',
        'assigned_to',
        'responded_at',
        'ip_address',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'plan_price' => 'decimal:2',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'new' => 'bg-primary text-white',
            'in_progress' => 'bg-warning text-white',
            'contacted' => 'bg-success text-white',
            'closed' => 'bg-secondary text-white',
            default => 'bg-secondary text-white',
        };
    }
}
