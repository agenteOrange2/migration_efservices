@extends('../themes/' . $activeTheme)
@section('title', 'Repairs Management')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
        ['label' => 'Repairs Management', 'active' => true],
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
                            <x-base.lucide class="w-8 h-8 text-primary" icon="Wrench" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">Repairs Management</h1>
                            <p class="text-slate-600">Track and manage vehicle repairs</p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-[300px]">
                        <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.create') }}" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                            New Repair
                        </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Interactive Filter Cards -->
            <div class="box box--stacked flex flex-col p-5">
                <div class="grid grid-cols-4 gap-5">
                    <!-- Total Repairs Card -->
                    <a href="{{ route('admin.vehicles.emergency-repairs.index', ['tab' => 'all'] + request()->except('tab', 'page')) }}"
                       class="box col-span-4 rounded-[0.6rem] border border-dashed relative
                              {{ ($currentTab ?? 'all') == 'all' ? 'border-primary/80 bg-primary/5' : 'border-slate-300/80' }}
                              p-5 shadow-sm md:col-span-2 xl:col-span-1
                              hover:border-primary/60 hover:bg-primary/5
                              transition-all duration-150 ease-in-out cursor-pointer">
                        <div class="text-base {{ ($currentTab ?? 'all') == 'all' ? 'text-primary' : 'text-slate-500' }}">
                            Total Repairs
                        </div>
                        <div class="mt-1.5 text-2xl font-medium">{{ $totalCount }}</div>
                        <div class="mt-1 text-xs text-slate-500">${{ number_format($totalCost, 2) }}</div>
                        <div class="absolute inset-y-0 right-0 mr-5 flex flex-col justify-center">
                            <div class="flex items-center rounded-full border border-slate-200 bg-slate-100
                                        py-[2px] pl-[7px] pr-1 text-xs font-medium text-slate-600">
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5] mr-1" icon="wrench" />
                                All
                            </div>
                        </div>
                    </a>

                    <!-- Pending Repairs Card -->
                    <a href="{{ route('admin.vehicles.emergency-repairs.index', ['tab' => 'pending'] + request()->except('tab', 'page')) }}"
                       class="box col-span-4 rounded-[0.6rem] border border-dashed relative
                              {{ ($currentTab ?? 'all') == 'pending' ? 'border-primary/80 bg-primary/5' : 'border-slate-300/80' }}
                              p-5 shadow-sm md:col-span-2 xl:col-span-1
                              hover:border-primary/60 hover:bg-primary/5
                              transition-all duration-150 ease-in-out cursor-pointer">
                        <div class="text-base {{ ($currentTab ?? 'all') == 'pending' ? 'text-primary' : 'text-slate-500' }}">
                            Pending
                        </div>
                        <div class="mt-1.5 text-2xl font-medium">{{ $pendingCount }}</div>
                        <div class="mt-1 text-xs text-slate-500">${{ number_format($pendingCost, 2) }}</div>
                        <div class="absolute inset-y-0 right-0 mr-5 flex flex-col justify-center">
                            <div class="flex items-center rounded-full border border-warning/10 bg-warning/10
                                        py-[2px] pl-[7px] pr-1 text-xs font-medium text-warning">
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5] mr-1" icon="clock" />
                                Pending
                            </div>
                        </div>
                    </a>

                    <!-- In Progress Repairs Card -->
                    <a href="{{ route('admin.vehicles.emergency-repairs.index', ['tab' => 'in_progress'] + request()->except('tab', 'page')) }}"
                       class="box col-span-4 rounded-[0.6rem] border border-dashed relative
                              {{ ($currentTab ?? 'all') == 'in_progress' ? 'border-primary/80 bg-primary/5' : 'border-slate-300/80' }}
                              p-5 shadow-sm md:col-span-2 xl:col-span-1
                              hover:border-primary/60 hover:bg-primary/5
                              transition-all duration-150 ease-in-out cursor-pointer">
                        <div class="text-base {{ ($currentTab ?? 'all') == 'in_progress' ? 'text-primary' : 'text-slate-500' }}">
                            In Progress
                        </div>
                        <div class="mt-1.5 text-2xl font-medium">{{ $inProgressCount }}</div>
                        <div class="mt-1 text-xs text-slate-500">${{ number_format($inProgressCost, 2) }}</div>
                        <div class="absolute inset-y-0 right-0 mr-5 flex flex-col justify-center">
                            <div class="flex items-center rounded-full border border-primary/10 bg-primary/10
                                        py-[2px] pl-[7px] pr-1 text-xs font-medium text-primary">
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5] mr-1" icon="settings" />
                                Working
                            </div>
                        </div>
                    </a>

                    <!-- Completed Repairs Card -->
                    <a href="{{ route('admin.vehicles.emergency-repairs.index', ['tab' => 'completed'] + request()->except('tab', 'page')) }}"
                       class="box col-span-4 rounded-[0.6rem] border border-dashed relative
                              {{ ($currentTab ?? 'all') == 'completed' ? 'border-primary/80 bg-primary/5' : 'border-slate-300/80' }}
                              p-5 shadow-sm md:col-span-2 xl:col-span-1
                              hover:border-primary/60 hover:bg-primary/5
                              transition-all duration-150 ease-in-out cursor-pointer">
                        <div class="text-base {{ ($currentTab ?? 'all') == 'completed' ? 'text-primary' : 'text-slate-500' }}">
                            Completed
                        </div>
                        <div class="mt-1.5 text-2xl font-medium">{{ $completedCount }}</div>
                        <div class="mt-1 text-xs text-slate-500">${{ number_format($completedCost, 2) }}</div>
                        <div class="absolute inset-y-0 right-0 mr-5 flex flex-col justify-center">
                            <div class="flex items-center rounded-full border border-success/10 bg-success/10
                                        py-[2px] pl-[7px] pr-1 text-xs font-medium text-success">
                                <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5] mr-1" icon="check-circle" />
                                Done
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="intro-y box p-5 mt-5">
                <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                    <div class="font-medium text-base truncate">Filters</div>
                </div>

                <form method="GET" action="{{ route('admin.vehicles.emergency-repairs.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                    <!-- Search -->
                    <div>
                        <x-base.form-label for="search">Search</x-base.form-label>
                        <x-base.form-input id="search" name="search" type="text" class="w-full" 
                            placeholder="Search by repair name, vehicle..." value="{{ request('search') }}" />
                    </div>
                    <!-- Carrier Filter -->
                    <div>
                        <x-base.form-label for="carrier_id">Carrier</x-base.form-label>
                        <select id="carrier_id" name="carrier_id" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Carriers</option>
                            @foreach ($carriers as $carrier)
                                <option value="{{ $carrier->id }}" {{ request('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                    {{ $carrier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <x-base.form-label for="start_date">Start Date</x-base.form-label>
                        <x-base.litepicker id="start_date" name="start_date" value="{{ request('start_date') }}"
                            placeholder="MM/DD/YYYY" data-single-mode="true" data-format="MM/DD/YYYY" />
                    </div>

                    <div>
                        <x-base.form-label for="end_date">End Date</x-base.form-label>
                        <x-base.litepicker id="end_date" name="end_date" value="{{ request('end_date') }}"
                            placeholder="MM/DD/YYYY" data-single-mode="true" data-format="MM/DD/YYYY" />
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex items-end gap-2">
                        <x-base.button type="submit" variant="primary" class="w-full">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Search" />
                            Filter
                        </x-base.button>
                    </div>

                    <div class="flex items-end">
                        <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.index') }}" 
                            variant="outline-secondary" class="w-full">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="RotateCcw" />
                            Clear
                        </x-base.button>
                    </div>
                </form>
            </div>

            <!-- Emergency Repairs Table -->
            <div class="box box--stacked mt-5 p-3">
                <div class="box-header">
                    <h3 class="box-title">Repairs Management ({{ $emergencyRepairs->total() }})</h3>
                </div>
                <div class="box-body p-0">
                    @if($emergencyRepairs->count() > 0)
                    <div class="overflow-x-auto">
                        <x-base.table class="table-auto">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.th class="whitespace-nowrap">Repair Name</x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">Vehicle</x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">Carrier</x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">Driver</x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">Repair Date</x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">Cost</x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">Status</x-base.table.th>
                                    <x-base.table.th class="whitespace-nowrap">Actions</x-base.table.th>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @foreach($emergencyRepairs as $repair)
                                <x-base.table.tr>
                                    <x-base.table.td class="whitespace-nowrap">
                                        <div class="font-medium text-slate-900">{{ $repair->repair_name }}</div>
                                        @if($repair->description)
                                        <div class="text-sm text-slate-500 mt-0.5">{{ Str::limit($repair->description, 50) }}</div>
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td class="whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <div class="h-8 w-8 rounded-full bg-slate-300 flex items-center justify-center">
                                                    <x-base.lucide class="w-4 h-4 text-slate-600" icon="truck" />
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-slate-900">
                                                    {{ $repair->vehicle->make }} {{ $repair->vehicle->model }}
                                                </div>
                                                <div class="text-sm text-slate-500">
                                                    {{ $repair->vehicle->company_unit_number ?? $repair->vehicle->vin }}
                                                </div>
                                            </div>
                                        </div>
                                    </x-base.table.td>
                                    <x-base.table.td class="whitespace-nowrap">
                                        @if($repair->vehicle->carrier)
                                        <div class="text-sm font-medium text-slate-900">
                                            {{ $repair->vehicle->carrier->name }}
                                        </div>
                                        @else
                                        <span class="text-slate-400">No carrier</span>
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td class="whitespace-nowrap">
                                        @if($repair->vehicle->currentDriverAssignment && $repair->vehicle->currentDriverAssignment->driver)
                                        <div class="text-sm font-medium text-slate-900">
                                            {{ $repair->vehicle->currentDriverAssignment->driver->user->name ?? '' }} {{ $repair->vehicle->currentDriverAssignment->driver->last_name }}
                                        </div>
                                        @else
                                        <span class="text-slate-400">No driver</span>
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td class="whitespace-nowrap">
                                        {{ $repair->repair_date->format('m/d/Y') }}
                                    </x-base.table.td>
                                    <x-base.table.td class="whitespace-nowrap">
                                        <span class="font-medium text-slate-900">${{ number_format($repair->cost, 2) }}</span>
                                    </x-base.table.td>
                                    <x-base.table.td class="whitespace-nowrap">
                                        @php
                                            $statusClasses = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'in_progress' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800'
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses[$repair->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst(str_replace('_', ' ', $repair->status)) }}
                                        </span>
                                    </x-base.table.td>
                                    <x-base.table.td class="whitespace-nowrap">
                                        <x-base.menu>
                                            <x-base.menu.button as="x-base.button" variant="outline-secondary" size="sm">
                                                <x-base.lucide class="w-4 h-4" icon="more-horizontal" />
                                            </x-base.menu.button>
                                            <x-base.menu.items class="w-48">
                                                <x-base.menu.item as="a" href="{{ route('admin.vehicles.emergency-repairs.show', $repair) }}">
                                                    <x-base.lucide class="w-4 h-4 mr-2" icon="eye" />
                                                    View Details
                                                </x-base.menu.item>
                                                <x-base.menu.item as="a" href="{{ route('admin.vehicles.emergency-repairs.edit', $repair) }}">
                                                    <x-base.lucide class="w-4 h-4 mr-2" icon="edit" />
                                                    Edit
                                                </x-base.menu.item>
                                                <x-base.menu.divider />
                                                <x-base.menu.item>
                                                    <form action="{{ route('admin.vehicles.emergency-repairs.destroy', $repair) }}" method="POST"
                                                        onsubmit="return confirm('Are you sure you want to delete this emergency repair?')" class="w-full">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="flex items-center w-full text-red-600 hover:text-red-700">
                                                            <x-base.lucide class="w-4 h-4 mr-2" icon="trash-2" />
                                                            Delete
                                                        </button>
                                                    </form>
                                                </x-base.menu.item>
                                            </x-base.menu.items>
                                        </x-base.menu>
                                    </x-base.table.td>
                                </x-base.table.tr>
                                @endforeach
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center py-16">
                        <x-base.lucide class="w-16 h-16 text-slate-300 mb-4" icon="wrench" />
                        <h3 class="text-lg font-medium text-slate-500 mb-2">No repairs found</h3>
                        <p class="text-slate-400 mb-6 text-center max-w-md">
                            No emergency repairs match your current filters. Try adjusting your search criteria or create a new repair.
                        </p>
                        <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.create') }}" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                            Add First Repairs Management
                        </x-base.button>
                    </div>
                    @endif
                </div>
                <!-- Pagination -->
                @if($emergencyRepairs->hasPages())
                <div class="w-full">
                    {{ $emergencyRepairs->links('custom.pagination') }}
                </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const carrierSelect = document.getElementById('carrier_id');
                const driverSelect = document.getElementById('driver_id');

                // Load drivers when carrier changes
                carrierSelect.addEventListener('change', function () {
                    const carrierId = this.value;
                    
                    // Clear driver options
                    driverSelect.innerHTML = '<option value="">All Drivers</option>';
                    
                    if (carrierId) {
                        fetch(`{{ route('admin.vehicles.emergency-repairs.index') }}/drivers-by-carrier?carrier_id=${carrierId}`)
                            .then(response => response.json())
                            .then(drivers => {
                                drivers.forEach(driver => {
                                    const option = document.createElement('option');
                                    option.value = driver.id;
                                    option.textContent = `${driver.user ? driver.user.name : ''} ${driver.last_name}`;
                                    driverSelect.appendChild(option);
                                });
                            })
                            .catch(error => console.error('Error loading drivers:', error));
                    }
                });
            });
        </script>
    @endpush
@endsection