<?php

namespace App\Http\Controllers\Admin;

use App\Models\Trip;
use App\Models\Carrier;
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
     * Display list of all trips across carriers.
     */
    public function index(Request $request)
    {
        $query = Trip::with(['carrier', 'driver.user', 'vehicle'])
            ->whereNull('deleted_at');

        // Filter by carrier
        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('scheduled_start_date', [
                $request->start_date,
                $request->end_date,
            ]);
        }

        $trips = $query->orderBy('scheduled_start_date', 'desc')
            ->paginate(20);

        $carriers = Carrier::orderBy('name')->get();

        return view('admin.trips.index', [
            'trips' => $trips,
            'carriers' => $carriers,
            'filters' => $request->only(['carrier_id', 'status', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Display trip details.
     */
    public function show(Trip $trip)
    {
        $trip->load(['carrier', 'driver.user', 'vehicle', 'gpsPoints', 'violations', 'hosEntries', 'pauses.forcedByUser']);

        $gpsStats = null;
        if ($trip->gpsPoints->isNotEmpty()) {
            $gpsStats = $this->gpsService->getTripStatistics($trip);
        }

        // Check if driver is on break
        $isOnBreak = false;
        if ($trip->isInProgress() || $trip->isPaused()) {
            $currentEntry = \App\Models\Hos\HosEntry::where('user_driver_detail_id', $trip->user_driver_detail_id)
                ->whereNull('end_time')
                ->latest('start_time')
                ->first();
            
            $isOnBreak = $currentEntry && $currentEntry->status === 'on_duty_not_driving';
        }

        // Get all trip report PDFs
        $tripReportPdfs = $trip->driver->getTripReportPdfs($trip->id);

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

        return view('admin.trips.show', [
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
     * Get trip statistics for dashboard.
     */
    public function statistics(Request $request)
    {
        $query = Trip::query();

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->carrier_id);
        }

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', Trip::STATUS_PENDING)->count(),
            'in_progress' => (clone $query)->where('status', Trip::STATUS_IN_PROGRESS)->count(),
            'completed' => (clone $query)->where('status', Trip::STATUS_COMPLETED)->count(),
            'cancelled' => (clone $query)->where('status', Trip::STATUS_CANCELLED)->count(),
            'with_violations' => (clone $query)->where('has_violations', true)->count(),
            'ghost_logs' => (clone $query)->where('forgot_to_close', true)->count(),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($stats);
        }

        return view('admin.trips.statistics', compact('stats'));
    }

    /**
     * Show create trip form.
     */
    public function create()
    {
        $carriers = Carrier::orderBy('name')->get();
        
        return view('admin.trips.create', [
            'carriers' => $carriers,
            'drivers' => collect(),
            'vehicles' => collect(),
        ]);
    }

    /**
     * Get drivers and vehicles for a carrier (AJAX).
     */
    public function getCarrierData(Request $request)
    {
        $carrierId = $request->get('carrier_id');
        
        if (!$carrierId) {
            return response()->json(['drivers' => [], 'vehicles' => []]);
        }

        $drivers = UserDriverDetail::where('carrier_id', $carrierId)
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

        $vehicles = Vehicle::where('carrier_id', $carrierId)
            ->where('status', 'active')
            ->get(['id', 'company_unit_number', 'make', 'model', 'year']);

        return response()->json([
            'drivers' => $drivers,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Store a new trip.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'driver_id' => 'required|exists:user_driver_details,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'origin_address' => 'required|string|max:500',
            'destination_address' => 'required|string|max:500',
            'scheduled_start_date' => 'required|date_format:m/d/Y',
            'scheduled_start_time' => 'required|date_format:H:i',
            'scheduled_end_date' => 'nullable|date_format:m/d/Y',
            'scheduled_end_time' => 'nullable|date_format:H:i',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'load_type' => 'nullable|string|max:100',
            'load_weight' => 'nullable|numeric|min:0',
        ]);

        // Combine date and time fields
        $validated['scheduled_start_date'] = \Carbon\Carbon::createFromFormat(
            'm/d/Y H:i',
            $validated['scheduled_start_date'] . ' ' . $validated['scheduled_start_time']
        );

        if (!empty($validated['scheduled_end_date']) && !empty($validated['scheduled_end_time'])) {
            $validated['scheduled_end_date'] = \Carbon\Carbon::createFromFormat(
                'm/d/Y H:i',
                $validated['scheduled_end_date'] . ' ' . $validated['scheduled_end_time']
            );
        } elseif (!empty($validated['scheduled_end_date'])) {
            $validated['scheduled_end_date'] = \Carbon\Carbon::createFromFormat('m/d/Y', $validated['scheduled_end_date'])->startOfDay();
        } else {
            $validated['scheduled_end_date'] = null;
        }

        $validated['created_by'] = Auth::id();

        try {
            $trip = $this->tripService->createTrip($validated['carrier_id'], $validated);
            
            return redirect()->route('admin.trips.show', $trip)
                ->with('success', 'Trip created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit trip form.
     */
    public function edit(Trip $trip)
    {
        $carriers = Carrier::orderBy('name')->get();
        
        $drivers = UserDriverDetail::where('carrier_id', $trip->carrier_id)
            ->with('user')
            ->get();

        $vehicles = Vehicle::where('carrier_id', $trip->carrier_id)
            ->where('status', 'active')
            ->get();

        return view('admin.trips.edit', [
            'trip' => $trip,
            'carriers' => $carriers,
            'drivers' => $drivers,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Update a trip.
     */
    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'driver_id' => 'required|exists:user_driver_details,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'origin_address' => 'required|string|max:500',
            'destination_address' => 'required|string|max:500',
            'scheduled_start_date' => 'required|date_format:m/d/Y',
            'scheduled_start_time' => 'required|date_format:H:i',
            'scheduled_end_date' => 'nullable|date_format:m/d/Y',
            'scheduled_end_time' => 'nullable|date_format:H:i',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Combine date and time fields
        $validated['scheduled_start_date'] = \Carbon\Carbon::createFromFormat(
            'm/d/Y H:i',
            $validated['scheduled_start_date'] . ' ' . $validated['scheduled_start_time']
        );

        if (!empty($validated['scheduled_end_date']) && !empty($validated['scheduled_end_time'])) {
            $validated['scheduled_end_date'] = \Carbon\Carbon::createFromFormat(
                'm/d/Y H:i',
                $validated['scheduled_end_date'] . ' ' . $validated['scheduled_end_time']
            );
        } elseif (!empty($validated['scheduled_end_date'])) {
            $validated['scheduled_end_date'] = \Carbon\Carbon::createFromFormat('m/d/Y', $validated['scheduled_end_date'])->startOfDay();
        } else {
            $validated['scheduled_end_date'] = null;
        }

        $validated['updated_by'] = Auth::id();
        $validated['user_driver_detail_id'] = $validated['driver_id'];

        $trip->update($validated);

        return redirect()->route('admin.trips.show', $trip)
            ->with('success', 'Trip updated successfully.');
    }

    /**
     * Delete a trip.
     */
    public function destroy(Trip $trip)
    {
        if ($trip->isInProgress()) {
            return back()->with('error', 'Cannot delete a trip that is in progress.');
        }

        $trip->delete();

        return redirect()->route('admin.trips.index')
            ->with('success', 'Trip deleted successfully.');
    }

    /**
     * Force start a trip (emergency control by admin).
     */
    public function forceStart(Trip $trip)
    {
        try {
            $this->tripService->forceStartTrip($trip);
            
            return redirect()->route('admin.trips.show', $trip)
                ->with('success', 'Trip started successfully (emergency control by admin).');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Force pause a trip (emergency control by admin).
     */
    public function forcePause(Trip $trip)
    {
        try {
            $this->tripService->forcePauseTrip($trip, Auth::id(), 'Paused by admin (emergency control)');
            
            return redirect()->route('admin.trips.show', $trip)
                ->with('success', 'Trip paused successfully (emergency control by admin).');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Force resume a trip (emergency control by admin).
     */
    public function forceResume(Trip $trip)
    {
        try {
            $this->tripService->forceResumeTrip($trip, Auth::id());
            
            return redirect()->route('admin.trips.show', $trip)
                ->with('success', 'Trip resumed successfully (emergency control by admin).');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Force end a trip (emergency control by admin).
     */
    public function forceEnd(Trip $trip)
    {
        try {
            $this->tripService->forceEndTrip($trip);
            
            return redirect()->route('admin.trips.show', $trip)
                ->with('success', 'Trip ended successfully (emergency control by admin).');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
