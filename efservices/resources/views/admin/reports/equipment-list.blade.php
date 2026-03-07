@extends('../themes/' . $activeTheme)
@section('title', 'Equipment List Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Equipment List Report', 'active' => true],
    ];
    $currentTab = request('tab', 'all');
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM19 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13 16V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1m8-1a1 1 0 0 1-1 1H9m4-1V8a1 1 0 0 1 1-1h2.586a1 1 0 0 1 .707.293l3.414 3.414a1 1 0 0 1 .293.707V16a1 1 0 0 1-1 1h-1m-6-1a1 1 0 0 0 1 1h1M5 17a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0m6 0a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Equipment List Report</h1>
                    <p class="text-slate-600 mt-1">View and manage vehicle equipment information</p>
                </div>
            </div>
            <x-base.button as="a" href="{{ route('admin.reports.index') }}" variant="outline-secondary" class="gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back to Reports
            </x-base.button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <!-- Total Vehicles -->
        <a href="{{ route('admin.reports.equipment-list', ['tab' => 'all'] + request()->except('tab', 'page')) }}"
            class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg {{ $currentTab == 'all' ? 'border-primary border-2 bg-primary/5' : 'hover:border-primary/50' }}">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM19 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13 16V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1m8-1a1 1 0 0 1-1 1H9m4-1V8a1 1 0 0 1 1-1h2.586a1 1 0 0 1 .707.293l3.414 3.414a1 1 0 0 1 .293.707V16a1 1 0 0 1-1 1h-1m-6-1a1 1 0 0 0 1 1h1M5 17a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0m6 0a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="primary" class="text-xs">All</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Vehicles</div>
            <div class="text-3xl font-bold {{ $currentTab == 'all' ? 'text-primary' : 'text-slate-800' }}">{{ number_format($totalVehiclesCount) }}</div>
        </a>

        <!-- Active Vehicles -->
        <a href="{{ route('admin.reports.equipment-list', ['tab' => 'active'] + request()->except('tab', 'page')) }}"
            class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg {{ $currentTab == 'active' ? 'border-primary border-2 bg-primary/5' : 'hover:border-primary/50' }}">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="success" class="text-xs">Active</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Active Vehicles</div>
            <div class="text-3xl font-bold {{ $currentTab == 'active' ? 'text-primary' : 'text-slate-800' }}">{{ number_format($activeVehiclesCount) }}</div>
        </a>

        <!-- Out of Service -->
        <a href="{{ route('admin.reports.equipment-list', ['tab' => 'out_of_service'] + request()->except('tab', 'page')) }}"
            class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg {{ $currentTab == 'out_of_service' ? 'border-primary border-2 bg-primary/5' : 'hover:border-primary/50' }}">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 9v4M12 17h.01" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="danger" class="text-xs">Out</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Out of Service</div>
            <div class="text-3xl font-bold {{ $currentTab == 'out_of_service' ? 'text-primary' : 'text-slate-800' }}">{{ number_format($outOfServiceVehiclesCount) }}</div>
        </a>

        <!-- Suspended -->
        <a href="{{ route('admin.reports.equipment-list', ['tab' => 'suspended'] + request()->except('tab', 'page')) }}"
            class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg {{ $currentTab == 'suspended' ? 'border-primary border-2 bg-primary/5' : 'hover:border-primary/50' }}">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 8v4M12 16h.01" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="warning" class="text-xs">Suspended</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Suspended Vehicles</div>
            <div class="text-3xl font-bold {{ $currentTab == 'suspended' ? 'text-primary' : 'text-slate-800' }}">{{ number_format($suspendedVehiclesCount) }}</div>
        </a>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <!-- Filters Bar -->
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <!-- Search -->
                <div class="flex-1 w-full lg:w-auto">
                    <form action="{{ route('admin.reports.equipment-list') }}" method="GET" id="search-form">
                        <input type="hidden" name="tab" value="{{ $currentTab }}">
                        @if (!empty($carrierFilter))
                            <input type="hidden" name="carrier" value="{{ $carrierFilter }}">
                        @endif
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 21l-4.35-4.35" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <x-base.form-input 
                                class="pl-10 w-full lg:w-80" 
                                type="text" 
                                name="search"
                                value="{{ $search }}" 
                                placeholder="Search vehicles..."
                                onchange="this.form.submit()" />
                        </div>
                    </form>
                </div>

                <!-- Filter Buttons -->
                <div class="flex gap-3">
                    @php
                        $activeFiltersCount = 0;
                        if (!empty($carrierFilter)) $activeFiltersCount++;
                        if (request('date_from')) $activeFiltersCount++;
                        if (request('date_to')) $activeFiltersCount++;
                    @endphp

                    <x-base.popover class="inline-block">
                        <x-base.popover.button as="x-base.button" variant="outline-secondary" class="gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6h18M7 12h10M5 18h14" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Filter Options
                            @if($activeFiltersCount > 0)
                                <span class="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-medium text-white">
                                    {{ $activeFiltersCount }}
                                </span>
                            @endif
                        </x-base.popover.button>
                        <x-base.popover.panel>
                            <div class="p-4 w-80">
                                <form method="GET" action="{{ route('admin.reports.equipment-list') }}">
                                    @if (!empty($search))
                                        <input type="hidden" name="search" value="{{ $search }}">
                                    @endif
                                    <input type="hidden" name="tab" value="{{ $currentTab }}">

                                    <div class="mb-4">
                                        <x-base.form-label>Carrier</x-base.form-label>
                                        <x-base.form-select name="carrier" class="mt-2">
                                            <option value="">All Carriers</option>
                                            @foreach ($carriers as $carrier)
                                                <option value="{{ $carrier->id }}" {{ $carrierFilter == $carrier->id ? 'selected' : '' }}>
                                                    {{ $carrier->name }}
                                                </option>
                                            @endforeach
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Date Range</x-base.form-label>
                                        <div class="grid grid-cols-2 gap-2 mt-2">
                                            <div>
                                                <x-base.form-label class="text-xs">From</x-base.form-label>
                                                <x-base.form-input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" />
                                            </div>
                                            <div>
                                                <x-base.form-label class="text-xs">To</x-base.form-label>
                                                <x-base.form-input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" />
                                            </div>
                                        </div>
                                        <p id="date-error" class="text-xs text-red-600 mt-1 hidden">From date must be before To date</p>
                                    </div>

                                    <div class="flex gap-2">
                                        <x-base.button class="flex-1" variant="outline-secondary" as="a"
                                            href="{{ route('admin.reports.equipment-list', ['tab' => $currentTab]) }}">
                                            Clear
                                        </x-base.button>
                                        <x-base.button class="flex-1" variant="primary" type="submit">
                                            Apply
                                        </x-base.button>
                                    </div>
                                </form>
                            </div>
                        </x-base.popover.panel>
                    </x-base.popover>

                    <x-base.button id="export-pdf-inline" variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Export PDF
                    </x-base.button>
                </div>
            </div>
        </div>

        @if($vehicles->count() > 0)
            <!-- Table Header -->
            <div class="p-6 border-b border-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Equipment List</h3>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ $vehicles->total() }} vehicle{{ $vehicles->total() !== 1 ? 's' : '' }} found
                        </p>
                    </div>
                    <x-base.badge variant="primary">{{ $vehicles->total() }} total</x-base.badge>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Type</x-base.table.th>
                            <x-base.table.th>Make/Model</x-base.table.th>
                            <x-base.table.th>Year</x-base.table.th>
                            <x-base.table.th>VIN</x-base.table.th>
                            <x-base.table.th>Registration Status</x-base.table.th>
                            <x-base.table.th>Registration Expiration</x-base.table.th>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th>Driver</x-base.table.th>
                            <x-base.table.th>Actions</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach ($vehicles as $vehicle)
                            <x-base.table.tr>
                                <x-base.table.td>
                                    <div class="font-medium text-slate-900">
                                        {{ $vehicle->vehicleType ? $vehicle->vehicleType->name : 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        @if($vehicle->vehicleMake)
                                            {{ $vehicle->vehicleMake->name }} {{ $vehicle->model }}
                                        @else
                                            {{ $vehicle->model ?? 'N/A' }}
                                        @endif
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">{{ $vehicle->year ?? 'N/A' }}</div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-600 font-mono">{{ $vehicle->vin ?? 'N/A' }}</div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if(isset($vehicle->registration_expiration_date))
                                        @php
                                            $expirationDate = \Carbon\Carbon::parse($vehicle->registration_expiration_date);
                                            $today = \Carbon\Carbon::today();
                                            $status = $expirationDate->isFuture() ? 'success' : 'danger';
                                        @endphp
                                        <x-base.badge variant="{{ $status }}" class="text-xs">
                                            {{ $expirationDate->isFuture() ? 'Active' : 'Expired' }}
                                        </x-base.badge>
                                    @else
                                        <span class="text-sm text-slate-400">Not Available</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        {{ $vehicle->registration_expiration_date ? \Carbon\Carbon::parse($vehicle->registration_expiration_date)->format('M d, Y') : 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if ($vehicle->carrier)
                                        <x-base.badge variant="primary" class="text-xs">{{ $vehicle->carrier->name }}</x-base.badge>
                                    @else
                                        <span class="text-sm text-slate-400">Not assigned</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if($vehicle->driver && $vehicle->driver->user)
                                        <div class="text-sm text-slate-900">{{ $vehicle->driver->full_name }}</div>
                                    @else
                                        <span class="text-sm text-slate-400">Not Assigned</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    <x-base.button as="a" href="{{ route('admin.admin-vehicles.show', $vehicle->id) }}" 
                                        variant="outline-primary" 
                                        size="sm"
                                        class="gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        View
                                    </x-base.button>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>

            <!-- Pagination -->
            @if($vehicles->hasPages())
                <div class="p-6 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-slate-600">
                            Showing <span class="font-medium">{{ $vehicles->firstItem() }}</span> to 
                            <span class="font-medium">{{ $vehicles->lastItem() }}</span> of 
                            <span class="font-medium">{{ $vehicles->total() }}</span> results
                        </div>
                        <div>
                            {{ $vehicles->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM19 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13 16V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1m8-1a1 1 0 0 1-1 1H9m4-1V8a1 1 0 0 1 1-1h2.586a1 1 0 0 1 .707.293l3.414 3.414a1 1 0 0 1 .293.707V16a1 1 0 0 1-1 1h-1m-6-1a1 1 0 0 0 1 1h1M5 17a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0m6 0a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No vehicles found</h3>
                <p class="text-slate-600 mb-6">No vehicles match the current filters. Try adjusting your search criteria.</p>
                <x-base.button as="a" href="{{ route('admin.reports.equipment-list', ['tab' => $currentTab]) }}" variant="primary">
                    Clear Filters
                </x-base.button>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Date range validation
                const dateFrom = document.getElementById('date_from');
                const dateTo = document.getElementById('date_to');
                const dateError = document.getElementById('date-error');

                function validateDateRange() {
                    if (dateFrom && dateTo && dateFrom.value && dateTo.value) {
                        const fromDate = new Date(dateFrom.value);
                        const toDate = new Date(dateTo.value);

                        if (fromDate > toDate) {
                            if (dateError) {
                                dateError.classList.remove('hidden');
                            }
                            return false;
                        } else {
                            if (dateError) {
                                dateError.classList.add('hidden');
                            }
                            return true;
                        }
                    }
                    if (dateError) {
                        dateError.classList.add('hidden');
                    }
                    return true;
                }

                if (dateFrom) {
                    dateFrom.addEventListener('change', validateDateRange);
                }
                if (dateTo) {
                    dateTo.addEventListener('change', validateDateRange);
                }

                const filterForm = dateFrom?.closest('form');
                if (filterForm) {
                    filterForm.addEventListener('submit', function(e) {
                        if (!validateDateRange()) {
                            e.preventDefault();
                        }
                    });
                }

                // Export PDF
                const exportPdfInlineBtn = document.getElementById('export-pdf-inline');

                function getExportUrl() {
                    const params = new URLSearchParams(window.location.search);
                    if (params.has('page')) {
                        params.delete('page');
                    }
                    if (!params.has('tab')) {
                        const currentTab = '{{ $currentTab }}';
                        params.append('tab', currentTab);
                    }
                    let url = '{{ route("admin.reports.equipment-list.pdf") }}';
                    const queryString = params.toString();
                    return queryString ? `${url}?${queryString}` : url;
                }

                function handleExport() {
                    const button = this;
                    const originalHTML = button.innerHTML;
                    button.innerHTML = `
                        <svg class="animate-spin h-4 w-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Generating PDF...
                    `;
                    button.disabled = true;
                    window.open(getExportUrl(), '_blank');
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    }, 2000);
                }

                if (exportPdfInlineBtn) {
                    exportPdfInlineBtn.addEventListener('click', handleExport);
                }
            });
        </script>
    @endpush
@endsection
