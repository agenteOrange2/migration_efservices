<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\VehicleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverVehicleController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->driverDetail) {
                abort(403, 'Access denied. Driver profile not found.');
            }
            return $next($request);
        });
    }

    /**
     * Get the authenticated driver's detail.
     */
    private function getDriverDetail()
    {
        return Auth::user()->driverDetail;
    }

    /**
     * Display a listing of the driver's assigned vehicles.
     */
    public function index()
    {
        $driver = $this->getDriverDetail()->load(['vehicles', 'activeVehicleAssignment.vehicle']);
        
        $vehicles = $driver->vehicles ?? collect();
        
        // Also check for active vehicle assignment if vehicles relationship is empty
        if ($vehicles->isEmpty() && $driver->activeVehicleAssignment && $driver->activeVehicleAssignment->vehicle) {
            $vehicles = collect([$driver->activeVehicleAssignment->vehicle]);
        }

        return view('driver.vehicles.index', compact('driver', 'vehicles'));
    }

    /**
     * Display the specified vehicle.
     */
    public function show($vehicleId)
    {
        $driver = $this->getDriverDetail()->load(['vehicles', 'activeVehicleAssignment.vehicle']);
        
        // Find vehicle in driver's assigned vehicles
        $vehicle = $driver->vehicles->find($vehicleId);
        
        // Check active assignment if not found
        if (!$vehicle && $driver->activeVehicleAssignment && $driver->activeVehicleAssignment->vehicle) {
            if ($driver->activeVehicleAssignment->vehicle->id == $vehicleId) {
                $vehicle = $driver->activeVehicleAssignment->vehicle;
            }
        }

        if (!$vehicle) {
            abort(404, 'Vehicle not found or not assigned to you.');
        }

        // Load maintenance records and documents for the vehicle
        $vehicle->load(['maintenances', 'documents']);

        // Get upcoming maintenances (next 5 pending)
        $upcomingMaintenances = $vehicle->maintenances()
            ->where('status', false)
            ->where('next_service_date', '>=', now())
            ->orderBy('next_service_date', 'asc')
            ->limit(5)
            ->get();

        // Get overdue maintenances
        $overdueMaintenances = $vehicle->maintenances()
            ->where('status', false)
            ->where('next_service_date', '<', now())
            ->orderBy('next_service_date', 'asc')
            ->limit(5)
            ->get();

        // Get recent completed maintenances
        $recentMaintenances = $vehicle->maintenances()
            ->where('status', true)
            ->orderBy('service_date', 'desc')
            ->limit(5)
            ->get();

        // Get active vehicle documents for driver access
        $vehicleDocuments = $vehicle->documents()
            ->where('status', 'active')
            ->orderBy('expiration_date', 'asc')
            ->get();

        return view('driver.vehicles.show', compact('driver', 'vehicle', 'upcomingMaintenances', 'overdueMaintenances', 'recentMaintenances', 'vehicleDocuments'));
    }

    /**
     * Download a vehicle document.
     */
    public function downloadDocument($vehicleId, $documentId)
    {
        $driver = $this->getDriverDetail()->load(['vehicles', 'activeVehicleAssignment.vehicle']);

        // Verify driver has access to this vehicle
        $vehicle = $driver->vehicles->find($vehicleId);
        if (!$vehicle && $driver->activeVehicleAssignment && $driver->activeVehicleAssignment->vehicle) {
            if ($driver->activeVehicleAssignment->vehicle->id == $vehicleId) {
                $vehicle = $driver->activeVehicleAssignment->vehicle;
            }
        }

        if (!$vehicle) {
            abort(404, 'Vehicle not found or not assigned to you.');
        }

        $document = VehicleDocument::where('id', $documentId)
            ->where('vehicle_id', $vehicleId)
            ->firstOrFail();

        $media = $document->getFirstMedia('document_files');

        if (!$media) {
            return redirect()->back()->with('error', 'File not found');
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Preview a vehicle document.
     */
    public function previewDocument($vehicleId, $documentId)
    {
        $driver = $this->getDriverDetail()->load(['vehicles', 'activeVehicleAssignment.vehicle']);

        // Verify driver has access to this vehicle
        $vehicle = $driver->vehicles->find($vehicleId);
        if (!$vehicle && $driver->activeVehicleAssignment && $driver->activeVehicleAssignment->vehicle) {
            if ($driver->activeVehicleAssignment->vehicle->id == $vehicleId) {
                $vehicle = $driver->activeVehicleAssignment->vehicle;
            }
        }

        if (!$vehicle) {
            abort(404, 'Vehicle not found or not assigned to you.');
        }

        $document = VehicleDocument::where('id', $documentId)
            ->where('vehicle_id', $vehicleId)
            ->firstOrFail();

        $media = $document->getFirstMedia('document_files');

        if (!$media) {
            return redirect()->back()->with('error', 'File not found');
        }

        $path = $media->getPath();
        $mimeType = $media->mime_type;

        // If it's a PDF, display in browser
        if ($mimeType === 'application/pdf' || strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
            ]);
        }

        // If it's an image, show preview
        if (strpos($mimeType, 'image/') === 0 || in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
            return response()->file($path, [
                'Content-Type' => $mimeType ?: 'image/' . strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
            ]);
        }

        // For other types, download
        return response()->download($path, basename($path));
    }
}
