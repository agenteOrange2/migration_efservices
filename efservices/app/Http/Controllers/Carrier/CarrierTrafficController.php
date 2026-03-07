<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverTrafficConviction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierTrafficController extends Controller
{
    /**
     * Constructor - Apply middleware for carrier authentication
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->hasRole('user_carrier')) {
                abort(403, 'Unauthorized access. Carrier role required.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of traffic convictions for the authenticated carrier's drivers
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Authorize: check if user can view any traffic convictions
        $this->authorize('viewAny', DriverTrafficConviction::class);
        
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            Log::info('Traffic convictions index accessed', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'filters' => $request->only(['search_term', 'driver_filter', 'date_from', 'date_to']),
            ]);
            
            $query = DriverTrafficConviction::query()
                ->with(['userDriverDetail.user'])
                ->whereHas('userDriverDetail', function ($q) use ($carrier) {
                    $q->where('carrier_id', $carrier->id);
                });

            // Search filter by charge, location, penalty
            if ($request->filled('search_term')) {
                $searchTerm = $request->search_term;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('charge', 'like', '%' . $searchTerm . '%')
                      ->orWhere('location', 'like', '%' . $searchTerm . '%')
                      ->orWhere('penalty', 'like', '%' . $searchTerm . '%');
                });
            }

            // Driver filter (only carrier's drivers)
            if ($request->filled('driver_filter')) {
                $query->where('user_driver_detail_id', $request->driver_filter);
            }

            // Date range filter (from/to) with MM/DD/YYYY format
            if ($request->filled('date_from')) {
                try {
                    $dateFrom = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_from)->startOfDay();
                    $query->whereDate('conviction_date', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Invalid date format in date_from', [
                        'date_from' => $request->date_from,
                        'carrier_id' => $carrier->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($request->filled('date_to')) {
                try {
                    $dateTo = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_to)->endOfDay();
                    $query->whereDate('conviction_date', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Invalid date format in date_to', [
                        'date_to' => $request->date_to,
                        'carrier_id' => $carrier->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Order by conviction date descending
            $query->orderBy('conviction_date', 'desc');

            // Paginate results (10 items per page)
            $convictions = $query->paginate(10)->withQueryString();
            
            // Get list of drivers for filter
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->with('user:id,name,email')
                ->get();

            return view('carrier.drivers.traffic.index', compact('convictions', 'drivers', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error loading traffic convictions index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while loading traffic convictions. Please try again.');
        }
    }

    /**
     * Show the form for creating a new traffic conviction
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Authorize: check if user can create traffic convictions
        $this->authorize('create', DriverTrafficConviction::class);
        
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Get active drivers for authenticated carrier
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrier->id)
                ->where('status', UserDriverDetail::STATUS_ACTIVE)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->get();
            
            Log::info('Traffic conviction create form accessed', [
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
                'available_drivers_count' => $drivers->count(),
            ]);
                
            return view('carrier.drivers.traffic.create', compact('drivers', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error loading traffic conviction create form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.drivers.traffic.index')
                ->with('error', 'An error occurred while loading the form. Please try again.');
        }
    }

    /**
     * Store a newly created traffic conviction
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Authorize: check if user can create traffic convictions
        $this->authorize('create', DriverTrafficConviction::class);
        
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Validate request data (including optional documents)
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'conviction_date' => 'required|date|before_or_equal:today',
            'location' => 'required|string|max:255',
            'charge' => 'required|string|max:255',
            'penalty' => 'required|string|max:255',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|mimes:jpeg,png,pdf,doc,docx|max:10240'
        ], [
            'conviction_date.before_or_equal' => 'The conviction date cannot be in the future.',
            'user_driver_detail_id.required' => 'Please select a driver.',
            'documents.*.mimes' => 'Only PDF, images, and Word documents are allowed.',
            'documents.*.max' => 'Each file must not exceed 10MB.'
        ]);
        
        // Verify selected driver belongs to authenticated carrier
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            Log::warning('Unauthorized attempt to create conviction for driver', [
                'carrier_id' => $carrier->id,
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.traffic.index')
                ->with('error', 'Unauthorized access to this driver.');
        }
        
        // Check for duplicate convictions (same driver, date, location, charge)
        $duplicate = DriverTrafficConviction::where('user_driver_detail_id', $validated['user_driver_detail_id'])
            ->where('conviction_date', $validated['conviction_date'])
            ->where('location', $validated['location'])
            ->where('charge', $validated['charge'])
            ->exists();
            
        if ($duplicate) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This traffic conviction already exists for this driver.');
        }

        try {
            DB::beginTransaction();
            
            // Create DriverTrafficConviction record
            $conviction = DriverTrafficConviction::create($validated);
            
            // Process uploaded documents using Spatie Media Library
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $conviction->addMedia($file)
                        ->toMediaCollection('traffic_images');
                }
            }
            
            DB::commit();
            
            Log::info('Traffic conviction created successfully', [
                'carrier_id' => $carrier->id,
                'conviction_id' => $conviction->id,
                'driver_id' => $conviction->user_driver_detail_id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.traffic.index')
                ->with('success', 'Traffic conviction created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating traffic conviction', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating traffic conviction: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified traffic conviction
     * 
     * @param DriverTrafficConviction $conviction
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(DriverTrafficConviction $conviction)
    {
        // Authorize: check if user can view this specific conviction
        $this->authorize('view', $conviction);
        
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Get active drivers for authenticated carrier
            $drivers = UserDriverDetail::with('user')
                ->where('carrier_id', $carrier->id)
                ->where('status', UserDriverDetail::STATUS_ACTIVE)
                ->whereHas('user', function($q) {
                    $q->whereNotNull('id');
                })
                ->get();
            
            // Load conviction with media relationship
            $conviction->load(['media', 'userDriverDetail.user']);
            
            Log::info('Traffic conviction edit form accessed', [
                'carrier_id' => $carrier->id,
                'conviction_id' => $conviction->id,
                'media_count' => $conviction->media->count(),
                'user_id' => Auth::id(),
            ]);
                
            return view('carrier.drivers.traffic.edit', compact('conviction', 'drivers', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error loading traffic conviction edit form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conviction_id' => $conviction->id ?? null,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.traffic.index')
                ->with('error', 'An error occurred while loading the form. Please try again.');
        }
    }

    /**
     * Update the specified traffic conviction
     * 
     * @param Request $request
     * @param DriverTrafficConviction $conviction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, DriverTrafficConviction $conviction)
    {
        // Authorize: check if user can update this conviction
        $this->authorize('update', $conviction);
        
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Validate request data (including optional documents)
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'conviction_date' => [
                'required',
                'date',
                'before_or_equal:today',
                Rule::unique('driver_traffic_convictions')
                    ->where('user_driver_detail_id', $request->user_driver_detail_id)
                    ->where('location', $request->location)
                    ->where('charge', $request->charge)
                    ->ignore($conviction->id)
            ],
            'location' => 'required|string|max:255',
            'charge' => 'required|string|max:255',
            'penalty' => 'required|string|max:255',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|mimes:jpeg,png,pdf,doc,docx|max:10240'
        ], [
            'conviction_date.unique' => 'This traffic conviction already exists for this driver.',
            'conviction_date.before_or_equal' => 'The conviction date cannot be in the future.',
            'user_driver_detail_id.required' => 'Please select a driver.',
            'documents.*.mimes' => 'Only PDF, images, and Word documents are allowed.',
            'documents.*.max' => 'Each file must not exceed 10MB.'
        ]);
        
        // Verify selected driver belongs to authenticated carrier
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            Log::warning('Unauthorized attempt to change conviction driver', [
                'carrier_id' => $carrier->id,
                'conviction_id' => $conviction->id,
                'driver_id' => $driver->id,
                'driver_carrier_id' => $driver->carrier_id,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.traffic.index')
                ->with('error', 'Unauthorized access to this driver.');
        }

        try {
            DB::beginTransaction();
            
            // Update DriverTrafficConviction record
            $conviction->update($validated);
            
            // Log to check if files are being received
            Log::info('Checking for uploaded documents in update', [
                'has_documents' => $request->hasFile('documents'),
                'all_files' => $request->allFiles(),
                'conviction_id' => $conviction->id,
            ]);
            
            // Process new uploaded documents
            $uploadedCount = 0;
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $conviction->addMedia($file)
                        ->toMediaCollection('traffic_images');
                    $uploadedCount++;
                }
            }
            
            DB::commit();
            
            Log::info('Traffic conviction updated successfully', [
                'carrier_id' => $carrier->id,
                'conviction_id' => $conviction->id,
                'driver_id' => $conviction->user_driver_detail_id,
                'uploaded_documents' => $uploadedCount,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.traffic.index')
                ->with('success', 'Traffic conviction updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating traffic conviction', [
                'carrier_id' => $carrier->id,
                'conviction_id' => $conviction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating traffic conviction: ' . $e->getMessage());
        }
    }

    /**
     * Display the documents associated with a traffic conviction
     * 
     * @param DriverTrafficConviction $conviction
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showDocuments(DriverTrafficConviction $conviction)
    {
        // Authorize: check if user can view this conviction
        $this->authorize('view', $conviction);
        
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Load conviction with userDriverDetail and media relationships
            $conviction->load(['userDriverDetail.user', 'media']);
            
            // Get all media items from 'traffic_images' collection
            $documents = $conviction->getMedia('traffic_images');
            
            Log::info('Traffic conviction documents viewed', [
                'carrier_id' => $carrier->id,
                'conviction_id' => $conviction->id,
                'documents_count' => $documents->count(),
                'user_id' => Auth::id(),
            ]);
                
            return view('carrier.drivers.traffic.documents', compact('conviction', 'documents', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error loading traffic conviction documents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'conviction_id' => $conviction->id ?? null,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.drivers.traffic.index')
                ->with('error', 'An error occurred while loading documents. Please try again.');
        }
    }

    /**
     * Display the traffic conviction history for a specific driver
     * 
     * @param UserDriverDetail $driver
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function driverHistory(UserDriverDetail $driver, Request $request)
    {
        // Authorize: check if user can view any traffic convictions
        $this->authorize('viewAny', DriverTrafficConviction::class);
        
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Verify driver belongs to authenticated carrier
            if ((int) $driver->carrier_id !== (int) $carrier->id) {
                Log::warning('Unauthorized attempt to view driver history', [
                    'carrier_id' => $carrier->id,
                    'driver_id' => $driver->id,
                    'driver_carrier_id' => $driver->carrier_id,
                    'user_id' => Auth::id(),
                ]);
                
                abort(403, 'Unauthorized access to this driver.');
            }
            
            // Query all convictions for that driver
            $query = DriverTrafficConviction::where('user_driver_detail_id', $driver->id);

            // Implement search filter
            if ($request->filled('search_term')) {
                $searchTerm = $request->search_term;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('charge', 'like', '%' . $searchTerm . '%')
                      ->orWhere('location', 'like', '%' . $searchTerm . '%')
                      ->orWhere('penalty', 'like', '%' . $searchTerm . '%');
                });
            }

            // Implement date range filter
            if ($request->filled('date_from')) {
                try {
                    $dateFrom = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_from)->startOfDay();
                    $query->whereDate('conviction_date', '>=', $dateFrom);
                } catch (\Exception $e) {
                    Log::warning('Invalid date format in date_from', [
                        'date_from' => $request->date_from,
                        'driver_id' => $driver->id,
                        'carrier_id' => $carrier->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($request->filled('date_to')) {
                try {
                    $dateTo = \Carbon\Carbon::createFromFormat('m/d/Y', $request->date_to)->endOfDay();
                    $query->whereDate('conviction_date', '<=', $dateTo);
                } catch (\Exception $e) {
                    Log::warning('Invalid date format in date_to', [
                        'date_to' => $request->date_to,
                        'driver_id' => $driver->id,
                        'carrier_id' => $carrier->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Order by conviction date descending
            $query->orderBy('conviction_date', 'desc');

            // Add pagination (10 items per page)
            $convictions = $query->paginate(10)->withQueryString();
            
            Log::info('Driver traffic history viewed', [
                'carrier_id' => $carrier->id,
                'driver_id' => $driver->id,
                'convictions_count' => $convictions->total(),
                'user_id' => Auth::id(),
            ]);
                
            return view('carrier.drivers.traffic.driver_history', compact('driver', 'convictions', 'carrier'));
            
        } catch (\Exception $e) {
            Log::error('Error loading driver traffic history', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'driver_id' => $driver->id ?? null,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.drivers.traffic.index')
                ->with('error', 'An error occurred while loading driver history. Please try again.');
        }
    }

    /**
     * Remove the specified traffic conviction from storage
     * 
     * @param DriverTrafficConviction $conviction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(DriverTrafficConviction $conviction)
    {
        // Authorize: check if user can delete this conviction
        $this->authorize('delete', $conviction);
        
        $carrier = Auth::user()->carrierDetails->carrier;

        try {
            DB::beginTransaction();
            
            $convictionId = $conviction->id;
            $driverId = $conviction->user_driver_detail_id;
            
            // Delete all associated media using Spatie Media Library
            // This is handled automatically by the model's boot method
            
            // Delete conviction record
            $conviction->delete();
            
            DB::commit();
            
            // Log deletion operation
            Log::info('Traffic conviction deleted successfully', [
                'carrier_id' => $carrier->id,
                'conviction_id' => $convictionId,
                'driver_id' => $driverId,
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->route('carrier.drivers.traffic.index')
                ->with('success', 'Traffic conviction deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log errors
            Log::error('Error deleting traffic conviction', [
                'carrier_id' => $carrier->id,
                'conviction_id' => $conviction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Error deleting traffic conviction: ' . $e->getMessage());
        }
    }

    /**
     * Delete a specific document from a traffic conviction
     * 
     * @param int $mediaId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyDocument($mediaId)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Find media by ID
            $media = Media::findOrFail($mediaId);
            
            // Get the conviction associated with this media with eager loading
            $conviction = DriverTrafficConviction::with('userDriverDetail')->find($media->model_id);
            
            if (!$conviction) {
                Log::warning('Media not associated with any conviction', [
                    'media_id' => $mediaId,
                    'model_id' => $media->model_id,
                    'model_type' => $media->model_type,
                    'carrier_id' => $carrier->id,
                    'user_id' => Auth::id(),
                ]);
                
                if (request()->expectsJson()) {
                    return response()->json(['error' => 'Document not found.'], 404);
                }
                
                return redirect()->back()
                    ->with('error', 'Document not found.');
            }
            
            // Authorize: check if user can update this conviction (deleting documents is part of updating)
            $this->authorize('update', $conviction);
            
            // Delete media file using Spatie Media Library
            $media->delete();
            
            Log::info('Traffic conviction document deleted', [
                'media_id' => $mediaId,
                'conviction_id' => $conviction->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            // Return JSON response for AJAX requests
            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Document deleted successfully.']);
            }
            
            // Return redirect for regular requests
            return redirect()->back()
                ->with('success', 'Document deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting traffic conviction document', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Error deleting document.'], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Error deleting document.');
        }
    }

    /**
     * Upload documents to a traffic conviction
     * 
     * @param Request $request
     * @param DriverTrafficConviction $conviction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeDocuments(Request $request, DriverTrafficConviction $conviction)
    {
        // Authorize: check if user can update this conviction
        $this->authorize('update', $conviction);
        
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Validate documents array
            $request->validate([
                'documents' => 'required|array',
                'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB
            ], [
                'documents.required' => 'Please select at least one document to upload.',
                'documents.*.mimes' => 'Only PDF, images, and Word documents are allowed.',
                'documents.*.max' => 'Each file must not exceed 10MB.'
            ]);
            
            $uploadCount = 0;
            $errors = [];
            
            // Loop through uploaded files
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    try {
                        $conviction->addMedia($file)
                            ->usingName($file->getClientOriginalName())
                            ->toMediaCollection('traffic_images');
                        $uploadCount++;
                    } catch (\Exception $e) {
                        $errors[] = 'Failed to upload: ' . $file->getClientOriginalName();
                        Log::error('Error uploading individual document', [
                            'file_name' => $file->getClientOriginalName(),
                            'conviction_id' => $conviction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
            
            Log::info('Documents uploaded to traffic conviction', [
                'carrier_id' => $carrier->id,
                'conviction_id' => $conviction->id,
                'upload_count' => $uploadCount,
                'errors_count' => count($errors),
                'user_id' => Auth::id(),
            ]);
            
            if ($uploadCount > 0) {
                $message = $uploadCount . ' document(s) uploaded successfully.';
                if (count($errors) > 0) {
                    $message .= ' However, ' . count($errors) . ' file(s) failed to upload.';
                }
                return redirect()->back()->with('success', $message);
            } else {
                return redirect()->back()->with('error', 'No documents were uploaded. Please try again.');
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->with('error', 'Validation failed. Please check your files.');
        } catch (\Exception $e) {
            Log::error('Error uploading documents to traffic conviction', [
                'carrier_id' => $this->getCarrierId(),
                'conviction_id' => $conviction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Error uploading documents: ' . $e->getMessage());
        }
    }

    /**
     * Preview a document (PDF/images inline, others as download)
     * 
     * @param int $mediaId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function previewDocument($mediaId)
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Find media by ID
            $media = Media::findOrFail($mediaId);
            
            // Get the conviction associated with this media
            $conviction = DriverTrafficConviction::find($media->model_id);
            
            if (!$conviction) {
                abort(404, 'Document not found.');
            }
            
            // Authorize: check if user can view this conviction
            $this->authorize('view', $conviction);
            
            Log::info('Traffic conviction document previewed', [
                'media_id' => $mediaId,
                'conviction_id' => $conviction->id,
                'carrier_id' => $carrier->id,
                'user_id' => Auth::id(),
            ]);
            
            // Return file response for preview (PDF/images inline, others as download)
            $mimeType = $media->mime_type;
            $isPreviewable = in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/gif']);
            
            if ($isPreviewable) {
                return response()->file($media->getPath(), [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . $media->file_name . '"'
                ]);
            } else {
                return response()->download($media->getPath(), $media->file_name);
            }
                
        } catch (\Exception $e) {
            Log::error('Error previewing traffic conviction document', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            abort(500, 'Error loading document.');
        }
    }
    
    /**
     * Get the authenticated carrier's ID
     */
    private function getCarrierId()
    {
        return Auth::user()->carrierDetails->carrier->id;
    }
}
