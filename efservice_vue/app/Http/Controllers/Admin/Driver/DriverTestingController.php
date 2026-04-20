<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverTesting;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverTestingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search'         => (string) $request->input('search', ''),
            'carrier_filter' => (string) $request->input('carrier_filter', ''),
            'test_type'      => (string) $request->input('test_type', ''),
            'status'         => (string) $request->input('status', ''),
            'test_result'    => (string) $request->input('test_result', ''),
            'date_from'      => (string) $request->input('date_from', ''),
            'date_to'        => (string) $request->input('date_to', ''),
        ];

        $query = DriverTesting::with([
            'userDriverDetail.user:id,name,email',
            'userDriverDetail:id,user_id,carrier_id,middle_name,last_name,phone',
            'carrier:id,name',
        ])->orderByDesc('test_date');

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('administered_by', 'like', $term)
                  ->orWhere('requester_name', 'like', $term)
                  ->orWhereHas('userDriverDetail.user', fn ($u) => $u->where('name', 'like', $term))
                  ->orWhereHas('userDriverDetail', fn ($d) => $d->where('last_name', 'like', $term)
                      ->orWhere('middle_name', 'like', $term));
            });
        }

        if ($filters['carrier_filter'] !== '') {
            $query->where('carrier_id', $filters['carrier_filter']);
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

        if ($filters['date_from'] !== '') {
            $query->whereDate('test_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('test_date', '<=', $filters['date_to']);
        }

        $testTypes = DriverTesting::getTestTypes();

        $testings = $query->paginate(15)->withQueryString();

        $testings->through(function (DriverTesting $t) use ($testTypes) {
            $driver = $t->userDriverDetail;
            return [
                'id'               => $t->id,
                'driver_id'        => $driver?->id,
                'driver_name'      => trim(($driver?->user?->name ?? '') . ' ' . ($driver?->middle_name ?? '') . ' ' . ($driver?->last_name ?? '')) ?: 'N/A',
                'driver_email'     => $driver?->user?->email,
                'carrier_name'     => $t->carrier?->name ?? 'N/A',
                'carrier_id'       => $t->carrier_id,
                'test_type'        => $t->test_type,
                'test_type_label'  => $testTypes[$t->test_type] ?? $t->test_type,
                'test_date'        => $t->test_date?->format('n/j/Y'),
                'test_date_raw'    => $t->test_date?->format('Y-m-d'),
                'test_result'      => $t->test_result,
                'status'           => $t->status,
                'administered_by'  => $t->administered_by,
                'location'         => $t->location,
                'next_test_due'    => $t->next_test_due?->format('n/j/Y'),
                'is_random_test'               => $t->is_random_test,
                'is_post_accident_test'        => $t->is_post_accident_test,
                'is_reasonable_suspicion_test' => $t->is_reasonable_suspicion_test,
                'is_pre_employment_test'        => $t->is_pre_employment_test,
                'is_follow_up_test'            => $t->is_follow_up_test,
                'is_return_to_duty_test'       => $t->is_return_to_duty_test,
                'is_other_reason_test'         => $t->is_other_reason_test,
            ];
        });

        // Stats (without pagination filters for totals)
        $statsBase = DriverTesting::query();
        if ($filters['carrier_filter'] !== '') {
            $statsBase->where('carrier_id', $filters['carrier_filter']);
        }

        return Inertia::render('admin/driver-testings/Index', [
            'testings'    => $testings,
            'carriers'    => Carrier::orderBy('name')->get(['id', 'name']),
            'filters'     => $filters,
            'testTypes'   => $testTypes,
            'statuses'    => DriverTesting::getStatuses(),
            'testResults' => DriverTesting::getTestResults(),
            'stats'       => [
                'total'     => (clone $statsBase)->count(),
                'positive'  => (clone $statsBase)->where('test_result', 'Positive')->count(),
                'negative'  => (clone $statsBase)->where('test_result', 'Negative')->count(),
                'scheduled' => (clone $statsBase)->whereIn('status', ['Schedule', 'In Progress'])->count(),
            ],
        ]);
    }

    public function createGlobal(): Response
    {
        $drivers = UserDriverDetail::with([
            'user:id,name,email',
            'carrier:id,name',
            'licenses:id,user_driver_detail_id,license_number,license_class,expiration_date',
        ])
        ->whereHas('user')
        ->orderBy('id')
        ->get()
        ->map(fn ($d) => [
            'id'        => $d->id,
            'full_name' => trim(($d->user?->name ?? 'Unknown') . ' ' . ($d->middle_name ?? '') . ' ' . ($d->last_name ?? '')),
            'email'     => $d->user?->email ?? '',
            'phone'     => $d->phone ?? null,
            'carrier'   => $d->carrier ? ['id' => $d->carrier->id, 'name' => $d->carrier->name] : null,
            'license'   => $d->licenses->first() ? [
                'number'  => $d->licenses->first()->license_number,
                'class'   => $d->licenses->first()->license_class,
                'expires' => $d->licenses->first()->expiration_date?->format('Y-m-d'),
            ] : null,
        ]);

        return Inertia::render('admin/driver-testings/Create', [
            'drivers'        => $drivers->values(),
            'carriers'       => Carrier::orderBy('name')->get(['id', 'name']),
            'testTypes'      => DriverTesting::getTestTypes(),
            'locations'      => DriverTesting::getLocations(),
            'statuses'       => DriverTesting::getStatuses(),
            'testResults'    => DriverTesting::getTestResults(),
            'billOptions'    => DriverTesting::getBillOptions(),
            'administrators' => DriverTesting::getAdministrators(),
        ]);
    }

    public function storeGlobal(Request $request)
    {
        $validated = $request->validate([
            'user_driver_detail_id'        => 'required|exists:user_driver_details,id',
            'test_type'                    => 'required|string',
            'administered_by'              => 'required|string|max:255',
            'test_date'                    => 'required|date',
            'location'                     => 'required|string|max:255',
            'requester_name'               => 'required|string|max:255',
            'mro'                          => 'nullable|string|max:255',
            'scheduled_time'               => 'nullable|date',
            'test_result'                  => 'nullable|in:Positive,Negative,Refusal',
            'status'                       => 'nullable|in:Schedule,In Progress,Pending Review,Completed,Cancelled',
            'next_test_due'                => 'nullable|date',
            'bill_to'                      => 'nullable|in:company,employee',
            'notes'                        => 'nullable|string',
            'is_random_test'               => 'boolean',
            'is_post_accident_test'        => 'boolean',
            'is_reasonable_suspicion_test' => 'boolean',
            'is_pre_employment_test'       => 'boolean',
            'is_follow_up_test'            => 'boolean',
            'is_return_to_duty_test'       => 'boolean',
            'is_other_reason_test'         => 'boolean',
            'other_reason_description'     => 'nullable|string|max:500',
            'attachments'                  => 'nullable|array',
            'attachments.*'                => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);

        $testing = DriverTesting::create([
            'user_driver_detail_id'        => $driver->id,
            'carrier_id'                   => $driver->carrier_id,
            'test_type'                    => $validated['test_type'],
            'administered_by'              => $validated['administered_by'],
            'test_date'                    => $validated['test_date'],
            'location'                     => $validated['location'],
            'requester_name'               => $validated['requester_name'],
            'mro'                          => $validated['mro'] ?? null,
            'scheduled_time'               => $validated['scheduled_time'] ?? null,
            'test_result'                  => $validated['test_result'] ?? null,
            'status'                       => $validated['status'] ?? 'Schedule',
            'next_test_due'                => $validated['next_test_due'] ?? null,
            'bill_to'                      => $validated['bill_to'] ?? null,
            'notes'                        => $validated['notes'] ?? null,
            'is_random_test'               => $request->boolean('is_random_test'),
            'is_post_accident_test'        => $request->boolean('is_post_accident_test'),
            'is_reasonable_suspicion_test' => $request->boolean('is_reasonable_suspicion_test'),
            'is_pre_employment_test'       => $request->boolean('is_pre_employment_test'),
            'is_follow_up_test'            => $request->boolean('is_follow_up_test'),
            'is_return_to_duty_test'       => $request->boolean('is_return_to_duty_test'),
            'is_other_reason_test'         => $request->boolean('is_other_reason_test'),
            'other_reason_description'     => $validated['other_reason_description'] ?? null,
            'created_by'                   => Auth::id(),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $testing->addMedia($file)->toMediaCollection('document_attachments');
            }
        }

        return redirect()
            ->route('admin.driver-testings.show', $testing)
            ->with('success', 'Drug/Alcohol test record created successfully.');
    }

    public function show(DriverTesting $testing): Response
    {
        $testing->load([
            'userDriverDetail.user:id,name,email',
            'userDriverDetail:id,user_id,carrier_id,middle_name,last_name,phone',
            'userDriverDetail.carrier:id,name,dot_number,mc_number',
            'userDriverDetail.licenses' => fn ($q) => $q->where('status', 'active')->orderByDesc('created_at'),
            'carrier:id,name,dot_number,mc_number',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        $testTypes = DriverTesting::getTestTypes();
        $driver    = $testing->userDriverDetail;
        $license   = $driver?->licenses->first();
        $pdfMedia  = $testing->getFirstMedia('drug_test_pdf');

        $attachments = $testing->getMedia('document_attachments')->map(fn ($m) => [
            'id'        => $m->id,
            'name'      => $m->file_name,
            'url'       => $m->getUrl(),
            'size'      => round($m->size / 1024, 1) . ' KB',
            'mime_type' => $m->mime_type,
            'extension' => pathinfo($m->file_name, PATHINFO_EXTENSION),
        ])->values()->toArray();

        return Inertia::render('admin/driver-testings/Show', [
            'testing' => [
                'id'                           => $testing->id,
                'test_type'                    => $testing->test_type,
                'test_type_label'              => $testTypes[$testing->test_type] ?? $testing->test_type,
                'test_date'                    => $testing->test_date?->format('n/j/Y'),
                'scheduled_time'               => $testing->scheduled_time?->format('n/j/Y g:i A'),
                'test_result'                  => $testing->test_result,
                'status'                       => $testing->status,
                'administered_by'              => $testing->administered_by,
                'mro'                          => $testing->mro,
                'requester_name'               => $testing->requester_name,
                'location'                     => $testing->location,
                'next_test_due'                => $testing->next_test_due?->format('n/j/Y'),
                'bill_to'                      => $testing->bill_to,
                'notes'                        => $testing->notes,
                'is_random_test'               => $testing->is_random_test,
                'is_post_accident_test'        => $testing->is_post_accident_test,
                'is_reasonable_suspicion_test' => $testing->is_reasonable_suspicion_test,
                'is_pre_employment_test'       => $testing->is_pre_employment_test,
                'is_follow_up_test'            => $testing->is_follow_up_test,
                'is_return_to_duty_test'       => $testing->is_return_to_duty_test,
                'is_other_reason_test'         => $testing->is_other_reason_test,
                'other_reason_description'     => $testing->other_reason_description,
                'created_at'                   => $testing->created_at?->format('n/j/Y g:i A'),
                'updated_at'                   => $testing->updated_at?->format('n/j/Y g:i A'),
                'created_by'                   => $testing->createdBy?->name,
                'updated_by'                   => $testing->updatedBy?->name,
                'pdf_url'                      => $pdfMedia?->getUrl(),
                'pdf_size'                     => $pdfMedia ? round($pdfMedia->size / 1024, 1) . ' KB' : null,
                'has_pdf'                      => (bool) $pdfMedia,
                'attachments'                  => $attachments,
            ],
            'driver' => [
                'id'           => $driver?->id,
                'full_name'    => trim(($driver?->user?->name ?? '') . ' ' . ($driver?->middle_name ?? '') . ' ' . ($driver?->last_name ?? '')) ?: 'N/A',
                'email'        => $driver?->user?->email,
                'phone'        => $driver?->phone ?? $driver?->user?->phone,
                'license'      => $license ? [
                    'number'  => $license->license_number,
                    'class'   => $license->license_class,
                    'state'   => $license->state_of_issue,
                    'expires' => $license->expiration_date?->format('n/j/Y'),
                ] : null,
            ],
            'carrier' => $testing->carrier ? [
                'id'         => $testing->carrier->id,
                'name'       => $testing->carrier->name,
                'dot_number' => $testing->carrier->dot_number,
                'mc_number'  => $testing->carrier->mc_number,
            ] : null,
        ]);
    }

    public function downloadPdf(DriverTesting $testing): BinaryFileResponse
    {
        $media = $testing->getFirstMedia('drug_test_pdf');
        if (!$media) {
            abort(404, 'PDF not found. Please regenerate it.');
        }
        return response()->download($media->getPath(), 'drug_test_' . $testing->id . '.pdf');
    }

    public function regeneratePdf(DriverTesting $testing)
    {
        $testing->load([
            'userDriverDetail.user',
            'userDriverDetail.carrier',
            'userDriverDetail.licenses' => fn ($q) => $q->where('status', 'active')->orderByDesc('created_at'),
            'carrier',
        ]);

        $pdf = $this->generatePDF($testing);

        $directory = storage_path('app/public/driver_testings');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdfPath = $directory . '/driver_testing_' . $testing->id . '.pdf';
        file_put_contents($pdfPath, $pdf->output());

        $testing->clearMediaCollection('drug_test_pdf');
        $testing->addMedia($pdfPath)->toMediaCollection('drug_test_pdf');

        return redirect()
            ->route('admin.driver-testings.show', $testing)
            ->with('success', 'PDF regenerated successfully.');
    }

    private function generatePDF(DriverTesting $testing)
    {
        return Pdf::loadView('admin.driver-testings.pdf', ['driverTesting' => $testing]);
    }

    public function uploadAttachment(Request $request, DriverTesting $testing)
    {
        $request->validate([
            'attachments'   => 'required|array|min:1',
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        foreach ($request->file('attachments') as $file) {
            $testing->addMedia($file)->toMediaCollection('document_attachments');
        }

        return redirect()
            ->route('admin.driver-testings.show', $testing)
            ->with('success', 'Documents uploaded successfully.');
    }

    public function deleteAttachment(DriverTesting $testing, int $media)
    {
        $item = $testing->getMedia('document_attachments')->find($media);
        abort_if(!$item, 404, 'Attachment not found.');
        $item->delete();

        return redirect()
            ->route('admin.driver-testings.show', $testing)
            ->with('success', 'Attachment deleted.');
    }

    public function destroyGlobal(DriverTesting $testing)
    {
        $testing->clearMediaCollection('document_attachments');
        $testing->clearMediaCollection('drug_test_pdf');
        $testing->clearMediaCollection('test_results');
        $testing->delete();

        return redirect()
            ->route('admin.driver-testings.index')
            ->with('success', 'Test record deleted successfully.');
    }

    public function create(UserDriverDetail $driver): Response
    {
        $driver->load(['user:id,name,email', 'carrier:id,name', 'licenses:id,user_driver_detail_id,license_number,license_class,expiration_date']);

        return Inertia::render('admin/drivers/testings/Create', [
            'driver' => [
                'id'        => $driver->id,
                'full_name' => trim(($driver->user?->name ?? 'Unknown') . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? '')),
                'email'     => $driver->user?->email ?? '',
                'phone'     => $driver->phone ?? null,
                'carrier'   => $driver->carrier ? ['id' => $driver->carrier->id, 'name' => $driver->carrier->name] : null,
                'license'   => $driver->licenses->first() ? [
                    'number'  => $driver->licenses->first()->license_number,
                    'class'   => $driver->licenses->first()->license_class,
                    'expires' => $driver->licenses->first()->expiration_date?->format('Y-m-d'),
                ] : null,
            ],
            'testTypes'    => DriverTesting::getTestTypes(),
            'locations'    => DriverTesting::getLocations(),
            'statuses'     => DriverTesting::getStatuses(),
            'testResults'  => DriverTesting::getTestResults(),
            'billOptions'  => DriverTesting::getBillOptions(),
            'administrators' => DriverTesting::getAdministrators(),
        ]);
    }

    public function store(Request $request, UserDriverDetail $driver)
    {
        $validated = $request->validate([
            'test_type'                    => 'required|string',
            'administered_by'              => 'required|string|max:255',
            'test_date'                    => 'required|date',
            'location'                     => 'required|string|max:255',
            'requester_name'               => 'required|string|max:255',
            'mro'                          => 'nullable|string|max:255',
            'scheduled_time'               => 'nullable|date',
            'test_result'                  => 'nullable|in:Positive,Negative,Refusal',
            'status'                       => 'nullable|in:Schedule,In Progress,Pending Review,Completed,Cancelled',
            'next_test_due'                => 'nullable|date',
            'bill_to'                      => 'nullable|in:company,employee',
            'notes'                        => 'nullable|string',
            'is_random_test'               => 'boolean',
            'is_post_accident_test'        => 'boolean',
            'is_reasonable_suspicion_test' => 'boolean',
            'is_pre_employment_test'       => 'boolean',
            'is_follow_up_test'            => 'boolean',
            'is_return_to_duty_test'       => 'boolean',
            'is_other_reason_test'         => 'boolean',
            'other_reason_description'     => 'nullable|string|max:500',
            'attachments'                  => 'nullable|array',
            'attachments.*'                => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $testing = DriverTesting::create([
            'user_driver_detail_id'        => $driver->id,
            'carrier_id'                   => $driver->carrier_id,
            'test_type'                    => $validated['test_type'],
            'administered_by'              => $validated['administered_by'],
            'test_date'                    => $validated['test_date'],
            'location'                     => $validated['location'],
            'requester_name'               => $validated['requester_name'],
            'mro'                          => $validated['mro'] ?? null,
            'scheduled_time'               => $validated['scheduled_time'] ?? null,
            'test_result'                  => $validated['test_result'] ?? null,
            'status'                       => $validated['status'] ?? 'Schedule',
            'next_test_due'                => $validated['next_test_due'] ?? null,
            'bill_to'                      => $validated['bill_to'] ?? null,
            'notes'                        => $validated['notes'] ?? null,
            'is_random_test'               => $request->boolean('is_random_test'),
            'is_post_accident_test'        => $request->boolean('is_post_accident_test'),
            'is_reasonable_suspicion_test' => $request->boolean('is_reasonable_suspicion_test'),
            'is_pre_employment_test'       => $request->boolean('is_pre_employment_test'),
            'is_follow_up_test'            => $request->boolean('is_follow_up_test'),
            'is_return_to_duty_test'       => $request->boolean('is_return_to_duty_test'),
            'is_other_reason_test'         => $request->boolean('is_other_reason_test'),
            'other_reason_description'     => $validated['other_reason_description'] ?? null,
            'created_by'                   => Auth::id(),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $testing->addMedia($file)->toMediaCollection('document_attachments');
            }
        }

        return redirect()
            ->route('admin.drivers.show', $driver)
            ->with('success', 'Drug/Alcohol test record created successfully.');
    }

    public function edit(UserDriverDetail $driver, DriverTesting $testing): Response
    {
        $driver->load(['user:id,name,email', 'carrier:id,name', 'licenses:id,user_driver_detail_id,license_number,license_class,expiration_date']);

        $attachments = $testing->getMedia('document_attachments')->map(fn ($m) => [
            'id'   => $m->id,
            'name' => $m->file_name,
            'url'  => $m->getUrl(),
            'size' => round($m->size / 1024, 1) . ' KB',
        ])->toArray();

        return Inertia::render('admin/drivers/testings/Edit', [
            'driver' => [
                'id'        => $driver->id,
                'full_name' => trim(($driver->user?->name ?? 'Unknown') . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? '')),
                'email'     => $driver->user?->email ?? '',
                'phone'     => $driver->phone ?? null,
                'carrier'   => $driver->carrier ? ['id' => $driver->carrier->id, 'name' => $driver->carrier->name] : null,
                'license'   => $driver->licenses->first() ? [
                    'number'  => $driver->licenses->first()->license_number,
                    'class'   => $driver->licenses->first()->license_class,
                    'expires' => $driver->licenses->first()->expiration_date?->format('Y-m-d'),
                ] : null,
            ],
            'testing' => [
                'id'                           => $testing->id,
                'test_type'                    => $testing->test_type,
                'administered_by'              => $testing->administered_by,
                'test_date'                    => $testing->test_date?->format('Y-m-d'),
                'location'                     => $testing->location,
                'requester_name'               => $testing->requester_name,
                'mro'                          => $testing->mro,
                'scheduled_time'               => $testing->scheduled_time?->format('Y-m-d\TH:i'),
                'test_result'                  => $testing->test_result,
                'status'                       => $testing->status,
                'next_test_due'                => $testing->next_test_due?->format('Y-m-d'),
                'bill_to'                      => $testing->bill_to,
                'notes'                        => $testing->notes,
                'is_random_test'               => $testing->is_random_test,
                'is_post_accident_test'        => $testing->is_post_accident_test,
                'is_reasonable_suspicion_test' => $testing->is_reasonable_suspicion_test,
                'is_pre_employment_test'       => $testing->is_pre_employment_test,
                'is_follow_up_test'            => $testing->is_follow_up_test,
                'is_return_to_duty_test'       => $testing->is_return_to_duty_test,
                'is_other_reason_test'         => $testing->is_other_reason_test,
                'other_reason_description'     => $testing->other_reason_description,
                'attachments'                  => $attachments,
            ],
            'testTypes'      => DriverTesting::getTestTypes(),
            'locations'      => DriverTesting::getLocations(),
            'statuses'       => DriverTesting::getStatuses(),
            'testResults'    => DriverTesting::getTestResults(),
            'billOptions'    => DriverTesting::getBillOptions(),
            'administrators' => DriverTesting::getAdministrators(),
        ]);
    }

    public function update(Request $request, UserDriverDetail $driver, DriverTesting $testing)
    {
        $validated = $request->validate([
            'test_type'                    => 'required|string',
            'administered_by'              => 'required|string|max:255',
            'test_date'                    => 'required|date',
            'location'                     => 'required|string|max:255',
            'requester_name'               => 'required|string|max:255',
            'mro'                          => 'nullable|string|max:255',
            'scheduled_time'               => 'nullable|date',
            'test_result'                  => 'nullable|in:Positive,Negative,Refusal',
            'status'                       => 'nullable|in:Schedule,In Progress,Pending Review,Completed,Cancelled',
            'next_test_due'                => 'nullable|date',
            'bill_to'                      => 'nullable|in:company,employee',
            'notes'                        => 'nullable|string',
            'is_random_test'               => 'boolean',
            'is_post_accident_test'        => 'boolean',
            'is_reasonable_suspicion_test' => 'boolean',
            'is_pre_employment_test'       => 'boolean',
            'is_follow_up_test'            => 'boolean',
            'is_return_to_duty_test'       => 'boolean',
            'is_other_reason_test'         => 'boolean',
            'other_reason_description'     => 'nullable|string|max:500',
            'attachments'                  => 'nullable|array',
            'attachments.*'                => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'delete_attachments'           => 'nullable|array',
            'delete_attachments.*'         => 'integer',
        ]);

        $testing->update([
            'test_type'                    => $validated['test_type'],
            'administered_by'              => $validated['administered_by'],
            'test_date'                    => $validated['test_date'],
            'location'                     => $validated['location'],
            'requester_name'               => $validated['requester_name'],
            'mro'                          => $validated['mro'] ?? null,
            'scheduled_time'               => $validated['scheduled_time'] ?? null,
            'test_result'                  => $validated['test_result'] ?? null,
            'status'                       => $validated['status'] ?? $testing->status,
            'next_test_due'                => $validated['next_test_due'] ?? null,
            'bill_to'                      => $validated['bill_to'] ?? null,
            'notes'                        => $validated['notes'] ?? null,
            'is_random_test'               => $request->boolean('is_random_test'),
            'is_post_accident_test'        => $request->boolean('is_post_accident_test'),
            'is_reasonable_suspicion_test' => $request->boolean('is_reasonable_suspicion_test'),
            'is_pre_employment_test'       => $request->boolean('is_pre_employment_test'),
            'is_follow_up_test'            => $request->boolean('is_follow_up_test'),
            'is_return_to_duty_test'       => $request->boolean('is_return_to_duty_test'),
            'is_other_reason_test'         => $request->boolean('is_other_reason_test'),
            'other_reason_description'     => $validated['other_reason_description'] ?? null,
            'updated_by'                   => Auth::id(),
        ]);

        // Delete selected attachments
        if (!empty($validated['delete_attachments'])) {
            foreach ($validated['delete_attachments'] as $mediaId) {
                $media = $testing->getMedia('document_attachments')->find($mediaId);
                $media?->delete();
            }
        }

        // Add new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $testing->addMedia($file)->toMediaCollection('document_attachments');
            }
        }

        return redirect()
            ->route('admin.drivers.show', $driver)
            ->with('success', 'Test record updated successfully.');
    }

    public function destroy(UserDriverDetail $driver, DriverTesting $testing)
    {
        $testing->clearMediaCollection('document_attachments');
        $testing->clearMediaCollection('drug_test_pdf');
        $testing->clearMediaCollection('test_results');
        $testing->delete();

        return redirect()
            ->route('admin.drivers.show', $driver)
            ->with('success', 'Test record deleted successfully.');
    }
}
