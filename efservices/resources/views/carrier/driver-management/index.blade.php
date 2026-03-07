@extends('../themes/' . $activeTheme)
@section('title', 'Driver & Vehicle Management')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver & Vehicle Management', 'active' => true],
    ];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="Users" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Driver & Vehicle Management</h1>
                        <p class="text-slate-600">Manage driver assignments and vehicle allocations</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 md:grid-cols-4 mb-5">
            <div class="box p-5 bg-gradient-to-br from-primary/5 to-primary/10 border-primary/20">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <x-base.lucide class="h-6 w-6 text-primary" icon="Users" />
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold">{{ $drivers->total() }}</div>
                        <div class="text-xs text-slate-500">Total Drivers</div>
                    </div>
                </div>
            </div>
            
            <div class="box p-5 bg-gradient-to-br from-success/5 to-success/10 border-success/20">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success/10">
                        <x-base.lucide class="h-6 w-6 text-success" icon="CheckCircle" />
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold">{{ $drivers->where('activeVehicleAssignment')->count() }}</div>
                        <div class="text-xs text-slate-500">Assigned</div>
                    </div>
                </div>
            </div>
            
            <div class="box p-5 bg-gradient-to-br from-warning/5 to-warning/10 border-warning/20">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning/10">
                        <x-base.lucide class="h-6 w-6 text-warning" icon="Circle" />
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold">{{ $drivers->where('activeVehicleAssignment', null)->count() }}</div>
                        <div class="text-xs text-slate-500">Unassigned</div>
                    </div>
                </div>
            </div>
            
            <div class="box p-5 bg-gradient-to-br from-info/5 to-info/10 border-info/20">
                <div class="flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-info/10">
                        <x-base.lucide class="h-6 w-6 text-info" icon="Truck" />
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold">{{ $drivers->unique('activeVehicleAssignment.vehicle_id')->count() }}</div>
                        <div class="text-xs text-slate-500">Vehicles in Use</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="box box--stacked flex flex-col shadow-lg">
            @if(session('success'))
                <div class="alert-success alert m-5 mb-0">
                    <x-base.lucide class="inline h-4 w-4 mr-2" icon="CheckCircle" />
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert-danger alert m-5 mb-0">
                    <x-base.lucide class="inline h-4 w-4 mr-2" icon="AlertCircle" />
                    {{ session('error') }}
                </div>
            @endif
                
            <form method="GET" action="{{ route('carrier.driver-vehicle-management.index') }}" id="filterForm">
                    <!-- Hidden filter fields -->
                    <input type="hidden" name="driver_type" id="hidden_driver_type" value="{{ request('driver_type') }}">
                    <input type="hidden" name="assignment_status" id="hidden_assignment_status" value="{{ request('assignment_status') }}">
                    
                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center">
                        <div>
                            <div class="relative">
                                <x-base.lucide
                                    class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500"
                                    icon="Search" />
                                <x-base.form-input class="rounded-[0.5rem] pl-9 sm:w-64" type="text"
                                    name="search" value="{{ request('search') }}"
                                    placeholder="Search by name or email..." />
                            </div>
                        </div>
                        <div class="flex flex-col gap-x-3 gap-y-2 sm:ml-auto sm:flex-row">
                            <!-- Advanced Filter Popover -->
                            <x-base.popover class="inline-block">
                                <x-base.popover.button type="button" class="w-full sm:w-auto" as="x-base.button"
                                    variant="outline-secondary">
                                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowDownWideNarrow" />
                                    Filter
                                    @php
                                    $activeFilters = 0;
                                    if (request('driver_type')) $activeFilters++;
                                    if (request('assignment_status')) $activeFilters++;
                                    @endphp
                                    @if($activeFilters > 0)
                                    <span
                                        class="ml-2 flex h-5 items-center justify-center rounded-full border bg-slate-100 px-1.5 text-xs font-medium">
                                        {{ $activeFilters }}
                                    </span>
                                    @endif
                                </x-base.popover.button>
                                <x-base.popover.panel>
                                    <div class="p-2">
                                        <!-- Driver Type Filter -->
                                        <div>
                                            <div class="text-left text-slate-500">
                                                Driver Type
                                            </div>
                                            <x-base.form-select id="popover_driver_type" class="mt-2 flex-1" onchange="updateHiddenField('driver_type', this.value)">
                                                <option value="">All Types</option>
                                                <option value="company_driver" {{ request('driver_type') == 'company_driver' ? 'selected' : '' }}>Company Driver</option>
                                                <option value="owner_operator" {{ request('driver_type') == 'owner_operator' ? 'selected' : '' }}>Owner Operator</option>
                                                <option value="third_party" {{ request('driver_type') == 'third_party' ? 'selected' : '' }}>Third Party</option>
                                            </x-base.form-select>
                                        </div>
                                        
                                        <!-- Assignment Status Filter -->
                                        <div class="mt-3">
                                            <div class="text-left text-slate-500">
                                                Assignment Status
                                            </div>
                                            <x-base.form-select id="popover_assignment_status" class="mt-2 flex-1" onchange="updateHiddenField('assignment_status', this.value)">
                                                <option value="">All Drivers</option>
                                                <option value="assigned" {{ request('assignment_status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                                <option value="unassigned" {{ request('assignment_status') == 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                                            </x-base.form-select>
                                        </div>
                                        
                                        <!-- Filter Actions -->
                                        <div class="mt-4 flex items-center">
                                            <x-base.button type="button" onclick="clearAllFilters()" class="ml-auto w-32" variant="secondary">
                                                Clear All
                                            </x-base.button>
                                            <x-base.button type="button" onclick="applyFilters()" class="ml-2 w-32" variant="primary">
                                                Apply
                                            </x-base.button>
                                        </div>
                                    </div>
                                </x-base.popover.panel>
                            </x-base.popover>
                        </div>
                    </div>
                </form>
                
                <!-- Responsive Driver Table -->
                <div class="overflow-auto xl:overflow-visible">
                    <x-base.table class="border-b border-slate-200/60">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Driver Name
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Email
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Driver Type
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Assigned Vehicle
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                    Assignment Status
                                </x-base.table.td>
                                <x-base.table.td
                                    class="w-36 border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                    Actions
                                </x-base.table.td>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @forelse ($drivers as $driver)
                            <x-base.table.tr class="[&_td]:last:border-b-0">
                                <!-- Driver Name Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <a class="font-medium text-primary hover:underline" 
                                       href="{{ route('carrier.driver-vehicle-management.show', $driver->id) }}">
                                        {{ $driver->user->name ?? 'N/A' }} {{ $driver->last_name ?? '' }}
                                    </a>
                                </x-base.table.td>
                                
                                <!-- Email Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <div class="text-sm">{{ $driver->user->email ?? 'N/A' }}</div>
                                </x-base.table.td>
                                
                                <!-- Driver Type Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <div class="whitespace-nowrap">
                                        @if($driver->activeVehicleAssignment)
                                            @php
                                                $driverType = $driver->activeVehicleAssignment->driver_type ?? 'company_driver';
                                                $typeLabels = [
                                                    'owner_operator' => 'Owner Operator',
                                                    'company_driver' => 'Company Driver', 
                                                    'third_party' => 'Third Party'
                                                ];
                                            @endphp
                                            {{ $typeLabels[$driverType] ?? ucfirst(str_replace('_', ' ', $driverType)) }}
                                        @else
                                            <span class="text-slate-400">Not assigned</span>
                                        @endif
                                    </div>
                                </x-base.table.td>
                                
                                <!-- Assigned Vehicle Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    @if($driver->activeVehicleAssignment && $driver->activeVehicleAssignment->vehicle)
                                        <div class="font-medium">
                                            {{ $driver->activeVehicleAssignment->vehicle->company_unit_number ?: 'N/A' }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $driver->activeVehicleAssignment->vehicle->make }} 
                                            {{ $driver->activeVehicleAssignment->vehicle->model }}
                                        </div>
                                    @else
                                        <span class="text-slate-400">No vehicle assigned</span>
                                    @endif
                                </x-base.table.td>
                                
                                <!-- Assignment Status Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <div @class([
                                        'flex items-center justify-center',
                                        'text-success' => $driver->activeVehicleAssignment,
                                        'text-slate-400' => !$driver->activeVehicleAssignment,
                                    ])>
                                        <x-base.lucide class="h-3.5 w-3.5 stroke-[1.7]"
                                            icon="{{ $driver->activeVehicleAssignment ? 'CheckCircle' : 'Circle' }}" />
                                        <div class="ml-1.5 whitespace-nowrap">
                                            {{ $driver->activeVehicleAssignment ? 'Assigned' : 'Unassigned' }}
                                        </div>
                                    </div>
                                </x-base.table.td>
                                
                                <!-- Actions Column -->
                                <x-base.table.td class="relative border-dashed py-4">
                                    <div class="flex items-center justify-center">
                                        <x-base.menu class="h-5">
                                            <x-base.menu.button class="h-5 w-5 text-slate-500">
                                                <x-base.lucide
                                                    class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70"
                                                    icon="MoreVertical" />
                                            </x-base.menu.button>
                                            <x-base.menu.items class="w-40">
                                                <x-base.menu.item
                                                    href="{{ route('carrier.driver-vehicle-management.show', $driver->id) }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Eye" />
                                                    View Details
                                                </x-base.menu.item>
                                                <x-base.menu.item
                                                    href="{{ route('carrier.driver-vehicle-management.edit-assignment', $driver->id) }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Edit" />
                                                    {{ $driver->activeVehicleAssignment ? 'Edit Assignment' : 'Assign Vehicle' }}
                                                </x-base.menu.item>
                                                <x-base.menu.item
                                                    href="{{ route('carrier.driver-vehicle-management.assignment-history', $driver->id) }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="History" />
                                                    Assignment History
                                                </x-base.menu.item>
                                                <x-base.menu.item
                                                    href="{{ route('carrier.driver-vehicle-management.contact', $driver->id) }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Mail" />
                                                    Contact Driver
                                                </x-base.menu.item>
                                            </x-base.menu.items>
                                        </x-base.menu>
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                            @empty
                            <x-base.table.tr>
                                <x-base.table.td colspan="6" class="border-dashed py-8 text-center">
                                    <div class="text-slate-500">
                                        <x-base.lucide class="mx-auto h-16 w-16 text-slate-300 mb-4" icon="Users" />
                                        <div class="text-lg font-medium">No drivers found</div>
                                        <div class="mt-1">
                                            @if(request()->hasAny(['search', 'driver_type', 'assignment_status']))
                                                Try adjusting your search criteria or 
                                                <button type="button" onclick="clearAllFilters()" class="text-primary hover:underline">clear all filters</button>
                                            @else
                                                No drivers are currently registered with your carrier.
                                            @endif
                                        </div>
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                            @endforelse
                        </x-base.table.tbody>
                    </x-base.table>
                </div>
                
                <!-- Pagination Section -->
                <div class="flex-reverse flex flex-col-reverse flex-wrap items-center gap-y-2 p-5 sm:flex-row">
                    <div class="mr-auto">
                        <div class="text-xs text-slate-500">
                            Showing {{ $drivers->firstItem() ?? 0 }} to {{ $drivers->lastItem() ?? 0 }} 
                            of {{ $drivers->total() }} drivers
                        </div>
                    </div>
                    <div class="w-full sm:w-auto">
                        {{ $drivers->appends(request()->query())->links('custom.pagination') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        initializeSearch();
    });

    // Auto-submit search form on input with debounce
    function initializeSearch() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 500);
            });
        }
    }

    // Update hidden field when filter changes
    function updateHiddenField(fieldName, value) {
        document.getElementById('hidden_' + fieldName).value = value;
    }

    // Apply filters
    function applyFilters() {
        document.getElementById('filterForm').submit();
    }

    // Clear all filters
    function clearAllFilters() {
        // Clear hidden fields
        document.getElementById('hidden_driver_type').value = '';
        document.getElementById('hidden_assignment_status').value = '';
        
        // Clear search input
        document.querySelector('input[name="search"]').value = '';
        
        // Clear filter dropdowns
        document.getElementById('popover_driver_type').value = '';
        document.getElementById('popover_assignment_status').value = '';
        
        // Submit form
        document.getElementById('filterForm').submit();
    }
</script>
@endpush
