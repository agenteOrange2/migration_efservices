<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Driver\DriverTesting;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CarrierDriverTestingsController
{
    use ResolvesCarrierContext;

    public function index(Request $request): Response
    {
        $carrier = $this->resolveCarrier();

        $filters = [
            'search' => (string) $request->input('search', ''),
            'driver_filter' => (string) $request->input('driver_filter', ''),
            'test_type' => (string) $request->input('test_type', ''),
            'status' => (string) $request->input('status', ''),
            'test_result' => (string) $request->input('test_result', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
        ];

        $query = DriverTesting::query()
            ->with([
                'userDriverDetail.user:id,name,email',
                'userDriverDetail:id,user_id,carrier_id,middle_name,last_name,phone',
                'carrier:id,name',
            ])
            ->where('carrier_id', $carrier->id)
            ->orderByDesc('test_date');

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($builder) use ($term) {
                $builder->where('administered_by', 'like', $term)
                    ->orWhere('requester_name', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('userDriverDetail.user', fn ($userQuery) => $userQuery->where('name', 'like', $term))
                    ->orWhereHas('userDriverDetail', fn ($driverQuery) => $driverQuery
                        ->where('last_name', 'like', $term)
                        ->orWhere('middle_name', 'like', $term));
            });
        }

        if ($filters['driver_filter'] !== '') {
            $query->where('user_driver_detail_id', $filters['driver_filter']);
        }

        if ($filters['test_type'] !== '') {
            $query->where('test_type', $filters['test_type']);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['test_result'] !== '') {
            $query->where('test_result', $filters['test_result']);
        }

        if ($filters['date_from'] !== '' && ($dateFrom = $this->parseUsDate($filters['date_from']))) {
            $query->whereDate('test_date', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($filters['date_to'] !== '' && ($dateTo = $this->parseUsDate($filters['date_to']))) {
            $query->whereDate('test_date', '<=', $dateTo->format('Y-m-d'));
        }

        $testTypes = DriverTesting::getTestTypes();

        $testings = $query->paginate(15)->withQueryString();
        $testings->through(function (DriverTesting $testing) use ($testTypes) {
            $driver = $testing->userDriverDetail;

            return [
                'id' => $testing->id,
                'driver_id' => $driver?->id,
                'driver_name' => trim(($driver?->user?->name ?? '') . ' ' . ($driver?->middle_name ?? '') . ' ' . ($driver?->last_name ?? '')) ?: 'N/A',
                'driver_email' => $driver?->user?->email,
                'carrier_name' => $testing->carrier?->name ?? 'N/A',
                'carrier_id' => $testing->carrier_id,
                'test_type' => $testing->test_type,
                'test_type_label' => $testTypes[$testing->test_type] ?? $testing->test_type,
                'test_date' => $testing->test_date?->format('n/j/Y'),
                'test_date_raw' => $testing->test_date?->format('Y-m-d'),
                'test_result' => $testing->test_result,
                'status' => $testing->status,
                'administered_by' => $testing->administered_by,
                'location' => $testing->location,
                'next_test_due' => $testing->next_test_due?->format('n/j/Y'),
                'is_random_test' => $testing->is_random_test,
                'is_post_accident_test' => $testing->is_post_accident_test,
                'is_reasonable_suspicion_test' => $testing->is_reasonable_suspicion_test,
                'is_pre_employment_test' => $testing->is_pre_employment_test,
                'is_follow_up_test' => $testing->is_follow_up_test,
                'is_return_to_duty_test' => $testing->is_return_to_duty_test,
                'is_other_reason_test' => $testing->is_other_reason_test,
            ];
        });

        $statsBase = DriverTesting::query()->where('carrier_id', $carrier->id);

        return Inertia::render('carrier/driver-testings/Index', [
            'testings' => $testings,
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions($carrier),
            'filters' => $filters,
            'testTypes' => $testTypes,
            'statuses' => DriverTesting::getStatuses(),
            'testResults' => DriverTesting::getTestResults(),
            'stats' => [
                'total' => (clone $statsBase)->count(),
                'positive' => (clone $statsBase)->where('test_result', 'Positive')->count(),
                'negative' => (clone $statsBase)->where('test_result', 'Negative')->count(),
                'scheduled' => (clone $statsBase)->whereIn('status', ['Schedule', 'In Progress'])->count(),
            ],
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function driverHistory(UserDriverDetail $driver, Request $request): Response
    {
        $this->authorizeCarrierDriver($driver);

        $filters = [
            'search' => (string) $request->input('search', ''),
            'test_type' => (string) $request->input('test_type', ''),
            'status' => (string) $request->input('status', ''),
            'test_result' => (string) $request->input('test_result', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
        ];

        $query = DriverTesting::query()
            ->where('user_driver_detail_id', $driver->id)
            ->orderByDesc('test_date');

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($builder) use ($term) {
                $builder->where('administered_by', 'like', $term)
                    ->orWhere('requester_name', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        if ($filters['test_type'] !== '') {
            $query->where('test_type', $filters['test_type']);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['test_result'] !== '') {
            $query->where('test_result', $filters['test_result']);
        }

        if ($filters['date_from'] !== '' && ($dateFrom = $this->parseUsDate($filters['date_from']))) {
            $query->whereDate('test_date', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($filters['date_to'] !== '' && ($dateTo = $this->parseUsDate($filters['date_to']))) {
            $query->whereDate('test_date', '<=', $dateTo->format('Y-m-d'));
        }

        $testTypes = DriverTesting::getTestTypes();
        $testings = $query->paginate(15)->withQueryString();
        $testings->through(function (DriverTesting $testing) use ($testTypes) {
            return [
                'id' => $testing->id,
                'test_date' => $testing->test_date?->format('n/j/Y'),
                'test_type' => $testing->test_type,
                'test_type_label' => $testTypes[$testing->test_type] ?? $testing->test_type,
                'status' => $testing->status,
                'test_result' => $testing->test_result,
                'administered_by' => $testing->administered_by,
                'location' => $testing->location,
                'next_test_due' => $testing->next_test_due?->format('n/j/Y'),
            ];
        });

        $driver->loadMissing(['user', 'carrier']);

        return Inertia::render('carrier/driver-testings/DriverHistory', [
            'driver' => [
                'id' => $driver->id,
                'name' => $this->driverFullName($driver),
                'carrier_name' => $driver->carrier?->name,
            ],
            'testings' => $testings,
            'filters' => $filters,
            'testTypes' => $testTypes,
            'statuses' => DriverTesting::getStatuses(),
            'testResults' => DriverTesting::getTestResults(),
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create(Request $request): Response
    {
        $carrier = $this->resolveCarrier();
        $selectedDriver = null;

        if ($request->filled('driver_id')) {
            $selectedDriver = $this->findCarrierDriverOrFail((int) $request->integer('driver_id'), (int) $carrier->id);
        }

        return Inertia::render('carrier/driver-testings/Create', [
            'driver' => $selectedDriver ? $this->driverPayload($selectedDriver) : null,
            'drivers' => $this->carrierDriverOptions($carrier),
            'testTypes' => DriverTesting::getTestTypes(),
            'locations' => DriverTesting::getLocations(),
            'statuses' => DriverTesting::getStatuses(),
            'testResults' => DriverTesting::getTestResults(),
            'billOptions' => DriverTesting::getBillOptions(),
            'administrators' => DriverTesting::getAdministrators(),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $carrier = $this->resolveCarrier();
        $validated = $this->validatePayload($request, true);

        $driver = $this->findCarrierDriverOrFail((int) $validated['user_driver_detail_id'], (int) $carrier->id);

        $testing = DriverTesting::create([
            'user_driver_detail_id' => $driver->id,
            'carrier_id' => $carrier->id,
            'test_type' => $validated['test_type'],
            'administered_by' => $validated['administered_by'],
            'test_date' => $this->parseUsDate($validated['test_date'])?->format('Y-m-d'),
            'location' => $validated['location'],
            'requester_name' => $validated['requester_name'],
            'mro' => $validated['mro'] ?? null,
            'scheduled_time' => $this->parseDateTime($validated['scheduled_time'] ?? null),
            'test_result' => $validated['test_result'] ?? null,
            'status' => $validated['status'] ?? 'Schedule',
            'next_test_due' => $this->parseUsDate($validated['next_test_due'] ?? null)?->format('Y-m-d'),
            'bill_to' => $validated['bill_to'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_random_test' => $request->boolean('is_random_test'),
            'is_post_accident_test' => $request->boolean('is_post_accident_test'),
            'is_reasonable_suspicion_test' => $request->boolean('is_reasonable_suspicion_test'),
            'is_pre_employment_test' => $request->boolean('is_pre_employment_test'),
            'is_follow_up_test' => $request->boolean('is_follow_up_test'),
            'is_return_to_duty_test' => $request->boolean('is_return_to_duty_test'),
            'is_other_reason_test' => $request->boolean('is_other_reason_test'),
            'other_reason_description' => $validated['other_reason_description'] ?? null,
            'created_by' => Auth::id(),
        ]);

        $this->storeAttachments($request, $testing);
        $this->storePdf($testing);

        return redirect()
            ->route('carrier.drivers.testings.show', $testing)
            ->with('success', 'Drug/Alcohol test record created successfully.');
    }

    public function show(DriverTesting $testing): Response
    {
        $this->authorizeCarrierTesting($testing);

        $testing->load([
            'userDriverDetail.user:id,name,email',
            'userDriverDetail:id,user_id,carrier_id,middle_name,last_name,phone',
            'userDriverDetail.carrier:id,name,dot_number,mc_number',
            'userDriverDetail.licenses' => fn ($query) => $query->where('status', 'active')->orderByDesc('created_at'),
            'carrier:id,name,dot_number,mc_number',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        $testTypes = DriverTesting::getTestTypes();
        $driver = $testing->userDriverDetail;
        $license = $driver?->licenses->first();
        $pdfMedia = $testing->getFirstMedia('drug_test_pdf');

        $attachments = $testing->getMedia('document_attachments')->map(fn ($media) => [
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'size' => round($media->size / 1024, 1) . ' KB',
            'mime_type' => $media->mime_type,
            'extension' => pathinfo($media->file_name, PATHINFO_EXTENSION),
        ])->values()->toArray();

        return Inertia::render('carrier/driver-testings/Show', [
            'testing' => [
                'id' => $testing->id,
                'test_type' => $testing->test_type,
                'test_type_label' => $testTypes[$testing->test_type] ?? $testing->test_type,
                'test_date' => $testing->test_date?->format('n/j/Y'),
                'scheduled_time' => $testing->scheduled_time?->format('n/j/Y g:i A'),
                'test_result' => $testing->test_result,
                'status' => $testing->status,
                'administered_by' => $testing->administered_by,
                'mro' => $testing->mro,
                'requester_name' => $testing->requester_name,
                'location' => $testing->location,
                'next_test_due' => $testing->next_test_due?->format('n/j/Y'),
                'bill_to' => $testing->bill_to,
                'notes' => $testing->notes,
                'is_random_test' => $testing->is_random_test,
                'is_post_accident_test' => $testing->is_post_accident_test,
                'is_reasonable_suspicion_test' => $testing->is_reasonable_suspicion_test,
                'is_pre_employment_test' => $testing->is_pre_employment_test,
                'is_follow_up_test' => $testing->is_follow_up_test,
                'is_return_to_duty_test' => $testing->is_return_to_duty_test,
                'is_other_reason_test' => $testing->is_other_reason_test,
                'other_reason_description' => $testing->other_reason_description,
                'created_at' => $testing->created_at?->format('n/j/Y g:i A'),
                'updated_at' => $testing->updated_at?->format('n/j/Y g:i A'),
                'created_by' => $testing->createdBy?->name,
                'updated_by' => $testing->updatedBy?->name,
                'pdf_url' => $pdfMedia?->getUrl(),
                'pdf_size' => $pdfMedia ? round($pdfMedia->size / 1024, 1) . ' KB' : null,
                'has_pdf' => (bool) $pdfMedia,
                'attachments' => $attachments,
            ],
            'driver' => [
                'id' => $driver?->id,
                'full_name' => trim(($driver?->user?->name ?? '') . ' ' . ($driver?->middle_name ?? '') . ' ' . ($driver?->last_name ?? '')) ?: 'N/A',
                'email' => $driver?->user?->email,
                'phone' => $driver?->phone ?? $driver?->user?->phone,
                'license' => $license ? [
                    'number' => $license->license_number,
                    'class' => $license->license_class,
                    'state' => $license->state_of_issue,
                    'expires' => $license->expiration_date?->format('n/j/Y'),
                ] : null,
            ],
            'carrier' => $testing->carrier ? [
                'id' => $testing->carrier->id,
                'name' => $testing->carrier->name,
                'dot_number' => $testing->carrier->dot_number,
                'mc_number' => $testing->carrier->mc_number,
            ] : null,
            'routeNames' => $this->routeNames(),
            'isCarrierContext' => true,
        ]);
    }

    public function edit(DriverTesting $testing): Response
    {
        $this->authorizeCarrierTesting($testing);
        $carrier = $this->resolveCarrier();
        $testing->load([
            'userDriverDetail.user:id,name,email',
            'userDriverDetail:id,user_id,carrier_id,middle_name,last_name,phone',
            'userDriverDetail.carrier:id,name',
            'userDriverDetail.licenses:id,user_driver_detail_id,license_number,license_class,expiration_date,state_of_issue,status',
        ]);

        $attachments = $testing->getMedia('document_attachments')->map(fn ($media) => [
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'size' => round($media->size / 1024, 1) . ' KB',
        ])->toArray();

        return Inertia::render('carrier/driver-testings/Edit', [
            'driver' => $this->driverPayload($testing->userDriverDetail),
            'drivers' => $this->carrierDriverOptions($carrier),
            'testing' => [
                'id' => $testing->id,
                'test_type' => $testing->test_type,
                'administered_by' => $testing->administered_by,
                'test_date' => $testing->test_date?->format('n/j/Y'),
                'location' => $testing->location,
                'requester_name' => $testing->requester_name,
                'mro' => $testing->mro,
                'scheduled_time' => $testing->scheduled_time?->format('Y-m-d\TH:i'),
                'test_result' => $testing->test_result,
                'status' => $testing->status,
                'next_test_due' => $testing->next_test_due?->format('n/j/Y'),
                'bill_to' => $testing->bill_to,
                'notes' => $testing->notes,
                'is_random_test' => $testing->is_random_test,
                'is_post_accident_test' => $testing->is_post_accident_test,
                'is_reasonable_suspicion_test' => $testing->is_reasonable_suspicion_test,
                'is_pre_employment_test' => $testing->is_pre_employment_test,
                'is_follow_up_test' => $testing->is_follow_up_test,
                'is_return_to_duty_test' => $testing->is_return_to_duty_test,
                'is_other_reason_test' => $testing->is_other_reason_test,
                'other_reason_description' => $testing->other_reason_description,
                'attachments' => $attachments,
            ],
            'testTypes' => DriverTesting::getTestTypes(),
            'locations' => DriverTesting::getLocations(),
            'statuses' => DriverTesting::getStatuses(),
            'testResults' => DriverTesting::getTestResults(),
            'billOptions' => DriverTesting::getBillOptions(),
            'administrators' => DriverTesting::getAdministrators(),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function update(Request $request, DriverTesting $testing): RedirectResponse
    {
        $this->authorizeCarrierTesting($testing);
        $carrier = $this->resolveCarrier();
        $validated = $this->validatePayload($request, false);

        $driver = $this->findCarrierDriverOrFail((int) $validated['user_driver_detail_id'], (int) $carrier->id);

        $testing->update([
            'user_driver_detail_id' => $driver->id,
            'carrier_id' => $carrier->id,
            'test_type' => $validated['test_type'],
            'administered_by' => $validated['administered_by'],
            'test_date' => $this->parseUsDate($validated['test_date'])?->format('Y-m-d'),
            'location' => $validated['location'],
            'requester_name' => $validated['requester_name'],
            'mro' => $validated['mro'] ?? null,
            'scheduled_time' => $this->parseDateTime($validated['scheduled_time'] ?? null),
            'test_result' => $validated['test_result'] ?? null,
            'status' => $validated['status'] ?? $testing->status,
            'next_test_due' => $this->parseUsDate($validated['next_test_due'] ?? null)?->format('Y-m-d'),
            'bill_to' => $validated['bill_to'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_random_test' => $request->boolean('is_random_test'),
            'is_post_accident_test' => $request->boolean('is_post_accident_test'),
            'is_reasonable_suspicion_test' => $request->boolean('is_reasonable_suspicion_test'),
            'is_pre_employment_test' => $request->boolean('is_pre_employment_test'),
            'is_follow_up_test' => $request->boolean('is_follow_up_test'),
            'is_return_to_duty_test' => $request->boolean('is_return_to_duty_test'),
            'is_other_reason_test' => $request->boolean('is_other_reason_test'),
            'other_reason_description' => $validated['other_reason_description'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        if (! empty($validated['delete_attachments'])) {
            foreach ($validated['delete_attachments'] as $mediaId) {
                $media = $testing->getMedia('document_attachments')->find($mediaId);
                $media?->delete();
            }
        }

        $this->storeAttachments($request, $testing);
        $this->storePdf($testing);

        return redirect()
            ->route('carrier.drivers.testings.show', $testing)
            ->with('success', 'Test record updated successfully.');
    }

    public function destroy(DriverTesting $testing): RedirectResponse
    {
        $this->authorizeCarrierTesting($testing);

        $testing->clearMediaCollection('document_attachments');
        $testing->clearMediaCollection('drug_test_pdf');
        $testing->clearMediaCollection('test_results');
        $testing->delete();

        return redirect()
            ->route('carrier.drivers.testings.index')
            ->with('success', 'Test record deleted successfully.');
    }

    public function downloadPdf(DriverTesting $testing): BinaryFileResponse
    {
        $this->authorizeCarrierTesting($testing);

        $media = $testing->getFirstMedia('drug_test_pdf');
        abort_if(! $media, 404, 'PDF not found. Please regenerate it.');

        return response()->download($media->getPath(), 'drug_test_' . $testing->id . '.pdf');
    }

    public function regeneratePdf(DriverTesting $testing): RedirectResponse
    {
        $this->authorizeCarrierTesting($testing);
        $this->storePdf($testing);

        return redirect()
            ->route('carrier.drivers.testings.show', $testing)
            ->with('success', 'PDF regenerated successfully.');
    }

    public function uploadAttachment(Request $request, DriverTesting $testing): RedirectResponse
    {
        $this->authorizeCarrierTesting($testing);

        $request->validate([
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        foreach ($request->file('attachments') as $file) {
            $testing->addMedia($file)->toMediaCollection('document_attachments');
        }

        return redirect()
            ->route('carrier.drivers.testings.show', $testing)
            ->with('success', 'Documents uploaded successfully.');
    }

    public function deleteAttachment(DriverTesting $testing, int $media): RedirectResponse
    {
        $this->authorizeCarrierTesting($testing);

        $item = $testing->getMedia('document_attachments')->find($media);
        abort_if(! $item, 404, 'Attachment not found.');
        $item->delete();

        return redirect()
            ->route('carrier.drivers.testings.show', $testing)
            ->with('success', 'Attachment deleted.');
    }

    protected function validatePayload(Request $request, bool $creating = true): array
    {
        $rules = [
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'test_type' => 'required|string',
            'administered_by' => 'required|string|max:255',
            'test_date' => 'required|string',
            'location' => 'required|string|max:255',
            'requester_name' => 'required|string|max:255',
            'mro' => 'nullable|string|max:255',
            'scheduled_time' => 'nullable|string',
            'test_result' => 'nullable|in:Positive,Negative,Refusal',
            'status' => 'nullable|in:Schedule,In Progress,Pending Review,Completed,Cancelled',
            'next_test_due' => 'nullable|string',
            'bill_to' => 'nullable|in:company,employee',
            'notes' => 'nullable|string',
            'is_random_test' => 'boolean',
            'is_post_accident_test' => 'boolean',
            'is_reasonable_suspicion_test' => 'boolean',
            'is_pre_employment_test' => 'boolean',
            'is_follow_up_test' => 'boolean',
            'is_return_to_duty_test' => 'boolean',
            'is_other_reason_test' => 'boolean',
            'other_reason_description' => 'nullable|string|max:500',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ];

        if (! $creating) {
            $rules['delete_attachments'] = 'nullable|array';
            $rules['delete_attachments.*'] = 'integer';
        }

        return $request->validate($rules);
    }

    protected function storeAttachments(Request $request, DriverTesting $testing): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $testing->addMedia($file)->toMediaCollection('document_attachments');
        }
    }

    protected function storePdf(DriverTesting $testing): void
    {
        $testing->loadMissing([
            'userDriverDetail.user',
            'userDriverDetail.carrier',
            'userDriverDetail.licenses' => fn ($query) => $query->where('status', 'active')->orderByDesc('created_at'),
            'carrier',
        ]);

        $pdf = Pdf::loadView('admin.driver-testings.pdf', ['driverTesting' => $testing]);
        $directory = storage_path('app/public/driver_testings');
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdfPath = $directory . '/driver_testing_' . $testing->id . '.pdf';
        file_put_contents($pdfPath, $pdf->output());

        $testing->clearMediaCollection('drug_test_pdf');
        $testing->addMedia($pdfPath)->toMediaCollection('drug_test_pdf');
    }

    protected function authorizeCarrierTesting(DriverTesting $testing): void
    {
        $testing->loadMissing('userDriverDetail');
        abort_unless((int) $testing->userDriverDetail?->carrier_id === (int) $this->resolveCarrierId(), 404);
    }

    protected function authorizeCarrierDriver(UserDriverDetail $driver): void
    {
        abort_unless((int) $driver->carrier_id === (int) $this->resolveCarrierId(), 404);
    }

    protected function findCarrierDriverOrFail(int $driverId, int $carrierId): UserDriverDetail
    {
        return UserDriverDetail::query()
            ->where('carrier_id', $carrierId)
            ->with([
                'user:id,name,email',
                'carrier:id,name',
                'licenses:id,user_driver_detail_id,license_number,license_class,expiration_date,state_of_issue,status',
            ])
            ->findOrFail($driverId);
    }

    protected function carrierDriverOptions(Carrier $carrier): array
    {
        return UserDriverDetail::query()
            ->where('carrier_id', $carrier->id)
            ->with([
                'user:id,name,email',
                'carrier:id,name',
                'licenses:id,user_driver_detail_id,license_number,license_class,expiration_date,state_of_issue,status',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (UserDriverDetail $driver) => $this->driverPayload($driver))
            ->values()
            ->all();
    }

    protected static function carrierOption(Carrier $carrier): array
    {
        return [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ];
    }

    protected function driverPayload(UserDriverDetail $driver): array
    {
        $license = $driver->licenses
            ->first(fn ($item) => strtolower((string) $item->status) === 'active')
            ?? $driver->licenses->first();

        return [
            'id' => $driver->id,
            'full_name' => $this->driverFullName($driver),
            'email' => $driver->user?->email ?? '',
            'phone' => $driver->phone ?? null,
            'carrier' => $driver->carrier ? [
                'id' => $driver->carrier->id,
                'name' => $driver->carrier->name,
            ] : null,
            'license' => $license ? [
                'number' => $license->license_number,
                'class' => $license->license_class,
                'state' => $license->state_of_issue,
                'expires' => $license->expiration_date?->format('n/j/Y'),
            ] : null,
        ];
    }

    protected function driverFullName(UserDriverDetail $driver): string
    {
        return trim(($driver->user?->name ?? '') . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? '')) ?: 'Unknown';
    }

    protected function parseUsDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value));
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseDateTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        foreach (['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value))->format('Y-m-d H:i:s');
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.drivers.testings.index',
            'show' => 'carrier.drivers.testings.show',
            'create' => 'carrier.drivers.testings.create',
            'store' => 'carrier.drivers.testings.store',
            'edit' => 'carrier.drivers.testings.edit',
            'update' => 'carrier.drivers.testings.update',
            'destroy' => 'carrier.drivers.testings.destroy',
            'driverHistory' => 'carrier.drivers.testings.driver-history',
            'downloadPdf' => 'carrier.drivers.testings.download-pdf',
            'regeneratePdf' => 'carrier.drivers.testings.regenerate-pdf',
            'uploadAttachment' => 'carrier.drivers.testings.upload-attachment',
            'deleteAttachment' => 'carrier.drivers.testings.delete-attachment',
            'driverShow' => 'carrier.drivers.show',
        ];
    }
}
