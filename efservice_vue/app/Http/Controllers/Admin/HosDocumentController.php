<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InteractsWithAdminScope;
use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Services\Hos\HosPdfService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use ZipArchive;

class HosDocumentController extends Controller
{
    use InteractsWithAdminScope;

    public function __construct(protected HosPdfService $pdfService)
    {
    }

    public function index(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $filters = [
            'type' => (string) $request->input('type', 'all'),
            'carrier_id' => $scope['is_superadmin'] ? (string) $request->input('carrier_id', '') : (string) ($scope['carrier_id'] ?? ''),
            'driver_id' => (string) $request->input('driver_id', ''),
            'start_date' => (string) $request->input('start_date', ''),
            'end_date' => (string) $request->input('end_date', ''),
        ];

        $documents = $this->filteredDocuments($scope, $filters)
            ->sortByDesc(fn (array $row) => $row['sort_timestamp'])
            ->values();

        return Inertia::render('admin/hos/documents/Index', [
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
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $this->driverOptions($scope, $filters['carrier_id'], false),
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function generateDailyLog(Request $request): RedirectResponse
    {
        $scope = $this->scopeContext();
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:user_driver_details,id'],
            'date' => ['required', 'string'],
        ]);

        $driver = UserDriverDetail::query()->findOrFail((int) $validated['driver_id']);
        $this->ensureAllowedCarrier((int) $driver->carrier_id, $scope);

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
        $scope = $this->scopeContext();
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:user_driver_details,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $driver = UserDriverDetail::query()->findOrFail((int) $validated['driver_id']);
        $this->ensureAllowedCarrier((int) $driver->carrier_id, $scope);

        try {
            $this->pdfService->generateMonthlySummary($driver->id, (int) $validated['year'], (int) $validated['month']);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Monthly summary generated successfully.');
    }

    public function generateDocumentMonthly(Request $request): RedirectResponse
    {
        $scope = $this->scopeContext();
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:user_driver_details,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $driver = UserDriverDetail::query()->findOrFail((int) $validated['driver_id']);
        $this->ensureAllowedCarrier((int) $driver->carrier_id, $scope);

        try {
            $this->pdfService->generateDocumentMonthly($driver->id, (int) $validated['year'], (int) $validated['month']);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'FMCSA monthly report generated successfully.');
    }

    public function download(Media $media)
    {
        $this->authorizeMedia($media, $this->scopeContext());
        $path = $media->getPath();

        if (! file_exists($path)) {
            return back()->with('error', 'File not found on disk.');
        }

        return response()->download($path, $media->file_name);
    }

    public function preview(Media $media)
    {
        $this->authorizeMedia($media, $this->scopeContext());
        $path = $media->getPath();

        if (! file_exists($path)) {
            return back()->with('error', 'File not found on disk.');
        }

        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
        ]);
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->authorizeMedia($media, $this->scopeContext());
        $media->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'exists:media,id'],
        ]);

        $scope = $this->scopeContext();
        $documents = Media::query()->whereIn('id', $validated['document_ids'])->get();
        foreach ($documents as $media) {
            $this->authorizeMedia($media, $scope);
        }

        Media::query()->whereIn('id', $validated['document_ids'])->delete();

        return back()->with('success', count($validated['document_ids']) . ' documents deleted successfully.');
    }

    public function bulkDownload(Request $request)
    {
        $scope = $this->scopeContext();
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter()
            ->values();

        abort_if($ids->isEmpty(), 422, 'No documents were selected.');

        $documents = Media::query()->whereIn('id', $ids)->get();
        abort_if($documents->isEmpty(), 404);

        foreach ($documents as $media) {
            $this->authorizeMedia($media, $scope);
        }

        $zipName = 'hos-documents-' . now()->format('YmdHis') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipName);
        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Unable to create ZIP archive.');
        }

        foreach ($documents as $media) {
            $path = $media->getPath();
            if (file_exists($path)) {
                $zip->addFile($path, $media->file_name);
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    protected function filteredDocuments(array $scope, array $filters): Collection
    {
        if ($filters['carrier_id'] !== '') {
            $this->ensureAllowedCarrier((int) $filters['carrier_id'], $scope);
        }

        $driversQuery = UserDriverDetail::query()->with([
            'user:id,name,email',
            'carrier:id,name',
            'media' => fn ($q) => $q->where('collection_name', 'profile_photo_driver'),
        ]);
        $this->applyDriverScope($driversQuery, $scope, $filters['carrier_id']);

        if ($filters['driver_id'] !== '') {
            $driversQuery->where('id', (int) $filters['driver_id']);
        }

        $drivers = $driversQuery->get();
        if ($drivers->isEmpty()) {
            return collect();
        }

        $driversById = $drivers->keyBy('id');
        $driverIds = $driversById->keys()->all();

        $collections = match ($filters['type']) {
            'trip_reports' => ['trip_reports'],
            'inspection_reports' => ['inspection_reports'],
            'daily_logs' => ['daily_logs'],
            'monthly_summaries', 'fmcsa_monthly' => ['monthly_summaries'],
            default => ['trip_reports', 'inspection_reports', 'daily_logs', 'monthly_summaries'],
        };

        $mediaItems = Media::query()
            ->where('model_type', UserDriverDetail::class)
            ->whereIn('model_id', $driverIds)
            ->whereIn('collection_name', $collections)
            ->orderByDesc('created_at')
            ->get();

        $start = $this->parseUsDate($filters['start_date'])?->startOfDay();
        $end = $this->parseUsDate($filters['end_date'])?->endOfDay();

        return $mediaItems->filter(function (Media $media) use ($filters, $start, $end) {
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
        })->map(function (Media $media) use ($driversById) {
            /** @var UserDriverDetail|null $driver */
            $driver = $driversById->get((int) $media->model_id);
            $documentDate = $this->documentDateForMedia($media);
            $typeKey = $media->getCustomProperty('document_type') === 'fmcsa_monthly'
                ? 'fmcsa_monthly'
                : $media->collection_name;

            return [
                'id' => $media->id,
                'driver_id' => $driver?->id,
                'driver_name' => $driver?->full_name ?: ($driver?->user?->name ?: 'Unknown Driver'),
                'driver_avatar' => $driver?->media->first()?->getUrl() ?? null,
                'carrier_name' => $driver?->carrier?->name,
                'type_key' => $typeKey,
                'type_label' => $this->documentTypeLabel($media),
                'file_name' => $media->file_name,
                'size_label' => $this->formatBytes((int) $media->size),
                'document_date' => $documentDate?->format('n/j/Y'),
                'created_at' => $media->created_at?->format('n/j/Y g:i A'),
                'preview_url' => route('admin.hos.documents.preview', $media),
                'download_url' => route('admin.hos.documents.download', $media),
                'sort_timestamp' => $documentDate?->timestamp ?? $media->created_at?->timestamp ?? 0,
            ];
        })->values();
    }

    protected function authorizeMedia(Media $media, array $scope): UserDriverDetail
    {
        abort_unless($media->model_type === UserDriverDetail::class, 404);

        $driver = UserDriverDetail::query()->findOrFail((int) $media->model_id);
        $this->ensureAllowedCarrier((int) $driver->carrier_id, $scope);

        return $driver;
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
}
