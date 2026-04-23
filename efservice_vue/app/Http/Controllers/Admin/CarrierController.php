<?php

namespace App\Http\Controllers\Admin;

use App\Models\Carrier;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Membership;
use App\Models\DocumentType;
use App\Models\CarrierDocument;
use App\Models\CarrierBankingDetail;
use App\Models\User;
use App\Helpers\Constants;
use App\Http\Controllers\Controller;
use App\Services\CarrierService;
use App\Services\CarrierDocumentService;
use App\Services\DotPolicyPdfService;
use App\Repositories\CarrierDocumentRepository;
use App\Traits\SendsCustomNotifications;
use App\Mail\PaymentValidatedMail;
use App\Mail\BankingRejectedMail;
use App\Mail\BankingPendingMail;
use App\Notifications\Admin\Carrier\NewCarrierNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class CarrierController extends Controller
{
    use SendsCustomNotifications;

    protected $carrierService;
    protected $documentService;
    protected CarrierDocumentRepository $documentRepository;

    public function __construct(
        CarrierService $carrierService,
        CarrierDocumentService $documentService,
        CarrierDocumentRepository $documentRepository
    ) {
        $this->carrierService       = $carrierService;
        $this->documentService      = $documentService;
        $this->documentRepository   = $documentRepository;
    }

    public function index(Request $request): Response
    {
        try {
            $filters = $request->only(['search', 'status', 'document_status']);
            $perPage = $request->input('per_page', 15);

            $query = Carrier::with(['membership:id,name,price'])
                ->select([
                    'id', 'name', 'slug', 'address', 'state', 'zipcode',
                    'ein_number', 'dot_number', 'mc_number', 'status',
                    'document_status', 'id_plan', 'created_at', 'updated_at'
                ]);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (!empty($filters['document_status'])) {
                $query->where('document_status', $filters['document_status']);
            }
            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('ein_number', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('dot_number', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('mc_number', 'like', '%' . $filters['search'] . '%');
                });
            }

            $carriers = $query->orderBy('created_at', 'desc')->paginate($perPage);
            $carriers->through(function (Carrier $carrier) {
                $carrierArray = $carrier->toArray();
                $carrierArray['logo_url'] = $carrier->getFirstMediaUrl('logo_carrier') ?: null;

                return $carrierArray;
            });

            return Inertia::render('admin/carriers/Index', [
                'carriers' => $carriers,
                'filters' => $filters,
                'carrierStats' => $this->carrierService->getCarrierStats(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading carriers index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('admin/carriers/Index', [
                'carriers' => ['data' => [], 'links' => [], 'current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0],
                'filters' => [],
                'carrierStats' => [],
            ]);
        }
    }

    public function create(): Response
    {
        $memberships = Membership::where('status', 1)->select('id', 'name', 'price')->get();
        $usStates = Constants::usStates();

        return Inertia::render('admin/carriers/Create', [
            'memberships' => $memberships,
            'usStates' => $usStates,
            'statusOptions' => $this->getStatusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zipcode' => 'required|string|max:10',
            'ein_number' => 'required|string|max:255|unique:carriers,ein_number',
            'dot_number' => 'nullable|string|max:255|unique:carriers,dot_number',
            'mc_number' => 'nullable|string|max:255|unique:carriers,mc_number',
            'state_dot' => 'nullable|string|max:255',
            'ifta_account' => 'nullable|string|max:255',
            'logo_carrier' => 'nullable|image|max:2048',
            'id_plan' => 'required|exists:memberships,id',
            'status' => 'required|integer|in:' . implode(',', [
                Carrier::STATUS_INACTIVE,
                Carrier::STATUS_ACTIVE,
                Carrier::STATUS_PENDING,
                Carrier::STATUS_PENDING_VALIDATION,
            ]),
        ]);

        try {
            $carrier = $this->carrierService->createCarrier($validated, $request->file('logo_carrier'));
            $this->generateDotPolicyPdf($carrier);

            foreach (User::role('superadmin')->get() as $admin) {
                app(\App\Services\NotificationService::class)
                    ->sendWithPreferences($admin, new NewCarrierNotification($carrier), 'carrier_registration');
            }

            return redirect()
                ->route('admin.carriers.show', $carrier)
                ->with('success', 'Carrier created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating carrier', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()->withInput()->with('error', 'Error creating carrier: ' . $e->getMessage());
        }
    }

    public function show(Carrier $carrier): Response
    {
        try {
            $carrierData = $this->carrierService->getCarrierWithDetails($carrier->id);
            $carrierModel = $carrierData['carrier'];

            $dotPolicyMedia = $carrierModel->getFirstMedia('dot_policy_documents');
            $logoUrl = $carrierModel->getFirstMediaUrl('logo_carrier');

            $carrierArray = $carrierModel->only([
                'id', 'name', 'slug', 'address', 'state', 'zipcode',
                'ein_number', 'dot_number', 'mc_number', 'state_dot',
                'ifta_account', 'status', 'document_status', 'id_plan',
                'referrer_token', 'created_at', 'updated_at',
            ]);
            $carrierArray['membership'] = $carrierModel->membership?->only(['id', 'name', 'price', 'description']);
            $carrierArray['logo_url'] = $logoUrl ?: null;
            $carrierArray['referral_url'] = $carrierModel->referrer_token
                ? route('driver.register', [$carrierModel->slug, 'token' => $carrierModel->referrer_token])
                : null;

            $userCarriers = $carrierData['userCarriers']->map(fn ($uc) => [
                'id' => $uc->id,
                'user_id' => $uc->user_id,
                'phone' => $uc->phone,
                'job_position' => $uc->job_position,
                'status' => $uc->status,
                'created_at' => $uc->created_at,
                'user' => $uc->user ? $uc->user->only(['id', 'name', 'email', 'status']) : null,
            ]);

            $drivers = $carrierData['drivers']->map(fn ($d) => [
                'id'         => $d->id,
                'user_id'    => $d->user_id,
                'status'     => $d->status,
                'created_at' => $d->created_at,
                'user'       => $d->user ? $d->user->only(['id', 'name', 'email', 'status']) : null,
            ]);

            $documents = $carrierData['documents']->map(fn ($doc) => [
                'id'               => $doc->id,
                'document_type_id' => $doc->document_type_id,
                'status'           => $doc->status,
                'status_name'      => $doc->status_name,
                'date'             => $doc->date,
                'created_at'       => $doc->created_at,
                'updated_at'       => $doc->updated_at,
                'document_type'    => $doc->documentType ? $doc->documentType->only(['id', 'name', 'requirement']) : null,
                'file_url'         => $doc->getFirstMediaUrl('carrier_documents') ?: null,
                'has_file'         => (bool) $doc->getFirstMediaUrl('carrier_documents'),
                'notes'            => $doc->notes,
            ]);

            $bankingData = $carrierModel->bankingDetails
                ? $carrierModel->bankingDetails->only([
                    'id', 'account_holder_name', 'account_number',
                    'banking_routing_number', 'zip_code', 'security_code',
                    'country_code', 'status', 'rejection_reason',
                    'created_at', 'updated_at',
                ])
                : null;

            $vehicles = $carrierModel->vehicles()
                ->with('currentDriverAssignment.driver.user')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($v) => [
                    'id'                  => $v->id,
                    'make'                => $v->make,
                    'model'               => $v->model,
                    'year'                => $v->year,
                    'type'                => $v->type,
                    'vin'                 => $v->vin,
                    'company_unit_number' => $v->company_unit_number,
                    'registration_number' => $v->registration_number,
                    'status'              => $v->status,
                    'created_at'          => $v->created_at,
                    'driver'              => $v->currentDriverAssignment?->driver ? [
                        'id'   => $v->currentDriverAssignment->driver->id,
                        'name' => $v->currentDriverAssignment->driver->user?->name ?? 'N/A',
                    ] : null,
                ]);

            return Inertia::render('admin/carriers/Show', [
                'carrier' => $carrierArray,
                'userCarriers' => $userCarriers->values(),
                'drivers' => $drivers->values(),
                'vehicles' => $vehicles->values(),
                'documents' => $documents->values(),
                'pendingDocuments' => $carrierData['pendingDocuments']->map(fn ($d) => [
                    'id' => $d->id, 'document_type_id' => $d->document_type_id,
                    'status' => $d->status, 'document_type' => $d->documentType?->only(['id', 'name']),
                ])->values(),
                'approvedDocuments' => $carrierData['approvedDocuments']->map(fn ($d) => [
                    'id' => $d->id, 'document_type_id' => $d->document_type_id,
                    'status' => $d->status, 'document_type' => $d->documentType?->only(['id', 'name']),
                ])->values(),
                'rejectedDocuments' => $carrierData['rejectedDocuments']->map(fn ($d) => [
                    'id' => $d->id, 'document_type_id' => $d->document_type_id,
                    'status' => $d->status, 'document_type' => $d->documentType?->only(['id', 'name']),
                ])->values(),
                'missingDocumentTypes' => $carrierData['missingDocumentTypes']->map(fn ($dt) => $dt->only(['id', 'name', 'requirement']))->values(),
                'stats' => $carrierData['stats'],
                'bankingDetails' => $bankingData,
                'dotPolicy' => $dotPolicyMedia ? [
                    'url' => $dotPolicyMedia->getUrl(),
                    'generated_at' => $dotPolicyMedia->created_at->diffForHumans(),
                    'file_name' => $dotPolicyMedia->file_name,
                ] : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading carrier details', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('admin/carriers/Show', [
                'carrier' => $carrier->only(['id', 'name', 'slug', 'status', 'created_at', 'updated_at']),
                'userCarriers' => [],
                'drivers' => [],
                'vehicles' => [],
                'documents' => [],
                'pendingDocuments' => [],
                'approvedDocuments' => [],
                'rejectedDocuments' => [],
                'missingDocumentTypes' => [],
                'stats' => [],
                'bankingDetails' => null,
                'dotPolicy' => null,
            ]);
        }
    }

    public function edit(Carrier $carrier): Response
    {
        $memberships = Membership::where('status', 1)->select('id', 'name', 'price')->get();
        $usStates = Constants::usStates();
        $bankingDetails = $carrier->bankingDetails?->only([
            'id', 'account_holder_name', 'account_number',
            'banking_routing_number', 'zip_code', 'security_code',
            'country_code', 'status', 'rejection_reason',
        ]);
        $carrier->load('membership:id,name,price');

        $referralUrl = route('driver.register', [$carrier->slug, 'token' => $carrier->referrer_token]);

        $carrierData = $carrier->only([
            'id', 'name', 'slug', 'address', 'state', 'zipcode',
            'ein_number', 'dot_number', 'mc_number', 'state_dot',
            'ifta_account', 'status', 'document_status', 'id_plan',
            'referrer_token', 'created_at', 'updated_at',
        ]);
        $carrierData['membership'] = $carrier->membership?->only(['id', 'name', 'price']);
        $carrierData['logo_url'] = $carrier->getFirstMediaUrl('logo_carrier') ?: null;

        return Inertia::render('admin/carriers/Edit', [
            'carrier' => $carrierData,
            'memberships' => $memberships,
            'usStates' => $usStates,
            'statusOptions' => $this->getStatusOptions(),
            'bankingDetails' => $bankingDetails,
            'referralUrl' => $referralUrl,
        ]);
    }

    public function update(Request $request, Carrier $carrier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zipcode' => 'required|string|max:10',
            'ein_number' => 'required|string|max:255',
            'dot_number' => 'nullable|string|max:255',
            'mc_number' => 'nullable|string|max:255',
            'state_dot' => 'nullable|string|max:255',
            'ifta_account' => 'nullable|string|max:255',
            'logo_carrier' => 'nullable|image|max:2048',
            'id_plan' => 'required|exists:memberships,id',
            'status' => 'required|integer|in:' . implode(',', [
                Carrier::STATUS_INACTIVE,
                Carrier::STATUS_ACTIVE,
                Carrier::STATUS_PENDING,
                Carrier::STATUS_PENDING_VALIDATION,
            ]),
            'referrer_token' => 'nullable|string|max:16|unique:carriers,referrer_token,' . $carrier->id,
        ]);

        try {
            $updatedCarrier = $this->carrierService->updateCarrier(
                $carrier->id,
                $validated,
                $request->file('logo_carrier')
            );

            $this->generateDotPolicyPdf($updatedCarrier);

            return redirect()
                ->route('admin.carriers.show', $updatedCarrier)
                ->with('success', 'Carrier updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating carrier', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Error updating carrier: ' . $e->getMessage());
        }
    }

    public function destroy(Carrier $carrier): RedirectResponse
    {
        try {
            $this->carrierService->deleteCarrier($carrier->id);

            return redirect()
                ->route('admin.carriers.index')
                ->with('success', 'Carrier deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function documents(Carrier $carrier): Response
    {
        $documents = CarrierDocument::where('carrier_id', $carrier->id)
            ->with('documentType:id,name,requirement')
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'document_type_id' => $doc->document_type_id,
                'status' => $doc->status,
                'date' => $doc->date,
                'created_at' => $doc->created_at,
                'document_type' => $doc->documentType?->only(['id', 'name', 'requirement']),
                'file_url' => $doc->getFirstMediaUrl('carrier_documents') ?: null,
            ]);

        $documentTypes = DocumentType::select('id', 'name', 'requirement')->get();

        return Inertia::render('admin/carriers/Documents', [
            'carrier' => $carrier->only(['id', 'name', 'slug', 'mc_number', 'dot_number']),
            'documents' => $documents->values(),
            'documentTypes' => $documentTypes,
        ]);
    }

    public function updateDocumentStatus(Request $request, CarrierDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|integer|in:0,1,2,3',
        ]);

        $document->update(['status' => (int) $validated['status']]);

        // Sync carrier.document_status to reflect the change
        $carrier = Carrier::find($document->carrier_id);
        if ($carrier) {
            $this->documentRepository->syncCarrierDocumentStatus($carrier);
        }

        \Cache::forget("carrier_stats_{$document->carrier_id}");
        \Cache::forget("carrier_details_{$document->carrier_id}");

        return back()->with('success', 'Document status updated successfully.');
    }

    public function regenerateReferrerToken(Carrier $carrier): RedirectResponse
    {
        $token = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8));

        // Ensure uniqueness
        while (Carrier::where('referrer_token', $token)->where('id', '!=', $carrier->id)->exists()) {
            $token = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8));
        }

        $carrier->update(['referrer_token' => $token]);

        return back()->with('success', 'Referrer token regenerated successfully.');
    }

    public function generateMissingDocuments(Carrier $carrier): RedirectResponse
    {
        try {
            $allDocumentTypes = DocumentType::all();
            $existingIds = CarrierDocument::where('carrier_id', $carrier->id)
                ->pluck('document_type_id')
                ->toArray();

            $missing = $allDocumentTypes->whereNotIn('id', $existingIds);
            $createdCount = 0;

            foreach ($missing as $type) {
                CarrierDocument::create([
                    'carrier_id' => $carrier->id,
                    'document_type_id' => $type->id,
                    'status' => CarrierDocument::STATUS_PENDING,
                    'date' => now(),
                ]);
                $createdCount++;
            }

            $message = $createdCount > 0
                ? "{$createdCount} missing documents generated."
                : 'All document types already exist.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating documents: ' . $e->getMessage());
        }
    }

    public function deletePhoto(Carrier $carrier): \Illuminate\Http\JsonResponse
    {
        try {
            if ($carrier->hasMedia('logo_carrier')) {
                $carrier->getFirstMedia('logo_carrier')->delete();
            }

            return response()->json([
                'success' => true,
                'defaultPhotoUrl' => asset('images/default-carrier-logo.png'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approveBanking(Carrier $carrier): RedirectResponse
    {
        try {
            $bankingDetails = $carrier->bankingDetails;

            if (!$bankingDetails) {
                return back()->with('error', 'No banking information found.');
            }

            $bankingDetails->update(['status' => 'approved']);
            $carrier->update(['status' => Carrier::STATUS_ACTIVE]);

            $this->sendBankingEmail($carrier, 'approved');

            return back()->with('success', 'Banking information approved. Carrier is now active.');
        } catch (\Exception $e) {
            Log::error('Error approving banking', ['carrier_id' => $carrier->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function rejectBanking(Request $request, Carrier $carrier): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        try {
            $bankingDetails = $carrier->bankingDetails;

            if (!$bankingDetails) {
                return back()->with('error', 'No banking information found.');
            }

            $bankingDetails->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
            ]);
            $carrier->update(['status' => Carrier::STATUS_PENDING_VALIDATION]);

            $this->sendBankingEmail($carrier, 'rejected', $request->rejection_reason);

            return back()->with('warning', 'Banking information rejected. Carrier has been notified.');
        } catch (\Exception $e) {
            Log::error('Error rejecting banking', ['carrier_id' => $carrier->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function updateBanking(Request $request, Carrier $carrier): RedirectResponse
    {
        $request->validate([
            'account_holder_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'banking_routing_number' => 'required|string|size:9|regex:/^[0-9]{9}$/',
            'zip_code' => 'required|string|regex:/^[0-9]{5}(-[0-9]{4})?$/',
            'security_code' => 'required|string|min:3|max:4|regex:/^[0-9]{3,4}$/',
            'country_code' => 'required|string|max:3',
            'status' => 'required|in:pending,approved,rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        try {
            $bankingDetails = $carrier->bankingDetails;

            if (!$bankingDetails) {
                return back()->with('error', 'No banking information found.');
            }

            $oldStatus = $bankingDetails->status;
            $newStatus = $request->status;

            $bankingDetails->update([
                'account_holder_name' => $request->account_holder_name,
                'account_number' => $request->account_number,
                'banking_routing_number' => $request->banking_routing_number,
                'zip_code' => $request->zip_code,
                'security_code' => $request->security_code,
                'country_code' => $request->country_code,
                'status' => $newStatus,
                'rejection_reason' => $request->rejection_reason,
            ]);

            if ($oldStatus !== $newStatus) {
                $this->handleBankingStatusChange($carrier, $oldStatus, $newStatus, $request->rejection_reason);
            }

            return back()->with('success', 'Banking information updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating banking', ['carrier_id' => $carrier->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function storeBanking(Request $request, Carrier $carrier): RedirectResponse
    {
        $request->validate([
            'account_holder_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'banking_routing_number' => 'required|string|size:9|regex:/^[0-9]{9}$/',
            'zip_code' => 'required|string|regex:/^[0-9]{5}(-[0-9]{4})?$/',
            'security_code' => 'required|string|min:3|max:4|regex:/^[0-9]{3,4}$/',
            'country_code' => 'required|string|max:3',
            'status' => 'required|in:pending,approved,rejected',
            'rejection_reason' => 'nullable|string|max:1000|required_if:status,rejected',
        ]);

        try {
            if ($carrier->bankingDetails) {
                return back()->with('error', 'Banking information already exists. Use edit instead.');
            }

            CarrierBankingDetail::create([
                'carrier_id' => $carrier->id,
                'account_holder_name' => $request->account_holder_name,
                'account_number' => $request->account_number,
                'banking_routing_number' => $request->banking_routing_number,
                'zip_code' => $request->zip_code,
                'security_code' => $request->security_code,
                'country_code' => $request->country_code,
                'status' => $request->status,
                'rejection_reason' => $request->rejection_reason,
            ]);

            return back()->with('success', 'Banking information created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating banking', ['carrier_id' => $carrier->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function regenerateDotPolicy(Carrier $carrier): RedirectResponse
    {
        try {
            $this->generateDotPolicyPdf($carrier);
            return back()->with('success', 'DOT Policy PDF generated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating DOT Policy: ' . $e->getMessage());
        }
    }

    // ── Private Helpers ──

    private function getStatusOptions(): array
    {
        return [
            ['value' => Carrier::STATUS_INACTIVE, 'label' => 'Inactive'],
            ['value' => Carrier::STATUS_ACTIVE, 'label' => 'Active'],
            ['value' => Carrier::STATUS_PENDING, 'label' => 'Pending'],
            ['value' => Carrier::STATUS_PENDING_VALIDATION, 'label' => 'Pending Validation'],
        ];
    }

    private function generateDotPolicyPdf(Carrier $carrier): void
    {
        try {
            $pdfService = app(DotPolicyPdfService::class);
            $pdfPath = $pdfService->generate($carrier);

            if (file_exists($pdfPath)) {
                $carrier->clearMediaCollection('dot_policy_documents');
                $carrier->addMedia($pdfPath)
                    ->preservingOriginal()
                    ->usingFileName('DOT_Policy_' . str_replace(' ', '_', $carrier->name) . '.pdf')
                    ->toMediaCollection('dot_policy_documents');
            }
        } catch (\Exception $e) {
            Log::error('Error generating DOT Policy PDF', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleBankingStatusChange(Carrier $carrier, string $oldStatus, string $newStatus, ?string $reason = null): void
    {
        if ($newStatus === 'approved') {
            $carrier->update(['status' => Carrier::STATUS_ACTIVE]);
            $this->sendBankingEmail($carrier, 'approved');
        } elseif ($newStatus === 'rejected' && $reason) {
            $carrier->update(['status' => Carrier::STATUS_PENDING_VALIDATION]);
            $this->sendBankingEmail($carrier, 'rejected', $reason);
        } elseif ($newStatus === 'pending') {
            $carrier->update(['status' => Carrier::STATUS_PENDING_VALIDATION]);
            $this->sendBankingEmail($carrier, 'pending');
        }
    }

    private function sendBankingEmail(Carrier $carrier, string $status, ?string $reason = null): void
    {
        try {
            $primaryUser = $carrier->userCarriers()->with('user')->first();
            $userEmail = $primaryUser?->user?->email;
            $user = $primaryUser?->user;

            if (!$userEmail) {
                Log::warning('No primary user email found for carrier', ['carrier_id' => $carrier->id]);
                return;
            }

            match ($status) {
                'approved' => Mail::to($userEmail)->send(new PaymentValidatedMail($carrier, $user)),
                'rejected' => Mail::to($userEmail)->send(new BankingRejectedMail($carrier, $reason)),
                'pending' => Mail::to($userEmail)->send(new BankingPendingMail($carrier, $user)),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error("Error sending banking {$status} email", [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
