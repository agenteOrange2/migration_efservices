@extends('../themes/' . $activeTheme)
@section('title', 'HOS Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'HOS Report', 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">HOS Report</h1>
                    <p class="text-slate-600 mt-1">Driver summary &mdash; {{ $dateRangeLabel }}</p>
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
                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Drivers</div>
            <div class="text-3xl font-bold text-slate-800">{{ $driverSummaries->total() }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Compliance Rate</div>
            <div class="text-3xl font-bold text-success">{{ $stats['compliance_percentage'] }}%</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Days w/ Violations</div>
            <div class="text-3xl font-bold text-danger">{{ number_format($stats['logs_with_violations']) }}</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-info/10 rounded-lg">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Avg Driving/Day</div>
            <div class="text-3xl font-bold text-info">{{ number_format($stats['average_driving_hours'], 1) }}h</div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Log Days</div>
            <div class="text-3xl font-bold text-warning">{{ number_format($stats['total_logs']) }}</div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <div class="flex-1 w-full lg:w-auto">
                    <form action="{{ route('admin.reports.hos') }}" method="GET" id="filter-form">
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

                            <x-base.form-select name="has_violations" class="w-40" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="no" {{ request('has_violations') == 'no' ? 'selected' : '' }}>Compliant</option>
                                <option value="yes" {{ request('has_violations') == 'yes' ? 'selected' : '' }}>With Violations</option>
                            </x-base.form-select>

                            <x-base.form-input type="date" name="date_from" value="{{ request('date_from') }}" class="w-40" onchange="this.form.submit()" />
                            <x-base.form-input type="date" name="date_to" value="{{ request('date_to') }}" class="w-40" onchange="this.form.submit()" />
                        </div>
                    </form>
                </div>

                <div class="flex gap-3">
                    <x-base.button as="a" href="{{ route('admin.reports.hos') }}" variant="outline-secondary">Clear</x-base.button>
                    <x-base.button id="export-pdf" variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Export PDF
                    </x-base.button>
                </div>
            </div>
        </div>

        @if ($driverSummaries->count() > 0)
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Driver</x-base.table.th>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th class="text-center">Days Logged</x-base.table.th>
                            <x-base.table.th>Total Driving</x-base.table.th>
                            <x-base.table.th>Avg Driving/Day</x-base.table.th>
                            <x-base.table.th>Total On-Duty</x-base.table.th>
                            <x-base.table.th>Total Off-Duty</x-base.table.th>
                            <x-base.table.th class="text-center">Violations</x-base.table.th>
                            <x-base.table.th>Period</x-base.table.th>
                            <x-base.table.th>Actions</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach ($driverSummaries as $summary)
                            @php
                                $totalDrivingH = floor($summary->total_driving_minutes / 60);
                                $totalDrivingM = $summary->total_driving_minutes % 60;
                                $avgDrivingH = floor($summary->avg_driving_minutes / 60);
                                $avgDrivingM = round($summary->avg_driving_minutes % 60);
                                $totalOnDutyH = floor($summary->total_on_duty_minutes / 60);
                                $totalOnDutyM = $summary->total_on_duty_minutes % 60;
                                $totalOffDutyH = floor($summary->total_off_duty_minutes / 60);
                                $totalOffDutyM = $summary->total_off_duty_minutes % 60;
                                $complianceRate = $summary->total_days > 0 
                                    ? round((($summary->total_days - $summary->days_with_violations) / $summary->total_days) * 100, 0) 
                                    : 100;
                            @endphp
                            <x-base.table.tr class="hover:bg-slate-50">
                                <x-base.table.td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                            <span class="text-primary font-semibold text-xs">
                                                {{ strtoupper(substr($summary->driver->user->name ?? 'D', 0, 1)) }}{{ strtoupper(substr($summary->driver->last_name ?? 'R', 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $summary->driver->full_name ?? 'N/A' }}</div>
                                            <div class="text-xs text-slate-500">{{ $summary->driver->user->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <span class="text-sm">{{ $summary->carrier->name ?? 'N/A' }}</span>
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ $summary->total_days }}
                                    </span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <span class="font-semibold text-info">{{ $totalDrivingH }}h {{ $totalDrivingM }}m</span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <span class="text-sm text-slate-600">{{ $avgDrivingH }}h {{ $avgDrivingM }}m</span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <span class="text-sm">{{ $totalOnDutyH }}h {{ $totalOnDutyM }}m</span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <span class="text-sm text-slate-500">{{ $totalOffDutyH }}h {{ $totalOffDutyM }}m</span>
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    @if($summary->days_with_violations > 0)
                                        <div class="flex flex-col items-center">
                                            <x-base.badge variant="danger">{{ $summary->days_with_violations }} day{{ $summary->days_with_violations > 1 ? 's' : '' }}</x-base.badge>
                                            <span class="text-xs text-slate-400 mt-0.5">{{ $complianceRate }}% compliant</span>
                                        </div>
                                    @else
                                        <x-base.badge variant="success">Clean</x-base.badge>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($summary->first_log_date)->format('m/d/Y') }} &ndash; {{ \Carbon\Carbon::parse($summary->last_log_date)->format('m/d/Y') }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <x-base.button as="a" href="{{ route('admin.reports.hos.show', ['driver' => $summary->user_driver_detail_id]) }}?{{ http_build_query(array_filter(['date_from' => request('date_from', $summary->first_log_date), 'date_to' => request('date_to', $summary->last_log_date)])) }}" variant="outline-primary" size="sm">
                                        View
                                    </x-base.button>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>

            @if ($driverSummaries->hasPages())
                <div class="p-6 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-slate-600">
                            Showing {{ $driverSummaries->firstItem() }} to {{ $driverSummaries->lastItem() }} of {{ $driverSummaries->total() }} drivers
                        </div>
                        <div>{{ $driverSummaries->appends(request()->query())->links() }}</div>
                    </div>
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No HOS data found</h3>
                <p class="text-slate-600">No drivers have HOS logs matching the current filters.</p>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.getElementById('export-pdf')?.addEventListener('click', function() {
                const params = new URLSearchParams(window.location.search);
                params.delete('page');
                window.open('{{ route('admin.reports.hos.pdf') }}?' + params.toString(), '_blank');
            });
        </script>
    @endpush
@endsection
