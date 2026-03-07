@extends('../themes/' . $activeTheme)
@section('title', 'HOS Violations')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'HOS', 'url' => route('carrier.hos.dashboard')],
        ['label' => 'Violations', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Flash Messages -->
@if(session('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
        {{ session('success') }}
    </div>
@endif

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-danger/10 rounded-xl border border-danger/20">
                <x-base.lucide class="w-8 h-8 text-danger" icon="AlertTriangle" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">HOS Violations</h1>
                <p class="text-slate-600">View and manage Hours of Service violations for your drivers</p>
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
    <form action="{{ route('carrier.violations.index') }}" method="GET">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <x-base.form-label for="driver_id">Driver</x-base.form-label>
                <x-base.tom-select id="driver_id" name="driver_id" class="w-full">
                    <option value="">All Drivers</option>
                    @foreach($drivers ?? [] as $driver)
                        <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                            {{ implode(' ', array_filter([$driver->user->name ?? 'Unknown', $driver->middle_name ?? '', $driver->last_name ?? ''])) }}
                        </option>
                    @endforeach
                </x-base.tom-select>
            </div>
            <div>
                <x-base.form-label for="severity">Severity</x-base.form-label>
                <x-base.tom-select id="severity" name="severity" class="w-full">
                    <option value="">All</option>
                    <option value="minor" {{ request('severity') == 'minor' ? 'selected' : '' }}>Minor</option>
                    <option value="moderate" {{ request('severity') == 'moderate' ? 'selected' : '' }}>Moderate</option>
                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                </x-base.tom-select>
            </div>
            <div>
                <x-base.form-label for="date_from">From Date</x-base.form-label>
                <x-base.form-input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" />
            </div>
            <div>
                <x-base.form-label for="date_to">To Date</x-base.form-label>
                <x-base.form-input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" />
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <x-base.button type="submit" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Search" />
                Apply Filters
            </x-base.button>
        </div>
    </form>
</div>

<!-- Violations Table -->
<div class="box box--stacked">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-b border-slate-200/60">
            <thead>
                <tr class="border-b border-slate-200/60 bg-slate-50">
                    <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">Date</th>
                    <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">Driver</th>
                    <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">Type</th>
                    <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">Severity</th>
                    <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">FMCSA Reference</th>
                    <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">Penalty</th>
                    <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap text-center">Status</th>
                    <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($violations as $violation)
                    <tr class="border-b border-slate-200/60">
                        <td class="px-5 py-4 border-slate-200/60">
                            <div class="font-medium">{{ $violation->violation_date?->format('M d, Y') ?? 'N/A' }} {{ $violation->created_at?->format('h:i A') ?? '' }}</div>
                        </td>
                        <td class="px-5 py-4 border-slate-200/60">
                            <div class="font-medium">{{ implode(' ', array_filter([$violation->driver->user->name ?? 'N/A', $violation->driver->middle_name ?? '', $violation->driver->last_name ?? ''])) }}</div>
                        </td>
                        <td class="px-5 py-4 border-slate-200/60">
                            <div class="font-medium">{{ $violation->violation_type_name ?? ucfirst(str_replace('_', ' ', $violation->violation_type ?? 'N/A')) }}</div>
                        </td>
                        <td class="px-5 py-4 border-slate-200/60">
                            @if($violation->violation_severity === 'critical')
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                                    <x-base.lucide class="mr-1 h-3 w-3" icon="XCircle" />
                                    Critical
                                </span>
                            @elseif($violation->violation_severity === 'moderate')
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                    <x-base.lucide class="mr-1 h-3 w-3" icon="AlertTriangle" />
                                    Moderate
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">
                                    <x-base.lucide class="mr-1 h-3 w-3" icon="AlertCircle" />
                                    Minor
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 border-slate-200/60">
                            {{ $violation->fmcsa_rule_reference ?? 'N/A' }}
                        </td>
                        <td class="px-5 py-4 border-slate-200/60">
                            {{ $violation->penalty_type ?: 'none' }}
                        </td>
                        <td class="px-5 py-4 border-slate-200/60 text-center">
                            @if($violation->acknowledged)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                    Acknowledged
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 border-slate-200/60 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('carrier.violations.show', $violation) }}">
                                    <x-base.button variant="outline-secondary" size="sm" class="gap-1.5 text-xs">
                                        <x-base.lucide class="w-3 h-3" icon="Eye" />
                                        View
                                    </x-base.button>
                                </a>
                                @if(!$violation->acknowledged)
                                    <form action="{{ route('carrier.violations.acknowledge', $violation) }}" method="POST" class="inline">
                                        @csrf
                                        <x-base.button type="submit" variant="outline-primary" size="sm" class="gap-1.5 text-xs">
                                            <x-base.lucide class="w-3 h-3" icon="Check" />
                                            Acknowledge
                                        </x-base.button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <x-base.lucide class="h-12 w-12 text-slate-300 mb-3" icon="AlertTriangle" />
                                <div class="text-base font-medium">No violations found</div>
                                <div class="text-sm text-slate-400 mt-1">Try adjusting your filters or date range</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if(isset($violations) && $violations->hasPages())
            <div class="p-6 border-t border-slate-200/60">
                {{ $violations->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
