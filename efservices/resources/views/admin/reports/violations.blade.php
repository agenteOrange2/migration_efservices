@extends('../themes/' . $activeTheme)
@section('title', 'Violations Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Violations Report', 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-danger/10 rounded-xl border border-danger/20">
                    <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Violations Report</h1>
                    <p class="text-slate-600 mt-1">HOS violations across all carriers</p>
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
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Violations</div>
            <div class="text-3xl font-bold text-danger">{{ number_format($stats['total_violations']) }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Acknowledged</div>
            <div class="text-3xl font-bold text-success">{{ number_format($stats['acknowledged_count']) }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Unacknowledged</div>
            <div class="text-3xl font-bold text-warning">{{ number_format($stats['unacknowledged_count']) }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-info/10 rounded-lg">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Acknowledgment Rate</div>
            <div class="text-3xl font-bold text-info">{{ $stats['acknowledgment_rate'] }}%</div>
        </div>
    </div>

    <!-- Severity Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                <span class="font-medium">Minor</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['by_severity']['minor'] ?? 0) }}</div>
        </div>
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                <span class="font-medium">Moderate</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['by_severity']['moderate'] ?? 0) }}</div>
        </div>
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <span class="font-medium">Critical</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['by_severity']['critical'] ?? 0) }}</div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <div class="flex-1 w-full lg:w-auto">
                    <form action="{{ route('admin.reports.violations') }}" method="GET" id="filter-form">
                        <div class="flex flex-wrap gap-3">
                            <x-base.form-select name="carrier_id" class="w-44" onchange="this.form.submit()">
                                <option value="">All Carriers</option>
                                @foreach ($carriers as $carrier)
                                    <option value="{{ $carrier->id }}" {{ request('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                        {{ $carrier->name }}
                                    </option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-select name="driver_id" class="w-44" onchange="this.form.submit()">
                                <option value="">All Drivers</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver['id'] }}" {{ request('driver_id') == $driver['id'] ? 'selected' : '' }}>
                                        {{ $driver['name'] }}
                                    </option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-select name="violation_type" class="w-48" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                @foreach ($violationTypes as $type)
                                    <option value="{{ $type }}" {{ request('violation_type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                                    </option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-select name="severity" class="w-36" onchange="this.form.submit()">
                                <option value="">All Severity</option>
                                @foreach ($severities as $severity)
                                    <option value="{{ $severity }}" {{ request('severity') == $severity ? 'selected' : '' }}>
                                        {{ ucfirst($severity) }}
                                    </option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-select name="acknowledged" class="w-40" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="yes" {{ request('acknowledged') == 'yes' ? 'selected' : '' }}>Acknowledged</option>
                                <option value="no" {{ request('acknowledged') == 'no' ? 'selected' : '' }}>Unacknowledged</option>
                            </x-base.form-select>

                            <x-base.form-input type="date" name="date_from" value="{{ request('date_from') }}" class="w-36" onchange="this.form.submit()" />
                            <x-base.form-input type="date" name="date_to" value="{{ request('date_to') }}" class="w-36" onchange="this.form.submit()" />
                        </div>
                    </form>
                </div>

                <div class="flex gap-3">
                    <x-base.button as="a" href="{{ route('admin.reports.violations') }}" variant="outline-secondary">Clear</x-base.button>
                    <x-base.button id="export-pdf" variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Export PDF
                    </x-base.button>
                </div>
            </div>
        </div>

        @if ($violations->count() > 0)
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Date</x-base.table.th>
                            <x-base.table.th>Driver</x-base.table.th>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th>Type</x-base.table.th>
                            <x-base.table.th>Severity</x-base.table.th>
                            <x-base.table.th>Hours Exceeded</x-base.table.th>
                            <x-base.table.th>Status</x-base.table.th>
                            <x-base.table.th>Actions</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach ($violations as $violation)
                            <x-base.table.tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('admin.reports.violations.show', $violation->id) }}'">
                                <x-base.table.td>
                                    <span class="font-medium">{{ $violation->violation_date->format('M d, Y') }}</span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    {{ $violation->driver?->full_name ?? 'N/A' }}
                                </x-base.table.td>
                                <x-base.table.td>{{ $violation->carrier?->name ?? 'N/A' }}</x-base.table.td>
                                <x-base.table.td>
                                    <span class="text-sm">{{ $violation->violation_type_name }}</span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <x-base.badge variant="{{ $violation->severity_color }}">
                                        {{ $violation->severity_name }}
                                    </x-base.badge>
                                </x-base.table.td>
                                <x-base.table.td>{{ $violation->formatted_hours_exceeded }}</x-base.table.td>
                                <x-base.table.td>
                                    @if($violation->acknowledged)
                                        <x-base.badge variant="success">Acknowledged</x-base.badge>
                                    @else
                                        <x-base.badge variant="warning">Pending</x-base.badge>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    <x-base.button as="a" href="{{ route('admin.reports.violations.show', $violation->id) }}" variant="outline-primary" size="sm">
                                        View
                                    </x-base.button>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>

            @if ($violations->hasPages())
                <div class="p-6 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-slate-600">
                            Showing {{ $violations->firstItem() }} to {{ $violations->lastItem() }} of {{ $violations->total() }} results
                        </div>
                        <div>{{ $violations->appends(request()->query())->links() }}</div>
                    </div>
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No violations found</h3>
                <p class="text-slate-600">No violations match the current filters.</p>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.getElementById('export-pdf')?.addEventListener('click', function() {
                const params = new URLSearchParams(window.location.search);
                params.delete('page');
                window.open('{{ route('admin.reports.violations.pdf') }}?' + params.toString(), '_blank');
            });
        </script>
    @endpush
@endsection
