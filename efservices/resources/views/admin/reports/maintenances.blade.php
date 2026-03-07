@extends('../themes/' . $activeTheme)
@section('title', 'Maintenances Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Maintenances Report', 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Maintenances Report</h1>
                    <p class="text-slate-600 mt-1">View and manage vehicle maintenance records</p>
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
        <!-- Total Maintenances -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="primary" class="text-xs">Total</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Maintenances</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($totalCount) }}</div>
        </div>

        <!-- Completed -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="success" class="text-xs">Completed</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Completed</div>
            <div class="text-3xl font-bold text-success">{{ number_format($completedCount) }}</div>
        </div>

        <!-- Pending -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="warning" class="text-xs">Pending</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Pending</div>
            <div class="text-3xl font-bold text-warning">{{ number_format($pendingCount) }}</div>
        </div>

        <!-- Overdue -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 9v4M12 17h.01" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="danger" class="text-xs">Overdue</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Overdue</div>
            <div class="text-3xl font-bold text-danger">{{ number_format($overdueCount) }}</div>
        </div>
    </div>

    <!-- Cost Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <line x1="12" y1="1" x2="12" y2="23" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="text-lg font-semibold text-slate-800">Total Cost</h3>
            </div>
            <div class="text-3xl font-bold text-primary">${{ number_format($totalCost, 2) }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="text-lg font-semibold text-slate-800">Average Cost</h3>
            </div>
            <div class="text-3xl font-bold text-slate-800">${{ number_format($avgCost, 2) }}</div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <!-- Filters Bar -->
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <!-- Search -->
                <div class="flex-1 w-full lg:w-auto">
                    <form action="{{ route('admin.reports.maintenances') }}" method="GET" id="search-form">
                        @foreach(request()->except(['search', 'page']) as $key => $value)
                            @if($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                        @endforeach
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
                                placeholder="Search maintenances..."
                                onchange="this.form.submit()" />
                        </div>
                    </form>
                </div>

                <!-- Filter Buttons -->
                <div class="flex gap-3">
                    @php
                        $activeFiltersCount = collect([$carrierFilter, $statusFilter, $dateFrom, $dateTo])->filter()->count();
                    @endphp

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
                                <form method="GET" action="{{ route('admin.reports.maintenances') }}">
                                    @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                                    
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
                                        <x-base.form-label>Status</x-base.form-label>
                                        <x-base.form-select name="status" class="mt-2">
                                            <option value="">All Status</option>
                                            <option value="completed" {{ $statusFilter == 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="pending" {{ $statusFilter == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="overdue" {{ $statusFilter == 'overdue' ? 'selected' : '' }}>Overdue</option>
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

                                    <div class="flex gap-2">
                                        <x-base.button class="flex-1" variant="outline-secondary" as="a" href="{{ route('admin.reports.maintenances') }}">
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

                    <x-base.button id="export-pdf-btn" variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Export PDF
                    </x-base.button>
                </div>
            </div>
        </div>

        @if($maintenances->count() > 0)
            <!-- Table -->
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Service Date</x-base.table.th>
                            <x-base.table.th>Vehicle</x-base.table.th>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th>Tasks</x-base.table.th>
                            <x-base.table.th>Vendor</x-base.table.th>
                            <x-base.table.th>Cost</x-base.table.th>
                            <x-base.table.th>Status</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach($maintenances as $maintenance)
                            <x-base.table.tr>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        {{ $maintenance->service_date ? $maintenance->service_date->format('M d, Y') : 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="font-medium text-slate-900">
                                        {{ $maintenance->vehicle->company_unit_number ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $maintenance->vehicle->make ?? '' }} {{ $maintenance->vehicle->model ?? '' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if($maintenance->vehicle && $maintenance->vehicle->carrier)
                                        <x-base.badge variant="primary" class="text-xs">{{ $maintenance->vehicle->carrier->name }}</x-base.badge>
                                    @else
                                        <span class="text-sm text-slate-400">N/A</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900 max-w-xs truncate">
                                        {{ $maintenance->service_tasks ?? 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        {{ $maintenance->vendor_mechanic ?? 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm font-medium text-slate-900">
                                        ${{ number_format($maintenance->cost ?? 0, 2) }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if($maintenance->status)
                                        <x-base.badge variant="success" class="text-xs">Completed</x-base.badge>
                                    @elseif($maintenance->isOverdue())
                                        <x-base.badge variant="danger" class="text-xs">Overdue</x-base.badge>
                                    @else
                                        <x-base.badge variant="warning" class="text-xs">Pending</x-base.badge>
                                    @endif
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>

            <!-- Pagination -->
            @if($maintenances->hasPages())
                <div class="p-6 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-slate-600">
                            Showing <span class="font-medium">{{ $maintenances->firstItem() }}</span> to 
                            <span class="font-medium">{{ $maintenances->lastItem() }}</span> of 
                            <span class="font-medium">{{ $maintenances->total() }}</span> results
                        </div>
                        <div>
                            {{ $maintenances->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No maintenances found</h3>
                <p class="text-slate-600 mb-6">No maintenance records match the applied filters.</p>
                <x-base.button as="a" href="{{ route('admin.reports.maintenances') }}" variant="primary">
                    Clear Filters
                </x-base.button>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('export-pdf-btn').addEventListener('click', function() {
                    const params = new URLSearchParams(window.location.search);
                    params.delete('page');
                    let url = '{{ route("admin.reports.maintenances.pdf") }}';
                    const queryString = params.toString();
                    window.location.href = queryString ? `${url}?${queryString}` : url;
                });
            });
        </script>
    @endpush
@endsection
