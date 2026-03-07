<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Log;
use Laravel\Jetstream\HasProfilePhoto;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Foundation\Auth\User as Authenticatable;



class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, HasRoles;

    /** @use HasFactory<\Database\Factories\UserFactory> */    
    use HasProfilePhoto;    
    use TwoFactorAuthenticatable;    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that aren't mass assignable.
     * Protects sensitive fields from mass assignment vulnerabilities.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'id',
        'status',
        'email_verified_at',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * NOTE: Eager loading removed to prevent N+1 queries and improve performance.
     * Use explicit eager loading when needed:
     * - User::with('carrierDetails')->get()
     * - User::with('driverDetails')->get()
     * - User::with(['carrierDetails', 'driverDetails'])->get()
     */

    // Agregar método helper
    public function isCarrierUser(): bool
    {
        return $this->hasRole('user_carrier') && $this->carrierDetails()->exists();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relación con los detalles específicos de UserCarrier.
     */
    public function carrierDetails()
    {
        return $this->hasOne(UserCarrierDetail::class, 'user_id', 'id');
    }

    /**
     * Relación con los detalles específicos de UserDriver.
     */
    public function driverDetails()
    {
        return $this->hasOne(UserDriverDetail::class);
    }

    public function driverApplication()
    {
        return $this->hasOne(DriverApplication::class);
    }

    // Relación con carriers (managers de carriers)
    public function carriers()
    {
        return $this->belongsToMany(Carrier::class, 'user_carrier')
            ->withPivot('phone', 'job_position', 'photo', 'status')
            ->withTimestamps();
    }

    // Relación con Driver
    public function driver()
    {
        return $this->hasOne(UserDriverDetail::class);
    }

    // Relación singular para compatibilidad con controladores
    public function driverDetail()
    {
        return $this->hasOne(UserDriverDetail::class);
    }

    //Registro de Media Library
    public function getProfilePhotoUrlAttribute()
    {
        // Si el usuario pertenece a un UserCarrier, busca en la colección "profile_photo_carrier"
        if ($this->carrierDetails()->exists()) {
            $media = $this->getFirstMedia('profile_photo_carrier');
            $collection = 'profile_photo_carrier';
        } 
        // Si el usuario es un driver, busca en la colección "profile_photo_driver" del modelo UserDriverDetail
        elseif ($this->driverDetails()->exists()) {
            $driverDetail = $this->driverDetails;
            $media = $driverDetail->getFirstMedia('profile_photo_driver');
            return $media ? $media->getUrl() : asset('build/default_profile.png');
        } 
        else {
            // Si no, busca en la colección "profile_photos" (para superadmin o User estándar)
            $media = $this->getFirstMedia('profile_photos');
        }

        return $media ? $media->getUrl() : asset('build/default_profile.png');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photos')
            ->useDisk('public');
            
        $this->addMediaCollection('profile_photo_carrier')
            ->useDisk('public');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->keepOriginalImageFormat();
    }
    public function getMediaDirectoryAttribute(): string
    {
        return "users/{$this->id}/";
    }

    public function getMediaFileNameAttribute(): string
    {
        return "{$this->name}.webp";
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
    
    /**
     * Relación con las asignaciones de conductor del usuario.
     */
    public function driverAssignments()
    {
        return $this->hasMany(VehicleDriverAssignment::class);
    }

    /**
     * Relación con la asignación activa actual del conductor.
     */
    public function currentDriverAssignment()
    {
        return $this->hasOne(VehicleDriverAssignment::class)->where('status', 'active');
    }

    /**
     * Relación con las preferencias de notificación del usuario.
     */
    public function notificationPreferences()
    {
        return $this->hasMany(UserNotificationPreference::class);
    }

    /**
     * Obtener preferencia de notificación para una categoría específica.
     *
     * @param string $category
     * @return UserNotificationPreference|null
     */
    public function getNotificationPreference(string $category): ?UserNotificationPreference
    {
        return $this->notificationPreferences()
            ->where('category', $category)
            ->first();
    }

    /**
     * Verificar si una categoría de notificación está habilitada para in-app.
     *
     * @param string $category
     * @return bool
     */
    public function isNotificationInAppEnabled(string $category): bool
    {
        // Las categorías críticas siempre están habilitadas
        if (UserNotificationPreference::isCriticalCategory($category)) {
            return true;
        }

        $preference = $this->getNotificationPreference($category);
        
        // Si no hay preferencia configurada, está habilitada por defecto
        return $preference ? $preference->in_app_enabled : true;
    }

    /**
     * Verificar si una categoría de notificación está habilitada para email.
     *
     * @param string $category
     * @return bool
     */
    public function isNotificationEmailEnabled(string $category): bool
    {
        // Las categorías críticas siempre están habilitadas
        if (UserNotificationPreference::isCriticalCategory($category)) {
            return true;
        }

        $preference = $this->getNotificationPreference($category);
        
        // Si no hay preferencia configurada, está habilitada por defecto
        return $preference ? $preference->email_enabled : true;
    }

}
