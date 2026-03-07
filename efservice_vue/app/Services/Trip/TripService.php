<?php

namespace App\Services\Trip;

use App\Models\Trip;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosDailyLog;
use App\Models\UserDriverDetail;
use App\Services\Hos\HosFMCSAService;
use App\Services\Hos\HosWeeklyCycleService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TripService
{
    protected HosFMCSAService $fmcsaService;
    protected HosWeeklyCycleService $weeklyCycleService;
    protected TripPauseService $tripPauseService;

    public function __construct(
        HosFMCSAService $fmcsaService,
        HosWeeklyCycleService $weeklyCycleService,
        TripPauseService $tripPauseService
    ) {
        $this->fmcsaService = $fmcsaService;
        $this->weeklyCycleService = $weeklyCycleService;
        $this->tripPauseService = $tripPauseService;
    }

    /**
     * Create a new trip.
     */
    public function createTrip(int $carrierId, array $data): Trip
    {
        // Validate required fields
        $this->validateTripData($data);

        // Parse scheduled date for legacy fields
        $scheduledDate = Carbon::parse($data['scheduled_start_date']);

        // Calculate estimated duration in time format for legacy field
        $estimatedMinutes = $data['estimated_duration_minutes'] ?? 60;
        $hours = floor($estimatedMinutes / 60);
        $minutes = $estimatedMinutes % 60;
        $estimatedDurationTime = sprintf('%02d:%02d:00', $hours, $minutes);

        // Use transaction to prevent race conditions with trip_number generation
        $trip = \DB::transaction(function () use ($carrierId, $data, $scheduledDate, $estimatedDurationTime, $estimatedMinutes) {
            // Generate trip number inside transaction to prevent duplicates
            $tripNumber = $this->generateUniqueTripNumber();

            // Create the trip
            return Trip::create([
                'carrier_id' => $carrierId,
                'user_driver_detail_id' => $data['driver_id'],
                'vehicle_id' => $data['vehicle_id'],
                'created_by' => $data['created_by'] ?? auth()->id(),
                'trip_number' => $tripNumber,
                // Legacy fields (original table structure)
                'destination' => $data['destination_address'],
                'start_time' => $scheduledDate,
                'estimated_duration' => $estimatedDurationTime,
                // New FMCSA fields
                'origin_address' => $data['origin_address'],
                'origin_latitude' => $data['origin_latitude'] ?? null,
                'origin_longitude' => $data['origin_longitude'] ?? null,
                'destination_address' => $data['destination_address'],
                'destination_latitude' => $data['destination_latitude'] ?? null,
                'destination_longitude' => $data['destination_longitude'] ?? null,
                'scheduled_start_date' => $scheduledDate,
                'scheduled_end_date' => $data['scheduled_end_date'] ?? null,
                'estimated_duration_minutes' => $estimatedMinutes,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'load_type' => $data['load_type'] ?? null,
                'load_weight' => $data['load_weight'] ?? null,
                'status' => Trip::STATUS_PENDING,
            ]);
        });

        return $trip;
    }

    /**
     * Create a Quick Trip with minimal information.
     * Quick trips can be started immediately and completed with full info later.
     */
    public function createQuickTrip(int $carrierId, array $data): Trip
    {
        // Validate minimal required fields for quick trip
        $this->validateQuickTripData($data);

        // Use current time as scheduled start
        $scheduledDate = Carbon::now();

        // Default estimated duration
        $estimatedMinutes = $data['estimated_duration_minutes'] ?? 60;
        $hours = floor($estimatedMinutes / 60);
        $minutes = $estimatedMinutes % 60;
        $estimatedDurationTime = sprintf('%02d:%02d:00', $hours, $minutes);

        // Determine if trip needs completion (missing origin or destination)
        $needsCompletion = empty($data['origin_address']) || empty($data['destination_address']);

        // Use transaction to prevent race conditions with trip_number generation
        $trip = \DB::transaction(function () use ($carrierId, $data, $scheduledDate, $estimatedDurationTime, $estimatedMinutes, $needsCompletion) {
            // Generate trip number inside transaction to prevent duplicates
            $tripNumber = $this->generateUniqueTripNumber();

            // Create the quick trip
            return Trip::create([
                'carrier_id' => $carrierId,
                'user_driver_detail_id' => $data['driver_id'],
                'vehicle_id' => $data['vehicle_id'],
                'created_by' => $data['created_by'] ?? auth()->id(),
                'trip_number' => $tripNumber,
                // Legacy fields
                'destination' => $data['destination_address'] ?? 'Quick Trip - To be completed',
                'start_time' => $scheduledDate,
                'estimated_duration' => $estimatedDurationTime,
                // FMCSA fields - these can be empty for quick trips
                'origin_address' => $data['origin_address'] ?? null,
                'origin_latitude' => $data['origin_latitude'] ?? null,
                'origin_longitude' => $data['origin_longitude'] ?? null,
                'destination_address' => $data['destination_address'] ?? null,
                'destination_latitude' => $data['destination_latitude'] ?? null,
                'destination_longitude' => $data['destination_longitude'] ?? null,
                'scheduled_start_date' => $scheduledDate,
                'scheduled_end_date' => $data['scheduled_end_date'] ?? null,
                'estimated_duration_minutes' => $estimatedMinutes,
                'description' => $data['description'] ?? 'Quick Trip',
                'notes' => $data['notes'] ?? null,
                'load_type' => $data['load_type'] ?? null,
                'load_weight' => $data['load_weight'] ?? null,
                'status' => Trip::STATUS_PENDING,
                // Quick trip specific fields
                'is_quick_trip' => true,
                'requires_completion' => $needsCompletion,
            ]);
        });

        return $trip;
    }

    /**
     * Validate quick trip data (minimal requirements).
     */
    protected function validateQuickTripData(array $data): void
    {
        // Only vehicle_id and driver_id are required for quick trips
        $required = ['driver_id', 'vehicle_id'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw ValidationException::withMessages([
                    $field => ["The {$field} field is required for quick trips."],
                ]);
            }
        }
    }

    /**
     * Complete quick trip information.
     */
    public function completeQuickTripInfo(Trip $trip, array $data, int $userId): Trip
    {
        if (!$trip->isQuickTrip()) {
            throw ValidationException::withMessages([
                'trip' => ['This is not a quick trip.'],
            ]);
        }

        // Update with provided data
        $updateData = [];

        if (!empty($data['origin_address'])) {
            $updateData['origin_address'] = $data['origin_address'];
            $updateData['origin_latitude'] = $data['origin_latitude'] ?? null;
            $updateData['origin_longitude'] = $data['origin_longitude'] ?? null;
        }

        if (!empty($data['destination_address'])) {
            $updateData['destination_address'] = $data['destination_address'];
            $updateData['destination_latitude'] = $data['destination_latitude'] ?? null;
            $updateData['destination_longitude'] = $data['destination_longitude'] ?? null;
            // Also update legacy field
            $updateData['destination'] = $data['destination_address'];
        }

        if (!empty($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        if (!empty($data['notes'])) {
            $updateData['notes'] = $data['notes'];
        }

        if (!empty($data['load_type'])) {
            $updateData['load_type'] = $data['load_type'];
        }

        if (isset($data['load_weight'])) {
            $updateData['load_weight'] = $data['load_weight'];
        }

        $updateData['updated_by'] = $userId;

        $trip->update($updateData);
        $trip->refresh();

        // Check if all required info is now complete
        if ($trip->hasCompleteInfo()) {
            $trip->markInfoAsComplete($userId);
        }

        return $trip->fresh();
    }

    /**
     * Generate a unique trip number within current transaction.
     */
    protected function generateUniqueTripNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "TRP-{$date}-";
        
        // Get the last trip number with today's date prefix (including soft deleted)
        $lastTrip = Trip::withTrashed()
            ->where('trip_number', 'LIKE', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING(trip_number, -4) AS UNSIGNED) DESC")
            ->lockForUpdate()
            ->first();
        
        if ($lastTrip && preg_match('/TRP-\d{8}-(\d{4})$/', $lastTrip->trip_number, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        } else {
            $sequence = 1;
        }
        
        $tripNumber = sprintf('TRP-%s-%04d', $date, $sequence);
        
        // Double-check uniqueness - include soft deleted
        $attempts = 0;
        while (Trip::withTrashed()->where('trip_number', $tripNumber)->exists() && $attempts < 100) {
            $sequence++;
            $tripNumber = sprintf('TRP-%s-%04d', $date, $sequence);
            $attempts++;
        }
        
        return $tripNumber;
    }

    /**
     * Assign a driver to a trip.
     */
    public function assignDriver(Trip $trip, int $driverId): Trip
    {
        // Check if driver has sufficient weekly hours
        $weeklyStatus = $this->weeklyCycleService->getWeeklyCycleStatus($driverId);
        
        if ($weeklyStatus['is_over_limit']) {
            throw ValidationException::withMessages([
                'driver_id' => ['Driver has exceeded weekly hour limit and cannot be assigned.'],
            ]);
        }

        // Check estimated trip duration against remaining hours
        if ($trip->estimated_duration_minutes) {
            $estimatedHours = $trip->estimated_duration_minutes / 60;
            if ($estimatedHours > $weeklyStatus['hours_remaining']) {
                throw ValidationException::withMessages([
                    'driver_id' => ["Driver only has {$weeklyStatus['hours_remaining']} hours remaining, but trip requires approximately {$estimatedHours} hours."],
                ]);
            }
        }

        // Check for active penalties
        $penaltyCheck = $this->fmcsaService->hasBlockingPenalty($driverId);
        if ($penaltyCheck['has_penalty']) {
            throw ValidationException::withMessages([
                'driver_id' => ["Driver has an active penalty ({$penaltyCheck['penalty_type']}) and cannot be assigned."],
            ]);
        }

        $trip->update([
            'user_driver_detail_id' => $driverId,
        ]);

        return $trip->fresh();
    }

    /**
     * Accept a trip (by driver).
     */
    public function acceptTrip(Trip $trip, int $driverId): Trip
    {
        if ($trip->user_driver_detail_id != $driverId) {
            throw ValidationException::withMessages([
                'trip' => ['This trip is not assigned to you.'],
            ]);
        }

        if (!$trip->isPending()) {
            throw ValidationException::withMessages([
                'trip' => ['This trip cannot be accepted in its current state.'],
            ]);
        }

        $trip->update([
            'status' => Trip::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        return $trip->fresh();
    }

    /**
     * Reject a trip (by driver).
     */
    public function rejectTrip(Trip $trip, int $driverId, string $reason): Trip
    {
        if ($trip->user_driver_detail_id != $driverId) {
            throw ValidationException::withMessages([
                'trip' => ['This trip is not assigned to you.'],
            ]);
        }

        if (empty($reason)) {
            throw ValidationException::withMessages([
                'reason' => ['A rejection reason is required.'],
            ]);
        }

        $trip->update([
            'status' => Trip::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $driverId,
            'cancelled_at' => now(),
        ]);

        return $trip->fresh();
    }

    /**
     * Start a trip (by driver).
     */
    public function startTrip(Trip $trip, int $driverId, array $preInspection = []): Trip
    {
        if ($trip->user_driver_detail_id != $driverId) {
            throw ValidationException::withMessages([
                'trip' => ['This trip is not assigned to you.'],
            ]);
        }

        if (!$trip->canBeStarted()) {
            throw ValidationException::withMessages([
                'trip' => ['This trip cannot be started in its current state.'],
            ]);
        }

        // Validate FMCSA requirements
        $validation = $this->fmcsaService->validateTripStart($driverId, $trip->carrier_id);

        if (!$validation['valid']) {
            $errorMessages = array_map(fn($e) => $e['message'], $validation['errors']);
            throw ValidationException::withMessages([
                'fmcsa' => $errorMessages,
            ]);
        }

        // Process inspection checklist data
        $hasTrailer = !empty($preInspection['has_trailer']);
        $inspectionData = $this->processInspectionData($preInspection, $hasTrailer);
        $hasDefects = !empty($preInspection['remarks']);

        // Update trip status
        $trip->update([
            'status' => Trip::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'actual_start_time' => now(),
            'pre_trip_inspection_completed' => !empty($preInspection['tractor']),
            'pre_trip_inspection_at' => !empty($preInspection['tractor']) ? now() : null,
            'pre_trip_inspection_data' => $inspectionData,
            'has_trailer' => $hasTrailer,
            'pre_trip_remarks' => $preInspection['remarks'] ?? null,
            'pre_trip_defects_found' => $hasDefects,
            'vehicle_condition_satisfactory' => !empty($preInspection['condition_satisfactory']),
        ]);

        // Create HOS entry for driving
        $this->createDrivingEntry($driverId, $trip);

        // Start duty period if not already started
        $this->startDutyPeriod($driverId, $trip->carrier_id);

        return $trip->fresh();
    }

    /**
     * End a trip (by driver).
     */
    public function endTrip(Trip $trip, int $driverId, array $postInspection = [], ?string $notes = null): Trip
    {
        if ($trip->user_driver_detail_id != $driverId) {
            throw ValidationException::withMessages([
                'trip' => ['This trip is not assigned to you.'],
            ]);
        }

        if (!$trip->canBeEnded()) {
            throw ValidationException::withMessages([
                'trip' => ['This trip cannot be ended in its current state.'],
            ]);
        }

        // Close the current HOS driving entry
        $this->closeDrivingEntry($driverId, $trip);

        // Calculate actual duration
        $actualDuration = $trip->actual_start_time
            ? $trip->actual_start_time->diffInMinutes(now())
            : null;

        // Process inspection checklist data (use trip's has_trailer flag)
        $inspectionData = $this->processInspectionData($postInspection, $trip->has_trailer);
        $hasDefects = !empty($postInspection['remarks']);

        // Update trip
        $trip->update([
            'status' => Trip::STATUS_COMPLETED,
            'completed_at' => now(),
            'actual_end_time' => now(),
            'actual_duration_minutes' => $actualDuration,
            'post_trip_inspection_completed' => !empty($postInspection['tractor']),
            'post_trip_inspection_at' => !empty($postInspection['tractor']) ? now() : null,
            'post_trip_inspection_data' => $inspectionData,
            'post_trip_remarks' => $postInspection['remarks'] ?? null,
            'post_trip_defects_found' => $hasDefects,
            'driver_notes' => $notes,
        ]);

        // Create off-duty HOS entry
        $this->createOffDutyEntry($driverId, $trip);

        return $trip->fresh();
    }

    /**
     * Pause a trip (for breaks - meals, rest, loading/unloading).
     */
    public function pauseTrip(Trip $trip, int $driverId, ?array $location = null, ?string $reason = null, ?int $forcedBy = null): Trip
    {
        if ($trip->user_driver_detail_id != $driverId) {
            throw ValidationException::withMessages([
                'trip' => ['This trip is not assigned to you.'],
            ]);
        }

        if (!$trip->isInProgress()) {
            throw ValidationException::withMessages([
                'trip' => ['Only trips in progress can be paused.'],
            ]);
        }

        // Check current entry status
        $currentEntry = HosEntry::where('user_driver_detail_id', $driverId)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        // If already on break, don't create another break entry
        if ($currentEntry && $currentEntry->status === 'on_duty_not_driving' && $currentEntry->trip_id == $trip->id) {
            return $trip->fresh();
        }

        // Close the current driving entry
        $this->closeDrivingEntry($driverId, $trip);

        // Get vehicle_id from trip or driver's active assignment
        $vehicleId = $trip->vehicle_id;
        if (!$vehicleId) {
            $driver = UserDriverDetail::find($driverId);
            $vehicleId = $driver?->activeVehicleAssignment?->vehicle_id;
        }

        // Create on-duty not driving entry (for break)
        HosEntry::create([
            'user_driver_detail_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'carrier_id' => $trip->carrier_id,
            'trip_id' => $trip->id,
            'status' => 'on_duty_not_driving',
            'start_time' => now(),
            'date' => today(),
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'formatted_address' => $location['address'] ?? null,
            'location_available' => isset($location['latitude']) && isset($location['longitude']),
        ]);

        // Create TripPause record
        $this->tripPauseService->createPause($trip, $location, $reason, $forcedBy);

        // Update trip status to paused
        $trip->update(['status' => Trip::STATUS_PAUSED]);

        return $trip->fresh();
    }

    /**
     * Resume a paused trip.
     */
    public function resumeTrip(Trip $trip, int $driverId, ?array $location = null): Trip
    {
        if ($trip->user_driver_detail_id != $driverId) {
            throw ValidationException::withMessages([
                'trip' => ['This trip is not assigned to you.'],
            ]);
        }

        if (!$trip->isPaused()) {
            throw ValidationException::withMessages([
                'trip' => ['Only paused trips can be resumed.'],
            ]);
        }

        // Check daily driving limits (but not 10-hour reset, since we're continuing the same duty period)
        $drivingLimit = $this->fmcsaService->checkDrivingLimit($driverId, $trip->carrier_id);
        $dutyPeriod = $this->fmcsaService->checkDutyPeriod($driverId, $trip->carrier_id);
        $weeklyLimit = $this->fmcsaService->checkWeeklyCycle($driverId, $trip->carrier_id);
        
        // Check if driver has exceeded 12-hour driving limit
        if ($drivingLimit['is_exceeded']) {
            throw ValidationException::withMessages([
                'fmcsa' => ['You have reached the 12-hour daily driving limit and cannot continue driving. You must take a 10-hour rest period.'],
            ]);
        }
        
        // Check if driver has exceeded 14-hour duty period
        if ($dutyPeriod['is_exceeded']) {
            throw ValidationException::withMessages([
                'fmcsa' => ['You have reached the 14-hour duty period limit and cannot continue driving. You must take a rest period.'],
            ]);
        }

        // Check weekly limit
        if ($weeklyLimit['is_over_limit']) {
            throw ValidationException::withMessages([
                'fmcsa' => ['You have exceeded your weekly cycle limit and cannot continue driving. You must take a 34-hour reset.'],
            ]);
        }

        // Check if approaching limit (less than 30 minutes remaining) - warn but allow
        $warningMessages = [];
        if ($drivingLimit['remaining_minutes'] <= 30 && $drivingLimit['remaining_minutes'] > 0) {
            $warningMessages[] = "Warning: Only {$drivingLimit['remaining_minutes']} minutes of driving time remaining today.";
        }
        if ($dutyPeriod['remaining_minutes'] <= 30 && $dutyPeriod['remaining_minutes'] > 0) {
            $warningMessages[] = "Warning: Only {$dutyPeriod['remaining_minutes']} minutes left in your duty period.";
        }

        // Close the current on-duty not driving entry
        $currentEntry = HosEntry::where('user_driver_detail_id', $driverId)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($currentEntry) {
            $currentEntry->update(['end_time' => now()]);
        }

        // End the active TripPause
        $this->tripPauseService->endPause($trip);

        // Get vehicle_id from trip or driver's active assignment
        $vehicleId = $trip->vehicle_id;
        if (!$vehicleId) {
            $driver = UserDriverDetail::find($driverId);
            $vehicleId = $driver?->activeVehicleAssignment?->vehicle_id;
        }

        // Create new driving entry
        HosEntry::create([
            'user_driver_detail_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'carrier_id' => $trip->carrier_id,
            'trip_id' => $trip->id,
            'status' => 'on_duty_driving',
            'start_time' => now(),
            'date' => today(),
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'formatted_address' => $location['address'] ?? null,
            'location_available' => isset($location['latitude']) && isset($location['longitude']),
        ]);

        // Update trip status back to in_progress
        $trip->update(['status' => Trip::STATUS_IN_PROGRESS]);

        $result = $trip->fresh();
        
        // Attach warnings to session if any
        if (!empty($warningMessages)) {
            session()->flash('hos_warnings', $warningMessages);
        }

        return $result;
    }

    /**
     * Cancel a trip.
     */
    public function cancelTrip(Trip $trip, string $reason, int $cancelledBy): Trip
    {
        if ($trip->isCompleted() || $trip->isCancelled()) {
            throw ValidationException::withMessages([
                'trip' => ['This trip cannot be cancelled.'],
            ]);
        }

        // If trip is in progress, close the driving entry
        if ($trip->isInProgress()) {
            $this->closeDrivingEntry($trip->user_driver_detail_id, $trip);
        }

        $trip->update([
            'status' => Trip::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);

        return $trip->fresh();
    }

    /**
     * Get pending trips for a driver.
     */
    public function getDriverPendingTrips(int $driverId): Collection
    {
        return Trip::forDriver($driverId)
            ->pending()
            ->orderBy('scheduled_start_date')
            ->get();
    }

    /**
     * Get all trips for a driver.
     */
    public function getDriverTrips(int $driverId, array $filters = []): Collection
    {
        $query = Trip::forDriver($driverId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->scheduledBetween($filters['start_date'], $filters['end_date']);
        }

        return $query->orderBy('scheduled_start_date', 'desc')->get();
    }

    /**
     * Get trips for a carrier.
     */
    public function getCarrierTrips(int $carrierId, array $filters = []): Collection
    {
        $query = Trip::forCarrier($carrierId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['driver_id'])) {
            $query->forDriver($filters['driver_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->scheduledBetween($filters['start_date'], $filters['end_date']);
        }

        return $query->orderBy('scheduled_start_date', 'desc')->get();
    }

    /**
     * Validate trip data.
     */
    protected function validateTripData(array $data): void
    {
        $required = ['driver_id', 'vehicle_id', 'origin_address', 'destination_address', 'scheduled_start_date'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw ValidationException::withMessages([
                    $field => ["The {$field} field is required."],
                ]);
            }
        }
    }

    /**
     * Create a driving HOS entry.
     */
    protected function createDrivingEntry(int $driverId, Trip $trip): HosEntry
    {
        // Close any open entries first
        HosEntry::forDriver($driverId)
            ->open()
            ->update(['end_time' => now()]);

        // Get vehicle_id from trip or driver's active assignment
        $vehicleId = $trip->vehicle_id;
        if (!$vehicleId) {
            $driver = UserDriverDetail::find($driverId);
            $vehicleId = $driver?->activeVehicleAssignment?->vehicle_id;
        }

        return HosEntry::create([
            'user_driver_detail_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'carrier_id' => $trip->carrier_id,
            'trip_id' => $trip->id,
            'status' => HosEntry::STATUS_ON_DUTY_DRIVING,
            'start_time' => now(),
            'latitude' => $trip->origin_latitude,
            'longitude' => $trip->origin_longitude,
            'formatted_address' => $trip->origin_address,
            'location_available' => $trip->origin_latitude !== null,
            'date' => today(),
        ]);
    }

    /**
     * Close the driving HOS entry.
     */
    protected function closeDrivingEntry(int $driverId, Trip $trip): void
    {
        HosEntry::forDriver($driverId)
            ->where('trip_id', $trip->id)
            ->where('status', HosEntry::STATUS_ON_DUTY_DRIVING)
            ->open()
            ->update(['end_time' => now()]);
    }

    /**
     * Create an off-duty HOS entry.
     */
    protected function createOffDutyEntry(int $driverId, Trip $trip): HosEntry
    {
        // Get vehicle_id from trip or driver's active assignment
        $vehicleId = $trip->vehicle_id;
        if (!$vehicleId) {
            $driver = UserDriverDetail::find($driverId);
            $vehicleId = $driver?->activeVehicleAssignment?->vehicle_id;
        }

        return HosEntry::create([
            'user_driver_detail_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'carrier_id' => $trip->carrier_id,
            'trip_id' => $trip->id,
            'status' => HosEntry::STATUS_OFF_DUTY,
            'start_time' => now(),
            'latitude' => $trip->destination_latitude,
            'longitude' => $trip->destination_longitude,
            'formatted_address' => $trip->destination_address,
            'location_available' => $trip->destination_latitude !== null,
            'date' => today(),
        ]);
    }

    /**
     * Start duty period for the day.
     */
    protected function startDutyPeriod(int $driverId, int $carrierId): void
    {
        $dailyLog = HosDailyLog::getOrCreateForDate($driverId, $carrierId, null, today());
        
        if (!$dailyLog->duty_period_start) {
            $dailyLog->startDutyPeriod();
        }
    }

    /**
     * Emergency control: Start trip without FMCSA validation (for admin/carrier emergency use).
     */
    public function forceStartTrip(Trip $trip): Trip
    {
        if (!$trip->canBeStarted()) {
            throw ValidationException::withMessages([
                'trip' => ['This trip cannot be started in its current state.'],
            ]);
        }

        // Update trip status
        $trip->update([
            'status' => Trip::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'actual_start_time' => now(),
        ]);

        // Create HOS entry for driving
        $this->createDrivingEntry($trip->user_driver_detail_id, $trip);

        // Start duty period if not already started
        $this->startDutyPeriod($trip->user_driver_detail_id, $trip->carrier_id);

        return $trip->fresh();
    }

    /**
     * Emergency control: Pause trip without driver action (for admin/carrier emergency use).
     */
    public function forcePauseTrip(Trip $trip, int $forcedBy, ?string $reason = null): Trip
    {
        if (!$trip->isInProgress()) {
            throw ValidationException::withMessages([
                'trip' => ['Only trips in progress can be paused.'],
            ]);
        }

        // Close the current driving entry
        $this->closeDrivingEntry($trip->user_driver_detail_id, $trip);

        // Get vehicle_id from trip or driver's active assignment
        $vehicleId = $trip->vehicle_id;
        if (!$vehicleId) {
            $driver = UserDriverDetail::find($trip->user_driver_detail_id);
            $vehicleId = $driver?->activeVehicleAssignment?->vehicle_id;
        }

        // Create on-duty not driving entry (for break)
        HosEntry::create([
            'user_driver_detail_id' => $trip->user_driver_detail_id,
            'vehicle_id' => $vehicleId,
            'carrier_id' => $trip->carrier_id,
            'trip_id' => $trip->id,
            'status' => 'on_duty_not_driving',
            'start_time' => now(),
            'date' => today(),
            'latitude' => null,
            'longitude' => null,
            'location' => 'Paused trip (emergency control)',
        ]);

        // Create TripPause record with forced_by
        $this->tripPauseService->createPause($trip, null, $reason ?? 'Forced pause by admin/carrier', $forcedBy);

        // Update trip status to paused
        $trip->update(['status' => Trip::STATUS_PAUSED]);

        return $trip->fresh();
    }

    /**
     * Emergency control: Resume trip without FMCSA validation (for admin/carrier emergency use).
     */
    public function forceResumeTrip(Trip $trip, ?int $forcedBy = null): Trip
    {
        if (!$trip->isPaused()) {
            throw ValidationException::withMessages([
                'trip' => ['Only paused trips can be resumed.'],
            ]);
        }

        // Close the current on-duty not driving entry
        $currentEntry = HosEntry::where('user_driver_detail_id', $trip->user_driver_detail_id)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($currentEntry) {
            $currentEntry->update(['end_time' => now()]);
        }

        // End the active TripPause
        $this->tripPauseService->endPause($trip);

        // Get vehicle_id from trip or driver's active assignment
        $vehicleId = $trip->vehicle_id;
        if (!$vehicleId) {
            $driver = UserDriverDetail::find($trip->user_driver_detail_id);
            $vehicleId = $driver?->activeVehicleAssignment?->vehicle_id;
        }

        // Create new driving entry
        HosEntry::create([
            'user_driver_detail_id' => $trip->user_driver_detail_id,
            'vehicle_id' => $vehicleId,
            'carrier_id' => $trip->carrier_id,
            'trip_id' => $trip->id,
            'status' => 'on_duty_driving',
            'start_time' => now(),
            'date' => today(),
            'latitude' => null,
            'longitude' => null,
            'location' => 'Resumed trip (emergency control)',
        ]);

        // Update trip status back to in_progress
        $trip->update(['status' => Trip::STATUS_IN_PROGRESS]);

        return $trip->fresh();
    }

    /**
     * Emergency control: End trip without driver action (for admin/carrier emergency use).
     */
    public function forceEndTrip(Trip $trip): Trip
    {
        if (!$trip->canBeEnded()) {
            throw ValidationException::withMessages([
                'trip' => ['This trip cannot be ended in its current state.'],
            ]);
        }

        // If trip is paused, end the active pause first
        if ($trip->isPaused()) {
            $this->tripPauseService->endPause($trip);
        }

        // Close the current HOS entry
        $currentEntry = HosEntry::where('user_driver_detail_id', $trip->user_driver_detail_id)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($currentEntry) {
            $currentEntry->update(['end_time' => now()]);
        }

        // Calculate actual duration
        $actualDuration = $trip->actual_start_time 
            ? $trip->actual_start_time->diffInMinutes(now()) 
            : null;

        // Update trip
        $trip->update([
            'status' => Trip::STATUS_COMPLETED,
            'completed_at' => now(),
            'actual_end_time' => now(),
            'actual_duration_minutes' => $actualDuration,
        ]);

        // Create off-duty HOS entry
        $this->createOffDutyEntry($trip->user_driver_detail_id, $trip);

        return $trip->fresh();
    }

    /**
     * Process inspection checklist data from form submission.
     *
     * @param array $data Form data containing tractor, trailer, and other_* fields
     * @param bool $hasTrailer Whether the trip includes a trailer
     * @return array Structured inspection data for JSON storage
     */
    protected function processInspectionData(array $data, bool $hasTrailer): array
    {
        $tractorItems = config('inspection.tractor_items', []);
        $trailerItems = config('inspection.trailer_items', []);

        $result = [
            'tractor' => [],
            'inspection_timestamp' => now()->toIso8601String(),
        ];

        // Process tractor items - store which items were checked
        $checkedTractor = $data['tractor'] ?? [];
        foreach ($tractorItems as $key => $label) {
            $result['tractor'][$key] = in_array($key, $checkedTractor);
        }

        // Add "other" text if provided
        if (!empty($data['other_tractor'])) {
            $result['other_tractor_text'] = $data['other_tractor'];
        }

        // Process trailer items if applicable
        if ($hasTrailer) {
            $result['trailer'] = [];
            $checkedTrailer = $data['trailer'] ?? [];
            foreach ($trailerItems as $key => $label) {
                $result['trailer'][$key] = in_array($key, $checkedTrailer);
            }

            // Add "other" text if provided
            if (!empty($data['other_trailer'])) {
                $result['other_trailer_text'] = $data['other_trailer'];
            }
        }

        return $result;
    }
}
