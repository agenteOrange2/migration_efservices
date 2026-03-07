<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VehicleDocumentController extends Controller
{
    /**
     * Display a listing of the vehicle documents.
     */
    public function index(Vehicle $vehicle)
    {
        $vehicle->load(['documents' => function($query) {
            $query->orderBy('expiration_date', 'asc');
        }]);

        // Agrupar documentos por tipo
        $documentsByType = $vehicle->documents->groupBy('document_type');
        
        // Obtener los tipos de documentos para el selector
        $documentTypes = $this->getDocumentTypes();
        
        return view('admin.vehicles.documents.index', compact('vehicle', 'documentsByType', 'documentTypes'));
    }

    /**
     * Show the form for creating a new vehicle document.
     */
    public function create(Vehicle $vehicle)
    {
        $documentTypes = $this->getDocumentTypes();
        
        return view('admin.vehicles.documents.create', compact('vehicle', 'documentTypes'));
    }

    /**
     * Store a newly created vehicle document in storage.
     */
    public function store(Request $request, Vehicle $vehicle)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'document_number' => 'nullable|string|max:255',
            'issued_date' => 'nullable|string',
            'expiration_date' => 'nullable|string',
            'notes' => 'nullable|string',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'document_type.required' => 'El tipo de documento es obligatorio',
            'document_file.required' => 'Debe subir un archivo',
            'document_file.mimes' => 'El archivo debe ser PDF, JPG, JPEG o PNG',
            'document_file.max' => 'El archivo no debe superar los 10MB',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Parse dates from MM/DD/YYYY format
        $issuedDate = null;
        $expirationDate = null;
        
        if ($request->filled('issued_date')) {
            $issuedDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->issued_date)->format('Y-m-d');
        }
        
        if ($request->filled('expiration_date')) {
            $expirationDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->expiration_date)->format('Y-m-d');
        }

        // Determinar estado inicial basado en la fecha de vencimiento
        $status = VehicleDocument::STATUS_ACTIVE;
        if ($expirationDate) {
            $expDate = \Carbon\Carbon::parse($expirationDate);
            if ($expDate->isPast()) {
                $status = VehicleDocument::STATUS_EXPIRED;
            }
        }

        // Crear documento
        $document = VehicleDocument::create([
            'vehicle_id' => $vehicle->id,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'issued_date' => $issuedDate,
            'expiration_date' => $expirationDate,
            'status' => $status,
            'notes' => $request->notes,
        ]);

        // Manejar la carga de archivos con Spatie Media Library
        if ($request->hasFile('document_file')) {
            $document->addMediaFromRequest('document_file')
                ->withCustomProperties(['vehicle_id' => $vehicle->id])
                ->toMediaCollection('document_files', 'public');
        }

        return redirect()->route('admin.vehicles.documents.index', $vehicle->id)
            ->with('success', 'Documento agregado exitosamente');
    }

    /**
     * Display the specified vehicle document.
     */
    public function show($vehicleId, $documentId)
    {
        // Buscamos directamente el documento por ID sin verificación adicional
        $document = VehicleDocument::findOrFail($documentId);
        
        // Registrar información para debug
        \Illuminate\Support\Facades\Log::info('Mostrando documento', [
            'document_id' => $document->id,
            'document_vehicle_id' => $document->vehicle_id,
            'requested_vehicle_id' => $vehicleId
        ]);
        
        // Obtener el vehículo para la vista
        $vehicle = Vehicle::findOrFail($vehicleId);
        
        return view('admin.vehicles.documents.show', compact('vehicle', 'document'));
    }

    /**
     * Show the form for editing the specified vehicle document.
     */
    public function edit($vehicleId, $documentId)
    {
        // Buscamos directamente el documento por ID sin verificación adicional
        $document = VehicleDocument::findOrFail($documentId);
        
        // Registrar información para debug
        \Illuminate\Support\Facades\Log::info('Editando documento', [
            'document_id' => $document->id,
            'document_vehicle_id' => $document->vehicle_id,
            'requested_vehicle_id' => $vehicleId
        ]);
        
        // Obtener el vehículo para la vista
        $vehicle = Vehicle::findOrFail($vehicleId);
        
        $documentTypes = $this->getDocumentTypes();
        
        return view('admin.vehicles.documents.edit', compact('vehicle', 'document', 'documentTypes'));
    }

    /**
     * Update the specified vehicle document in storage.
     */
    public function update(Request $request, $vehicleId, $documentId)
    {
        // Buscamos directamente el documento por ID sin verificación adicional
        $document = VehicleDocument::findOrFail($documentId);
        
        // Registrar información para debug
        \Illuminate\Support\Facades\Log::info('Actualizando documento', [
            'document_id' => $document->id,
            'document_vehicle_id' => $document->vehicle_id,
            'requested_vehicle_id' => $vehicleId
        ]);
        
        // Obtener el vehículo para la redirección
        $vehicle = Vehicle::findOrFail($vehicleId);

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'document_number' => 'nullable|string|max:255',
            'issued_date' => 'nullable|string',
            'expiration_date' => 'nullable|string',
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'document_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'document_type.required' => 'El tipo de documento es obligatorio',
            'document_file.mimes' => 'El archivo debe ser PDF, JPG, JPEG o PNG',
            'document_file.max' => 'El archivo no debe superar los 10MB',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Parse dates from MM/DD/YYYY format
        $issuedDate = null;
        $expirationDate = null;
        
        if ($request->filled('issued_date')) {
            $issuedDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->issued_date)->format('Y-m-d');
        }
        
        if ($request->filled('expiration_date')) {
            $expirationDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->expiration_date)->format('Y-m-d');
        }

        // Actualizar documento
        $document->update([
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'issued_date' => $issuedDate,
            'expiration_date' => $expirationDate,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        // Manejar la carga de archivos
        if ($request->hasFile('document_file')) {
            // Eliminar archivo anterior
            $document->clearMediaCollection('document_files');
            
            // Subir nuevo archivo
            $document->addMediaFromRequest('document_file')
                ->withCustomProperties(['vehicle_id' => $vehicle->id])
                ->toMediaCollection('document_files', 'public');
        }

        return redirect()->route('admin.vehicles.documents.index', $vehicle->id)
            ->with('success', 'Documento actualizado exitosamente');
    }

    /**
     * Remove the specified vehicle document from storage.
     */
    public function destroy($vehicleId, $documentId)
    {
        // Buscamos directamente el documento por ID sin verificación adicional
        $document = VehicleDocument::findOrFail($documentId);
        
        // Registrar información para debug
        \Illuminate\Support\Facades\Log::info('Eliminando documento', [
            'document_id' => $document->id,
            'document_vehicle_id' => $document->vehicle_id,
            'requested_vehicle_id' => $vehicleId
        ]);
        
        // Obtener el vehículo para la redirección
        $vehicle = Vehicle::findOrFail($vehicleId);

        try {
            // Eliminar archivos asociados
            $document->clearMediaCollection('document_files');
            
            // Eliminar registro
            $document->delete();
            
            return redirect()->route('admin.vehicles.documents.index', $vehicle->id)
                ->with('success', 'Documento eliminado exitosamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.vehicles.documents.index', $vehicle->id)
                ->with('error', 'Error al eliminar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Download the document file.
     */
    public function download($vehicleId, $documentId)
    {
        // Buscamos directamente el documento por ID sin verificación adicional
        $document = VehicleDocument::findOrFail($documentId);
        
        // Registrar información para debug
        \Illuminate\Support\Facades\Log::info('Descargando documento', [
            'document_id' => $document->id,
            'document_vehicle_id' => $document->vehicle_id,
            'requested_vehicle_id' => $vehicleId
        ]);
        
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
        // Buscamos directamente el documento por ID sin verificación adicional
        $document = VehicleDocument::findOrFail($documentId);
        
        // Registrar información para debug
        \Illuminate\Support\Facades\Log::info('Previsualizando documento', [
            'document_id' => $document->id,
            'document_vehicle_id' => $document->vehicle_id,
            'requested_vehicle_id' => $vehicleId
        ]);

        $media = $document->getFirstMedia('document_files');
        
        if (!$media) {
            return redirect()->back()->with('error', 'El archivo no existe');
        }

        // Intentar obtener la ruta del archivo
        $path = $media->getPath();
        
        // Si el archivo no existe en la ruta principal, intentar buscar en rutas alternativas
        if (!file_exists($path)) {
            // Log para depuración
            \Illuminate\Support\Facades\Log::warning('File not found at primary path', [
                'path' => $path,
                'vehicle_id' => $vehicleId,
                'document_id' => $document->id
            ]);
            
            // Intentar encontrar el archivo en storage/app/public/others
            $alternativePath = storage_path('app/public/others/' . $document->id . '/' . basename($path));
            if (file_exists($alternativePath)) {
                $path = $alternativePath;
            } else {
                // Si no se encuentra en ninguna ubicación, buscar el archivo por nombre
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
                
                // Si aún no se encuentra, buscar por glob
                if (!file_exists($path)) {
                    $globPattern = storage_path('app/public/*/') . $fileName;
                    $matches = glob($globPattern);
                    if (!empty($matches) && file_exists($matches[0])) {
                        $path = $matches[0];
                    }
                }
            }
            
            // Si aún así no se encuentra
            if (!file_exists($path)) {
                return redirect()->back()->with('error', 'No se pudo encontrar el archivo en ninguna ubicación');
            }
        }
        
        // Determinar el tipo MIME del archivo
        $mimeType = $media->mime_type;
        
        // Si es un PDF, mostrarlo en el navegador
        if ($mimeType === 'application/pdf' || strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
            ]);
        }
        
        // Si es una imagen, mostrar vista previa
        if (strpos($mimeType, 'image/') === 0 || in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
            return response()->file($path, [
                'Content-Type' => $mimeType ?: 'image/' . strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"'
            ]);
        }
        
        // Para otros tipos, descargar
        return response()->download($path, basename($path));
    }

    /**
     * Download all vehicle documents as a ZIP file.
     *
     * @param int $vehicleId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function downloadAll($vehicleId)
    {
        $vehicle = Vehicle::with('documents')->findOrFail($vehicleId);
        
        // Filter documents that have media files
        $documentsWithFiles = $vehicle->documents->filter(function ($document) {
            return $document->getFirstMedia('document_files') !== null;
        });
        
        // Check if there are any documents with files
        if ($documentsWithFiles->isEmpty()) {
            return redirect()->back()->with('warning', 'No hay documentos con archivos para descargar');
        }
        
        // Generate ZIP filename
        $make = preg_replace('/[^a-zA-Z0-9]/', '_', $vehicle->make);
        $model = preg_replace('/[^a-zA-Z0-9]/', '_', $vehicle->model);
        $year = $vehicle->year;
        $date = now()->format('Ymd');
        $zipFileName = "{$make}_{$model}_{$year}_documents_{$date}.zip";
        
        // Create temporary ZIP file
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }
        $zipPath = $tempPath . '/' . $zipFileName;
        
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'No se pudo crear el archivo ZIP');
        }
        
        $filesAdded = 0;
        
        foreach ($documentsWithFiles as $document) {
            $media = $document->getFirstMedia('document_files');
            if (!$media) {
                continue;
            }
            
            $filePath = $media->getPath();
            
            // Skip if file doesn't exist
            if (!file_exists($filePath)) {
                \Illuminate\Support\Facades\Log::warning('File not found for ZIP', [
                    'document_id' => $document->id,
                    'path' => $filePath
                ]);
                continue;
            }
            
            // Create folder name from document type (replace spaces with underscores)
            $folderName = str_replace(' ', '_', $document->documentTypeName);
            
            // Create unique filename to avoid conflicts
            $fileName = $media->file_name;
            $zipEntryName = $folderName . '/' . $fileName;
            
            // Add file to ZIP
            if ($zip->addFile($filePath, $zipEntryName)) {
                $filesAdded++;
            }
        }
        
        $zip->close();
        
        // Check if any files were added
        if ($filesAdded === 0) {
            @unlink($zipPath);
            return redirect()->back()->with('warning', 'No se encontraron archivos válidos para descargar');
        }
        
        // Return download response and delete temp file after sending
        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
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