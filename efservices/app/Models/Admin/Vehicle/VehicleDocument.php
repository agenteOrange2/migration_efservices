<?php

namespace App\Models\Admin\Vehicle;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VehicleDocument extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'vehicle_id',
        'document_type',
        'document_number',
        'issued_date',
        'expiration_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiration_date' => 'date',
    ];

    // Tipos de documentos comunes para vehículos en Estados Unidos
    const DOC_TYPE_REGISTRATION = 'registration';
    const DOC_TYPE_INSURANCE = 'insurance';
    const DOC_TYPE_ANNUAL_INSPECTION = 'annual_inspection';
    const DOC_TYPE_IRP_PERMIT = 'irp_permit';
    const DOC_TYPE_IFTA = 'ifta';
    const DOC_TYPE_TITLE = 'title';
    const DOC_TYPE_LEASE_AGREEMENT = 'lease_agreement';
    const DOC_TYPE_MAINTENANCE_RECORD = 'maintenance_record';
    const DOC_TYPE_REPAIR_RECORD = 'repair_record';
    const DOC_TYPE_EMISSIONS_TEST = 'emissions_test';
    const DOC_TYPE_OTHER = 'other';

    // Estados de documentos
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the vehicle that owns the document.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get document type name
     */
    public function getDocumentTypeNameAttribute(): string
    {
        return match ($this->document_type) {
            self::DOC_TYPE_REGISTRATION => 'Registration',
            self::DOC_TYPE_INSURANCE => 'Insurance',
            self::DOC_TYPE_ANNUAL_INSPECTION => 'Annual Inspection',
            self::DOC_TYPE_IRP_PERMIT => 'IRP Permit',
            self::DOC_TYPE_IFTA => 'IFTA',
            self::DOC_TYPE_TITLE => 'Title',
            self::DOC_TYPE_LEASE_AGREEMENT => 'Lease Agreement',
            self::DOC_TYPE_MAINTENANCE_RECORD => 'Maintenance Record',
            self::DOC_TYPE_REPAIR_RECORD => 'Repair Record',
            self::DOC_TYPE_EMISSIONS_TEST => 'Emissions Test',
            self::DOC_TYPE_OTHER => 'Other',
            default => 'Unknown'
        };
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown'
        };
    }

    /**
     * Check if document is expired
     */
    public function isExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->isPast();
    }

    /**
     * Check if document is about to expire (within next 30 days)
     */
    public function isAboutToExpire(int $days = 30): bool
    {
        if (!$this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isFuture() &&
            $this->expiration_date->lte(now()->addDays($days));
    }

    /**
     * Check if document is expiring soon (30 days default)
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->isAboutToExpire($days);
    }

    /**
     * Get preview URL attribute
     */
    public function getPreviewUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('document_files');
        
        if (!$media) {
            return null;
        }

        // For images, return the preview conversion URL
        if (str_starts_with($media->mime_type, 'image/')) {
            return $media->hasGeneratedConversion('preview') 
                ? $media->getUrl('preview') 
                : $media->getUrl();
        }

        // For PDFs, return the original URL (browsers can preview PDFs)
        if ($media->mime_type === 'application/pdf') {
            return $media->getUrl();
        }

        // For other file types, return null (no preview available)
        return null;
    }

    /**
     * Check if document can be previewed
     */
    public function canPreview(): bool
    {
        $media = $this->getFirstMedia('document_files');
        
        if (!$media) {
            return false;
        }

        // Support preview for images and PDFs
        return str_starts_with($media->mime_type, 'image/') || 
               $media->mime_type === 'application/pdf';
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeAttribute(): ?string
    {
        $media = $this->getFirstMedia('document_files');
        
        if (!$media) {
            return null;
        }

        $bytes = $media->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expiration_date) {
            return null;
        }

        return now()->diffInDays($this->expiration_date, false);
    }

    /**
     * Get expiration status with color coding
     */
    public function getExpirationStatusAttribute(): array
    {
        if (!$this->expiration_date) {
            return [
                'status' => 'no_expiration',
                'label' => 'No Expiration',
                'color' => 'gray',
                'icon' => 'fas fa-minus'
            ];
        }

        if ($this->isExpired()) {
            return [
                'status' => 'expired',
                'label' => 'Expired',
                'color' => 'red',
                'icon' => 'fas fa-exclamation-triangle',
                'days' => abs($this->days_until_expiration)
            ];
        }

        if ($this->isExpiringSoon()) {
            return [
                'status' => 'expiring_soon',
                'label' => 'Expiring Soon',
                'color' => 'yellow',
                'icon' => 'fas fa-clock',
                'days' => $this->days_until_expiration
            ];
        }

        return [
            'status' => 'active',
            'label' => 'Active',
            'color' => 'green',
            'icon' => 'fas fa-check-circle',
            'days' => $this->days_until_expiration
        ];
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document_files')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);
    }
    
    /**
     * Define la ruta personalizada para almacenar los archivos
     */
    public function getMediaFolderName(): string
    {
        return 'vehicle/' . $this->vehicle_id;
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10);

        $this->addMediaConversion('preview')
            ->width(500)
            ->height(500);
    }

    /**
     * Update status based on expiration date
     */
    public function updateStatusBasedOnExpiration(): void
    {
        if ($this->isExpired()) {
            $this->status = self::STATUS_EXPIRED;
            $this->save();
        }
    }


    /**
     * Relación con los documentos del vehículo
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    /**
     * Verificar si hay documentos vencidos
     */
    public function hasExpiredDocuments(): bool
    {
        return $this->documents()
            ->where('status', VehicleDocument::STATUS_EXPIRED)
            ->orWhere(function ($query) {
                $query->whereNotNull('expiration_date')
                    ->where('expiration_date', '<', now());
            })
            ->exists();
    }

    /**
     * Verificar si hay documentos por vencer (próximos 30 días)
     */
    public function hasDocumentsAboutToExpire(int $days = 30): bool
    {
        return $this->documents()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '>', now())
            ->where('expiration_date', '<=', now()->addDays($days))
            ->exists();
    }

    /**
     * Obtener documentos activos
     */
    public function activeDocuments(): HasMany
    {
        return $this->documents()
            ->where('status', VehicleDocument::STATUS_ACTIVE);
    }

    /**
     * Obtener documentos vencidos
     */
    public function expiredDocuments(): HasMany
    {
        return $this->documents()
            ->where(function ($query) {
                $query->where('status', VehicleDocument::STATUS_EXPIRED)
                    ->orWhere(function ($q) {
                        $q->whereNotNull('expiration_date')
                            ->where('expiration_date', '<', now());
                    });
            });
    }

    /**
     * Obtener documentos pendientes
     */
    public function pendingDocuments(): HasMany
    {
        return $this->documents()
            ->where('status', VehicleDocument::STATUS_PENDING);
    }
}
