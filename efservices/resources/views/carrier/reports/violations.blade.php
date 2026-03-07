@extends('../themes/' . $activeTheme)
@section('title', 'Violations Reports')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Reports', 'url' => route('carrier.reports.index')],
    ['label' => 'Violations', 'active' => true],
];
@endphp

@section('subcontent')
<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-danger/10 rounded-xl border border-danger/20">
                <x-base.lucide class="w-8 h-8 text-danger" icon="AlertTriangle" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">HOS Violations Reports</h1>
                <p class="text-slate-600">Track and manage Hours of Service violations for your drivers</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button 
                as="a" 
                href="{{ route('carrier.reports.violations.export-pdf', request()->query()) }}" 
                variant="success" 
                class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Download" />
                Export to PDF
            </x-base.button>
            <x-base.button 
                as="a" 
                href="{{ route('carrier.reports.index') }}" 
                variant="secondary" 
                class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Reports
            </x-base.button>
        </div>
    </div>
</div>

<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12">
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
            <!-- Total Violations -->
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-danger/10 rounded-xl border border-danger/20">
                        <x-base.lucide class="w-6 h-6 text-danger" icon="AlertTriangle" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Total Violations</p>
                        <p class="text-2xl font-bold text-slate-800">{{ $stats['total_violations'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Acknowledged -->
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-success/10 rounded-xl border border-success/20">
                        <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Acknowledged</p>
                        <p class="text-2xl font-bold text-slate-800">{{ $stats['acknowledged_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Unacknowledged -->
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-warning/10 rounded-xl border border-warning/20">
                        <x-base.lucide class="w-6 h-6 text-warning" icon="Clock" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Unacknowledged</p>
                        <p class="text-2xl font-bold text-slate-800">{{ $stats['unacknowledged_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Acknowledgment Rate -->
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-6 h-6 text-primary" icon="BarChart3" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Acknowledgment Rate</p>
                        <p class="text-2xl font-bold text-slate-800">{{ $stats['acknowledgment_rate'] ?? 0 }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Severity Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-warning/10 rounded-xl border border-warning/20">
                        <x-base.lucide class="w-6 h-6 text-warning" icon="AlertCircle" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Minor</p>
                        <p class="text-2xl font-bold text-slate-800">{{ $stats['by_severity']['minor'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-orange-500/10 rounded-xl border border-orange-500/20">
                        <x-base.lucide class="w-6 h-6 text-orange-500" icon="AlertTriangle" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Moderate</p>
                        <p class="text-2xl font-bold text-slate-800">{{ $stats['by_severity']['moderate'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-danger/10 rounded-xl border border-danger/20">
                        <x-base.lucide class="w-6 h-6 text-danger" icon="XCircle" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Critical</p>
                        <p class="text-2xl font-bold text-slate-800">{{ $stats['by_severity']['critical'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Filter" />
                <h2 class="text-lg font-semibold text-slate-800">Filters</h2>
            </div>
            <form action="{{ route('carrier.reports.violations') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <x-base.form-label>Driver</x-base.form-label>
                    <select name="driver_id" class="form-select w-full rounded-lg border-slate-200">
                        <option value="">All Drivers</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver['id'] }}" {{ request('driver_id') == $driver['id'] ? 'selected' : '' }}>
                                {{ $driver['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-base.form-label>Type</x-base.form-label>
                    <select name="violation_type" class="form-select w-full rounded-lg border-slate-200">
                        <option value="">All Types</option>
                        @foreach ($violationTypes as $type)
                            <option value="{{ $type }}" {{ request('violation_type') == $type ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-base.form-label>Severity</x-base.form-label>
                    <select name="severity" class="form-select w-full rounded-lg border-slate-200">
                        <option value="">All Severities</option>
                        @foreach ($severities as $severity)
                            <option value="{{ $severity }}" {{ request('severity') == $severity ? 'selected' : '' }}>
                                {{ ucfirst($severity) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-base.form-label>From Date</x-base.form-label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                        class="form-control w-full rounded-lg border-slate-200">
                </div>
                <div>
                    <x-base.form-label>To Date</x-base.form-label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                        class="form-control w-full rounded-lg border-slate-200">
                </div>
                <div class="flex items-end gap-3">
                    <x-base.button type="submit" variant="primary" class="flex-1 gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Search" />
                        Apply Filters
                    </x-base.button>
                    <x-base.button 
                        as="a" 
                        href="{{ route('carrier.reports.violations') }}" 
                        variant="outline-secondary" 
                        class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="X" />
                        Clear
                    </x-base.button>
                </div>
            </form>
        </div>

        <!-- Violations Table -->
        <div class="box box--stacked">
            <div class="flex items-center gap-3 p-6 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                <h2 class="text-lg font-semibold text-slate-800">Violations List</h2>
                @if($violations->total() > 0)
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded">
                        {{ $violations->total() }} violation(s)
                    </span>
                @endif
            </div>
            
            <div class="overflow-auto xl:overflow-visible">
                <x-base.table class="border-b border-slate-200/60">
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                Date
                            </x-base.table.td>
                            <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                Driver
                            </x-base.table.td>
                            <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                Type
                            </x-base.table.td>
                            <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                Severity
                            </x-base.table.td>
                            <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                FMCSA Reference
                            </x-base.table.td>
                            <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                Penalty
                            </x-base.table.td>
                            <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 text-center">
                                Status
                            </x-base.table.td>
                            <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 text-center w-32">
                                Actions
                            </x-base.table.td>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @forelse($violations as $violation)
                            <x-base.table.tr class="hover:bg-slate-50 transition-colors">
                                <x-base.table.td class="border-dashed py-4">
                                    <div class="flex items-center gap-2">
                                        <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                                        <span class="font-medium text-slate-800">
                                            {{ $violation->violation_date->format('M d, Y') }}
                                        </span>
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td class="border-dashed py-4">
                                    <div class="flex items-center gap-2">
                                        <x-base.lucide class="w-4 h-4 text-slate-400" icon="User" />
                                        <span class="text-slate-700">
                                            {{ $violation->driver?->full_name ?? 'N/A' }}
                                        </span>
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td class="border-dashed py-4">
                                    <span class="text-slate-700 font-medium">{{ $violation->violation_type_name }}</span>
                                </x-base.table.td>
                                <x-base.table.td class="border-dashed py-4">
                                    @if($violation->violation_severity === 'critical')
                                        <x-base.badge variant="danger" class="gap-1.5">
                                            <x-base.lucide class="w-3 h-3" icon="XCircle" />
                                            Critical
                                        </x-base.badge>
                                    @elseif($violation->violation_severity === 'moderate')
                                        <x-base.badge variant="warning" class="gap-1.5">
                                            <x-base.lucide class="w-3 h-3" icon="AlertTriangle" />
                                            Moderate
                                        </x-base.badge>
                                    @else
                                        <x-base.badge variant="warning" class="gap-1.5 bg-yellow-100 text-yellow-700 border-yellow-200">
                                            <x-base.lucide class="w-3 h-3" icon="AlertCircle" />
                                            Minor
                                        </x-base.badge>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td class="border-dashed py-4">
                                    <span class="text-slate-600 text-sm">
                                        {{ $violation->fmcsa_rule_reference ?: 'N/A' }}
                                    </span>
                                </x-base.table.td>
                                <x-base.table.td class="border-dashed py-4">
                                    @if($violation->penalty_type)
                                        <x-base.badge 
                                            variant="{{ $violation->penalty_type === 'mandatory_rest' ? 'warning' : ($violation->penalty_type === 'suspension' ? 'danger' : 'secondary') }}" 
                                            class="gap-1.5">
                                            {{ ucfirst(str_replace('_', ' ', $violation->penalty_type)) }}
                                        </x-base.badge>
                                    @else
                                        <span class="text-slate-400 text-sm italic">none</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td class="border-dashed py-4 text-center">
                                    @if($violation->acknowledged)
                                        <x-base.badge variant="success" class="gap-1.5">
                                            <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                                            Acknowledged
                                        </x-base.badge>
                                    @else
                                        <x-base.badge variant="warning" class="gap-1.5">
                                            <x-base.lucide class="w-3 h-3" icon="Clock" />
                                            Pending
                                        </x-base.badge>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td class="border-dashed py-4 text-center">
                                    <div class="flex items-center justify-center">
                                        @if($violation->acknowledged)
                                            <span class="text-xs text-slate-400 italic">No action needed</span>
                                        @else
                                            <form action="{{ route('carrier.violations.acknowledge', $violation) }}" method="POST" class="inline" 
                                                  onsubmit="return confirm('Are you sure you want to acknowledge this violation?');">
                                                @csrf
                                                <x-base.button 
                                                    type="submit" 
                                                    variant="outline-primary" 
                                                    size="sm"
                                                    class="gap-1.5 text-xs">
                                                    <x-base.lucide class="w-3 h-3" icon="Check" />
                                                    Acknowledge
                                                </x-base.button>
                                            </form>
                                        @endif
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @empty
                            <x-base.table.tr>
                                <x-base.table.td colspan="8" class="text-center py-12">
                                    <div class="flex flex-col items-center">
                                        <x-base.lucide class="w-16 h-16 text-slate-300 mb-4" icon="AlertTriangle" />
                                        <h3 class="text-lg font-medium text-slate-800 mb-2">No Violations Found</h3>
                                        <p class="text-sm text-slate-500 max-w-sm">
                                            No violations match the current filter criteria. Try adjusting your filters.
                                        </p>
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforelse
                    </x-base.table.tbody>
                </x-base.table>
            </div>
            
            @if($violations->hasPages())
                <div class="flex items-center justify-between p-6 border-t border-slate-200/60 bg-slate-50/50">
                    <div class="text-sm text-slate-600">
                        Showing <span class="font-medium">{{ $violations->firstItem() }}</span> to 
                        <span class="font-medium">{{ $violations->lastItem() }}</span> of 
                        <span class="font-medium">{{ $violations->total() }}</span> violations
                    </div>
                    <div>{{ $violations->appends(request()->query())->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
