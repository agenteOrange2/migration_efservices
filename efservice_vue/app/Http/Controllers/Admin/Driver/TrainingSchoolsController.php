<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Helpers\Constants;
use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverTrainingSchool;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TrainingSchoolsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => (string) $request->input('carrier_filter', ''),
            'driver_filter' => (string) $request->input('driver_filter', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'sort_field' => (string) $request->input('sort_field', 'created_at'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $query = DriverTrainingSchool::query()
            ->with(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

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

        if ($filters['carrier_filter'] !== '') {
            $query->whereHas('userDriverDetail', fn ($builder) => $builder->where('carrier_id', $filters['carrier_filter']));
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

        return Inertia::render('admin/training-schools/Index', [
            'trainingSchools' => $schools,
            'filters' => $filters,
            'drivers' => $this->driverOptions(),
            'carriers' => $this->carrierOptions(),
            'stats' => [
                'total' => DriverTrainingSchool::count(),
                'graduated' => DriverTrainingSchool::query()->where('graduated', true)->count(),
                'in_progress' => DriverTrainingSchool::query()->where('graduated', false)->count(),
                'documents' => Media::query()
                    ->where('model_type', DriverTrainingSchool::class)
                    ->where('collection_name', 'school_certificates')
                    ->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/training-schools/Create', [
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
            'states' => Constants::usStates(),
            'skillOptions' => $this->skillOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($request, $validated) {
            $school = DriverTrainingSchool::create($this->schoolPayload($validated, $request));
            $this->syncMedia($school, $request);
        });

        return redirect()
            ->route('admin.training-schools.index')
            ->with('success', 'Training school created successfully.');
    }

    public function show(DriverTrainingSchool $training_school): Response
    {
        $training_school->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        return Inertia::render('admin/training-schools/Show', [
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
        ]);
    }

    public function edit(DriverTrainingSchool $training_school): Response
    {
        $training_school->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        return Inertia::render('admin/training-schools/Edit', [
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
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
            'states' => Constants::usStates(),
            'skillOptions' => $this->skillOptions(),
        ]);
    }

    public function update(Request $request, DriverTrainingSchool $training_school): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($request, $validated, $training_school) {
            $training_school->update($this->schoolPayload($validated, $request));
            $this->syncMedia($training_school, $request);
        });

        return redirect()
            ->route('admin.training-schools.index')
            ->with('success', 'Training school updated successfully.');
    }

    public function destroy(DriverTrainingSchool $training_school): RedirectResponse
    {
        DB::transaction(function () use ($training_school) {
            $training_school->clearMediaCollection('school_certificates');
            $training_school->delete();
        });

        return redirect()
            ->route('admin.training-schools.index')
            ->with('success', 'Training school deleted successfully.');
    }

    public function documents(Request $request): Response
    {
        return $this->renderDocumentsPage($request);
    }

    public function showDocuments(Request $request, DriverTrainingSchool $training_school): Response
    {
        $training_school->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        return $this->renderDocumentsPage($request, $training_school);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless(
            $media->model_type === DriverTrainingSchool::class
            && $media->collection_name === 'school_certificates',
            404
        );

        $media->delete();

        return back()->with('success', 'Training school document deleted successfully.');
    }

    protected function renderDocumentsPage(Request $request, ?DriverTrainingSchool $school = null): Response
    {
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => (string) $request->input('carrier_filter', ''),
            'school_filter' => (string) $request->input('school_filter', $school?->id ? (string) $school->id : ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'file_type' => (string) $request->input('file_type', ''),
        ];

        $query = Media::query()
            ->where('model_type', DriverTrainingSchool::class)
            ->where('collection_name', 'school_certificates');

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

        if (! $school && $filters['carrier_filter'] !== '') {
            $query->whereHasMorph('model', [DriverTrainingSchool::class], function ($modelQuery) use ($filters) {
                $modelQuery->whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $filters['carrier_filter']));
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

        return Inertia::render('admin/training-schools/Documents', [
            'documents' => $documents,
            'filters' => $filters,
            'school' => $school ? [
                'id' => $school->id,
                'school_name' => $school->school_name,
                'driver_name' => $this->driverFullName($school->userDriverDetail),
                'carrier_name' => $school->userDriverDetail?->carrier?->name,
            ] : null,
            'carriers' => $this->carrierOptions(),
            'schools' => DriverTrainingSchool::query()
                ->with(['userDriverDetail.carrier:id,name'])
                ->orderBy('school_name')
                ->get()
                ->map(fn (DriverTrainingSchool $item) => [
                    'id' => $item->id,
                    'school_name' => $item->school_name,
                    'carrier_name' => $item->userDriverDetail?->carrier?->name,
                ]),
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
            'school_name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:10'],
            'date_start' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after_or_equal:date_start'],
            'graduated' => ['nullable', 'boolean'],
            'subject_to_safety_regulations' => ['nullable', 'boolean'],
            'performed_safety_functions' => ['nullable', 'boolean'],
            'training_skills' => ['nullable', 'array'],
            'training_skills.*' => ['string', 'max:100'],
            'training_documents' => ['nullable', 'array'],
            'training_documents.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
        ]);
    }

    protected function schoolPayload(array $validated, Request $request): array
    {
        return [
            'user_driver_detail_id' => $validated['user_driver_detail_id'],
            'school_name' => $validated['school_name'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'date_start' => $this->toDbDate($validated['date_start']),
            'date_end' => $this->toDbDate($validated['date_end']),
            'graduated' => $request->boolean('graduated'),
            'subject_to_safety_regulations' => $request->boolean('subject_to_safety_regulations'),
            'performed_safety_functions' => $request->boolean('performed_safety_functions'),
            'training_skills' => array_values($validated['training_skills'] ?? []),
        ];
    }

    protected function syncMedia(DriverTrainingSchool $school, Request $request): void
    {
        foreach ($request->file('training_documents', []) as $document) {
            $school->addMedia($document)
                ->usingName(pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('school_certificates');
        }
    }

    protected function transformSchool(DriverTrainingSchool $school, int $documentCount): array
    {
        return [
            'id' => $school->id,
            'created_at' => $school->created_at?->format('n/j/Y'),
            'school_name' => $school->school_name,
            'city' => $school->city,
            'state' => $school->state,
            'date_start' => $school->date_start?->format('n/j/Y'),
            'date_end' => $school->date_end?->format('n/j/Y'),
            'graduated' => (bool) $school->graduated,
            'document_count' => $documentCount,
            'driver' => $school->userDriverDetail ? [
                'id' => $school->userDriverDetail->id,
                'name' => $this->driverFullName($school->userDriverDetail),
                'email' => $school->userDriverDetail->user?->email,
            ] : null,
            'carrier' => $school->userDriverDetail?->carrier ? [
                'id' => $school->userDriverDetail->carrier->id,
                'name' => $school->userDriverDetail->carrier->name,
            ] : null,
        ];
    }

    protected function documentsPayload(DriverTrainingSchool $school): array
    {
        return $school->getMedia('school_certificates')
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

    protected function documentCounts(array $schoolIds): array
    {
        if ($schoolIds === []) {
            return [];
        }

        return Media::query()
            ->selectRaw('model_id, COUNT(*) as aggregate')
            ->where('model_type', DriverTrainingSchool::class)
            ->where('collection_name', 'school_certificates')
            ->whereIn('model_id', $schoolIds)
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

    protected function skillOptions(): array
    {
        return [
            'double_trailer' => 'Double Trailer',
            'passenger' => 'Passenger',
            'tank_vehicle' => 'Tank Vehicle',
            'hazardous_material' => 'Hazardous Material',
            'combination_vehicle' => 'Combination Vehicle',
            'air_brakes' => 'Air Brakes',
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
