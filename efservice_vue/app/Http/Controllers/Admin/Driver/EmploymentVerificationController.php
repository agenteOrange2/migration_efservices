<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Mail\EmploymentVerification;
use App\Models\Carrier;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\EmploymentVerificationToken;
use App\Models\Admin\Driver\MasterCompany;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class EmploymentVerificationController extends Controller
{
    public function create()
    {
        $carriers = Carrier::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ]);

        $drivers = UserDriverDetail::query()
            ->with(['user', 'carrier'])
            ->orderByDesc('id')
            ->get()
            ->map(fn ($driver) => [
                'id' => $driver->id,
                'carrier_id' => $driver->carrier_id,
                'carrier_name' => $driver->carrier?->name,
                'name' => trim(($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')),
                'email' => $driver->user->email,
            ]);

        return Inertia::render('admin/drivers/employment-verification/Create', [
            'carriers' => $carriers,
            'drivers' => $drivers,
        ]);
    }

    public function index(Request $request)
    {
        $query = DriverEmploymentCompany::query()
            ->with(['userDriverDetail.user', 'masterCompany', 'verificationTokens'])
            ->where('email_sent', true);

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->whereNull('verification_status');
            } else {
                $query->where('verification_status', $request->status);
            }
        }

        if ($request->filled('driver')) {
            $query->where('user_driver_detail_id', $request->driver);
        }

        $verifications = $query
            ->select('driver_employment_companies.*')
            ->addSelect(['latest_token_date' => EmploymentVerificationToken::select('created_at')
                ->whereColumn('employment_company_id', 'driver_employment_companies.id')
                ->orderByDesc('created_at')
                ->limit(1),
            ])
            ->orderByDesc('latest_token_date')
            ->orderByDesc('driver_employment_companies.updated_at')
            ->paginate(15)
            ->withQueryString();

        $verifications->getCollection()->transform(function ($item) {
            $driver = $item->userDriverDetail;
            $company = $item->masterCompany;

            return [
                'id'                  => $item->id,
                'driver_id'           => $item->user_driver_detail_id,
                'driver_name'         => $driver ? (($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')) : '—',
                'company_name'        => $company ? $company->company_name : ($item->company_name ?? 'Custom company'),
                'email'               => $item->email,
                'email_sent'          => $item->email_sent,
                'verification_status' => $item->verification_status ?? 'pending',
                'attempt_count'       => $item->verificationTokens->count(),
                'updated_at'          => $item->updated_at?->format('M d, Y'),
            ];
        });

        $drivers = UserDriverDetail::with('user')
            ->whereHas('employmentCompanies', fn ($q) => $q->where('email_sent', true))
            ->get()
            ->map(fn ($d) => [
                'id'   => $d->id,
                'name' => trim(($d->user->name ?? '') . ' ' . ($d->last_name ?? '')),
            ]);

        return Inertia::render('admin/drivers/employment-verification/Index', [
            'verifications' => $verifications,
            'drivers'       => $drivers,
            'filters'       => $request->only(['status', 'driver']),
        ]);
    }

    public function show($id)
    {
        $company = DriverEmploymentCompany::with([
            'userDriverDetail.user',
            'masterCompany',
            'verificationTokens',
            'media',
        ])->findOrFail($id);

        $driver = $company->userDriverDetail;
        $masterCompany = $company->masterCompany;

        $maxAttempts = 3;
        $attemptCount = $company->verificationTokens->count();

        $tokens = $company->verificationTokens->sortBy('created_at')->values()->map(fn ($t) => [
            'id'            => $t->id,
            'email'         => $t->email,
            'created_at'    => $t->created_at?->format('M d, Y H:i'),
            'expires_at'    => $t->expires_at?->format('M d, Y H:i'),
            'verified_at'   => $t->verified_at?->format('M d, Y H:i'),
            'is_verified'   => $t->isVerified(),
            'is_expired'    => $t->isExpired(),
            'document_url'  => $t->document_path ? Storage::url($t->document_path) : null,
            'signature_url' => $t->signature_path ? Storage::url($t->signature_path) : null,
        ]);

        $latestToken = $tokens->last();

        $documents = $company->getMedia('employment_verification_documents')->map(fn ($m) => [
            'id'             => $m->id,
            'file_name'      => $m->file_name,
            'original_name'  => $m->getCustomProperty('original_name'),
            'uploaded_by'    => $m->getCustomProperty('uploaded_by'),
            'uploaded_at'    => $m->created_at?->format('M d, Y H:i'),
            'url'            => $m->getUrl(),
            'size_formatted' => number_format($m->size / 1024, 1) . ' KB',
        ]);

        // Attempt Record PDFs — stored on the driver model filtered by company_id
        $attemptPdfs = $driver
            ? $driver->getMedia('employment_verification_attempts')
                ->filter(fn ($m) => $m->getCustomProperty('company_id') == $company->id)
                ->sortBy('created_at')
                ->values()
                ->map(fn ($m) => [
                    'id'             => $m->id,
                    'label'          => $m->name,
                    'file_name'      => $m->file_name,
                    'attempt_number' => $m->getCustomProperty('attempt_number'),
                    'email_sent_to'  => $m->getCustomProperty('email_sent_to'),
                    'sent_at'        => $m->getCustomProperty('sent_at'),
                    'url'            => $m->getUrl(),
                    'size_formatted' => number_format($m->size / 1024, 1) . ' KB',
                ])
            : collect();

        return Inertia::render('admin/drivers/employment-verification/Show', [
            'verification' => [
                'id'                        => $company->id,
                'driver_id'                 => $company->user_driver_detail_id,
                'driver_name'               => $driver ? trim(($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')) : '—',
                'company_name'              => $masterCompany?->company_name ?? ($company->company_name ?? 'Custom Company'),
                'email'                     => $company->email,
                'email_sent'                => $company->email_sent,
                'verification_status'       => $company->verification_status,
                'verification_date'         => $company->verification_date?->format('M d, Y H:i'),
                'verification_notes'        => $company->verification_notes,
                'positions_held'            => $company->positions_held,
                'employed_from'             => $company->employed_from?->format('M d, Y'),
                'employed_to'               => $company->employed_to?->format('M d, Y'),
                'subject_to_fmcsr'          => $company->subject_to_fmcsr,
                'safety_sensitive_function' => $company->safety_sensitive_function,
                'reason_for_leaving'        => $company->reason_for_leaving,
                'attempt_count'             => $attemptCount,
                'max_attempts'              => $maxAttempts,
                'can_send_more'             => $attemptCount < $maxAttempts,
                'tokens'                    => $tokens,
                'latest_token'              => $latestToken,
                'documents'                 => $documents,
                'attempt_pdfs'              => $attemptPdfs->values(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'driver_id' => 'required|exists:user_driver_details,id',
            'company_mode' => 'required|in:existing,new',
            'selected_company_id' => 'nullable|required_if:company_mode,existing|exists:master_companies,id',
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:10',
            'company_zip' => 'nullable|string|max:20',
            'company_phone' => 'nullable|string|max:30',
            'company_contact' => 'nullable|string|max:255',
            'company_fax' => 'nullable|string|max:30',
            'employed_from' => 'required|date',
            'employed_to' => 'nullable|date|after_or_equal:employed_from',
            'positions_held' => 'required|string|max:255',
            'subject_to_fmcsr' => 'boolean',
            'safety_sensitive_function' => 'boolean',
            'reason_for_leaving' => 'required|string|max:255',
            'other_reason_description' => 'nullable|string|max:255',
            'explanation' => 'nullable|string|max:1000',
            'send_email' => 'boolean',
            'add_to_directory' => 'boolean',
        ]);

        $driver = UserDriverDetail::with('user')->findOrFail($validated['driver_id']);

        if ((int) $driver->carrier_id !== (int) $validated['carrier_id']) {
            return back()->withErrors([
                'driver_id' => 'The selected driver does not belong to the selected carrier.',
            ])->withInput();
        }

        try {
            $company = DB::transaction(function () use ($validated, $driver) {
                if ($validated['company_mode'] === 'existing') {
                    $masterCompany = MasterCompany::findOrFail($validated['selected_company_id']);
                } else {
                    $masterCompany = MasterCompany::firstOrCreate(
                        ['company_name' => $validated['company_name']],
                        [
                            'address' => $validated['company_address'] ?? null,
                            'city' => $validated['company_city'] ?? null,
                            'state' => $validated['company_state'] ?? null,
                            'zip' => $validated['company_zip'] ?? null,
                            'phone' => $validated['company_phone'] ?? null,
                            'contact' => $validated['company_contact'] ?? null,
                            'email' => $validated['company_email'] ?? null,
                            'fax' => $validated['company_fax'] ?? null,
                        ]
                    );

                    if (!empty($validated['add_to_directory'])) {
                        $masterCompany->fill([
                            'address' => $validated['company_address'] ?? $masterCompany->address,
                            'city' => $validated['company_city'] ?? $masterCompany->city,
                            'state' => $validated['company_state'] ?? $masterCompany->state,
                            'zip' => $validated['company_zip'] ?? $masterCompany->zip,
                            'phone' => $validated['company_phone'] ?? $masterCompany->phone,
                            'contact' => $validated['company_contact'] ?? $masterCompany->contact,
                            'email' => $validated['company_email'] ?? $masterCompany->email,
                            'fax' => $validated['company_fax'] ?? $masterCompany->fax,
                        ])->save();
                    }
                }

                return DriverEmploymentCompany::create([
                    'user_driver_detail_id' => $driver->id,
                    'master_company_id' => $masterCompany->id,
                    'employed_from' => $validated['employed_from'],
                    'employed_to' => $validated['employed_to'] ?? null,
                    'positions_held' => $validated['positions_held'],
                    'subject_to_fmcsr' => (bool) ($validated['subject_to_fmcsr'] ?? false),
                    'safety_sensitive_function' => (bool) ($validated['safety_sensitive_function'] ?? false),
                    'reason_for_leaving' => $validated['reason_for_leaving'],
                    'other_reason_description' => $validated['other_reason_description'] ?? null,
                    'explanation' => $validated['explanation'] ?? null,
                    'email' => $validated['company_email'] ?? $masterCompany->email,
                    'email_sent' => false,
                ]);
            });

            $message = 'Employment verification created successfully.';

            if (!empty($validated['send_email']) && !empty($company->email)) {
                try {
                    $attemptNumber = $this->sendVerificationEmail($company);
                    $message .= " Verification email sent (Attempt #{$attemptNumber}/3).";
                } catch (\Exception $e) {
                    Log::warning('Employment verification created but initial email failed', [
                        'employment_company_id' => $company->id,
                        'error' => $e->getMessage(),
                    ]);

                    $message .= ' The record was created, but the verification email could not be sent.';
                }
            }

            return redirect()
                ->route('admin.drivers.employment-verification.show', $company->id)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to create employment verification', [
                'error' => $e->getMessage(),
                'driver_id' => $validated['driver_id'] ?? null,
            ]);

            return back()->withErrors([
                'general' => 'Failed to create employment verification: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    public function resend($id)
    {
        $company = DriverEmploymentCompany::with(['userDriverDetail.user', 'masterCompany'])->findOrFail($id);

        if (empty($company->email)) {
            return back()->with('error', 'No email address found for this company.');
        }

        $attemptCount = EmploymentVerificationToken::where('employment_company_id', $company->id)->count();
        if ($attemptCount >= 3) {
            return back()->with('error', 'Maximum verification attempts (3) reached. No more emails can be sent.');
        }

        try {
            $attemptNumber = $this->sendVerificationEmail($company);

            Log::info('Admin resent employment verification email', [
                'employment_id' => $company->id,
                'attempt'       => $attemptNumber,
                'admin'         => Auth::id(),
            ]);

            return back()->with('success', "Verification email sent (Attempt #{$attemptNumber}/3).");
        } catch (\Exception $e) {
            Log::error('Failed to resend employment verification', ['error' => $e->getMessage(), 'id' => $id]);

            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function toggleEmailFlag($id)
    {
        $company = DriverEmploymentCompany::findOrFail($id);
        $newStatus = ! $company->email_sent;
        $company->update(['email_sent' => $newStatus]);

        return back()->with('success', $newStatus ? 'Marked as email sent.' : 'Marked as email not sent.');
    }

    public function markVerified(Request $request, $id)
    {
        DriverEmploymentCompany::findOrFail($id)->update([
            'verification_status' => 'verified',
            'verification_date'   => now(),
            'verification_notes'  => $request->input('notes', 'Manually verified by admin'),
        ]);

        return back()->with('success', 'Marked as verified.');
    }

    public function markRejected(Request $request, $id)
    {
        DriverEmploymentCompany::findOrFail($id)->update([
            'verification_status' => 'rejected',
            'verification_date'   => now(),
            'verification_notes'  => $request->input('notes', 'Manually rejected by admin'),
        ]);

        return back()->with('success', 'Marked as rejected.');
    }

    public function uploadDocument(Request $request, $id)
    {
        $request->validate([
            'verification_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'verification_date'     => 'required|date',
            'verification_notes'    => 'nullable|string|max:500',
        ]);

        $company = DriverEmploymentCompany::findOrFail($id);

        try {
            $file = $request->file('verification_document');
            $originalName = $file->getClientOriginalName();
            $uniqueFileName = 'Employment_verification_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $company->addMedia($file->getRealPath())
                ->usingName('Employment Verification - ' . $originalName)
                ->usingFileName($uniqueFileName)
                ->withCustomProperties([
                    'uploaded_by'        => Auth::user()?->name ?? 'Admin',
                    'uploaded_at'        => now()->format('Y-m-d H:i:s'),
                    'manual_upload'      => true,
                    'original_name'      => $originalName,
                    'verification_date'  => $request->verification_date,
                    'verification_notes' => $request->verification_notes,
                ])
                ->toMediaCollection('employment_verification_documents');

            // Auto-mark as verified on upload
            if ($company->verification_status !== 'verified') {
                $company->update([
                    'verification_status' => 'verified',
                    'verification_date'   => now(),
                    'verification_notes'  => ($request->verification_notes ?? '') . "\n\nManually verified by " . (Auth::user()?->name ?? 'Admin') . ' on ' . now()->format('Y-m-d H:i:s'),
                ]);
            }

            return back()->with('success', 'Document uploaded successfully.');
        } catch (\Exception $e) {
            Log::error('Error uploading verification document', ['error' => $e->getMessage(), 'id' => $id]);

            return back()->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    public function deleteDocument($id, $mediaId)
    {
        $company = DriverEmploymentCompany::findOrFail($id);
        $media = $company->getMedia('employment_verification_documents')->firstWhere('id', $mediaId);

        if (! $media) {
            return back()->with('error', 'Document not found.');
        }

        $media->delete();

        return back()->with('success', 'Document deleted.');
    }

    public function deleteToken($id, $tokenId)
    {
        $company = DriverEmploymentCompany::findOrFail($id);
        $token = EmploymentVerificationToken::where('id', $tokenId)
            ->where('employment_company_id', $id)
            ->firstOrFail();

        if ($token->isVerified()) {
            return back()->with('error', 'Cannot delete a verified token.');
        }

        $token->delete();

        return back()->with('success', 'Verification attempt deleted.');
    }

    protected function sendVerificationEmail(DriverEmploymentCompany $company): int
    {
        $attemptCount = EmploymentVerificationToken::where('employment_company_id', $company->id)->count();
        if ($attemptCount >= 3) {
            throw new \RuntimeException('Maximum verification attempts (3) reached. No more emails can be sent.');
        }

        $token = Str::random(64);

        EmploymentVerificationToken::create([
            'token'                 => $token,
            'driver_id'             => $company->user_driver_detail_id,
            'employment_company_id' => $company->id,
            'email'                 => $company->email,
            'expires_at'            => now()->addDays(30),
        ]);

        $companyName = $company->masterCompany?->company_name ?? ($company->company_name ?? 'Company');
        $driver = $company->userDriverDetail;
        $driverName = $driver ? trim(($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')) : 'Driver';

        $employmentData = [
            'positions_held'            => $company->positions_held,
            'employed_from'             => $company->employed_from?->format('m/d/Y'),
            'employed_to'               => $company->employed_to?->format('m/d/Y'),
            'reason_for_leaving'        => $company->reason_for_leaving,
            'subject_to_fmcsr'          => $company->subject_to_fmcsr,
            'safety_sensitive_function' => $company->safety_sensitive_function,
        ];

        Mail::to($company->email)->send(new EmploymentVerification(
            $companyName,
            $driverName,
            $employmentData,
            $token,
            $company->user_driver_detail_id,
            $company->id,
        ));

        $company->update(['email_sent' => true]);

        $attemptNumber = $attemptCount + 1;

        // Generate Email Attempt Record PDF (same as efservices)
        $this->generateAttemptPdf($company, $driver, $companyName, $driverName, $token, $attemptNumber);

        return $attemptNumber;
    }

    protected function generateAttemptPdf(
        DriverEmploymentCompany $company,
        ?object $driver,
        string $companyName,
        string $driverName,
        string $token,
        int $attemptNumber
    ): void {
        if (!$driver) return;

        try {
            $pdfData = [
                'attemptNumber'  => $attemptNumber,
                'attemptDate'    => now()->format('m/d/Y'),
                'attemptTime'    => now()->format('h:i:s A'),
                'emailSentTo'    => $company->email,
                'driverName'     => $driverName,
                'driverId'       => $driver->id,
                'companyName'    => $companyName,
                'companyEmail'   => $company->email,
                'employedFrom'   => $company->employed_from?->format('m/d/Y') ?? 'Not specified',
                'employedTo'     => $company->employed_to?->format('m/d/Y') ?? 'Not specified',
                'positionsHeld'  => $company->positions_held ?? 'Not specified',
                'reasonForLeaving' => $company->reason_for_leaving ?? 'Not specified',
                'token'          => $token,
                'expiresAt'      => now()->addDays(30)->format('m/d/Y h:i A'),
                'generatedAt'    => now()->format('m/d/Y h:i:s A'),
            ];

            $pdf = Pdf::loadView('pdf.driver.employment-verification-attempt', $pdfData);

            $companySlug  = preg_replace('/[^a-zA-Z0-9]/', '_', $companyName);
            $pdfFileName  = "employment_verification_attempt_{$attemptNumber}_{$companySlug}_" . time() . '.pdf';
            $tempPath     = storage_path('app/temp/' . $pdfFileName);

            Storage::disk('local')->makeDirectory('temp');
            $pdf->save($tempPath);

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

            Log::info('Employment verification attempt PDF generated', [
                'driver_id'      => $driver->id,
                'company_id'     => $company->id,
                'attempt_number' => $attemptNumber,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to generate employment verification attempt PDF', [
                'error'      => $e->getMessage(),
                'company_id' => $company->id,
                'driver_id'  => $driver->id ?? null,
            ]);
        }
    }
}
