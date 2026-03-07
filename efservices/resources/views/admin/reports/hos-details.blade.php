@extends('../themes/' . $activeTheme)
@section('title', 'HOS Driver Details')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'HOS Report', 'url' => route('admin.reports.hos')],
        ['label' => $driver->full_name ?? 'Driver', 'active' => true],
    ];

    $totalDrivingH = floor($stats['total_driving_minutes'] / 60);
    $totalDrivingM = $stats['total_driving_minutes'] % 60;
    $totalOnDutyH = floor($stats['total_on_duty_minutes'] / 60);
    $totalOnDutyM = $stats['total_on_duty_minutes'] % 60;
    $totalOffDutyH = floor($stats['total_off_duty_minutes'] / 60);
    $totalOffDutyM = $stats['total_off_duty_minutes'] % 60;
    $avgDrivingH = floor($stats['avg_driving_minutes'] / 60);
    $avgDrivingM = round($stats['avg_driving_minutes'] % 60);
    $complianceRate = $stats['total_days'] > 0
        ? round((($stats['total_days'] - $stats['days_with_violations']) / $stats['total_days']) * 100, 0)
        : 100;
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center border border-primary/20">
                    <span class="text-primary font-bold text-lg">
                        {{ strtoupper(substr($driver->user->name ?? 'D', 0, 1)) }}{{ strtoupper(substr($driver->last_name ?? 'R', 0, 1)) }}
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">{{ $driver->full_name ?? 'N/A' }}</h1>
                    <p class="text-slate-500 text-sm mt-0.5">
                        {{ $driver->carrier->name ?? 'No carrier' }}
                        @if($driver->user->email)
                            &middot; {{ $driver->user->email }}
                        @endif
                        &middot; {{ $dateRangeLabel }}
                    </p>
                </div>
            </div>
            <div class="flex gap-3">
                <x-base.button as="a" href="{{ route('admin.reports.hos') }}" variant="outline-secondary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                    Back to HOS Report
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Period Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
        <div class="box box--stacked p-4 text-center">
            <div class="text-2xl font-bold text-slate-800">{{ $stats['total_days'] }}</div>
            <div class="text-xs text-slate-500 uppercase mt-1">Days Logged</div>
        </div>
        <div class="box box--stacked p-4 text-center">
            <div class="text-2xl font-bold text-primary">{{ $totalDrivingH }}h {{ $totalDrivingM }}m</div>
            <div class="text-xs text-slate-500 uppercase mt-1">Total Driving</div>
        </div>
        <div class="box box--stacked p-4 text-center">
            <div class="text-2xl font-bold text-info">{{ $avgDrivingH }}h {{ $avgDrivingM }}m</div>
            <div class="text-xs text-slate-500 uppercase mt-1">Avg Driving/Day</div>
        </div>
        <div class="box box--stacked p-4 text-center">
            <div class="text-2xl font-bold text-warning">{{ $totalOnDutyH }}h {{ $totalOnDutyM }}m</div>
            <div class="text-xs text-slate-500 uppercase mt-1">Total On-Duty</div>
        </div>
        <div class="box box--stacked p-4 text-center">
            <div class="text-2xl font-bold text-slate-500">{{ $totalOffDutyH }}h {{ $totalOffDutyM }}m</div>
            <div class="text-xs text-slate-500 uppercase mt-1">Total Off-Duty</div>
        </div>
        <div class="box box--stacked p-4 text-center">
            <div class="text-2xl font-bold {{ $stats['days_with_violations'] > 0 ? 'text-danger' : 'text-success' }}">{{ $stats['days_with_violations'] }}</div>
            <div class="text-xs text-slate-500 uppercase mt-1">Violation Days</div>
        </div>
        <div class="box box--stacked p-4 text-center">
            <div class="text-2xl font-bold text-success">{{ $complianceRate }}%</div>
            <div class="text-xs text-slate-500 uppercase mt-1">Compliance</div>
        </div>
    </div>

    <!-- Daily Logs Table -->
    <div class="box box--stacked">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold">Daily Logs</h3>
        </div>
        <div class="overflow-x-auto">
            <x-base.table>
                <x-base.table.thead>
                    <x-base.table.tr class="bg-slate-50">
                        <x-base.table.th class="whitespace-nowrap font-medium text-slate-700">Date</x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap font-medium text-slate-700">Driving</x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap font-medium text-slate-700">On-Duty</x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap font-medium text-slate-700">Off-Duty</x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap font-medium text-slate-700">Sleeper</x-base.table.th>
                        <x-base.table.th class="whitespace-nowrap font-medium text-slate-700 text-center">Status</x-base.table.th>
                    </x-base.table.tr>
                </x-base.table.thead>
                <x-base.table.tbody>
                    @forelse($dailyLogs as $log)
                        @php
                            $dH = floor(($log->total_driving_minutes ?? 0) / 60);
                            $dM = ($log->total_driving_minutes ?? 0) % 60;
                            $onH = floor(($log->total_on_duty_minutes ?? 0) / 60);
                            $onM = ($log->total_on_duty_minutes ?? 0) % 60;
                            $offH = floor(($log->total_off_duty_minutes ?? 0) / 60);
                            $offM = ($log->total_off_duty_minutes ?? 0) % 60;
                            $slH = floor(($log->total_sleeper_minutes ?? 0) / 60);
                            $slM = ($log->total_sleeper_minutes ?? 0) % 60;
                        @endphp
                        <x-base.table.tr class="hover:bg-slate-50 {{ $log->has_violations ? 'bg-danger/5' : '' }}">
                            <x-base.table.td class="font-medium">
                                {{ $log->date instanceof \Carbon\Carbon ? $log->date->format('M d, Y (D)') : \Carbon\Carbon::parse($log->date)->format('M d, Y (D)') }}
                            </x-base.table.td>
                            <x-base.table.td>
                                <span class="font-semibold text-primary">{{ $dH }}h {{ $dM }}m</span>
                            </x-base.table.td>
                            <x-base.table.td>
                                <span class="text-warning">{{ $onH }}h {{ $onM }}m</span>
                            </x-base.table.td>
                            <x-base.table.td>
                                <span class="text-slate-500">{{ $offH }}h {{ $offM }}m</span>
                            </x-base.table.td>
                            <x-base.table.td>
                                <span class="text-slate-400">{{ $slH }}h {{ $slM }}m</span>
                            </x-base.table.td>
                            <x-base.table.td class="text-center">
                                @if($log->has_violations)
                                    <x-base.badge variant="danger">Violation</x-base.badge>
                                @else
                                    <x-base.badge variant="success">Compliant</x-base.badge>
                                @endif
                            </x-base.table.td>
                        </x-base.table.tr>
                    @empty
                        <x-base.table.tr>
                            <x-base.table.td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center text-slate-500">
                                    <x-base.lucide class="h-10 w-10 text-slate-300 mb-3" icon="Clock" />
                                    <p class="font-medium">No daily logs found</p>
                                    <p class="text-sm mt-1">No HOS data for this driver in the selected period</p>
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                    @endforelse
                </x-base.table.tbody>
            </x-base.table>
        </div>

        @if($dailyLogs->hasPages())
            <div class="p-6 border-t border-slate-200">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-slate-600">
                        Showing {{ $dailyLogs->firstItem() }} to {{ $dailyLogs->lastItem() }} of {{ $dailyLogs->total() }} days
                    </div>
                    <div>{{ $dailyLogs->links() }}</div>
                </div>
            </div>
        @endif
    </div>
@endsection
