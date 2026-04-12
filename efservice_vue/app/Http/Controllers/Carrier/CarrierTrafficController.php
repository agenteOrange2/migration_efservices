<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Driver\TrafficConvictionsController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
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

class CarrierTrafficController extends TrafficConvictionsController
{
    use ResolvesCarrierContext;

    public function index(Request $request): Response
    {
        $carrier = $this->resolveCarrier();

        $query = DriverTrafficConviction::query()
            ->with(['userDriverDetail.user', 'userDriverDetail.carrier'])
            ->whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrier->id));

        if ($request->filled('search_term')) {
            $term = trim((string) $request->search_term);

            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('charge', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%")
                    ->orWhere('penalty', 'like', "%{$term}%")
                    ->orWhereHas('userDriverDetail.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('driver_filter')) {
            $query->where('user_driver_detail_id', $request->driver_filter);
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

        return Inertia::render('carrier/traffic/Index', [
            'convictions' => $convictions,
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carriers' => [self::carrierOption($carrier)],
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'carrier_filter' => '',
                'driver_filter' => (string) $request->get('driver_filter', ''),
                'date_from' => (string) $request->get('date_from', ''),
                'date_to' => (string) $request->get('date_to', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create(): Response
    {
        $carrier = $this->resolveCarrier();

        return Inertia::render('carrier/traffic/Create', [
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $carrier = $this->resolveCarrier();
        $validated = $this->validateCarrierPayload($request);

        $driver = $this->findCarrierDriverOrFail((int) $validated['user_driver_detail_id'], (int) $carrier->id);

        if ((int) $validated['carrier_id'] !== (int) $carrier->id) {
            return back()->withErrors([
                'carrier_id' => 'The selected carrier is not valid.',
            ])->withInput();
        }

        $convictionDate = $this->parseUsDate($validated['conviction_date'] ?? null);
        if (! $convictionDate) {
            return back()->withErrors(['conviction_date' => 'Invalid conviction date format.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $driver, $convictionDate) {
            $conviction = DriverTrafficConviction::create([
                'user_driver_detail_id' => $driver->id,
                'carrier_id' => $driver->carrier_id,
                'conviction_date' => $convictionDate->format('Y-m-d'),
                'location' => $validated['location'],
                'charge' => $validated['charge'],
                'penalty' => $validated['penalty'],
            ]);

            $this->storeAttachments($request, $conviction);
        });

        return redirect()->route('carrier.drivers.traffic.index')->with('success', 'Traffic conviction created successfully.');
    }

    public function edit(DriverTrafficConviction $conviction): Response
    {
        $this->authorizeCarrierConviction($conviction);
        $carrier = $this->resolveCarrier();
        $conviction->load(['userDriverDetail.user', 'userDriverDetail.carrier']);

        return Inertia::render('carrier/traffic/Edit', [
            'conviction' => $this->convictionPayload($conviction),
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function update(Request $request, DriverTrafficConviction $conviction): RedirectResponse
    {
        $this->authorizeCarrierConviction($conviction);
        $carrier = $this->resolveCarrier();
        $validated = $this->validateCarrierPayload($request, $conviction->id);

        $driver = $this->findCarrierDriverOrFail((int) $validated['user_driver_detail_id'], (int) $carrier->id);

        if ((int) $validated['carrier_id'] !== (int) $carrier->id) {
            return back()->withErrors([
                'carrier_id' => 'The selected carrier is not valid.',
            ])->withInput();
        }

        $convictionDate = $this->parseUsDate($validated['conviction_date'] ?? null);
        if (! $convictionDate) {
            return back()->withErrors(['conviction_date' => 'Invalid conviction date format.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $driver, $convictionDate, $conviction) {
            $conviction->update([
                'user_driver_detail_id' => $driver->id,
                'carrier_id' => $driver->carrier_id,
                'conviction_date' => $convictionDate->format('Y-m-d'),
                'location' => $validated['location'],
                'charge' => $validated['charge'],
                'penalty' => $validated['penalty'],
            ]);

            $this->storeAttachments($request, $conviction);
        });

        return redirect()->route('carrier.drivers.traffic.edit', $conviction)->with('success', 'Traffic conviction updated successfully.');
    }

    public function destroy(DriverTrafficConviction $conviction): RedirectResponse
    {
        $this->authorizeCarrierConviction($conviction);
        $conviction->delete();

        return redirect()->route('carrier.drivers.traffic.index')->with('success', 'Traffic conviction deleted successfully.');
    }

    public function driverHistory(UserDriverDetail $driver, Request $request): Response
    {
        $this->authorizeCarrierDriver($driver);

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

        return Inertia::render('carrier/traffic/DriverHistory', [
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
            'carrier' => self::carrierOption($this->resolveCarrier()),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function showDocuments(DriverTrafficConviction $conviction, Request $request): Response
    {
        $this->authorizeCarrierConviction($conviction);
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

        return Inertia::render('carrier/traffic/Documents', [
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
            'carrier' => self::carrierOption($this->resolveCarrier()),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function destroyDocument(Media $media): RedirectResponse
    {
        $this->destroyMedia($media);

        return back()->with('success', 'Traffic document deleted successfully.');
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless($media->model_type === DriverTrafficConviction::class, 404);

        /** @var DriverTrafficConviction $conviction */
        $conviction = DriverTrafficConviction::findOrFail($media->model_id);
        $this->authorizeCarrierConviction($conviction);
        $conviction->safeDeleteMedia($media->id);

        return back()->with('success', 'Traffic document deleted successfully.');
    }

    protected function validateCarrierPayload(Request $request, ?int $convictionId = null): array
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

    protected function authorizeCarrierConviction(DriverTrafficConviction $conviction): void
    {
        $conviction->loadMissing('userDriverDetail');
        abort_unless((int) $conviction->userDriverDetail?->carrier_id === (int) $this->resolveCarrierId(), 403);
    }

    protected function authorizeCarrierDriver(UserDriverDetail $driver): void
    {
        abort_unless((int) $driver->carrier_id === (int) $this->resolveCarrierId(), 403);
    }

    protected function findCarrierDriverOrFail(int $driverId, int $carrierId): UserDriverDetail
    {
        return UserDriverDetail::query()
            ->where('id', $driverId)
            ->where('carrier_id', $carrierId)
            ->firstOrFail();
    }

    protected function carrierDriverOptions(int $carrierId)
    {
        return UserDriverDetail::query()
            ->with(['user', 'carrier'])
            ->where('carrier_id', $carrierId)
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

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.drivers.traffic.index',
            'create' => 'carrier.drivers.traffic.create',
            'store' => 'carrier.drivers.traffic.store',
            'edit' => 'carrier.drivers.traffic.edit',
            'update' => 'carrier.drivers.traffic.update',
            'destroy' => 'carrier.drivers.traffic.destroy',
            'driverHistory' => 'carrier.drivers.traffic.driver.history',
            'documentsShow' => 'carrier.drivers.traffic.documents',
            'mediaDestroy' => 'carrier.drivers.traffic.documents.delete',
            'driverShow' => 'carrier.drivers.show',
        ];
    }

    protected static function carrierOption(Carrier $carrier): array
    {
        return [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ];
    }
}
