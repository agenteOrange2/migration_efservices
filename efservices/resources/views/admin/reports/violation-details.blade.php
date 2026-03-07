@extends('../themes/' . $activeTheme)
@section('title', 'Violation Details')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Violations Report', 'url' => route('admin.reports.violations')],
        ['label' => 'Violation Details', 'active' => true],
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
                    <h1 class="text-2xl font-bold text-slate-800">Violation Details</h1>
                    <p class="text-slate-600 mt-1">{{ $violation->violation_type_name }} - {{ $violation->violation_date->format('F d, Y') }}</p>
                </div>
            </div>
            <div class="flex gap-3">
                <x-base.button as="a" href="{{ route('admin.reports.violations') }}" variant="outline-secondary" class="gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Violations Report
                </x-base.button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <!-- Main Content -->
        <div class="col-span-12 lg:col-span-8">
            <!-- Violation Info -->
            <div class="box box--stacked p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Violation Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-slate-500">Violation Type</label>
                        <p class="font-medium">{{ $violation->violation_type_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">Severity</label>
                        <p><x-base.badge variant="{{ $violation->severity_color }}">{{ $violation->severity_name }}</x-base.badge></p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">Date & Time</label>
                        <p class="font-medium">{{ $violation->violation_date->format('M d, Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">Hours Exceeded</label>
                        <p class="font-medium text-danger">{{ $violation->formatted_hours_exceeded }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">Status</label>
                        <p>
                            @if($violation->acknowledged)
                                <x-base.badge variant="success">Acknowledged</x-base.badge>
                            @else
                                <x-base.badge variant="warning">Pending</x-base.badge>
                            @endif
                        </p>
                    </div>
                    @if($violation->acknowledged_at)
                        <div>
                            <label class="text-sm text-slate-500">Acknowledged At</label>
                            <p class="font-medium">{{ $violation->acknowledged_at->format('M d, Y H:i') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Violation Description -->
            <div class="box box--stacked p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Description</h3>
                <div class="p-4 bg-slate-50 rounded-lg">
                    <p class="text-slate-700">{{ $violation->description ?? 'No description available.' }}</p>
                </div>
                @if($violation->notes)
                    <div class="mt-4">
                        <label class="text-sm text-slate-500">Notes</label>
                        <p class="mt-1">{{ $violation->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Related HOS Entry -->
            @if($violation->hosEntry)
                <div class="box box--stacked p-6">
                    <h3 class="text-lg font-semibold mb-4 border-b pb-3">Related HOS Entry</h3>
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-slate-500">Status</label>
                                <p class="font-medium capitalize">{{ str_replace('_', ' ', $violation->hosEntry->status) }}</p>
                            </div>
                            <div>
                                <label class="text-sm text-slate-500">Time</label>
                                <p class="font-medium">{{ $violation->hosEntry->start_time?->format('H:i') }} - {{ $violation->hosEntry->end_time?->format('H:i') ?? 'Ongoing' }}</p>
                            </div>
                            @if($violation->hosEntry->location)
                                <div class="col-span-2">
                                    <label class="text-sm text-slate-500">Location</label>
                                    <p class="font-medium">{{ $violation->hosEntry->location }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-span-12 lg:col-span-4">
            <!-- Driver Info -->
            <div class="box box--stacked p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Driver</h3>
                @if($violation->driver)
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="text-primary font-semibold">
                                {{ strtoupper(substr($violation->driver->user->name ?? 'D', 0, 1)) }}{{ strtoupper(substr($violation->driver->last_name ?? 'R', 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <p class="font-medium">{{ $violation->driver->full_name ?? 'N/A' }}</p>
                            <p class="text-sm text-slate-500">{{ $violation->driver->user->email ?? '' }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-slate-500">No driver information</p>
                @endif
            </div>

            <!-- Carrier Info -->
            <div class="box box--stacked p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Carrier</h3>
                @if($violation->carrier)
                    <p class="font-medium">{{ $violation->carrier->name }}</p>
                    <p class="text-sm text-slate-500">DOT: {{ $violation->carrier->dot_number ?? 'N/A' }}</p>
                    <p class="text-sm text-slate-500">MC: {{ $violation->carrier->mc_number ?? 'N/A' }}</p>
                @else
                    <p class="text-slate-500">No carrier information</p>
                @endif
            </div>

            <!-- Severity Explanation -->
            <div class="box box--stacked p-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Severity Level</h3>
                @if($violation->severity === 'critical')
                    <div class="p-4 bg-danger/10 rounded-lg border border-danger/20">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-3 h-3 rounded-full bg-danger"></div>
                            <span class="font-medium text-danger">Critical</span>
                        </div>
                        <p class="text-sm text-slate-600">This violation requires immediate attention and may result in out-of-service orders.</p>
                    </div>
                @elseif($violation->severity === 'moderate')
                    <div class="p-4 bg-warning/10 rounded-lg border border-warning/20">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-3 h-3 rounded-full bg-warning"></div>
                            <span class="font-medium text-warning">Moderate</span>
                        </div>
                        <p class="text-sm text-slate-600">This violation should be addressed promptly to maintain compliance.</p>
                    </div>
                @else
                    <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <span class="font-medium text-yellow-700">Minor</span>
                        </div>
                        <p class="text-sm text-slate-600">This is a minor violation that should be noted for record-keeping.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
