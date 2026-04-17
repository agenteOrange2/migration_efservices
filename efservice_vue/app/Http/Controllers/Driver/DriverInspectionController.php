<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverInspection;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DriverInspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $driver = $this->resolveDriver();

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', ''),
            'type' => (string) $request->input('type', ''),
        ];

        $query = DriverInspection::query()
            ->where('user_driver_detail_id', $driver->id)
            ->with('vehicle')
            ->orderByDesc('inspection_date')
            ->orderByDesc('id');

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';

            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('inspection_type', 'like', $term)
                    ->orWhere('inspection_level', 'like', $term)
                    ->orWhere('inspector_name', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($term) {
                        $vehicleQuery
                            ->where('company_unit_number', 'like', $term)
                            ->orWhere('make', 'like', $term)
                            ->orWhere('model', 'like', $term)
                            ->orWhere('vin', 'like', $term);
                    });
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['type'] !== '') {
            $query->where('inspection_type', $filters['type']);
        }

        $inspections = $query
            ->paginate(12)
            ->withQueryString();

        $inspections->through(fn (DriverInspection $inspection) => $this->inspectionRow($inspection));

        $statsQuery = DriverInspection::query()->where('user_driver_detail_id', $driver->id);

        return Inertia::render('driver/inspections/Index', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'filters' => $filters,
            'inspections' => $inspections,
            'statuses' => DriverInspection::query()
                ->where('user_driver_detail_id', $driver->id)
                ->distinct()
                ->pluck('status')
                ->filter()
                ->values(),
            'inspectionTypes' => DriverInspection::query()
                ->where('user_driver_detail_id', $driver->id)
                ->distinct()
                ->pluck('inspection_type')
                ->filter()
                ->values(),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'passed' => (clone $statsQuery)->whereIn('status', ['Pass', 'Passed', 'Conditional Pass'])->count(),
                'issues_found' => (clone $statsQuery)->whereNotNull('defects_found')->where('defects_found', '!=', '')->count(),
                'documents' => (clone $statsQuery)->get()->sum(fn (DriverInspection $inspection) => $inspection->getMedia('inspection_documents')->count()),
            ],
        ]);
    }

    public function show(DriverInspection $inspection): Response
    {
        $driver = $this->resolveDriver();
        $this->authorizeInspection($driver, $inspection);

        $inspection->load(['vehicle', 'userDriverDetail.carrier']);

        return Inertia::render('driver/inspections/Show', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'inspection' => [
                'id' => $inspection->id,
                'inspection_date' => $inspection->inspection_date?->format('n/j/Y'),
                'inspection_type' => $inspection->inspection_type,
                'inspection_level' => $inspection->inspection_level,
                'status' => $inspection->status,
                'inspector_name' => $inspection->inspector_name,
                'inspector_number' => $inspection->inspector_number,
                'location' => $inspection->location,
                'defects_found' => $inspection->defects_found,
                'corrective_actions' => $inspection->corrective_actions,
                'is_defects_corrected' => (bool) $inspection->is_defects_corrected,
                'defects_corrected_date' => $inspection->defects_corrected_date?->format('n/j/Y'),
                'corrected_by' => $inspection->corrected_by,
                'is_vehicle_safe_to_operate' => (bool) $inspection->is_vehicle_safe_to_operate,
                'notes' => $inspection->notes,
                'created_at' => $inspection->created_at?->format('n/j/Y g:i A'),
                'vehicle' => $inspection->vehicle ? [
                    'id' => $inspection->vehicle->id,
                    'label' => trim(implode(' ', array_filter([
                        $inspection->vehicle->company_unit_number,
                        $inspection->vehicle->year,
                        $inspection->vehicle->make,
                        $inspection->vehicle->model,
                    ]))),
                    'vin' => $inspection->vehicle->vin,
                ] : null,
                'documents' => $inspection->getMedia('inspection_documents')->map(fn (Media $media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'mime_type' => $media->mime_type,
                    'size_label' => $media->human_readable_size,
                    'created_at' => $media->created_at?->format('n/j/Y g:i A'),
                    'extension' => strtolower((string) pathinfo($media->file_name, PATHINFO_EXTENSION)),
                ])->values(),
            ],
            'recentInspections' => DriverInspection::query()
                ->where('user_driver_detail_id', $driver->id)
                ->whereKeyNot($inspection->id)
                ->with('vehicle')
                ->orderByDesc('inspection_date')
                ->limit(5)
                ->get()
                ->map(fn (DriverInspection $item) => $this->inspectionRow($item))
                ->values(),
        ]);
    }

    protected function resolveDriver(): UserDriverDetail
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        $driver->loadMissing('carrier:id,name');

        return $driver;
    }

    protected function authorizeInspection(UserDriverDetail $driver, DriverInspection $inspection): void
    {
        abort_unless((int) $inspection->user_driver_detail_id === (int) $driver->id, 403, 'Unauthorized inspection record.');
    }

    protected function inspectionRow(DriverInspection $inspection): array
    {
        $inspection->loadMissing('vehicle');

        return [
            'id' => $inspection->id,
            'inspection_date' => $inspection->inspection_date?->format('n/j/Y'),
            'inspection_type' => $inspection->inspection_type ?: 'Vehicle Inspection',
            'inspection_level' => $inspection->inspection_level,
            'status' => $inspection->status,
            'inspector_name' => $inspection->inspector_name,
            'location' => $inspection->location,
            'document_count' => $inspection->getMedia('inspection_documents')->count(),
            'has_issues' => filled($inspection->defects_found),
            'vehicle_label' => $inspection->vehicle
                ? trim(implode(' ', array_filter([
                    $inspection->vehicle->company_unit_number,
                    $inspection->vehicle->year,
                    $inspection->vehicle->make,
                    $inspection->vehicle->model,
                ])))
                : null,
        ];
    }
}
