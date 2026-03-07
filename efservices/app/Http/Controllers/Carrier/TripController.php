<?php

namespace App\Http\Controllers\Carrier;

use App\Models\Trip;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Trip\TripService;
use App\Services\Trip\TripGpsTrackingService;
use App\Services\Trip\TripTimelineService;
use App\Services\Trip\DestinationVerificationService;
use App\Services\Hos\HosWeeklyCycleService;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    protected TripService $tripService;
    protected TripGpsTrackingService $gpsService;
    protected HosWeeklyCycleService $weeklyCycleService;
    protected TripTimelineService $timelineService;
    protected DestinationVerificationService $verificationService;

    public function __construct(
        TripService $tripService,
        TripGpsTrackingService $gpsService,
        HosWeeklyCycleService $weeklyCycleService,
        TripTimelineService $timelineService,
        DestinationVerificationService $verificationService
    ) {
        $this->tripService = $tripService;
        $this->gpsService = $gpsService;
        $this->weeklyCycleService = $weeklyCycleService;
        $this->timelineService = $timelineService;
        $this->verificationService = $verificationService;
    }

    /**
     * Display trip dashboard.
     */
    public function dashboard()
    {
        $carrier = $this->getCarrier();
        
        $trips = $this->tripService->getCarrierTrips($carrier->id);
        
        $stats = [
            'total' => $trips->count(),
            'pending' => $trips->where('status', Trip::STATUS_PENDING)->count(),
            'in_progress' => $trips->where('status', Trip::STATUS_IN_PROGRESS)->count(),
            'completed_today' => $trips->where('status', Trip::STATUS_COMPLETED)
                ->where('completed_at', '>=', today())
                ->count(),
        ];

        $activeTrips = $trips->where('status', Trip::STATUS_IN_PROGRESS);

        return view('carrier.trips.dashboard', [
            'stats' => $stats,
            'activeTrips' => $activeTrips,
        ]);
    }

    /**
     * Display list of trips.
     */
    public function index(Request $request)
    {
        $carrier = $this->getCarrier();
        
        $filters = [
            'status' => $request->get('status'),
            'driver_id' => $request->get('driver_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $trips = $this->tripService->getCarrierTrips($carrier->id, $filters);
        
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get();

        return view('carrier.trips.index', [
            'trips' => $trips,
            'drivers' => $drivers,
            'filters' => $filters,
        ]);
    }

    /**
     * Show create trip form.
     */
    public function create()
    {
        $carrier = $this->getCarrier();
        
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get()
            ->map(function ($driver) {
                $weeklyStatus = $this->weeklyCycleService->getWeeklyCycleStatus($driver->id);
                return [
                    'id' => $driver->id,
                    'name' => $driver->user->name ?? 'Unknown',
                    'hours_remaining' => $weeklyStatus['hours_remaining'],
                    'status_color' => $weeklyStatus['status_color'],
                    'can_drive' => !$weeklyStatus['is_over_limit'],
                ];
            });

        $vehicles = Vehicle::where('carrier_id', $carrier->id)
            ->where('status', 'active')
            ->get();

        return view('carrier.trips.create', [
            'drivers' => $drivers,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Store a new trip.
     */
    public function store(Request $request)
    {
        $carrier = $this->getCarrier();
        
        $validated = $request->validate([
            'driver_id' => 'required|exists:user_driver_details,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'origin_address' => 'required|string|max:500',
            'destination_address' => 'required|string|max:500',
            'scheduled_start_date' => 'required|date',
            'scheduled_end_date' => 'nullable|date|after:scheduled_start_date',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'load_type' => 'nullable|string|max:100',
            'load_weight' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = Auth::id();

        try {
            $trip = $this->tripService->createTrip($carrier->id, $validated);
            
            return redirect()->route('carrier.trips.show', $trip)
                ->with('success', 'Trip created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display trip details.
     */
    public function show(Trip $trip)
    {
        $carrier = $this->getCarrier();
        
        if ($trip->carrier_id != $carrier->id) {
            abort(403, 'You do not have access to this trip.');
        }

        $trip->load(['driver.user', 'vehicle', 'gpsPoints', 'violations', 'pauses.forcedByUser']);
        
        $gpsStats = null;
        if ($trip->gpsPoints->isNotEmpty()) {
            $gpsStats = $this->gpsService->getTripStatistics($trip);
        }

        // Get current HOS entry to check if driver is on break
        $currentEntry = null;
        $isOnBreak = false;
        if ($trip->driver) {
            $currentEntry = \App\Models\Hos\HosEntry::where('user_driver_detail_id', $trip->driver->id)
                ->whereNull('end_time')
                ->latest('start_time')
                ->first();
            
            $isOnBreak = $currentEntry && $currentEntry->status === 'on_duty_not_driving' && $currentEntry->trip_id === $trip->id;
        }

        // Get all trip report PDFs
        $tripReportPdfs = $trip->driver ? $trip->driver->getTripReportPdfs($trip->id) : collect();

        // Build timeline
        $timeline = $this->timelineService->buildTimeline($trip);
        $hosEntries = $this->timelineService->getHosEntriesForTrip($trip);

        // Verify destination arrival
        $destinationVerification = $this->verificationService->verifyArrival($trip);

        // Build Google Maps URLs
        $googleMapsUrls = [
            'origin' => $trip->origin_latitude && $trip->origin_longitude 
                ? $this->verificationService->buildGoogleMapsUrl($trip->origin_latitude, $trip->origin_longitude)
                : null,
            'destination' => $trip->destination_latitude && $trip->destination_longitude
                ? $this->verificationService->buildGoogleMapsUrl($trip->destination_latitude, $trip->destination_longitude)
                : null,
            'route' => $this->verificationService->buildRouteGoogleMapsUrl($trip, $trip->gpsPoints->last()),
        ];

        // Get last 5 HOS entries with coordinates for this trip
        $recentHosLocations = \App\Models\Hos\HosEntry::where('trip_id', $trip->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('start_time', 'desc')
            ->take(5)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'status' => $entry->status,
                    'status_name' => $entry->status_name ?? ucfirst(str_replace('_', ' ', $entry->status)),
                    'latitude' => $entry->latitude,
                    'longitude' => $entry->longitude,
                    'formatted_address' => $entry->formatted_address,
                    'start_time' => $entry->start_time,
                    'maps_url' => "https://www.google.com/maps?q={$entry->latitude},{$entry->longitude}",
                ];
            });
        
        // If no entries with coordinates for this trip, get any recent entries for the driver
        if ($recentHosLocations->isEmpty()) {
            $recentHosLocations = \App\Models\Hos\HosEntry::where('user_driver_detail_id', $trip->user_driver_detail_id)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderBy('start_time', 'desc')
                ->take(5)
                ->get()
                ->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'status' => $entry->status,
                        'status_name' => $entry->status_name ?? ucfirst(str_replace('_', ' ', $entry->status)),
                        'latitude' => $entry->latitude,
                        'longitude' => $entry->longitude,
                        'formatted_address' => $entry->formatted_address,
                        'start_time' => $entry->start_time,
                        'maps_url' => "https://www.google.com/maps?q={$entry->latitude},{$entry->longitude}",
                    ];
                });
        }

        return view('carrier.trips.show', [
            'trip' => $trip,
            'gpsStats' => $gpsStats,
            'isOnBreak' => $isOnBreak,
            'tripReportPdfs' => $tripReportPdfs,
            'timeline' => $timeline,
            'hosEntries' => $hosEntries,
            'destinationVerification' => $destinationVerification,
            'googleMapsUrls' => $googleMapsUrls,
            'recentHosLocations' => $recentHosLocations,
        ]);
    }

    /**
     * Show edit trip form.
     * Quick trips can be edited in any status (except cancelled) to complete their info.
     */
    public function edit(Trip $trip)
    {
        $carrier = $this->getCarrier();

        if ($trip->carrier_id != $carrier->id) {
            abort(403, 'You do not have access to this trip.');
        }

        // Quick trips can be edited in any status (except cancelled) to complete their info
        // Normal trips can only be edited when pending or accepted
        if (!$trip->isQuickTrip() && !$trip->isPending() && !$trip->isAccepted()) {
            return redirect()->route('carrier.trips.show', $trip)
                ->with('error', 'This trip cannot be edited.');
        }

        if ($trip->isCancelled()) {
            return redirect()->route('carrier.trips.show', $trip)
                ->with('error', 'Cancelled trips cannot be edited.');
        }

        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get();

        $vehicles = Vehicle::where('carrier_id', $carrier->id)
            ->where('status', 'active')
            ->get();

        return view('carrier.trips.edit', [
            'trip' => $trip,
            'drivers' => $drivers,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Update a trip.
     * For quick trips, this also handles completing the trip information.
     */
    public function update(Request $request, Trip $trip)
    {
        $carrier = $this->getCarrier();

        if ($trip->carrier_id != $carrier->id) {
            abort(403, 'You do not have access to this trip.');
        }

        // For quick trips that are in progress or completed, only allow updating info fields
        // not driver/vehicle assignment
        if ($trip->isQuickTrip() && ($trip->isInProgress() || $trip->isCompleted() || $trip->isPaused())) {
            $validated = $request->validate([
                'origin_address' => 'required|string|max:500',
                'destination_address' => 'required|string|max:500',
                'description' => 'nullable|string|max:1000',
                'notes' => 'nullable|string|max:1000',
                'load_type' => 'nullable|string|max:100',
                'load_weight' => 'nullable|numeric|min:0',
            ]);

            // Use the service to complete the quick trip info
            $this->tripService->completeQuickTripInfo($trip, $validated, Auth::id());

            $message = 'Trip information updated successfully.';
            if (!$trip->fresh()->requires_completion) {
                $message = 'Trip information completed successfully.';
            }

            return redirect()->route('carrier.trips.show', $trip)
                ->with('success', $message);
        }

        // Normal update for pending/accepted trips
        $validated = $request->validate([
            'driver_id' => 'required|exists:user_driver_details,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'origin_address' => 'required|string|max:500',
            'destination_address' => 'required|string|max:500',
            'scheduled_start_date' => 'required|date',
            'scheduled_end_date' => 'nullable|date|after:scheduled_start_date',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'load_type' => 'nullable|string|max:100',
            'load_weight' => 'nullable|numeric|min:0',
        ]);

        $validated['updated_by'] = Auth::id();
        $validated['user_driver_detail_id'] = $validated['driver_id'];
        unset($validated['driver_id']);

        // Update legacy destination field
        $validated['destination'] = $validated['destination_address'];

        $trip->update($validated);

        // For quick trips, check if info is now complete
        if ($trip->isQuickTrip() && $trip->hasCompleteInfo() && $trip->requires_completion) {
            $trip->markInfoAsComplete(Auth::id());
        }

        return redirect()->route('carrier.trips.show', $trip)
            ->with('success', 'Trip updated successfully.');
    }

    /**
     * Cancel a trip.
     */
    public function destroy(Request $request, Trip $trip)
    {
        $carrier = $this->getCarrier();
        
        if ($trip->carrier_id != $carrier->id) {
            abort(403, 'You do not have access to this trip.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->tripService->cancelTrip($trip, $request->reason, Auth::id());
            
            return redirect()->route('carrier.trips.index')
                ->with('success', 'Trip cancelled.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Force start a trip (emergency control by carrier/admin).
     */
    public function forceStart(Trip $trip)
    {
        $carrier = $this->getCarrier();
        
        if ($trip->carrier_id != $carrier->id) {
            abort(403, 'You do not have access to this trip.');
        }

        try {
            $this->tripService->forceStartTrip($trip);
            
            return redirect()->route('carrier.trips.show', $trip)
                ->with('success', 'Trip started successfully (emergency control).');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Force pause a trip (emergency control by carrier/admin).
     */
    public function forcePause(Trip $trip)
    {
        $carrier = $this->getCarrier();
        
        if ($trip->carrier_id != $carrier->id) {
            abort(403, 'You do not have access to this trip.');
        }

        try {
            $this->tripService->forcePauseTrip($trip, Auth::id(), 'Paused by carrier (emergency control)');
            
            return redirect()->route('carrier.trips.show', $trip)
                ->with('success', 'Trip paused successfully (emergency control).');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Force resume a trip (emergency control by carrier/admin).
     */
    public function forceResume(Trip $trip)
    {
        $carrier = $this->getCarrier();
        
        if ($trip->carrier_id != $carrier->id) {
            abort(403, 'You do not have access to this trip.');
        }

        try {
            $this->tripService->forceResumeTrip($trip, Auth::id());
            
            return redirect()->route('carrier.trips.show', $trip)
                ->with('success', 'Trip resumed successfully (emergency control).');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Force end a trip (emergency control by carrier/admin).
     */
    public function forceEnd(Trip $trip)
    {
        $carrier = $this->getCarrier();
        
        if ($trip->carrier_id != $carrier->id) {
            abort(403, 'You do not have access to this trip.');
        }

        try {
            $this->tripService->forceEndTrip($trip);
            
            return redirect()->route('carrier.trips.show', $trip)
                ->with('success', 'Trip ended successfully (emergency control).');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get the current carrier.
     */
    protected function getCarrier()
    {
        $user = Auth::user();
        
        // Check if user has carrier details (singular relationship)
        if ($user->carrierDetails) {
            return $user->carrierDetails->carrier;
        }

        // Check carriers relationship (many-to-many)
        $carrier = $user->carriers()->first();
        if ($carrier) {
            return $carrier;
        }

        abort(403, 'Carrier not found.');
    }
}
