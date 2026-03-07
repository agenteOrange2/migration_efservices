<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Log;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Driver\DriverAddress;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverAccident;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\Admin\Driver\DriverFmcsrData;
use App\Models\Admin\Driver\DriverExperience;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverWorkHistory;
use App\Models\Admin\Driver\DriverCertification;
use App\Models\Admin\Driver\DriverCompanyPolicy;
use App\Models\Admin\Driver\DriverTrainingSchool;
use App\Models\Admin\Driver\DriverCriminalHistory;
use App\Models\Admin\Driver\DriverW9Form;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\DriverTrafficConviction;
use App\Models\Admin\Driver\DriverUnemploymentPeriod;
use App\Models\Admin\Driver\DriverTesting;
use App\Models\Admin\Driver\DriverInspection;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\OwnerOperatorDetail;
use App\Models\ThirdPartyDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\Admin\Driver\DriverMedicalQualification;

class UserDriverDetail extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, \App\Traits\HasHosDocuments;

    protected $fillable = [
        'user_id',
        'carrier_id',
        'middle_name',
        'last_name',
        'phone',
        'date_of_birth',
        'status',
        'terms_accepted',
        'confirmation_token',
        'application_completed',
        'current_step',
        'assigned_vehicle_id',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'notes',
        'hire_date',
        'termination_date',
        'created_by_admin',
        'updated_by_admin',
        'completion_percentage',
        'use_custom_dates',
        'custom_created_at',
        'has_completed_employment_history',
        'custom_registration_date',
        'custom_completion_date',
        // HOS Cycle fields
        'hos_cycle_type',
        'hos_cycle_change_requested',
        'hos_cycle_change_requested_to',
        'hos_cycle_change_requested_at',
        'hos_cycle_change_approved_at',
        'hos_cycle_change_approved_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'status' => 'integer',
        'terms_accepted' => 'boolean',
        'application_completed' => 'boolean',
        'current_step' => 'integer',
        'assigned_vehicle_id' => 'integer',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'created_by_admin' => 'integer',
        'updated_by_admin' => 'integer',
        'completion_percentage' => 'integer',
        'use_custom_dates' => 'boolean',
        'custom_created_at' => 'datetime',
        'custom_registration_date' => 'date',
        'custom_completion_date' => 'date',
        // HOS Cycle casts
        'hos_cycle_change_requested' => 'boolean',
        'hos_cycle_change_requested_at' => 'datetime',
        'hos_cycle_change_approved_at' => 'datetime',
    ];

    public function hasRequiredDocuments(): bool
    {
        // Implementar lógica de verificación de documentos
        return true; // Temporalmente para testing
    }

    // Constantes para los valores de status
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_PENDING = 2;

    // Effective status constants (combining driver + application status)
    public const EFFECTIVE_STATUS_DRAFT = 'draft';
    public const EFFECTIVE_STATUS_PENDING_REVIEW = 'pending_review';
    public const EFFECTIVE_STATUS_APPROVED = 'approved';
    public const EFFECTIVE_STATUS_REJECTED = 'rejected';
    public const EFFECTIVE_STATUS_ACTIVE = 'active';
    public const EFFECTIVE_STATUS_INACTIVE = 'inactive';

    /**
     * Get the effective status combining UserDriverDetail.status and DriverApplication.status.
     * If application is NOT yet approved (draft/pending/rejected), application status takes priority.
     * If application is approved (or doesn't exist), use the driver status directly.
     */
    public function getEffectiveStatus(): string
    {
        // Check application status - only matters if NOT yet approved
        $application = $this->relationLoaded('application') 
            ? $this->application 
            : $this->application()->first();

        if ($application) {
            switch ($application->status) {
                case 'draft':
                    return self::EFFECTIVE_STATUS_DRAFT;
                case 'pending':
                    return self::EFFECTIVE_STATUS_PENDING_REVIEW;
                case 'rejected':
                    return self::EFFECTIVE_STATUS_REJECTED;
                // 'approved' → recruitment done, fall through to driver status below
            }
        }

        // Application approved or no application — use driver status
        if ($this->status === self::STATUS_ACTIVE) {
            return self::EFFECTIVE_STATUS_ACTIVE;
        }

        if ($this->status === self::STATUS_PENDING) {
            return self::EFFECTIVE_STATUS_PENDING_REVIEW;
        }

        return self::EFFECTIVE_STATUS_INACTIVE;
    }

    /**
     * Get all possible effective statuses with labels for filters.
     */
    public static function getEffectiveStatusOptions(): array
    {
        return [
            self::EFFECTIVE_STATUS_DRAFT => 'Draft',
            self::EFFECTIVE_STATUS_PENDING_REVIEW => 'Pending Review',
            self::EFFECTIVE_STATUS_APPROVED => 'Approved',
            self::EFFECTIVE_STATUS_REJECTED => 'Rejected',
            self::EFFECTIVE_STATUS_ACTIVE => 'Active',
            self::EFFECTIVE_STATUS_INACTIVE => 'Inactive',
        ];
    }

    // HOS Cycle type constants
    public const HOS_CYCLE_60_7 = '60_7'; // 60 hours in 7 days
    public const HOS_CYCLE_70_8 = '70_8'; // 70 hours in 8 days (default)

    /**
     * Get the driver's HOS cycle type with fallback.
     * Returns the driver's individual setting, or carrier default if not set.
     */
    public function getEffectiveHosCycleType(): string
    {
        // If driver has a specific cycle type set, use it
        if ($this->hos_cycle_type) {
            return $this->hos_cycle_type;
        }

        // Fallback to carrier configuration or default
        return self::HOS_CYCLE_70_8;
    }

    /**
     * Request a cycle type change (driver initiates, carrier/admin approves).
     */
    public function requestCycleChange(string $newCycleType): bool
    {
        if (!in_array($newCycleType, [self::HOS_CYCLE_60_7, self::HOS_CYCLE_70_8])) {
            return false;
        }

        // Don't allow request if already on this cycle
        if ($this->getEffectiveHosCycleType() === $newCycleType) {
            return false;
        }

        $this->update([
            'hos_cycle_change_requested' => true,
            'hos_cycle_change_requested_to' => $newCycleType,
            'hos_cycle_change_requested_at' => now(),
            'hos_cycle_change_approved_at' => null,
            'hos_cycle_change_approved_by' => null,
        ]);

        return true;
    }

    /**
     * Approve a pending cycle change request (carrier/admin only).
     */
    public function approveCycleChange(int $approvedBy): bool
    {
        if (!$this->hos_cycle_change_requested || !$this->hos_cycle_change_requested_to) {
            return false;
        }

        $this->update([
            'hos_cycle_type' => $this->hos_cycle_change_requested_to,
            'hos_cycle_change_requested' => false,
            'hos_cycle_change_requested_to' => null,
            'hos_cycle_change_approved_at' => now(),
            'hos_cycle_change_approved_by' => $approvedBy,
        ]);

        return true;
    }

    /**
     * Reject a pending cycle change request.
     */
    public function rejectCycleChange(): bool
    {
        if (!$this->hos_cycle_change_requested) {
            return false;
        }

        $this->update([
            'hos_cycle_change_requested' => false,
            'hos_cycle_change_requested_to' => null,
            'hos_cycle_change_requested_at' => null,
        ]);

        return true;
    }

    /**
     * Check if driver has a pending cycle change request.
     */
    public function hasPendingCycleChangeRequest(): bool
    {
        return (bool) $this->hos_cycle_change_requested;
    }

    /**
     * Relationship to user who approved the last cycle change.
     */
    public function cycleChangeApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hos_cycle_change_approved_by');
    }
    
    /**
     * Scope to filter only active drivers.
     * Filters drivers with status = STATUS_ACTIVE (1).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to filter active drivers with active users.
     * Combines driver status filtering with user status filtering.
     * Returns only drivers where:
     * - driver.status = STATUS_ACTIVE (1)
     * - user.status = 1 (active)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveWithActiveUser($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereHas('user', function ($q) {
                $q->where('status', 1);
            });
    }

    /**
     * Obtener el teléfono formateado del conductor.
     *
     * @return string
     */
    public function getFormattedPhoneAttribute()
    {
        if (!$this->phone) return 'No phone available';

        // Limpiar el número (solo dígitos)
        $phone = preg_replace('/\D/', '', $this->phone);

        // Formatear según la longitud
        if (strlen($phone) === 10) {
            // Formato: (XXX) XXX-XXXX
            return sprintf(
                '(%s) %s-%s',
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6, 4)
            );
        } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
            // Formato: +1 (XXX) XXX-XXXX
            return sprintf(
                '+1 (%s) %s-%s',
                substr($phone, 1, 3),
                substr($phone, 4, 3),
                substr($phone, 7, 4)
            );
        }

        // Si no coincide con formatos estándar, devolver original
        return $this->phone;
    }

    /**
     * Obtener el nombre completo del conductor.
     * Concatena name (de user), middle_name y last_name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        $firstName = $this->user->name ?? '';
        $middleName = $this->middle_name ?? '';
        $lastName = $this->last_name ?? '';
        
        return trim("{$firstName} {$middleName} {$lastName}");
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }

    public function application()
    {
        return $this->hasOne(DriverApplication::class, 'user_id', 'user_id');
    }
    public function assignedVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_PENDING => 'Pending',
            default => 'Unknown',
        };
    }

    public function getProfilePhotoUrlAttribute()
    {
        $media = $this->getFirstMedia('profile_photo_driver');
        return $media ? $media->getUrl() : asset('build/default_profile.png');
    }

    //Media library
    public function registerMediaCollections(): void
    {
        // Driver profile and license photos
        $this->addMediaCollection('profile_photo_driver')
            ->useDisk('public')
            ->singleFile();
            
        $this->addMediaCollection('license_front')
            ->useDisk('public')
            ->singleFile();
            
        $this->addMediaCollection('license_back')
            ->useDisk('public')
            ->singleFile();

        // HOS Documents (from HasHosDocuments trait)
        $this->addMediaCollection('trip_reports')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('daily_logs')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('monthly_summaries')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('signatures')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/png', 'image/jpeg']);

        $this->addMediaCollection('employment_verification_attempts')
            ->useDisk('public')
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('w9_documents')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);

        $this->addMediaCollection('dot_policy_documents')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        // Deshabilitar conversiones temporalmente para evitar errores
        // Solo aplicar conversiones a fotos de perfil, no a licencias
        if ($media && $media->collection_name === 'profile_photo_driver') {
            $this->addMediaConversion('webp')
                ->format('webp')
                ->keepOriginalImageFormat();
        }
    }

    // Relación con direcciones eliminada - las direcciones pertenecen a DriverApplication
    // Usar $userDriverDetail->application->addresses en su lugar

    //Licencias
    public function licenses()
    {
        return $this->hasMany(DriverLicense::class, 'user_driver_detail_id');
    }

    public function primaryLicense()
    {
        return $this->hasOne(DriverLicense::class, 'user_driver_detail_id')
            ->where('is_primary', true)
            ->latest();
    }

    // Experiencia de conducción
    public function experiences()
    {
        return $this->hasMany(DriverExperience::class);
    }

    // Calificación médica
    public function medicalQualification()
    {
        return $this->hasOne(DriverMedicalQualification::class);
    }

    public function workHistories()
    {
        return $this->hasMany(DriverWorkHistory::class, 'user_driver_detail_id');
    }

    public function trainingSchools()
    {
        return $this->hasMany(DriverTrainingSchool::class);
    }

    public function trafficConvictions()
    {
        return $this->hasMany(DriverTrafficConviction::class);
    }

    public function accidents()
    {
        return $this->hasMany(DriverAccident::class);
    }

    public function testings()
    {
        return $this->hasMany(DriverTesting::class);
    }

    public function inspections()
    {
        return $this->hasMany(DriverInspection::class);
    }

    public function fmcsrData()
    {
        return $this->hasOne(DriverFmcsrData::class);
    }

    /**
     * Relación con períodos de desempleo
     */

    public function unemploymentPeriods()
    {
        return $this->hasMany(\App\Models\Admin\Driver\DriverUnemploymentPeriod::class);
    }

    /**
     * Relación con empresas donde ha trabajado
     */
    public function employmentCompanies()
    {
        return $this->hasMany(DriverEmploymentCompany::class);
    }

    /**
     * Relación con empleos relacionados
     */
    public function relatedEmployments()
    {
        return $this->hasMany(\App\Models\Admin\Driver\DriverRelatedEmployment::class);
    }

    /**
     * Relación con empleos relacionados del conductor (nueva tabla)
     */
    public function driver_related_employments()
    {
        return $this->hasMany(\App\Models\Admin\Driver\DriverRelatedEmployment::class, 'user_driver_detail_id');
    }


    public function companyPolicy()
    {
        return $this->hasOne(DriverCompanyPolicy::class);
    }

    public function criminalHistory()
    {
        return $this->hasOne(DriverCriminalHistory::class);
    }

    public function w9Form()
    {
        return $this->hasOne(DriverW9Form::class);
    }

    // En el modelo UserDriverDetail
    public function certification()
    {
        return $this->hasOne(DriverCertification::class, 'user_driver_detail_id');
    }

    // Relación con la tabla de historial de empleos del conductor
    public function driverEmploymentCompanies()
    {
        return $this->hasMany(\App\Models\Admin\Driver\DriverEmploymentCompany::class, 'user_driver_detail_id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'user_driver_detail_id');
    }

    /**
     * Relación con los cursos de capacitación del conductor
     */
    public function courses()
    {
        return $this->hasMany(\App\Models\Admin\Driver\DriverCourse::class, 'user_driver_detail_id');
    }

    /**
     * Relación con los entrenamientos asignados al conductor
     */
    public function driverTrainings()
    {
        return $this->hasMany(DriverTraining::class, 'user_driver_detail_id');
    }

    /**
     * Relación con todas las asignaciones de vehículos del conductor.
     */
    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(VehicleDriverAssignment::class);
    }

    /**
     * Relación con la asignación activa de vehículo del conductor.
     */
    public function activeVehicleAssignment(): HasOne
    {
        return $this->hasOne(VehicleDriverAssignment::class)->where('status', 'active');
    }

    /**
     * Alias para activeVehicleAssignment para compatibilidad.
     */
    public function currentVehicleAssignment(): HasOne
    {
        return $this->activeVehicleAssignment();
    }

    /**
     * Obtener el vehículo actualmente asignado al conductor.
     */
    public function currentVehicle()
    {
        return $this->activeVehicleAssignment()?->vehicle();
    }

    /**
     * Relación con los detalles de owner operator a través de las asignaciones de vehículos.
     */
    public function ownerOperatorDetail(): HasOneThrough
    {
        return $this->hasOneThrough(
            OwnerOperatorDetail::class,
            VehicleDriverAssignment::class,
            'user_driver_detail_id', // Foreign key on VehicleDriverAssignment table
            'vehicle_driver_assignment_id', // Foreign key on OwnerOperatorDetail table
            'id', // Local key on UserDriverDetail table
            'id' // Local key on VehicleDriverAssignment table
        );
    }

    /**
     * Relación con los detalles de third party a través de las asignaciones de vehículos.
     */
    public function thirdPartyDetail(): HasOneThrough
    {
        return $this->hasOneThrough(
            ThirdPartyDetail::class,
            VehicleDriverAssignment::class,
            'user_driver_detail_id', // Foreign key on VehicleDriverAssignment table
            'vehicle_driver_assignment_id', // Foreign key on ThirdPartyDetail table
            'id', // Local key on UserDriverDetail table
            'id' // Local key on VehicleDriverAssignment table
        );
    }

    /**
     * Get all messages received by this driver
     * (Driver is read-only, cannot send messages)
     */
    public function receivedMessages()
    {
        return $this->morphMany(MessageRecipient::class, 'recipient')
            ->with('message');
    }
}