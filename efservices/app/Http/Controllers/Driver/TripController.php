<?php

namespace App\Http\Controllers\Driver;

use App\Models\Trip;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Trip\TripService;
use App\Services\Hos\HosFMCSAService;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    protected TripService $tripService;
    protected HosFMCSAService $fmcsaService;

    public function __construct(TripService $tripService, HosFMCSAService $fmcsaService)
    {
        $this->tripService = $tripService;
        $this->fmcsaService = $fmcsaService;
    }

    /**
     * Show the form to create a new trip.
     * Drivers can create their own trips.
     */
    public function create()
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        // Get carrier info from driver
        $carrier = $driver->carrier;
        
        if (!$carrier) {
            abort(403, 'No carrier associated with your account.');
        }

        // Get assigned vehicles for this driver
        $vehicles = $driver->vehicleAssignments()
            ->where('status', 'active')
            ->with('vehicle')
            ->get()
            ->pluck('vehicle')
            ->filter();

        if ($vehicles->isEmpty()) {
            return redirect()->route('driver.trips.index')
                ->with('error', 'You do not have any vehicles assigned. Please contact your carrier.');
        }

        // Get FMCSA/HOS status
        $fmcsaStatus = $this->fmcsaService->getDriverFMCSAStatus($driver->id, $carrier->id);

        // Get weekly cycle status
        $weeklyCycleService = app(\App\Services\Hos\HosWeeklyCycleService::class);
        $weeklyCycleStatus = $weeklyCycleService->getWeeklyCycleStatus($driver->id);

        return view('driver.trips.create', [
            'driver' => $driver,
            'carrier' => $carrier,
            'vehicles' => $vehicles,
            'fmcsaStatus' => $fmcsaStatus,
            'weeklyCycleStatus' => $weeklyCycleStatus,
        ]);
    }

    /**
     * Store a new trip created by the driver.
     * Trip is created with 'accepted' status, ready to start when driver decides.
     */
    public function store(Request $request)
    {
        $driver = Auth::user()->driverDetail;

        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        $carrier = $driver->carrier;

        if (!$carrier) {
            abort(403, 'No carrier associated with your account.');
        }

        // Validate request
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'origin_address' => 'required|string|max:500',
            'destination_address' => 'required|string|max:500',
            'scheduled_start_date' => 'required|date|after_or_equal:today',
            'estimated_duration_minutes' => 'nullable|integer|min:15|max:720',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ], [
            'scheduled_start_date.after_or_equal' => 'The start date cannot be in the past.',
        ]);

        // Verify vehicle belongs to driver
        $hasVehicle = $driver->vehicleAssignments()
            ->where('status', 'active')
            ->where('vehicle_id', $validated['vehicle_id'])
            ->exists();

        if (!$hasVehicle) {
            return back()->withErrors(['vehicle_id' => 'This vehicle is not assigned to you.']);
        }

        // Validate FMCSA requirements before creating
        $fmcsaValidation = $this->fmcsaService->validateTripStart($driver->id, $carrier->id);

        if (!$fmcsaValidation['valid']) {
            $errorMessages = array_map(fn($e) => $e['message'], $fmcsaValidation['errors']);
            return back()
                ->withInput()
                ->withErrors(['fmcsa' => $errorMessages]);
        }

        try {
            // Create trip using TripService
            $trip = $this->tripService->createTrip($carrier->id, [
                'driver_id' => $driver->id,
                'vehicle_id' => $validated['vehicle_id'],
                'origin_address' => $validated['origin_address'],
                'destination_address' => $validated['destination_address'],
                'scheduled_start_date' => $validated['scheduled_start_date'],
                'estimated_duration_minutes' => $validated['estimated_duration_minutes'] ?? 60,
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Immediately accept the trip (driver-created trips are auto-accepted)
            $this->tripService->acceptTrip($trip, $driver->id);

            return redirect()->route('driver.trips.show', $trip)
                ->with('success', 'Trip created successfully! You can start it when ready.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error creating trip: ' . $e->getMessage());
        }
    }

    /**
     * Store a Quick Trip with minimal information.
     * Quick trips can be started immediately with just a vehicle selected.
     */
    public function storeQuickTrip(Request $request)
    {
        $driver = Auth::user()->driverDetail;

        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        $carrier = $driver->carrier;

        if (!$carrier) {
            abort(403, 'No carrier associated with your account.');
        }

        // Validate minimal request - only vehicle is required
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'origin_address' => 'nullable|string|max:500',
            'destination_address' => 'nullable|string|max:500',
            'estimated_duration_minutes' => 'nullable|integer|min:15|max:720',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Verify vehicle belongs to driver
        $hasVehicle = $driver->vehicleAssignments()
            ->where('status', 'active')
            ->where('vehicle_id', $validated['vehicle_id'])
            ->exists();

        if (!$hasVehicle) {
            return back()->withErrors(['vehicle_id' => 'This vehicle is not assigned to you.']);
        }

        // Validate FMCSA requirements before creating
        $fmcsaValidation = $this->fmcsaService->validateTripStart($driver->id, $carrier->id);

        if (!$fmcsaValidation['valid']) {
            $errorMessages = array_map(fn($e) => $e['message'], $fmcsaValidation['errors']);
            return back()
                ->withInput()
                ->withErrors(['fmcsa' => $errorMessages]);
        }

        try {
            // Create quick trip using TripService
            $trip = $this->tripService->createQuickTrip($carrier->id, [
                'driver_id' => $driver->id,
                'vehicle_id' => $validated['vehicle_id'],
                'origin_address' => $validated['origin_address'] ?? null,
                'destination_address' => $validated['destination_address'] ?? null,
                'estimated_duration_minutes' => $validated['estimated_duration_minutes'] ?? 60,
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Immediately accept the trip (driver-created trips are auto-accepted)
            $this->tripService->acceptTrip($trip, $driver->id);

            $message = 'Quick Trip created successfully!';
            if ($trip->requires_completion) {
                $message .= ' Remember: your carrier will need to complete the trip information later.';
            }

            return redirect()->route('driver.trips.show', $trip)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error creating quick trip: ' . $e->getMessage());
        }
    }


    /**
     * Display list of trips for the driver.
     */
    public function index(Request $request)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        $status = $request->get('status', 'all');
        
        $trips = $this->tripService->getDriverTrips($driver->id, [
            'status' => $status !== 'all' ? $status : null,
        ]);

        return view('driver.trips.index', [
            'trips' => $trips,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Display trip details.
     */
    public function show(Trip $trip)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver || $trip->user_driver_detail_id != $driver->id) {
            abort(403, 'You do not have access to this trip.');
        }

        // Get FMCSA status for the driver
        $fmcsaStatus = $this->fmcsaService->getDriverFMCSAStatus($driver->id, $trip->carrier_id);

        // Get current HOS entry to check if on break
        $currentEntry = \App\Models\Hos\HosEntry::where('user_driver_detail_id', $driver->id)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        // Use loose comparison (==) instead of strict (===) to handle type differences
        $isOnBreak = $currentEntry && $currentEntry->status === 'on_duty_not_driving' && $currentEntry->trip_id == $trip->id;

        return view('driver.trips.show', [
            'trip' => $trip->load(['vehicle', 'carrier', 'gpsPoints']),
            'fmcsaStatus' => $fmcsaStatus,
            'isOnBreak' => $isOnBreak,
        ]);
    }

    /**
     * Accept a trip.
     */
    public function accept(Trip $trip)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        try {
            $this->tripService->acceptTrip($trip, $driver->id);
            
            return redirect()->route('driver.trips.show', $trip)
                ->with('success', 'Trip accepted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a trip.
     */
    public function reject(Request $request, Trip $trip)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->tripService->rejectTrip($trip, $driver->id, $request->reason);
            
            return redirect()->route('driver.trips.index')
                ->with('success', 'Trip rejected.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show trip start page with pre-trip inspection.
     */
    public function startForm(Trip $trip)
    {
        $driver = Auth::user()->driverDetail;

        if (!$driver || $trip->user_driver_detail_id != $driver->id) {
            abort(403, 'You do not have access to this trip.');
        }

        if (!$trip->canBeStarted()) {
            return redirect()->route('driver.trips.show', $trip)
                ->with('error', 'This trip cannot be started.');
        }

        // Validate FMCSA requirements
        $validation = $this->fmcsaService->validateTripStart($driver->id, $trip->carrier_id);

        return view('driver.trips.start', [
            'trip' => $trip,
            'validation' => $validation,
            'tractorItems' => config('inspection.tractor_items'),
            'tractorColumns' => config('inspection.tractor_columns'),
            'trailerItems' => config('inspection.trailer_items'),
            'trailerColumns' => config('inspection.trailer_columns'),
        ]);
    }

    /**
     * Start a trip.
     */
    public function start(Request $request, Trip $trip)
    {
        $driver = Auth::user()->driverDetail;

        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        // Validate inspection checklist - all tractor items required EXCEPT "other" which is optional
        $tractorKeys = array_keys(config('inspection.tractor_items', []));
        $trailerKeys = array_keys(config('inspection.trailer_items', []));
        
        // Remove "other" from required keys - it's optional
        $requiredTractorKeys = array_filter($tractorKeys, fn($key) => $key !== 'other_tractor');
        $requiredTrailerKeys = array_filter($trailerKeys, fn($key) => $key !== 'other_trailer');

        $rules = [
            'has_trailer' => 'sometimes|boolean',
            'tractor' => 'required|array|min:' . count($requiredTractorKeys),
            'tractor.*' => 'required|string|in:' . implode(',', $tractorKeys),
            'other_tractor' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'condition_satisfactory' => 'required|accepted',
        ];

        // Add trailer validation if has_trailer is checked
        if ($request->boolean('has_trailer')) {
            $rules['trailer'] = 'required|array|min:' . count($requiredTrailerKeys);
            $rules['trailer.*'] = 'required|string|in:' . implode(',', $trailerKeys);
            $rules['other_trailer'] = 'nullable|string|max:255';
        }

        $messages = [
            'tractor.required' => 'You must complete the tractor/truck inspection checklist.',
            'tractor.min' => 'You must check all ' . count($requiredTractorKeys) . ' required tractor/truck inspection items.',
            'trailer.required' => 'You must complete the trailer inspection checklist.',
            'trailer.min' => 'You must check all ' . count($requiredTrailerKeys) . ' required trailer inspection items.',
            'condition_satisfactory.required' => 'You must certify the vehicle condition.',
            'condition_satisfactory.accepted' => 'You must confirm the vehicle is in satisfactory condition.',
        ];
        
        // Custom validation: if "other_tractor" is checked, require the text field
        $tractorItems = $request->input('tractor', []);
        if (in_array('other_tractor', $tractorItems) && empty($request->input('other_tractor'))) {
            return back()->withErrors(['other_tractor' => 'Please specify details for "Other" tractor item.'])->withInput();
        }
        
        // Custom validation: if "other_trailer" is checked, require the text field
        if ($request->boolean('has_trailer')) {
            $trailerItems = $request->input('trailer', []);
            if (in_array('other_trailer', $trailerItems) && empty($request->input('other_trailer'))) {
                return back()->withErrors(['other_trailer' => 'Please specify details for "Other" trailer item.'])->withInput();
            }
        }

        $request->validate($rules, $messages);

        try {
            $preInspection = [
                'has_trailer' => $request->boolean('has_trailer'),
                'tractor' => $request->input('tractor', []),
                'trailer' => $request->input('trailer', []),
                'other_tractor' => $request->input('other_tractor'),
                'other_trailer' => $request->input('other_trailer'),
                'remarks' => $request->input('remarks'),
                'condition_satisfactory' => $request->boolean('condition_satisfactory'),
            ];

            $this->tripService->startTrip($trip, $driver->id, $preInspection);

            return redirect()->route('driver.trips.show', $trip)
                ->with('success', 'Trip started successfully. Drive safely!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Show trip end page with post-trip inspection.
     */
    public function endForm(Trip $trip)
    {
        $driver = Auth::user()->driverDetail;

        if (!$driver || $trip->user_driver_detail_id != $driver->id) {
            abort(403, 'You do not have access to this trip.');
        }

        if (!$trip->canBeEnded()) {
            return redirect()->route('driver.trips.show', $trip)
                ->with('error', 'This trip cannot be ended.');
        }

        return view('driver.trips.end', [
            'trip' => $trip,
            'tractorItems' => config('inspection.tractor_items'),
            'tractorColumns' => config('inspection.tractor_columns'),
            'trailerItems' => config('inspection.trailer_items'),
            'trailerColumns' => config('inspection.trailer_columns'),
        ]);
    }

    /**
     * End a trip.
     */
    public function end(Request $request, Trip $trip)
    {
        $driver = Auth::user()->driverDetail;

        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        // Validate inspection checklist - all tractor items required EXCEPT "other" which is optional
        $tractorKeys = array_keys(config('inspection.tractor_items', []));
        $trailerKeys = array_keys(config('inspection.trailer_items', []));
        
        // Remove "other" from required keys - it's optional
        $requiredTractorKeys = array_filter($tractorKeys, fn($key) => $key !== 'other_tractor');
        $requiredTrailerKeys = array_filter($trailerKeys, fn($key) => $key !== 'other_trailer');

        $rules = [
            'tractor' => 'required|array|min:' . count($requiredTractorKeys),
            'tractor.*' => 'required|string|in:' . implode(',', $tractorKeys),
            'other_tractor' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'condition_satisfactory' => 'required|accepted',
            'notes' => 'nullable|string|max:2000',
        ];

        // Add trailer validation if trip has_trailer
        if ($trip->has_trailer) {
            $rules['trailer'] = 'required|array|min:' . count($requiredTrailerKeys);
            $rules['trailer.*'] = 'required|string|in:' . implode(',', $trailerKeys);
            $rules['other_trailer'] = 'nullable|string|max:255';
        }

        $messages = [
            'tractor.required' => 'You must complete the tractor/truck inspection checklist.',
            'tractor.min' => 'You must check all ' . count($requiredTractorKeys) . ' required tractor/truck inspection items.',
            'trailer.required' => 'You must complete the trailer inspection checklist.',
            'trailer.min' => 'You must check all ' . count($requiredTrailerKeys) . ' required trailer inspection items.',
            'condition_satisfactory.required' => 'You must certify the vehicle condition.',
            'condition_satisfactory.accepted' => 'You must confirm the vehicle is in satisfactory condition.',
        ];

        // Custom validation: if "other_tractor" is checked, require the text field
        $tractorItems = $request->input('tractor', []);
        if (in_array('other_tractor', $tractorItems) && empty($request->input('other_tractor'))) {
            return back()->withErrors(['other_tractor' => 'Please specify details for "Other" tractor item.'])->withInput();
        }
        
        // Custom validation: if "other_trailer" is checked, require the text field
        if ($trip->has_trailer) {
            $trailerItems = $request->input('trailer', []);
            if (in_array('other_trailer', $trailerItems) && empty($request->input('other_trailer'))) {
                return back()->withErrors(['other_trailer' => 'Please specify details for "Other" trailer item.'])->withInput();
            }
        }

        // Use Validator::make() to capture errors for additional processing
        $validator = \Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $postInspection = [
                'tractor' => $request->input('tractor', []),
                'trailer' => $request->input('trailer', []),
                'other_tractor' => $request->input('other_tractor'),
                'other_trailer' => $request->input('other_trailer'),
                'remarks' => $request->input('remarks'),
                'condition_satisfactory' => $request->boolean('condition_satisfactory'),
            ];
            $notes = $request->input('notes');

            $this->tripService->endTrip($trip, $driver->id, $postInspection, $notes);

            return redirect()->route('driver.trips.show', $trip)
                ->with('success', 'Trip completed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Pause a trip (for breaks - meals, rest, loading/unloading).
     */
    public function pause(Request $request, Trip $trip)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver || $trip->user_driver_detail_id != $driver->id) {
            abort(403, 'You do not have access to this trip.');
        }

        try {
            $location = null;
            if ($request->filled('latitude') && $request->filled('longitude')) {
                $location = [
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'address' => $request->input('address'),
                ];
            }

            $this->tripService->pauseTrip($trip, $driver->id, $location);
            
            return redirect()->route('driver.trips.show', $trip)
                ->with('success', 'Trip paused. You are now on break.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Resume a paused trip.
     */
    public function resume(Request $request, Trip $trip)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver || $trip->user_driver_detail_id != $driver->id) {
            abort(403, 'You do not have access to this trip.');
        }

        try {
            $location = null;
            if ($request->filled('latitude') && $request->filled('longitude')) {
                $location = [
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'address' => $request->input('address'),
                ];
            }

            $this->tripService->resumeTrip($trip, $driver->id, $location);
            
            return redirect()->route('driver.trips.show', $trip)
                ->with('success', 'Trip resumed. Drive safely!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get pending trips count (for dashboard/notifications).
     */
    public function pendingCount()
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver) {
            return response()->json(['count' => 0]);
        }

        $count = $this->tripService->getDriverPendingTrips($driver->id)->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Upload documents to a trip.
     */
    public function uploadDocuments(Request $request, Trip $trip)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver || $trip->user_driver_detail_id != $driver->id) {
            abort(403, 'You do not have access to this trip.');
        }

        if (!$trip->canUploadDocuments()) {
            return back()->with('error', 'Documents cannot be uploaded to this trip at this time.');
        }

        $request->validate([
            'documents' => 'required|array|min:1|max:10',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,gif|max:10240', // 10MB max
            'document_types' => 'required|array',
            'document_types.*' => 'required|string|in:' . implode(',', array_keys(Trip::DOCUMENT_TYPES)),
            'document_notes' => 'nullable|array',
            'document_notes.*' => 'nullable|string|max:255',
        ]);

        $uploadedCount = 0;

        foreach ($request->file('documents') as $index => $file) {
            $documentType = $request->input("document_types.{$index}", Trip::DOC_TYPE_OTHER);
            $documentNote = $request->input("document_notes.{$index}", '');

            $trip->addMedia($file)
                ->withCustomProperties([
                    'document_type' => $documentType,
                    'document_type_name' => Trip::getDocumentTypeName($documentType),
                    'notes' => $documentNote,
                    'uploaded_by' => Auth::id(),
                    'uploaded_by_name' => Auth::user()->name,
                    'uploaded_at' => now()->toIso8601String(),
                ])
                ->toMediaCollection('trip_documents');

            $uploadedCount++;
        }

        return back()->with('success', "{$uploadedCount} document(s) uploaded successfully.");
    }

    /**
     * Delete a document from a trip.
     */
    public function deleteDocument(Trip $trip, $mediaId)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver || $trip->user_driver_detail_id != $driver->id) {
            abort(403, 'You do not have access to this trip.');
        }

        if (!$trip->canDeleteDocuments()) {
            return back()->with('error', 'Documents cannot be deleted from this trip. The deletion window has expired.');
        }

        $media = $trip->getMedia('trip_documents')->where('id', $mediaId)->first();

        if (!$media) {
            return back()->with('error', 'Document not found.');
        }

        // Verify the document was uploaded by this driver (or allow if within 24 hours)
        $uploadedBy = $media->getCustomProperty('uploaded_by');
        if ($uploadedBy && $uploadedBy != Auth::id()) {
            return back()->with('error', 'You can only delete documents you uploaded.');
        }

        $media->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Download a document from a trip.
     */
    public function downloadDocument(Trip $trip, $mediaId)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver || $trip->user_driver_detail_id != $driver->id) {
            abort(403, 'You do not have access to this trip.');
        }

        $media = $trip->getMedia('trip_documents')->where('id', $mediaId)->first();

        if (!$media) {
            abort(404, 'Document not found.');
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Preview a document from a trip.
     */
    public function previewDocument(Trip $trip, $mediaId)
    {
        $driver = Auth::user()->driverDetail;
        
        if (!$driver || $trip->user_driver_detail_id != $driver->id) {
            abort(403, 'You do not have access to this trip.');
        }

        $media = $trip->getMedia('trip_documents')->where('id', $mediaId)->first();

        if (!$media) {
            abort(404, 'Document not found.');
        }

        // For images, return the preview conversion if available
        if (str_starts_with($media->mime_type, 'image/')) {
            $path = $media->hasGeneratedConversion('preview') 
                ? $media->getPath('preview') 
                : $media->getPath();
            
            return response()->file($path);
        }

        // For PDFs, return the file directly
        return response()->file($media->getPath());
    }
}
