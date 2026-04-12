<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Admin\TripController as AdminTripController;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\Hos\HosEntry;
use App\Models\Trip;
use App\Models\UserDriverDetail;
use App\Services\Hos\HosFMCSAService;
use App\Services\Hos\HosWeeklyCycleService;
use App\Services\Trip\DestinationVerificationService;
use App\Services\Trip\TripGpsTrackingService;
use App\Services\Trip\TripService;
use App\Services\Trip\TripTimelineService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverTripController extends AdminTripController
{
    public function __construct(
        TripService $tripService,
        TripGpsTrackingService $gpsService,
        HosWeeklyCycleService $weeklyCycleService,
        TripTimelineService $timelineService,
        DestinationVerificationService $verificationService,
        protected HosFMCSAService $fmcsaService,
    ) {
        parent::__construct(
            $tripService,
            $gpsService,
            $weeklyCycleService,
            $timelineService,
            $verificationService,
        );
    }

    public function index(Request $request): Response
    {
        $driver = $this->resolveDriver();
        $filters = [
            'status' => (string) $request->input('status', 'all'),
            'search' => trim((string) $request->input('search', '')),
        ];

        $query = Trip::query()
            ->where('user_driver_detail_id', $driver->id)
            ->with(['carrier:id,name', 'vehicle:id,company_unit_number,make,model,year'])
            ->withCount('gpsPoints');

        if ($filters['status'] !== '' && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('trip_number', 'like', $term)
                    ->orWhere('origin_address', 'like', $term)
                    ->orWhere('destination_address', 'like', $term)
                    ->orWhereHas('vehicle', function (Builder $vehicle) use ($term) {
                        $vehicle
                            ->where('company_unit_number', 'like', $term)
                            ->orWhere('make', 'like', $term)
                            ->orWhere('model', 'like', $term);
                    });
            });
        }

        $trips = $query
            ->orderByRaw("CASE
                WHEN status = 'in_progress' THEN 1
                WHEN status = 'paused' THEN 2
                WHEN status = 'accepted' THEN 3
                WHEN status = 'pending' THEN 4
                WHEN status = 'completed' THEN 5
                ELSE 6
            END")
            ->orderByDesc('scheduled_start_date')
            ->paginate(12)
            ->withQueryString();

        $trips->through(fn (Trip $trip) => $this->tripCardPayload($trip));

        $statsQuery = Trip::query()->where('user_driver_detail_id', $driver->id);

        return Inertia::render('driver/trips/Index', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'filters' => $filters,
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'pending' => (clone $statsQuery)->where('status', Trip::STATUS_PENDING)->count(),
                'accepted' => (clone $statsQuery)->where('status', Trip::STATUS_ACCEPTED)->count(),
                'in_progress' => (clone $statsQuery)->whereIn('status', [Trip::STATUS_IN_PROGRESS, Trip::STATUS_PAUSED])->count(),
                'completed' => (clone $statsQuery)->where('status', Trip::STATUS_COMPLETED)->count(),
                'quick_trips' => (clone $statsQuery)->where('is_quick_trip', true)->count(),
            ],
            'trips' => $trips,
        ]);
    }

    public function pendingCount(): JsonResponse
    {
        $driver = $this->resolveDriver();

        return response()->json([
            'count' => $this->tripService->getDriverPendingTrips($driver->id)->count(),
        ]);
    }

    public function create(Request $request): Response
    {
        $driver = $this->resolveDriver();
        $carrier = $driver->carrier;

        abort_unless($carrier, 403, 'No carrier associated with this account.');

        $vehicles = $this->vehicleOptions($driver);

        return Inertia::render('driver/trips/Create', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
            ],
            'carrier' => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ],
            'vehicles' => $vehicles->values(),
            'fmcsaStatus' => $this->fmcsaService->getDriverFMCSAStatus($driver->id, $carrier->id),
            'weeklyCycleStatus' => $this->weeklyCycleService->getWeeklyCycleStatus($driver->id),
            'noAssignedVehicles' => $vehicles->isEmpty(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $carrier = $driver->carrier;

        abort_unless($carrier, 403, 'No carrier associated with this account.');

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'origin_address' => ['required', 'string', 'max:500'],
            'destination_address' => ['required', 'string', 'max:500'],
            'scheduled_start_date' => ['required', 'string', 'max:20'],
            'scheduled_start_time' => ['required', 'date_format:H:i'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:15', 'max:720'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->ensureVehicleAssignedToDriver($driver, (int) $validated['vehicle_id']);

        $startAt = $this->combineDateTime(
            $validated['scheduled_start_date'],
            $validated['scheduled_start_time'],
            'scheduled_start_date'
        );

        if ($startAt->copy()->startOfDay()->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'scheduled_start_date' => 'The start date cannot be in the past.',
            ]);
        }

        $this->ensureDriverCanCreateTrip($driver, $carrier->id);

        try {
            $trip = $this->tripService->createTrip($carrier->id, [
                'driver_id' => $driver->id,
                'vehicle_id' => (int) $validated['vehicle_id'],
                'origin_address' => $validated['origin_address'],
                'destination_address' => $validated['destination_address'],
                'scheduled_start_date' => $startAt,
                'estimated_duration_minutes' => $validated['estimated_duration_minutes'] ?? 60,
                'description' => $validated['description'] ?: null,
                'notes' => $validated['notes'] ?: null,
                'created_by' => Auth::id(),
            ]);

            $this->tripService->acceptTrip($trip, $driver->id);
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors([
                'trip' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('driver.trips.show', $trip)
            ->with('success', 'Trip created successfully. You can start it when ready.');
    }

    public function storeQuickTrip(Request $request): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $carrier = $driver->carrier;

        abort_unless($carrier, 403, 'No carrier associated with this account.');

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'origin_address' => ['nullable', 'string', 'max:500'],
            'destination_address' => ['nullable', 'string', 'max:500'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:15', 'max:720'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->ensureVehicleAssignedToDriver($driver, (int) $validated['vehicle_id']);
        $this->ensureDriverCanCreateTrip($driver, $carrier->id);

        try {
            $trip = $this->tripService->createQuickTrip($carrier->id, [
                'driver_id' => $driver->id,
                'vehicle_id' => (int) $validated['vehicle_id'],
                'origin_address' => $validated['origin_address'] ?: null,
                'destination_address' => $validated['destination_address'] ?: null,
                'estimated_duration_minutes' => $validated['estimated_duration_minutes'] ?? 60,
                'description' => $validated['description'] ?: null,
                'notes' => $validated['notes'] ?: null,
                'created_by' => Auth::id(),
            ]);

            $this->tripService->acceptTrip($trip, $driver->id);
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors([
                'trip' => $exception->getMessage(),
            ]);
        }

        $message = 'Quick trip created successfully.';

        if ($trip->requires_completion) {
            $message .= ' Your carrier may still need to complete the route details later.';
        }

        return redirect()
            ->route('driver.trips.show', $trip)
            ->with('success', $message);
    }

    public function show(Trip $trip): Response
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        $trip->load([
            'carrier:id,name',
            'vehicle:id,company_unit_number,year,make,model,registration_number',
            'gpsPoints',
            'violations',
            'hosEntries',
            'pauses.forcedByUser:id,name',
        ]);

        $gpsStats = $trip->gpsPoints->isNotEmpty()
            ? $this->gpsService->getTripStatistics($trip)
            : null;

        $timeline = $this->timelineService->buildTimelineWithHos($trip)->map(function (array $event) {
            return [
                'type' => $event['type'],
                'title' => $event['title'],
                'description' => $event['description'] ?? null,
                'timestamp' => isset($event['timestamp']) && $event['timestamp']
                    ? Carbon::parse($event['timestamp'])->format('n/j/Y g:i A')
                    : null,
                'color' => $event['color'] ?? 'secondary',
                'icon' => $event['icon'] ?? 'Circle',
                'location' => $event['location'] ?? null,
                'forced_by' => $event['forced_by'] ?? null,
                'is_active' => (bool) ($event['is_active'] ?? false),
            ];
        })->values();

        $destinationVerification = $this->verificationService->verifyArrival($trip);
        $googleMapsUrls = [
            'origin' => $trip->origin_latitude && $trip->origin_longitude
                ? $this->verificationService->buildGoogleMapsUrl((float) $trip->origin_latitude, (float) $trip->origin_longitude)
                : null,
            'destination' => $trip->destination_latitude && $trip->destination_longitude
                ? $this->verificationService->buildGoogleMapsUrl((float) $trip->destination_latitude, (float) $trip->destination_longitude)
                : null,
            'route' => $this->verificationService->buildRouteGoogleMapsUrl($trip, $trip->gpsPoints->last()),
        ];

        $tripReportPdfs = $driver->getTripReportPdfs($trip->id)?->map(fn (Media $media) => $this->mediaPayload($media, 'Trip Summary'))->values() ?? collect();
        $inspectionDocuments = collect([
            $driver->getPreTripInspectionPdf($trip->id),
            $driver->getPostTripInspectionPdf($trip->id),
        ])->filter()->map(function (Media $media) {
            $type = $media->getCustomProperty('report_type') === 'post_trip_inspection'
                ? 'Post-Trip Inspection'
                : 'Pre-Trip Inspection';

            return $this->mediaPayload($media, $type);
        })->values();

        $tripDocuments = $trip->getTripDocuments()->map(function (Media $media) use ($trip) {
            return [
                'id' => $media->id,
                'label' => Trip::getDocumentTypeName((string) $media->getCustomProperty('document_type', Trip::DOC_TYPE_OTHER)),
                'file_name' => $media->file_name,
                'size_label' => $this->formatBytes((int) $media->size),
                'mime_type' => $media->mime_type,
                'notes' => $media->getCustomProperty('notes'),
                'uploaded_at' => $media->created_at?->format('n/j/Y g:i A'),
                'preview_url' => route('driver.trips.documents.preview', [$trip, $media]),
                'download_url' => route('driver.trips.documents.download', [$trip, $media]),
                'can_delete' => $trip->canDeleteDocuments() && ((int) $media->getCustomProperty('uploaded_by', Auth::id()) === (int) Auth::id()),
            ];
        })->values();

        $currentEntry = HosEntry::query()
            ->where('user_driver_detail_id', $driver->id)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        $isOnBreak = $currentEntry
            && $currentEntry->status === HosEntry::STATUS_ON_DUTY_NOT_DRIVING
            && (int) $currentEntry->trip_id === (int) $trip->id;

        return Inertia::render('driver/trips/Show', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'trip' => array_merge($this->tripDetailPayload($trip), [
                'is_quick_trip' => (bool) $trip->is_quick_trip,
                'requires_completion' => (bool) $trip->requires_completion,
                'missing_fields' => $trip->getMissingFields(),
                'vehicle_license_plate' => $trip->vehicle?->registration_number,
                'can_accept' => $trip->isPending(),
                'can_reject' => $trip->isPending(),
                'can_start' => $trip->canBeStarted(),
                'can_pause' => $trip->isInProgress(),
                'can_resume' => $trip->canBeResumed(),
                'can_end' => $trip->canBeEnded(),
                'can_upload_documents' => $trip->canUploadDocuments(),
                'can_delete_documents' => $trip->canDeleteDocuments(),
                'documents_count' => $tripDocuments->count(),
                'has_trailer' => (bool) $trip->has_trailer,
            ]),
            'fmcsaStatus' => $this->fmcsaService->getDriverFMCSAStatus($driver->id, $trip->carrier_id),
            'isOnBreak' => (bool) $isOnBreak,
            'gpsStats' => $gpsStats,
            'timeline' => $timeline,
            'hosEntries' => $trip->hosEntries->sortBy('start_time')->values()->map(fn (HosEntry $entry) => [
                'id' => $entry->id,
                'status' => $entry->status_name,
                'start_time' => $entry->start_time?->format('n/j/Y g:i A'),
                'end_time' => $entry->end_time?->format('n/j/Y g:i A'),
                'duration' => $entry->formatted_duration,
                'location' => $entry->formatted_address ?: $entry->location_display,
                'is_active' => $entry->isOpen(),
            ]),
            'violations' => $trip->violations->sortByDesc('violation_date')->values()->map(fn ($violation) => [
                'id' => $violation->id,
                'type' => $violation->violation_type_name,
                'severity' => $violation->severity_name,
                'date' => $violation->violation_date?->format('n/j/Y'),
                'hours_exceeded' => $violation->formatted_hours_exceeded,
                'reference' => $violation->fmcsa_rule_reference,
                'acknowledged' => (bool) $violation->acknowledged,
                'forgiven' => (bool) $violation->is_forgiven,
            ]),
            'pauses' => $trip->pauses->sortByDesc('started_at')->values()->map(fn ($pause) => [
                'id' => $pause->id,
                'started_at' => $pause->started_at?->format('n/j/Y g:i A'),
                'ended_at' => $pause->ended_at?->format('n/j/Y g:i A'),
                'duration' => $pause->formatted_duration,
                'reason' => $pause->reason,
                'location' => $pause->formatted_address,
                'forced_by' => $pause->forcedByUser?->name,
            ]),
            'destinationVerification' => $destinationVerification,
            'googleMapsUrls' => $googleMapsUrls,
            'tripReportPdfs' => $tripReportPdfs,
            'inspectionDocuments' => $inspectionDocuments,
            'tripDocuments' => $tripDocuments,
            'hosLogRoute' => Route::has('driver.hos.history') ? route('driver.hos.history') : null,
        ]);
    }

    public function accept(Trip $trip): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        try {
            $this->tripService->acceptTrip($trip, $driver->id);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('driver.trips.show', $trip)
            ->with('success', 'Trip accepted successfully.');
    }

    public function reject(Request $request, Trip $trip): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->tripService->rejectTrip($trip, $driver->id, $validated['reason']);
        } catch (\Throwable $exception) {
            return back()->withErrors(['reason' => $exception->getMessage()]);
        }

        return redirect()
            ->route('driver.trips.index')
            ->with('success', 'Trip rejected.');
    }

    public function startForm(Trip $trip): Response|RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        if (! $trip->canBeStarted()) {
            return redirect()
                ->route('driver.trips.show', $trip)
                ->with('error', 'This trip cannot be started.');
        }

        return Inertia::render('driver/trips/Start', [
            'trip' => $this->inspectionTripPayload($trip),
            'validation' => $this->fmcsaService->validateTripStart($driver->id, $trip->carrier_id),
            'inspection' => $this->inspectionConfigPayload(),
        ]);
    }

    public function start(Request $request, Trip $trip): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        $validated = $this->validateInspectionRequest($request, true, $request->boolean('has_trailer'));

        try {
            $this->tripService->startTrip($trip, $driver->id, $validated);
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors([
                'trip' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('driver.trips.show', $trip)
            ->with('success', 'Trip started successfully. Drive safely.');
    }

    public function pause(Request $request, Trip $trip): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        try {
            $this->tripService->pauseTrip($trip, $driver->id, $this->optionalLocationPayload($request), trim((string) $request->input('reason', '')) ?: null);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('driver.trips.show', $trip)
            ->with('success', 'Trip paused. You are now on break.');
    }

    public function resume(Request $request, Trip $trip): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        try {
            $this->tripService->resumeTrip($trip, $driver->id, $this->optionalLocationPayload($request));
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('driver.trips.show', $trip)
            ->with('success', 'Trip resumed. Drive safely.');
    }

    public function endForm(Trip $trip): Response|RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        if (! $trip->canBeEnded()) {
            return redirect()
                ->route('driver.trips.show', $trip)
                ->with('error', 'This trip cannot be ended.');
        }

        return Inertia::render('driver/trips/End', [
            'trip' => $this->inspectionTripPayload($trip),
            'inspection' => $this->inspectionConfigPayload(),
        ]);
    }

    public function end(Request $request, Trip $trip): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        $validated = $this->validateInspectionRequest($request, false, (bool) $trip->has_trailer);

        try {
            $this->tripService->endTrip($trip, $driver->id, $validated, $validated['notes'] ?? null);
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors([
                'trip' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('driver.trips.show', $trip)
            ->with('success', 'Trip completed successfully.');
    }

    public function uploadDocuments(Request $request, Trip $trip): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        if (! $trip->canUploadDocuments()) {
            return back()->with('error', 'Documents cannot be uploaded to this trip at this time.');
        }

        $request->validate([
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,gif', 'max:10240'],
            'document_types' => ['required', 'array'],
            'document_types.*' => ['required', 'string', 'in:' . implode(',', array_keys(Trip::DOCUMENT_TYPES))],
            'document_notes' => ['nullable', 'array'],
            'document_notes.*' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($request->file('documents') as $index => $file) {
            $documentType = $request->input("document_types.{$index}", Trip::DOC_TYPE_OTHER);
            $documentNote = $request->input("document_notes.{$index}", '');

            $trip->addMedia($file)
                ->withCustomProperties([
                    'document_type' => $documentType,
                    'document_type_name' => Trip::getDocumentTypeName($documentType),
                    'notes' => $documentNote,
                    'uploaded_by' => Auth::id(),
                    'uploaded_by_name' => Auth::user()?->name,
                    'uploaded_at' => now()->toIso8601String(),
                ])
                ->toMediaCollection('trip_documents');
        }

        return back()->with('success', 'Documents uploaded successfully.');
    }

    public function deleteDocument(Trip $trip, Media $media): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);

        abort_unless((int) $media->model_id === (int) $trip->id && $media->model_type === Trip::class, 404);

        if (! $trip->canDeleteDocuments()) {
            return back()->with('error', 'Documents cannot be deleted from this trip. The deletion window has expired.');
        }

        $uploadedBy = (int) $media->getCustomProperty('uploaded_by', 0);
        if ($uploadedBy && $uploadedBy !== (int) Auth::id()) {
            return back()->with('error', 'You can only delete documents you uploaded.');
        }

        $media->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    public function downloadDocument(Trip $trip, Media $media): BinaryFileResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);
        abort_unless((int) $media->model_id === (int) $trip->id && $media->model_type === Trip::class, 404);
        abort_unless(file_exists($media->getPath()), 404, 'Document not found.');

        return response()->download($media->getPath(), $media->file_name);
    }

    public function previewDocument(Trip $trip, Media $media): BinaryFileResponse
    {
        $driver = $this->resolveDriver();
        $this->authorizeTrip($driver, $trip);
        abort_unless((int) $media->model_id === (int) $trip->id && $media->model_type === Trip::class, 404);
        abort_unless(file_exists($media->getPath()), 404, 'Document not found.');

        $path = str_starts_with((string) $media->mime_type, 'image/')
            ? ($media->hasGeneratedConversion('preview') ? $media->getPath('preview') : $media->getPath())
            : $media->getPath();

        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }

    protected function resolveDriver(): UserDriverDetail
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver, 403, 'No driver profile associated with this account.');
        $driver->loadMissing('carrier:id,name');

        return $driver;
    }

    protected function authorizeTrip(UserDriverDetail $driver, Trip $trip): void
    {
        abort_unless((int) $trip->user_driver_detail_id === (int) $driver->id, 403, 'You do not have access to this trip.');
    }

    protected function vehicleOptions(UserDriverDetail $driver): Collection
    {
        return VehicleDriverAssignment::query()
            ->where('user_driver_detail_id', $driver->id)
            ->where('status', 'active')
            ->with('vehicle:id,company_unit_number,year,make,model,registration_number')
            ->get()
            ->pluck('vehicle')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($vehicle) => [
                'id' => $vehicle->id,
                'label' => $this->vehicleLabel($vehicle),
                'license_plate' => $vehicle->registration_number,
            ]);
    }

    protected function ensureVehicleAssignedToDriver(UserDriverDetail $driver, int $vehicleId): void
    {
        $hasVehicle = VehicleDriverAssignment::query()
            ->where('user_driver_detail_id', $driver->id)
            ->where('status', 'active')
            ->where('vehicle_id', $vehicleId)
            ->exists();

        if (! $hasVehicle) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'This vehicle is not assigned to you.',
            ]);
        }
    }

    protected function ensureDriverCanCreateTrip(UserDriverDetail $driver, int $carrierId): void
    {
        $validation = $this->fmcsaService->validateTripStart($driver->id, $carrierId);

        if (! $validation['valid']) {
            throw ValidationException::withMessages([
                'fmcsa' => array_map(fn ($error) => $error['message'], $validation['errors']),
            ]);
        }
    }

    protected function tripCardPayload(Trip $trip): array
    {
        return [
            'id' => $trip->id,
            'trip_number' => $trip->trip_number ?: 'Trip #' . $trip->id,
            'status' => $trip->status,
            'status_label' => $trip->status_name,
            'scheduled_start' => $trip->scheduled_start_date?->format('n/j/Y g:i A'),
            'origin_address' => $trip->origin_address ?: 'N/A',
            'destination_address' => $trip->destination_address ?: ($trip->destination ?: 'N/A'),
            'vehicle_label' => $this->vehicleLabel($trip->vehicle),
            'is_quick_trip' => (bool) $trip->is_quick_trip,
            'requires_completion' => (bool) $trip->requires_completion,
            'missing_fields' => array_values($trip->getMissingFields()),
            'has_violations' => (bool) $trip->has_violations,
            'gps_points_count' => (int) ($trip->gps_points_count ?? 0),
            'can_accept' => $trip->isPending(),
            'can_reject' => $trip->isPending(),
            'can_start' => $trip->canBeStarted(),
            'can_pause' => $trip->isInProgress(),
            'can_resume' => $trip->canBeResumed(),
            'can_end' => $trip->canBeEnded(),
        ];
    }

    protected function inspectionTripPayload(Trip $trip): array
    {
        $trip->loadMissing('vehicle:id,company_unit_number,year,make,model', 'carrier:id,name');

        return [
            'id' => $trip->id,
            'trip_number' => $trip->trip_number ?: 'Trip #' . $trip->id,
            'origin_address' => $trip->origin_address,
            'destination_address' => $trip->destination_address ?: $trip->destination,
            'scheduled_start' => $trip->scheduled_start_date?->format('n/j/Y g:i A'),
            'actual_start' => $trip->actual_start_time?->format('n/j/Y g:i A'),
            'status' => $trip->status,
            'status_label' => $trip->status_name,
            'vehicle_label' => $this->vehicleLabel($trip->vehicle),
            'has_trailer' => (bool) $trip->has_trailer,
        ];
    }

    protected function inspectionConfigPayload(): array
    {
        return [
            'tractor_items' => Trip::getTractorInspectionItems(),
            'tractor_columns' => Trip::getTractorInspectionColumns(),
            'trailer_items' => Trip::getTrailerInspectionItems(),
            'trailer_columns' => Trip::getTrailerInspectionColumns(),
        ];
    }

    protected function validateInspectionRequest(Request $request, bool $canToggleTrailer, bool $hasTrailer): array
    {
        $tractorKeys = array_keys(Trip::getTractorInspectionItems());
        $trailerKeys = array_keys(Trip::getTrailerInspectionItems());
        $requiredTractorKeys = array_values(array_filter($tractorKeys, fn (string $key) => $key !== 'other_tractor'));
        $requiredTrailerKeys = array_values(array_filter($trailerKeys, fn (string $key) => $key !== 'other_trailer'));

        $rules = [
            'tractor' => ['required', 'array', 'min:' . count($requiredTractorKeys)],
            'tractor.*' => ['required', 'string', 'in:' . implode(',', $tractorKeys)],
            'other_tractor' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'condition_satisfactory' => ['required', 'accepted'],
            'defects_corrected' => ['nullable', 'boolean'],
            'defects_corrected_notes' => ['nullable', 'string', 'max:1000'],
            'defects_not_need_correction' => ['nullable', 'boolean'],
            'defects_not_need_correction_notes' => ['nullable', 'string', 'max:1000'],
            'driver_signature' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($canToggleTrailer) {
            $rules['has_trailer'] = ['nullable', 'boolean'];
        }

        if ($hasTrailer) {
            $rules['trailer'] = ['required', 'array', 'min:' . count($requiredTrailerKeys)];
            $rules['trailer.*'] = ['required', 'string', 'in:' . implode(',', $trailerKeys)];
            $rules['other_trailer'] = ['nullable', 'string', 'max:255'];
        }

        $request->validate($rules, [
            'tractor.min' => 'You must check all required tractor/truck inspection items.',
            'trailer.min' => 'You must check all required trailer inspection items.',
            'condition_satisfactory.accepted' => 'You must confirm the vehicle is in satisfactory condition.',
        ]);

        if (in_array('other_tractor', $request->input('tractor', []), true) && blank($request->input('other_tractor'))) {
            throw ValidationException::withMessages([
                'other_tractor' => 'Please specify details for the other tractor item.',
            ]);
        }

        if ($hasTrailer && in_array('other_trailer', $request->input('trailer', []), true) && blank($request->input('other_trailer'))) {
            throw ValidationException::withMessages([
                'other_trailer' => 'Please specify details for the other trailer item.',
            ]);
        }

        return [
            'has_trailer' => $canToggleTrailer ? $request->boolean('has_trailer') : $hasTrailer,
            'tractor' => $request->input('tractor', []),
            'trailer' => $hasTrailer ? $request->input('trailer', []) : [],
            'other_tractor' => $request->input('other_tractor'),
            'other_trailer' => $request->input('other_trailer'),
            'remarks' => $request->input('remarks'),
            'condition_satisfactory' => $request->boolean('condition_satisfactory'),
            'defects_corrected' => $request->boolean('defects_corrected'),
            'defects_corrected_notes' => $request->input('defects_corrected_notes'),
            'defects_not_need_correction' => $request->boolean('defects_not_need_correction'),
            'defects_not_need_correction_notes' => $request->input('defects_not_need_correction_notes'),
            'driver_signature' => $request->input('driver_signature'),
            'notes' => $request->input('notes'),
        ];
    }

    protected function optionalLocationPayload(Request $request): ?array
    {
        if (! $request->filled('latitude') || ! $request->filled('longitude')) {
            return null;
        }

        return [
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'address' => $request->input('address'),
        ];
    }
}
