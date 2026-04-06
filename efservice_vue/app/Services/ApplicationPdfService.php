<?php

namespace App\Services;

use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Generates all driver application PDFs on step 14 (certification).
 * Mirrors the logic from efservices' DriverCertificationStep::generateApplicationPDFs().
 */
class ApplicationPdfService
{
    /** Individual step PDFs (view => filename). */
    private const STEP_PDFS = [
        ['view' => 'pdf.driver.general',       'filename' => 'general_information.pdf',     'title' => 'General Information'],
        ['view' => 'pdf.driver.address',        'filename' => 'address_information.pdf',     'title' => 'Address Information'],
        ['view' => 'pdf.driver.application',    'filename' => 'application_details.pdf',     'title' => 'Application Details'],
        ['view' => 'pdf.driver.licenses',       'filename' => 'drivers_licenses.pdf',        'title' => 'Drivers Licenses'],
        ['view' => 'pdf.driver.medical',        'filename' => 'calificacion_medica.pdf',     'title' => 'Medical Qualification'],
        ['view' => 'pdf.driver.training',       'filename' => 'training_schools.pdf',        'title' => 'Training Schools'],
        ['view' => 'pdf.driver.traffic',        'filename' => 'traffic_violations.pdf',      'title' => 'Traffic Violations'],
        ['view' => 'pdf.driver.accident',       'filename' => 'accident_record.pdf',         'title' => 'Accident Record'],
        ['view' => 'pdf.driver.fmcsr',          'filename' => 'fmcsr_requirements.pdf',      'title' => 'FMCSR Requirements'],
        ['view' => 'pdf.driver.employment',     'filename' => 'employment_history.pdf',      'title' => 'Employment History'],
        ['view' => 'pdf.driver.certification',  'filename' => 'certification.pdf',           'title' => 'Certification'],
    ];

    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    /**
     * Generate all application PDFs for a driver.
     *
     * @param UserDriverDetail $driver
     * @param string|null      $signature  base64 data URI or file path
     */
    public function generate(UserDriverDetail $driver, ?string $signature = null): void
    {
        if (empty($signature)) {
            Log::warning('ApplicationPdfService: no signature provided, skipping PDF generation', [
                'driver_id' => $driver->id,
            ]);
            return;
        }

        $signaturePath = $this->prepareSignature($signature);
        if (! $signaturePath) {
            Log::error('ApplicationPdfService: could not prepare signature file', ['driver_id' => $driver->id]);
            return;
        }

        // Eager-load all relations needed by every Blade template
        $driver->load([
            'user',
            'carrier',
            'application.addresses',
            'application.details',
            'licenses.endorsements',
            'medicalQualification',
            'experiences',
            'trainingSchools',
            'courses',
            'trafficConvictions',
            'accidents',
            'fmcsrData',
            'employmentCompanies.company',
            'unemploymentPeriods',
            'relatedEmployments',
            'criminalHistory',
            'certification',
            'vehicleAssignments.vehicle',
        ]);

        // Ensure storage directories exist
        $driverPath = 'driver/' . $driver->id;
        $appSubPath = $driverPath . '/driver_applications';
        Storage::disk('public')->makeDirectory($driverPath);
        Storage::disk('public')->makeDirectory($appSubPath);

        $effectiveDates   = $this->getEffectiveDates($driver);
        $formattedDates   = $this->buildFormattedDates($effectiveDates);
        $useCustomDates   = $effectiveDates['show_custom_created_at'];

        // ── Individual step PDFs ───────────────────────────────────────────────
        foreach (self::STEP_PDFS as $step) {
            if (! view()->exists($step['view'])) {
                continue;
            }

            try {
                $data = array_merge($this->baseData($driver, $signaturePath, $effectiveDates, $formattedDates, $useCustomDates), [
                    'title' => $step['title'],
                ]);

                $content = App::make('dompdf.wrapper')->loadView($step['view'], $data)->output();
                Storage::disk('public')->put($appSubPath . '/' . $step['filename'], $content);

                Log::info('ApplicationPdfService: individual PDF generated', [
                    'driver_id' => $driver->id,
                    'file'      => $step['filename'],
                ]);
            } catch (\Exception $e) {
                Log::error('ApplicationPdfService: error generating individual PDF', [
                    'driver_id' => $driver->id,
                    'file'      => $step['filename'],
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        // ── Combined / complete application PDF ───────────────────────────────
        $this->generateCombinedPdf($driver, $signaturePath, $effectiveDates, $formattedDates, $useCustomDates);

        // ── Criminal History PDF ──────────────────────────────────────────────
        $this->generateCriminalHistoryPdf($driver, $signaturePath, $effectiveDates, $formattedDates, $useCustomDates);

        // ── Driver-type specific documents ────────────────────────────────────
        $activeAssignment = $driver->vehicleAssignments
            ->whereIn('status', ['active', 'pending'])
            ->sortByDesc('created_at')
            ->first();

        if ($activeAssignment) {
            match ($activeAssignment->driver_type ?? '') {
                'owner_operator' => $this->generateLeaseAgreementOwnerPdf($driver, $signaturePath),
                default          => null,
            };
        }

        // Cleanup signature temp file
        if (str_contains((string) $signaturePath, 'sig_') && file_exists($signaturePath)) {
            @unlink($signaturePath);
        }
    }

    // -------------------------------------------------------------------------
    // Combined application PDF
    // -------------------------------------------------------------------------

    private function generateCombinedPdf(UserDriverDetail $driver, string $signaturePath, array $effectiveDates, array $formattedDates, bool $useCustomDates): void
    {
        if (! view()->exists('pdf.driver.complete_application')) {
            return;
        }

        try {
            $data = array_merge($this->baseData($driver, $signaturePath, $effectiveDates, $formattedDates, $useCustomDates), [
                'signature'       => $signaturePath,
                'fullName'        => $this->fullName($driver),
                'criminalHistory' => $this->criminalHistoryArray($driver),
                'carrier'         => $driver->carrier,
            ]);

            $content  = App::make('dompdf.wrapper')->loadView('pdf.driver.complete_application', $data)->output();
            $filePath = 'driver/' . $driver->id . '/complete_application.pdf';
            Storage::disk('public')->put($filePath, $content);

            // Attach to DriverApplication media collection
            if ($driver->application) {
                $tempPath = tempnam(sys_get_temp_dir(), 'complete_app_') . '.pdf';
                file_put_contents($tempPath, $content);

                $driver->application->clearMediaCollection('application_pdf');
                $driver->application->addMedia($tempPath)->toMediaCollection('application_pdf');

                if (Schema::hasColumn('driver_applications', 'pdf_path')) {
                    $driver->application->update(['pdf_path' => $filePath]);
                }
            }

            Log::info('ApplicationPdfService: complete_application.pdf generated', ['driver_id' => $driver->id]);
        } catch (\Exception $e) {
            Log::error('ApplicationPdfService: error generating combined PDF', [
                'driver_id' => $driver->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Criminal History PDF
    // -------------------------------------------------------------------------

    private function generateCriminalHistoryPdf(UserDriverDetail $driver, string $signaturePath, array $effectiveDates, array $formattedDates, bool $useCustomDates): void
    {
        if (! view()->exists('pdf.driver.criminal_history')) {
            return;
        }

        try {
            $data = array_merge($this->baseData($driver, $signaturePath, $effectiveDates, $formattedDates, $useCustomDates), [
                'signature'       => $signaturePath,
                'fullName'        => $this->fullName($driver),
                'criminalHistory' => $this->criminalHistoryArray($driver),
                'carrier'         => $driver->carrier,
            ]);

            $appSubPath = 'driver/' . $driver->id . '/driver_applications';
            $filePath   = $appSubPath . '/criminal_history_investigation.pdf';
            $content    = App::make('dompdf.wrapper')->loadView('pdf.driver.criminal_history', $data)->output();

            Storage::disk('public')->put($filePath, $content);

            // Attach to media collection if it exists on the application
            if ($driver->application) {
                $tempPath = tempnam(sys_get_temp_dir(), 'crim_hist_') . '.pdf';
                file_put_contents($tempPath, $content);

                try {
                    $driver->application->clearMediaCollection('criminal_history_pdf');
                    $driver->application->addMedia($tempPath)->toMediaCollection('criminal_history_pdf');
                } catch (\Exception) {
                    // Collection may not be registered — keep the disk copy
                    @unlink($tempPath);
                }
            }

            Log::info('ApplicationPdfService: criminal_history_investigation.pdf generated', ['driver_id' => $driver->id]);
        } catch (\Exception $e) {
            Log::error('ApplicationPdfService: error generating criminal history PDF', [
                'driver_id' => $driver->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Lease Agreement (owner-operator)
    // -------------------------------------------------------------------------

    private function generateLeaseAgreementOwnerPdf(UserDriverDetail $driver, string $signaturePath): void
    {
        if (! view()->exists('pdfs.lease-agreement-owner')) {
            return;
        }

        try {
            $data    = ['userDriverDetail' => $driver, 'signaturePath' => $signaturePath, 'carrier' => $driver->carrier];
            $content = App::make('dompdf.wrapper')->loadView('pdfs.lease-agreement-owner', $data)->output();

            $filePath = 'driver/' . $driver->id . '/vehicle_verifications/lease_agreement_owner.pdf';
            Storage::disk('public')->makeDirectory(dirname($filePath));
            Storage::disk('public')->put($filePath, $content);

            if ($driver->application) {
                $tempPath = tempnam(sys_get_temp_dir(), 'lease_') . '.pdf';
                file_put_contents($tempPath, $content);
                $driver->application->addMedia($tempPath)->toMediaCollection('application_pdf');
            }

            Log::info('ApplicationPdfService: lease_agreement_owner.pdf generated', ['driver_id' => $driver->id]);
        } catch (\Exception $e) {
            Log::error('ApplicationPdfService: error generating lease agreement PDF', [
                'driver_id' => $driver->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function prepareSignature(string $signature): ?string
    {
        if (file_exists($signature)) {
            return $signature;
        }

        if (str_starts_with($signature, 'data:image')) {
            $data     = base64_decode(explode(',', $signature)[1]);
            $tempFile = storage_path('app/temp/sig_' . uniqid() . '.png');

            if (! file_exists(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }

            file_put_contents($tempFile, $data);
            return $tempFile;
        }

        return null;
    }

    private function getEffectiveDates(UserDriverDetail $driver): array
    {
        $showCustom  = $driver->use_custom_dates && $driver->custom_created_at;
        $customDate  = $showCustom ? $driver->custom_created_at : null;

        return [
            'created_at'             => $driver->created_at,
            'updated_at'             => $driver->updated_at ?? now(),
            'custom_created_at'      => $customDate,
            'show_created_at'        => true,
            'show_custom_created_at' => (bool) $showCustom,
        ];
    }

    private function buildFormattedDates(array $dates): array
    {
        $f = [
            'updated_at'      => $dates['updated_at']->format('m/d/Y'),
            'updated_at_long' => $dates['updated_at']->format('F j, Y'),
        ];

        if ($dates['show_created_at'] && $dates['created_at']) {
            $f['created_at']      = $dates['created_at']->format('m/d/Y');
            $f['created_at_long'] = $dates['created_at']->format('F j, Y');
        }

        if ($dates['show_custom_created_at'] && $dates['custom_created_at']) {
            $f['custom_created_at']      = Carbon::parse($dates['custom_created_at'])->format('m/d/Y');
            $f['custom_created_at_long'] = Carbon::parse($dates['custom_created_at'])->format('F j, Y');
        }

        return $f;
    }

    private function baseData(UserDriverDetail $driver, string $signaturePath, array $effectiveDates, array $formattedDates, bool $useCustomDates): array
    {
        return [
            'userDriverDetail' => $driver,
            'signaturePath'    => $signaturePath,
            'date'             => now()->format('m/d/Y'),
            'created_at'       => $effectiveDates['created_at'],
            'updated_at'       => $effectiveDates['updated_at'],
            'custom_created_at'=> $effectiveDates['custom_created_at'],
            'formatted_dates'  => $formattedDates,
            'use_custom_dates' => $useCustomDates,
        ];
    }

    private function fullName(UserDriverDetail $driver): string
    {
        return trim(
            ($driver->user?->name ?? 'N/A') . ' ' .
            ($driver->middle_name ?? '') . ' ' .
            ($driver->last_name ?? 'N/A')
        );
    }

    private function criminalHistoryArray(UserDriverDetail $driver): ?array
    {
        if (! $driver->criminalHistory) {
            return null;
        }

        return [
            'has_criminal_charges'    => $driver->criminalHistory->has_criminal_charges ?? false,
            'has_felony_conviction'   => $driver->criminalHistory->has_felony_conviction ?? false,
            'has_minister_permit'     => $driver->criminalHistory->has_minister_permit ?? false,
            'fcra_consent'            => $driver->criminalHistory->fcra_consent ?? false,
            'background_info_consent' => $driver->criminalHistory->background_info_consent ?? false,
        ];
    }
}
