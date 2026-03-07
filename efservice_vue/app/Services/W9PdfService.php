<?php

namespace App\Services;

use App\Models\Admin\Driver\DriverW9Form;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class W9PdfService
{
    private const TAX_MAP = [
        'individual'    => 0, // c1_1[0] → /1
        'c_corporation' => 1, // c1_1[1] → /2
        's_corporation' => 2, // c1_1[2] → /3
        'partnership'   => 3, // c1_1[3] → /4
        'trust_estate'  => 4, // c1_1[4] → /5
        'llc'           => 5, // c1_1[5] → /6
        'other'         => 6, // c1_1[6] → /7
    ];

    /**
     * Generate a filled W-9 PDF from a DriverW9Form model.
     * @param DriverW9Form $w9
     * @param string|null $signatureBase64 Optional base64 signature (from CertificationStep)
     */
    public function generate(DriverW9Form $w9, ?string $signatureBase64 = null): string
    {
        $fieldValues = $this->buildFieldValues($w9);

        // Write JSON to temp file
        $jsonPath = tempnam(sys_get_temp_dir(), 'w9_') . '.json';
        file_put_contents($jsonPath, json_encode($fieldValues, JSON_PRETTY_PRINT));

        // Output path
        $outputDir = storage_path('app/w9-generated');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $outputPath = $outputDir . '/w9_' . $w9->id . '_' . now()->format('Ymd_His') . '.pdf';

        // Prepare signature image if provided
        $signaturePath = $this->prepareSignatureFile($signatureBase64);

        // Run Python script
        $pythonCmd = $this->getPythonCommand();
        $scriptPath = storage_path('app/scripts/fill_w9.py');
        $templatePath = storage_path('app/templates/fw9.pdf');

        $command = [
            $pythonCmd,
            $scriptPath,
            $templatePath,
            $jsonPath,
            $outputPath,
        ];

        // Pass signature path as optional 4th argument and date as 5th
        if ($signaturePath) {
            $command[] = $signaturePath;
            $command[] = $w9->signed_date ? $w9->signed_date->format('m/d/Y') : now()->format('m/d/Y');
        }

        $result = Process::run($command);

        // Cleanup temp files
        if (file_exists($jsonPath)) {
            unlink($jsonPath);
        }
        if ($signaturePath && file_exists($signaturePath)) {
            @unlink($signaturePath);
        }

        if (!$result->successful()) {
            Log::error('W9 PDF generation failed', [
                'error' => $result->errorOutput(),
                'output' => $result->output(),
                'w9_id' => $w9->id,
            ]);
            throw new \RuntimeException('Failed to generate W-9 PDF: ' . $result->errorOutput());
        }

        return $outputPath;
    }

    /**
     * Build the field values array for the Python script
     */
    private function buildFieldValues(DriverW9Form $w9): array
    {
        $fields = [];

        // Line 1 - Name
        $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_01[0]', 'value' => $w9->name];

        // Line 2 - Business name
        $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_02[0]', 'value' => $w9->business_name ?? ''];

        // Line 3a - Tax classification (radio)
        if (isset(self::TAX_MAP[$w9->tax_classification])) {
            $index = self::TAX_MAP[$w9->tax_classification];
            $fields[] = [
                'field_id' => "topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].c1_1[{$index}]",
                'value' => '/' . ($index + 1),
            ];
        }

        // LLC classification letter (only if LLC)
        if ($w9->tax_classification === 'llc' && $w9->llc_classification) {
            $fields[] = [
                'field_id' => 'topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].f1_03[0]',
                'value' => $w9->llc_classification,
            ];
        }

        // Other classification text (only if Other)
        if ($w9->tax_classification === 'other' && $w9->other_classification) {
            $fields[] = [
                'field_id' => 'topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].f1_04[0]',
                'value' => $w9->other_classification,
            ];
        }

        // Line 3b - Foreign partners checkbox
        if ($w9->has_foreign_partners) {
            $fields[] = [
                'field_id' => 'topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].c1_2[0]',
                'value' => '/1',
            ];
        }

        // Line 4 - Exempt payee code
        $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_05[0]', 'value' => $w9->exempt_payee_code ?? ''];

        // Line 4 - FATCA exemption code
        $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_06[0]', 'value' => $w9->fatca_exemption_code ?? ''];

        // Line 5 - Address
        $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].Address_ReadOrder[0].f1_07[0]', 'value' => $w9->address];

        // Line 6 - City, state, ZIP
        $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].Address_ReadOrder[0].f1_08[0]', 'value' => $w9->city_state_zip];

        // Line 7 - Account numbers
        $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_10[0]', 'value' => $w9->account_numbers ?? ''];

        // Part I - TIN
        if ($w9->tin_type === 'ssn') {
            $parts = $w9->getSsnParts();
            $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_11[0]', 'value' => $parts[0]];
            $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_12[0]', 'value' => $parts[1]];
            $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_13[0]', 'value' => $parts[2]];
        } elseif ($w9->tin_type === 'ein') {
            $parts = $w9->getEinParts();
            $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_14[0]', 'value' => $parts[0]];
            $fields[] = ['field_id' => 'topmostSubform[0].Page1[0].f1_15[0]', 'value' => $parts[1]];
        }

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

            $tempFile = tempnam(sys_get_temp_dir(), 'w9sig_') . '.png';
            file_put_contents($tempFile, $data);

            return $tempFile;
        } catch (\Exception $e) {
            Log::warning('Failed to prepare W-9 signature file', ['error' => $e->getMessage()]);
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
