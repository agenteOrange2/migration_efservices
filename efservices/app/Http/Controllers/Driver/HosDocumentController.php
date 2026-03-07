<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\Hos\HosPdfService;

class HosDocumentController extends Controller
{
    protected $pdfService;

    public function __construct(HosPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Display driver's HOS documents.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'Driver profile not found.');
        }

        // Get filter parameters
        $type = $request->get('type', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Get all documents
        $documents = collect();

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

        return view('driver.hos.documents.index', compact('documents', 'type', 'startDate', 'endDate'));
    }

    /**
     * Download a document.
     */
    public function download($mediaId)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }

        $media = $driver->getMedia('*')->find($mediaId);

        if (!$media) {
            abort(404, 'Document not found.');
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Generate daily log PDF.
     */
    public function generateDailyLog(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'Driver profile not found.');
        }

        $date = Carbon::parse($request->date);

        try {
            $this->pdfService->generateDailyLog($driver->id, $date);

            return redirect()->route('driver.hos.documents.index')
                ->with('success', 'Daily log PDF generated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to generate daily log: ' . $e->getMessage());
        }
    }

    /**
     * Generate monthly summary PDF.
     */
    public function generateMonthlySummary(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'month' => 'required|integer|min:1|max:12',
        ]);

        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'Driver profile not found.');
        }

        try {
            $this->pdfService->generateMonthlySummary(
                $driver->id,
                $request->year,
                $request->month
            );

            return redirect()->route('driver.hos.documents.index')
                ->with('success', 'Monthly summary PDF generated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to generate monthly summary: ' . $e->getMessage());
        }
    }

    /**
     * Generate Document Monthly PDF (FMCSA Intermittent Driver format).
     */
    public function generateDocumentMonthly(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'month' => 'required|integer|min:1|max:12',
        ]);

        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            return redirect()->route('driver.dashboard')
                ->with('error', 'Driver profile not found.');
        }

        try {
            $this->pdfService->generateDocumentMonthly(
                $driver->id,
                $request->year,
                $request->month
            );

            return redirect()->route('driver.hos.documents.index')
                ->with('success', 'Document Monthly PDF generated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to generate Document Monthly: ' . $e->getMessage());
        }
    }
}

