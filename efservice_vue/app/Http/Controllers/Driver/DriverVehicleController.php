<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Driver\Concerns\ResolvesDriverVehicleContext;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\EmergencyRepair;
use App\Models\UserDriverDetail;
use Inertia\Inertia;
use Inertia\Response;

class DriverVehicleController extends Controller
{
    use ResolvesDriverVehicleContext;

    public function index(): Response
    {
        $driver = $this->resolveDriver();
        $vehicleIds = $this->accessibleVehicleIds($driver);

        $vehicles = empty($vehicleIds)
            ? collect()
            : Vehicle::query()
                ->with(['carrier:id,name'])
                ->withCount(['documents', 'maintenances', 'emergencyRepairs'])
                ->whereIn('id', $vehicleIds)
                ->orderByDesc('id')
                ->get()
                ->sortBy(fn (Vehicle $vehicle) => array_search((int) $vehicle->id, $vehicleIds, true))
                ->values();

        return Inertia::render('driver/vehicles/Index', [
            'driver' => $this->driverPayload($driver),
            'stats' => [
                'total' => $vehicles->count(),
                'active' => $vehicles->where('status', 'active')->count(),
                'attention_needed' => $vehicles->filter(fn (Vehicle $vehicle) => $this->vehicleNeedsAttention($vehicle))->count(),
                'documents' => $vehicles->sum('documents_count'),
            ],
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle) => $this->vehicleRow($vehicle))->values(),
        ]);
    }

    public function show(Vehicle $vehicle): Response
    {
        $driver = $this->resolveDriver();
        $this->authorizeVehicleAccess($driver, $vehicle);

        $vehicle->load([
            'carrier:id,name',
            'documents.media',
            'currentDriverAssignment.driver.user:id,name,email',
            'currentDriverAssignment.ownerOperatorDetail',
            'currentDriverAssignment.thirdPartyDetail',
        ]);

        $overdueMaintenances = $vehicle->maintenances()
            ->where('status', false)
            ->whereNotNull('next_service_date')
            ->where('next_service_date', '<', now())
            ->orderBy('next_service_date')
            ->limit(5)
            ->get();

        $upcomingMaintenances = $vehicle->maintenances()
            ->where('status', false)
            ->whereNotNull('next_service_date')
            ->where('next_service_date', '>=', now())
            ->orderBy('next_service_date')
            ->limit(5)
            ->get();

        $recentMaintenances = $vehicle->maintenances()
            ->where('status', true)
            ->orderByDesc('service_date')
            ->limit(5)
            ->get();

        $recentRepairs = $vehicle->emergencyRepairs()
            ->orderByDesc('repair_date')
            ->limit(5)
            ->get();

        return Inertia::render('driver/vehicles/Show', [
            'driver' => $this->driverPayload($driver),
            'vehicle' => $this->vehicleDetail($vehicle),
            'documents' => $vehicle->documents
                ->sortBy('expiration_date')
                ->values()
                ->map(fn (VehicleDocument $document) => $this->documentRow($document))
                ->all(),
            'maintenance' => [
                'overdue' => $overdueMaintenances->map(fn ($item) => $this->maintenanceRow($item))->values()->all(),
                'upcoming' => $upcomingMaintenances->map(fn ($item) => $this->maintenanceRow($item))->values()->all(),
                'recent' => $recentMaintenances->map(fn ($item) => $this->maintenanceRow($item))->values()->all(),
                'count' => $vehicle->maintenances()->count(),
            ],
            'repairs' => [
                'recent' => $recentRepairs->map(fn (EmergencyRepair $repair) => $this->repairRow($repair))->values()->all(),
                'count' => $vehicle->emergencyRepairs()->count(),
            ],
        ]);
    }

    protected function driverPayload(UserDriverDetail $driver): array
    {
        $driver->loadMissing('carrier:id,name');

        return [
            'id' => $driver->id,
            'full_name' => $driver->full_name,
            'carrier_name' => $driver->carrier?->name,
        ];
    }

    protected function vehicleRow(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'title' => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'Vehicle',
            'unit_number' => $vehicle->company_unit_number,
            'vin' => $vehicle->vin,
            'type' => $vehicle->type,
            'status' => $vehicle->status,
            'status_label' => str($vehicle->status ?: 'active')->replace('_', ' ')->title()->toString(),
            'carrier_name' => $vehicle->carrier?->name,
            'location' => $vehicle->location,
            'registration_expiration_date' => $vehicle->registration_expiration_date?->format('n/j/Y'),
            'annual_inspection_expiration_date' => $vehicle->annual_inspection_expiration_date?->format('n/j/Y'),
            'documents_count' => (int) $vehicle->documents_count,
            'maintenance_count' => (int) $vehicle->maintenances_count,
            'repair_count' => (int) $vehicle->emergency_repairs_count,
            'needs_attention' => $this->vehicleNeedsAttention($vehicle),
        ];
    }

    protected function vehicleDetail(Vehicle $vehicle): array
    {
        $assignment = $vehicle->currentDriverAssignment;

        return [
            'id' => $vehicle->id,
            'title' => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'Vehicle',
            'carrier_name' => $vehicle->carrier?->name,
            'company_unit_number' => $vehicle->company_unit_number,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'type' => $vehicle->type,
            'vin' => $vehicle->vin,
            'gvwr' => $vehicle->gvwr,
            'fuel_type' => $vehicle->fuel_type,
            'tire_size' => $vehicle->tire_size,
            'location' => $vehicle->location,
            'status' => $vehicle->status,
            'status_label' => str($vehicle->status ?: 'active')->replace('_', ' ')->title()->toString(),
            'driver_type_label' => str($vehicle->driver_type ?: 'company')->replace('_', ' ')->title()->toString(),
            'registration_state' => $vehicle->registration_state,
            'registration_number' => $vehicle->registration_number,
            'registration_expiration_date' => $vehicle->registration_expiration_date?->format('n/j/Y'),
            'annual_inspection_expiration_date' => $vehicle->annual_inspection_expiration_date?->format('n/j/Y'),
            'permanent_tag' => (bool) $vehicle->permanent_tag,
            'irp_apportioned_plate' => (bool) $vehicle->irp_apportioned_plate,
            'notes' => $vehicle->notes,
            'assignment' => $assignment ? [
                'status_label' => str($assignment->status ?: 'active')->replace('_', ' ')->title()->toString(),
                'start_date' => $assignment->start_date?->format('n/j/Y'),
                'driver_name' => $assignment->driver?->full_name,
                'driver_email' => $assignment->driver?->user?->email,
                'owner_operator_name' => $assignment->ownerOperatorDetail?->owner_name,
                'third_party_name' => $assignment->thirdPartyDetail?->third_party_name,
                'notes' => $assignment->notes,
            ] : null,
            'document_stats' => [
                'total' => $vehicle->documents->count(),
                'expired' => $vehicle->documents->filter(fn (VehicleDocument $document) => $document->isExpired())->count(),
                'expiring_soon' => $vehicle->documents->filter(fn (VehicleDocument $document) => $document->isExpiringSoon())->count(),
            ],
        ];
    }

    protected function documentRow(VehicleDocument $document): array
    {
        $media = $document->getFirstMedia('document_files');

        return [
            'id' => $document->id,
            'document_type_label' => $document->document_type_name,
            'document_number' => $document->document_number,
            'status_label' => $document->status_name,
            'expiration_date' => $document->expiration_date?->format('n/j/Y'),
            'is_expired' => $document->isExpired(),
            'is_expiring_soon' => $document->isExpiringSoon(),
            'file_name' => $media?->file_name,
            'preview_url' => $document->preview_url,
            'download_url' => $media?->getUrl(),
            'file_size' => $document->file_size,
            'can_preview' => $document->canPreview(),
        ];
    }

    protected function maintenanceRow($maintenance): array
    {
        return [
            'id' => $maintenance->id,
            'service_tasks' => $maintenance->service_tasks,
            'service_date' => $maintenance->service_date?->format('n/j/Y'),
            'next_service_date' => $maintenance->next_service_date?->format('n/j/Y'),
            'vendor_mechanic' => $maintenance->vendor_mechanic,
            'cost' => $maintenance->cost !== null ? '$' . number_format((float) $maintenance->cost, 2) : null,
            'status_label' => $maintenance->status ? 'Completed' : ($maintenance->isOverdue() ? 'Overdue' : ($maintenance->isUpcoming() ? 'Upcoming' : 'Pending')),
        ];
    }

    protected function repairRow(EmergencyRepair $repair): array
    {
        return [
            'id' => $repair->id,
            'repair_name' => $repair->repair_name,
            'repair_date' => $repair->repair_date?->format('n/j/Y'),
            'cost' => $repair->cost !== null ? '$' . number_format((float) $repair->cost, 2) : null,
            'status_label' => str($repair->status ?: 'pending')->replace('_', ' ')->title()->toString(),
        ];
    }

    protected function vehicleNeedsAttention(Vehicle $vehicle): bool
    {
        return ($vehicle->registration_expiration_date?->isPast() ?? false)
            || ($vehicle->annual_inspection_expiration_date?->isPast() ?? false)
            || in_array($vehicle->status, ['out_of_service', 'suspended'], true);
    }
}
