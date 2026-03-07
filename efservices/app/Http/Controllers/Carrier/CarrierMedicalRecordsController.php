<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierMedicalRecordsController extends Controller
{
    /**
     * Get the authenticated carrier's ID
     */
    private function getCarrierId()
    {
        return Auth::user()->carrierDetails->carrier->id;
    }

    /**
     * Verify that a medical record belongs to the authenticated carrier
     */
    private function authorizeMedicalRecord(DriverMedicalQualification $medicalRecord)
    {
        $carrierId = $this->getCarrierId();
        
        if ((int) $medicalRecord->userDriverDetail->carrier_id !== (int) $carrierId) {
            Log::warning('Carrier attempted to access unauthorized medical record', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'record_carrier_id' => $medicalRecord->userDriverDetail->carrier_id
            ]);
            
            abort(403, 'Unauthorized access to medical record');
        }
    }

    /**
     * Display a listing of medical records for carrier's drivers
     */
    public function index(Request $request)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Log access
            Log::info('Carrier accessed medical records', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'filters' => $request->only(['search_term', 'driver_filter', 'date_from', 'date_to', 'tab'])
            ]);
            
            // Build base query with carrier filtering and eager loading
            $query = DriverMedicalQualification::with(['userDriverDetail.user', 'userDriverDetail.carrier'])
                ->whereHas('userDriverDetail', function($q) use ($carrierId) {
                    $q->where('carrier_id', $carrierId);
                });
            
            // Handle tab-based filtering
            $currentTab = $request->get('tab', 'all');
            
            if ($currentTab !== 'all') {
                switch ($currentTab) {
                    case 'active':
                        // Active: expiration > 30 days from now
                        $query->where('medical_card_expiration_date', '>', now()->addDays(30));
                        break;
                    case 'expiring':
                        // Expiring: expiration between now and 30 days
                        $query->where('medical_card_expiration_date', '<=', now()->addDays(30))
                              ->where('medical_card_expiration_date', '>=', now());
                        break;
                    case 'expired':
                        // Expired: expiration < now
                        $query->where('medical_card_expiration_date', '<', now());
                        break;
                }
            }
            
            // Apply search filter
            if ($request->filled('search_term')) {
                $searchTerm = '%' . $request->search_term . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('medical_examiner_name', 'like', $searchTerm)
                      ->orWhere('medical_examiner_registry_number', 'like', $searchTerm)
                      ->orWhereHas('userDriverDetail.user', function($subQ) use ($searchTerm) {
                          $subQ->where('name', 'like', $searchTerm)
                               ->orWhere('email', 'like', $searchTerm);
                      });
                });
            }
            
            // Apply driver filter (only carrier's drivers)
            if ($request->filled('driver_filter')) {
                $query->where('user_driver_detail_id', $request->driver_filter);
            }
            
            // Apply date range filters
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Handle sorting
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            
            if (in_array($sortField, ['created_at', 'medical_card_expiration_date', 'medical_examiner_name'])) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }
            
            // Paginate results (15 per page)
            $medicalRecords = $query->paginate(15);
            
            // Add document counts to each record
            foreach ($medicalRecords as $record) {
                $record->documents_count = $record->getMedia()->count();
            }
            
            // Get drivers for filter dropdown (only carrier's drivers)
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrierId)
                ->get();
            
            // Calculate statistics for carrier's medical records
            $baseStatsQuery = DriverMedicalQualification::whereHas('userDriverDetail', function($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            });
            
            $totalCount = (clone $baseStatsQuery)->count();
            $activeCount = (clone $baseStatsQuery)->where('medical_card_expiration_date', '>', now()->addDays(30))->count();
            $expiringCount = (clone $baseStatsQuery)
                ->where('medical_card_expiration_date', '<=', now()->addDays(30))
                ->where('medical_card_expiration_date', '>=', now())
                ->count();
            $expiredCount = (clone $baseStatsQuery)->where('medical_card_expiration_date', '<', now())->count();
            
            return view('carrier.drivers.medical-records.index', compact(
                'medicalRecords',
                'drivers',
                'totalCount',
                'activeCount',
                'expiringCount',
                'expiredCount',
                'currentTab'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading carrier medical records', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Error loading medical records: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new medical record
     */
    public function create()
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Log access
            Log::info('Carrier accessed create medical record form', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id()
            ]);
            
            // Load only carrier's drivers for dropdown
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrierId)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->get();
            
            return view('carrier.drivers.medical-records.create', compact('drivers'));
        } catch (\Exception $e) {
            Log::error('Error loading create medical record form', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('carrier.medical-records.index')
                ->with('error', 'Error loading form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created medical record in storage
     */
    public function store(Request $request)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Validate all input fields according to requirements
            $validated = $request->validate([
                'user_driver_detail_id' => 'required|exists:user_driver_details,id',
                'social_security_number' => 'required|string|max:255',
                'hire_date' => 'nullable|date',
                'location' => 'nullable|string|max:255',
                'is_suspended' => 'nullable|boolean',
                'suspension_date' => 'nullable|date',
                'is_terminated' => 'nullable|boolean',
                'termination_date' => 'nullable|date',
                'medical_examiner_name' => 'required|string|max:255',
                'medical_examiner_registry_number' => 'nullable|string|max:255',
                'medical_card_expiration_date' => 'nullable|date',
                'medical_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'social_security_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
            ]);
            
            // Verify selected driver belongs to authenticated carrier
            $driverDetail = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
            
            if ((int) $driverDetail->carrier_id !== (int) $carrierId) {
                Log::warning('Carrier attempted to create medical record for unauthorized driver', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'driver_detail_id' => $validated['user_driver_detail_id'],
                    'driver_carrier_id' => $driverDetail->carrier_id
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'You are not authorized to create a medical record for this driver.');
            }
            
            // Check if driver already has a medical record
            $existingRecord = DriverMedicalQualification::where('user_driver_detail_id', $validated['user_driver_detail_id'])->first();
            
            if ($existingRecord) {
                return redirect()->route('carrier.medical-records.edit', $existingRecord->id)
                    ->with('error', 'This driver already has a Medical Record. You have been redirected to edit the existing record.');
            }

            DB::beginTransaction();
            
            // Create DriverMedicalQualification record
            $medicalRecord = DriverMedicalQualification::create([
                'user_driver_detail_id' => $validated['user_driver_detail_id'],
                'social_security_number' => $validated['social_security_number'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'location' => $validated['location'] ?? null,
                'is_suspended' => $validated['is_suspended'] ?? false,
                'suspension_date' => $validated['suspension_date'] ?? null,
                'is_terminated' => $validated['is_terminated'] ?? false,
                'termination_date' => $validated['termination_date'] ?? null,
                'medical_examiner_name' => $validated['medical_examiner_name'] ?? null,
                'medical_examiner_registry_number' => $validated['medical_examiner_registry_number'] ?? null,
                'medical_card_expiration_date' => $validated['medical_card_expiration_date'] ?? null,
            ]);
            
            // Handle medical_card file upload using Spatie Media Library
            if ($request->hasFile('medical_card')) {
                $medicalRecord->addMediaFromRequest('medical_card')
                    ->toMediaCollection('medical_card');
            }

            // Handle social_security_card file upload
            if ($request->hasFile('social_security_card')) {
                $medicalRecord->addMediaFromRequest('social_security_card')
                    ->toMediaCollection('social_security_card');
            }
            
            DB::commit();
            
            Log::info('Carrier created medical record', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'driver_detail_id' => $validated['user_driver_detail_id']
            ]);
            
            return redirect()->route('carrier.medical-records.index')
                ->with('success', 'Medical record created successfully.');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating medical record', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating medical record: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified medical record
     */
    public function show(DriverMedicalQualification $medicalRecord)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Call authorizeMedicalRecord() to verify ownership
            $this->authorizeMedicalRecord($medicalRecord);
            
            // Eager load driverDetail.user and media relationships
            $medicalRecord->load([
                'userDriverDetail.user',
                'userDriverDetail.carrier',
                'media'
            ]);
            
            // Calculate document counts for each collection type
            $medicalCertificateCount = $medicalRecord->getMedia('medical_certificate')->count();
            $testResultsCount = $medicalRecord->getMedia('test_results')->count();
            $medicalDocumentsCount = $medicalRecord->getMedia('medical_documents')->count();
            $medicalCardCount = $medicalRecord->getMedia('medical_card')->count();
            $socialSecurityCardCount = $medicalRecord->getMedia('social_security_card')->count();
            
            // Calculate total documents count
            $totalDocumentsCount = $medicalCertificateCount + $testResultsCount + $medicalDocumentsCount + $medicalCardCount + $socialSecurityCardCount;
            
            // Get last 5 documents for preview
            $recentDocuments = $medicalRecord->getMedia()->sortByDesc('created_at')->take(5);
            
            // Log successful access
            Log::info('Carrier accessed medical record details', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id
            ]);
            
            // Return show view with all data
            return view('carrier.drivers.medical-records.show', compact(
                'medicalRecord',
                'medicalCertificateCount',
                'testResultsCount',
                'medicalDocumentsCount',
                'medicalCardCount',
                'totalDocumentsCount',
                'recentDocuments'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error loading medical record details', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('carrier.medical-records.index')
                ->with('error', 'Error loading medical record: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified medical record
     */
    public function edit(DriverMedicalQualification $medicalRecord)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Call authorizeMedicalRecord() to verify ownership
            $this->authorizeMedicalRecord($medicalRecord);
            
            // Log access
            Log::info('Carrier accessed edit medical record form', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id
            ]);
            
            // Load medical record with relationships
            $medicalRecord->load(['userDriverDetail.user', 'userDriverDetail.carrier']);
            
            // Load only carrier's drivers for dropdown
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrierId)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->get();
            
            // Ensure current driver is included even if inactive
            $currentDriverId = $medicalRecord->user_driver_detail_id;
            $currentDriverExists = $drivers->contains('id', $currentDriverId);
            
            if (!$currentDriverExists) {
                $currentDriver = UserDriverDetail::with('user')->find($currentDriverId);
                if ($currentDriver && $currentDriver->carrier_id == $carrierId) {
                    $drivers->prepend($currentDriver);
                }
            }
            
            return view('carrier.drivers.medical-records.edit', compact('medicalRecord', 'drivers'));
            
        } catch (\Exception $e) {
            Log::error('Error loading edit medical record form', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('carrier.medical-records.index')
                ->with('error', 'Error loading form: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified medical record in storage
     */
    public function update(Request $request, DriverMedicalQualification $medicalRecord)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Call authorizeMedicalRecord() to verify ownership
            $this->authorizeMedicalRecord($medicalRecord);
            
            // Validate all input fields
            $validated = $request->validate([
                'user_driver_detail_id' => 'required|exists:user_driver_details,id',
                'social_security_number' => 'nullable|string|max:255',
                'hire_date' => 'nullable|date',
                'location' => 'nullable|string|max:255',
                'is_suspended' => 'nullable|boolean',
                'suspension_date' => 'nullable|date',
                'is_terminated' => 'nullable|boolean',
                'termination_date' => 'nullable|date',
                'medical_examiner_name' => 'nullable|string|max:255',
                'medical_examiner_registry_number' => 'nullable|string|max:255',
                'medical_card_expiration_date' => 'nullable|date',
                'medical_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'social_security_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
            ]);
            
            // Verify driver belongs to carrier
            $driverDetail = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
            
            if ((int) $driverDetail->carrier_id !== (int) $carrierId) {
                Log::warning('Carrier attempted to update medical record with unauthorized driver', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'medical_record_id' => $medicalRecord->id,
                    'driver_detail_id' => $validated['user_driver_detail_id'],
                    'driver_carrier_id' => $driverDetail->carrier_id
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'You are not authorized to assign this driver to the medical record.');
            }
            
            DB::beginTransaction();
            
            // Update DriverMedicalQualification record
            $medicalRecord->update([
                'user_driver_detail_id' => $validated['user_driver_detail_id'],
                'social_security_number' => $validated['social_security_number'] ?? null,
                'hire_date' => $validated['hire_date'] ?? null,
                'location' => $validated['location'] ?? null,
                'is_suspended' => $validated['is_suspended'] ?? false,
                'suspension_date' => $validated['suspension_date'] ?? null,
                'is_terminated' => $validated['is_terminated'] ?? false,
                'termination_date' => $validated['termination_date'] ?? null,
                'medical_examiner_name' => $validated['medical_examiner_name'] ?? null,
                'medical_examiner_registry_number' => $validated['medical_examiner_registry_number'] ?? null,
                'medical_card_expiration_date' => $validated['medical_card_expiration_date'] ?? null,
            ]);
            
            // Handle medical card replacement if new file uploaded
            if ($request->hasFile('medical_card')) {
                // Clear existing medical card
                $medicalRecord->clearMediaCollection('medical_card');
                
                // Add new medical card
                $medicalRecord->addMediaFromRequest('medical_card')
                    ->toMediaCollection('medical_card');
            }

            // Handle social security card replacement if new file uploaded
            if ($request->hasFile('social_security_card')) {
                $medicalRecord->clearMediaCollection('social_security_card');
                $medicalRecord->addMediaFromRequest('social_security_card')
                    ->toMediaCollection('social_security_card');
            }
            
            DB::commit();
            
            Log::info('Carrier updated medical record', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'driver_detail_id' => $validated['user_driver_detail_id']
            ]);
            
            return redirect()->route('carrier.medical-records.index')
                ->with('success', 'Medical record updated successfully.');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating medical record', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating medical record: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified medical record from storage
     */
    public function destroy(DriverMedicalQualification $medicalRecord)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Call authorizeMedicalRecord() to verify ownership
            $this->authorizeMedicalRecord($medicalRecord);
            
            // Use DB transaction for data integrity
            DB::beginTransaction();
            
            // Clear all media collections (medical_certificate, test_results, medical_documents, medical_card)
            $medicalRecord->clearMediaCollection('medical_certificate');
            $medicalRecord->clearMediaCollection('test_results');
            $medicalRecord->clearMediaCollection('medical_documents');
            $medicalRecord->clearMediaCollection('medical_card');
            
            // Store ID for logging before deletion
            $medicalRecordId = $medicalRecord->id;
            $driverDetailId = $medicalRecord->user_driver_detail_id;
            
            // Delete the medical record from database
            $medicalRecord->delete();
            
            // Commit transaction
            DB::commit();
            
            Log::info('Carrier deleted medical record', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecordId,
                'driver_detail_id' => $driverDetailId
            ]);
            
            // Redirect with success message
            return redirect()->route('carrier.medical-records.index')
                ->with('success', 'Medical record deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting medical record', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error deleting medical record: ' . $e->getMessage());
        }
    }

    /**
     * Display documents for a specific medical record
     */
    public function showDocuments(Request $request, DriverMedicalQualification $medicalRecord)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Call authorizeMedicalRecord() to verify ownership
            $this->authorizeMedicalRecord($medicalRecord);
            
            // Log access
            Log::info('Carrier accessed medical record documents', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'filters' => $request->only(['collection', 'date_from', 'date_to'])
            ]);
            
            // Load medical record with relationships
            $medicalRecord->load(['userDriverDetail.user', 'userDriverDetail.carrier']);
            
            // Get current collection filter from request
            $currentCollection = $request->get('collection', 'all');
            
            // Query media table filtered by collection if specified
            $documentsQuery = $medicalRecord->media();
            
            if ($currentCollection !== 'all') {
                $documentsQuery->where('collection_name', $currentCollection);
            }
            
            // Apply date range filters if provided
            if ($request->filled('date_from')) {
                $documentsQuery->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $documentsQuery->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Order by most recent first
            $documentsQuery->orderBy('created_at', 'desc');
            
            // Paginate documents (15 per page)
            $documents = $documentsQuery->paginate(15);
            
            // Calculate counts for each collection type
            $medicalCertificateCount = $medicalRecord->getMedia('medical_certificate')->count();
            $testResultsCount = $medicalRecord->getMedia('test_results')->count();
            $medicalDocumentsCount = $medicalRecord->getMedia('medical_documents')->count();
            $medicalCardCount = $medicalRecord->getMedia('medical_card')->count();
            $socialSecurityCardCount = $medicalRecord->getMedia('social_security_card')->count();
            $totalDocumentsCount = $medicalCertificateCount + $testResultsCount + $medicalDocumentsCount + $medicalCardCount + $socialSecurityCardCount;
            
            // Return documents view with all data
            return view('carrier.drivers.medical-records.documents', compact(
                'medicalRecord',
                'documents',
                'currentCollection',
                'medicalCertificateCount',
                'testResultsCount',
                'medicalDocumentsCount',
                'medicalCardCount',
                'socialSecurityCardCount',
                'totalDocumentsCount'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error loading medical record documents', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('carrier.medical-records.show', $medicalRecord)
                ->with('error', 'Error loading documents: ' . $e->getMessage());
        }
    }

    /**
     * Upload documents to a medical record
     */
    public function uploadDocument(Request $request, DriverMedicalQualification $medicalRecord)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Call authorizeMedicalRecord() to verify ownership
            $this->authorizeMedicalRecord($medicalRecord);
            
            // Validate documents array (PDF, JPG, JPEG, PNG, DOC, DOCX, max 10MB each)
            $request->validate([
                'documents' => 'required|array',
                'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB
            ]);
            
            // Track upload count and errors
            $uploadCount = 0;
            $errors = [];
            
            // Loop through uploaded files
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $index => $file) {
                    try {
                        // Add each file to medical_documents collection using Spatie Media Library
                        $medicalRecord->addMedia($file)
                            ->toMediaCollection('medical_documents');
                        
                        $uploadCount++;
                        
                        Log::info('Carrier uploaded document to medical record', [
                            'carrier_id' => $carrierId,
                            'user_id' => Auth::id(),
                            'medical_record_id' => $medicalRecord->id,
                            'file_name' => $file->getClientOriginalName(),
                            'file_size' => $file->getSize()
                        ]);
                        
                    } catch (\Exception $e) {
                        $errors[] = "Failed to upload file '{$file->getClientOriginalName()}': {$e->getMessage()}";
                        
                        Log::error('Error uploading document to medical record', [
                            'carrier_id' => $carrierId,
                            'user_id' => Auth::id(),
                            'medical_record_id' => $medicalRecord->id,
                            'file_name' => $file->getClientOriginalName(),
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            }
            
            // Return with success/error messages
            if ($uploadCount > 0 && empty($errors)) {
                return redirect()->back()
                    ->with('success', "{$uploadCount} document(s) uploaded successfully.");
            } elseif ($uploadCount > 0 && !empty($errors)) {
                return redirect()->back()
                    ->with('warning', "{$uploadCount} document(s) uploaded successfully, but some failed: " . implode(', ', $errors));
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to upload documents: ' . implode(', ', $errors));
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->with('error', 'Validation failed. Please check your files and try again.');
        } catch (\Exception $e) {
            Log::error('Error in uploadDocument method', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'medical_record_id' => $medicalRecord->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error uploading documents: ' . $e->getMessage());
        }
    }

    /**
     * Preview or download a document
     */
    public function previewDocument($id, Request $request = null)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Find media record by ID
            $media = Media::findOrFail($id);
            
            // Verify media belongs to DriverMedicalQualification model
            if ($media->model_type !== DriverMedicalQualification::class) {
                Log::warning('Carrier attempted to access non-medical-record document', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'model_type' => $media->model_type
                ]);
                
                abort(403, 'Unauthorized access to document');
            }
            
            // Get the medical record
            $medicalRecord = DriverMedicalQualification::find($media->model_id);
            
            if (!$medicalRecord) {
                Log::warning('Carrier attempted to access document for non-existent medical record', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'model_id' => $media->model_id
                ]);
                
                abort(404, 'Medical record not found');
            }
            
            // Verify medical record belongs to carrier's driver
            if ((int) $medicalRecord->userDriverDetail->carrier_id !== (int) $carrierId) {
                Log::warning('Carrier attempted to access document from unauthorized medical record', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'medical_record_id' => $medicalRecord->id,
                    'record_carrier_id' => $medicalRecord->userDriverDetail->carrier_id
                ]);
                
                abort(403, 'Unauthorized access to document');
            }
            
            // Get file path and check if it exists
            $filePath = $media->getPath();
            
            if (!file_exists($filePath)) {
                Log::error('Document file not found on disk', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'file_path' => $filePath
                ]);
                
                abort(404, 'Document file not found');
            }
            
            // Check if download parameter is present
            $isDownload = $request && $request->has('download');
            
            // Get file information
            $fileName = $media->file_name;
            $mimeType = $media->mime_type;
            
            // Log access
            Log::info('Carrier accessed document', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'media_id' => $id,
                'medical_record_id' => $medicalRecord->id,
                'file_name' => $fileName,
                'action' => $isDownload ? 'download' : 'preview'
            ]);
            
            // If download, return file with download headers
            if ($isDownload) {
                return response()->download($filePath, $fileName, [
                    'Content-Type' => $mimeType,
                ]);
            }
            
            // If preview, return file with inline headers
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Carrier attempted to access non-existent document', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'media_id' => $id
            ]);
            
            abort(404, 'Document not found');
            
        } catch (\Exception $e) {
            Log::error('Error accessing document', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'media_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(500, 'Error accessing document: ' . $e->getMessage());
        }
    }

    /**
     * Delete a document (form submission)
     */
    public function destroyDocument($id)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Find media record by ID
            $media = Media::findOrFail($id);
            
            // Verify media belongs to DriverMedicalQualification model
            if ($media->model_type !== DriverMedicalQualification::class) {
                Log::warning('Carrier attempted to delete non-medical-record document', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'model_type' => $media->model_type
                ]);
                
                abort(403, 'Unauthorized access to document');
            }
            
            // Get the medical record
            $medicalRecord = DriverMedicalQualification::find($media->model_id);
            
            if (!$medicalRecord) {
                Log::warning('Carrier attempted to delete document for non-existent medical record', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'model_id' => $media->model_id
                ]);
                
                abort(404, 'Medical record not found');
            }
            
            // Verify medical record belongs to carrier's driver
            if ((int) $medicalRecord->userDriverDetail->carrier_id !== (int) $carrierId) {
                Log::warning('Carrier attempted to delete document from unauthorized medical record', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'medical_record_id' => $medicalRecord->id,
                    'record_carrier_id' => $medicalRecord->userDriverDetail->carrier_id
                ]);
                
                abort(403, 'Unauthorized access to document');
            }
            
            // Store info for logging before deletion
            $fileName = $media->file_name;
            $collectionName = $media->collection_name;
            
            // Delete media using Spatie Media Library (handles file deletion)
            $media->delete();
            
            Log::info('Carrier deleted document from medical record', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'media_id' => $id,
                'medical_record_id' => $medicalRecord->id,
                'file_name' => $fileName,
                'collection' => $collectionName
            ]);
            
            // Redirect back with success message
            return redirect()->back()
                ->with('success', 'Document deleted successfully.');
                
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Carrier attempted to delete non-existent document', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'media_id' => $id
            ]);
            
            return redirect()->back()
                ->with('error', 'Document not found.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting document', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'media_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error deleting document: ' . $e->getMessage());
        }
    }

    /**
     * Delete a document via AJAX
     */
    public function ajaxDestroyDocument($id)
    {
        try {
            $carrierId = $this->getCarrierId();
            
            // Find media record by ID
            $media = Media::findOrFail($id);
            
            // Verify media belongs to DriverMedicalQualification model
            if ($media->model_type !== DriverMedicalQualification::class) {
                Log::warning('Carrier attempted to delete non-medical-record document via AJAX', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'model_type' => $media->model_type
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to document'
                ], 403);
            }
            
            // Get the medical record
            $medicalRecord = DriverMedicalQualification::find($media->model_id);
            
            if (!$medicalRecord) {
                Log::warning('Carrier attempted to delete document for non-existent medical record via AJAX', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'model_id' => $media->model_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Medical record not found'
                ], 404);
            }
            
            // Verify medical record belongs to carrier's driver
            if ((int) $medicalRecord->userDriverDetail->carrier_id !== (int) $carrierId) {
                Log::warning('Carrier attempted to delete document from unauthorized medical record via AJAX', [
                    'carrier_id' => $carrierId,
                    'user_id' => Auth::id(),
                    'media_id' => $id,
                    'medical_record_id' => $medicalRecord->id,
                    'record_carrier_id' => $medicalRecord->userDriverDetail->carrier_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to document'
                ], 403);
            }
            
            // Store info for logging before deletion
            $fileName = $media->file_name;
            $collectionName = $media->collection_name;
            $filePath = $media->getPath();
            $directoryPath = dirname($filePath);
            
            // Delete physical file from storage
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete directory if empty
            if (is_dir($directoryPath) && count(scandir($directoryPath)) == 2) { // Only . and ..
                rmdir($directoryPath);
            }
            
            // Delete database record directly
            $media->delete();
            
            Log::info('Carrier deleted document from medical record via AJAX', [
                'carrier_id' => $carrierId,
                'user_id' => Auth::id(),
                'media_id' => $id,
                'medical_record_id' => $medicalRecord->id,
                'file_name' => $fileName,
                'collection' => $collectionName
            ]);
            
            // Return JSON response with success
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Carrier attempted to delete non-existent document via AJAX', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'media_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error deleting document via AJAX', [
                'carrier_id' => $this->getCarrierId(),
                'user_id' => Auth::id(),
                'media_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting document: ' . $e->getMessage()
            ], 500);
        }
    }
}
