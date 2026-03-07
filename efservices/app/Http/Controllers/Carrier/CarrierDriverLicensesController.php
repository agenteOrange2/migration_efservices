<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierDriverLicensesController extends Controller
{
    /**
     * Get the authenticated carrier's ID
     */
    private function getCarrierId()
    {
        return Auth::user()->carrierDetails->carrier->id;
    }

    /**
     * Verify that a license belongs to the authenticated carrier
     */
    private function authorizeLicense(DriverLicense $license)
    {
        $carrierId = $this->getCarrierId();
        
        if ((int) $license->driverDetail->carrier_id !== (int) $carrierId) {
            Log::warning('Carrier attempted to access unauthorized license', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'license_id' => $license->id,
                'license_carrier_id' => $license->driverDetail->carrier_id
            ]);
            
            abort(403, 'Unauthorized access to license');
        }
    }

    /**
     * Display a listing of licenses for carrier's drivers
     */
    public function index(Request $request)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            $query = DriverLicense::with(['driverDetail.user', 'driverDetail.carrier'])
                ->whereHas('driverDetail', function($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });
            
            // Apply filters
            if ($request->filled('search_term')) {
                $searchTerm = '%' . $request->search_term . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('license_number', 'like', $searchTerm)
                      ->orWhere('license_class', 'like', $searchTerm)
                      ->orWhere('state_of_issue', 'like', $searchTerm);
                });
            }
            
            if ($request->filled('driver_filter')) {
                $query->where('user_driver_detail_id', $request->driver_filter);
            }
            
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Sort results
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            if (in_array($sortField, ['created_at', 'license_number', 'expiration_date'])) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }
            
            $licenses = $query->paginate(15)->withQueryString();
            
            // Get drivers for filter dropdown (only carrier's drivers)
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrierId)
                ->get();
            
            // Get document counts for each license
            $licenseIds = $licenses->pluck('id')->toArray();
            $documentCounts = [];
            
            if (!empty($licenseIds)) {
                $counts = Media::where('model_type', DriverLicense::class)
                    ->whereIn('model_id', $licenseIds)
                    ->select('model_id', DB::raw('count(*) as count'))
                    ->groupBy('model_id')
                    ->pluck('count', 'model_id')
                    ->toArray();
                    
                $documentCounts = $counts;
            }
            
            Log::info('Carrier accessed licenses list', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'filters' => $request->only(['search_term', 'driver_filter', 'date_from', 'date_to'])
            ]);
            
            return view('carrier.drivers.licenses.index', compact('licenses', 'drivers', 'documentCounts'));
        } catch (\Exception $e) {
            Log::error('Error loading licenses', [
                'carrier_id' => $this->getCarrierId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error loading licenses: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new license
     */
    public function create()
    {
        $carrierId = $this->getCarrierId();
        
        // Get only carrier's drivers
        $drivers = UserDriverDetail::with('user')
            ->where('carrier_id', $carrierId)
            ->get();
        
        Log::info('Carrier accessed license creation form', [
            'carrier_id' => $carrierId,
            'user_id' => Auth::id()
        ]);
        
        return view('carrier.drivers.licenses.create', compact('drivers'));
    }

    /**
     * Store a newly created license in storage
     */
    public function store(Request $request)
    {
        $carrierId = $this->getCarrierId();
        
        $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'license_number' => 'required|string|max:255',
            'license_class' => 'required|string|max:255',
            'state_of_issue' => 'required|string|max:255',
            'expiration_date' => 'required|date|after:today',
            'restrictions' => 'nullable|string',
            'is_cdl' => 'nullable|boolean',
            'endorsement_n' => 'nullable|boolean',
            'endorsement_h' => 'nullable|boolean',
            'endorsement_x' => 'nullable|boolean',
            'endorsement_t' => 'nullable|boolean',
            'endorsement_p' => 'nullable|boolean',
            'endorsement_s' => 'nullable|boolean',
            'license_front_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'license_back_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'additional_documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        try {
            // Verify driver belongs to carrier
            $driver = UserDriverDetail::findOrFail($request->user_driver_detail_id);
            if ((int) $driver->carrier_id !== (int) $carrierId) {
                Log::warning('Carrier attempted to create license for unauthorized driver', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'driver_id' => $driver->id,
                    'driver_carrier_id' => $driver->carrier_id
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'You do not have access to this driver.');
            }
            
            DB::beginTransaction();
            
            $license = DriverLicense::create([
                'user_driver_detail_id' => $request->user_driver_detail_id,
                'license_number' => $request->license_number,
                'license_class' => $request->license_class,
                'state_of_issue' => $request->state_of_issue,
                'expiration_date' => $request->expiration_date,
                'restrictions' => $request->restrictions,
                'is_cdl' => $request->boolean('is_cdl')
            ]);
            
            // Handle endorsements through many-to-many relationship
            if ($request->boolean('is_cdl')) {
                $endorsementCodes = [];
                if ($request->boolean('endorsement_n')) $endorsementCodes[] = 'N';
                if ($request->boolean('endorsement_h')) $endorsementCodes[] = 'H';
                if ($request->boolean('endorsement_x')) $endorsementCodes[] = 'X';
                if ($request->boolean('endorsement_t')) $endorsementCodes[] = 'T';
                if ($request->boolean('endorsement_p')) $endorsementCodes[] = 'P';
                if ($request->boolean('endorsement_s')) $endorsementCodes[] = 'S';
                
                if (!empty($endorsementCodes)) {
                    $endorsementIds = \App\Models\Admin\Driver\LicenseEndorsement::whereIn('code', $endorsementCodes)->pluck('id');
                    $license->endorsements()->sync($endorsementIds);
                }
            }
            
            // Process license images
            if ($request->hasFile('license_front_image')) {
                $license->addMediaFromRequest('license_front_image')
                    ->usingName('License Front Image')
                    ->toMediaCollection('license_front');
            }
            
            if ($request->hasFile('license_back_image')) {
                $license->addMediaFromRequest('license_back_image')
                    ->usingName('License Back Image')
                    ->toMediaCollection('license_back');
            }
            
            // Process additional documents using Spatie Media Library
            if ($request->hasFile('additional_documents')) {
                foreach ($request->file('additional_documents') as $file) {
                    $license->addMedia($file)
                        ->usingName($file->getClientOriginalName())
                        ->toMediaCollection('licenses');
                }
            }
            
            DB::commit();
            
            Log::info('License created successfully by carrier', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'license_id' => $license->id,
                'driver_id' => $driver->id
            ]);
            
            return redirect()->route('carrier.licenses.index')
                ->with('success', 'License created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating license', [
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating license: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified license
     */
    public function show(DriverLicense $license)
    {
        $this->authorizeLicense($license);
        
        $license->load(['driverDetail.user', 'driverDetail.carrier', 'endorsements']);
        
        // Get associated documents using Spatie Media Library
        $documents = $license->getMedia('licenses');
        
        Log::info('Carrier viewed license details', [
            'carrier_id' => $this->getCarrierId(),
            'user_id' => Auth::id(),
            'license_id' => $license->id
        ]);
        
        return view('carrier.drivers.licenses.show', compact('license', 'documents'));
    }

    /**
     * Show the form for editing the specified license
     */
    public function edit(DriverLicense $license)
    {
        $this->authorizeLicense($license);
        
        $carrierId = $this->getCarrierId();
        $license->load(['driverDetail.user', 'driverDetail.carrier', 'endorsements']);
        
        // Get drivers from current carrier
        $drivers = UserDriverDetail::with('user')
            ->where('carrier_id', $carrierId)
            ->get();
        
        // Ensure current driver is included even if inactive
        $currentDriver = $license->driverDetail;
        if (!$drivers->contains('id', $currentDriver->id)) {
            $drivers->push($currentDriver);
        }
        
        // Get existing documents and convert to format expected by FileUploader
        $existingDocuments = $license->getMedia('licenses')->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'created_at' => $media->created_at->format('Y-m-d H:i:s'),
                'preview_url' => route('carrier.licenses.doc.preview', $media->id),
                'download_url' => route('carrier.licenses.doc.preview', [$media->id, 'download' => true]),
            ];
        })->toArray();
        
        Log::info('Carrier accessed license edit form', [
            'carrier_id' => $carrierId,
            'user_id' => Auth::id(),
            'license_id' => $license->id
        ]);
        
        return view('carrier.drivers.licenses.edit', compact('license', 'drivers', 'existingDocuments'));
    }

    /**
     * Update the specified license in storage
     */
    public function update(Request $request, DriverLicense $license)
    {
        $this->authorizeLicense($license);
        
        $carrierId = $this->getCarrierId();
        
        $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'license_number' => 'required|string|max:255',
            'license_class' => 'required|string|max:255',
            'state_of_issue' => 'required|string|max:255',
            'expiration_date' => 'required|date|after:today',
            'restrictions' => 'nullable|string',
            'is_cdl' => 'nullable|boolean',
            'endorsement_n' => 'nullable|boolean',
            'endorsement_h' => 'nullable|boolean',
            'endorsement_x' => 'nullable|boolean',
            'endorsement_t' => 'nullable|boolean',
            'endorsement_p' => 'nullable|boolean',
            'endorsement_s' => 'nullable|boolean',
            'license_front_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'license_back_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'additional_documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        try {
            // Verify driver belongs to carrier
            $driver = UserDriverDetail::findOrFail($request->user_driver_detail_id);
            if ((int) $driver->carrier_id !== (int) $carrierId) {
                Log::warning('Carrier attempted to update license with unauthorized driver', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'license_id' => $license->id,
                    'driver_id' => $driver->id,
                    'driver_carrier_id' => $driver->carrier_id
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'You do not have access to this driver.');
            }
            
            DB::beginTransaction();
            
            $license->update([
                'user_driver_detail_id' => $request->user_driver_detail_id,
                'license_number' => $request->license_number,
                'license_class' => $request->license_class,
                'state_of_issue' => $request->state_of_issue,
                'expiration_date' => $request->expiration_date,
                'restrictions' => $request->restrictions,
                'is_cdl' => $request->boolean('is_cdl')
            ]);
            
            // Handle endorsements through many-to-many relationship
            if ($request->boolean('is_cdl')) {
                $endorsementCodes = [];
                if ($request->boolean('endorsement_n')) $endorsementCodes[] = 'N';
                if ($request->boolean('endorsement_h')) $endorsementCodes[] = 'H';
                if ($request->boolean('endorsement_x')) $endorsementCodes[] = 'X';
                if ($request->boolean('endorsement_t')) $endorsementCodes[] = 'T';
                if ($request->boolean('endorsement_p')) $endorsementCodes[] = 'P';
                if ($request->boolean('endorsement_s')) $endorsementCodes[] = 'S';
                
                if (!empty($endorsementCodes)) {
                    $endorsementIds = \App\Models\Admin\Driver\LicenseEndorsement::whereIn('code', $endorsementCodes)->pluck('id');
                    $license->endorsements()->sync($endorsementIds);
                } else {
                    $license->endorsements()->detach();
                }
            } else {
                $license->endorsements()->detach();
            }
            
            // Process license images
            if ($request->hasFile('license_front_image')) {
                $license->clearMediaCollection('license_front');
                $license->addMediaFromRequest('license_front_image')
                    ->usingName('License Front Image')
                    ->toMediaCollection('license_front');
            }
            
            if ($request->hasFile('license_back_image')) {
                $license->clearMediaCollection('license_back');
                $license->addMediaFromRequest('license_back_image')
                    ->usingName('License Back Image')
                    ->toMediaCollection('license_back');
            }
            
            // Process additional documents using Spatie Media Library
            if ($request->hasFile('additional_documents')) {
                foreach ($request->file('additional_documents') as $file) {
                    $license->addMedia($file)
                        ->usingName($file->getClientOriginalName())
                        ->toMediaCollection('licenses');
                }
            }
            
            DB::commit();
            
            Log::info('License updated successfully by carrier', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'license_id' => $license->id
            ]);
            
            return redirect()->route('carrier.licenses.index')
                ->with('success', 'License updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating license', [
                'carrier_id' => $carrierId,
                'license_id' => $license->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating license: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified license from storage
     */
    public function destroy(DriverLicense $license)
    {
        $this->authorizeLicense($license);
        
        $carrierId = $this->getCarrierId();
        
        try {
            DB::beginTransaction();
            
            // Delete associated documents using Spatie Media Library
            $license->clearMediaCollection('license_front');
            $license->clearMediaCollection('license_back');
            $license->clearMediaCollection('licenses');
            
            // Delete the license
            $license->delete();
            
            DB::commit();
            
            Log::info('License deleted successfully by carrier', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'license_id' => $license->id
            ]);
            
            return redirect()->route('carrier.licenses.index')
                ->with('success', 'License deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting license', [
                'carrier_id' => $carrierId,
                'license_id' => $license->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('carrier.licenses.index')
                ->with('error', 'Error deleting license: ' . $e->getMessage());
        }
    }

    /**
     * Display documents for a specific license
     */
    public function showDocuments(DriverLicense $license, Request $request)
    {
        $this->authorizeLicense($license);
        
        $license->load('driverDetail.user');
        
        // Build base query for documents of this license
        $query = Media::where('model_type', DriverLicense::class)
            ->where('model_id', $license->id);
        
        // Apply collection filter (for clickable cards)
        if ($request->filled('collection') && $request->collection !== 'all') {
            if ($request->collection === 'additional') {
                $query->whereNotIn('collection_name', ['license_front', 'license_back']);
            } else {
                $query->where('collection_name', $request->collection);
            }
        }
        
        // Apply date filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Apply document type filter
        if ($request->filled('document_type')) {
            $query->where('collection_name', $request->document_type);
        }
        
        // Get paginated documents
        $documents = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        
        // Calculate document statistics for this license
        $baseConditions = ['model_type' => DriverLicense::class, 'model_id' => $license->id];
            
        $totalDocuments = Media::where($baseConditions)->count();
        $licenseFrontImages = Media::where($baseConditions)->where('collection_name', 'license_front')->count();
        $licenseBackImages = Media::where($baseConditions)->where('collection_name', 'license_back')->count();
        $additionalDocuments = Media::where($baseConditions)->whereNotIn('collection_name', ['license_front', 'license_back'])->count();
        
        // Determine current collection based on filter
        $currentCollection = $request->get('collection', 'all');
        
        // Document types available for filter
        $documentTypes = [
            'license_front' => 'License Front',
            'license_back' => 'License Back',
            'licenses' => 'Additional Documents'
        ];
        
        Log::info('Carrier viewed license documents', [
            'carrier_id' => $this->getCarrierId(),
            'user_id' => Auth::id(),
            'license_id' => $license->id
        ]);
        
        return view('carrier.drivers.licenses.documents', compact(
            'license', 
            'documents', 
            'totalDocuments',
            'licenseFrontImages',
            'licenseBackImages', 
            'additionalDocuments',
            'currentCollection',
            'documentTypes'
        ));
    }

    /**
     * Display all documents across all licenses for carrier's drivers
     */
    public function documents(Request $request)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Get license IDs for carrier's drivers
            $licenseIds = DriverLicense::whereHas('driverDetail', function($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            })->pluck('id')->toArray();
            
            // Use Spatie Media Library
            $query = Media::where('model_type', DriverLicense::class)
                ->whereIn('model_id', $licenseIds);
            
            // Apply filters
            if ($request->filled('search_term')) {
                $searchTerm = '%' . $request->search_term . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', $searchTerm)
                      ->orWhere('file_name', 'like', $searchTerm);
                });
            }
            
            if ($request->filled('driver_filter')) {
                $driverId = $request->driver_filter;
                // Get license IDs associated with this driver
                $driverLicenseIds = DriverLicense::where('user_driver_detail_id', $driverId)
                    ->pluck('id')
                    ->toArray();
                    
                $query->whereIn('model_id', $driverLicenseIds);
            }
            
            if ($request->filled('license_filter')) {
                $licenseId = $request->license_filter;
                $query->where('model_id', $licenseId);
            }
            
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Sort results
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            $documents = $query->paginate(15)->withQueryString();
            
            // Eager load license relationships to avoid N+1 queries
            $licenseIds = $documents->pluck('model_id')->unique()->toArray();
            $licensesData = DriverLicense::with(['driverDetail.user'])
                ->whereIn('id', $licenseIds)
                ->get()
                ->keyBy('id');
            
            // Data for filters (only carrier's drivers)
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrierId)
                ->get();
            
            $licenses = DriverLicense::whereHas('driverDetail', function($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            })->with('driverDetail.user')->orderBy('license_number')->get();
            
            Log::info('Carrier viewed all license documents', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id()
            ]);
            
            return view('carrier.drivers.licenses.all_documents', compact('documents', 'drivers', 'licenses', 'licensesData'));
        } catch (\Exception $e) {
            Log::error('Error loading license documents', [
                'carrier_id' => $this->getCarrierId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('carrier.licenses.index')
                ->with('error', 'Error loading documents: ' . $e->getMessage());
        }
    }

    /**
     * Upload a document to a license
     */
    public function uploadDocument(Request $request, DriverLicense $license)
    {
        $this->authorizeLicense($license);
        
        $carrierId = $this->getCarrierId();
        
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
        ]);

        try {
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                
                $license->addMedia($file)
                    ->usingName($file->getClientOriginalName())
                    ->toMediaCollection('licenses');
                
                Log::info('Document uploaded to license by carrier', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'license_id' => $license->id,
                    'file_name' => $file->getClientOriginalName()
                ]);
                
                return redirect()->back()
                    ->with('success', 'Document uploaded successfully');
            }
            
            return redirect()->back()
                ->with('error', 'No document file provided');
        } catch (\Exception $e) {
            Log::error('Error uploading document', [
                'carrier_id' => $carrierId,
                'license_id' => $license->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Error uploading document: ' . $e->getMessage());
        }
    }

    /**
     * Preview or download a document
     */
    public function previewDocument($id, Request $request = null)
    {
        try {
            // Find the document in Spatie media table
            $media = Media::findOrFail($id);

            // Verify document belongs to a license
            if ($media->model_type !== DriverLicense::class) {
                Log::warning('Carrier attempted to access non-license document', [
                    'carrier_id' => $this->getCarrierId(),
                    'user_id' => Auth::id(),
                    'document_id' => $id,
                    'model_type' => $media->model_type
                ]);
                return redirect()->back()->with('error', 'Invalid document type');
            }

            // Verify license belongs to carrier
            $license = DriverLicense::findOrFail($media->model_id);
            $this->authorizeLicense($license);

            Log::info('Carrier accessed document', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'document_id' => $id,
                'license_id' => $license->id
            ]);

            // Determine if download or view
            $isDownload = $request && $request->has('download');

            if ($isDownload) {
                return response()->download(
                    $media->getPath(), 
                    $media->file_name,
                    ['Content-Type' => $media->mime_type]
                );
            } else {
                $headers = [
                    'Content-Type' => $media->mime_type,
                    'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
                ];
                
                return response()->file($media->getPath(), $headers);
            }
        } catch (\Exception $e) {
            Log::error('Error previewing document', [
                'carrier_id' => $this->getCarrierId(),
                'document_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error accessing document: ' . $e->getMessage());
        }
    }

    /**
     * Delete a document (form submission)
     */
    public function destroyDocument($id)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Verify document exists
            $media = Media::findOrFail($id);

            // Verify document belongs to a license
            if ($media->model_type !== DriverLicense::class) {
                return redirect()->back()->with('error', 'Invalid document type');
            }

            // Verify license belongs to carrier
            $license = DriverLicense::findOrFail($media->model_id);
            $this->authorizeLicense($license);

            $fileName = $media->file_name;
            $licenseId = $media->model_id;

            // Delete physical file if exists
            $diskName = $media->disk;
            $filePath = $media->id . '/' . $media->file_name;
            
            if (Storage::disk($diskName)->exists($filePath)) {
                Storage::disk($diskName)->delete($filePath);
            }
            
            // Delete media directory if exists
            $dirPath = $media->id;
            if (Storage::disk($diskName)->exists($dirPath)) {
                Storage::disk($diskName)->deleteDirectory($dirPath);
            }
            
            // Delete record directly from database
            $result = DB::table('media')->where('id', $id)->delete();

            if (!$result) {
                return redirect()->back()->with('error', 'Failed to delete document');
            }

            Log::info('Document deleted by carrier', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'license_id' => $licenseId,
                'document_id' => $id,
                'file_name' => $fileName
            ]);

            // Determine return URL based on referer
            $referer = request()->headers->get('referer');
            
            if (strpos($referer, 'documents') !== false) {
                return redirect()->route('carrier.licenses.docs.show', $licenseId)
                    ->with('success', "Document '{$fileName}' deleted successfully");
            }
            
            return redirect()->route('carrier.licenses.edit', $licenseId)
                ->with('success', "Document '{$fileName}' deleted successfully");
                
        } catch (\Exception $e) {
            Log::error('Error deleting document', [
                'carrier_id' => $this->getCarrierId(),
                'document_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error deleting document: ' . $e->getMessage());
        }
    }

    /**
     * Delete a document via AJAX
     */
    public function ajaxDestroyDocument(Request $request, $id)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Verify document exists
            $media = Media::findOrFail($id);
            
            // Verify document belongs to a license
            if ($media->model_type !== DriverLicense::class) {
                Log::warning('Carrier attempted to delete non-license document via AJAX', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'document_id' => $id,
                    'model_type' => $media->model_type
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid document type'
                ], 400);
            }
            
            // Verify license belongs to carrier using authorization helper
            $license = DriverLicense::findOrFail($media->model_id);
            
            if ((int) $license->driverDetail->carrier_id !== (int) $carrierId) {
                Log::warning('Carrier attempted to delete unauthorized document via AJAX', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'document_id' => $id,
                    'license_id' => $license->id,
                    'license_carrier_id' => $license->driverDetail->carrier_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
            
            $fileName = $media->file_name;
            
            // Delete physical file if exists
            $diskName = $media->disk;
            $filePath = $media->id . '/' . $media->file_name;
            
            if (Storage::disk($diskName)->exists($filePath)) {
                Storage::disk($diskName)->delete($filePath);
            }
            
            // Delete media directory if exists
            $dirPath = $media->id;
            if (Storage::disk($diskName)->exists($dirPath)) {
                Storage::disk($diskName)->deleteDirectory($dirPath);
            }
            
            // Delete record directly from database
            $result = DB::table('media')->where('id', $id)->delete();
            
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete document'
                ], 500);
            }
            
            Log::info('Document deleted via AJAX by carrier', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'document_id' => $id,
                'license_id' => $license->id,
                'file_name' => $fileName
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Document '{$fileName}' deleted successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting document via AJAX', [
                'carrier_id' => $this->getCarrierId(),
                'document_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get drivers by carrier (AJAX endpoint for dynamic dropdowns)
     */
    public function getDriversByCarrier()
    {
        $carrierId = $this->getCarrierId();
        
        $drivers = UserDriverDetail::where('carrier_id', $carrierId)
            ->whereHas('user', function ($query) {
                $query->where('status', 1);
            })
            ->with('user')
            ->get();

        return response()->json($drivers);
    }
}
