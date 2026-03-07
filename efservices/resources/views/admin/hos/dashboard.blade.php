@extends('../themes/' . $activeTheme)
@section('title', 'HOS Overview')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Hours of Service', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div>
        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="Clock" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Hours of Service</h1>
                        <p class="text-slate-600">System-wide HOS monitoring across all carriers</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('admin.hos.documents.index') }}" class="w-full sm:w-auto"
                        variant="outline-primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="FileText" />
                        Documents
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.hos.violations') }}" class="w-full sm:w-auto"
                        variant="outline-danger">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="AlertTriangle" />
                        Violations ({{ $totalTodayViolations }} today)
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-primary" icon="Building2" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Total Carriers</div>
                        <div class="text-2xl font-bold text-slate-800">{{ $carrierSummaries->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-success/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-success" icon="Users" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Active Drivers</div>
                        <div class="text-2xl font-bold text-slate-800">{{ $carrierSummaries->sum('active_drivers') }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-danger/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-danger" icon="AlertCircle" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Today's Violations</div>
                        <div class="text-2xl font-bold text-danger">{{ $totalTodayViolations }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-warning/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-warning" icon="Calendar" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Month Violations</div>
                        <div class="text-2xl font-bold text-warning">{{ $carrierSummaries->sum('month_violations') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carriers Table -->
        <div class="box box--stacked">
            <div class="box-header flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-5 border-b border-slate-200/60">
                <h2 class="text-lg font-semibold text-slate-800">Carriers Overview</h2>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                    <div class="relative">
                        <x-base.lucide class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" icon="Search" />
                        <input type="text" id="carrier-search" placeholder="Search carrier..." 
                               class="rounded-[0.5rem] pl-9 w-full" />
                    </div>
                    <select id="violation-filter" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="all">All Carriers</option>
                        <option value="with_violations">With Violations Today</option>
                        <option value="no_violations">No Violations Today</option>
                    </select>
                </div>
            </div>
            <div class="box-body p-5">
                @if($carrierSummaries->isEmpty())
                    <div class="text-center py-10">
                        <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="Building2" />
                        <p class="text-slate-500">No carriers found</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left" id="carriers-table">
                            <thead class="text-slate-500 border-b border-slate-200/60">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Carrier</th>
                                    <th class="px-4 py-3 font-medium text-center">Active Drivers</th>
                                    <th class="px-4 py-3 font-medium text-center">Today's Violations</th>
                                    <th class="px-4 py-3 font-medium text-center">Month Violations</th>
                                    <th class="px-4 py-3 font-medium text-center">HOS Limits</th>
                                    <th class="px-4 py-3 font-medium text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($carrierSummaries as $summary)
                                    <tr class="border-b border-slate-200/60 hover:bg-slate-50 @if($summary['today_violations'] > 0) bg-danger/5 @endif"
                                        data-carrier-name="{{ $summary['carrier']->name }}"
                                        data-has-violations="{{ $summary['today_violations'] > 0 ? '1' : '0' }}">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                    <x-base.lucide class="w-5 h-5 text-primary" icon="Building2" />
                                                </div>
                                                <div>
                                                    <div class="font-medium text-slate-800">{{ $summary['carrier']->name }}</div>
                                                    <div class="text-xs text-slate-500">ID: {{ $summary['carrier']->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                {{ $summary['active_drivers'] }} drivers
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if($summary['today_violations'] > 0)
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-danger/10 text-danger">
                                                    {{ $summary['today_violations'] }} violations
                                                </span>
                                            @else
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                                    0 violations
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if($summary['month_violations'] > 0)
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                                    {{ $summary['month_violations'] }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">0</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <div class="text-sm">
                                                <span class="text-slate-600">{{ $summary['config']->max_driving_hours }}h driving</span>
                                                <span class="text-slate-400 mx-1">/</span>
                                                <span class="text-slate-600">{{ $summary['config']->max_duty_hours }}h duty</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <div class="flex justify-center items-center gap-1">
                                                <a href="{{ route('admin.hos.carrier.detail', $summary['carrier']->id) }}" 
                                                    class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                                    title="View Details">
                                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('carrier-search');
    const violationFilter = document.getElementById('violation-filter');
    const tableBody = document.querySelector('#carriers-table tbody');
    const rows = tableBody ? tableBody.querySelectorAll('tr') : [];

    function filterTable() {
        const search = searchInput.value.toLowerCase().trim();
        const filter = violationFilter.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const carrierName = row.getAttribute('data-carrier-name') || '';
            const hasViolations = row.getAttribute('data-has-violations') === '1';

            const matchesSearch = !search || carrierName.toLowerCase().includes(search);
            const matchesFilter = filter === 'all' 
                || (filter === 'with_violations' && hasViolations)
                || (filter === 'no_violations' && !hasViolations);

            if (matchesSearch && matchesFilter) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Show/hide no results message
        let noResults = document.getElementById('no-filter-results');
        if (visibleCount === 0 && rows.length > 0) {
            if (!noResults) {
                noResults = document.createElement('tr');
                noResults.id = 'no-filter-results';
                noResults.innerHTML = '<td colspan="6" class="px-4 py-8 text-center text-slate-500">No carriers match your search</td>';
                tableBody.appendChild(noResults);
            }
            noResults.style.display = '';
        } else if (noResults) {
            noResults.style.display = 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (violationFilter) violationFilter.addEventListener('change', filterTable);
});
</script>
@endpush
