<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\TripController as AdminTripController;
use App\Models\Hos\HosEntry;
use App\Models\Trip;
use App\Models\TripPause;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierTripController extends AdminTripController
{
    public function dashboard(Request $request): InertiaResponse|JsonResponse
    {
        $scope = $this->scopeContext();
        $filters = [
            'carrier_id' => (string) ($scope['carrier_id'] ?? ''),
        ];

        $query = Trip::query()
            ->with(['carrier:id,name', 'driver.user:id,name', 'vehicle:id,company_unit_number,year,make,model']);
        $this->applyTripScope($query, $scope, $filters['carrier_id']);

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', Trip::STATUS_PENDING)->count(),
            'accepted' => (clone $query)->where('status', Trip::STATUS_ACCEPTED)->count(),
            'in_progress' => (clone $query)->where('status', Trip::STATUS_IN_PROGRESS)->count(),
            'paused' => (clone $query)->where('status', Trip::STATUS_PAUSED)->count(),
            'completed' => (clone $query)->where('status', Trip::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $query)->where('status', Trip::STATUS_CANCELLED)->count(),
            'with_violations' => (clone $query)->where('has_violations', true)->count(),
            'ghost_logs' => (clone $query)->where('forgot_to_close', true)->count(),
        ];

        if ($request->expectsJson()) {
            return response()->json($stats);
        }

        $recentTrips = $query->orderByDesc('scheduled_start_date')->limit(10)->get()
            ->map(fn (Trip $trip) => [
                'id' => $trip->id,
                'trip_number' => $trip->trip_number,
                'carrier_name' => $trip->carrier?->name,
                'driver_name' => $trip->driver?->full_name ?: ($trip->driver?->user?->name ?: 'Unknown Driver'),
                'vehicle_label' => $this->vehicleLabel($trip->vehicle),
                'status' => $trip->status,
                'scheduled_start' => $trip->scheduled_start_date?->format('n/j/Y g:i A'),
            ])
            ->values();

        return Inertia::render('carrier/trips/Dashboard', [
            'filters' => $filters,
            'stats' => $stats,
            'recentTrips' => $recentTrips,
            'carriers' => $this->carrierOptions($scope),
            'isSuperadmin' => false,
        ]);
    }

    public function index(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => (string) ($scope['carrier_id'] ?? ''),
            'status' => (string) $request->input('status', ''),
            'start_date' => (string) $request->input('start_date', ''),
            'end_date' => (string) $request->input('end_date', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = Trip::query()
            ->with(['carrier:id,name', 'driver.user:id,name,email', 'vehicle:id,company_unit_number,year,make,model'])
            ->withCount('violations');
        $this->applyTripScope($query, $scope, $filters['carrier_id']);

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('trip_number', 'like', $term)
                    ->orWhere('origin_address', 'like', $term)
                    ->orWhere('destination_address', 'like', $term)
                    ->orWhereHas('carrier', fn (Builder $carrier) => $carrier->where('name', 'like', $term))
                    ->orWhereHas('driver.user', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('vehicle', function (Builder $vehicle) use ($term) {
                        $vehicle
                            ->where('company_unit_number', 'like', $term)
                            ->orWhere('make', 'like', $term)
                            ->orWhere('model', 'like', $term);
                    });
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($startDate = $this->parseUsDate($filters['start_date'])) {
            $query->whereDate('scheduled_start_date', '>=', $startDate->format('Y-m-d'));
        }

        if ($endDate = $this->parseUsDate($filters['end_date'])) {
            $query->whereDate('scheduled_start_date', '<=', $endDate->format('Y-m-d'));
        }

        $trips = $query
            ->orderByDesc('scheduled_start_date')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $tripStatsQuery = Trip::query();
        $this->applyTripScope($tripStatsQuery, $scope, $filters['carrier_id']);

        $stats = [
            'total' => (clone $tripStatsQuery)->count(),
            'pending' => (clone $tripStatsQuery)->where('status', Trip::STATUS_PENDING)->count(),
            'accepted' => (clone $tripStatsQuery)->where('status', Trip::STATUS_ACCEPTED)->count(),
            'in_progress' => (clone $tripStatsQuery)->whereIn('status', [Trip::STATUS_IN_PROGRESS, Trip::STATUS_PAUSED])->count(),
            'completed' => (clone $tripStatsQuery)->where('status', Trip::STATUS_COMPLETED)->count(),
            'violations' => (clone $tripStatsQuery)->where('has_violations', true)->count(),
        ];

        $trips->through(fn (Trip $trip) => $this->tripIndexRow($trip));

        return Inertia::render('carrier/trips/Index', [
            'filters' => $filters,
            'trips' => $trips,
            'stats' => $stats,
            'carriers' => $this->carrierOptions($scope),
            'statusOptions' => collect(Trip::STATUSES)->map(fn (string $status) => [
                'value' => $status,
                'label' => str($status)->replace('_', ' ')->title()->toString(),
            ])->values(),
            'isSuperadmin' => false,
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $selectedCarrierId = $scope['carrier_id'];

        if ($selectedCarrierId) {
            $this->ensureAllowedCarrier($selectedCarrierId, $scope);
        }

        return Inertia::render('carrier/trips/Create', [
            'trip' => null,
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $selectedCarrierId ? $this->driverOptionsForCarrier($selectedCarrierId) : [],
            'vehicles' => $selectedCarrierId ? $this->vehicleOptionsForCarrier($selectedCarrierId) : [],
            'isSuperadmin' => false,
            'selectedCarrierId' => $selectedCarrierId ? (string) $selectedCarrierId : '',
        ]);
    }

    public function getCarrierData(Request $request): JsonResponse
    {
        $scope = $this->scopeContext();
        $carrierId = (int) ($scope['carrier_id'] ?? 0);

        if (! $carrierId) {
            return response()->json(['drivers' => [], 'vehicles' => []]);
        }

        $this->ensureAllowedCarrier($carrierId, $scope);

        return response()->json([
            'drivers' => $this->driverOptionsForCarrier($carrierId, $request->integer('selected_driver_id') ?: null),
            'vehicles' => $this->vehicleOptionsForCarrier($carrierId, $request->integer('selected_vehicle_id') ?: null),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $scope = $this->scopeContext();
        $validated = $request->validate([
            'carrier_id' => ['required', 'integer', 'exists:carriers,id'],
            'driver_id' => ['required', 'integer', 'exists:user_driver_details,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'origin_address' => ['required', 'string', 'max:500'],
            'destination_address' => ['required', 'string', 'max:500'],
            'scheduled_start_date' => ['required', 'string', 'max:20'],
            'scheduled_start_time' => ['required', 'date_format:H:i'],
            'scheduled_end_date' => ['nullable', 'string', 'max:20'],
            'scheduled_end_time' => ['nullable', 'date_format:H:i'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'load_type' => ['nullable', 'string', 'max:100'],
            'load_weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $carrierId = (int) $validated['carrier_id'];
        $this->ensureAllowedCarrier($carrierId, $scope);
        $this->ensureDriverBelongsToCarrier((int) $validated['driver_id'], $carrierId);
        $this->ensureVehicleBelongsToCarrier((int) $validated['vehicle_id'], $carrierId);

        $validated['scheduled_start_date'] = $this->combineDateTime(
            $validated['scheduled_start_date'],
            $validated['scheduled_start_time'],
            'scheduled_start_date'
        );

        $validated['scheduled_end_date'] = $validated['scheduled_end_date']
            ? $this->combineDateTime(
                $validated['scheduled_end_date'],
                $validated['scheduled_end_time'] ?? '00:00',
                'scheduled_end_date'
            )
            : null;

        $validated['created_by'] = Auth::id();

        try {
            $trip = $this->tripService->createTrip($carrierId, $validated);
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('carrier.trips.show', $trip)
            ->with('success', 'Trip created successfully.');
    }

    public function edit(Trip $trip): InertiaResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $trip->carrier_id, $scope);
        $this->ensureEditable($trip);

        return Inertia::render('carrier/trips/Edit', [
            'trip' => $this->tripFormPayload($trip),
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $this->driverOptionsForCarrier((int) $trip->carrier_id, $trip->user_driver_detail_id),
            'vehicles' => $this->vehicleOptionsForCarrier((int) $trip->carrier_id, $trip->vehicle_id),
            'isSuperadmin' => false,
            'selectedCarrierId' => (string) $trip->carrier_id,
        ]);
    }

    public function update(Request $request, Trip $trip): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $trip->carrier_id, $scope);
        $this->ensureEditable($trip);

        $validated = $request->validate([
            'carrier_id' => ['required', 'integer', 'exists:carriers,id'],
            'driver_id' => ['required', 'integer', 'exists:user_driver_details,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'origin_address' => ['required', 'string', 'max:500'],
            'destination_address' => ['required', 'string', 'max:500'],
            'scheduled_start_date' => ['required', 'string', 'max:20'],
            'scheduled_start_time' => ['required', 'date_format:H:i'],
            'scheduled_end_date' => ['nullable', 'string', 'max:20'],
            'scheduled_end_time' => ['nullable', 'date_format:H:i'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'load_type' => ['nullable', 'string', 'max:100'],
            'load_weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $carrierId = (int) $validated['carrier_id'];
        $this->ensureAllowedCarrier($carrierId, $scope);
        $this->ensureDriverBelongsToCarrier((int) $validated['driver_id'], $carrierId);
        $this->ensureVehicleBelongsToCarrier((int) $validated['vehicle_id'], $carrierId);

        $scheduledStart = $this->combineDateTime(
            $validated['scheduled_start_date'],
            $validated['scheduled_start_time'],
            'scheduled_start_date'
        );

        $trip->update([
            'carrier_id' => $carrierId,
            'user_driver_detail_id' => (int) $validated['driver_id'],
            'vehicle_id' => (int) $validated['vehicle_id'],
            'origin_address' => $validated['origin_address'],
            'destination' => $validated['destination_address'],
            'destination_address' => $validated['destination_address'],
            'scheduled_start_date' => $scheduledStart,
            'scheduled_end_date' => $validated['scheduled_end_date']
                ? $this->combineDateTime(
                    $validated['scheduled_end_date'],
                    $validated['scheduled_end_time'] ?? '00:00',
                    'scheduled_end_date'
                )
                : null,
            'start_time' => $scheduledStart,
            'estimated_duration_minutes' => $validated['estimated_duration_minutes'] ?? null,
            'description' => $validated['description'] ?: null,
            'notes' => $validated['notes'] ?: null,
            'load_type' => $validated['load_type'] ?: null,
            'load_weight' => $validated['load_weight'] ?: null,
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('carrier.trips.show', $trip)
            ->with('success', 'Trip updated successfully.');
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $trip->carrier_id, $scope);

        if (in_array($trip->status, [Trip::STATUS_IN_PROGRESS, Trip::STATUS_PAUSED, Trip::STATUS_COMPLETED], true)) {
            return back()->with('error', 'Only pending, accepted, or cancelled trips can be deleted.');
        }

        $trip->delete();

        return redirect()
            ->route('carrier.trips.index')
            ->with('success', 'Trip deleted successfully.');
    }

    public function show(Trip $trip): InertiaResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $trip->carrier_id, $scope);

        $trip->load([
            'carrier:id,name',
            'driver.user:id,name,email',
            'vehicle:id,company_unit_number,year,make,model',
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
                'timestamp' => isset($event['timestamp']) && $event['timestamp'] ? Carbon::parse($event['timestamp'])->format('n/j/Y g:i A') : null,
                'color' => $event['color'] ?? 'secondary',
                'icon' => $event['icon'] ?? 'Circle',
                'location' => $event['location'] ?? null,
                'forced_by' => $event['forced_by'] ?? null,
                'pause_duration' => $event['pause_duration'] ?? null,
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

        $recentHosLocations = HosEntry::query()
            ->where('trip_id', $trip->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('start_time')
            ->take(5)
            ->get()
            ->map(fn (HosEntry $entry) => [
                'id' => $entry->id,
                'status' => $entry->status_name,
                'start_time' => $entry->start_time?->format('n/j/Y g:i A'),
                'coordinates' => $entry->latitude && $entry->longitude ? number_format((float) $entry->latitude, 6) . ', ' . number_format((float) $entry->longitude, 6) : null,
                'formatted_address' => $entry->formatted_address,
                'maps_url' => $entry->latitude && $entry->longitude ? "https://www.google.com/maps?q={$entry->latitude},{$entry->longitude}" : null,
            ])
            ->values();

        $tripReportPdfs = $trip->driver?->getTripReportPdfs($trip->id)?->map(fn (Media $media) => $this->mediaPayload($media, 'Trip Summary'))->values() ?? collect();
        $inspectionDocuments = collect([
            $trip->driver?->getPreTripInspectionPdf($trip->id),
            $trip->driver?->getPostTripInspectionPdf($trip->id),
        ])->filter()->map(function (Media $media) {
            $type = $media->getCustomProperty('report_type') === 'post_trip_inspection'
                ? 'Post-Trip Inspection'
                : 'Pre-Trip Inspection';

            return $this->mediaPayload($media, $type);
        })->values();

        $tripDocuments = $trip->driver?->getTripDocuments($trip->id)?->map(fn (Media $media) => $this->mediaPayload($media, 'Trip Document'))->values() ?? collect();

        $gpsRoute = $trip->gpsPoints->map(fn ($pt) => [
            'lat' => (float) $pt->latitude,
            'lng' => (float) $pt->longitude,
        ])->values();

        return Inertia::render('carrier/trips/Show', [
            'trip' => $this->tripDetailPayload($trip),
            'gpsRoute' => $gpsRoute,
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
            'pauses' => $trip->pauses->sortByDesc('started_at')->values()->map(fn (TripPause $pause) => [
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
            'recentHosLocations' => $recentHosLocations,
            'tripReportPdfs' => $tripReportPdfs,
            'inspectionDocuments' => $inspectionDocuments,
            'tripDocuments' => $tripDocuments,
            'hosLogRoute' => null,
        ]);
    }

    public function forceStart(Trip $trip): RedirectResponse
    {
        return $this->runEmergencyAction($trip, fn () => $this->tripService->forceStartTrip($trip), 'Trip started successfully.');
    }

    public function forcePause(Trip $trip): RedirectResponse
    {
        return $this->runEmergencyAction(
            $trip,
            fn () => $this->tripService->forcePauseTrip($trip, Auth::id(), 'Paused by carrier emergency control'),
            'Trip paused successfully.'
        );
    }

    public function forceResume(Trip $trip): RedirectResponse
    {
        return $this->runEmergencyAction($trip, fn () => $this->tripService->forceResumeTrip($trip, Auth::id()), 'Trip resumed successfully.');
    }

    public function forceEnd(Trip $trip): RedirectResponse
    {
        return $this->runEmergencyAction($trip, fn () => $this->tripService->forceEndTrip($trip), 'Trip ended successfully.');
    }

    protected function runEmergencyAction(Trip $trip, callable $callback, string $successMessage): RedirectResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $trip->carrier_id, $scope);

        try {
            $callback();
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('carrier.trips.show', $trip)
            ->with('success', $successMessage);
    }
}
