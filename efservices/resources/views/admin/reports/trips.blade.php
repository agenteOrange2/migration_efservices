@extends('../themes/' . $activeTheme)
@section('title', 'Trip Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Trip Report', 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Trip Report</h1>
                    <p class="text-slate-600 mt-1">View and analyze trip data across all carriers</p>
                </div>
            </div>
            <x-base.button as="a" href="{{ route('admin.reports.index') }}" variant="outline-secondary" class="gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back to Reports
            </x-base.button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6 mb-6">
        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Trips</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['total_trips']) }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Completed</div>
            <div class="text-3xl font-bold text-success">{{ number_format($stats['completed_trips']) }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">In Progress</div>
            <div class="text-3xl font-bold text-warning">{{ number_format($stats['in_progress_trips']) }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Cancelled</div>
            <div class="text-3xl font-bold text-danger">{{ number_format($stats['cancelled_trips']) }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-amber-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">With Violations</div>
            <div class="text-3xl font-bold text-amber-500">{{ number_format($stats['trips_with_violations']) }}</div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <!-- Filters Bar -->
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <div class="flex-1 w-full lg:w-auto">
                    <form action="{{ route('admin.reports.trips') }}" method="GET" id="filter-form">
                        <div class="flex flex-wrap gap-3">
                            <x-base.form-select name="carrier_id" class="w-48" onchange="this.form.submit()">
                                <option value="">All Carriers</option>
                                @foreach ($carriers as $carrier)
                                    <option value="{{ $carrier->id }}" {{ request('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                        {{ $carrier->name }}
                                    </option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-select name="driver_id" class="w-48" onchange="this.form.submit()">
                                <option value="">All Drivers</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver['id'] }}" {{ request('driver_id') == $driver['id'] ? 'selected' : '' }}>
                                        {{ $driver['name'] }}
                                    </option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-select name="status" class="w-40" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                @foreach (\App\Models\Trip::STATUSES as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-input type="date" name="date_from" value="{{ request('date_from') }}" class="w-40" onchange="this.form.submit()" placeholder="From" />
                            <x-base.form-input type="date" name="date_to" value="{{ request('date_to') }}" class="w-40" onchange="this.form.submit()" placeholder="To" />
                        </div>
                    </form>
                </div>

                <div class="flex gap-3">
                    <x-base.button as="a" href="{{ route('admin.reports.trips') }}" variant="outline-secondary" class="gap-2">
                        Clear Filters
                    </x-base.button>
                    <x-base.button id="export-pdf" variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Export PDF
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Table -->
        @if ($trips->count() > 0)
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Trip #</x-base.table.th>
                            <x-base.table.th>Driver</x-base.table.th>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th>Vehicle</x-base.table.th>
                            <x-base.table.th>Origin</x-base.table.th>
                            <x-base.table.th>Destination</x-base.table.th>
                            <x-base.table.th>Status</x-base.table.th>
                            <x-base.table.th>Date</x-base.table.th>
                            <x-base.table.th>Actions</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach ($trips as $trip)
                            <x-base.table.tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('admin.reports.trips.show', $trip->id) }}'">
                                <x-base.table.td>
                                    <span class="font-medium text-primary">{{ $trip->trip_number }}</span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    {{ $trip->driver?->full_name ?? 'N/A' }}
                                </x-base.table.td>
                                <x-base.table.td>
                                    {{ $trip->carrier?->name ?? 'N/A' }}
                                </x-base.table.td>
                                <x-base.table.td>
                                    {{ $trip->vehicle?->company_unit_number ?? $trip->vehicle?->vin ?? 'N/A' }}
                                </x-base.table.td>
                                <x-base.table.td>
                                    <span class="truncate max-w-[150px] block" title="{{ $trip->origin_address }}">
                                        {{ \Illuminate\Support\Str::limit($trip->origin_address, 25) ?? 'N/A' }}
                                    </span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <span class="truncate max-w-[150px] block" title="{{ $trip->destination_address }}">
                                        {{ \Illuminate\Support\Str::limit($trip->destination_address, 25) ?? 'N/A' }}
                                    </span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <x-base.badge variant="{{ $trip->status_color }}">
                                        {{ $trip->status_name }}
                                    </x-base.badge>
                                    @if($trip->has_violations)
                                        <x-base.badge variant="danger" class="ml-1">!</x-base.badge>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    {{ $trip->scheduled_start_date?->format('M d, Y') ?? 'N/A' }}
                                </x-base.table.td>
                                <x-base.table.td>
                                    <x-base.button as="a" href="{{ route('admin.reports.trips.show', $trip->id) }}" variant="outline-primary" size="sm">
                                        View
                                    </x-base.button>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>

            @if ($trips->hasPages())
                <div class="p-6 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-slate-600">
                            Showing {{ $trips->firstItem() }} to {{ $trips->lastItem() }} of {{ $trips->total() }} results
                        </div>
                        <div>{{ $trips->appends(request()->query())->links() }}</div>
                    </div>
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No trips found</h3>
                <p class="text-slate-600">No trips match the current filters.</p>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.getElementById('export-pdf')?.addEventListener('click', function() {
                const params = new URLSearchParams(window.location.search);
                params.delete('page');
                window.open('{{ route('admin.reports.trips.pdf') }}?' + params.toString(), '_blank');
            });
        </script>
    @endpush
@endsection
