<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\Hos\HosPdfService;
use App\Models\UserDriverDetail;
use App\Models\Carrier;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HosDocumentController extends Controller
{
    protected $pdfService;

    public function __construct(HosPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Display all HOS documents across carriers.
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $type = $request->get('type', 'all');
        $carrierId = $request->get('carrier_id');
        $driverId = $request->get('driver_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Get all carriers for filter dropdown
        $carriers = Carrier::orderBy('name')->get();

        // Get drivers based on carrier filter for filter dropdown
        $drivers = UserDriverDetail::with('user')
            ->when($carrierId, function ($query) use ($carrierId) {
                return $query->where('carrier_id', $carrierId);
            })
            ->get();

        // Get documents grouped by carrier and driver
        $documentsByCarrier = $this->groupDocumentsByCarrier(
            $type,
            $carrierId,
            $driverId,
            $startDate,
            $endDate
        );

        // Flatten documents for backward compatibility with current view
        // This will be removed once the view is updated to use the grouped structure
        $documents = collect();
        foreach ($documentsByCarrier as $carrierGroup) {
            foreach ($carrierGroup['drivers'] as $driverGroup) {
                $documents = $documents->merge($driverGroup['documents']);
            }
        }

        return view('admin.hos.documents.index', compact(
            'documentsByCarrier',
            'documents',
            'carriers', 
            'drivers', 
            'type', 
            'carrierId', 
            'driverId', 
            'startDate', 
            'endDate'
        ));
    }

    /**
     * Download a document.
     */
    public function download($mediaId)
    {
        $media = Media::find($mediaId);

        if (!$media) {
            abort(404, 'Document not found.');
        }

        // Get the disk and path from Spatie Media
        $disk = $media->disk;
        $diskPath = $media->getPathRelativeToRoot();
        
        // Build full path based on disk
        if ($disk === 'public') {
            $fullPath = storage_path('app/public/' . $diskPath);
        } else {
            $fullPath = $media->getPath();
        }
        
        if (!file_exists($fullPath)) {
            \Log::warning('HOS Document download failed - file not found', [
                'media_id' => $media->id,
                'disk' => $disk,
                'path' => $fullPath
            ]);
            return back()->with('error', 'File not found on server.');
        }

        return response()->download($fullPath, $media->file_name);
    }

    /**
     * Preview a document inline in the browser.
     */
    public function preview($mediaId)
    {
        $media = Media::find($mediaId);

        if (!$media) {
            abort(404, 'Document not found.');
        }

        $disk = $media->disk;
        $diskPath = $media->getPathRelativeToRoot();
        
        if ($disk === 'public') {
            $fullPath = storage_path('app/public/' . $diskPath);
        } else {
            $fullPath = $media->getPath();
        }
        
        if (!file_exists($fullPath)) {
            return back()->with('error', 'File not found on server.');
        }

        return response()->file($fullPath, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }

    /**
     * Delete a document.
     */
    public function destroy($mediaId)
    {
        $media = Media::find($mediaId);

        if (!$media) {
            return redirect()->back()->with('error', 'Document not found.');
        }

        $media->delete();

        return redirect()->back()->with('success', 'Document deleted successfully.');
    }

    /**
     * Delete multiple selected documents.
     */
    public function bulkDestroy(Request $request)
    {
        \Log::info('bulkDestroy called', ['all_input' => $request->all()]);

        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:media,id',
        ]);

        try {
            $deletedCount = 0;
            foreach ($request->document_ids as $mediaId) {
                $media = Media::find($mediaId);
                if ($media) {
                    \Log::info('Deleting media', ['id' => $mediaId, 'name' => $media->name]);
                    $media->delete();
                    $deletedCount++;
                }
            }

            \Log::info('Bulk delete completed', ['deleted_count' => $deletedCount]);
            return redirect()->back()->with('success', "Successfully deleted {$deletedCount} document(s).");
        } catch (\Exception $e) {
            \Log::error('Error deleting HOS documents in bulk', [
                'document_ids' => $request->document_ids,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'An error occurred while deleting documents.');
        }
    }

    /**
     * Generate daily log PDF for a driver.
     */
    public function generateDailyLog(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:user_driver_details,id',
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date);

        try {
            $this->pdfService->generateDailyLog($request->driver_id, $date);

            if ($request->ajax() || $request->wantsJson()) {
                $driver = UserDriverDetail::findOrFail($request->driver_id);
                $documents = collect()
                    ->merge($driver->getMedia('daily_logs'))
                    ->merge($driver->getMedia('monthly_summaries'))
                    ->sortByDesc(fn($doc) => $doc->created_at)
                    ->values();

                return response()->json([
                    'success' => true,
                    'message' => 'Daily log PDF generated successfully.',
                    'documents' => $documents->map(fn($doc) => [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'file_name' => $doc->file_name,
                        'collection_name' => $doc->collection_name,
                        'document_type' => $doc->getCustomProperty('document_type', ''),
                        'size' => $doc->size,
                        'created_at' => $doc->created_at->format('m/d/Y H:i'),
                        'download_url' => route('admin.hos.documents.download', $doc->id),
                        'preview_url' => route('admin.hos.documents.preview', $doc->id),
                    ]),
                ]);
            }

            return redirect()->route('admin.hos.documents.index')
                ->with('success', 'Daily log PDF generated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to generate daily log: ' . $e->getMessage()], 500);
            }
            return redirect()->back()
                ->with('error', 'Failed to generate daily log: ' . $e->getMessage());
        }
    }

    /**
     * Generate monthly summary PDF for a driver.
     */
    public function generateMonthlySummary(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:user_driver_details,id',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $this->pdfService->generateMonthlySummary(
                $request->driver_id,
                $request->year,
                $request->month
            );

            if ($request->ajax() || $request->wantsJson()) {
                $driver = UserDriverDetail::findOrFail($request->driver_id);
                $documents = collect()
                    ->merge($driver->getMedia('daily_logs'))
                    ->merge($driver->getMedia('monthly_summaries'))
                    ->sortByDesc(fn($doc) => $doc->created_at)
                    ->values();

                return response()->json([
                    'success' => true,
                    'message' => 'Monthly summary PDF generated successfully.',
                    'documents' => $documents->map(fn($doc) => [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'file_name' => $doc->file_name,
                        'collection_name' => $doc->collection_name,
                        'document_type' => $doc->getCustomProperty('document_type', ''),
                        'size' => $doc->size,
                        'created_at' => $doc->created_at->format('m/d/Y H:i'),
                        'download_url' => route('admin.hos.documents.download', $doc->id),
                        'preview_url' => route('admin.hos.documents.preview', $doc->id),
                    ]),
                ]);
            }

            return redirect()->route('admin.hos.documents.index')
                ->with('success', 'Monthly summary PDF generated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to generate monthly summary: ' . $e->getMessage()], 500);
            }
            return redirect()->back()
                ->with('error', 'Failed to generate monthly summary: ' . $e->getMessage());
        }
    }

    /**
     * Generate Document Monthly PDF for a driver (FMCSA Intermittent Driver format).
     */
    public function generateDocumentMonthly(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:user_driver_details,id',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $this->pdfService->generateDocumentMonthly(
                $request->driver_id,
                $request->year,
                $request->month
            );

            if ($request->ajax() || $request->wantsJson()) {
                $driver = UserDriverDetail::findOrFail($request->driver_id);
                $documents = collect()
                    ->merge($driver->getMedia('daily_logs'))
                    ->merge($driver->getMedia('monthly_summaries'))
                    ->sortByDesc(fn($doc) => $doc->created_at)
                    ->values();

                return response()->json([
                    'success' => true,
                    'message' => 'FMCSA Monthly PDF generated successfully.',
                    'documents' => $documents->map(fn($doc) => [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'file_name' => $doc->file_name,
                        'collection_name' => $doc->collection_name,
                        'document_type' => $doc->getCustomProperty('document_type', ''),
                        'size' => $doc->size,
                        'created_at' => $doc->created_at->format('m/d/Y H:i'),
                        'download_url' => route('admin.hos.documents.download', $doc->id),
                        'preview_url' => route('admin.hos.documents.preview', $doc->id),
                    ]),
                ]);
            }

            return redirect()->route('admin.hos.documents.index')
                ->with('success', 'FMCSA Monthly PDF generated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to generate FMCSA Monthly: ' . $e->getMessage()], 500);
            }
            return redirect()->back()
                ->with('error', 'Failed to generate FMCSA Monthly: ' . $e->getMessage());
        }
    }

    /**
     * Bulk download documents.
     */

    public function bulkDownload(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:media,id',
        ]);

        $documents = Media::whereIn('id', $request->document_ids)->get();

        // Create ZIP file
        $zip = new \ZipArchive();
        $zipFileName = 'hos_documents_' . now()->format('YmdHis') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            $addedFiles = 0;
            foreach ($documents as $doc) {
                try {
                    // Get the disk and path from Spatie Media
                    $disk = $doc->disk;
                    $diskPath = $doc->getPathRelativeToRoot();
                    
                    // Build full path based on disk
                    if ($disk === 'public') {
                        $fullPath = storage_path('app/public/' . $diskPath);
                    } else {
                        $fullPath = $doc->getPath();
                    }
                    
                    // Check if file exists
                    if (file_exists($fullPath)) {
                        // Create a unique filename to avoid collisions
                        $uniqueFileName = $doc->id . '_' . $doc->file_name;
                        $zip->addFile($fullPath, $uniqueFileName);
                        $addedFiles++;
                    } else {
                        \Log::warning('HOS Document file not found', [
                            'media_id' => $doc->id,
                            'disk' => $disk,
                            'relative_path' => $diskPath,
                            'full_path' => $fullPath,
                            'file_name' => $doc->file_name
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error adding document to ZIP', [
                        'media_id' => $doc->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            $zip->close();
            
            if ($addedFiles === 0) {
                return back()->with('error', 'No files could be found to download. Please check that the files exist on the server.');
            }
        } else {
            return back()->with('error', 'Could not create ZIP file.');
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Group documents by carrier and driver hierarchically.
     * 
     * @param string $type Document type filter ('all', 'daily_logs', 'monthly_summaries', 'fmcsa_monthly', 'trip_reports')
     * @param int|null $carrierId Carrier ID filter
     * @param int|null $driverId Driver ID filter
     * @param string|null $startDate Start date filter
     * @param string|null $endDate End date filter
     * @return array Nested array structure: carrier → driver → documents
     */
    private function groupDocumentsByCarrier($type, $carrierId = null, $driverId = null, $startDate = null, $endDate = null)
    {
        // Build query for drivers with eager loading
        $driversQuery = UserDriverDetail::with(['carrier', 'user']);

        // Apply carrier filter
        if ($carrierId) {
            $driversQuery->where('carrier_id', $carrierId);
        }

        // Apply driver filter
        if ($driverId) {
            $driversQuery->where('id', $driverId);
        }

        $drivers = $driversQuery->get();

        // Collect documents for each driver
        $documentsByCarrier = [];

        foreach ($drivers as $driver) {
            // Skip drivers without a carrier
            if (!$driver->carrier) {
                continue;
            }

            $driverDocuments = collect();

            // Get documents based on type filter
            if ($type === 'all' || $type === 'trip_reports') {
                $tripReports = $driver->getMedia('trip_reports');
                $driverDocuments = $driverDocuments->merge($tripReports);
            }

            if ($type === 'all' || $type === 'daily_logs') {
                $dailyLogs = $driver->getMedia('daily_logs');
                $driverDocuments = $driverDocuments->merge($dailyLogs);
            }

            if ($type === 'all' || $type === 'monthly_summaries' || $type === 'fmcsa_monthly') {
                $monthlySummaries = $driver->getMedia('monthly_summaries');

                if ($type === 'monthly_summaries') {
                    // Exclude FMCSA Monthly documents
                    $monthlySummaries = $monthlySummaries->filter(fn($doc) => $doc->getCustomProperty('document_type') !== 'fmcsa_monthly');
                } elseif ($type === 'fmcsa_monthly') {
                    // Only FMCSA Monthly documents
                    $monthlySummaries = $monthlySummaries->filter(fn($doc) => $doc->getCustomProperty('document_type') === 'fmcsa_monthly');
                }

                $driverDocuments = $driverDocuments->merge($monthlySummaries);
            }

            // Apply date filters
            if ($startDate) {
                $parsedStart = Carbon::parse($startDate)->startOfDay();
                $driverDocuments = $driverDocuments->filter(function ($doc) use ($parsedStart) {
                    $docDate = $doc->getCustomProperty('document_date') 
                        ? Carbon::parse($doc->getCustomProperty('document_date'))->startOfDay()
                        : $doc->created_at->startOfDay();
                    return $docDate->gte($parsedStart);
                });
            }

            if ($endDate) {
                $parsedEnd = Carbon::parse($endDate)->startOfDay();
                $driverDocuments = $driverDocuments->filter(function ($doc) use ($parsedEnd) {
                    $docDate = $doc->getCustomProperty('document_date') 
                        ? Carbon::parse($doc->getCustomProperty('document_date'))->startOfDay()
                        : $doc->created_at->startOfDay();
                    return $docDate->lte($parsedEnd);
                });
            }

            // Sort documents by date descending
            $driverDocuments = $driverDocuments->sortByDesc(function ($doc) {
                return $doc->getCustomProperty('document_date') ?? $doc->created_at;
            });

            // Only include driver if they have documents
            if ($driverDocuments->isNotEmpty()) {
                $carrierId = $driver->carrier_id;

                // Initialize carrier group if not exists
                if (!isset($documentsByCarrier[$carrierId])) {
                    $documentsByCarrier[$carrierId] = [
                        'carrier' => $driver->carrier,
                        'drivers' => []
                    ];
                }

                // Add driver and their documents
                $documentsByCarrier[$carrierId]['drivers'][$driver->id] = [
                    'driver' => $driver,
                    'documents' => $driverDocuments
                ];
            }
        }

        return $documentsByCarrier;
    }
}
