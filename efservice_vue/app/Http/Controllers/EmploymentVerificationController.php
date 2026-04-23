<?php

namespace App\Http\Controllers;

use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\EmploymentVerificationToken;
use App\Models\Admin\Driver\MasterCompany;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class EmploymentVerificationController extends Controller
{
    public function showVerificationForm(string $token)
    {
        $verification = EmploymentVerificationToken::where('token', $token)->first();

        if (!$verification) {
            return Inertia::render('employment-verification/Error');
        }

        if ($verification->expires_at <= now()) {
            return Inertia::render('employment-verification/Expired');
        }

        if ($verification->verified_at !== null) {
            return Inertia::render('employment-verification/ThankYou');
        }

        $company = DriverEmploymentCompany::find($verification->employment_company_id);
        $driver  = UserDriverDetail::with('user')->find($verification->driver_id);

        if (!$company || !$driver) {
            return Inertia::render('employment-verification/Error');
        }

        $masterCompany = $company->master_company_id
            ? MasterCompany::find($company->master_company_id)
            : null;

        $medical = DriverMedicalQualification::where('user_driver_detail_id', $driver->id)->first();
        $ssnLast4 = $medical?->social_security_number
            ? '•••-••-' . substr($medical->social_security_number, -4)
            : null;

        return Inertia::render('employment-verification/Form', [
            'token'           => $token,
            'companyName'     => $masterCompany?->company_name ?? $company->company_name ?? 'Company',
            'driverName'      => trim(($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')),
            'ssnLast4'        => $ssnLast4,
            'employment'      => [
                'employed_from'             => $company->employed_from?->format('m/d/Y'),
                'employed_to'               => $company->employed_to?->format('m/d/Y'),
                'positions_held'            => $company->positions_held,
                'reason_for_leaving'        => $company->reason_for_leaving,
                'subject_to_fmcsr'          => (bool) $company->subject_to_fmcsr,
                'safety_sensitive_function' => (bool) $company->safety_sensitive_function,
            ],
        ]);
    }

    public function processVerification(Request $request, string $token)
    {
        $verification = EmploymentVerificationToken::where('token', $token)->first();

        if (!$verification) {
            return redirect()->route('employment-verification.error');
        }

        if ($verification->expires_at < now()) {
            return redirect()->route('employment-verification.expired');
        }

        $validated = $request->validate([
            'verification_status'    => ['required', 'in:verified,rejected'],
            'verification_notes'     => ['nullable', 'string'],
            'verification_by'        => ['required', 'string', 'max:255'],
            'signature'              => ['required', 'string'],
            'employment_confirmed'   => ['required'],
            'dates_confirmed'        => ['required', 'in:0,1'],
            'correct_dates'          => ['nullable', 'string'],
            'drove_commercial'       => ['required', 'in:0,1'],
            'safe_driver'            => ['required', 'in:0,1'],
            'unsafe_driver_details'  => ['nullable', 'string'],
            'had_accidents'          => ['required', 'in:0,1'],
            'accidents_details'      => ['nullable', 'string'],
            'reason_confirmed'       => ['required', 'in:0,1'],
            'different_reason'       => ['nullable', 'string'],
            'positive_drug_test'     => ['required', 'in:0,1'],
            'drug_test_details'      => ['nullable', 'string'],
            'positive_alcohol_test'  => ['required', 'in:0,1'],
            'alcohol_test_details'   => ['nullable', 'string'],
            'refused_test'           => ['required', 'in:0,1'],
            'refused_test_details'   => ['nullable', 'string'],
            'completed_rehab'        => ['required', 'in:0,1,2'],
            'other_violations'       => ['required', 'in:0,1'],
            'violation_details'      => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($verification, $validated, $token) {
                $verification->update([
                    'verified'            => true,
                    'verified_at'         => now(),
                    'verification_status' => $validated['verification_status'],
                    'verification_notes'  => $validated['verification_notes'] ?? null,
                    'verification_by'     => $validated['verification_by'],
                ]);

                $company = DriverEmploymentCompany::find($verification->employment_company_id);
                $driver  = UserDriverDetail::with('user')->find($verification->driver_id);

                if ($company) {
                    $safetyData = array_merge($validated, ['verified_at' => now()->toDateTimeString()]);

                    $company->update([
                        'verification_status'     => $validated['verification_status'],
                        'employment_confirmed'     => $validated['employment_confirmed'],
                        'safety_performance_data' => json_encode($safetyData),
                    ]);

                    // Generate PDF and save signature if driver exists
                    if ($driver && !empty($validated['signature'])) {
                        $this->generateVerificationPdf($verification, $company, $driver, $validated);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('Employment verification failed', ['token' => $token, 'error' => $e->getMessage()]);
            return redirect()->route('employment-verification.error');
        }

        return redirect()->route('employment-verification.thank-you');
    }

    public function thankYou() { return Inertia::render('employment-verification/ThankYou'); }
    public function expired()  { return Inertia::render('employment-verification/Expired'); }
    public function error()    { return Inertia::render('employment-verification/Error'); }

    protected function generateVerificationPdf(
        EmploymentVerificationToken $verification,
        DriverEmploymentCompany $company,
        $driver,
        array $validated
    ): void {
        try {
            $driverId = $driver->id;

            // ── Save signature image ──────────────────────────────────────────
            $signatureData = $validated['signature'];
            $imageBase64   = explode(',', explode(';', $signatureData)[1])[0];
            $imageContent  = base64_decode($imageBase64);

            $sigDir  = "driver/{$driverId}/certification";
            $sigName = 'employment_verification_signature_' . time() . '.png';
            Storage::disk('public')->makeDirectory($sigDir);
            Storage::disk('public')->put("{$sigDir}/{$sigName}", $imageContent);
            $sigRelPath = "{$sigDir}/{$sigName}";

            // ── SSN for PDF ───────────────────────────────────────────────────
            $medical  = DriverMedicalQualification::where('user_driver_detail_id', $driverId)->first();
            $ssn      = $medical?->social_security_number;

            // ── Build safety performance map ──────────────────────────────────
            $safetyData = [
                'dates_confirmed'       => $validated['dates_confirmed'],
                'correct_dates'         => $validated['correct_dates'] ?? null,
                'drove_commercial'      => $validated['drove_commercial'],
                'safe_driver'           => $validated['safe_driver'],
                'unsafe_driver_details' => $validated['unsafe_driver_details'] ?? null,
                'had_accidents'         => $validated['had_accidents'],
                'accidents_details'     => $validated['accidents_details'] ?? null,
                'reason_confirmed'      => $validated['reason_confirmed'],
                'different_reason'      => $validated['different_reason'] ?? null,
                'positive_drug_test'    => $validated['positive_drug_test'],
                'drug_test_details'     => $validated['drug_test_details'] ?? null,
                'positive_alcohol_test' => $validated['positive_alcohol_test'],
                'alcohol_test_details'  => $validated['alcohol_test_details'] ?? null,
                'refused_test'          => $validated['refused_test'],
                'refused_test_details'  => $validated['refused_test_details'] ?? null,
                'completed_rehab'       => $validated['completed_rehab'],
                'other_violations'      => $validated['other_violations'],
                'violation_details'     => $validated['violation_details'] ?? null,
            ];

            // ── Generate PDF ──────────────────────────────────────────────────
            $pdf = Pdf::loadView('pdf.driver.employment-verification', [
                'verification'        => $verification,
                'employmentCompany'   => $company,
                'driver'              => $driver,
                'signature'           => $signatureData,
                'safetyPerformanceData' => $safetyData,
                'ssn'                 => $ssn,
                'verification_by'     => $validated['verification_by'],
            ]);

            $companySlug = preg_replace('/[^a-zA-Z0-9]/', '_', $company->company_name ?? 'company');
            $pdfName     = "employment_verification_{$companySlug}_" . time() . '.pdf';
            $pdfDir      = "driver/{$driverId}";
            Storage::disk('public')->makeDirectory($pdfDir);
            $pdfRelPath  = "{$pdfDir}/{$pdfName}";
            Storage::disk('public')->put($pdfRelPath, $pdf->output());

            // ── Update token with file paths ──────────────────────────────────
            $verification->update([
                'signature_path' => $sigRelPath,
                'document_path'  => $pdfRelPath,
            ]);

            Log::info('Employment verification PDF generated', [
                'driver_id'  => $driverId,
                'pdf_path'   => $pdfRelPath,
                'sig_path'   => $sigRelPath,
            ]);

        } catch (\Throwable $e) {
            // Log but don't fail the transaction — verification is already saved
            Log::error('PDF generation failed for employment verification', [
                'error'         => $e->getMessage(),
                'driver_id'     => $driver->id ?? null,
                'verification'  => $verification->token,
            ]);
        }
    }
}
