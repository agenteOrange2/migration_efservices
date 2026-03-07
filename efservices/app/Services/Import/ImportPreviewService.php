<?php

namespace App\Services\Import;

use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverAddress;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\DriverTrainingSchool;
use App\Models\Admin\Driver\MasterCompany;
use App\Models\EmergencyRepair;
use App\Models\Hos\HosEntry;
use App\Models\User;
use App\Models\UserDriverDetail;
use App\Models\UserCarrierDetail;
use App\Models\Carrier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class ImportPreviewService
{
    /**
     * Generate preview of import data.
     */
    public function generatePreview(string $type, UploadedFile $file, ?int $carrierId): array
    {
        // Read the file
        $data = Excel::toCollection(null, $file)->first();

        if (!$data || $data->isEmpty() || $data->count() < 2) {
            return [
                'success' => false,
                'error' => 'The file is empty or could not be read.',
                'rows' => [],
                'total' => 0,
                'valid' => 0,
                'duplicates' => 0,
                'errors' => 0,
            ];
        }

        // Get headers from first row (the values, not the keys)
        $firstRow = $data->first()->toArray();
        $headers = array_values($firstRow);

        // Normalize headers to lowercase and snake_case
        $normalizedHeaders = array_map(function ($header) {
            return strtolower(str_replace([' ', '-'], '_', trim((string) $header)));
        }, $headers);

        // Process rows for preview
        $previewData = [];
        $validCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;

        // Skip header row (index 0) and process data rows
        foreach ($data as $index => $row) {
            if ($index === 0) {
                continue; // Skip header row
            }

            // Map row values to header keys
            $rowValues = array_values($row->toArray());
            $rowArray = [];

            foreach ($normalizedHeaders as $i => $header) {
                $rowArray[$header] = $rowValues[$i] ?? null;
            }

            $rowNumber = $index + 1;

            // Validate the row
            $validation = $this->validateRow($type, $rowArray);

            // Check for duplicates
            $isDuplicate = $this->checkDuplicate($type, $rowArray, $carrierId);

            $status = 'valid';
            $statusMessage = 'Ready to import';

            if (!$validation['valid']) {
                $status = 'error';
                $statusMessage = implode(', ', $validation['errors']);
                $errorCount++;
            } elseif ($isDuplicate) {
                $status = 'duplicate';
                $statusMessage = $isDuplicate;
                $duplicateCount++;
            } else {
                $validCount++;
            }

            $previewData[] = [
                'row' => $rowNumber,
                'data' => $rowArray,
                'status' => $status,
                'message' => $statusMessage,
            ];

            // Limit preview to first 100 rows
            if (count($previewData) >= 100) {
                break;
            }
        }

        return [
            'success' => true,
            'headers' => $normalizedHeaders,
            'rows' => $previewData,
            'total' => $data->count() - 1, // Exclude header
            'valid' => $validCount,
            'duplicates' => $duplicateCount,
            'errors' => $errorCount,
            'preview_limited' => ($data->count() - 1) > 100,
        ];
    }

    /**
     * Validate a row based on import type.
     */
    protected function validateRow(string $type, array $row): array
    {
        $rules = $this->getValidationRules($type);
        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all(),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Get validation rules for import type.
     */
    protected function getValidationRules(string $type): array
    {
        return match ($type) {
            'drivers' => [
                'name' => 'required|string',
                'email' => 'required|email',
                'last_name' => 'required|string',
                'date_of_birth' => 'required',
            ],
            'carriers' => [
                'name' => 'required',
                'address' => 'required',
                'state' => 'required',
                'zipcode' => 'required',
                'ein_number' => 'required',
            ],
            'user_carriers' => [
                'name' => 'required|string',
                'email' => 'required|email',
            ],
            'vehicles' => [
                'make' => 'required|string',
                'model' => 'required|string',
                'type' => 'required|string',
                'year' => 'required',
                'vin' => 'required|string',
            ],
            'maintenance' => [
                'service_date' => 'required',
            ],
            'repairs' => [
                'repair_name' => 'required|string',
                'repair_date' => 'required',
            ],
            'hos_entries' => [
                'driver_email' => 'required|email',
                'date' => 'required',
                'status' => 'required|string',
                'start_time' => 'required',
            ],
            'driver_addresses' => [
                'driver_email' => 'required|email',
                'address_line1' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string',
                'zip_code' => 'required|string',
                'from_date' => 'required',
            ],
            'driver_licenses' => [
                'driver_email' => 'required|email',
                'license_number' => 'required|string',
                'state_of_issue' => 'required|string',
                'license_class' => 'required|string',
                'expiration_date' => 'required',
            ],
            'driver_medical' => [
                'driver_email' => 'required|email',
                'medical_examiner_name' => 'required|string',
                'medical_examiner_registry_number' => 'required|string',
                'medical_card_expiration_date' => 'required',
            ],
            'driver_employment' => [
                'driver_email' => 'required|email',
                'company_name' => 'required|string',
                'employed_from' => 'required',
                'employed_to' => 'required',
            ],
            'driver_training' => [
                'driver_email' => 'required|email',
                'school_name' => 'required|string',
                'date_start' => 'required',
                'date_end' => 'required',
                'city' => 'required|string',
                'state' => 'required|string',
            ],
            default => [],
        };
    }

    /**
     * Check for duplicates based on import type.
     */
    protected function checkDuplicate(string $type, array $row, ?int $carrierId): ?string
    {
        return match ($type) {
            'drivers' => $this->checkDriverDuplicate($row),
            'carriers' => $this->checkCarrierDuplicate($row),
            'user_carriers' => $this->checkUserCarrierDuplicate($row),
            'vehicles' => $this->checkVehicleDuplicate($row, $carrierId),
            'maintenance' => $this->checkMaintenanceDuplicate($row, $carrierId),
            'repairs' => $this->checkRepairDuplicate($row, $carrierId),
            'hos_entries' => $this->checkHosEntryDuplicate($row, $carrierId),
            'driver_addresses' => $this->checkDriverAddressDuplicate($row, $carrierId),
            'driver_licenses' => $this->checkDriverLicenseDuplicate($row, $carrierId),
            'driver_medical' => $this->checkDriverMedicalDuplicate($row, $carrierId),
            'driver_employment' => $this->checkDriverEmploymentDuplicate($row, $carrierId),
            'driver_training' => $this->checkDriverTrainingDuplicate($row, $carrierId),
            default => null,
        };
    }

    /**
     * Check for duplicate driver (by email).
     */
    protected function checkDriverDuplicate(array $row): ?string
    {
        if (empty($row['email'])) {
            return null;
        }

        $email = strtolower(trim($row['email']));

        if (User::where('email', $email)->exists()) {
            return "User with email {$email} already exists";
        }

        return null;
    }

    /**
     * Check for duplicate carrier (by EIN, DOT, or MC).
     */
    protected function checkCarrierDuplicate(array $row): ?string
    {
        // Check EIN
        if (!empty($row['ein_number'])) {
            $ein = preg_replace('/[^0-9]/', '', $row['ein_number']);
            if (strlen($ein) === 9) {
                $formattedEin = substr($ein, 0, 2) . '-' . substr($ein, 2);
                if (Carrier::where('ein_number', $formattedEin)->exists()) {
                    return "Carrier with EIN {$formattedEin} already exists";
                }
            }
        }

        // Check DOT
        if (!empty($row['dot_number'])) {
            $dot = trim($row['dot_number']);
            if (Carrier::where('dot_number', $dot)->exists()) {
                return "Carrier with DOT {$dot} already exists";
            }
        }

        // Check MC
        if (!empty($row['mc_number'])) {
            $mc = trim($row['mc_number']);
            if (Carrier::where('mc_number', $mc)->exists()) {
                return "Carrier with MC {$mc} already exists";
            }
        }

        return null;
    }

    /**
     * Check for duplicate user carrier (by email).
     */
    protected function checkUserCarrierDuplicate(array $row): ?string
    {
        if (empty($row['email'])) {
            return null;
        }

        $email = strtolower(trim($row['email']));

        if (User::where('email', $email)->exists()) {
            return "User with email {$email} already exists";
        }

        return null;
    }

    /**
     * Check for duplicate vehicle.
     */
    protected function checkVehicleDuplicate(array $row, ?int $carrierId): ?string
    {
        if (empty($row['vin'])) {
            return null;
        }

        $vin = strtoupper(preg_replace('/\s+/', '', $row['vin']));

        if (Vehicle::where('carrier_id', $carrierId)->where('vin', $vin)->exists()) {
            return "Vehicle with VIN {$vin} already exists";
        }

        return null;
    }

    /**
     * Check for duplicate maintenance record.
     */
    protected function checkMaintenanceDuplicate(array $row, ?int $carrierId): ?string
    {
        $vehicle = $this->findVehicle($row, $carrierId);

        if (!$vehicle) {
            return null; // Will be handled as error during import
        }

        $serviceDate = $this->parseDate($row['service_date'] ?? null);

        if (!$serviceDate) {
            return null;
        }

        $query = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->whereDate('service_date', $serviceDate);

        if (!empty($row['service_tasks'])) {
            $query->where('service_tasks', $row['service_tasks']);
        }

        if ($query->exists()) {
            return "Maintenance record already exists for this date";
        }

        return null;
    }

    /**
     * Check for duplicate repair record.
     */
    protected function checkRepairDuplicate(array $row, ?int $carrierId): ?string
    {
        $vehicle = $this->findVehicle($row, $carrierId);

        if (!$vehicle) {
            return null;
        }

        $repairDate = $this->parseDate($row['repair_date'] ?? null);

        if (!$repairDate) {
            return null;
        }

        $query = EmergencyRepair::where('vehicle_id', $vehicle->id)
            ->whereDate('repair_date', $repairDate);

        if (!empty($row['repair_name'])) {
            $query->where('repair_name', $row['repair_name']);
        }

        if ($query->exists()) {
            return "Repair record already exists for this date";
        }

        return null;
    }

    /**
     * Check for duplicate HOS entry.
     */
    protected function checkHosEntryDuplicate(array $row, ?int $carrierId): ?string
    {
        if (empty($row['driver_email'])) {
            return null;
        }

        $email = trim($row['driver_email']);
        $user = User::where('email', $email)->first();
        if (!$user) {
            return null;
        }

        $driver = UserDriverDetail::where('user_id', $user->id)
            ->where('carrier_id', $carrierId)
            ->first();

        if (!$driver) {
            return null;
        }

        $date = $this->parseDate($row['date'] ?? $row['start_time'] ?? null);

        if (!$date) {
            return null;
        }

        // Match by driver + date + status + start_time to allow multiple
        // entries with the same status on the same day (different time blocks)
        $query = HosEntry::where('user_driver_detail_id', $driver->id)
            ->where('carrier_id', $carrierId)
            ->whereDate('date', $date);

        // Optionally filter by status if provided
        $status = strtolower(trim($row['status'] ?? ''));
        if (!empty($status)) {
            // Normalize status for comparison
            $normalizedStatus = $this->normalizeHosStatus($status);
            if ($normalizedStatus) {
                $query->where('status', $normalizedStatus);
            }
        }

        // Include start_time to distinguish time blocks with the same status
        $startTime = $this->parseDateTime($row['start_time'] ?? null);
        if ($startTime) {
            $query->where('start_time', $startTime);
        }

        if ($query->exists()) {
            return "Duplicate HOS entry";
        }

        return null;
    }

    /**
     * Normalize HOS status value.
     */
    protected function normalizeHosStatus(string $value): ?string
    {
        $value = strtolower(trim($value));

        $mapping = [
            'on_duty_driving' => 'on_duty_driving',
            'on-duty-driving' => 'on_duty_driving',
            'driving' => 'on_duty_driving',
            'd' => 'on_duty_driving',
            'on_duty_not_driving' => 'on_duty_not_driving',
            'on-duty-not-driving' => 'on_duty_not_driving',
            'on_duty' => 'on_duty_not_driving',
            'on-duty' => 'on_duty_not_driving',
            'od' => 'on_duty_not_driving',
            'off_duty' => 'off_duty',
            'off-duty' => 'off_duty',
            'off' => 'off_duty',
        ];

        return $mapping[$value] ?? null;
    }

    /**
     * Find vehicle by unit number or VIN.
     */
    protected function findVehicle(array $row, ?int $carrierId): ?Vehicle
    {
        if (!empty($row['vehicle_unit_number'])) {
            $vehicle = Vehicle::where('carrier_id', $carrierId)
                ->where('company_unit_number', trim($row['vehicle_unit_number']))
                ->first();

            if ($vehicle) {
                return $vehicle;
            }
        }

        if (!empty($row['vehicle_vin'])) {
            return Vehicle::where('carrier_id', $carrierId)
                ->where('vin', strtoupper(preg_replace('/\s+/', '', $row['vehicle_vin'])))
                ->first();
        }

        return null;
    }

    /**
     * Parse date safely.
     */
    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $value = trim((string) $value);
            
            // Try common date formats
            // US format (m/d/Y) MUST be before d/m/Y - system uses US dates
            $formats = [
                'Y-m-d',      // 2026-01-25 (ISO) - Most common, check first
                'm/d/Y',      // 01/25/2026 (US format) - Before d/m/Y!
                'm-d-Y',      // 01-25-2026 (US format)
                'd/m/Y',      // 25/01/2026 (DD/MM/YYYY)
                'd-m-Y',      // 25-01-2026
                'd.m.Y',      // 25.01.2026
                'Y/m/d',      // 2026/01/25
            ];

            foreach ($formats as $format) {
                try {
                    $date = \Carbon\Carbon::createFromFormat($format, $value);
                    if ($date && $date->format($format) === $value) {
                        return $date->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback to Carbon::parse
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse datetime safely.
     */
    protected function parseDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
