@extends('../themes/' . $activeTheme)
@section('title', 'Accidents Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Accidents Report', 'active' => true],
    ];
    
    $activeFiltersCount = 0;
    if (!empty($search)) $activeFiltersCount++;
    if (!empty($carrierFilter)) $activeFiltersCount++;
    if (!empty($driverId)) $activeFiltersCount++;
    if (!empty($dateFrom)) $activeFiltersCount++;
    if (!empty($dateTo)) $activeFiltersCount++;
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-danger/10 rounded-xl border border-danger/20">
                    <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 9v4M12 17h.01" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Accidents Report</h1>
                    <p class="text-slate-600 mt-1">Track and manage accident incidents</p>
                </div>
            </div>
            <div class="flex gap-3">
                <x-base.button as="a" href="{{ route('admin.reports.index') }}" variant="outline-secondary" class="gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Reports
                </x-base.button>
                <x-base.button id="export-pdf" variant="primary" class="gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Export PDF
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total Accidents -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 9v4M12 17h.01" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="danger" class="text-xs">Total</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Accidents</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($totalAccidents) }}</div>
            <div class="text-xs text-slate-500 mt-2">All registered incidents</div>
        </div>

        <!-- Preventable Accidents -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="warning" class="text-xs">Preventable</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Preventable</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($preventableAccidents) }}</div>
            <div class="text-xs text-slate-500 mt-2">{{ $totalAccidents > 0 ? round(($preventableAccidents / $totalAccidents) * 100, 1) : 0 }}% of total</div>
        </div>

        <!-- Non-Preventable Accidents -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="success" class="text-xs">Non-Preventable</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Non-Preventable</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($nonPreventableAccidents) }}</div>
            <div class="text-xs text-slate-500 mt-2">{{ $totalAccidents > 0 ? round(($nonPreventableAccidents / $totalAccidents) * 100, 1) : 0 }}% of total</div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <!-- Filters Bar -->
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <!-- Search -->
                <div class="flex-1 w-full lg:w-auto">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="11" cy="11" r="8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 21l-4.35-4.35" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input 
                            id="table-search" 
                            type="text" 
                            class="form-input form-input--sm pl-9 pr-4 w-full lg:w-80" 
                            placeholder="Search accidents..." 
                            value="{{ $search }}" 
                            onkeypress="searchOnEnter(event)">
                        @if(!empty($search))
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-xs text-slate-500">{{ $accidents->total() }} results</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="flex gap-3">
                    <x-base.popover class="inline-block">
                        <x-base.popover.button as="x-base.button" variant="outline-secondary" class="gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6h18M7 12h10M5 18h14" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Filters
                            @if($activeFiltersCount > 0)
                                <span class="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-medium text-white">
                                    {{ $activeFiltersCount }}
                                </span>
                            @endif
                        </x-base.popover.button>
                        <x-base.popover.panel>
                            <div class="p-4 w-80">
                                <form method="GET" action="{{ route('admin.reports.accidents') }}">
                                    <div class="mb-4">
                                        <x-base.form-label>Carrier</x-base.form-label>
                                        <x-base.form-select name="carrier" class="mt-2">
                                            <option value="">All Carriers</option>
                                            @foreach($carriers as $carrier)
                                                <option value="{{ $carrier->id }}" {{ $carrierFilter == $carrier->id ? 'selected' : '' }}>
                                                    {{ $carrier->name }}
                                                </option>
                                            @endforeach
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Driver</x-base.form-label>
                                        <x-base.form-select name="driver" class="mt-2">
                                            <option value="">All Drivers</option>
                                            @foreach($drivers as $driver)
                                                <option value="{{ $driver->id }}" {{ $driverId == $driver->id ? 'selected' : '' }}>
                                                    {{ $driver->full_name ?? 'N/A' }}
                                                </option>
                                            @endforeach
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Date Range</x-base.form-label>
                                        <div class="grid grid-cols-2 gap-2 mt-2">
                                            <div>
                                                <x-base.form-label class="text-xs">From</x-base.form-label>
                                                <x-base.form-input type="date" name="date_from" value="{{ $dateFrom }}" />
                                            </div>
                                            <div>
                                                <x-base.form-label class="text-xs">To</x-base.form-label>
                                                <x-base.form-input type="date" name="date_to" value="{{ $dateTo }}" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Search</x-base.form-label>
                                        <x-base.form-input
                                            type="text"
                                            name="search"
                                            value="{{ $search }}"
                                            placeholder="Location, description..."
                                            class="mt-2"
                                        />
                                    </div>

                                    <div class="flex gap-2">
                                        <x-base.button class="flex-1" variant="outline-secondary" as="a"
                                            href="{{ route('admin.reports.accidents') }}">
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
                </div>
            </div>
        </div>

        @if($accidents->count() > 0)
            <!-- Table Header -->
            <div class="p-6 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-slate-800">Accident Records</h2>
                    <x-base.badge variant="primary" class="text-xs">
                        {{ $accidents->total() }} {{ $accidents->total() === 1 ? 'Record' : 'Records' }}
                    </x-base.badge>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Driver</x-base.table.th>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th>Date</x-base.table.th>
                            <x-base.table.th>Nature of Accident</x-base.table.th>
                            <x-base.table.th class="text-center">Fatalities</x-base.table.th>
                            <x-base.table.th class="text-center">Injuries</x-base.table.th>
                            <x-base.table.th class="text-center">Actions</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach($accidents as $accident)
                            @php
                                $driverName = $accident->userDriverDetail 
                                    ? $accident->userDriverDetail->full_name 
                                    : 'N/A';
                                $driverLastName = '';
                                $driverImageUrl = null;
                                if ($accident->userDriverDetail && $accident->userDriverDetail->user) {
                                    $driverImageUrl = $accident->userDriverDetail->user->getFirstMediaUrl('profile_photo') ?: null;
                                }
                            @endphp
                            <x-base.table.tr>
                                <x-base.table.td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            @if($driverImageUrl)
                                                <img src="{{ $driverImageUrl }}" alt="{{ $driverName }}" class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $driverName }}</div>
                                            @if($driverLastName)
                                                <div class="text-xs text-slate-500">{{ $driverLastName }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="font-medium text-slate-900">
                                        {{ $accident->userDriverDetail && $accident->userDriverDetail->carrier ? $accident->userDriverDetail->carrier->name : 'N/A' }}
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        DOT: {{ $accident->userDriverDetail && $accident->userDriverDetail->carrier ? $accident->userDriverDetail->carrier->dot_number : 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        {{ $accident->accident_date ? $accident->accident_date->format('M d, Y') : 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        {{ $accident->nature_of_accident ?? 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    @if($accident->had_fatalities && $accident->number_of_fatalities > 0)
                                        <x-base.badge variant="danger" class="text-xs gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M12 9v4M12 17h.01" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            {{ $accident->number_of_fatalities }}
                                        </x-base.badge>
                                    @else
                                        <span class="text-sm text-slate-400">0</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    @if($accident->had_injuries && $accident->number_of_injuries > 0)
                                        <x-base.badge variant="warning" class="text-xs gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M12 8v4M12 16h.01" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            {{ $accident->number_of_injuries }}
                                        </x-base.badge>
                                    @else
                                        <span class="text-sm text-slate-400">0</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <x-base.button type="button" 
                                            class="view-accident gap-2" 
                                            data-id="{{ $accident->id }}" 
                                            data-driver-id="{{ $accident->user_driver_detail_id }}"
                                            variant="outline-primary" 
                                            size="sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            View
                                        </x-base.button>
                                        
                                        <x-base.button type="button" 
                                            class="delete-accident gap-2" 
                                            data-id="{{ $accident->id }}"
                                            variant="outline-danger" 
                                            size="sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Delete
                                        </x-base.button>
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>
    
            <!-- Pagination -->
            <div class="p-6 border-t border-slate-200">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-slate-600">
                        Showing <span class="font-medium">{{ $accidents->firstItem() }}</span> to 
                        <span class="font-medium">{{ $accidents->lastItem() }}</span> of 
                        <span class="font-medium">{{ $accidents->total() }}</span> results
                    </div>
                    <div>
                        {{ $accidents->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No accidents found</h3>
                <p class="text-slate-600 mb-6 max-w-md mx-auto">
                    @if($activeFiltersCount > 0)
                        No accidents match your current filters. Try adjusting your search criteria.
                    @else
                        There are no accident records in the system yet.
                    @endif
                </p>
                @if($activeFiltersCount > 0)
                    <x-base.button as="a" href="{{ route('admin.reports.accidents') }}" variant="primary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Clear Filters
                    </x-base.button>
                @endif
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            function searchOnEnter(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    const params = new URLSearchParams(window.location.search);
                    params.set('search', event.target.value);
                    window.location.href = `{{ route('admin.reports.accidents') }}?${params.toString()}`;
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Export to PDF
                const exportBtn = document.getElementById('export-pdf');
                if (exportBtn) {
                    exportBtn.addEventListener('click', function() {
                        const originalContent = this.innerHTML;
                        this.disabled = true;
                        this.innerHTML = `
                            <svg class="animate-spin h-4 w-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Generating PDF...
                        `;
                        
                        const params = new URLSearchParams(window.location.search);
                        params.append('export', 'pdf');
                        
                        window.location.href = `{{ route('admin.reports.accidents') }}?${params.toString()}`;
                        
                        setTimeout(() => {
                            this.disabled = false;
                            this.innerHTML = originalContent;
                        }, 3000);
                    });
                }
                
                // View accident details
                const viewButtons = document.querySelectorAll('.view-accident');
                viewButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const accidentId = this.dataset.id;
                        const driverId = this.dataset.driverId;
                        if (accidentId && driverId) {
                            window.location.href = `/admin/drivers/${driverId}/accident-history`;
                        } else if (accidentId) {
                            window.location.href = `/admin/accidents/${accidentId}/edit`;
                        }
                    });
                });
                
                // Delete accident
                const deleteButtons = document.querySelectorAll('.delete-accident');
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const accidentId = this.getAttribute('data-id');
                        if (accidentId && confirm('Are you sure you want to delete this accident record? This action cannot be undone.')) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = `/admin/reports/accident/${accidentId}`;
                            form.style.display = 'none';
                            
                            const csrfField = document.createElement('input');
                            csrfField.type = 'hidden';
                            csrfField.name = '_token';
                            csrfField.value = '{{ csrf_token() }}';
                            
                            const methodField = document.createElement('input');
                            methodField.type = 'hidden';
                            methodField.name = '_method';
                            methodField.value = 'DELETE';
                            
                            form.appendChild(csrfField);
                            form.appendChild(methodField);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
