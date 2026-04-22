<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DriverContactMail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\AdminMessage;
use App\Models\CompanyDriverDetail;
use App\Models\MessageRecipient;
use App\Models\OwnerOperatorDetail;
use App\Models\ThirdPartyDetail;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DriverTypeController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => (string) $request->input('carrier_id', ''),
            'driver_type' => (string) $request->input('driver_type', ''),
            'assignment_status' => (string) $request->input('assignment_status', ''),
        ];

        $baseQuery = UserDriverDetail::query()
            ->with([
                'user:id,name,email',
                'carrier:id,name',
                'activeVehicleAssignment.vehicle:id,carrier_id,company_unit_number,make,model,year,vin,status',
                'activeVehicleAssignment.companyDriverDetail',
                'activeVehicleAssignment.ownerOperatorDetail',
                'activeVehicleAssignment.thirdPartyDetail',
            ]);

        if ($filters['carrier_id'] !== '') {
            $baseQuery->where('carrier_id', (int) $filters['carrier_id']);
        }

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $baseQuery->where(function (Builder $builder) use ($search) {
                $builder
                    ->whereHas('user', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search))
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        if ($filters['driver_type'] !== '') {
            $baseQuery->whereHas('activeVehicleAssignment', fn (Builder $assignmentQuery) => $assignmentQuery
                ->where('driver_type', $filters['driver_type'])
                ->where('status', 'active'));
        }

        if ($filters['assignment_status'] === 'assigned') {
            $baseQuery->whereHas('activeVehicleAssignment', fn (Builder $assignmentQuery) => $assignmentQuery->where('status', 'active'));
        } elseif ($filters['assignment_status'] === 'unassigned') {
            $baseQuery->whereDoesntHave('activeVehicleAssignment', fn (Builder $assignmentQuery) => $assignmentQuery->where('status', 'active'));
        }

        $drivers = $baseQuery
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $drivers->through(fn (UserDriverDetail $driver) => $this->driverRow($driver));

        $statsBase = UserDriverDetail::query();
        if ($filters['carrier_id'] !== '') {
            $statsBase->where('carrier_id', (int) $filters['carrier_id']);
        }

        return Inertia::render('admin/driver-types/Index', [
            'drivers' => $drivers,
            'filters' => $filters,
            'stats' => [
                'total' => (clone $statsBase)->count(),
                'assigned' => (clone $statsBase)->whereHas('activeVehicleAssignment', fn (Builder $assignmentQuery) => $assignmentQuery->where('status', 'active'))->count(),
                'unassigned' => (clone $statsBase)->whereDoesntHave('activeVehicleAssignment', fn (Builder $assignmentQuery) => $assignmentQuery->where('status', 'active'))->count(),
                'vehicles_in_use' => VehicleDriverAssignment::query()
                    ->where('status', 'active')
                    ->when($filters['carrier_id'] !== '', fn (Builder $query) => $query->whereHas('vehicle', fn (Builder $vehicleQuery) => $vehicleQuery->where('carrier_id', (int) $filters['carrier_id'])))
                    ->distinct('vehicle_id')
                    ->count('vehicle_id'),
            ],
            'driverTypeOptions' => $this->driverTypeOptions(),
            'assignmentStatusOptions' => [
                'assigned' => 'Assigned',
                'unassigned' => 'Unassigned',
            ],
            'carriers' => \App\Models\Carrier::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($carrier) => [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                ]),
        ]);
    }

    public function show(UserDriverDetail $driver): InertiaResponse
    {
        $driver->load([
            'user:id,name,email',
            'carrier:id,name',
            'activeVehicleAssignment.vehicle:id,carrier_id,company_unit_number,make,model,year,vin,status,driver_type,user_driver_detail_id',
            'activeVehicleAssignment.companyDriverDetail.carrier:id,name',
            'activeVehicleAssignment.ownerOperatorDetail',
            'activeVehicleAssignment.thirdPartyDetail',
        ]);

        $recentAssignments = $driver->vehicleAssignments()
            ->with([
                'vehicle:id,carrier_id,company_unit_number,make,model,year,vin,status',
                'assignedByUser:id,name,email',
                'companyDriverDetail.carrier:id,name',
                'ownerOperatorDetail',
                'thirdPartyDetail',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (VehicleDriverAssignment $assignment) => $this->assignmentPayload($assignment));

        return Inertia::render('admin/driver-types/Show', [
            'driver' => $this->driverDetailPayload($driver),
            'activeAssignment' => $driver->activeVehicleAssignment
                ? $this->assignmentPayload($driver->activeVehicleAssignment)
                : null,
            'recentAssignments' => $recentAssignments,
            'availableVehiclesCount' => $this->availableVehiclesQuery($driver->carrier_id)->count(),
        ]);
    }

    public function assignVehicle(UserDriverDetail $driver): InertiaResponse|RedirectResponse
    {
        if ($driver->activeVehicleAssignment()->where('status', 'active')->exists()) {
            return redirect()
                ->route('admin.driver-types.show', $driver)
                ->with('warning', 'This driver already has an active assignment.');
        }

        return Inertia::render('admin/driver-types/AssignVehicle', [
            'driver' => $this->driverDetailPayload($driver->load('user:id,name,email', 'carrier:id,name')),
            'availableVehicles' => $this->availableVehiclesQuery($driver->carrier_id)
                ->orderBy('company_unit_number')
                ->get()
                ->map(fn (Vehicle $vehicle) => $this->vehicleOption($vehicle)),
        ]);
    }

    public function storeVehicleAssignment(Request $request, UserDriverDetail $driver): RedirectResponse
    {
        $request->merge([
            'assignment_date' => $this->normalizeDateInput($request->input('assignment_date')),
        ]);

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'assignment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        if ($driver->activeVehicleAssignment()->where('status', 'active')->exists()) {
            return back()->withInput()->with('error', 'The driver already has an active vehicle assignment.');
        }

        if ($this->vehicleHasActiveAssignment($vehicle)) {
            return back()->withInput()->with('error', 'That vehicle is already assigned to another driver.');
        }

        DB::transaction(function () use ($driver, $vehicle, $validated) {
            $assignment = VehicleDriverAssignment::query()->create([
                'vehicle_id' => $vehicle->id,
                'user_driver_detail_id' => $driver->id,
                'driver_type' => 'company_driver',
                'start_date' => $validated['assignment_date'],
                'status' => 'active',
                'notes' => $this->emptyToNull($validated['notes'] ?? null),
                'assigned_by' => Auth::id(),
            ]);

            CompanyDriverDetail::query()->updateOrCreate(
                ['vehicle_driver_assignment_id' => $assignment->id],
                [
                    'carrier_id' => $driver->carrier_id,
                    'notes' => $this->emptyToNull($validated['notes'] ?? null),
                ]
            );

            $vehicle->update([
                'user_driver_detail_id' => $driver->id,
                'driver_type' => 'company_driver',
            ]);
        });

        return redirect()
            ->route('admin.driver-types.show', $driver)
            ->with('success', 'Vehicle assigned successfully.');
    }

    public function editAssignment(UserDriverDetail $driver): InertiaResponse|RedirectResponse
    {
        $driver->load([
            'user:id,name,email',
            'carrier:id,name',
            'activeVehicleAssignment.vehicle:id,carrier_id,company_unit_number,make,model,year,vin,status,driver_type,user_driver_detail_id',
            'activeVehicleAssignment.companyDriverDetail.carrier:id,name',
            'activeVehicleAssignment.ownerOperatorDetail',
            'activeVehicleAssignment.thirdPartyDetail',
        ]);

        $currentAssignment = $driver->activeVehicleAssignment;

        if (! $currentAssignment) {
            return redirect()
                ->route('admin.driver-types.show', $driver)
                ->with('warning', 'No active assignment was found for this driver.');
        }

        return Inertia::render('admin/driver-types/EditAssignment', [
            'driver' => $this->driverDetailPayload($driver),
            'currentAssignment' => $this->assignmentPayload($currentAssignment),
            'availableVehicles' => $this->availableVehiclesQuery($driver->carrier_id, (int) $currentAssignment->vehicle_id)
                ->orderBy('company_unit_number')
                ->get()
                ->map(fn (Vehicle $vehicle) => $this->vehicleOption($vehicle, (int) $currentAssignment->vehicle_id)),
            'driverTypeOptions' => $this->driverTypeOptions(),
        ]);
    }

    public function updateAssignment(Request $request, UserDriverDetail $driver): RedirectResponse
    {
        $request->merge([
            'start_date' => $this->normalizeDateInput($request->input('start_date')),
        ]);

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_type' => ['required', 'in:company_driver,owner_operator,third_party'],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'third_party_name' => ['required_if:driver_type,third_party', 'nullable', 'string', 'max:255'],
            'third_party_dba' => ['nullable', 'string', 'max:255'],
            'third_party_address' => ['required_if:driver_type,third_party', 'nullable', 'string', 'max:500'],
            'third_party_phone' => ['required_if:driver_type,third_party', 'nullable', 'string', 'max:20'],
            'third_party_email' => ['required_if:driver_type,third_party', 'nullable', 'email', 'max:255'],
            'third_party_fein' => ['nullable', 'string', 'max:30'],
            'third_party_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $currentAssignment = $driver->activeVehicleAssignment()->with(['vehicle'])->first();

        if (! $currentAssignment) {
            return redirect()
                ->route('admin.driver-types.show', $driver)
                ->with('warning', 'No active assignment was found for this driver.');
        }

        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        if ((int) $vehicle->id !== (int) $currentAssignment->vehicle_id && $this->vehicleHasActiveAssignment($vehicle)) {
            return back()->withInput()->with('error', 'That vehicle is already assigned to another driver.');
        }

        DB::transaction(function () use ($driver, $validated, $currentAssignment, $vehicle) {
            $currentVehicle = $currentAssignment->vehicle;
            $currentAssignment->update([
                'status' => 'inactive',
                'end_date' => $validated['start_date'],
            ]);

            if ($currentVehicle && (int) $currentVehicle->id !== (int) $vehicle->id) {
                $currentVehicle->update([
                    'user_driver_detail_id' => null,
                    'driver_type' => null,
                    'status' => Vehicle::STATUS_PENDING,
                ]);
            }

            $newAssignment = VehicleDriverAssignment::query()->create([
                'vehicle_id' => $vehicle->id,
                'user_driver_detail_id' => $driver->id,
                'driver_type' => $validated['driver_type'],
                'start_date' => $validated['start_date'],
                'status' => 'active',
                'notes' => $this->emptyToNull($validated['notes'] ?? null),
                'assigned_by' => Auth::id(),
            ]);

            if ($validated['driver_type'] === 'third_party') {
                ThirdPartyDetail::query()->updateOrCreate(
                    ['vehicle_driver_assignment_id' => $newAssignment->id],
                    [
                        'third_party_name' => $this->emptyToNull($validated['third_party_name'] ?? null),
                        'third_party_dba' => $this->emptyToNull($validated['third_party_dba'] ?? null),
                        'third_party_address' => $this->emptyToNull($validated['third_party_address'] ?? null),
                        'third_party_phone' => $this->emptyToNull($validated['third_party_phone'] ?? null),
                        'third_party_email' => $this->emptyToNull($validated['third_party_email'] ?? null),
                        'third_party_fein' => $this->emptyToNull($validated['third_party_fein'] ?? null),
                        'third_party_contact' => $this->emptyToNull($validated['third_party_contact'] ?? null),
                        'notes' => $this->emptyToNull($validated['notes'] ?? null),
                    ]
                );
            } elseif ($validated['driver_type'] === 'owner_operator') {
                OwnerOperatorDetail::query()->updateOrCreate(
                    ['vehicle_driver_assignment_id' => $newAssignment->id],
                    [
                        'owner_name' => $driver->full_name,
                        'owner_phone' => $driver->phone,
                        'owner_email' => $driver->user?->email,
                        'notes' => $this->emptyToNull($validated['notes'] ?? null),
                    ]
                );
            } else {
                CompanyDriverDetail::query()->updateOrCreate(
                    ['vehicle_driver_assignment_id' => $newAssignment->id],
                    [
                        'carrier_id' => $driver->carrier_id,
                        'notes' => $this->emptyToNull($validated['notes'] ?? null),
                    ]
                );
            }

            $vehicle->update([
                'user_driver_detail_id' => $driver->id,
                'driver_type' => $validated['driver_type'],
                'status' => $vehicle->status ?: Vehicle::STATUS_ACTIVE,
            ]);
        });

        return redirect()
            ->route('admin.driver-types.show', $driver)
            ->with('success', 'Vehicle assignment updated successfully.');
    }

    public function cancelAssignment(Request $request, UserDriverDetail $driver): RedirectResponse
    {
        $request->merge([
            'termination_date' => $this->normalizeDateInput($request->input('termination_date')),
        ]);

        $validated = $request->validate([
            'termination_date' => ['required', 'date'],
            'termination_reason' => ['required', 'string', 'max:1000'],
        ]);

        $currentAssignment = $driver->activeVehicleAssignment()->with('vehicle')->first();

        if (! $currentAssignment) {
            return redirect()
                ->route('admin.driver-types.show', $driver)
                ->with('warning', 'No active assignment was found for this driver.');
        }

        DB::transaction(function () use ($currentAssignment, $validated) {
            $currentAssignment->update([
                'status' => 'inactive',
                'end_date' => $validated['termination_date'],
                'notes' => trim(implode("\n\n", array_filter([
                    $currentAssignment->notes,
                    'Termination Reason: ' . $validated['termination_reason'],
                ]))),
            ]);

            if ($currentAssignment->vehicle) {
                $currentAssignment->vehicle->update([
                    'user_driver_detail_id' => null,
                    'driver_type' => null,
                    'status' => Vehicle::STATUS_PENDING,
                ]);
            }
        });

        return redirect()
            ->route('admin.driver-types.show', $driver)
            ->with('success', 'Vehicle assignment cancelled successfully.');
    }

    public function assignmentHistory(UserDriverDetail $driver): InertiaResponse
    {
        $driver->load(['user:id,name,email', 'carrier:id,name']);

        $assignments = $driver->vehicleAssignments()
            ->with([
                'vehicle:id,carrier_id,company_unit_number,make,model,year,vin,status',
                'assignedByUser:id,name,email',
                'companyDriverDetail.carrier:id,name',
                'ownerOperatorDetail',
                'thirdPartyDetail',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (VehicleDriverAssignment $assignment) => $this->assignmentPayload($assignment));

        return Inertia::render('admin/driver-types/AssignmentHistory', [
            'driver' => $this->driverDetailPayload($driver),
            'assignments' => $assignments,
            'hasActiveAssignment' => $driver->activeVehicleAssignment()->where('status', 'active')->exists(),
        ]);
    }

    public function contact(UserDriverDetail $driver): InertiaResponse
    {
        $driver->load(['user:id,name,email', 'carrier:id,name']);

        return Inertia::render('admin/driver-types/Contact', [
            'driver' => $this->driverDetailPayload($driver),
            'priorityOptions' => [
                ['value' => 'low', 'label' => 'Low'],
                ['value' => 'normal', 'label' => 'Normal'],
                ['value' => 'high', 'label' => 'High'],
            ],
        ]);
    }

    public function sendContact(Request $request, UserDriverDetail $driver): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high'],
        ]);

        if (! $driver->user?->email) {
            return back()->withInput()->with('error', 'This driver does not have a valid email address.');
        }

        DB::transaction(function () use ($validated, $driver) {
            $message = AdminMessage::query()->create([
                'sender_type' => 'App\\Models\\User',
                'sender_id' => Auth::id(),
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'priority' => $validated['priority'],
                'status' => 'sent',
                'sent_at' => now(),
                'context_type' => UserDriverDetail::class,
                'context_id' => $driver->id,
            ]);

            $recipient = MessageRecipient::query()->create([
                'message_id' => $message->id,
                'recipient_type' => 'driver',
                'recipient_id' => $driver->id,
                'email' => (string) $driver->user->email,
                'name' => (string) $driver->full_name,
                'delivery_status' => 'pending',
            ]);

            try {
                Mail::to($driver->user->email)->send(new DriverContactMail(
                    $validated,
                    (string) (Auth::user()?->name ?? 'Admin'),
                    (string) (Auth::user()?->email ?? '')
                ));

                $recipient->update([
                    'delivery_status' => 'delivered',
                    'delivered_at' => now(),
                ]);
            } catch (\Throwable $exception) {
                report($exception);

                $recipient->update([
                    'delivery_status' => 'failed',
                ]);

                $message->update([
                    'status' => 'failed',
                ]);
            }
        });

        return redirect()
            ->route('admin.driver-types.show', $driver)
            ->with('success', 'Message sent successfully to the driver.');
    }

    protected function availableVehiclesQuery(int $carrierId, ?int $includeVehicleId = null)
    {
        return Vehicle::query()
            ->where('carrier_id', $carrierId)
            ->where(function (Builder $builder) use ($includeVehicleId) {
                $builder->whereDoesntHave('driverAssignments', fn (Builder $assignmentQuery) => $assignmentQuery->where('status', 'active'));

                if ($includeVehicleId) {
                    $builder->orWhere('id', $includeVehicleId);
                }
            })
            ->where(function (Builder $builder) {
                $builder
                    ->whereNull('status')
                    ->orWhereNotIn('status', [Vehicle::STATUS_OUT_OF_SERVICE, Vehicle::STATUS_SUSPENDED]);
            });
    }

    protected function vehicleHasActiveAssignment(Vehicle $vehicle): bool
    {
        return $vehicle->driverAssignments()->where('status', 'active')->exists();
    }

    protected function driverRow(UserDriverDetail $driver): array
    {
        return [
            'id' => $driver->id,
            'name' => $driver->full_name,
            'email' => $driver->user?->email,
            'phone' => $driver->phone,
            'status' => $driver->status_name,
            'profile_photo_url' => $this->resolveProfilePhotoUrl($driver),
            'assignment' => $driver->activeVehicleAssignment
                ? $this->assignmentPayload($driver->activeVehicleAssignment)
                : null,
        ];
    }

    protected function driverDetailPayload(UserDriverDetail $driver): array
    {
        return [
            'id' => $driver->id,
            'name' => $driver->full_name,
            'email' => $driver->user?->email,
            'phone' => $driver->phone,
            'date_of_birth' => $this->formatDateForUi($driver->date_of_birth),
            'status' => $driver->status_name,
            'profile_photo_url' => $this->resolveProfilePhotoUrl($driver),
            'carrier' => $driver->carrier ? [
                'id' => $driver->carrier->id,
                'name' => $driver->carrier->name,
            ] : null,
        ];
    }

    protected function assignmentPayload(VehicleDriverAssignment $assignment): array
    {
        $assignment->loadMissing([
            'vehicle:id,carrier_id,company_unit_number,make,model,year,vin,status',
            'assignedByUser:id,name,email',
            'companyDriverDetail.carrier:id,name',
            'ownerOperatorDetail',
            'thirdPartyDetail',
        ]);

        $vehicle = $assignment->vehicle;
        $duration = $assignment->end_date && $assignment->start_date
            ? Carbon::parse($assignment->start_date)->diffInDays(Carbon::parse($assignment->end_date)) + 1
            : null;

        return [
            'id' => $assignment->id,
            'driver_type' => $assignment->driver_type,
            'driver_type_label' => $this->driverTypeOptions()[$assignment->driver_type] ?? str($assignment->driver_type)->replace('_', ' ')->title()->toString(),
            'start_date' => $this->formatDateForUi($assignment->start_date),
            'end_date' => $this->formatDateForUi($assignment->end_date),
            'status' => $assignment->status,
            'status_label' => str($assignment->status)->replace('_', ' ')->title()->toString(),
            'notes' => $assignment->notes,
            'duration_label' => $duration ? $duration . ' day' . ($duration === 1 ? '' : 's') : 'Active',
            'assigned_by' => $assignment->assignedByUser?->name,
            'created_at' => $this->formatDateForUi($assignment->created_at),
            'vehicle' => $vehicle ? [
                'id' => $vehicle->id,
                'unit' => $vehicle->company_unit_number ?: ('Vehicle #' . $vehicle->id),
                'title' => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))),
                'vin' => $vehicle->vin,
                'status' => $vehicle->status,
            ] : null,
            'company_driver' => $assignment->companyDriverDetail ? [
                'carrier_name' => $assignment->companyDriverDetail->carrier?->name,
            ] : null,
            'owner_operator' => $assignment->ownerOperatorDetail ? [
                'owner_name' => $assignment->ownerOperatorDetail->owner_name,
                'owner_phone' => $assignment->ownerOperatorDetail->owner_phone,
                'owner_email' => $assignment->ownerOperatorDetail->owner_email,
            ] : null,
            'third_party' => $assignment->thirdPartyDetail ? [
                'name' => $assignment->thirdPartyDetail->third_party_name,
                'dba' => $assignment->thirdPartyDetail->third_party_dba,
                'address' => $assignment->thirdPartyDetail->third_party_address,
                'phone' => $assignment->thirdPartyDetail->third_party_phone,
                'email' => $assignment->thirdPartyDetail->third_party_email,
                'fein' => $assignment->thirdPartyDetail->third_party_fein,
                'contact' => $assignment->thirdPartyDetail->third_party_contact,
                'email_sent' => (bool) $assignment->thirdPartyDetail->email_sent,
            ] : null,
        ];
    }

    protected function vehicleOption(Vehicle $vehicle, ?int $currentVehicleId = null): array
    {
        return [
            'id' => $vehicle->id,
            'label' => trim(implode(' - ', array_filter([
                $vehicle->company_unit_number ?: ('Vehicle #' . $vehicle->id),
                trim(implode(' ', array_filter([$vehicle->make, $vehicle->model, $vehicle->year]))),
                $vehicle->vin ? 'VIN ' . substr((string) $vehicle->vin, -6) : null,
            ]))),
            'unit' => $vehicle->company_unit_number ?: ('Vehicle #' . $vehicle->id),
            'title' => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))),
            'vin' => $vehicle->vin,
            'status' => $vehicle->status,
            'is_current' => $currentVehicleId !== null && (int) $vehicle->id === $currentVehicleId,
        ];
    }

    protected function driverTypeOptions(): array
    {
        return [
            'company_driver' => 'Company Driver',
            'owner_operator' => 'Owner Operator',
            'third_party' => 'Third Party',
        ];
    }

    protected function formatDateForUi($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('n/j/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected function resolveProfilePhotoUrl(UserDriverDetail $driver): ?string
    {
        $profilePhotoUrl = $driver->profile_photo_url;

        if (! $profilePhotoUrl || str_contains($profilePhotoUrl, '/build/default_profile.png')) {
            return null;
        }

        return $profilePhotoUrl;
    }

    protected function normalizeDateInput(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable) {
            }
        }

        return $value;
    }

    protected function emptyToNull($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' ? null : $value;
    }
}
