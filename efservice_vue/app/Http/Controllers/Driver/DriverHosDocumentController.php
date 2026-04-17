<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Services\Hos\HosPdfService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverHosDocumentController extends Controller
{
    public function __construct(protected HosPdfService $pdfService)
    {
    }

    public function index(Request $request): InertiaResponse
    {
        $driver = $this->resolveDriver();

        $filters = [
            'type' => (string) $request->input('type', 'all'),
            'start_date' => (string) $request->input('start_date', ''),
            'end_date' => (string) $request->input('end_date', ''),
        ];

        $documents = $this->filteredDocuments($driver, $filters)
            ->sortByDesc(fn (array $row) => $row['sort_timestamp'])
            ->values();

        return Inertia::render('driver/hos/documents/Index', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name ?: ($driver->user?->name ?: 'Driver'),
                'carrier_name' => $driver->carrier?->name,
            ],
            'filters' => $filters,
            'stats' => [
                'total' => $documents->count(),
                'trip_reports' => $documents->where('type_key', 'trip_reports')->count(),
                'inspection_reports' => $documents->where('type_key', 'inspection_reports')->count(),
                'daily_logs' => $documents->where('type_key', 'daily_logs')->count(),
                'monthly_summaries' => $documents->where('type_key', 'monthly_summaries')->count(),
                'fmcsa_monthly' => $documents->where('type_key', 'fmcsa_monthly')->count(),
            ],
            'documents' => $documents->map(function (array $row) {
                unset($row['sort_timestamp']);
                return $row;
            })->values(),
        ]);
    }

    public function generateDailyLog(Request $request): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $validated = $request->validate([
            'date' => ['required', 'string'],
        ]);

        $date = $this->parseUsDate($validated['date']);
        if (! $date) {
            return back()->with('error', 'Please provide a valid date in M/D/YYYY format.');
        }

        try {
            $this->pdfService->generateDailyLog($driver->id, $date);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Daily log generated successfully.');
    }

    public function generateMonthlySummary(Request $request): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        try {
            $this->pdfService->generateMonthlySummary($driver->id, (int) $validated['year'], (int) $validated['month']);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Monthly summary generated successfully.');
    }

    public function generateFmcsaMonthly(Request $request): RedirectResponse
    {
        $driver = $this->resolveDriver();
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        try {
            $this->pdfService->generateDocumentMonthly($driver->id, (int) $validated['year'], (int) $validated['month']);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'FMCSA monthly report generated successfully.');
    }

    public function preview(Media $media): BinaryFileResponse
    {
        $this->authorizeMedia($media);
        abort_unless(file_exists($media->getPath()), 404);

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }

    public function download(Media $media): BinaryFileResponse
    {
        $this->authorizeMedia($media);
        abort_unless(file_exists($media->getPath()), 404);

        return response()->download($media->getPath(), $media->file_name);
    }

    protected function filteredDocuments(UserDriverDetail $driver, array $filters)
    {
        $collections = match ($filters['type']) {
            'trip_reports' => ['trip_reports'],
            'inspection_reports' => ['inspection_reports'],
            'daily_logs' => ['daily_logs'],
            'monthly_summaries', 'fmcsa_monthly' => ['monthly_summaries'],
            default => ['trip_reports', 'inspection_reports', 'daily_logs', 'monthly_summaries'],
        };

        $start = $this->parseUsDate($filters['start_date'])?->startOfDay();
        $end = $this->parseUsDate($filters['end_date'])?->endOfDay();

        return Media::query()
            ->where('model_type', UserDriverDetail::class)
            ->where('model_id', $driver->id)
            ->whereIn('collection_name', $collections)
            ->latest('created_at')
            ->get()
            ->filter(function (Media $media) use ($filters, $start, $end) {
                if ($filters['type'] === 'fmcsa_monthly' && $media->getCustomProperty('document_type') !== 'fmcsa_monthly') {
                    return false;
                }

                if ($filters['type'] === 'monthly_summaries' && $media->collection_name === 'monthly_summaries' && $media->getCustomProperty('document_type') === 'fmcsa_monthly') {
                    return false;
                }

                $documentDate = $this->documentDateForMedia($media) ?? $media->created_at?->copy();
                if ($start && $documentDate && $documentDate->lt($start)) {
                    return false;
                }
                if ($end && $documentDate && $documentDate->gt($end)) {
                    return false;
                }

                return true;
            })
            ->map(function (Media $media) {
                $documentDate = $this->documentDateForMedia($media);
                $typeKey = $media->getCustomProperty('document_type') === 'fmcsa_monthly'
                    ? 'fmcsa_monthly'
                    : $media->collection_name;

                return [
                    'id' => $media->id,
                    'type_key' => $typeKey,
                    'type_label' => $this->documentTypeLabel($media),
                    'file_name' => $media->file_name,
                    'size_label' => $this->formatBytes((int) $media->size),
                    'document_date' => $documentDate?->format('n/j/Y'),
                    'created_at' => $media->created_at?->format('n/j/Y g:i A'),
                    'preview_url' => route('driver.hos.documents.preview', $media),
                    'download_url' => route('driver.hos.documents.download', $media),
                    'sort_timestamp' => $documentDate?->timestamp ?? $media->created_at?->timestamp ?? 0,
                ];
            });
    }

    protected function authorizeMedia(Media $media): void
    {
        $driver = $this->resolveDriver();

        abort_unless(
            $media->model_type === UserDriverDetail::class
            && (int) $media->model_id === (int) $driver->id,
            403,
        );
    }

    protected function resolveDriver(): UserDriverDetail
    {
        $user = auth()->user();
        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver instanceof UserDriverDetail, 403, 'Driver profile not found.');

        return $driver->loadMissing(['user:id,name,email', 'carrier:id,name']);
    }

    protected function parseUsDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function documentDateForMedia(Media $media): ?Carbon
    {
        $documentDate = $media->getCustomProperty('document_date');
        if ($documentDate) {
            return Carbon::parse($documentDate);
        }

        $yearMonth = $media->getCustomProperty('year_month');
        if ($yearMonth) {
            return Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        }

        return $media->created_at?->copy();
    }

    protected function documentTypeLabel(Media $media): string
    {
        if ($media->getCustomProperty('document_type') === 'fmcsa_monthly') {
            return 'FMCSA Monthly';
        }

        return match ($media->collection_name) {
            'trip_reports' => 'Trip Report',
            'inspection_reports' => 'Inspection Report',
            'daily_logs' => 'Daily Log',
            'monthly_summaries' => 'Monthly Summary',
            default => str($media->collection_name)->replace('_', ' ')->title()->toString(),
        };
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 2) . ' ' . $units[$power];
    }
}
