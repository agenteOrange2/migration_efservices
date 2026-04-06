<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Carrier;
use App\Models\DocumentAttachment;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AccidentsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DriverAccident::query()->with(['userDriverDetail.user', 'userDriverDetail.carrier']);

        if ($request->filled('search_term')) {
            $term = trim((string) $request->search_term);
            $query->where(function ($q) use ($term) {
                $q->where('nature_of_accident', 'like', "%{$term}%")
                    ->orWhere('comments', 'like', "%{$term}%")
                    ->orWhereHas('userDriverDetail.user', fn ($user) => $user->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('userDriverDetail.carrier', fn ($carrier) => $carrier->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('driver_filter')) {
            $query->where('user_driver_detail_id', $request->driver_filter);
        }

        if ($request->filled('carrier_filter')) {
            $query->whereHas('userDriverDetail', fn ($driver) => $driver->where('carrier_id', $request->carrier_filter));
        }

        if ($request->filled('date_from')) {
            if ($dateFrom = $this->parseUsDate($request->date_from)) {
                $query->whereDate('accident_date', '>=', $dateFrom->format('Y-m-d'));
            }
        }

        if ($request->filled('date_to')) {
            if ($dateTo = $this->parseUsDate($request->date_to)) {
                $query->whereDate('accident_date', '<=', $dateTo->format('Y-m-d'));
            }
        }

        $sortField = in_array($request->get('sort_field'), ['accident_date', 'created_at', 'nature_of_accident'], true)
            ? $request->get('sort_field')
            : 'accident_date';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        $accidents = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        $accidents->getCollection()->transform(function (DriverAccident $accident) {
            $driver = $accident->userDriverDetail;
            $documentCount = $accident->getDocuments('accident_documents')->count() + $accident->getMedia('accident-images')->count();

            return [
                'id' => $accident->id,
                'accident_date' => $accident->accident_date?->format('Y-m-d'),
                'accident_date_display' => $accident->accident_date?->format('n/j/Y'),
                'created_at' => $accident->created_at?->format('Y-m-d'),
                'created_at_display' => $accident->created_at?->format('n/j/Y'),
                'nature_of_accident' => $accident->nature_of_accident,
                'had_injuries' => (bool) $accident->had_injuries,
                'number_of_injuries' => (int) ($accident->number_of_injuries ?? 0),
                'had_fatalities' => (bool) $accident->had_fatalities,
                'number_of_fatalities' => (int) ($accident->number_of_fatalities ?? 0),
                'document_count' => $documentCount,
                'driver' => $driver ? [
                    'id' => $driver->id,
                    'name' => trim(($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')),
                ] : null,
                'carrier' => $driver?->carrier ? [
                    'id' => $driver->carrier->id,
                    'name' => $driver->carrier->name,
                ] : null,
            ];
        });

        return Inertia::render('admin/accidents/Index', [
            'accidents' => $accidents,
            'drivers' => $this->driverOptions(),
            'carriers' => $this->carrierOptions(),
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'carrier_filter' => (string) $request->get('carrier_filter', ''),
                'driver_filter' => (string) $request->get('driver_filter', ''),
                'date_from' => (string) $request->get('date_from', ''),
                'date_to' => (string) $request->get('date_to', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/accidents/Create', [
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'accident_date' => 'required|string',
            'nature_of_accident' => 'required|string|max:255',
            'had_injuries' => 'boolean',
            'number_of_injuries' => 'nullable|integer|min:0',
            'had_fatalities' => 'boolean',
            'number_of_fatalities' => 'nullable|integer|min:0',
            'comments' => 'nullable|string|max:2000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if ((int) $driver->carrier_id !== (int) $validated['carrier_id']) {
            return back()->withErrors(['user_driver_detail_id' => 'The selected driver does not belong to the selected carrier.'])->withInput();
        }

        $accidentDate = $this->parseUsDate($validated['accident_date']);
        if (! $accidentDate) {
            return back()->withErrors(['accident_date' => 'Invalid accident date format.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $driver, $accidentDate) {
            $accident = DriverAccident::create([
                'user_driver_detail_id' => $driver->id,
                'accident_date' => $accidentDate->format('Y-m-d'),
                'nature_of_accident' => $validated['nature_of_accident'],
                'had_injuries' => $request->boolean('had_injuries'),
                'number_of_injuries' => $request->boolean('had_injuries') ? (int) ($validated['number_of_injuries'] ?? 0) : 0,
                'had_fatalities' => $request->boolean('had_fatalities'),
                'number_of_fatalities' => $request->boolean('had_fatalities') ? (int) ($validated['number_of_fatalities'] ?? 0) : 0,
                'comments' => $validated['comments'] ?? null,
            ]);

            $this->storeAttachments($request, $accident);
        });

        return redirect()->route('admin.accidents.index')->with('success', 'Accident record created successfully.');
    }

    public function edit(DriverAccident $accident): Response
    {
        $accident->load(['userDriverDetail.user', 'userDriverDetail.carrier']);

        return Inertia::render('admin/accidents/Edit', [
            'accident' => $this->accidentPayload($accident),
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
        ]);
    }

    public function update(Request $request, DriverAccident $accident)
    {
        $validated = $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'accident_date' => 'required|string',
            'nature_of_accident' => 'required|string|max:255',
            'had_injuries' => 'boolean',
            'number_of_injuries' => 'nullable|integer|min:0',
            'had_fatalities' => 'boolean',
            'number_of_fatalities' => 'nullable|integer|min:0',
            'comments' => 'nullable|string|max:2000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if ((int) $driver->carrier_id !== (int) $validated['carrier_id']) {
            return back()->withErrors(['user_driver_detail_id' => 'The selected driver does not belong to the selected carrier.'])->withInput();
        }

        $accidentDate = $this->parseUsDate($validated['accident_date']);
        if (! $accidentDate) {
            return back()->withErrors(['accident_date' => 'Invalid accident date format.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $driver, $accidentDate, $accident) {
            $accident->update([
                'user_driver_detail_id' => $driver->id,
                'accident_date' => $accidentDate->format('Y-m-d'),
                'nature_of_accident' => $validated['nature_of_accident'],
                'had_injuries' => $request->boolean('had_injuries'),
                'number_of_injuries' => $request->boolean('had_injuries') ? (int) ($validated['number_of_injuries'] ?? 0) : 0,
                'had_fatalities' => $request->boolean('had_fatalities'),
                'number_of_fatalities' => $request->boolean('had_fatalities') ? (int) ($validated['number_of_fatalities'] ?? 0) : 0,
                'comments' => $validated['comments'] ?? null,
            ]);

            $this->storeAttachments($request, $accident);
        });

        return redirect()->route('admin.accidents.edit', $accident)->with('success', 'Accident record updated successfully.');
    }

    public function destroy(DriverAccident $accident)
    {
        $accident->delete();

        return redirect()->route('admin.accidents.index')->with('success', 'Accident record deleted successfully.');
    }

    public function driverHistory(UserDriverDetail $driver, Request $request): Response
    {
        $query = $driver->accidents()->with('userDriverDetail.user');

        if ($request->filled('search_term')) {
            $term = trim((string) $request->search_term);
            $query->where(function ($q) use ($term) {
                $q->where('nature_of_accident', 'like', "%{$term}%")
                    ->orWhere('comments', 'like', "%{$term}%");
            });
        }

        $sortField = in_array($request->get('sort_field'), ['accident_date', 'created_at', 'nature_of_accident'], true)
            ? $request->get('sort_field')
            : 'accident_date';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        $accidents = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();
        $accidents->getCollection()->transform(fn (DriverAccident $accident) => [
            'id' => $accident->id,
            'accident_date_display' => $accident->accident_date?->format('n/j/Y'),
            'nature_of_accident' => $accident->nature_of_accident,
            'had_injuries' => (bool) $accident->had_injuries,
            'number_of_injuries' => (int) ($accident->number_of_injuries ?? 0),
            'had_fatalities' => (bool) $accident->had_fatalities,
            'number_of_fatalities' => (int) ($accident->number_of_fatalities ?? 0),
            'document_count' => $accident->getDocuments('accident_documents')->count() + $accident->getMedia('accident-images')->count(),
        ]);

        return Inertia::render('admin/accidents/DriverHistory', [
            'driver' => [
                'id' => $driver->id,
                'name' => trim(($driver->user?->name ?? '') . ' ' . ($driver->last_name ?? '')),
                'carrier_name' => $driver->carrier?->name,
            ],
            'accidents' => $accidents,
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function carrierHistory(Carrier $carrier, Request $request): Response
    {
        $query = DriverAccident::query()
            ->with(['userDriverDetail.user', 'userDriverDetail.carrier'])
            ->whereHas('userDriverDetail', fn ($driver) => $driver->where('carrier_id', $carrier->id));

        if ($request->filled('search_term')) {
            $term = trim((string) $request->search_term);
            $query->where(function ($q) use ($term) {
                $q->where('nature_of_accident', 'like', "%{$term}%")
                    ->orWhere('comments', 'like', "%{$term}%")
                    ->orWhereHas('userDriverDetail.user', fn ($user) => $user->where('name', 'like', "%{$term}%"));
            });
        }

        $sortField = in_array($request->get('sort_field'), ['accident_date', 'created_at', 'nature_of_accident'], true)
            ? $request->get('sort_field')
            : 'accident_date';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        $accidents = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();
        $accidents->getCollection()->transform(fn (DriverAccident $accident) => [
            'id' => $accident->id,
            'driver_name' => trim(($accident->userDriverDetail?->user?->name ?? '') . ' ' . ($accident->userDriverDetail?->last_name ?? '')),
            'driver_id' => $accident->user_driver_detail_id,
            'accident_date_display' => $accident->accident_date?->format('n/j/Y'),
            'nature_of_accident' => $accident->nature_of_accident,
            'had_injuries' => (bool) $accident->had_injuries,
            'number_of_injuries' => (int) ($accident->number_of_injuries ?? 0),
            'had_fatalities' => (bool) $accident->had_fatalities,
            'number_of_fatalities' => (int) ($accident->number_of_fatalities ?? 0),
            'document_count' => $accident->getDocuments('accident_documents')->count() + $accident->getMedia('accident-images')->count(),
        ]);

        return Inertia::render('admin/accidents/CarrierHistory', [
            'carrier' => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ],
            'accidents' => $accidents,
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function documents(Request $request): Response
    {
        return $this->renderDocumentsPage($request);
    }

    public function showDocuments(DriverAccident $accident, Request $request): Response
    {
        return $this->renderDocumentsPage($request, $accident);
    }

    public function destroyDocument(DocumentAttachment $document)
    {
        /** @var DriverAccident $accident */
        $accident = $document->documentable;
        abort_unless($accident instanceof DriverAccident, 404);

        $accident->deleteDocument($document->id);

        return back()->with('success', 'Document deleted successfully.');
    }

    public function destroyMedia($mediaId)
    {
        $media = Media::findOrFail($mediaId);
        abort_unless($media->model_type === DriverAccident::class, 404);

        /** @var DriverAccident $accident */
        $accident = DriverAccident::findOrFail($media->model_id);
        $accident->safeDeleteMedia($media->id);

        return back()->with('success', 'Media file deleted successfully.');
    }

    protected function renderDocumentsPage(Request $request, ?DriverAccident $accident = null): Response
    {
        $documentRows = collect();

        $documentQuery = DocumentAttachment::query()
            ->where('documentable_type', DriverAccident::class)
            ->with('documentable.userDriverDetail.user', 'documentable.userDriverDetail.carrier');

        $mediaQuery = Media::query()
            ->where('model_type', DriverAccident::class);

        if ($accident) {
            $documentQuery->where('documentable_id', $accident->id);
            $mediaQuery->where('model_id', $accident->id);
        }

        if ($request->filled('driver_id')) {
            $driverId = $request->driver_id;
            $documentQuery->whereHas('documentable', fn ($q) => $q->where('user_driver_detail_id', $driverId));
            $mediaQuery->whereIn('model_id', DriverAccident::where('user_driver_detail_id', $driverId)->pluck('id'));
        }

        if ($request->filled('start_date') && ($startDate = $this->parseUsDate($request->start_date))) {
            $documentQuery->whereDate('created_at', '>=', $startDate->format('Y-m-d'));
            $mediaQuery->whereDate('created_at', '>=', $startDate->format('Y-m-d'));
        }

        if ($request->filled('end_date') && ($endDate = $this->parseUsDate($request->end_date))) {
            $documentQuery->whereDate('created_at', '<=', $endDate->format('Y-m-d'));
            $mediaQuery->whereDate('created_at', '<=', $endDate->format('Y-m-d'));
        }

        $fileType = $request->get('file_type');

        $documentRows = $documentRows->concat(
            $documentQuery->get()->map(function (DocumentAttachment $document) {
                /** @var DriverAccident|null $related */
                $related = $document->documentable;
                $driver = $related?->userDriverDetail;

                return [
                    'id' => $document->id,
                    'source' => 'document',
                    'file_name' => $document->file_name,
                    'original_name' => $document->original_name ?: $document->file_name,
                    'mime_type' => $document->mime_type,
                    'size' => $document->size,
                    'created_at_sort' => $document->created_at?->timestamp ?? 0,
                    'created_at_display' => $document->created_at?->format('n/j/Y g:i A'),
                    'preview_url' => $document->getUrl(),
                    'driver_id' => $driver?->id,
                    'driver_name' => $driver ? trim(($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')) : 'N/A',
                    'carrier_name' => $driver?->carrier?->name ?? 'N/A',
                    'accident_id' => $related?->id,
                    'accident_date_display' => $related?->accident_date?->format('n/j/Y'),
                    'nature_of_accident' => $related?->nature_of_accident,
                    'file_type' => $this->resolveFileType($document->mime_type, $document->file_name),
                ];
            })
        );

        $documentRows = $documentRows->concat(
            $mediaQuery->get()->map(function (Media $media) {
                /** @var DriverAccident|null $related */
                $related = DriverAccident::with(['userDriverDetail.user', 'userDriverDetail.carrier'])->find($media->model_id);
                $driver = $related?->userDriverDetail;

                return [
                    'id' => $media->id,
                    'source' => 'media',
                    'file_name' => $media->file_name,
                    'original_name' => $media->getCustomProperty('original_name') ?: $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'created_at_sort' => $media->created_at?->timestamp ?? 0,
                    'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
                    'preview_url' => $media->getUrl(),
                    'driver_id' => $driver?->id,
                    'driver_name' => $driver ? trim(($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')) : 'N/A',
                    'carrier_name' => $driver?->carrier?->name ?? 'N/A',
                    'accident_id' => $related?->id,
                    'accident_date_display' => $related?->accident_date?->format('n/j/Y'),
                    'nature_of_accident' => $related?->nature_of_accident,
                    'file_type' => $this->resolveFileType($media->mime_type, $media->file_name),
                ];
            })
        );

        if ($fileType) {
            $documentRows = $documentRows->filter(fn ($row) => $row['file_type'] === $fileType)->values();
        }

        $documentRows = $documentRows->sortByDesc('created_at_sort')->values();

        $perPage = 15;
        $currentPage = (int) $request->get('page', 1);
        $pageItems = $documentRows->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pageItems,
            $documentRows->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('admin/accidents/Documents', [
            'documents' => $paginator,
            'drivers' => UserDriverDetail::whereHas('accidents')->with('user')->get()->map(fn ($driver) => [
                'id' => $driver->id,
                'name' => trim(($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')),
            ])->values(),
            'filters' => [
                'driver_id' => (string) $request->get('driver_id', ''),
                'start_date' => (string) $request->get('start_date', ''),
                'end_date' => (string) $request->get('end_date', ''),
                'file_type' => (string) $request->get('file_type', ''),
            ],
            'accident' => $accident ? [
                'id' => $accident->id,
                'nature_of_accident' => $accident->nature_of_accident,
                'accident_date_display' => $accident->accident_date?->format('n/j/Y'),
            ] : null,
        ]);
    }

    protected function storeAttachments(Request $request, DriverAccident $accident): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $accident->addDocument($file, 'accident_documents', [
                'accident_id' => $accident->id,
                'driver_id' => $accident->user_driver_detail_id,
                'uploaded_at' => now()->toDateTimeString(),
            ]);
        }
    }

    protected function accidentPayload(DriverAccident $accident): array
    {
        $accident->loadMissing(['userDriverDetail.user', 'userDriverDetail.carrier']);

        return [
            'id' => $accident->id,
            'user_driver_detail_id' => $accident->user_driver_detail_id,
            'carrier_id' => $accident->userDriverDetail?->carrier_id,
            'driver_name' => trim(($accident->userDriverDetail?->user?->name ?? '') . ' ' . ($accident->userDriverDetail?->last_name ?? '')),
            'accident_date' => $accident->accident_date?->format('n/j/Y'),
            'registration_date' => $accident->created_at?->format('n/j/Y'),
            'nature_of_accident' => $accident->nature_of_accident,
            'had_injuries' => (bool) $accident->had_injuries,
            'number_of_injuries' => (int) ($accident->number_of_injuries ?? 0),
            'had_fatalities' => (bool) $accident->had_fatalities,
            'number_of_fatalities' => (int) ($accident->number_of_fatalities ?? 0),
            'comments' => $accident->comments,
            'documents' => $accident->getDocuments('accident_documents')->map(fn (DocumentAttachment $document) => [
                'id' => $document->id,
                'source' => 'document',
                'name' => $document->original_name ?: $document->file_name,
                'url' => $document->getUrl(),
                'size' => number_format(($document->size ?? 0) / 1024, 1) . ' KB',
            ])->values(),
            'media_files' => $accident->getMedia('accident-images')->map(fn (Media $media) => [
                'id' => $media->id,
                'source' => 'media',
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => number_format(($media->size ?? 0) / 1024, 1) . ' KB',
            ])->values(),
        ];
    }

    protected function carrierOptions()
    {
        return Carrier::query()->orderBy('name')->get(['id', 'name'])->map(fn ($carrier) => [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ])->values();
    }

    protected function driverOptions()
    {
        return UserDriverDetail::query()->with(['user', 'carrier'])->orderByDesc('id')->get()->map(fn ($driver) => [
            'id' => $driver->id,
            'carrier_id' => $driver->carrier_id,
            'carrier_name' => $driver->carrier?->name,
            'name' => trim(($driver->user->name ?? '') . ' ' . ($driver->last_name ?? '')),
            'email' => $driver->user?->email,
        ])->values();
    }

    protected function parseUsDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveFileType(?string $mimeType, string $fileName): string
    {
        $mimeType = strtolower((string) $mimeType);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (str_starts_with($mimeType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return 'image';
        }

        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return 'pdf';
        }

        return 'document';
    }
}
