<?php

namespace App\Services;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DotPolicyPdfService
{
    /**
     * Generate a filled DOT Drug & Alcohol Policy PDF.
     * Phase 1: Carrier-only (during Policy step) — driver fields blank
     * Phase 2: Carrier + Driver + Signature (during CertificationStep)
     *
     * @param Carrier $carrier
     * @param UserDriverDetail|null $driverDetail
     * @param string|null $signatureBase64  Base64 signature image from CertificationStep
     * @return string  Path to generated PDF
     */
    public function generate(
        Carrier $carrier,
        ?UserDriverDetail $driverDetail = null,
        ?string $signatureBase64 = null
    ): string {
        $fieldValues = $this->buildFieldValues($carrier, $driverDetail);

        // Write JSON to temp file
        $jsonPath = tempnam(sys_get_temp_dir(), 'dot_policy_') . '.json';
        file_put_contents($jsonPath, json_encode($fieldValues, JSON_PRETTY_PRINT));

        // Output path
        $outputDir = storage_path('app/dot-policy-generated');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $suffix = $driverDetail ? $driverDetail->id : 'carrier_' . $carrier->id;
        $outputPath = $outputDir . '/dot_policy_' . $suffix . '_' . now()->format('Ymd_His') . '.pdf';

        // Prepare signature image if provided
        $signaturePath = $this->prepareSignatureFile($signatureBase64);

        // Determine signed date
        $signedDate = null;
        if ($driverDetail && $driverDetail->certification && $driverDetail->certification->signed_at) {
            $signedDate = $driverDetail->certification->signed_at->format('m/d/Y');
        } elseif ($signaturePath) {
            $signedDate = now()->format('m/d/Y');
        }

        // Run Python script
        $pythonCmd = $this->getPythonCommand();
        $scriptPath = storage_path('app/scripts/fill_dot_policy.py');
        $templatePath = storage_path('app/templates/DOT_DRUG_ALCOHOL_POLICY.pdf');

        $command = [
            $pythonCmd,
            $scriptPath,
            $templatePath,
            $jsonPath,
            $outputPath,
        ];

        // Pass signature path and date as optional args
        if ($signaturePath) {
            $command[] = $signaturePath;
            $command[] = $signedDate ?? now()->format('m/d/Y');
        }

        $result = Process::run($command);

        // Cleanup temp files
        if (file_exists($jsonPath)) {
            @unlink($jsonPath);
        }
        if ($signaturePath && file_exists($signaturePath)) {
            @unlink($signaturePath);
        }

        if (!$result->successful()) {
            Log::error('DOT Policy PDF generation failed', [
                'error' => $result->errorOutput(),
                'output' => $result->output(),
                'carrier_id' => $carrier->id,
                'driver_id' => $driverDetail?->id,
            ]);
            throw new \RuntimeException('Failed to generate DOT Policy PDF: ' . $result->errorOutput());
        }

        return $outputPath;
    }

    /**
     * Build the 19 field values for the Python script
     */
    private function buildFieldValues(Carrier $carrier, ?UserDriverDetail $driverDetail = null): array
    {
        $fields = [];

        $carrierName = $carrier->name ?? '';
        $cityState = trim(($carrier->headquarters ?? '') . ', ' . ($carrier->state ?? ''), ', ');
        $policyDate = now()->format('m/d/Y');

        // ── Page 2: DER Contact Info ──
        $fields[] = ['field_id' => 'CARRIER_NAME_p2', 'value' => $carrierName];
        $fields[] = ['field_id' => 'CARRIER_PHONE_p2', 'value' => '']; // phone ignored per user request

        // ── Page 3: Information & Revision Sheet ──
        $fields[] = ['field_id' => 'DATE_p3', 'value' => $policyDate];
        $fields[] = ['field_id' => 'DER_NAME_p3', 'value' => $carrierName];
        $fields[] = ['field_id' => 'COMPANY_NAME_p3', 'value' => $carrierName];
        $fields[] = ['field_id' => 'CARRIER_ADDRESS_p3', 'value' => $carrier->address ?? ''];
        $fields[] = ['field_id' => 'CARRIER_STATE_p3', 'value' => $cityState];
        $fields[] = ['field_id' => 'CARRIER_ZIPCODE_p3', 'value' => $carrier->zipcode ?? ''];

        // ── Page 13: Carrier name repeated 7 times + company name ──
        $fields[] = ['field_id' => 'CARRIER_NAME_title_p13', 'value' => $carrierName];
        $fields[] = ['field_id' => 'CARRIER_NAME_copy_p13', 'value' => $carrierName];
        $fields[] = ['field_id' => 'CARRIER_NAME_conditions_p13', 'value' => $carrierName];
        $fields[] = ['field_id' => 'CARRIER_NAME_understand_p13', 'value' => $carrierName];
        $fields[] = ['field_id' => 'CARRIER_NAME_file_p13', 'value' => $carrierName];
        $fields[] = ['field_id' => 'CARRIER_NAME_tests_p13', 'value' => $carrierName];
        $fields[] = ['field_id' => 'CARRIER_NAME_setforth_p13', 'value' => $carrierName];
        $fields[] = ['field_id' => 'COMPANY_NAME_sig_p13', 'value' => $carrierName];

        // ── Page 13: Driver fields (blank if no driver provided) ──
        $driverName = '';
        $signatureText = '';
        $signedAt = '';

        if ($driverDetail) {
            $user = $driverDetail->user;
            $driverName = $user ? ($user->name . ' ' . ($driverDetail->last_name ?? '')) : '';
            $signatureText = $driverName; // Use full name as signature text
            
            if ($driverDetail->certification && $driverDetail->certification->signed_at) {
                $signedAt = $driverDetail->certification->signed_at->format('m/d/Y');
            }
        }

        $fields[] = ['field_id' => 'DRIVER_NAME_p13', 'value' => $driverName];
        $fields[] = ['field_id' => 'SIGNATURE_p13', 'value' => $signatureText];
        $fields[] = ['field_id' => 'DATE_CREATE_p13', 'value' => $signedAt];

        return $fields;
    }

    /**
     * Convert base64 signature to a temporary PNG file
     */
    private function prepareSignatureFile(?string $signature): ?string
    {
        if (empty($signature) || !str_starts_with($signature, 'data:image')) {
            return null;
        }

        try {
            $parts = explode(',', $signature, 2);
            if (count($parts) !== 2) {
                return null;
            }

            $data = base64_decode($parts[1]);
            if ($data === false) {
                return null;
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'dotsig_') . '.png';
            file_put_contents($tempFile, $data);

            return $tempFile;
        } catch (\Exception $e) {
            Log::warning('Failed to prepare DOT policy signature file', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get the Python command based on OS
     */
    private function getPythonCommand(): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'python';
        }
        return 'python3';
    }
}
