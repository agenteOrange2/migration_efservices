<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Helpers\Constants;
use App\Http\Controllers\Admin\Vehicles\Concerns\UsesVehicleAdminHelpers;
use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\Admin\Vehicle\VehicleMake;
use App\Models\Admin\Vehicle\VehicleType;
use App\Models\CompanyDriverDetail;
use App\Models\EmergencyRepair;
use App\Models\OwnerOperatorDetail;
use App\Models\ThirdPartyDetail;
use App\Models\UserDriverDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    use UsesVehicleAdminHelpers;

    public function index(Request $request): Response
    {
        $carrierId = $this->resolvedCarrierId($request);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->isSuperadmin() ? (string) $request->input('carrier_id', '') : (string) ($carrierId ?? ''),
            'status' => (string) $request->input('status', ''),
            'driver_type' => (string) $request->input('driver_type', ''),
            'vehicle_type' => (string) $request->input('vehicle_type', ''),
            'vehicle_make' => (string) $request->input('vehicle_make', ''),
            'sort_field' => (string) $request->input('sort_field', 'created_at'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $query = Vehicle::query()
            ->with([
                'carrier:id,name',
                'currentDriverAssignment.driver.user:id,name,email',
                'currentDriverAssignment.ownerOperatorDetail',
                'currentDriverAssignment.thirdPartyDetail',
            ])
            ->withCount([
                'documents',
                'documents as expiring_documents_count' => fn (Builder $builder) => $builder
                    ->whereNotNull('expiration_date')
                    ->whereDate('expiration_date', '>=', now()->toDateString())
                    ->whereDate('expiration_date', '<=', now()->addDays(30)->toDateString()),
                'driverAssignments',
            ]);

        $this->applyCarrierScope($query, $carrierId);

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('company_unit_number', 'like', $term)
                    ->orWhere('make', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhere('type', 'like', $term)
                    ->orWhere('vin', 'like', $term)
                    ->orWhere('registration_number', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhereHas('carrier', fn (Builder $carrierQuery) => $carrierQuery->where('name', 'like', $term));
            });
        }

        if ($filters['status'] !== '') {
            match ($filters['status']) {
                'out_of_service' => $query->where(function (Builder $builder) {
                    $builder->where('status', 'out_of_service')->orWhere('out_of_service', true);
                }),
                'suspended' => $query->where(function (Builder $builder) {
                    $builder->where('status', 'suspended')->orWhere('suspended', true);
                }),
                default => $query->where('status', $filters['status']),
            };
        }

        if ($filters['driver_type'] !== '') {
            $query->where('driver_type', $filters['driver_type']);
        }

        if ($filters['vehicle_type'] !== '') {
            $query->where('type', $filters['vehicle_type']);
        }

        if ($filters['vehicle_make'] !== '') {
            $query->where('make', $filters['vehicle_make']);
        }

        $allowedSorts = ['created_at', 'year', 'company_unit_number', 'make', 'model', 'type'];
        $sortField = in_array($filters['sort_field'], $allowedSorts, true) ? $filters['sort_field'] : 'created_at';
        $sortDirection = $filters['sort_direction'] === 'asc' ? 'asc' : 'desc';

        $vehicles = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(12)
            ->withQueryString();

        $vehicles->through(fn (Vehicle $vehicle) => $this->vehicleRow($vehicle));

        $statsQuery = Vehicle::query();
        $this->applyCarrierScope($statsQuery, $carrierId);

        return Inertia::render('admin/vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $filters,
            'carriers' => $this->carrierOptions(),
            'vehicleMakes' => $this->makeOptions(),
            'vehicleTypes' => $this->typeOptions(),
            'driverTypes' => $this->driverTypeOptions(),
            'statusOptions' => $this->vehicleStatusOptions(),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'active' => (clone $statsQuery)->where('status', 'active')->count(),
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                'out_of_service' => (clone $statsQuery)->where(function (Builder $builder) {
                    $builder->where('status', 'out_of_service')->orWhere('out_of_service', true);
                })->count(),
                'suspended' => (clone $statsQuery)->where(function (Builder $builder) {
                    $builder->where('status', 'suspended')->orWhere('suspended', true);
                })->count(),
                'unassigned' => (clone $statsQuery)->whereNull('user_driver_detail_id')->count(),
            ],
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function create(Request $request): Response
    {
        $carrierId = $this->resolvedCarrierId($request);

        return Inertia::render('admin/vehicles/Create', [
            'vehicle' => null,
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions($carrierId),
            'vehicleMakes' => $this->makeOptions(),
            'vehicleTypes' => $this->typeOptions(),
            'driverTypes' => $this->driverTypeOptions(),
            'fuelTypes' => $this->fuelTypeOptions(),
            'statusOptions' => $this->vehicleStatusOptions(),
            'states' => Constants::usStates(),
            'isSuperadmin' => $this->isSuperadmin(),
            'selectedCarrierId' => $carrierId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $this->ensureCarrierAccess((int) $validated['carrier_id']);
        $this->ensureDriverBelongsToCarrier($validated);

        $vehicle = null;

        DB::transaction(function () use ($request, $validated, &$vehicle) {
            $vehicle = Vehicle::create($this->vehiclePayload($request, $validated));
            $this->syncCatalogs($vehicle);
            $this->syncAssignment($vehicle, $request, $validated);
        });

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('success', 'Vehicle created successfully.');
    }

    public function show(Vehicle $vehicle): Response
    {
        $this->authorizeVehicle($vehicle);

        $vehicle->load([
            'carrier:id,name',
            'documents.media',
            'currentDriverAssignment.driver.user:id,name,email',
            'currentDriverAssignment.driver.carrier:id,name',
            'currentDriverAssignment.ownerOperatorDetail',
            'currentDriverAssignment.thirdPartyDetail',
            'currentDriverAssignment.companyDriverDetail.carrier:id,name',
        ]);

        $assignmentPreview = $vehicle->driverAssignments()
            ->with([
                'driver.user:id,name,email',
                'driver.carrier:id,name',
                'ownerOperatorDetail',
                'thirdPartyDetail',
                'companyDriverDetail.carrier:id,name',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (VehicleDriverAssignment $assignment) => $this->assignmentPayload($assignment));

        $recentMaintenances = $vehicle->maintenances()
            ->latest('service_date')
            ->limit(5)
            ->get()
            ->map(fn ($maintenance) => [
                'id' => $maintenance->id,
                'service_date' => $this->formatDateForUi($maintenance->service_date),
                'next_service_date' => $this->formatDateForUi($maintenance->next_service_date),
                'service_tasks' => $maintenance->service_tasks,
                'vendor_mechanic' => $maintenance->vendor_mechanic,
                'cost' => $maintenance->cost !== null ? '$' . number_format((float) $maintenance->cost, 2) : null,
                'odometer' => $maintenance->odometer,
                'status' => $maintenance->status ? 'Completed' : ($maintenance->isOverdue() ? 'Overdue' : ($maintenance->isUpcoming() ? 'Upcoming' : 'Pending')),
            ]);

        $recentRepairs = $vehicle->emergencyRepairs()
            ->latest('repair_date')
            ->limit(5)
            ->get()
            ->map(fn (EmergencyRepair $repair) => [
                'id' => $repair->id,
                'repair_name' => $repair->repair_name,
                'repair_date' => $this->formatDateForUi($repair->repair_date),
                'cost' => $repair->cost !== null ? '$' . number_format((float) $repair->cost, 2) : null,
                'status' => str($repair->status ?: 'pending')->replace('_', ' ')->title()->toString(),
                'odometer' => $repair->odometer,
            ]);

        return Inertia::render('admin/vehicles/Show', [
            'vehicle' => [
                'id' => $vehicle->id,
                'title' => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'Vehicle',
                'carrier' => $vehicle->carrier ? [
                    'id' => $vehicle->carrier->id,
                    'name' => $vehicle->carrier->name,
                ] : null,
                'company_unit_number' => $vehicle->company_unit_number,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'type' => $vehicle->type,
                'year' => $vehicle->year,
                'vin' => $vehicle->vin,
                'gvwr' => $vehicle->gvwr,
                'tire_size' => $vehicle->tire_size,
                'fuel_type' => $vehicle->fuel_type,
                'location' => $vehicle->location,
                'driver_type' => $vehicle->driver_type,
                'driver_type_label' => $this->titleizeDriverType($vehicle->driver_type),
                'status' => $vehicle->status,
                'status_label' => $this->vehicleStatusOptions()[$vehicle->status] ?? Str::headline((string) $vehicle->status),
                'status_date' => $vehicle->status === 'out_of_service'
                    ? $this->formatDateForUi($vehicle->out_of_service_date)
                    : ($vehicle->status === 'suspended' ? $this->formatDateForUi($vehicle->suspended_date) : null),
                'registration_state' => $vehicle->registration_state,
                'registration_number' => $vehicle->registration_number,
                'registration_expiration_date' => $this->formatDateForUi($vehicle->registration_expiration_date),
                'annual_inspection_expiration_date' => $this->formatDateForUi($vehicle->annual_inspection_expiration_date),
                'permanent_tag' => (bool) $vehicle->permanent_tag,
                'irp_apportioned_plate' => (bool) $vehicle->irp_apportioned_plate,
                'notes' => $vehicle->notes,
                'registration_is_expired' => $vehicle->registration_expiration_date?->isPast() ?? false,
                'inspection_is_expired' => $vehicle->annual_inspection_expiration_date?->isPast() ?? false,
                'documents' => $vehicle->documents
                    ->sortBy('expiration_date')
                    ->take(5)
                    ->values()
                    ->map(fn ($document) => $this->documentPreviewPayload($document))
                    ->all(),
                'document_stats' => [
                    'total' => $vehicle->documents->count(),
                    'active' => $vehicle->documents->where('status', 'active')->count(),
                    'expired' => $vehicle->documents->filter(fn ($document) => $document->status === 'expired' || ($document->expiration_date?->isPast() ?? false))->count(),
                    'pending' => $vehicle->documents->where('status', 'pending')->count(),
                ],
                'current_assignment' => $vehicle->currentDriverAssignment ? $this->assignmentPayload($vehicle->currentDriverAssignment) : null,
                'assignment_preview' => $assignmentPreview,
                'maintenance_count' => $vehicle->maintenances()->count(),
                'repair_count' => $vehicle->emergencyRepairs()->count(),
            ],
            'recentMaintenances' => $recentMaintenances,
            'recentRepairs' => $recentRepairs,
        ]);
    }

    public function edit(Vehicle $vehicle): Response
    {
        $this->authorizeVehicle($vehicle);

        $vehicle->load(['carrier:id,name']);

        $assignment = $vehicle->currentDriverAssignment()
            ->with([
                'driver.user:id,name,email',
                'ownerOperatorDetail',
                'thirdPartyDetail',
                'companyDriverDetail',
            ])
            ->first() ?? $vehicle->driverAssignments()
            ->with([
                'driver.user:id,name,email',
                'ownerOperatorDetail',
                'thirdPartyDetail',
                'companyDriverDetail',
            ])
            ->latest('created_at')
            ->first();

        return Inertia::render('admin/vehicles/Edit', [
            'vehicle' => $this->vehicleFormPayload($vehicle, $assignment),
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions($vehicle->carrier_id),
            'vehicleMakes' => $this->makeOptions(),
            'vehicleTypes' => $this->typeOptions(),
            'driverTypes' => $this->driverTypeOptions(),
            'fuelTypes' => $this->fuelTypeOptions(),
            'statusOptions' => $this->vehicleStatusOptions(),
            'states' => Constants::usStates(),
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        $validated = $this->validatePayload($request, $vehicle);
        $this->ensureCarrierAccess((int) $validated['carrier_id']);
        $this->ensureDriverBelongsToCarrier($validated);

        DB::transaction(function () use ($request, $validated, $vehicle) {
            $vehicle->update($this->vehiclePayload($request, $validated));
            $this->syncCatalogs($vehicle);
            $this->syncAssignment($vehicle, $request, $validated);
        });

        return redirect()
            ->route('admin.vehicles.show', $vehicle)
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        DB::transaction(function () use ($vehicle) {
            $vehicle->driverAssignments()->each(function (VehicleDriverAssignment $assignment) {
                $assignment->ownerOperatorDetail()?->delete();
                $assignment->thirdPartyDetail()?->delete();
                $assignment->companyDriverDetail()?->delete();
            });

            $vehicle->delete();
        });

        return redirect()
            ->route('admin.vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    public function driverAssignmentHistory(Vehicle $vehicle, Request $request): Response
    {
        $this->authorizeVehicle($vehicle);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => (string) $request->input('status', ''),
        ];

        $query = $vehicle->driverAssignments()
            ->with([
                'driver.user:id,name,email',
                'driver.carrier:id,name',
                'ownerOperatorDetail',
                'thirdPartyDetail',
                'companyDriverDetail.carrier:id,name',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('notes', 'like', $term)
                    ->orWhereHas('driver.user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $term))
                    ->orWhereHas('ownerOperatorDetail', fn (Builder $ownerQuery) => $ownerQuery->where('owner_name', 'like', $term))
                    ->orWhereHas('thirdPartyDetail', fn (Builder $thirdPartyQuery) => $thirdPartyQuery
                        ->where('third_party_name', 'like', $term)
                        ->orWhere('third_party_contact', 'like', $term));
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $assignments = $query->paginate(12)->withQueryString();
        $assignments->through(fn (VehicleDriverAssignment $assignment) => $this->assignmentPayload($assignment));

        return Inertia::render('admin/vehicles/AssignmentHistory', [
            'vehicle' => [
                'id' => $vehicle->id,
                'title' => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'Vehicle',
                'company_unit_number' => $vehicle->company_unit_number,
                'vin' => $vehicle->vin,
                'carrier_name' => $vehicle->carrier?->name,
            ],
            'assignments' => $assignments,
            'filters' => $filters,
            'statusOptions' => [
                'active' => 'Active',
                'pending' => 'Pending',
                'inactive' => 'Inactive',
            ],
        ]);
    }

    public function unassigned(Request $request): Response
    {
        $carrierId = $this->resolvedCarrierId($request);

        $query = Vehicle::query()
            ->with(['carrier:id,name'])
            ->whereNull('user_driver_detail_id')
            ->orderByDesc('created_at');

        $this->applyCarrierScope($query, $carrierId);

        if ($request->filled('search')) {
            $term = '%' . trim((string) $request->input('search')) . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('company_unit_number', 'like', $term)
                    ->orWhere('make', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhere('vin', 'like', $term);
            });
        }

        $vehicles = $query->paginate(12)->withQueryString();
        $vehicles->through(fn (Vehicle $vehicle) => [
            'id' => $vehicle->id,
            'title' => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'Vehicle',
            'company_unit_number' => $vehicle->company_unit_number,
            'vin' => $vehicle->vin,
            'carrier_name' => $vehicle->carrier?->name,
            'status' => $vehicle->status,
            'status_label' => $this->vehicleStatusOptions()[$vehicle->status] ?? Str::headline((string) $vehicle->status),
            'driver_type_label' => $this->titleizeDriverType($vehicle->driver_type),
            'created_at' => $vehicle->created_at?->format('n/j/Y'),
        ]);

        return Inertia::render('admin/vehicles/Unassigned', [
            'vehicles' => $vehicles,
            'filters' => [
                'search' => (string) $request->input('search', ''),
            ],
        ]);
    }

    protected function validatePayload(Request $request, ?Vehicle $vehicle = null): array
    {
        $rules = [
            'carrier_id' => ['required', 'exists:carriers,id'],
            'make' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'company_unit_number' => ['nullable', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1900', 'max:' . (now()->year + 1)],
            'vin' => ['required', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/i', 'unique:vehicles,vin' . ($vehicle ? ',' . $vehicle->id : '')],
            'gvwr' => ['nullable', 'string', 'max:255'],
            'registration_state' => ['required', 'string', 'max:10'],
            'registration_number' => ['required', 'string', 'max:255'],
            'registration_expiration_date' => ['nullable', 'string', 'max:20'],
            'annual_inspection_expiration_date' => ['nullable', 'string', 'max:20'],
            'permanent_tag' => ['nullable', 'boolean'],
            'tire_size' => ['nullable', 'string', 'max:255'],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'irp_apportioned_plate' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,pending,inactive,suspended,out_of_service'],
            'status_effective_date' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'driver_type' => ['nullable', 'in:company,owner_operator,third_party'],
            'user_driver_detail_id' => ['nullable', 'exists:user_driver_details,id'],
            'assignment_start_date' => ['nullable', 'string', 'max:20'],
            'assignment_end_date' => ['nullable', 'string', 'max:20'],
            'assignment_status' => ['nullable', 'in:active,pending,inactive'],
            'assignment_notes' => ['nullable', 'string', 'max:2000'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:50'],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'contract_agreed' => ['nullable', 'boolean'],
            'third_party_name' => ['nullable', 'string', 'max:255'],
            'third_party_phone' => ['nullable', 'string', 'max:50'],
            'third_party_email' => ['nullable', 'email', 'max:255'],
            'third_party_dba' => ['nullable', 'string', 'max:255'],
            'third_party_address' => ['nullable', 'string', 'max:255'],
            'third_party_contact' => ['nullable', 'string', 'max:255'],
            'third_party_fein' => ['nullable', 'string', 'max:50'],
            'third_party_email_sent' => ['nullable', 'boolean'],
        ];

        $validator = Validator::make($request->all(), $rules, [
            'vin.regex' => 'VIN must be 17 characters and cannot contain I, O, or Q.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $driverType = $request->input('driver_type');

            if ($driverType === 'owner_operator' && trim((string) $request->input('owner_name')) === '') {
                $validator->errors()->add('owner_name', 'Owner operator name is required.');
            }

            if ($driverType === 'third_party' && trim((string) $request->input('third_party_name')) === '') {
                $validator->errors()->add('third_party_name', 'Third party company name is required.');
            }

            foreach ([
                'registration_expiration_date',
                'annual_inspection_expiration_date',
                'status_effective_date',
                'assignment_start_date',
                'assignment_end_date',
            ] as $field) {
                $value = $request->input($field);

                if ($value !== null && $value !== '' && ! $this->parseUsDate((string) $value)) {
                    $validator->errors()->add($field, 'Invalid date format. Use M/D/YYYY.');
                }
            }

            $assignmentStart = $this->parseUsDate($request->input('assignment_start_date'));
            $assignmentEnd = $this->parseUsDate($request->input('assignment_end_date'));

            if ($assignmentStart && $assignmentEnd && $assignmentEnd->lt($assignmentStart)) {
                $validator->errors()->add('assignment_end_date', 'Assignment end date must be after the assignment start date.');
            }
        });

        return $validator->validate();
    }

    protected function vehiclePayload(Request $request, array $validated): array
    {
        $status = $validated['status'];
        $statusDate = $this->parseUsDate($validated['status_effective_date'] ?? null);

        return [
            'carrier_id' => (int) $validated['carrier_id'],
            'make' => trim($validated['make']),
            'model' => trim($validated['model']),
            'type' => trim($validated['type']),
            'company_unit_number' => $this->emptyToNull($validated['company_unit_number'] ?? null),
            'year' => (int) $validated['year'],
            'vin' => Str::upper(trim($validated['vin'])),
            'gvwr' => $this->emptyToNull($validated['gvwr'] ?? null),
            'registration_state' => trim($validated['registration_state']),
            'registration_number' => trim($validated['registration_number']),
            'registration_expiration_date' => $this->parseUsDate($validated['registration_expiration_date'] ?? null)?->format('Y-m-d'),
            'permanent_tag' => $request->boolean('permanent_tag'),
            'tire_size' => $this->emptyToNull($validated['tire_size'] ?? null),
            'fuel_type' => $this->emptyToNull($validated['fuel_type'] ?? null),
            'irp_apportioned_plate' => $request->boolean('irp_apportioned_plate'),
            'driver_type' => $validated['driver_type'] ?: null,
            'location' => $this->emptyToNull($validated['location'] ?? null),
            'user_driver_detail_id' => $request->filled('user_driver_detail_id') ? (int) $validated['user_driver_detail_id'] : null,
            'annual_inspection_expiration_date' => $this->parseUsDate($validated['annual_inspection_expiration_date'] ?? null)?->format('Y-m-d'),
            'out_of_service' => $status === 'out_of_service',
            'out_of_service_date' => $status === 'out_of_service' ? ($statusDate?->format('Y-m-d') ?? now()->toDateString()) : null,
            'suspended' => $status === 'suspended',
            'suspended_date' => $status === 'suspended' ? ($statusDate?->format('Y-m-d') ?? now()->toDateString()) : null,
            'status' => $status,
            'notes' => $this->emptyToNull($validated['notes'] ?? null),
        ];
    }

    protected function syncCatalogs(Vehicle $vehicle): void
    {
        if ($vehicle->make) {
            VehicleMake::firstOrCreate(['name' => $vehicle->make]);
        }

        if ($vehicle->type) {
            VehicleType::firstOrCreate(['name' => $vehicle->type]);
        }
    }

    protected function syncAssignment(Vehicle $vehicle, Request $request, array $validated): void
    {
        $driverType = $validated['driver_type'] ?: null;
        $driverId = $request->filled('user_driver_detail_id') ? (int) $validated['user_driver_detail_id'] : null;

        $currentAssignment = $vehicle->driverAssignments()
            ->whereIn('status', ['active', 'pending'])
            ->latest('created_at')
            ->first();

        if (! $driverType) {
            if ($currentAssignment) {
                $currentAssignment->update([
                    'status' => 'inactive',
                    'end_date' => $currentAssignment->end_date ?: now()->toDateString(),
                ]);
            }

            $vehicle->update([
                'driver_type' => null,
                'user_driver_detail_id' => null,
            ]);

            return;
        }

        $assignmentStart = $this->parseUsDate($validated['assignment_start_date'] ?? null)?->format('Y-m-d') ?? now()->toDateString();
        $assignmentEnd = $this->parseUsDate($validated['assignment_end_date'] ?? null)?->format('Y-m-d');
        $assignmentStatus = $validated['assignment_status'] ?: 'active';

        $shouldCreateNewAssignment = ! $currentAssignment
            || $currentAssignment->driver_type !== $driverType
            || (int) ($currentAssignment->user_driver_detail_id ?? 0) !== (int) ($driverId ?? 0);

        if ($shouldCreateNewAssignment && $currentAssignment) {
            $currentAssignment->update([
                'status' => 'inactive',
                'end_date' => $currentAssignment->end_date ?: now()->toDateString(),
            ]);
        }

        $assignment = $shouldCreateNewAssignment
            ? $vehicle->driverAssignments()->create([
                'vehicle_id' => $vehicle->id,
                'user_driver_detail_id' => $driverId,
                'driver_type' => $driverType,
                'start_date' => $assignmentStart,
                'end_date' => $assignmentEnd,
                'status' => $assignmentStatus,
                'notes' => $this->emptyToNull($validated['assignment_notes'] ?? null),
                'assigned_by' => auth()->id(),
            ])
            : tap($currentAssignment)->update([
                'user_driver_detail_id' => $driverId,
                'driver_type' => $driverType,
                'start_date' => $assignmentStart,
                'end_date' => $assignmentEnd,
                'status' => $assignmentStatus,
                'notes' => $this->emptyToNull($validated['assignment_notes'] ?? null),
                'assigned_by' => auth()->id(),
            ]);

        $assignment = $assignment instanceof VehicleDriverAssignment ? $assignment : $currentAssignment->fresh();

        $this->syncAssignmentDetails($assignment, $request, $validated);

        $vehicle->update([
            'driver_type' => $driverType,
            'user_driver_detail_id' => $driverId,
        ]);
    }

    protected function syncAssignmentDetails(VehicleDriverAssignment $assignment, Request $request, array $validated): void
    {
        $assignment->loadMissing(['ownerOperatorDetail', 'thirdPartyDetail', 'companyDriverDetail']);

        if ($assignment->driver_type === 'owner_operator') {
            OwnerOperatorDetail::updateOrCreate(
                ['vehicle_driver_assignment_id' => $assignment->id],
                [
                    'owner_name' => trim((string) ($validated['owner_name'] ?? '')),
                    'owner_phone' => $this->emptyToNull($validated['owner_phone'] ?? null),
                    'owner_email' => $this->emptyToNull($validated['owner_email'] ?? null),
                    'contract_agreed' => $request->boolean('contract_agreed'),
                    'notes' => $this->emptyToNull($validated['assignment_notes'] ?? null),
                ]
            );

            $assignment->thirdPartyDetail()?->delete();
            $assignment->companyDriverDetail()?->delete();

            return;
        }

        if ($assignment->driver_type === 'third_party') {
            ThirdPartyDetail::updateOrCreate(
                ['vehicle_driver_assignment_id' => $assignment->id],
                [
                    'third_party_name' => trim((string) ($validated['third_party_name'] ?? '')),
                    'third_party_phone' => $this->emptyToNull($validated['third_party_phone'] ?? null),
                    'third_party_email' => $this->emptyToNull($validated['third_party_email'] ?? null),
                    'third_party_dba' => $this->emptyToNull($validated['third_party_dba'] ?? null),
                    'third_party_address' => $this->emptyToNull($validated['third_party_address'] ?? null),
                    'third_party_contact' => $this->emptyToNull($validated['third_party_contact'] ?? null),
                    'third_party_fein' => $this->emptyToNull($validated['third_party_fein'] ?? null),
                    'email_sent' => $request->boolean('third_party_email_sent'),
                    'notes' => $this->emptyToNull($validated['assignment_notes'] ?? null),
                ]
            );

            $assignment->ownerOperatorDetail()?->delete();
            $assignment->companyDriverDetail()?->delete();

            return;
        }

        CompanyDriverDetail::updateOrCreate(
            ['vehicle_driver_assignment_id' => $assignment->id],
            [
                'carrier_id' => $assignment->vehicle?->carrier_id,
                'notes' => $this->emptyToNull($validated['assignment_notes'] ?? null),
            ]
        );

        $assignment->ownerOperatorDetail()?->delete();
        $assignment->thirdPartyDetail()?->delete();
    }

    protected function vehicleRow(Vehicle $vehicle): array
    {
        $assignment = $vehicle->currentDriverAssignment;

        return [
            'id' => $vehicle->id,
            'year' => $vehicle->year,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'type' => $vehicle->type,
            'company_unit_number' => $vehicle->company_unit_number,
            'vin' => $vehicle->vin,
            'carrier' => $vehicle->carrier ? [
                'id' => $vehicle->carrier->id,
                'name' => $vehicle->carrier->name,
            ] : null,
            'driver_type' => $vehicle->driver_type,
            'driver_type_label' => $this->titleizeDriverType($vehicle->driver_type),
            'status' => $vehicle->status,
            'status_label' => $this->vehicleStatusOptions()[$vehicle->status] ?? Str::headline((string) $vehicle->status),
            'registration_expiration_date' => $this->formatDateForUi($vehicle->registration_expiration_date),
            'annual_inspection_expiration_date' => $this->formatDateForUi($vehicle->annual_inspection_expiration_date),
            'document_count' => (int) ($vehicle->documents_count ?? 0),
            'expiring_documents_count' => (int) ($vehicle->expiring_documents_count ?? 0),
            'assignment_count' => (int) ($vehicle->driver_assignments_count ?? 0),
            'current_assignment' => $assignment ? [
                'type_label' => $this->titleizeDriverType($assignment->driver_type),
                'name' => $assignment->driver
                    ? $this->driverFullName($assignment->driver)
                    : ($assignment->ownerOperatorDetail?->owner_name ?: $assignment->thirdPartyDetail?->third_party_name),
                'secondary' => $assignment->driver?->user?->email
                    ?: $assignment->thirdPartyDetail?->third_party_email
                    ?: $assignment->ownerOperatorDetail?->owner_email,
                'status' => $assignment->status,
            ] : null,
            'created_at' => $vehicle->created_at?->format('n/j/Y'),
        ];
    }

    protected function assignmentPayload(VehicleDriverAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'driver_type' => $assignment->driver_type,
            'driver_type_label' => $this->titleizeDriverType($assignment->driver_type),
            'status' => $assignment->status,
            'status_label' => Str::headline($assignment->status),
            'start_date' => $this->formatDateForUi($assignment->start_date),
            'end_date' => $this->formatDateForUi($assignment->end_date),
            'notes' => $assignment->notes,
            'driver' => $assignment->driver ? [
                'id' => $assignment->driver->id,
                'name' => $this->driverFullName($assignment->driver),
                'email' => $assignment->driver->user?->email,
                'carrier_name' => $assignment->driver->carrier?->name,
            ] : null,
            'owner_operator' => $assignment->ownerOperatorDetail ? [
                'name' => $assignment->ownerOperatorDetail->owner_name,
                'phone' => $assignment->ownerOperatorDetail->owner_phone,
                'email' => $assignment->ownerOperatorDetail->owner_email,
                'contract_agreed' => (bool) $assignment->ownerOperatorDetail->contract_agreed,
            ] : null,
            'third_party' => $assignment->thirdPartyDetail ? [
                'name' => $assignment->thirdPartyDetail->third_party_name,
                'phone' => $assignment->thirdPartyDetail->third_party_phone,
                'email' => $assignment->thirdPartyDetail->third_party_email,
                'dba' => $assignment->thirdPartyDetail->third_party_dba,
                'address' => $assignment->thirdPartyDetail->third_party_address,
                'contact' => $assignment->thirdPartyDetail->third_party_contact,
                'fein' => $assignment->thirdPartyDetail->third_party_fein,
                'email_sent' => (bool) $assignment->thirdPartyDetail->email_sent,
            ] : null,
            'company' => $assignment->companyDriverDetail ? [
                'carrier_name' => $assignment->companyDriverDetail->carrier?->name,
            ] : null,
        ];
    }

    protected function vehicleFormPayload(Vehicle $vehicle, ?VehicleDriverAssignment $assignment): array
    {
        return [
            'id' => $vehicle->id,
            'carrier_id' => $vehicle->carrier_id ? (string) $vehicle->carrier_id : '',
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'type' => $vehicle->type,
            'company_unit_number' => $vehicle->company_unit_number,
            'year' => $vehicle->year,
            'vin' => $vehicle->vin,
            'gvwr' => $vehicle->gvwr,
            'registration_state' => $vehicle->registration_state,
            'registration_number' => $vehicle->registration_number,
            'registration_expiration_date' => $this->formatDateForUi($vehicle->registration_expiration_date),
            'annual_inspection_expiration_date' => $this->formatDateForUi($vehicle->annual_inspection_expiration_date),
            'permanent_tag' => (bool) $vehicle->permanent_tag,
            'tire_size' => $vehicle->tire_size,
            'fuel_type' => $vehicle->fuel_type,
            'irp_apportioned_plate' => (bool) $vehicle->irp_apportioned_plate,
            'location' => $vehicle->location,
            'status' => $vehicle->status,
            'status_effective_date' => $vehicle->status === 'out_of_service'
                ? $this->formatDateForUi($vehicle->out_of_service_date)
                : ($vehicle->status === 'suspended' ? $this->formatDateForUi($vehicle->suspended_date) : ''),
            'notes' => $vehicle->notes,
            'driver_type' => $assignment?->driver_type ?: $vehicle->driver_type,
            'user_driver_detail_id' => $assignment?->user_driver_detail_id ? (string) $assignment->user_driver_detail_id : ($vehicle->user_driver_detail_id ? (string) $vehicle->user_driver_detail_id : ''),
            'assignment_start_date' => $this->formatDateForUi($assignment?->start_date),
            'assignment_end_date' => $this->formatDateForUi($assignment?->end_date),
            'assignment_status' => $assignment?->status ?: 'active',
            'assignment_notes' => $assignment?->notes,
            'owner_name' => $assignment?->ownerOperatorDetail?->owner_name,
            'owner_phone' => $assignment?->ownerOperatorDetail?->owner_phone,
            'owner_email' => $assignment?->ownerOperatorDetail?->owner_email,
            'contract_agreed' => (bool) ($assignment?->ownerOperatorDetail?->contract_agreed ?? false),
            'third_party_name' => $assignment?->thirdPartyDetail?->third_party_name,
            'third_party_phone' => $assignment?->thirdPartyDetail?->third_party_phone,
            'third_party_email' => $assignment?->thirdPartyDetail?->third_party_email,
            'third_party_dba' => $assignment?->thirdPartyDetail?->third_party_dba,
            'third_party_address' => $assignment?->thirdPartyDetail?->third_party_address,
            'third_party_contact' => $assignment?->thirdPartyDetail?->third_party_contact,
            'third_party_fein' => $assignment?->thirdPartyDetail?->third_party_fein,
            'third_party_email_sent' => (bool) ($assignment?->thirdPartyDetail?->email_sent ?? false),
            'documents_url' => route('admin.vehicles.documents.index', $vehicle),
            'history_url' => route('admin.vehicles.driver-assignment-history', $vehicle),
        ];
    }

    protected function documentPreviewPayload($document): array
    {
        $media = $document->getFirstMedia('document_files');

        return [
            'id' => $document->id,
            'document_type' => $document->document_type,
            'document_type_label' => $document->document_type_name,
            'document_number' => $document->document_number,
            'expiration_date' => $this->formatDateForUi($document->expiration_date),
            'status' => $document->status,
            'status_label' => $document->status_name,
            'file_name' => $media?->file_name,
            'preview_url' => $media?->getUrl(),
        ];
    }

    protected function authorizeVehicle(Vehicle $vehicle): void
    {
        if (! $this->isSuperadmin() && (int) $vehicle->carrier_id !== (int) ($this->currentCarrierId() ?: 0)) {
            abort(403);
        }
    }

    protected function ensureCarrierAccess(int $carrierId): void
    {
        if (! $this->isSuperadmin() && $carrierId !== (int) ($this->currentCarrierId() ?: 0)) {
            abort(403);
        }
    }

    protected function ensureDriverBelongsToCarrier(array $validated): void
    {
        if (empty($validated['user_driver_detail_id'])) {
            return;
        }

        $driver = UserDriverDetail::query()->select('id', 'carrier_id')->find((int) $validated['user_driver_detail_id']);

        if (! $driver || (int) $driver->carrier_id !== (int) $validated['carrier_id']) {
            throw ValidationException::withMessages([
                'user_driver_detail_id' => 'The selected driver does not belong to the selected carrier.',
            ]);
        }
    }

    protected function emptyToNull(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
