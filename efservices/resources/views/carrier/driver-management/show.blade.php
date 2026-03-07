@extends('../themes/' . $activeTheme)
@section('title', 'Driver Details')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver & Vehicle Management', 'url' => route('carrier.driver-vehicle-management.index')],
        ['label' => 'Driver Details', 'active' => true],
    ];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <!-- Header Section -->
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Driver Details
            </div>
            <div class="flex gap-x-2 md:ml-auto">
                <x-base.button as="a"
                    href="{{ route('carrier.driver-vehicle-management.index') }}"
                    variant="outline-secondary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back to List
                </x-base.button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert-success alert mt-3">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert-danger alert mt-3">
                {{ session('error') }}
            </div>
        @endif

        <!-- Driver Information Card -->
        <div class="mt-3.5">
            <div class="box box--stacked flex flex-col p-5">
                <div class="flex flex-col gap-5 md:flex-row">
                    <!-- Profile Photo -->
                    <div class="flex-shrink-0">
                        <div class="h-32 w-32 overflow-hidden rounded-lg border-2 border-slate-200">
                            <img src="{{ $driver->profile_photo_url }}" 
                                 alt="{{ $driver->full_name }}"
                                 class="h-full w-full object-cover">
                        </div>
                    </div>

                    <!-- Driver Info -->
                    <div class="flex-1">
                        <div class="flex flex-col gap-y-3">
                            <div>
                                <h2 class="text-2xl font-medium">
                                    {{ $driver->user->name ?? 'N/A' }} {{ $driver->last_name ?? '' }}
                                </h2>
                                <div class="mt-1 text-slate-500">
                                    {{ $driver->user->email ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <!-- Phone -->
                                <div>
                                    <div class="text-xs text-slate-500">Phone</div>
                                    <div class="mt-1 font-medium">
                                        {{ $driver->phone ?? 'N/A' }}
                                    </div>
                                </div>

                                <!-- Date of Birth -->
                                <div>
                                    <div class="text-xs text-slate-500">Date of Birth</div>
                                    <div class="mt-1 font-medium">
                                        {{ $driver->date_of_birth ? $driver->date_of_birth->format('M d, Y') : 'N/A' }}
                                    </div>
                                </div>

                                <!-- Status -->
                                <div>
                                    <div class="text-xs text-slate-500">Status</div>
                                    <div class="mt-1">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            'bg-success/10 text-success' => $driver->status == 1,
                                            'bg-slate-100 text-slate-500' => $driver->status == 0,
                                            'bg-warning/10 text-warning' => $driver->status == 2,
                                        ])>
                                            {{ $driver->status_name }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Carrier -->
                                <div>
                                    <div class="text-xs text-slate-500">Carrier</div>
                                    <div class="mt-1 font-medium">
                                        {{ $driver->carrier->name ?? 'N/A' }}
                                    </div>
                                </div>

                                <!-- Hire Date -->
                                @if($driver->hire_date)
                                <div>
                                    <div class="text-xs text-slate-500">Hire Date</div>
                                    <div class="mt-1 font-medium">
                                        {{ $driver->hire_date->format('M d, Y') }}
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Assignment Section -->
        <div class="mt-5">
            <div class="box box--stacked flex flex-col">
                <div class="flex items-center border-b border-slate-200/60 p-5">
                    <h3 class="text-lg font-medium">Active Vehicle Assignment</h3>
                </div>

                @if($driver->activeVehicleAssignment && $driver->activeVehicleAssignment->vehicle)
                    @php
                        $assignment = $driver->activeVehicleAssignment;
                        $vehicle = $assignment->vehicle;
                        $driverType = $assignment->driver_type ?? 'company_driver';
                        $typeLabels = [
                            'owner_operator' => 'Owner Operator',
                            'company_driver' => 'Company Driver',
                            'third_party' => 'Third Party'
                        ];
                    @endphp
                    
                    <div class="p-5">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                            <!-- Vehicle Info -->
                            <div>
                                <div class="text-xs text-slate-500">Vehicle</div>
                                <div class="mt-1 font-medium">
                                    {{ $vehicle->company_unit_number ?: 'N/A' }}
                                </div>
                                <div class="mt-0.5 text-sm text-slate-500">
                                    {{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})
                                </div>
                            </div>

                            <!-- Driver Type -->
                            <div>
                                <div class="text-xs text-slate-500">Driver Type</div>
                                <div class="mt-1 font-medium">
                                    {{ $typeLabels[$driverType] ?? ucfirst(str_replace('_', ' ', $driverType)) }}
                                </div>
                            </div>

                            <!-- Start Date -->
                            <div>
                                <div class="text-xs text-slate-500">Start Date</div>
                                <div class="mt-1 font-medium">
                                    {{ $assignment->start_date ? $assignment->start_date->format('M d, Y') : 'N/A' }}
                                </div>
                            </div>

                            <!-- Notes -->
                            @if($assignment->notes)
                            <div class="md:col-span-2 lg:col-span-3">
                                <div class="text-xs text-slate-500">Notes</div>
                                <div class="mt-1 text-sm">
                                    {{ $assignment->notes }}
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-5 flex flex-wrap gap-2">
                            <x-base.button as="a"
                                href="{{ route('carrier.driver-vehicle-management.edit-assignment', $driver->id) }}"
                                variant="primary">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="Edit" />
                                Edit Assignment
                            </x-base.button>
                            <x-base.button as="a"
                                href="{{ route('carrier.driver-vehicle-management.assignment-history', $driver->id) }}"
                                variant="outline-secondary">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="History" />
                                View Full History
                            </x-base.button>
                            <x-base.button as="a"
                                href="{{ route('carrier.driver-vehicle-management.contact', $driver->id) }}"
                                variant="outline-secondary">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="Mail" />
                                Contact Driver
                            </x-base.button>
                        </div>
                    </div>
                @else
                    <div class="p-5">
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <x-base.lucide class="h-16 w-16 text-slate-300 mb-4" icon="TruckOff" />
                            <div class="text-lg font-medium text-slate-500">No Active Assignment</div>
                            <div class="mt-1 text-sm text-slate-400">
                                This driver does not currently have a vehicle assigned.
                            </div>
                            <div class="mt-4">
                                <x-base.button as="a"
                                    href="{{ route('carrier.driver-vehicle-management.assign-vehicle', $driver->id) }}"
                                    variant="primary">
                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Truck" />
                                    Assign Vehicle
                                </x-base.button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Assignment History Summary -->
        <div class="mt-5">
            <div class="box box--stacked flex flex-col">
                <div class="flex items-center border-b border-slate-200/60 p-5">
                    <h3 class="text-lg font-medium">Recent Assignment History</h3>
                    <div class="ml-auto">
                        <x-base.button as="a"
                            href="{{ route('carrier.driver-vehicle-management.assignment-history', $driver->id) }}"
                            variant="outline-secondary"
                            size="sm">
                            View All
                        </x-base.button>
                    </div>
                </div>

                @if($driver->vehicleAssignments && $driver->vehicleAssignments->count() > 0)
                    <div class="overflow-auto">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Vehicle
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Driver Type
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Start Date
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        End Date
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                        Status
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @foreach($driver->vehicleAssignments as $assignment)
                                    @php
                                        $vehicle = $assignment->vehicle;
                                        $driverType = $assignment->driver_type ?? 'company_driver';
                                        $typeLabels = [
                                            'owner_operator' => 'Owner Operator',
                                            'company_driver' => 'Company Driver',
                                            'third_party' => 'Third Party'
                                        ];
                                    @endphp
                                    <x-base.table.tr class="[&_td]:last:border-b-0">
                                        <x-base.table.td class="border-dashed py-4">
                                            @if($vehicle)
                                                <div class="font-medium">
                                                    {{ $vehicle->company_unit_number ?: 'N/A' }}
                                                </div>
                                                <div class="mt-0.5 text-xs text-slate-500">
                                                    {{ $vehicle->make }} {{ $vehicle->model }}
                                                </div>
                                            @else
                                                <span class="text-slate-400">Vehicle not found</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            {{ $typeLabels[$driverType] ?? ucfirst(str_replace('_', ' ', $driverType)) }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            {{ $assignment->start_date ? $assignment->start_date->format('M d, Y') : 'N/A' }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            {{ $assignment->end_date ? $assignment->end_date->format('M d, Y') : '-' }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center justify-center">
                                                <span @class([
                                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                    'bg-success/10 text-success' => $assignment->status === 'active',
                                                    'bg-slate-100 text-slate-500' => $assignment->status === 'inactive',
                                                ])>
                                                    {{ ucfirst($assignment->status) }}
                                                </span>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforeach
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                @else
                    <div class="p-5">
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <x-base.lucide class="h-16 w-16 text-slate-300 mb-4" icon="History" />
                            <div class="text-lg font-medium text-slate-500">No Assignment History</div>
                            <div class="mt-1 text-sm text-slate-400">
                                This driver has no vehicle assignment history yet.
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
