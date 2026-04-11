<?php

namespace App\Http\Controllers\Carrier;

use App\Helpers\Constants;
use App\Http\Controllers\Admin\Driver\CoursesController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Driver\DriverCourse;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierCoursesController extends CoursesController
{
    use ResolvesCarrierContext;

    public function index(Request $request): Response
    {
        $carrier = $this->resolveCarrier();
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => '',
            'driver_filter' => (string) $request->input('driver_filter', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'status' => (string) $request->input('status', ''),
            'sort_field' => (string) $request->input('sort_field', 'certification_date'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $query = DriverCourse::query()
            ->with(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name'])
            ->whereHas('driverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id));

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

        $statsQuery = DriverCourse::query()
            ->whereHas('driverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id));

        return Inertia::render('carrier/courses/Index', [
            'courses' => $courses,
            'filters' => $filters,
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carriers' => [self::carrierOption($carrier)],
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'active' => (clone $statsQuery)->where('status', 'active')->count(),
                'inactive' => (clone $statsQuery)->where('status', 'inactive')->count(),
                'documents' => Media::query()
                    ->where('model_type', DriverCourse::class)
                    ->where('collection_name', 'course_certificates')
                    ->whereIn('model_id', (clone $statsQuery)->pluck('id'))
                    ->count(),
            ],
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create(Request $request): Response
    {
        $carrier = $this->resolveCarrier();

        return Inertia::render('carrier/courses/Create', [
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'states' => Constants::usStates(),
            'organizationOptions' => $this->organizationOptions(),
            'selectedDriverId' => $request->filled('driver_id') ? (string) $request->input('driver_id') : '',
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $carrierId = (int) $this->resolveCarrier()->id;
        $validated = $this->validatePayload($request);
        $this->ensureCarrierOwnsDriver((int) $validated['user_driver_detail_id'], $carrierId);

        DB::transaction(function () use ($request, $validated) {
            $course = DriverCourse::create($this->coursePayload($validated));
            $this->syncMedia($course, $request);
        });

        return redirect()
            ->route('carrier.courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function edit(DriverCourse $course): Response
    {
        $this->authorizeCarrierCourse($course);
        $carrier = $this->resolveCarrier();
        $course->load(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name']);

        $organizationOptions = $this->organizationOptions();
        $knownOrganizations = array_keys($organizationOptions);
        $isCustomOrganization = ! in_array($course->organization_name, $knownOrganizations, true);

        return Inertia::render('carrier/courses/Edit', [
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
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'states' => Constants::usStates(),
            'organizationOptions' => $organizationOptions,
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function update(Request $request, DriverCourse $course): RedirectResponse
    {
        $this->authorizeCarrierCourse($course);
        $carrierId = (int) $this->resolveCarrier()->id;
        $validated = $this->validatePayload($request);
        $this->ensureCarrierOwnsDriver((int) $validated['user_driver_detail_id'], $carrierId);

        DB::transaction(function () use ($request, $validated, $course) {
            $course->update($this->coursePayload($validated));
            $this->syncMedia($course, $request);
        });

        return redirect()
            ->route('carrier.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(DriverCourse $course): RedirectResponse
    {
        $this->authorizeCarrierCourse($course);

        DB::transaction(function () use ($course) {
            $course->clearMediaCollection('course_certificates');
            $course->delete();
        });

        return redirect()
            ->route('carrier.courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    public function documents(Request $request): Response
    {
        return $this->renderCarrierDocumentsPage($request);
    }

    public function showDocuments(Request $request, DriverCourse $course): Response
    {
        $this->authorizeCarrierCourse($course);
        $course->load(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name']);

        return $this->renderCarrierDocumentsPage($request, $course);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless(
            $media->model_type === DriverCourse::class
            && $media->collection_name === 'course_certificates',
            404
        );

        $course = DriverCourse::query()->findOrFail($media->model_id);
        $this->authorizeCarrierCourse($course);
        $media->delete();

        return back()->with('success', 'Course document deleted successfully.');
    }

    protected function renderCarrierDocumentsPage(Request $request, ?DriverCourse $course = null): Response
    {
        $carrier = $this->resolveCarrier();
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
            ->where('collection_name', 'course_certificates')
            ->whereHasMorph('model', [DriverCourse::class], function ($modelQuery) use ($carrier) {
                $modelQuery->whereHas('driverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrier->id));
            });

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

        return Inertia::render('carrier/courses/Documents', [
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
                ->whereHas('driverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id))
                ->orderBy('organization_name')
                ->get()
                ->map(fn (DriverCourse $item) => [
                    'id' => $item->id,
                    'organization_name' => $item->organization_name,
                    'driver_name' => $this->driverFullName($item->driverDetail),
                ]),
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
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
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    protected function ensureCarrierOwnsDriver(int $driverId, int $carrierId): void
    {
        $exists = UserDriverDetail::query()
            ->where('id', $driverId)
            ->where('carrier_id', $carrierId)
            ->exists();

        abort_unless($exists, 403);
    }

    protected function authorizeCarrierCourse(DriverCourse $course): void
    {
        $course->loadMissing('driverDetail');
        abort_unless((int) $course->driverDetail?->carrier_id === (int) $this->resolveCarrierId(), 403);
    }

    protected function carrierDriverOptions(int $carrierId)
    {
        return UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name'])
            ->where('carrier_id', $carrierId)
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

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.courses.index',
            'create' => 'carrier.courses.create',
            'store' => 'carrier.courses.store',
            'edit' => 'carrier.courses.edit',
            'update' => 'carrier.courses.update',
            'destroy' => 'carrier.courses.destroy',
            'documentsIndex' => 'carrier.courses.documents.index',
            'documentsShow' => 'carrier.courses.documents.show',
            'mediaDestroy' => 'carrier.courses.media.destroy',
        ];
    }

    protected static function carrierOption(Carrier $carrier): array
    {
        return [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ];
    }
}
