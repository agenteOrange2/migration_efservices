<?php

namespace App\Services\Import;

use App\Imports\VehiclesImport;
use App\Imports\VehicleMaintenanceImport;
use App\Imports\EmergencyRepairsImport;
use App\Imports\HosEntriesImport;
use App\Imports\DriversImport;
use App\Imports\CarriersImport;
use App\Imports\UserCarriersImport;
use App\Imports\DriverAddressesImport;
use App\Imports\DriverLicensesImport;
use App\Imports\DriverMedicalImport;
use App\Imports\DriverEmploymentImport;
use App\Imports\DriverTrainingImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportService
{
    protected ImportPreviewService $previewService;

    public function __construct(ImportPreviewService $previewService)
    {
        $this->previewService = $previewService;
    }

    /**
     * Get available import types.
     */
    public function getAvailableTypes(): array
    {
        return [
            'drivers' => [
                'name' => 'Drivers (Complete Profile)',
                'description' => 'Import complete driver profiles including address, license, medical, employment history, and training school data',
                'template' => 'drivers_template.csv',
                'class' => DriversImport::class,
                'icon' => 'UserCheck',
            ],
            'carriers' => [
                'name' => 'Carriers',
                'description' => 'Import carrier companies',
                'template' => 'carriers_template.csv',
                'class' => CarriersImport::class,
                'icon' => 'Building',
            ],
            'user_carriers' => [
                'name' => 'Carrier Users',
                'description' => 'Import carrier staff users (creates User + Carrier Profile)',
                'template' => 'user_carriers_template.csv',
                'class' => UserCarriersImport::class,
                'icon' => 'Users',
            ],
            'vehicles' => [
                'name' => 'Vehicles',
                'description' => 'Import vehicle fleet data (trucks, trailers, etc.)',
                'template' => 'vehicles_template.csv',
                'class' => VehiclesImport::class,
                'icon' => 'Truck',
            ],
            'maintenance' => [
                'name' => 'Vehicle Maintenance',
                'description' => 'Import historical maintenance records',
                'template' => 'maintenance_template.csv',
                'class' => VehicleMaintenanceImport::class,
                'icon' => 'Wrench',
            ],
            'repairs' => [
                'name' => 'Emergency Repairs',
                'description' => 'Import emergency repair records',
                'template' => 'repairs_template.csv',
                'class' => EmergencyRepairsImport::class,
                'icon' => 'AlertTriangle',
            ],
            'hos_entries' => [
                'name' => 'HOS Driving Times',
                'description' => 'Import historical Hours of Service entries',
                'template' => 'hos_entries_template.csv',
                'class' => HosEntriesImport::class,
                'icon' => 'Clock',
            ],
            'driver_addresses' => [
                'name' => 'Driver Addresses (Additional History)',
                'description' => 'OPTIONAL: Import additional historical addresses for existing drivers',
                'template' => 'driver_addresses_template.csv',
                'class' => DriverAddressesImport::class,
                'icon' => 'MapPin',
            ],
            'driver_licenses' => [
                'name' => 'Driver Licenses (Additional)',
                'description' => 'OPTIONAL: Import additional license records for existing drivers',
                'template' => 'driver_licenses_template.csv',
                'class' => DriverLicensesImport::class,
                'icon' => 'CreditCard',
            ],
            'driver_medical' => [
                'name' => 'Driver Medical (Additional)',
                'description' => 'OPTIONAL: Import additional medical records for existing drivers',
                'template' => 'driver_medical_template.csv',
                'class' => DriverMedicalImport::class,
                'icon' => 'Heart',
            ],
            'driver_employment' => [
                'name' => 'Driver Employment (Additional History)',
                'description' => 'OPTIONAL: Import additional employment history for existing drivers',
                'template' => 'driver_employment_template.csv',
                'class' => DriverEmploymentImport::class,
                'icon' => 'Briefcase',
            ],
            'driver_training' => [
                'name' => 'Driver Training (Additional)',
                'description' => 'OPTIONAL: Import additional training records for existing drivers',
                'template' => 'driver_training_template.csv',
                'class' => DriverTrainingImport::class,
                'icon' => 'GraduationCap',
            ],
        ];
    }

    /**
     * Generate preview of import.
     */
    public function preview(string $type, UploadedFile $file, ?int $carrierId): array
    {
        return $this->previewService->generatePreview($type, $file, $carrierId);
    }

    /**
     * Execute the import.
     */
    public function import(string $type, string $filePath, ?int $carrierId, int $userId, string $duplicateAction = 'skip'): array
    {
        $types = $this->getAvailableTypes();

        if (!isset($types[$type])) {
            throw new \InvalidArgumentException("Invalid import type: {$type}");
        }

        $importClass = $types[$type]['class'];
        $import = new $importClass($carrierId, $userId, $duplicateAction);

        try {
            Excel::import($import, Storage::path($filePath));

            $result = [
                'success' => true,
                'imported_count' => count($import->getImportedRows()),
                'updated_count' => count($import->getUpdatedRows()),
                'skipped_count' => count($import->getSkippedRows()),
                'failed_count' => count($import->getFailures()),
                'imported_ids' => $import->getImportedRows(),
                'updated_ids' => $import->getUpdatedRows(),
                'skipped_rows' => $import->getSkippedRows(),
                'failures' => $import->getFailures(),
            ];

            Log::info('Import completed', [
                'type' => $type,
                'carrier_id' => $carrierId,
                'user_id' => $userId,
                'duplicate_action' => $duplicateAction,
                'imported' => $result['imported_count'],
                'updated' => $result['updated_count'],
                'skipped' => $result['skipped_count'],
                'failed' => $result['failed_count'],
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Import failed', [
                'type' => $type,
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Download CSV template.
     */
    public function downloadTemplate(string $type)
    {
        $templates = $this->getTemplates();

        if (!isset($templates[$type])) {
            throw new \InvalidArgumentException("Invalid template type: {$type}");
        }

        return response()->streamDownload(function () use ($templates, $type) {
            echo $templates[$type]['content'];
        }, $templates[$type]['filename'], [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get CSV template definitions.
     */
    protected function getTemplates(): array
    {
        return [
            'drivers' => [
                'filename' => 'drivers_template.csv',
                'content' => $this->getDriversTemplate(),
            ],
            'carriers' => [
                'filename' => 'carriers_template.csv',
                'content' => $this->getCarriersTemplate(),
            ],
            'user_carriers' => [
                'filename' => 'user_carriers_template.csv',
                'content' => $this->getUserCarriersTemplate(),
            ],
            'vehicles' => [
                'filename' => 'vehicles_template.csv',
                'content' => $this->getVehiclesTemplate(),
            ],
            'maintenance' => [
                'filename' => 'maintenance_template.csv',
                'content' => $this->getMaintenanceTemplate(),
            ],
            'repairs' => [
                'filename' => 'repairs_template.csv',
                'content' => $this->getRepairsTemplate(),
            ],
            'hos_entries' => [
                'filename' => 'hos_entries_template.csv',
                'content' => $this->getHosEntriesTemplate(),
            ],
            'driver_addresses' => [
                'filename' => 'driver_addresses_template.csv',
                'content' => $this->getDriverAddressesTemplate(),
            ],
            'driver_licenses' => [
                'filename' => 'driver_licenses_template.csv',
                'content' => $this->getDriverLicensesTemplate(),
            ],
            'driver_medical' => [
                'filename' => 'driver_medical_template.csv',
                'content' => $this->getDriverMedicalTemplate(),
            ],
            'driver_employment' => [
                'filename' => 'driver_employment_template.csv',
                'content' => $this->getDriverEmploymentTemplate(),
            ],
            'driver_training' => [
                'filename' => 'driver_training_template.csv',
                'content' => $this->getDriverTrainingTemplate(),
            ],
        ];
    }

    protected function getVehiclesTemplate(): string
    {
        return <<<CSV
unit_number,make,model,type,year,vin,registration_number,registration_state,registration_expiration_date,driver_type,status,fuel_type,gvwr,notes
TRUCK-001,Freightliner,Cascadia,tractor,2022,1FUJGLDR5CSBY1234,ABC123,TX,2026-12-31,company,active,diesel,80000,Main fleet vehicle
TRUCK-002,Kenworth,T680,tractor,2021,1XKYD49X4LJ123456,DEF456,CA,2026-06-30,owner_operator,active,diesel,75000,Owner operator unit
TRAILER-001,Great Dane,Everest,trailer,2020,1GRAA0626LK654321,GHI789,TX,2026-09-15,company,active,,53000,Dry van trailer
CSV;
    }

    protected function getMaintenanceTemplate(): string
    {
        return <<<CSV
vehicle_unit_number,vehicle_vin,service_date,next_service_date,service_tasks,vendor_mechanic,description,cost,odometer,status
TRUCK-001,,2025-01-15,2025-04-15,Oil Change,ABC Mechanics,Regular oil change and filter replacement,250.00,150000,completed
TRUCK-001,,2024-12-01,2025-06-01,Brake Inspection,ABC Mechanics,Full brake system inspection and pad replacement,850.00,145000,completed
,1FUJGLDR5CSBY1234,2024-10-15,2025-01-15,Tire Rotation,XYZ Tire Shop,Front and rear tire rotation,125.00,140000,completed
CSV;
    }

    protected function getRepairsTemplate(): string
    {
        return <<<CSV
vehicle_unit_number,vehicle_vin,repair_name,repair_date,cost,odometer,status,description,notes
TRUCK-001,,Tire Blowout Repair,2025-01-10,450.00,148500,completed,Rear driver-side tire blowout,Emergency roadside repair on I-10
TRUCK-002,,Alternator Replacement,2024-11-20,680.00,220000,completed,Alternator failed during trip,Replaced with OEM part
,1GRAA0626LK654321,Brake Line Repair,2024-09-05,320.00,85000,completed,Brake line leak detected during pre-trip,Replaced damaged brake line section
CSV;
    }

    protected function getHosEntriesTemplate(): string
    {
        return <<<CSV
driver_email,vehicle_unit_number,date,status,start_time,end_time,location
driver1@example.com,TRUCK-001,2025-01-15,on_duty_driving,2025-01-15 08:00,2025-01-15 12:00,Houston TX
driver1@example.com,TRUCK-001,2025-01-15,on_duty_not_driving,2025-01-15 12:00,2025-01-15 12:30,Dallas TX
driver1@example.com,TRUCK-001,2025-01-15,on_duty_driving,2025-01-15 12:30,2025-01-15 16:30,Oklahoma City OK
driver1@example.com,TRUCK-001,2025-01-15,off_duty,2025-01-15 16:30,2025-01-16 06:00,Oklahoma City OK
CSV;
    }

    protected function getDriversTemplate(): string
    {
        return <<<CSV
name,email,password,last_name,middle_name,phone,date_of_birth,status,driver_status,hos_cycle,applying_position,applying_location,eligible_to_work,can_speak_english,has_twic_card,twic_expiration_date,how_did_hear,expected_pay,application_status,social_security_number,hire_date,address_line1,address_line2,city,state,zip_code,lived_three_years,address_from_date,license_number,license_state,license_class,license_expiration_date,is_cdl,license_restrictions,medical_examiner_name,medical_examiner_registry_number,medical_card_expiration_date,medical_location,is_suspended,is_terminated,previous_employer_name,previous_employer_address,previous_employer_city,previous_employer_state,previous_employer_zip,previous_employer_phone,previous_employer_email,previous_employment_from,previous_employment_to,previous_positions_held,previous_subject_to_fmcsr,previous_safety_sensitive_function,previous_employment_reason_leaving,training_school_name,training_school_city,training_school_state,training_date_start,training_date_end,training_graduated,training_subject_to_safety_regulations,training_performed_safety_functions,training_skills
John,john.doe@example.com,SecurePass123!,Doe,Michael,555-123-4567,15/05/1990,active,active,70_8,company_driver,Houston TX,yes,yes,no,,indeed,2500.00,approved,123-45-6789,15/01/2020,123 Main Street,Apt 4B,Houston,TX,77001,yes,01/01/2020,DL123456789,TX,A,15/05/2027,yes,,Dr. Smith,1234567890,15/01/2026,Houston TX,no,no,ABC Trucking,123 Industrial Blvd,Houston,TX,77001,555-123-4567,hr@abctrucking.com,01/01/2018,31/12/2019,Driver,yes,yes,Better opportunity,Texas CDL Academy,Houston,TX,15/01/2015,15/03/2015,yes,yes,yes,"hazmat,tanker"
Jane,jane.smith@example.com,SecurePass123!,Smith,,555-987-6543,22/08/1985,active,active,60_7,owner_operator,Dallas TX,yes,yes,yes,15/06/2027,referral,3500.00,approved,987-65-4321,01/03/2019,789 Pine Road,,Austin,TX,78701,yes,01/03/2019,DL987654321,CA,A,22/08/2026,yes,L,Dr. Johnson,0987654321,01/03/2026,Dallas TX,no,no,XYZ Transport,456 Commerce St,Dallas,TX,75001,555-987-6543,jobs@xyztransport.com,01/06/2016,31/12/2017,Driver,yes,yes,Relocation,California Truck School,Los Angeles,CA,01/06/2014,31/08/2014,yes,yes,yes,passenger
Robert,robert.wilson@example.com,SecurePass123!,Wilson,James,555-456-7890,01/12/1992,active,pending,70_8,company_driver,Austin TX,yes,yes,no,,google,2800.00,pending,456-78-9123,01/06/2021,456 Oak Avenue,,Dallas,TX,75001,yes,01/06/2021,DL456789123,FL,B,01/12/2027,yes,E,Dr. Williams,5678901234,01/06/2026,Austin TX,no,no,Fast Freight,789 Logistics Way,Austin,TX,78701,555-456-7890,careers@fastfreight.com,01/03/2017,28/02/2019,Senior Driver,yes,yes,Company closed,Florida Driving Institute,Miami,FL,01/09/2018,30/11/2018,yes,no,no,
CSV;
    }

    protected function getCarriersTemplate(): string
    {
        return <<<CSV
name,address,headquarters,state,zipcode,ein_number,dot_number,mc_number,state_dot,ifta_account,membership,status,business_type,years_in_business,fleet_size
ABC Trucking LLC,123 Main Street,Houston TX,TX,75001,12-3456789,1234567,MC123456,TX123456,IFTA123,1,active,LLC,5,25
XYZ Transport Inc,456 Oak Avenue,Los Angeles CA,CA,90001,98-7654321,7654321,MC654321,CA654321,IFTA654,1,active,Corporation,10,50
Fast Freight Co,789 Pine Road,Miami FL,FL,33101,11-2233445,1122334,MC112233,,IFTA112,1,pending,LLC,2,10
CSV;
    }

    protected function getUserCarriersTemplate(): string
    {
        return <<<CSV
name,middle_name,last_name,email,password,phone,job_position,status,carrier_status
John,Michael,Smith,admin@abctrucking.com,SecurePass123!,555-111-2222,Administrator,active,active
Sarah,,Johnson,dispatch@abctrucking.com,SecurePass123!,555-333-4444,Dispatcher,active,active
Robert,Lee,Williams,safety@xyztrucking.com,SecurePass123!,555-555-6666,Safety Manager,active,active
CSV;
    }

    protected function getDriverAddressesTemplate(): string
    {
        return <<<CSV
driver_email,primary,address_line1,address_line2,city,state,zip_code,lived_three_years,from_date,to_date
john.doe@example.com,yes,123 Main Street,Apt 4B,Houston,TX,77001,yes,2020-01-01,
john.doe@example.com,no,456 Oak Avenue,,Dallas,TX,75001,no,2018-06-15,2019-12-31
jane.smith@example.com,yes,789 Pine Road,,Austin,TX,78701,yes,2019-03-01,
CSV;
    }

    protected function getDriverLicensesTemplate(): string
    {
        return <<<CSV
driver_email,license_number,state_of_issue,license_class,expiration_date,is_cdl,restrictions,status,is_primary
john.doe@example.com,DL123456789,TX,A,2027-05-15,yes,,active,yes
jane.smith@example.com,DL987654321,CA,A,2026-08-22,yes,L,active,yes
robert.wilson@example.com,DL456789123,FL,B,2027-12-01,yes,E,active,yes
CSV;
    }

    protected function getDriverMedicalTemplate(): string
    {
        return <<<CSV
driver_email,social_security_number,hire_date,location,medical_examiner_name,medical_examiner_registry_number,medical_card_expiration_date,is_suspended,is_terminated
john.doe@example.com,123-45-6789,2020-01-15,Houston TX,Dr. Smith,1234567890,2026-01-15,no,no
jane.smith@example.com,987-65-4321,2019-03-01,Dallas TX,Dr. Johnson,0987654321,2026-03-01,no,no
robert.wilson@example.com,456-78-9123,2021-06-01,Austin TX,Dr. Williams,5678901234,2026-06-01,no,no
CSV;
    }

    protected function getDriverEmploymentTemplate(): string
    {
        return <<<CSV
driver_email,company_name,company_address,company_city,company_state,company_zip,company_phone,company_email,employed_from,employed_to,positions_held,subject_to_fmcsr,safety_sensitive_function,reason_for_leaving
john.doe@example.com,ABC Trucking,123 Industrial Blvd,Houston,TX,77001,555-123-4567,hr@abctrucking.com,2018-01-01,2019-12-31,Driver,yes,yes,Better opportunity
john.doe@example.com,XYZ Transport,456 Commerce St,Dallas,TX,75001,555-987-6543,jobs@xyztransport.com,2016-06-01,2017-12-31,Driver,yes,yes,Relocation
jane.smith@example.com,Fast Freight,789 Logistics Way,Austin,TX,78701,555-456-7890,careers@fastfreight.com,2017-03-01,2019-02-28,Senior Driver,yes,yes,Company closed
CSV;
    }

    protected function getDriverTrainingTemplate(): string
    {
        return <<<CSV
driver_email,school_name,city,state,date_start,date_end,graduated,subject_to_safety_regulations,performed_safety_functions,training_skills
john.doe@example.com,Texas CDL Academy,Houston,TX,2015-01-15,2015-03-15,yes,yes,yes,"hazmat,tanker,doubles"
jane.smith@example.com,California Truck School,Los Angeles,CA,2014-06-01,2014-08-31,yes,yes,yes,"passenger,school_bus"
robert.wilson@example.com,Florida Driving Institute,Miami,FL,2018-09-01,2018-11-30,yes,no,no,
CSV;
    }

    /**
     * Store uploaded file temporarily.
     */
    public function storeTemporarily(UploadedFile $file): string
    {
        return $file->store('imports/temp');
    }

    /**
     * Delete temporary file.
     */
    public function deleteTemporaryFile(string $path): void
    {
        if (Storage::exists($path)) {
            Storage::delete($path);
        }
    }
}
