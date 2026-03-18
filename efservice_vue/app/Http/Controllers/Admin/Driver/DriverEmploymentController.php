<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Mail\EmploymentVerification;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\EmploymentVerificationToken;
use App\Models\Admin\Driver\MasterCompany;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        abort_unless($company->user_driver_detail_id === $driver->id, 404);

        $masterCompany = $company->masterCompany;
        $emailToUse    = $masterCompany?->email ?: $company->email;

        if (empty($emailToUse) || !filter_var($emailToUse, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'No valid email address for this company.'], 422);
        }

        try {
            $token = Str::random(64);

            EmploymentVerificationToken::create([
                'driver_id'             => $driver->id,
                'employment_company_id' => $company->id,
                'token'                 => $token,
                'email'                 => $emailToUse,
                'expires_at'            => now()->addDays(30),
            ]);

            $driverName     = $driver->user?->name ?? 'Driver';
            $employmentData = [
                'company_name'           => $masterCompany?->company_name ?? '',
                'contact_email'          => $emailToUse,
                'employed_from'          => $company->employed_from?->format('m/d/Y') ?? 'Not specified',
                'employed_to'            => $company->employed_to?->format('m/d/Y') ?? 'Not specified',
                'positions_held'         => $company->positions_held ?? 'Not specified',
                'reason_for_leaving'     => $company->reason_for_leaving ?? 'Not specified',
                'subject_to_fmcsr'       => $company->subject_to_fmcsr ?? false,
                'safety_sensitive_function' => $company->safety_sensitive_function ?? false,
            ];

            Mail::to($emailToUse)->send(new EmploymentVerification(
                $masterCompany?->company_name ?? '',
                $driverName,
                $employmentData,
                $token,
                $driver->id,
                $company->id,
            ));

            $company->update(['email_sent' => true]);

            return response()->json(['message' => 'Verification email sent.', 'email_sent' => true]);
        } catch (\Exception $e) {
            Log::error('Employment verification email failed', ['error' => $e->getMessage(), 'company_id' => $company->id]);
            return response()->json(['message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    /** Resend verification email (same as send, creates a new token) */
    public function resendEmail(Request $request, UserDriverDetail $driver, DriverEmploymentCompany $company)
    {
        return $this->sendEmail($request, $driver, $company);
    }

    /** Admin override: mark email as sent or unsent */
    public function markEmailStatus(Request $request, UserDriverDetail $driver, DriverEmploymentCompany $company)
    {
        abort_unless($company->user_driver_detail_id === $driver->id, 404);

        $request->validate(['sent' => 'required|boolean']);
        $company->update(['email_sent' => $request->boolean('sent')]);

        return response()->json(['message' => 'Email status updated.', 'email_sent' => $company->email_sent]);
    }
}
