<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverTesting;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DriverTestingController extends Controller
{
    public function index(Request $request): Response
    {
        $driver = $this->resolveDriver();

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', ''),
            'result' => (string) $request->input('result', ''),
        ];

        $query = DriverTesting::query()
            ->where('user_driver_detail_id', $driver->id)
            ->with(['carrier:id,name'])
            ->orderByDesc('test_date')
            ->orderByDesc('id');

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';

            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('administered_by', 'like', $term)
                    ->orWhere('requester_name', 'like', $term)
                    ->orWhere('mro', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['result'] !== '') {
            $query->where('test_result', $filters['result']);
        }

        $testTypes = DriverTesting::getTestTypes();

        $testings = $query
            ->paginate(12)
            ->withQueryString();

        $testings->through(function (DriverTesting $testing) use ($testTypes) {
            return [
                'id' => $testing->id,
                'test_date' => $testing->test_date?->format('n/j/Y'),
                'test_type' => $testing->test_type,
                'test_type_label' => $testTypes[$testing->test_type] ?? $testing->test_type,
                'status' => $testing->status,
                'test_result' => $testing->test_result,
                'location' => $testing->location,
                'administered_by' => $testing->administered_by,
                'requester_name' => $testing->requester_name,
                'next_test_due' => $testing->next_test_due?->format('n/j/Y'),
                'has_pdf' => $testing->hasMedia('drug_test_pdf'),
                'has_results' => $testing->getMedia('test_results')->isNotEmpty(),
                'reasons' => $this->testingReasons($testing),
            ];
        });

        $statsQuery = DriverTesting::query()->where('user_driver_detail_id', $driver->id);

        return Inertia::render('driver/testing/Index', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'filters' => $filters,
            'testings' => $testings,
            'statuses' => DriverTesting::getStatuses(),
            'results' => DriverTesting::getTestResults(),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'completed' => (clone $statsQuery)->where('status', 'Completed')->count(),
                'pending_review' => (clone $statsQuery)->where('status', 'Pending Review')->count(),
                'positive' => (clone $statsQuery)->where('test_result', 'Positive')->count(),
            ],
        ]);
    }

    public function show(DriverTesting $testing): Response
    {
        $driver = $this->resolveDriver();
        $this->authorizeTesting($driver, $testing);

        $testing->load([
            'carrier:id,name,dot_number,mc_number',
            'userDriverDetail.user:id,name,email',
            'userDriverDetail.carrier:id,name',
            'userDriverDetail.licenses' => fn ($query) => $query->where('status', 'active')->orderByDesc('created_at'),
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        $history = DriverTesting::query()
            ->where('user_driver_detail_id', $driver->id)
            ->whereKeyNot($testing->id)
            ->orderByDesc('test_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (DriverTesting $item) => [
                'id' => $item->id,
                'test_date' => $item->test_date?->format('n/j/Y'),
                'test_type' => DriverTesting::getTestTypes()[$item->test_type] ?? $item->test_type,
                'status' => $item->status,
                'test_result' => $item->test_result,
            ])
            ->values();

        $license = $testing->userDriverDetail?->licenses->first();
        $pdfMedia = $testing->getFirstMedia('drug_test_pdf');

        return Inertia::render('driver/testing/Show', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
                'email' => $driver->user?->email,
                'phone' => $driver->phone,
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
            'testing' => [
                'id' => $testing->id,
                'test_type' => $testing->test_type,
                'test_type_label' => DriverTesting::getTestTypes()[$testing->test_type] ?? $testing->test_type,
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
                'reasons' => $this->testingReasons($testing),
                'other_reason_description' => $testing->other_reason_description,
                'created_at' => $testing->created_at?->format('n/j/Y g:i A'),
                'updated_at' => $testing->updated_at?->format('n/j/Y g:i A'),
                'created_by' => $testing->createdBy?->name,
                'updated_by' => $testing->updatedBy?->name,
                'pdf' => $pdfMedia ? $this->mediaPayload($pdfMedia, 'Authorization PDF') : null,
                'result_documents' => $testing->getMedia('test_results')->map(fn (Media $media) => $this->mediaPayload($media, 'Result File'))->values(),
                'certificate_documents' => $testing->getMedia('test_certificates')->map(fn (Media $media) => $this->mediaPayload($media, 'Certificate'))->values(),
                'attachments' => $testing->getMedia('document_attachments')->map(fn (Media $media) => $this->mediaPayload($media, 'Attachment'))->values(),
            ],
            'history' => $history,
        ]);
    }

    public function uploadResults(Request $request, DriverTesting $testing): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTesting($driver, $testing);

        $validated = $request->validate([
            'results' => ['required', 'array', 'min:1'],
            'results.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        foreach ($validated['results'] as $file) {
            $testing->addMedia($file)->toMediaCollection('test_results');
        }

        if ($testing->status !== 'Completed') {
            $testing->forceFill(['status' => 'Pending Review'])->save();
        }

        return redirect()
            ->route('driver.testing.show', $testing)
            ->with('success', 'Results uploaded successfully. The test is now pending review.');
    }

    protected function resolveDriver(): UserDriverDetail
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        $driver->loadMissing(['user:id,name,email', 'carrier:id,name']);

        return $driver;
    }

    protected function authorizeTesting(UserDriverDetail $driver, DriverTesting $testing): void
    {
        abort_unless((int) $testing->user_driver_detail_id === (int) $driver->id, 403, 'Unauthorized testing record.');
    }

    protected function testingReasons(DriverTesting $testing): array
    {
        return collect([
            ['active' => (bool) $testing->is_random_test, 'label' => 'Random'],
            ['active' => (bool) $testing->is_post_accident_test, 'label' => 'Post Accident'],
            ['active' => (bool) $testing->is_reasonable_suspicion_test, 'label' => 'Reasonable Suspicion'],
            ['active' => (bool) $testing->is_pre_employment_test, 'label' => 'Pre-Employment'],
            ['active' => (bool) $testing->is_follow_up_test, 'label' => 'Follow-Up'],
            ['active' => (bool) $testing->is_return_to_duty_test, 'label' => 'Return-To-Duty'],
            ['active' => (bool) $testing->is_other_reason_test, 'label' => 'Other'],
        ])->filter(fn (array $reason) => $reason['active'])->values()->all();
    }

    protected function mediaPayload(Media $media, string $label): array
    {
        return [
            'id' => $media->id,
            'label' => $label,
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'mime_type' => $media->mime_type,
            'size_label' => $media->human_readable_size,
            'created_at' => $media->created_at?->format('n/j/Y g:i A'),
            'extension' => strtolower((string) pathinfo($media->file_name, PATHINFO_EXTENSION)),
        ];
    }
}
