<?php

namespace App\Models\Admin\Driver;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DriverTesting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_driver_detail_id',
        'carrier_id',
        'test_date',
        'test_type',
        'test_result',
        'status',
        'administered_by',
        'mro',
        'requester_name',
        'location',
        'scheduled_time',
        'notes',
        'next_test_due',
        'is_random_test',
        'is_post_accident_test',
        'is_reasonable_suspicion_test',
        'is_pre_employment_test',
        'is_follow_up_test',
        'is_return_to_duty_test',
        'is_other_reason_test',
        'other_reason_description',
        'bill_to',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'test_date' => 'date',
        'next_test_due' => 'date',
        'scheduled_time' => 'datetime',
        'is_random_test' => 'boolean',
        'is_post_accident_test' => 'boolean',
        'is_reasonable_suspicion_test' => 'boolean',
        'is_pre_employment_test' => 'boolean',
        'is_follow_up_test' => 'boolean',
        'is_return_to_duty_test' => 'boolean',
        'is_other_reason_test' => 'boolean',
    ];

    /**
     * Get the driver detail that owns the test.
     */
    public function userDriverDetail(): BelongsTo
    {
        return $this->belongsTo(UserDriverDetail::class);
    }

    /**
     * Get the driver detail associated with the test.
     */
    public function driverDetail(): BelongsTo
    {
        return $this->belongsTo(UserDriverDetail::class, 'user_driver_detail_id');
    }

    /**
     * Get the carrier associated with the test.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Get the user who created the test.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the test.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor Methods for Formatted Data
     */

    /**
     * Get formatted test date
     * 
     * @return string Formatted date in MM/DD/YYYY format
     */
    public function getFormattedTestDateAttribute(): string
    {
        return \App\Helpers\FormatHelper::formatDate($this->test_date);
    }

    /**
     * Get formatted scheduled time
     * 
     * @return string Formatted datetime in MM/DD/YYYY HH:MM AM/PM format
     */
    public function getFormattedScheduledTimeAttribute(): string
    {
        return \App\Helpers\FormatHelper::formatDateTime($this->scheduled_time);
    }

    /**
     * Get driver full name
     * 
     * @return string Driver's full name (first, middle, last) or 'N/A'
     */
    public function getDriverFullNameAttribute(): string
    {
        return \App\Helpers\FormatHelper::formatDriverName($this->userDriverDetail);
    }

    /**
     * Get carrier display name
     * 
     * @return string Carrier name with DOT number or 'N/A'
     */
    public function getCarrierDisplayNameAttribute(): string
    {
        return \App\Helpers\FormatHelper::formatCarrierName($this->carrier);
    }

    /**
     * Get status badge class
     * 
     * @return string CSS classes for status badge styling
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return \App\Helpers\FormatHelper::getStatusBadgeClass($this->status ?? '');
    }

    /**
     * Get test result badge class
     * 
     * @return string CSS classes for test result badge styling
     */
    public function getTestResultBadgeClassAttribute(): string
    {
        return \App\Helpers\FormatHelper::getTestResultBadgeClass($this->test_result ?? '');
    }

    /**
     * Check if testing has PDF
     * 
     * @return bool True if PDF exists in media library
     */
    public function getHasPdfAttribute(): bool
    {
        return $this->hasMedia('drug_test_pdf');
    }

    /**
     * Get test categories as array
     * 
     * @return array Array of test categories with labels and colors
     */
    public function getTestCategoriesAttribute(): array
    {
        $categories = [];
        
        if ($this->is_random_test) {
            $categories[] = ['label' => 'Random', 'color' => 'blue'];
        }
        if ($this->is_post_accident_test) {
            $categories[] = ['label' => 'Post Accident', 'color' => 'orange'];
        }
        if ($this->is_reasonable_suspicion_test) {
            $categories[] = ['label' => 'Reasonable Suspicion', 'color' => 'red'];
        }
        if ($this->is_pre_employment_test) {
            $categories[] = ['label' => 'Pre-Employment', 'color' => 'green'];
        }
        if ($this->is_follow_up_test) {
            $categories[] = ['label' => 'Follow-Up', 'color' => 'purple'];
        }
        if ($this->is_return_to_duty_test) {
            $categories[] = ['label' => 'Return-To-Duty', 'color' => 'indigo'];
        }
        if ($this->is_other_reason_test) {
            $categories[] = ['label' => 'Other', 'color' => 'gray'];
        }
        
        return $categories;
    }

    /**
     * Available test locations
     */
    public static function getLocations(): array
    {
        return [
            'odessa' => 'Odessa Office – 1560 W I-20 N, Odessa, TX, 79763',
            'midland' => 'Midland Office – 606 Kent St., Midland, TX, 79701',
            'abilene' => 'Abilene Office – 317 N Willis, Abilene, TX 79603',
            'seminole' => 'Seminole Office – 1305 Hobbs Hwy, Seminole, TX 79360',
            'kermit' => 'EFCTS Office – 801 Magnolia St, Kermit, TX 79745',
        ];
    }

    /**
     * Available test types for drug and alcohol testing
     */
    public static function getTestTypes(): array
    {
        return [
            'dot_drug_test' => 'DOT Drug test (MRO)',
            'non_dot_lab' => 'NON-DOT Lab (MRO)',
            'dot_alcohol_test' => 'DOT Alcohol test',
            'non_dot_alcohol_test' => 'NON-DOT Alcohol test',
            'panel_instant_test' => '10 Panel Instant test',
            'dot_drug_alcohol_test' => 'DOT Drug & Alcohol test',            
        ];
    }

    /**
     * Available test statuses
     */
    public static function getStatuses(): array
    {
        return [
            'Schedule' => 'Schedule',
            'In Progress' => 'In Progress',
            'Pending Review' => 'Pending Review',
            'Completed' => 'Completed',
            'Cancelled' => 'Cancelled',
        ];
    }

    /**
     * Bill to options
     */
    public static function getBillOptions(): array
    {
        return [
            'company' => 'Bill Company',
            'employee' => 'Employee pay for testing',
        ];
    }
    
    /**
     * Alias for getTestTypes() - Used by the controller
     */
    public static function getDrugTestTypes(): array
    {
        return self::getTestTypes();
    }
    
    /**
     * Available test results
     */
    public static function getTestResults(): array
    {
        return [
            'Positive' => 'Positive',
            'Negative' => 'Negative', 
            'Refusal' => 'Refusal',
        ];
    }
    
    /**
     * Get the list of administrators for drug tests
     */
    public static function getAdministrators(): array
    {
        return [
            'Permian Basin Drug & Alcohol' => 'Permian Basin Drug & Alcohol',
            'A-Dependable' => 'A-Dependable',
            'Norton' => 'Norton',
            'other' => 'Other'
        ];
    }
    
    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        // Collection for PDF reports
        $this->addMediaCollection('drug_test_pdf')
            ->singleFile();
            
        // Collection for test results documents (multiple files allowed)
        $this->addMediaCollection('test_results')
            ->useDisk('public');
        
        // Collection for test certificates
        $this->addMediaCollection('test_certificates')
            ->singleFile();
            
        // Collection for test authorization PDF generated by the system
        $this->addMediaCollection('test_authorization')
            ->singleFile();
            
        // Collection for user uploaded documents/attachments
        $this->addMediaCollection('document_attachments')
            ->useDisk('public');
    }
    
    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100);
    }
}
