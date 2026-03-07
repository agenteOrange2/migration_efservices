@extends('../themes/' . $activeTheme)
@section('title', 'Assignment History')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver & Vehicle Management', 'url' => route('carrier.driver-vehicle-management.index')],
        ['label' => 'Driver Details', 'url' => route('carrier.driver-vehicle-management.show', $driver->id)],
        ['label' => 'Assignment History', 'active' => true],
    ];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <!-- Header Section -->
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Assignment History - {{ $driver->user->name ?? 'N/A' }} {{ $driver->last_name ?? '' }}
            </div>
            <div class="flex gap-x-2 md:ml-auto">
                <x-base.button as="a"
                    href="{{ route('carrier.driver-vehicle-management.show', $driver->id) }}"
                    variant="outline-secondary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back to Driver Details
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

        <!-- Driver Summary Card -->
        <div class="mt-3.5">
            <div class="box box--stacked flex flex-col p-5">
                <div class="flex flex-col gap-5 md:flex-row md:items-center">
                    <!-- Profile Photo -->
                    <div class="flex-shrink-0">
                        <div class="h-20 w-20 overflow-hidden rounded-lg border-2 border-slate-200">
                            <img src="{{ $driver->profile_photo_url }}" 
                                 alt="{{ $driver->full_name }}"
                                 class="h-full w-full object-cover">
                        </div>
                    </div>

                    <!-- Driver Info -->
                    <div class="flex-1">
                        <div class="flex flex-col gap-y-2">
                            <div>
                                <h2 class="text-xl font-medium">
                                    {{ $driver->user->name ?? 'N/A' }} {{ $driver->last_name ?? '' }}
                                </h2>
                                <div class="mt-0.5 text-sm text-slate-500">
                                    {{ $driver->user->email ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="flex gap-4 md:flex-col md:items-end">
                        <div class="text-center md:text-right">
                            <div class="text-2xl font-bold text-primary">
                                {{ $assignments->count() }}
                            </div>
                            <div class="text-xs text-slate-500">Total Assignments</div>
                        </div>
                        <div class="text-center md:text-right">
                            <div class="text-2xl font-bold text-success">
                                {{ $assignments->where('status', 'active')->count() }}
                            </div>
                            <div class="text-xs text-slate-500">Active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment History Table -->
        <div class="mt-5">
            <div class="box box--stacked flex flex-col">
                <div class="flex items-center border-b border-slate-200/60 p-5">
                    <h3 class="text-lg font-medium">Complete Assignment History</h3>
                </div>

                @if($assignments && $assignments->count() > 0)
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
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Notes
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @foreach($assignments as $assignment)
                                    @php
                                        $vehicle = $assignment->vehicle;
                                        $driverType = $assignment->driver_type ?? 'company_driver';
                                        $typeLabels = [
                                            'owner_operator' => 'Owner Operator',
                                            'company_driver' => 'Company Driver',
                                            'third_party' => 'Third Party'
                                        ];
                                        $isActive = $assignment->status === 'active';
                                    @endphp
                                    <x-base.table.tr @class([
                                        '[&_td]:last:border-b-0',
                                        'bg-success/5' => $isActive
                                    ])>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if($vehicle)
                                                <div class="flex items-center gap-2">
                                                    @if($isActive)
                                                        <x-base.lucide class="h-4 w-4 text-success" icon="CheckCircle" />
                                                    @endif
                                                    <div>
                                                        <div class="font-medium">
                                                            {{ $vehicle->company_unit_number ?: 'N/A' }}
                                                        </div>
                                                        <div class="mt-0.5 text-xs text-slate-500">
                                                            {{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-slate-400">Vehicle not found</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            {{ $typeLabels[$driverType] ?? ucfirst(str_replace('_', ' ', $driverType)) }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="font-medium">
                                                {{ $assignment->start_date ? $assignment->start_date->format('M d, Y') : 'N/A' }}
                                            </div>
                                            @if($assignment->assignedByUser)
                                                <div class="mt-0.5 text-xs text-slate-500">
                                                    by {{ $assignment->assignedByUser->name }}
                                                </div>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if($assignment->end_date)
                                                <div class="font-medium">
                                                    {{ $assignment->end_date->format('M d, Y') }}
                                                </div>
                                                @if($assignment->status === 'inactive')
                                                    <div class="mt-0.5 text-xs text-slate-500">
                                                        Duration: {{ floor($assignment->start_date->diffInDays($assignment->end_date)) + 1 }} days
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-slate-400">-</span>
                                                @if($isActive)
                                                    <div class="mt-0.5 text-xs text-success">
                                                        Ongoing ({{ floor($assignment->start_date->diffInDays(now())) + 1 }} days)
                                                    </div>
                                                @endif
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center justify-center">
                                                <span @class([
                                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                    'bg-success/10 text-success' => $isActive,
                                                    'bg-slate-100 text-slate-500' => !$isActive,
                                                ])>
                                                    @if($isActive)
                                                        <x-base.lucide class="mr-1 h-3 w-3" icon="Circle" />
                                                    @endif
                                                    {{ ucfirst($assignment->status) }}
                                                </span>
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if($assignment->notes)
                                                <div class="max-w-xs truncate text-sm" title="{{ $assignment->notes }}">
                                                    {{ $assignment->notes }}
                                                </div>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforeach
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                @else
                    <div class="p-5">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <x-base.lucide class="h-20 w-20 text-slate-300 mb-4" icon="History" />
                            <div class="text-xl font-medium text-slate-500">No Assignment History</div>
                            <div class="mt-2 text-sm text-slate-400">
                                This driver has no vehicle assignment history yet.
                            </div>
                            <div class="mt-6">
                                <x-base.button
                                    href="{{ route('carrier.driver-vehicle-management.assign-vehicle', $driver->id) }}"
                                    variant="primary">
                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Truck" />
                                    Assign First Vehicle
                                </x-base.button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        @if($assignments && $assignments->count() > 0)
            <div class="mt-5 flex flex-wrap gap-2">
                @if($driver->activeVehicleAssignment)
                    <x-base.button as="a"
                        href="{{ route('carrier.driver-vehicle-management.edit-assignment', $driver->id) }}"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="Edit" />
                        Edit Current Assignment
                    </x-base.button>
                @else
                    <x-base.button as="a"
                        href="{{ route('carrier.driver-vehicle-management.assign-vehicle', $driver->id) }}"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="Truck" />
                        Assign Vehicle
                    </x-base.button>
                @endif
                <x-base.button as="a"
                    href="{{ route('carrier.driver-vehicle-management.contact', $driver->id) }}"
                    variant="outline-secondary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="Mail" />
                    Contact Driver
                </x-base.button>
            </div>
        @endif
    </div>
</div>
@endsection
