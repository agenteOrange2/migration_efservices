<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\Hos\HosPdfService;
use App\Models\UserDriverDetail;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HosDocumentController extends Controller
{
    protected $pdfService;

    public function __construct(HosPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Display carrier's HOS documents.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $carrier = $user->carrierDetails;

        if (!$carrier) {
            return redirect()->route('carrier.dashboard')
                ->with('error', 'Carrier profile not found.');
        }

        // Get filter parameters
        $type = $request->get('type', 'all');
        $driverId = $request->get('driver_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Get the actual carrier_id from UserCarrierDetail
        $carrierId = $carrier->carrier_id ?? $carrier->id;

        // Get all drivers for this carrier
        $drivers = UserDriverDetail::where('carrier_id', $carrierId)
            ->with('user')
            ->get();

        // Get documents from all drivers or specific driver
        $documents = collect();

        $driversToQuery = $driverId 
            ? $drivers->where('id', $driverId) 
            : $drivers;

        foreach ($driversToQuery as $driver) {
            if ($type === 'all' || $type === 'trip_reports') {
                $tripReports = $driver->getMedia('trip_reports');
                $documents = $documents->merge($tripReports);
            }

            if ($type === 'all' || $type === 'daily_logs') {
                $dailyLogs = $driver->getMedia('daily_logs');
                $documents = $documents->merge($dailyLogs);
            }

            if ($type === 'all' || $type === 'monthly_summaries') {
                $monthlySummaries = $driver->getMedia('monthly_summaries');
                $documents = $documents->merge($monthlySummaries);
            }
        }

        // Apply date filters
        if ($startDate) {
            $documents = $documents->filter(function ($doc) use ($startDate) {
                $docDate = $doc->getCustomProperty('document_date') ?? $doc->created_at->format('Y-m-d');
                return $docDate >= $startDate;
            });
        }

        if ($endDate) {
            $documents = $documents->filter(function ($doc) use ($endDate) {
                $docDate = $doc->getCustomProperty('document_date') ?? $doc->created_at->format('Y-m-d');
                return $docDate <= $endDate;
            });
        }

        // Sort by date descending
        $documents = $documents->sortByDesc(function ($doc) {
            return $doc->getCustomProperty('document_date') ?? $doc->created_at;
        });

        return view('carrier.hos.documents.index', compact('documents', 'drivers', 'type', 'driverId', 'startDate', 'endDate'));
    }

    /**
     * Download a document.
     */
    public function download($mediaId)
    {
        $user = Auth::user();
        $carrier = $user->carrierDetails;

        if (!$carrier) {
            abort(403, 'Carrier profile not found.');
        }

        // Get the actual carrier_id from UserCarrierDetail
        $carrierId = $carrier->carrier_id ?? $carrier->id;

        // Find document from any driver in this carrier
        $media = Media::find($mediaId);

        if (!$media) {
            abort(404, 'Document not found.');
        }

        // Verify the document belongs to a driver in this carrier
        $driver = UserDriverDetail::find($media->model_id);
        if (!$driver || (int) $driver->carrier_id !== (int) $carrierId) {
            abort(403, 'Unauthorized access to this document.');
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Delete a document.
     */
    public function destroy($mediaId)
    {
        $user = Auth::user();
        $carrier = $user->carrierDetails;

        if (!$carrier) {
            return redirect()->back()->with('error', 'Carrier profile not found.');
        }

        // Get the actual carrier_id from UserCarrierDetail
        $carrierId = $carrier->carrier_id ?? $carrier->id;

        // Find document
        $media = Media::find($mediaId);

        if (!$media) {
            return redirect()->back()->with('error', 'Document not found.');
        }

        // Verify the document belongs to a driver in this carrier
        $driver = UserDriverDetail::find($media->model_id);
        if (!$driver || (int) $driver->carrier_id !== (int) $carrierId) {
            return redirect()->back()->with('error', 'Unauthorized access to this document.');
        }

        $media->delete();

        return redirect()->back()->with('success', 'Document deleted successfully.');
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

        $user = Auth::user();
        $carrier = $user->carrierDetails;

        if (!$carrier) {
            return redirect()->route('carrier.dashboard')
                ->with('error', 'Carrier profile not found.');
        }

        // Verify driver belongs to this carrier
        $driver = UserDriverDetail::find($request->driver_id);
        if (!$driver || $driver->carrier_id != $carrier->id) {
            return redirect()->back()
                ->with('error', 'Driver not found or does not belong to your carrier.');
        }

        $date = Carbon::parse($request->date);

        try {
            $this->pdfService->generateDailyLog($driver->id, $date);

            return redirect()->route('carrier.hos.documents.index')
                ->with('success', 'Daily log PDF generated successfully.');
        } catch (\Exception $e) {
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

        $user = Auth::user();
        $carrier = $user->carrierDetails;

        if (!$carrier) {
            return redirect()->route('carrier.dashboard')
                ->with('error', 'Carrier profile not found.');
        }

        // Verify driver belongs to this carrier
        $driver = UserDriverDetail::find($request->driver_id);
        if (!$driver || $driver->carrier_id != $carrier->id) {
            return redirect()->back()
                ->with('error', 'Driver not found or does not belong to your carrier.');
        }

        try {
            $this->pdfService->generateMonthlySummary(
                $driver->id,
                $request->year,
                $request->month
            );

            return redirect()->route('carrier.hos.documents.index')
                ->with('success', 'Monthly summary PDF generated successfully.');
        } catch (\Exception $e) {
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

        $user = Auth::user();
        $carrier = $user->carrierDetails;

        if (!$carrier) {
            return redirect()->route('carrier.dashboard')
                ->with('error', 'Carrier profile not found.');
        }

        // Verify driver belongs to this carrier
        $driver = UserDriverDetail::find($request->driver_id);
        if (!$driver || $driver->carrier_id != $carrier->id) {
            return redirect()->back()
                ->with('error', 'Driver not found or does not belong to your carrier.');
        }

        try {
            $this->pdfService->generateDocumentMonthly(
                $driver->id,
                $request->year,
                $request->month
            );

            return redirect()->back()
                ->with('success', 'FMCSA Monthly PDF generated successfully.');
        } catch (\Exception $e) {
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

        $user = Auth::user();
        $carrier = $user->carrierDetails;

        if (!$carrier) {
            abort(403, 'Carrier profile not found.');
        }

        $documents = Media::whereIn('id', $request->document_ids)->get();

        // Get the actual carrier_id from UserCarrierDetail
        $carrierId = $carrier->carrier_id ?? $carrier->id;

        // Verify all documents belong to drivers in this carrier
        foreach ($documents as $doc) {
            $driver = UserDriverDetail::find($doc->model_id);
            if (!$driver || (int) $driver->carrier_id !== (int) $carrierId) {
                abort(403, 'Unauthorized access to one or more documents.');
            }
        }

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
                        \Log::warning('HOS Document file not found (Carrier)', [
                            'media_id' => $doc->id,
                            'disk' => $disk,
                            'relative_path' => $diskPath,
                            'full_path' => $fullPath,
                            'file_name' => $doc->file_name
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error adding document to ZIP (Carrier)', [
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
}
