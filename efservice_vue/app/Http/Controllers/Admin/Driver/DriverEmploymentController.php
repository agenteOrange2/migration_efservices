<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Mail\EmploymentVerification;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\EmploymentVerificationToken;
use App\Models\Admin\Driver\MasterCompany;
use App\Models\UserDriverDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DriverEmploymentController extends Controller
{
    /** Search MasterCompany by name */
    public function searchCompanies(Request $request)
    {
        $term = $request->input('q', '');

        $companies = MasterCompany::when($term, fn($q) => $q->where('company_name', 'like', "%{$term}%"))
            ->orderBy('company_name')
            ->limit(20)
            ->get(['id', 'company_name', 'address', 'city', 'state', 'zip', 'phone', 'fax', 'contact', 'email']);

        return response()->json($companies);
    }

    /** Send first verification email to employer */
    public function sendEmail(Request $request, UserDriverDetail $driver, DriverEmploymentCompany $company)
    {
        abort_unless((int) $company->user_driver_detail_id === (int) $driver->id, 404);

        $masterCompany = $company->masterCompany;
        $emailToUse    = $masterCompany?->email ?: $company->email;

        if (empty($emailToUse) || !filter_var($emailToUse, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'No valid email address for this company.'], 422);
        }

        $companyName = $masterCompany?->company_name ?? '';
        $driverName  = trim(($driver->user?->name ?? '') . ' ' . ($driver->last_name ?? ''));

        try {
            [$token, $attemptNumber] = \Illuminate\Support\Facades\DB::transaction(function () use ($company, $driver, $emailToUse) {
                $attemptCount = EmploymentVerificationToken::where('employment_company_id', $company->id)
                    ->lockForUpdate()
                    ->count();

                if ($attemptCount >= config('employment_verification.max_attempts')) {
                    throw new \RuntimeException('Maximum verification attempts (' . config('employment_verification.max_attempts') . ') reached. No more emails can be sent.');
                }

                $token = Str::random(64);
                EmploymentVerificationToken::create([
                    'driver_id'             => $driver->id,
                    'employment_company_id' => $company->id,
                    'token'                 => $token,
                    'email'                 => $emailToUse,
                    'expires_at'            => now()->addDays(config('employment_verification.token_expiry_days')),
                ]);

                return [$token, $attemptCount + 1];
            });

            $employmentData = [
                'company_name'              => $companyName,
                'contact_email'             => $emailToUse,
                'employed_from'             => $company->employed_from?->format('m/d/Y') ?? 'Not specified',
                'employed_to'               => $company->employed_to?->format('m/d/Y') ?? 'Not specified',
                'positions_held'            => $company->positions_held ?? 'Not specified',
                'reason_for_leaving'        => $company->reason_for_leaving ?? 'Not specified',
                'subject_to_fmcsr'          => $company->subject_to_fmcsr ?? false,
                'safety_sensitive_function' => $company->safety_sensitive_function ?? false,
            ];

            try {
                Mail::to($emailToUse)->send(new EmploymentVerification(
                    $companyName,
                    $driverName,
                    $employmentData,
                    $token,
                    $driver->id,
                    $company->id,
                ));
            } catch (\Throwable $mailError) {
                EmploymentVerificationToken::where('token', $token)->delete();
                throw $mailError;
            }

            $company->update(['email_sent' => true]);

            $this->generateAttemptPdf($company, $driver, $companyName, $driverName, $token, $attemptNumber, $emailToUse);

            return response()->json([
                'message'        => "Verification email sent (Attempt #{$attemptNumber}/3).",
                'email_sent'     => true,
                'attempt_number' => $attemptNumber,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Employment verification email failed', ['error' => $e->getMessage(), 'company_id' => $company->id]);
            return response()->json(['message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    /** Resend verification email (creates a new token, same max-attempt enforcement) */
    public function resendEmail(Request $request, UserDriverDetail $driver, DriverEmploymentCompany $company)
    {
        return $this->sendEmail($request, $driver, $company);
    }

    private function generateAttemptPdf(
        DriverEmploymentCompany $company,
        UserDriverDetail $driver,
        string $companyName,
        string $driverName,
        string $token,
        int $attemptNumber,
        string $emailSentTo = ''
    ): void {
        try {
            $pdfData = [
                'attemptNumber'    => $attemptNumber,
                'attemptDate'      => now()->format('m/d/Y'),
                'attemptTime'      => now()->format('h:i:s A'),
                'emailSentTo'      => $emailSentTo ?: $company->email,
                'driverName'       => $driverName,
                'driverId'         => $driver->id,
                'companyName'      => $companyName,
                'companyEmail'     => $company->email,
                'employedFrom'     => $company->employed_from?->format('m/d/Y') ?? 'Not specified',
                'employedTo'       => $company->employed_to?->format('m/d/Y') ?? 'Not specified',
                'positionsHeld'    => $company->positions_held ?? 'Not specified',
                'reasonForLeaving' => $company->reason_for_leaving ?? 'Not specified',
                'token'            => $token,
                'expiresAt'        => now()->addDays(config('employment_verification.token_expiry_days'))->format('m/d/Y h:i A'),
                'generatedAt'      => now()->format('m/d/Y h:i:s A'),
            ];

            $pdf = Pdf::loadView('pdf.driver.employment-verification-attempt', $pdfData);

            $companySlug = preg_replace('/[^a-zA-Z0-9]/', '_', $companyName);
            $pdfFileName = "employment_verification_attempt_{$attemptNumber}_{$companySlug}_" . time() . '.pdf';
            $tempPath    = storage_path('app/temp/' . $pdfFileName);

            Storage::disk('local')->makeDirectory('temp');
            $pdf->save($tempPath);

            try {
                $driver->addMedia($tempPath)
                    ->usingFileName($pdfFileName)
                    ->usingName("Employment Verification Attempt #{$attemptNumber} - {$companyName}")
                    ->withCustomProperties([
                        'attempt_number' => $attemptNumber,
                        'company_name'   => $companyName,
                        'company_id'     => $company->id,
                        'email_sent_to'  => $company->email,
                        'sent_at'        => now()->toDateTimeString(),
                    ])
                    ->toMediaCollection('employment_verification_attempts');
            } finally {
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            }

            Log::info('Employment verification attempt PDF generated via wizard', [
                'driver_id'      => $driver->id,
                'company_id'     => $company->id,
                'attempt_number' => $attemptNumber,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to generate employment verification attempt PDF via wizard', [
                'error'      => $e->getMessage(),
                'company_id' => $company->id,
                'driver_id'  => $driver->id,
            ]);
        }
    }

    /** Admin override: mark email as sent or unsent */
    public function markEmailStatus(Request $request, UserDriverDetail $driver, DriverEmploymentCompany $company)
    {
        abort_unless((int) $company->user_driver_detail_id === (int) $driver->id, 404);

        $request->validate(['sent' => 'required|boolean']);
        $company->update(['email_sent' => $request->boolean('sent')]);

        return response()->json(['message' => 'Email status updated.', 'email_sent' => $company->email_sent]);
    }
}
