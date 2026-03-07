<?php

namespace App\Models\Admin\Driver;

use App\Models\UserDriverDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DriverW9Form extends Model
{
    use HasFactory;

    protected $table = 'driver_w9_forms';

    protected $fillable = [
        'user_driver_detail_id',
        'name',
        'business_name',
        'tax_classification',
        'llc_classification',
        'other_classification',
        'has_foreign_partners',
        'exempt_payee_code',
        'fatca_exemption_code',
        'address',
        'city',
        'state',
        'zip_code',
        'account_numbers',
        'tin_type',
        'tin_encrypted',
        'signature',
        'signed_date',
        'pdf_path',
    ];

    protected $casts = [
        'tin_encrypted' => 'encrypted',
        'has_foreign_partners' => 'boolean',
        'signed_date' => 'date',
    ];

    /**
     * Get city, state, zip formatted string
     */
    public function getCityStateZipAttribute(): string
    {
        return "{$this->city}, {$this->state} {$this->zip_code}";
    }

    /**
     * Split decrypted SSN into 3-2-4 parts
     */
    public function getSsnParts(): array
    {
        $digits = preg_replace('/\D/', '', $this->tin_encrypted);
        return [
            substr($digits, 0, 3),
            substr($digits, 3, 2),
            substr($digits, 5, 4),
        ];
    }

    /**
     * Split decrypted EIN into 2-7 parts
     */
    public function getEinParts(): array
    {
        $digits = preg_replace('/\D/', '', $this->tin_encrypted);
        return [
            substr($digits, 0, 2),
            substr($digits, 2, 7),
        ];
    }

    public function userDriverDetail()
    {
        return $this->belongsTo(UserDriverDetail::class);
    }
}
