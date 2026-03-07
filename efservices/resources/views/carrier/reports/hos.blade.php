@extends('../themes/' . $activeTheme)
@section('title', 'HOS Reports')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Reports', 'url' => route('carrier.reports.index')],
    ['label' => 'HOS', 'active' => true],
];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-base font-medium group-[.mode--light]:text-white">HOS Driver Summary</div>
                <div class="text-xs text-slate-500 mt-0.5">{{ $dateRangeLabel }}</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('carrier.reports.hos.export-pdf', request()->query()) }}" 
                   class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer bg-success border-success text-white">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="Download" />
                    Export to PDF
                </a>
                <a href="{{ route('carrier.reports.index') }}" class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer bg-secondary/70 border-secondary/70 text-slate-500">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back
                </a>
            </div>
        </div>
        
        <div class="mt-3.5">
            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-5">
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Drivers</div>
                    <div class="text-2xl font-medium">{{ $driverSummaries->total() }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Compliance Rate</div>
                    <div class="text-2xl font-medium text-success">{{ $stats['compliance_percentage'] ?? 0 }}%</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Days w/ Violations</div>
                    <div class="text-2xl font-medium text-danger">{{ $stats['logs_with_violations'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Avg Driving/Day</div>
                    <div class="text-2xl font-medium text-primary">{{ number_format($stats['average_driving_hours'] ?? 0, 1) }}h</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Total Log Days</div>
                    <div class="text-2xl font-medium text-warning">{{ $stats['total_logs'] ?? 0 }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="box box--stacked p-5 mb-5">
                <form action="{{ route('carrier.reports.hos') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Driver</label>
                        <select name="driver_id" class="w-full text-sm border-slate-200 shadow-sm rounded-md">
                            <option value="">All Drivers</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver['id'] }}" {{ request('driver_id') == $driver['id'] ? 'selected' : '' }}>
                                    {{ $driver['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Compliance</label>
                        <select name="has_violations" class="w-full text-sm border-slate-200 shadow-sm rounded-md">
                            <option value="">All</option>
                            <option value="no" {{ request('has_violations') == 'no' ? 'selected' : '' }}>Compliant</option>
                            <option value="yes" {{ request('has_violations') == 'yes' ? 'selected' : '' }}>With Violations</option>
                        </select>
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">From Date</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full text-sm border-slate-200 shadow-sm rounded-md">
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">To Date</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full text-sm border-slate-200 shadow-sm rounded-md">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="py-2 px-3 rounded-md bg-primary text-white">Apply</button>
                        <a href="{{ route('carrier.reports.hos') }}" class="py-2 px-3 rounded-md bg-secondary/70 text-slate-500">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="box box--stacked">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-b border-slate-200/60">
                        <thead>
                            <tr class="border-b border-slate-200/60 bg-slate-50">
                                <th class="px-5 py-4 font-medium text-slate-700">Driver</th>
                                <th class="px-5 py-4 font-medium text-slate-700 text-center">Days</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Total Driving</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Avg Driving/Day</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Total On-Duty</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Total Off-Duty</th>
                                <th class="px-5 py-4 font-medium text-slate-700 text-center">Violations</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Period</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($driverSummaries as $summary)
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
                                <tr class="border-b border-slate-200/60 hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <div class="font-medium">{{ $summary->driver->full_name ?? 'N/A' }}</div>
                                        <div class="text-xs text-slate-400">{{ $summary->driver->user->email ?? '' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ $summary->total_days }}</span>
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-primary">{{ $totalDrivingH }}h {{ $totalDrivingM }}m</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $avgDrivingH }}h {{ $avgDrivingM }}m</td>
                                    <td class="px-5 py-4">{{ $totalOnDutyH }}h {{ $totalOnDutyM }}m</td>
                                    <td class="px-5 py-4 text-slate-500">{{ $totalOffDutyH }}h {{ $totalOffDutyM }}m</td>
                                    <td class="px-5 py-4 text-center">
                                        @if($summary->days_with_violations > 0)
                                            <span class="px-2 py-1 rounded text-xs bg-danger/10 text-danger font-medium">{{ $summary->days_with_violations }} day{{ $summary->days_with_violations > 1 ? 's' : '' }}</span>
                                            <div class="text-xs text-slate-400 mt-0.5">{{ $complianceRate }}%</div>
                                        @else
                                            <span class="px-2 py-1 rounded text-xs bg-success/10 text-success font-medium">Clean</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="text-xs text-slate-500">
                                            {{ \Carbon\Carbon::parse($summary->first_log_date)->format('m/d/Y') }} &ndash; {{ \Carbon\Carbon::parse($summary->last_log_date)->format('m/d/Y') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center">
                                            <x-base.lucide class="h-8 w-8 text-slate-400 mb-2" icon="Clock" />
                                            <div>No HOS data found</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($driverSummaries->hasPages())
                    <div class="flex items-center justify-between p-5 border-t border-slate-200/60">
                        <div class="text-sm text-slate-500">
                            Showing {{ $driverSummaries->firstItem() }} to {{ $driverSummaries->lastItem() }} of {{ $driverSummaries->total() }} drivers
                        </div>
                        <div>{{ $driverSummaries->appends(request()->query())->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
