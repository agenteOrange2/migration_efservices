<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CarrierVehicleDocumentController extends Controller
{
    /**
     * Display a listing of the vehicle documents.
     */
    public function index(Vehicle $vehicle)
    {
        // Verify vehicle belongs to carrier
        $this->verifyVehicleAccess($vehicle);

        $vehicle->load(['documents' => function($query) {
            $query->orderBy('expiration_date', 'asc');
        }]);

        // Group documents by type
        $documentsByType = $vehicle->documents->groupBy('document_type');
        
        // Get document types for selector
        $documentTypes = $this->getDocumentTypes();
        
        return view('carrier.vehicles.documents.index', compact('vehicle', 'documentsByType', 'documentTypes'));
    }

    /**
     * Show the form for creating a new vehicle document.
     */
    public function create(Vehicle $vehicle)
    {
        // Verify vehicle belongs to carrier
        $this->verifyVehicleAccess($vehicle);

        $documentTypes = $this->getDocumentTypes();
        
        return view('carrier.vehicles.documents.create', compact('vehicle', 'documentTypes'));
    }

    /**
     * Store a newly created vehicle document in storage.
     */
    public function store(Request $request, Vehicle $vehicle)
    {
        // Verify vehicle belongs to carrier
        $this->verifyVehicleAccess($vehicle);

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'document_number' => 'nullable|string|max:255',
            'issued_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after_or_equal:issued_date',
            'notes' => 'nullable|string',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'document_type.required' => 'El tipo de documento es obligatorio',
            'expiration_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión',
            'document_file.required' => 'Debe subir un archivo',
            'document_file.mimes' => 'El archivo debe ser PDF, JPG, JPEG o PNG',
            'document_file.max' => 'El archivo no debe superar los 10MB',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Determine initial status based on expiration date
        $status = VehicleDocument::STATUS_ACTIVE;
        if ($request->filled('expiration_date')) {
            $expirationDate = \Carbon\Carbon::parse($request->expiration_date);
            if ($expirationDate->isPast()) {
                $status = VehicleDocument::STATUS_EXPIRED;
            }
        }

        // Create document
        $document = VehicleDocument::create([
            'vehicle_id' => $vehicle->id,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'issued_date' => $request->issued_date,
            'expiration_date' => $request->expiration_date,
            'status' => $status,
            'notes' => $request->notes,
        ]);

        // Handle file upload with Spatie Media Library
        if ($request->hasFile('document_file')) {
            $document->addMediaFromRequest('document_file')
                ->withCustomProperties(['vehicle_id' => $vehicle->id])
                ->toMediaCollection('document_files', 'public');
        }

        return redirect()->route('carrier.vehicles.documents.index', $vehicle->id)
            ->with('success', 'Documento agregado exitosamente');
    }

    /**
     * Display the specified vehicle document.
     */
    public function show($vehicleId, $documentId)
    {
        // Find document
        $document = VehicleDocument::findOrFail($documentId);
        
        // Get vehicle and verify access
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->verifyVehicleAccess($vehicle);
        
        // Get document types for edit modal
        $documentTypes = $this->getDocumentTypes();
                
        return view('carrier.vehicles.documents.show', compact('vehicle', 'document', 'documentTypes'));
    }

    /**
     * Show the form for editing the specified vehicle document.
     */
    public function edit($vehicleId, $documentId)
    {
        // Find document
        $document = VehicleDocument::findOrFail($documentId);
        
        // Get vehicle and verify access
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->verifyVehicleAccess($vehicle);
                
        $documentTypes = $this->getDocumentTypes();
        
        return view('carrier.vehicles.documents.edit', compact('vehicle', 'document', 'documentTypes'));
    }

    /**
     * Update the specified vehicle document in storage.
     */
    public function update(Request $request, $vehicleId, $documentId)
    {
        // Find document
        $document = VehicleDocument::findOrFail($documentId);
        
        // Get vehicle and verify access
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->verifyVehicleAccess($vehicle);
        
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'document_number' => 'nullable|string|max:255',
            'issued_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after_or_equal:issued_date',
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'document_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'document_type.required' => 'El tipo de documento es obligatorio',
            'expiration_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión',
            'document_file.mimes' => 'El archivo debe ser PDF, JPG, JPEG o PNG',
            'document_file.max' => 'El archivo no debe superar los 10MB',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update document
        $document->update([
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'issued_date' => $request->issued_date,
            'expiration_date' => $request->expiration_date,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        // Handle file upload
        if ($request->hasFile('document_file')) {
            // Delete old file
            $document->clearMediaCollection('document_files');
            
            // Upload new file
            $document->addMediaFromRequest('document_file')
                ->withCustomProperties(['vehicle_id' => $vehicle->id])
                ->toMediaCollection('document_files', 'public');
        }

        return redirect()->route('carrier.vehicles.documents.index', $vehicle->id)
            ->with('success', 'Documento actualizado exitosamente');
    }

    /**
     * Remove the specified vehicle document from storage.
     */
    public function destroy($vehicleId, $documentId)
    {
        // Find document
        $document = VehicleDocument::findOrFail($documentId);
        
        // Get vehicle and verify access
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->verifyVehicleAccess($vehicle);
        
        try {
            // Delete associated files
            $document->clearMediaCollection('document_files');
            
            // Delete record
            $document->delete();
            
            return redirect()->route('carrier.vehicles.documents.index', $vehicle->id)
                ->with('success', 'Documento eliminado exitosamente');
        } catch (\Exception $e) {

            
            return redirect()->route('carrier.vehicles.documents.index', $vehicle->id)
                ->with('error', 'Error al eliminar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Download the document file.
     */
    public function download($vehicleId, $documentId)
    {
        // Find document
        $document = VehicleDocument::findOrFail($documentId);
        
        // Get vehicle and verify access
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->verifyVehicleAccess($vehicle);
        
        
        $media = $document->getFirstMedia('document_files');
        
        if (!$media) {
            return redirect()->back()->with('error', 'El archivo no existe');
        }
        
        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Preview the document file.
     */
    public function preview($vehicleId, $documentId)
    {
        // Find document
        $document = VehicleDocument::findOrFail($documentId);
        
        // Get vehicle and verify access
        $vehicle = Vehicle::findOrFail($vehicleId);
        $this->verifyVehicleAccess($vehicle);
    

        $media = $document->getFirstMedia('document_files');
        
        if (!$media) {
            return redirect()->back()->with('error', 'El archivo no existe');
        }

        // Try to get file path
        $path = $media->getPath();
        
        // If file doesn't exist at primary path, try alternative locations
        if (!file_exists($path)) {

            
            // Try to find file in storage/app/public/others
            $alternativePath = storage_path('app/public/others/' . $document->id . '/' . basename($path));
            if (file_exists($alternativePath)) {
                $path = $alternativePath;
            } else {
                // If not found in any location, search by filename
                $fileName = basename($path);
                $potentialLocations = [
                    storage_path('app/public/vehicle/' . $vehicleId . '/' . $fileName),
                    storage_path('app/public/vehicle/' . $vehicleId . '/documents/' . $fileName),
                    storage_path('app/public/' . $fileName),
                    storage_path('app/public/others/' . $fileName)
                ];
                
                foreach ($potentialLocations as $potentialPath) {
                    if (file_exists($potentialPath)) {
                        $path = $potentialPath;
                        break;
                    }
                }
                
                // If still not found, search by glob
                if (!file_exists($path)) {
                    $globPattern = storage_path('app/public/*/') . $fileName;
                    $matches = glob($globPattern);
                    if (!empty($matches) && file_exists($matches[0])) {
                        $path = $matches[0];
                    }
                }
            }
            
            // If still not found
            if (!file_exists($path)) {
                return redirect()->back()->with('error', 'No se pudo encontrar el archivo en ninguna ubicación');
            }
        }
        
        // Determine MIME type
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

    /**
     * Verify that the vehicle belongs to the authenticated carrier.
     * 
     * @param Vehicle $vehicle
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function verifyVehicleAccess(Vehicle $vehicle): void
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        if ($vehicle->carrier_id !== $carrier->id) {

            
            abort(403, 'You do not have access to this vehicle.');
        }
    }

    /**
     * Get array of document types for dropdowns.
     */
    private function getDocumentTypes(): array
    {
        return [
            VehicleDocument::DOC_TYPE_REGISTRATION => 'Registration',
            VehicleDocument::DOC_TYPE_INSURANCE => 'Insurance',
            VehicleDocument::DOC_TYPE_ANNUAL_INSPECTION => 'Annual Inspection',
            VehicleDocument::DOC_TYPE_IRP_PERMIT => 'IRP Permit',
            VehicleDocument::DOC_TYPE_IFTA => 'IFTA',
            VehicleDocument::DOC_TYPE_TITLE => 'Title',
            VehicleDocument::DOC_TYPE_LEASE_AGREEMENT => 'Lease Agreement',
            VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD => 'Maintenance Record',
            VehicleDocument::DOC_TYPE_EMISSIONS_TEST => 'Emissions Test',
            VehicleDocument::DOC_TYPE_OTHER => 'Other',
        ];
    }
}
