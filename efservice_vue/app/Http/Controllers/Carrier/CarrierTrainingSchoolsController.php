<?php

namespace App\Http\Controllers\Carrier;

use App\Helpers\Constants;
use App\Http\Controllers\Admin\Driver\TrainingSchoolsController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Driver\DriverTrainingSchool;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierTrainingSchoolsController extends TrainingSchoolsController
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
            'sort_field' => (string) $request->input('sort_field', 'created_at'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $query = DriverTrainingSchool::query()
            ->with(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name'])
            ->whereHas('userDriverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id));

        if ($filters['search_term'] !== '') {
            $searchTerm = '%' . $filters['search_term'] . '%';

            $query->where(function ($builder) use ($searchTerm) {
                $builder
                    ->where('school_name', 'like', $searchTerm)
                    ->orWhere('city', 'like', $searchTerm)
                    ->orWhere('state', 'like', $searchTerm)
                    ->orWhereHas('userDriverDetail.user', function ($userQuery) use ($searchTerm) {
                        $userQuery
                            ->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    })
                    ->orWhereHas('userDriverDetail', function ($driverQuery) use ($searchTerm) {
                        $driverQuery
                            ->where('middle_name', 'like', $searchTerm)
                            ->orWhere('last_name', 'like', $searchTerm)
                            ->orWhere('phone', 'like', $searchTerm);
                    });
            });
        }

        if ($filters['driver_filter'] !== '') {
            $query->where('user_driver_detail_id', $filters['driver_filter']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('date_start', '>=', $this->toDbDate($filters['date_from']));
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('date_end', '<=', $this->toDbDate($filters['date_to']));
        }

        $allowedSortFields = ['created_at', 'school_name', 'date_end'];
        $sortField = in_array($filters['sort_field'], $allowedSortFields, true) ? $filters['sort_field'] : 'created_at';
        $sortDirection = $filters['sort_direction'] === 'asc' ? 'asc' : 'desc';

        $schools = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        $documentCounts = $this->documentCounts($schools->pluck('id')->all());
        $schools->through(fn (DriverTrainingSchool $school) => $this->transformSchool($school, $documentCounts[$school->id] ?? 0));

        $statsQuery = DriverTrainingSchool::query()
            ->whereHas('userDriverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id));

        return Inertia::render('carrier/training-schools/Index', [
            'trainingSchools' => $schools,
            'filters' => $filters,
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carriers' => [self::carrierOption($carrier)],
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'graduated' => (clone $statsQuery)->where('graduated', true)->count(),
                'in_progress' => (clone $statsQuery)->where('graduated', false)->count(),
                'documents' => Media::query()
                    ->where('model_type', DriverTrainingSchool::class)
                    ->where('collection_name', 'school_certificates')
                    ->whereIn('model_id', (clone $statsQuery)->pluck('id'))
                    ->count(),
            ],
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create(): Response
    {
        $carrier = $this->resolveCarrier();

        return Inertia::render('carrier/training-schools/Create', [
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'states' => Constants::usStates(),
            'skillOptions' => $this->skillOptions(),
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

        DB::transaction(function () use ($request, $validated, $carrierId) {
            $payload = $this->schoolPayload($validated, $request);
            unset($payload['carrier_id']);

            $school = DriverTrainingSchool::create($payload);
            $this->syncMedia($school, $request);
        });

        return redirect()
            ->route('carrier.training-schools.index')
            ->with('success', 'Training school created successfully.');
    }

    public function show(DriverTrainingSchool $training_school): Response
    {
        $this->authorizeCarrierSchool($training_school);
        $training_school->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        return Inertia::render('carrier/training-schools/Show', [
            'school' => [
                'id' => $training_school->id,
                'school_name' => $training_school->school_name,
                'city' => $training_school->city,
                'state' => $training_school->state,
                'date_start' => $training_school->date_start?->format('n/j/Y'),
                'date_end' => $training_school->date_end?->format('n/j/Y'),
                'graduated' => (bool) $training_school->graduated,
                'subject_to_safety_regulations' => (bool) $training_school->subject_to_safety_regulations,
                'performed_safety_functions' => (bool) $training_school->performed_safety_functions,
                'training_skills' => $training_school->training_skills ?? [],
                'created_at' => $training_school->created_at?->format('n/j/Y g:i A'),
                'updated_at' => $training_school->updated_at?->format('n/j/Y g:i A'),
                'driver' => $training_school->userDriverDetail ? [
                    'id' => $training_school->userDriverDetail->id,
                    'name' => $this->driverFullName($training_school->userDriverDetail),
                    'email' => $training_school->userDriverDetail->user?->email,
                    'phone' => $training_school->userDriverDetail->phone,
                ] : null,
                'carrier' => $training_school->userDriverDetail?->carrier ? [
                    'id' => $training_school->userDriverDetail->carrier->id,
                    'name' => $training_school->userDriverDetail->carrier->name,
                ] : null,
                'documents' => $this->documentsPayload($training_school),
            ],
            'routeNames' => $this->routeNames(),
            'isCarrierContext' => true,
        ]);
    }

    public function edit(DriverTrainingSchool $training_school): Response
    {
        $this->authorizeCarrierSchool($training_school);
        $carrier = $this->resolveCarrier();
        $training_school->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        return Inertia::render('carrier/training-schools/Edit', [
            'school' => [
                'id' => $training_school->id,
                'carrier_id' => $training_school->userDriverDetail?->carrier_id,
                'user_driver_detail_id' => $training_school->user_driver_detail_id,
                'school_name' => $training_school->school_name,
                'city' => $training_school->city,
                'state' => $training_school->state,
                'date_start' => $training_school->date_start?->format('n/j/Y'),
                'date_end' => $training_school->date_end?->format('n/j/Y'),
                'graduated' => (bool) $training_school->graduated,
                'subject_to_safety_regulations' => (bool) $training_school->subject_to_safety_regulations,
                'performed_safety_functions' => (bool) $training_school->performed_safety_functions,
                'training_skills' => $training_school->training_skills ?? [],
                'driver_name' => $this->driverFullName($training_school->userDriverDetail),
                'documents' => $this->documentsPayload($training_school),
            ],
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'states' => Constants::usStates(),
            'skillOptions' => $this->skillOptions(),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function update(Request $request, DriverTrainingSchool $training_school): RedirectResponse
    {
        $this->authorizeCarrierSchool($training_school);
        $carrierId = (int) $this->resolveCarrier()->id;
        $validated = $this->validatePayload($request);
        $this->ensureCarrierOwnsDriver((int) $validated['user_driver_detail_id'], $carrierId);

        DB::transaction(function () use ($request, $validated, $training_school) {
            $payload = $this->schoolPayload($validated, $request);
            unset($payload['carrier_id']);

            $training_school->update($payload);
            $this->syncMedia($training_school, $request);
        });

        return redirect()
            ->route('carrier.training-schools.show', $training_school)
            ->with('success', 'Training school updated successfully.');
    }

    public function destroy(DriverTrainingSchool $training_school): RedirectResponse
    {
        $this->authorizeCarrierSchool($training_school);

        DB::transaction(function () use ($training_school) {
            $training_school->clearMediaCollection('school_certificates');
            $training_school->delete();
        });

        return redirect()
            ->route('carrier.training-schools.index')
            ->with('success', 'Training school deleted successfully.');
    }

    public function documents(Request $request): Response
    {
        $carrier = $this->resolveCarrier();

        return $this->renderCarrierDocumentsPage($request, $carrier);
    }

    public function showDocuments(Request $request, DriverTrainingSchool $training_school): Response
    {
        $this->authorizeCarrierSchool($training_school);
        $carrier = $this->resolveCarrier();
        $training_school->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        return $this->renderCarrierDocumentsPage($request, $carrier, $training_school);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless(
            $media->model_type === DriverTrainingSchool::class
            && $media->collection_name === 'school_certificates',
            404
        );

        $school = DriverTrainingSchool::query()->findOrFail($media->model_id);
        $this->authorizeCarrierSchool($school);
        $media->delete();

        return back()->with('success', 'Training school document deleted successfully.');
    }

    protected function renderCarrierDocumentsPage(Request $request, Carrier $carrier, ?DriverTrainingSchool $school = null): Response
    {
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => '',
            'school_filter' => (string) $request->input('school_filter', $school?->id ? (string) $school->id : ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'file_type' => (string) $request->input('file_type', ''),
        ];

        $query = Media::query()
            ->where('model_type', DriverTrainingSchool::class)
            ->where('collection_name', 'school_certificates')
            ->whereHasMorph('model', [DriverTrainingSchool::class], function ($modelQuery) use ($carrier) {
                $modelQuery->whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrier->id));
            });

        if ($school) {
            $query->where('model_id', $school->id);
        }

        if ($filters['search_term'] !== '') {
            $searchTerm = '%' . $filters['search_term'] . '%';
            $query->where(function ($builder) use ($searchTerm) {
                $builder
                    ->where('file_name', 'like', $searchTerm)
                    ->orWhere('mime_type', 'like', $searchTerm)
                    ->orWhereHasMorph('model', [DriverTrainingSchool::class], function ($modelQuery) use ($searchTerm) {
                        $modelQuery
                            ->where('school_name', 'like', $searchTerm)
                            ->orWhere('city', 'like', $searchTerm)
                            ->orWhereHas('userDriverDetail.user', function ($userQuery) use ($searchTerm) {
                                $userQuery
                                    ->where('name', 'like', $searchTerm)
                                    ->orWhere('email', 'like', $searchTerm);
                            });
                    });
            });
        }

        if (! $school && $filters['school_filter'] !== '') {
            $query->where('model_id', $filters['school_filter']);
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
            DriverTrainingSchool::class => ['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name'],
        ]);

        $documents->through(function (Media $media) {
            /** @var DriverTrainingSchool|null $record */
            $record = $media->model;

            return [
                'id' => $media->id,
                'school_id' => $record?->id,
                'school_name' => $record?->school_name,
                'carrier_name' => $record?->userDriverDetail?->carrier?->name,
                'driver_name' => $this->driverFullName($record?->userDriverDetail),
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'file_type' => $this->resolveFileType($media),
                'size' => (int) $media->size,
                'size_label' => $media->human_readable_size,
                'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
                'preview_url' => $media->getUrl(),
            ];
        });

        $statsQuery = clone $query;
        $schoolOptions = DriverTrainingSchool::query()
            ->with(['userDriverDetail.carrier:id,name'])
            ->whereHas('userDriverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id))
            ->orderBy('school_name')
            ->get()
            ->map(fn (DriverTrainingSchool $item) => [
                'id' => $item->id,
                'school_name' => $item->school_name,
                'carrier_name' => $item->userDriverDetail?->carrier?->name,
            ]);

        return Inertia::render('carrier/training-schools/Documents', [
            'documents' => $documents,
            'filters' => $filters,
            'carriers' => [self::carrierOption($carrier)],
            'schools' => $schoolOptions,
            'school' => $school ? [
                'id' => $school->id,
                'school_name' => $school->school_name,
                'driver_name' => $this->driverFullName($school->userDriverDetail),
                'carrier_name' => $school->userDriverDetail?->carrier?->name,
            ] : null,
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

    protected function authorizeCarrierSchool(DriverTrainingSchool $school): void
    {
        $school->loadMissing('userDriverDetail');
        abort_unless((int) $school->userDriverDetail?->carrier_id === (int) $this->resolveCarrierId(), 403);
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
            'index' => 'carrier.training-schools.index',
            'create' => 'carrier.training-schools.create',
            'store' => 'carrier.training-schools.store',
            'show' => 'carrier.training-schools.show',
            'edit' => 'carrier.training-schools.edit',
            'update' => 'carrier.training-schools.update',
            'destroy' => 'carrier.training-schools.destroy',
            'documentsIndex' => 'carrier.training-schools.documents.index',
            'documentsShow' => 'carrier.training-schools.documents.show',
            'mediaDestroy' => 'carrier.training-schools.media.destroy',
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
