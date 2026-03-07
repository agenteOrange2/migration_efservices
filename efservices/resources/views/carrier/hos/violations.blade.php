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
            <div class="flex flex-col sm:flex-row gap-3">
                <x-base.button as="a" href="{{ route('carrier.hos.dashboard') }}" variant="secondary"
                    class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                    Back to Dashboard
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Filter" />
            <h2 class="text-lg font-semibold text-slate-800">Filters</h2>
        </div>
        <form method="GET" action="{{ route('carrier.hos.violations') }}"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <x-base.form-label for="start_date">From Date</x-base.form-label>
                <x-base.litepicker id="start_date" name="start_date"
                    value="{{ $startDate ?? request('start_date') }}" class="w-full" placeholder="Select Date" />
            </div>
            <div>
                <x-base.form-label for="end_date">To Date</x-base.form-label>
                <x-base.litepicker id="end_date" name="end_date"
                    value="{{ $endDate ?? request('end_date') }}" class="w-full" placeholder="Select Date" />
            </div>
            <div class="flex items-end gap-3 md:col-span-2">
                <x-base.button type="submit" variant="primary" class="flex-1 gap-2">
                    <x-base.lucide class="w-4 h-4" icon="Search" />
                    Apply Filters
                </x-base.button>
                <x-base.button as="a" href="{{ route('carrier.hos.violations') }}" variant="outline-secondary"
                    class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="X" />
                    Clear
                </x-base.button>
            </div>
        </form>
    </div>

    <!-- Violations Table -->
    <div class="box box--stacked">
        <div class="overflow-x-auto">

            @if (!isset($violations) || $violations->isEmpty())
                <div class="text-center py-12">
                    <div class="p-4 bg-success/10 rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                        <x-base.lucide class="w-10 h-10 text-success" icon="ShieldCheck" />
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">No Violations Found</h3>
                    <p class="text-slate-500">All drivers are in compliance with HOS regulations.</p>
                </div>
            @else
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
                        @foreach ($violations as $violation)
                            <tr class="border-b border-slate-200/60 hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-4 border-slate-200/60">
                                    <div class="font-medium text-slate-800">
                                        {{ $violation->violation_date?->format('M d, Y') ?? 'N/A' }} {{ $violation->created_at?->format('h:i A') ?? '' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 border-slate-200/60">
                                    <div class="font-medium text-slate-800">
                                        {{ $violation->driver->full_name ?? ($violation->driver->user->name ?? 'N/A') . ' ' . ($violation->driver->last_name ?? '') }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 border-slate-200/60">
                                    <div class="font-medium text-slate-700">
                                        {{ $violation->violation_type_name ?? ucfirst(str_replace('_', ' ', $violation->violation_type ?? 'N/A')) }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 border-slate-200/60">
                                    @if ($violation->violation_severity === 'critical')
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
                                    <span class="text-slate-600 text-sm">
                                        {{ $violation->fmcsa_rule_reference ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 border-slate-200/60">
                                    <span class="text-slate-600 text-sm">
                                        {{ $violation->penalty_type ?: 'none' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 border-slate-200/60 text-center">
                                    @if ($violation->acknowledged)
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                            <x-base.lucide class="mr-1 h-3 w-3" icon="CheckCircle" />
                                            Acknowledged
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                            <x-base.lucide class="mr-1 h-3 w-3" icon="Clock" />
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 border-slate-200/60 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if (!$violation->acknowledged)
                                            <form action="{{ route('carrier.violations.acknowledge', $violation) }}" method="POST" class="inline">
                                                @csrf
                                                <x-base.button type="submit" variant="outline-primary" size="sm" class="gap-1.5 text-xs">
                                                    <x-base.lucide class="w-3 h-3" icon="Check" />
                                                    Acknowledge
                                                </x-base.button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400 italic">No action needed</span>
                                        @endif
                                        <a href="{{ route('carrier.hos.driver.log', $violation->user_driver_detail_id) }}" 
                                            class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                            title="View Driver Log">
                                            <x-base.lucide class="w-4 h-4" icon="Eye" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if (isset($violations) && method_exists($violations, 'hasPages') && $violations->hasPages())
                    <div class="flex items-center justify-between p-6 border-t border-slate-200/60 bg-slate-50/50">
                        <div class="text-sm text-slate-600">
                            Showing <span class="font-medium">{{ $violations->firstItem() }}</span> to 
                            <span class="font-medium">{{ $violations->lastItem() }}</span> of 
                            <span class="font-medium">{{ $violations->total() }}</span> violations
                        </div>
                        <div>{{ $violations->appends(request()->query())->links() }}</div>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
