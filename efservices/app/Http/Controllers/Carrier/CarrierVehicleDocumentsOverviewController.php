<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarrierVehicleDocumentsOverviewController extends Controller
{
    /**
     * Display a comprehensive overview of all vehicle documents for the carrier.
     * 
     * Requirements: 1.1, 1.3
     */
    public function index(Request $request)
    {
        // Get authenticated carrier
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Base query with eager loading for performance
        $query = Vehicle::with([
            'carrier',
            'documents' => function ($query) {
                $query->orderBy('expiration_date', 'asc');
            }
        ])->where('carrier_id', $carrier->id);
        
        // Apply filters if present
        $this->applyFilters($query, $request);
        
        // Paginate results (10 per page)
        $vehicles = $query->paginate(10)->appends($request->query());
        
        // Calculate summary statistics
        $statistics = $this->calculateSummaryStatistics($carrier);
        
        // Get document types for filter dropdown
        $documentTypes = $this->getDocumentTypes();
        
        return view('carrier.vehicles.documents.overview', compact(
            'vehicles',
            'carrier',
            'statistics',
            'documentTypes'
        ));
    }
    
    /**
     * Apply filters to the vehicle query based on request parameters.
     */
    private function applyFilters($query, Request $request)
    {
        // Vehicle status filter (Requirement 2.1)
        if ($request->filled('vehicle_status')) {
            switch ($request->vehicle_status) {
                case 'active':
                    $query->where('out_of_service', false)->where('suspended', false);
                    break;
                case 'out_of_service':
                    $query->where('out_of_service', true);
                    break;
                case 'suspended':
                    $query->where('suspended', true);
                    break;
            }
        }
        
        // Document type filter (Requirement 2.2)
        if ($request->filled('document_type')) {
            $query->whereHas('documents', function ($q) use ($request) {
                $q->where('document_type', $request->document_type);
            });
        }
        
        // Document status filter (Requirement 2.3)
        if ($request->filled('document_status')) {
            $query->whereHas('documents', function ($q) use ($request) {
                $q->where('status', $request->document_status);
            });
        }
    }
    
    /**
     * Calculate summary statistics for all documents across the fleet.
     * 
     * Requirement: 1.4
     */
    private function calculateSummaryStatistics($carrier)
    {
        $allDocuments = VehicleDocument::whereHas('vehicle', function ($query) use ($carrier) {
            $query->where('carrier_id', $carrier->id);
        })->get();
        
        return [
            'active' => $allDocuments->where('status', 'active')->count(),
            'expired' => $allDocuments->where('status', 'expired')->count(),
            'pending' => $allDocuments->where('status', 'pending')->count(),
            'total' => $allDocuments->count(),
        ];
    }
    
    /**
     * Get array of document types for filter dropdown.
     */
    private function getDocumentTypes()
    {
        return [
            'registration' => 'Registration',
            'insurance' => 'Insurance',
            'annual_inspection' => 'Annual Inspection',
            'irp_permit' => 'IRP Permit',
            'ifta' => 'IFTA',
            'title' => 'Title',
            'lease_agreement' => 'Lease Agreement',
            'maintenance_record' => 'Maintenance Record',
            'emissions_test' => 'Emissions Test',
            'other' => 'Other',
        ];
    }
}
