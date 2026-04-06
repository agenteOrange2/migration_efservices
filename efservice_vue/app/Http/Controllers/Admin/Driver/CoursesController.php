<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Helpers\Constants;
use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverCourse;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CoursesController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => (string) $request->input('carrier_filter', ''),
            'driver_filter' => (string) $request->input('driver_filter', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'status' => (string) $request->input('status', ''),
            'sort_field' => (string) $request->input('sort_field', 'certification_date'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $query = DriverCourse::query()
            ->with(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name']);

        if ($filters['search_term'] !== '') {
            $search = '%' . $filters['search_term'] . '%';

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('organization_name', 'like', $search)
                    ->orWhere('experience', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhere('state', 'like', $search)
                    ->orWhereHas('driverDetail.user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    })
                    ->orWhereHas('driverDetail', function ($driverQuery) use ($search) {
                        $driverQuery
                            ->where('middle_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search)
                            ->orWhere('phone', 'like', $search);
                    });
            });
        }

        if ($filters['driver_filter'] !== '') {
            $query->where('user_driver_detail_id', $filters['driver_filter']);
        }

        if ($filters['carrier_filter'] !== '') {
            $query->whereHas('driverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $filters['carrier_filter']));
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('certification_date', '>=', $this->toDbDate($filters['date_from']));
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('certification_date', '<=', $this->toDbDate($filters['date_to']));
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $allowedSortFields = ['certification_date', 'expiration_date', 'organization_name', 'created_at'];
        $sortField = in_array($filters['sort_field'], $allowedSortFields, true) ? $filters['sort_field'] : 'certification_date';
        $sortDirection = $filters['sort_direction'] === 'asc' ? 'asc' : 'desc';

        $courses = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        $documentCounts = $this->documentCounts($courses->pluck('id')->all());

        $courses->through(fn (DriverCourse $course) => $this->transformCourse($course, $documentCounts[$course->id] ?? 0));

        return Inertia::render('admin/courses/Index', [
            'courses' => $courses,
            'filters' => $filters,
            'drivers' => $this->driverOptions(),
            'carriers' => $this->carrierOptions(),
            'stats' => [
                'total' => DriverCourse::count(),
                'active' => DriverCourse::query()->where('status', 'active')->count(),
                'inactive' => DriverCourse::query()->where('status', 'inactive')->count(),
                'documents' => Media::query()
                    ->where('model_type', DriverCourse::class)
                    ->where('collection_name', 'course_certificates')
                    ->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/courses/Create', [
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
            'states' => Constants::usStates(),
            'organizationOptions' => $this->organizationOptions(),
            'selectedDriverId' => $request->filled('driver_id') ? (string) $request->input('driver_id') : '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($request, $validated) {
            $course = DriverCourse::create($this->coursePayload($validated));
            $this->syncMedia($course, $request);
        });

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function edit(DriverCourse $course): Response
    {
        $course->load(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name']);

        $organizationOptions = $this->organizationOptions();
        $knownOrganizations = array_keys($organizationOptions);
        $isCustomOrganization = ! in_array($course->organization_name, $knownOrganizations, true);

        return Inertia::render('admin/courses/Edit', [
            'course' => [
                'id' => $course->id,
                'carrier_id' => $course->driverDetail?->carrier_id ? (string) $course->driverDetail->carrier_id : '',
                'user_driver_detail_id' => (string) $course->user_driver_detail_id,
                'organization_name' => $isCustomOrganization ? 'Other' : $course->organization_name,
                'organization_name_other' => $isCustomOrganization ? $course->organization_name : '',
                'city' => $course->city,
                'state' => $course->state,
                'certification_date' => $course->certification_date?->format('n/j/Y'),
                'expiration_date' => $course->expiration_date?->format('n/j/Y'),
                'experience' => $course->experience,
                'status' => $course->status ?: 'active',
                'documents' => $this->documentsPayload($course),
            ],
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
            'states' => Constants::usStates(),
            'organizationOptions' => $organizationOptions,
        ]);
    }

    public function update(Request $request, DriverCourse $course): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($request, $validated, $course) {
            $course->update($this->coursePayload($validated));
            $this->syncMedia($course, $request);
        });

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(DriverCourse $course): RedirectResponse
    {
        DB::transaction(function () use ($course) {
            $course->clearMediaCollection('course_certificates');
            $course->delete();
        });

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    public function getAllDocuments(Request $request): Response
    {
        return $this->renderDocumentsPage($request);
    }

    public function getFiles(Request $request, DriverCourse $course): Response
    {
        $course->load(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name']);

        return $this->renderDocumentsPage($request, $course);
    }

    public function driverHistory(UserDriverDetail $driver, Request $request): Response
    {
        $driver->load(['user:id,name,email', 'carrier:id,name']);

        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'status' => (string) $request->input('status', ''),
            'sort_field' => (string) $request->input('sort_field', 'certification_date'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $query = DriverCourse::query()->where('user_driver_detail_id', $driver->id);

        if ($filters['search_term'] !== '') {
            $search = '%' . $filters['search_term'] . '%';
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('organization_name', 'like', $search)
                    ->orWhere('experience', 'like', $search)
                    ->orWhere('city', 'like', $search)
                    ->orWhere('state', 'like', $search);
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $allowedSortFields = ['certification_date', 'expiration_date', 'organization_name', 'created_at'];
        $sortField = in_array($filters['sort_field'], $allowedSortFields, true) ? $filters['sort_field'] : 'certification_date';
        $sortDirection = $filters['sort_direction'] === 'asc' ? 'asc' : 'desc';

        $courses = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        $documentCounts = $this->documentCounts($courses->pluck('id')->all());

        $courses->through(fn (DriverCourse $course) => $this->transformCourse($course, $documentCounts[$course->id] ?? 0));

        $expiringSoonCount = DriverCourse::query()
            ->where('user_driver_detail_id', $driver->id)
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->count();

        return Inertia::render('admin/courses/DriverHistory', [
            'driver' => [
                'id' => $driver->id,
                'name' => $this->driverFullName($driver),
                'email' => $driver->user?->email,
                'carrier_name' => $driver->carrier?->name,
                'photo_url' => $driver->getFirstMediaUrl('profile_photo_driver') ?: null,
            ],
            'courses' => $courses,
            'filters' => $filters,
            'stats' => [
                'total' => DriverCourse::query()->where('user_driver_detail_id', $driver->id)->count(),
                'active' => DriverCourse::query()->where('user_driver_detail_id', $driver->id)->where('status', 'active')->count(),
                'inactive' => DriverCourse::query()->where('user_driver_detail_id', $driver->id)->where('status', 'inactive')->count(),
                'expiring_soon' => $expiringSoonCount,
            ],
        ]);
    }

    public function destroyMedia(Media $document): RedirectResponse
    {
        abort_unless(
            $document->model_type === DriverCourse::class
            && $document->collection_name === 'course_certificates',
            404
        );

        $document->delete();

        return back()->with('success', 'Course document deleted successfully.');
    }

    protected function renderDocumentsPage(Request $request, ?DriverCourse $course = null): Response
    {
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'course_filter' => (string) $request->input('course_filter', $course?->id ? (string) $course->id : ''),
            'driver_filter' => (string) $request->input('driver_filter', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'file_type' => (string) $request->input('file_type', ''),
        ];

        $query = Media::query()
            ->where('model_type', DriverCourse::class)
            ->where('collection_name', 'course_certificates');

        if ($course) {
            $query->where('model_id', $course->id);
        }

        if ($filters['search_term'] !== '') {
            $search = '%' . $filters['search_term'] . '%';
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('file_name', 'like', $search)
                    ->orWhere('mime_type', 'like', $search)
                    ->orWhereHasMorph('model', [DriverCourse::class], function ($modelQuery) use ($search) {
                        $modelQuery
                            ->where('organization_name', 'like', $search)
                            ->orWhere('city', 'like', $search)
                            ->orWhere('state', 'like', $search)
                            ->orWhereHas('driverDetail.user', function ($userQuery) use ($search) {
                                $userQuery
                                    ->where('name', 'like', $search)
                                    ->orWhere('email', 'like', $search);
                            });
                    });
            });
        }

        if (! $course && $filters['course_filter'] !== '') {
            $query->where('model_id', $filters['course_filter']);
        }

        if ($filters['driver_filter'] !== '') {
            $query->whereHasMorph('model', [DriverCourse::class], function ($modelQuery) use ($filters) {
                $modelQuery->where('user_driver_detail_id', $filters['driver_filter']);
            });
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $this->toDbDate($filters['date_from']));
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $this->toDbDate($filters['date_to']));
        }

        if ($filters['file_type'] !== '') {
            match ($filters['file_type']) {
                'pdf' => $query->where('mime_type', 'like', '%pdf%'),
                'image' => $query->where('mime_type', 'like', 'image/%'),
                'doc' => $query->where(function ($builder) {
                    $builder
                        ->where('mime_type', 'like', '%word%')
                        ->orWhere('mime_type', 'like', '%document%')
                        ->orWhere('file_name', 'like', '%.doc')
                        ->orWhere('file_name', 'like', '%.docx');
                }),
                default => null,
            };
        }

        $documents = $query
            ->with('model')
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $documents->getCollection()->loadMorph('model', [
            DriverCourse::class => ['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name'],
        ]);

        $documents->through(function (Media $media) {
            /** @var DriverCourse|null $record */
            $record = $media->model;

            return [
                'id' => $media->id,
                'course_id' => $record?->id,
                'organization_name' => $record?->organization_name,
                'driver_name' => $this->driverFullName($record?->driverDetail),
                'carrier_name' => $record?->driverDetail?->carrier?->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'file_type' => $this->resolveFileType($media),
                'size_label' => $media->human_readable_size,
                'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
                'preview_url' => $media->getUrl(),
            ];
        });

        $statsQuery = clone $query;

        return Inertia::render('admin/courses/Documents', [
            'documents' => $documents,
            'filters' => $filters,
            'course' => $course ? [
                'id' => $course->id,
                'organization_name' => $course->organization_name,
                'driver_name' => $this->driverFullName($course->driverDetail),
                'carrier_name' => $course->driverDetail?->carrier?->name,
            ] : null,
            'courses' => DriverCourse::query()
                ->with(['driverDetail.user:id,name,email'])
                ->orderBy('organization_name')
                ->get()
                ->map(fn (DriverCourse $item) => [
                    'id' => $item->id,
                    'organization_name' => $item->organization_name,
                    'driver_name' => $this->driverFullName($item->driverDetail),
                ]),
            'drivers' => $this->driverOptions(),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'pdf' => (clone $statsQuery)->where('mime_type', 'like', '%pdf%')->count(),
                'images' => (clone $statsQuery)->where('mime_type', 'like', 'image/%')->count(),
                'docs' => (clone $statsQuery)->where(function ($builder) {
                    $builder
                        ->where('mime_type', 'like', '%word%')
                        ->orWhere('mime_type', 'like', '%document%')
                        ->orWhere('file_name', 'like', '%.doc')
                        ->orWhere('file_name', 'like', '%.docx');
                })->count(),
            ],
        ]);
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'user_driver_detail_id' => ['required', 'exists:user_driver_details,id'],
            'organization_name' => ['required', 'string', 'max:255'],
            'organization_name_other' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:10'],
            'certification_date' => ['nullable', 'date_format:n/j/Y'],
            'experience' => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date_format:n/j/Y', 'after_or_equal:certification_date'],
            'status' => ['required', 'in:active,inactive'],
            'course_documents' => ['nullable', 'array'],
            'course_documents.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
        ]);
    }

    protected function coursePayload(array $validated): array
    {
        $organizationName = $validated['organization_name'] === 'Other' && ! empty($validated['organization_name_other'])
            ? $validated['organization_name_other']
            : $validated['organization_name'];

        return [
            'user_driver_detail_id' => $validated['user_driver_detail_id'],
            'organization_name' => $organizationName,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'certification_date' => $validated['certification_date'] ? $this->toDbDate($validated['certification_date']) : null,
            'experience' => $validated['experience'] ?? null,
            'expiration_date' => $validated['expiration_date'] ? $this->toDbDate($validated['expiration_date']) : null,
            'status' => $validated['status'],
        ];
    }

    protected function syncMedia(DriverCourse $course, Request $request): void
    {
        foreach ($request->file('course_documents', []) as $document) {
            $course->addMedia($document)
                ->usingName(pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('course_certificates');
        }
    }

    protected function transformCourse(DriverCourse $course, int $documentCount): array
    {
        $expirationDate = $course->expiration_date;
        $daysUntilExpiration = $expirationDate ? now()->startOfDay()->diffInDays($expirationDate, false) : null;

        return [
            'id' => $course->id,
            'organization_name' => $course->organization_name,
            'city' => $course->city,
            'state' => $course->state,
            'experience' => $course->experience,
            'certification_date' => $course->certification_date?->format('n/j/Y'),
            'expiration_date' => $course->expiration_date?->format('n/j/Y'),
            'status' => $course->status ?: 'inactive',
            'document_count' => $documentCount,
            'days_until_expiration' => $daysUntilExpiration,
            'driver' => $course->driverDetail ? [
                'id' => $course->driverDetail->id,
                'name' => $this->driverFullName($course->driverDetail),
                'email' => $course->driverDetail->user?->email,
                'photo_url' => $course->driverDetail->getFirstMediaUrl('profile_photo_driver') ?: null,
            ] : null,
            'carrier' => $course->driverDetail?->carrier ? [
                'id' => $course->driverDetail->carrier->id,
                'name' => $course->driverDetail->carrier->name,
            ] : null,
        ];
    }

    protected function documentsPayload(DriverCourse $course): array
    {
        return $course->getMedia('course_certificates')
            ->map(fn (Media $media) => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'file_type' => $this->resolveFileType($media),
                'size_label' => $media->human_readable_size,
                'preview_url' => $media->getUrl(),
                'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
            ])
            ->values()
            ->all();
    }

    protected function documentCounts(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        return Media::query()
            ->selectRaw('model_id, COUNT(*) as aggregate')
            ->where('model_type', DriverCourse::class)
            ->where('collection_name', 'course_certificates')
            ->whereIn('model_id', $courseIds)
            ->groupBy('model_id')
            ->pluck('aggregate', 'model_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    protected function carrierOptions()
    {
        return Carrier::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Carrier $carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ]);
    }

    protected function driverOptions()
    {
        return UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name'])
            ->whereHas('application', function ($query) {
                $query->where('status', DriverApplication::STATUS_APPROVED);
            })
            ->orderBy('carrier_id')
            ->orderBy('last_name')
            ->get()
            ->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->id,
                'carrier_id' => $driver->carrier_id,
                'carrier_name' => $driver->carrier?->name,
                'name' => $this->driverFullName($driver),
                'email' => $driver->user?->email,
            ]);
    }

    protected function driverFullName(?UserDriverDetail $driver): string
    {
        if (! $driver) {
            return 'N/A';
        }

        return trim(implode(' ', array_filter([
            $driver->user?->name,
            $driver->middle_name,
            $driver->last_name,
        ]))) ?: 'N/A';
    }

    protected function resolveFileType(Media $media): string
    {
        $extension = Str::lower(pathinfo($media->file_name, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
            return 'image';
        }

        if ($extension === 'pdf') {
            return 'pdf';
        }

        if (in_array($extension, ['doc', 'docx'], true)) {
            return 'doc';
        }

        return $extension ?: 'file';
    }

    protected function organizationOptions(): array
    {
        return [
            'H2S' => 'H2S',
            'PEC' => 'PEC',
            'SANDTRAX' => 'SANDTRAX',
            'OSHA10' => 'OSHA10',
            'OSHA30' => 'OSHA30',
            'Other' => 'Other',
        ];
    }

    protected function toDbDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches) === 1) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[1], (int) $matches[2]);
        }

        return $value;
    }
}
