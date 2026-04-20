<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverTrafficConviction;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TrafficConvictionsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DriverTrafficConviction::query()
            ->with(['userDriverDetail.user', 'userDriverDetail.carrier']);

        if ($request->filled('search_term')) {
            $term = trim((string) $request->search_term);

            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('charge', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%")
                    ->orWhere('penalty', 'like', "%{$term}%")
                    ->orWhereHas('userDriverDetail.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('userDriverDetail.carrier', fn ($carrierQuery) => $carrierQuery->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('driver_filter')) {
            $query->where('user_driver_detail_id', $request->driver_filter);
        }

        if ($request->filled('carrier_filter')) {
            $query->whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $request->carrier_filter));
        }

        if ($request->filled('date_from') && ($dateFrom = $this->parseUsDate($request->date_from))) {
            $query->whereDate('conviction_date', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($request->filled('date_to') && ($dateTo = $this->parseUsDate($request->date_to))) {
            $query->whereDate('conviction_date', '<=', $dateTo->format('Y-m-d'));
        }

        $sortField = in_array($request->get('sort_field'), ['conviction_date', 'created_at', 'charge'], true)
            ? $request->get('sort_field')
            : 'conviction_date';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        $convictions = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        $convictions->getCollection()->transform(function (DriverTrafficConviction $conviction) {
            $driver = $conviction->userDriverDetail;

            return [
                'id' => $conviction->id,
                'created_at' => $conviction->created_at?->format('Y-m-d'),
                'created_at_display' => $conviction->created_at?->format('n/j/Y'),
                'conviction_date' => $conviction->conviction_date?->format('Y-m-d'),
                'conviction_date_display' => $conviction->conviction_date?->format('n/j/Y'),
                'location' => $conviction->location,
                'charge' => $conviction->charge,
                'penalty' => $conviction->penalty,
                'document_count' => $conviction->getMedia('traffic_images')->count(),
                'driver' => $driver ? [
                    'id' => $driver->id,
                    'name' => $this->driverFullName($driver),
                    'email' => $driver->user?->email,
                ] : null,
                'carrier' => $driver?->carrier ? [
                    'id' => $driver->carrier->id,
                    'name' => $driver->carrier->name,
                ] : null,
            ];
        });

        return Inertia::render('admin/traffic/Index', [
            'convictions' => $convictions,
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
        return Inertia::render('admin/traffic/Create', [
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);

        if ((int) $driver->carrier_id !== (int) $validated['carrier_id']) {
            return back()->withErrors([
                'user_driver_detail_id' => 'The selected driver does not belong to the selected carrier.',
            ])->withInput();
        }

        $convictionDate = $this->parseUsDate($validated['conviction_date'] ?? null);
        if (! $convictionDate) {
            return back()->withErrors(['conviction_date' => 'Invalid conviction date format.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $driver, $convictionDate) {
            $conviction = DriverTrafficConviction::create([
                'user_driver_detail_id' => $driver->id,
                'conviction_date' => $convictionDate->format('Y-m-d'),
                'location' => $validated['location'],
                'charge' => $validated['charge'],
                'penalty' => $validated['penalty'],
            ]);

            $this->storeAttachments($request, $conviction);
        });

        return redirect()->route('admin.traffic.index')->with('success', 'Traffic conviction created successfully.');
    }

    public function edit(DriverTrafficConviction $conviction): Response
    {
        $conviction->load(['userDriverDetail.user', 'userDriverDetail.carrier']);

        return Inertia::render('admin/traffic/Edit', [
            'conviction' => $this->convictionPayload($conviction),
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
        ]);
    }

    public function update(Request $request, DriverTrafficConviction $conviction): RedirectResponse
    {
        $validated = $this->validatePayload($request, $conviction->id);

        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);

        if ((int) $driver->carrier_id !== (int) $validated['carrier_id']) {
            return back()->withErrors([
                'user_driver_detail_id' => 'The selected driver does not belong to the selected carrier.',
            ])->withInput();
        }

        $convictionDate = $this->parseUsDate($validated['conviction_date'] ?? null);
        if (! $convictionDate) {
            return back()->withErrors(['conviction_date' => 'Invalid conviction date format.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $driver, $convictionDate, $conviction) {
            $conviction->update([
                'user_driver_detail_id' => $driver->id,
                'conviction_date' => $convictionDate->format('Y-m-d'),
                'location' => $validated['location'],
                'charge' => $validated['charge'],
                'penalty' => $validated['penalty'],
            ]);

            $this->storeAttachments($request, $conviction);
        });

        return redirect()->route('admin.traffic.edit', $conviction)->with('success', 'Traffic conviction updated successfully.');
    }

    public function destroy(DriverTrafficConviction $conviction): RedirectResponse
    {
        $conviction->delete();

        return redirect()->route('admin.traffic.index')->with('success', 'Traffic conviction deleted successfully.');
    }

    public function driverHistory(UserDriverDetail $driver, Request $request): Response
    {
        $query = $driver->trafficConvictions()->with(['userDriverDetail.user', 'userDriverDetail.carrier']);

        if ($request->filled('search_term')) {
            $term = trim((string) $request->search_term);
            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('charge', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%")
                    ->orWhere('penalty', 'like', "%{$term}%");
            });
        }

        if ($request->filled('date_from') && ($dateFrom = $this->parseUsDate($request->date_from))) {
            $query->whereDate('conviction_date', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($request->filled('date_to') && ($dateTo = $this->parseUsDate($request->date_to))) {
            $query->whereDate('conviction_date', '<=', $dateTo->format('Y-m-d'));
        }

        $sortField = in_array($request->get('sort_field'), ['conviction_date', 'created_at', 'charge'], true)
            ? $request->get('sort_field')
            : 'conviction_date';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        $convictions = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        $convictions->getCollection()->transform(function (DriverTrafficConviction $conviction) {
            return [
                'id' => $conviction->id,
                'conviction_date_display' => $conviction->conviction_date?->format('n/j/Y'),
                'location' => $conviction->location,
                'charge' => $conviction->charge,
                'penalty' => $conviction->penalty,
                'document_count' => $conviction->getMedia('traffic_images')->count(),
            ];
        });

        $driver->loadMissing(['user', 'carrier']);

        return Inertia::render('admin/traffic/DriverHistory', [
            'driver' => [
                'id' => $driver->id,
                'name' => $this->driverFullName($driver),
                'carrier_name' => $driver->carrier?->name,
            ],
            'convictions' => $convictions,
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'date_from' => (string) $request->get('date_from', ''),
                'date_to' => (string) $request->get('date_to', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function showDocuments(DriverTrafficConviction $conviction, Request $request): Response
    {
        $conviction->load(['userDriverDetail.user', 'userDriverDetail.carrier']);

        $documents = $conviction->getMedia('traffic_images')
            ->sortByDesc(fn (Media $media) => $media->created_at?->timestamp ?? 0)
            ->values()
            ->map(fn (Media $media) => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'file_type' => $this->resolveFileType((string) $media->mime_type, $media->file_name),
                'size' => (int) $media->size,
                'size_label' => number_format(($media->size ?? 0) / 1024, 1) . ' KB',
                'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
                'preview_url' => $media->getUrl(),
            ]);

        return Inertia::render('admin/traffic/Documents', [
            'conviction' => [
                'id' => $conviction->id,
                'driver_id' => $conviction->user_driver_detail_id,
                'driver_name' => $this->driverFullName($conviction->userDriverDetail),
                'carrier_name' => $conviction->userDriverDetail?->carrier?->name,
                'conviction_date_display' => $conviction->conviction_date?->format('n/j/Y'),
                'location' => $conviction->location,
                'charge' => $conviction->charge,
                'penalty' => $conviction->penalty,
            ],
            'documents' => $documents,
        ]);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless($media->model_type === DriverTrafficConviction::class, 404);

        /** @var DriverTrafficConviction $conviction */
        $conviction = DriverTrafficConviction::findOrFail($media->model_id);
        $conviction->safeDeleteMedia($media->id);

        return back()->with('success', 'Traffic document deleted successfully.');
    }

    protected function validatePayload(Request $request, ?int $convictionId = null): array
    {
        return $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'conviction_date' => [
                'required',
                'string',
                Rule::unique('driver_traffic_convictions')->ignore($convictionId)->where(function ($query) use ($request) {
                    return $query
                        ->where('user_driver_detail_id', $request->input('user_driver_detail_id'))
                        ->where('location', $request->input('location'))
                        ->where('charge', $request->input('charge'));
                }),
            ],
            'location' => 'required|string|max:255',
            'charge' => 'required|string|max:255',
            'penalty' => 'required|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ], [
            'conviction_date.unique' => 'This traffic conviction already exists for this driver.',
            'attachments.*.mimes' => 'Attachments must be JPG, PNG, PDF, DOC or DOCX files.',
        ]);
    }

    protected function storeAttachments(Request $request, DriverTrafficConviction $conviction): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $conviction->addMedia($file)
                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('traffic_images');
        }
    }

    protected function convictionPayload(DriverTrafficConviction $conviction): array
    {
        $conviction->loadMissing(['userDriverDetail.user', 'userDriverDetail.carrier']);

        return [
            'id' => $conviction->id,
            'carrier_id' => $conviction->userDriverDetail?->carrier_id,
            'user_driver_detail_id' => $conviction->user_driver_detail_id,
            'driver_name' => $this->driverFullName($conviction->userDriverDetail),
            'conviction_date' => $conviction->conviction_date?->format('n/j/Y'),
            'location' => $conviction->location,
            'charge' => $conviction->charge,
            'penalty' => $conviction->penalty,
            'documents' => $conviction->getMedia('traffic_images')->map(fn (Media $media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => number_format(($media->size ?? 0) / 1024, 1) . ' KB',
                'mime_type' => $media->mime_type,
            ])->values(),
        ];
    }

    protected function carrierOptions()
    {
        return Carrier::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Carrier $carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ])
            ->values();
    }

    protected function driverOptions()
    {
        return UserDriverDetail::query()
            ->with(['user', 'carrier'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->id,
                'carrier_id' => $driver->carrier_id,
                'carrier_name' => $driver->carrier?->name,
                'name' => $this->driverFullName($driver),
                'email' => $driver->user?->email,
            ])
            ->values();
    }

    protected function driverFullName(?UserDriverDetail $driver): string
    {
        if (! $driver) {
            return 'N/A';
        }

        return trim(implode(' ', array_filter([
            $driver->user?->name,
            $driver->middle_name,
            $driver->last_name,
        ]))) ?: 'N/A';
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

    protected function resolveFileType(string $mimeType, string $fileName): string
    {
        $mimeType = strtolower($mimeType);
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
