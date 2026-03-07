@extends('../themes/' . $activeTheme)
@section('title', 'Violation Details')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'HOS', 'url' => route('admin.hos.dashboard')],
        ['label' => 'Violations', 'url' => route('admin.hos.violations')],
        ['label' => 'Details', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
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

<!-- Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 rounded-xl border
                @if($violation->violation_severity === 'critical') bg-danger/10 border-danger/20
                @elseif($violation->violation_severity === 'moderate') bg-warning/10 border-warning/20
                @else bg-yellow-100 border-yellow-200 @endif">
                <x-base.lucide class="w-8 h-8 
                    @if($violation->violation_severity === 'critical') text-danger
                    @elseif($violation->violation_severity === 'moderate') text-warning
                    @else text-yellow-600 @endif" icon="AlertTriangle" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 mb-1">
                    {{ $violation->violation_type_name ?? ucfirst(str_replace('_', ' ', $violation->violation_type ?? 'Violation')) }}
                </h1>
                <p class="text-slate-500">
                    {{ $violation->violation_date?->format('F d, Y') }} at {{ $violation->created_at?->format('h:i A') }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if(!$violation->acknowledged)
                <form action="{{ route('admin.hos.violations.acknowledge', $violation) }}" method="POST">
                    @csrf
                    <x-base.button type="submit" variant="primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Check" />
                        Acknowledge Violation
                    </x-base.button>
                </form>
            @else
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-success/10 text-success border border-success/20">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="CheckCircle" />
                    Acknowledged
                </span>
            @endif
            @if(!$violation->is_forgiven && $violation->has_penalty)
                <a href="{{ route('admin.hos.violations.forgive.form', $violation) }}">
                    <x-base.button variant="warning" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="ShieldOff" />
                        Forgive Violation
                    </x-base.button>
                </a>
            @elseif($violation->is_forgiven)
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-warning/10 text-warning border border-warning/20">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ShieldOff" />
                    Forgiven
                </span>
            @endif
            <a href="{{ route('admin.hos.violations') }}">
                <x-base.button variant="outline-secondary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                    Back to List
                </x-base.button>
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Violation Information -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-danger" icon="AlertOctagon" />
                <h2 class="text-lg font-semibold text-slate-800">Violation Information</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Violation Type</label>
                    <p class="mt-1 text-slate-800 font-medium">
                        {{ $violation->violation_type_name ?? ucfirst(str_replace('_', ' ', $violation->violation_type ?? 'N/A')) }}
                    </p>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Severity</label>
                    <div class="mt-1">
                        @if($violation->violation_severity === 'critical')
                            <span class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-full bg-danger/10 text-danger">
                                <x-base.lucide class="mr-1.5 h-4 w-4" icon="XCircle" />
                                Critical
                            </span>
                        @elseif($violation->violation_severity === 'moderate')
                            <span class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-full bg-warning/10 text-warning">
                                <x-base.lucide class="mr-1.5 h-4 w-4" icon="AlertTriangle" />
                                Moderate
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-full bg-yellow-100 text-yellow-700">
                                <x-base.lucide class="mr-1.5 h-4 w-4" icon="AlertCircle" />
                                Minor
                            </span>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">FMCSA Reference</label>
                    <p class="mt-1 text-slate-800 font-mono text-sm bg-slate-100 px-3 py-2 rounded-lg inline-block">
                        {{ $violation->fmcsa_rule_reference ?? 'N/A' }}
                    </p>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Penalty Type</label>
                    <p class="mt-1 text-slate-800">
                        @if($violation->penalty_type)
                            <span class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-full bg-danger/10 text-danger">
                                {{ ucfirst(str_replace('_', ' ', $violation->penalty_type)) }}
                            </span>
                        @else
                            <span class="text-slate-500">No penalty applied</span>
                        @endif
                    </p>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Description</label>
                    <p class="mt-1 text-slate-700">
                        {{ $violation->description ?? 'No description provided.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Time Details -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Clock" />
                <h2 class="text-lg font-semibold text-slate-800">Time Details</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Violation Date</label>
                    <p class="mt-1 text-slate-800 font-medium">
                        {{ $violation->violation_date?->format('M d, Y') ?? 'N/A' }}
                    </p>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Time Recorded</label>
                    <p class="mt-1 text-slate-800">
                        {{ $violation->created_at?->format('h:i A') ?? 'N/A' }}
                    </p>
                </div>
                @if($violation->penalty_duration_minutes)
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Penalty Duration</label>
                    <p class="mt-1 text-slate-800">
                        {{ floor($violation->penalty_duration_minutes / 60) }}h {{ $violation->penalty_duration_minutes % 60 }}m
                    </p>
                </div>
                @endif
            </div>
        </div>

        <!-- Related Trip -->
        @if($violation->trip)
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-info" icon="MapPin" />
                <h2 class="text-lg font-semibold text-slate-800">Related Trip</h2>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                <div>
                    <p class="font-semibold text-slate-800">{{ $violation->trip->trip_number }}</p>
                    <p class="text-sm text-slate-500">
                        {{ $violation->trip->origin_address ?? 'N/A' }} → {{ $violation->trip->destination_address ?? 'N/A' }}
                    </p>
                </div>
                <a href="{{ route('admin.trips.show', $violation->trip) }}">
                    <x-base.button variant="outline-primary" size="sm" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                        View Trip
                    </x-base.button>
                </a>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Driver Information -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-primary" icon="User" />
                <h2 class="text-lg font-semibold text-slate-800">Driver</h2>
            </div>
            
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center">
                    <x-base.lucide class="w-7 h-7 text-primary" icon="User" />
                </div>
                <div>
                    <p class="font-semibold text-slate-800">
                        {{ implode(' ', array_filter([$violation->driver->user->name ?? 'N/A', $violation->driver->middle_name ?? '', $violation->driver->last_name ?? ''])) }}
                    </p>
                    <p class="text-sm text-slate-500">{{ $violation->driver->user->email ?? 'N/A' }}</p>
                </div>
            </div>
            
            <a href="{{ route('admin.drivers.show', $violation->driver) }}">
                <x-base.button variant="outline-secondary" class="w-full gap-2">
                    <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                    View Driver Profile
                </x-base.button>
            </a>
        </div>

        <!-- Carrier Information -->
        @if($violation->carrier ?? $violation->driver->carrier)
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-info" icon="Building" />
                <h2 class="text-lg font-semibold text-slate-800">Carrier</h2>
            </div>
            
            @php $carrier = $violation->carrier ?? $violation->driver->carrier; @endphp
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 rounded-full bg-info/10 flex items-center justify-center">
                    <x-base.lucide class="w-7 h-7 text-info" icon="Building" />
                </div>
                <div>
                    <p class="font-semibold text-slate-800">{{ $carrier->name ?? 'N/A' }}</p>
                    <p class="text-sm text-slate-500">{{ $carrier->dot_number ?? 'N/A' }}</p>
                </div>
            </div>
            
            <a href="{{ route('admin.carrier.show', $carrier) }}">
                <x-base.button variant="outline-secondary" class="w-full gap-2">
                    <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                    View Carrier
                </x-base.button>
            </a>
        </div>
        @endif

        <!-- Acknowledgment Status -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-success" icon="ClipboardCheck" />
                <h2 class="text-lg font-semibold text-slate-800">Status</h2>
            </div>

            @if($violation->acknowledged)
                <div class="text-center py-4">
                    <div class="w-16 h-16 rounded-full bg-success/10 flex items-center justify-center mx-auto mb-4">
                        <x-base.lucide class="w-8 h-8 text-success" icon="CheckCircle" />
                    </div>
                    <p class="font-semibold text-success mb-2">Acknowledged</p>
                    <p class="text-sm text-slate-500">
                        {{ $violation->acknowledged_at?->format('M d, Y h:i A') ?? 'N/A' }}
                    </p>
                </div>
            @else
                <div class="text-center py-4">
                    <div class="w-16 h-16 rounded-full bg-warning/10 flex items-center justify-center mx-auto mb-4">
                        <x-base.lucide class="w-8 h-8 text-warning" icon="Clock" />
                    </div>
                    <p class="font-semibold text-warning mb-2">Pending Review</p>
                    <p class="text-sm text-slate-500 mb-4">This violation requires acknowledgment</p>
                    <form action="{{ route('admin.hos.violations.acknowledge', $violation) }}" method="POST">
                        @csrf
                        <x-base.button type="submit" variant="primary" class="w-full gap-2">
                            <x-base.lucide class="w-4 h-4" icon="Check" />
                            Acknowledge Now
                        </x-base.button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Forgiveness Status -->
        @if($violation->is_forgiven)
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-warning" icon="ShieldOff" />
                <h2 class="text-lg font-semibold text-slate-800">Forgiveness</h2>
            </div>

            <div class="text-center py-4">
                <div class="w-16 h-16 rounded-full bg-warning/10 flex items-center justify-center mx-auto mb-4">
                    <x-base.lucide class="w-8 h-8 text-warning" icon="ShieldOff" />
                </div>
                <p class="font-semibold text-warning mb-2">Violation Forgiven</p>
                <p class="text-sm text-slate-500 mb-4">
                    {{ $violation->forgiven_at?->format('M d, Y h:i A') ?? 'N/A' }}
                </p>
                @if($violation->forgivenByUser)
                    <p class="text-sm text-slate-500 mb-2">
                        By: {{ $violation->forgivenByUser->name }}
                    </p>
                @endif
                @if($violation->forgiveness_reason)
                    <div class="mt-4 p-3 bg-slate-50 rounded-lg text-left">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Reason</label>
                        <p class="mt-1 text-sm text-slate-700">{{ $violation->forgiveness_reason }}</p>
                    </div>
                @endif
                @if($violation->adjusted_trip_end_time)
                    <div class="mt-3 p-3 bg-slate-50 rounded-lg text-left">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Adjusted End Time</label>
                        <p class="mt-1 text-sm text-slate-700">
                            {{ $violation->original_trip_end_time?->format('M d, Y h:i A') ?? 'N/A' }}
                            <x-base.lucide class="w-4 h-4 inline text-slate-400" icon="ArrowRight" />
                            {{ $violation->adjusted_trip_end_time->format('M d, Y h:i A') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
        @elseif($violation->has_penalty && !$violation->isPenaltyExpired())
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-danger" icon="Lock" />
                <h2 class="text-lg font-semibold text-slate-800">Active Penalty</h2>
            </div>

            <div class="text-center py-4">
                <div class="w-16 h-16 rounded-full bg-danger/10 flex items-center justify-center mx-auto mb-4">
                    <x-base.lucide class="w-8 h-8 text-danger" icon="Lock" />
                </div>
                <p class="font-semibold text-danger mb-2">Driver Blocked</p>
                <p class="text-sm text-slate-500 mb-4">
                    @if($violation->remaining_penalty_hours)
                        {{ floor($violation->remaining_penalty_hours) }}h {{ round(($violation->remaining_penalty_hours - floor($violation->remaining_penalty_hours)) * 60) }}m remaining
                    @else
                        Penalty active
                    @endif
                </p>
                <a href="{{ route('admin.hos.violations.forgive.form', $violation) }}">
                    <x-base.button variant="warning" class="w-full gap-2">
                        <x-base.lucide class="w-4 h-4" icon="ShieldOff" />
                        Forgive Violation
                    </x-base.button>
                </a>
            </div>
        </div>
        @endif

        <!-- Violation Report -->
        @php
            $violationReports = $violation->driver?->getMedia('violation_reports')
                ->filter(fn($m) => $m->getCustomProperty('violation_id') == $violation->id)
                ->sortByDesc('created_at');
        @endphp
        @if($violationReports && $violationReports->isNotEmpty())
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                <h2 class="text-lg font-semibold text-slate-800">Violation Report</h2>
            </div>
            <div class="space-y-3">
                @foreach($violationReports as $report)
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                                <x-base.lucide class="w-4 h-4 text-primary" icon="FileText" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ $report->file_name }}</p>
                                <p class="text-xs text-slate-500">{{ $report->created_at->format('m/d/Y h:i A') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.hos.documents.preview', $report->id) }}" target="_blank"
                               class="p-1.5 text-slate-600 hover:bg-slate-200 rounded-lg transition-colors" title="Preview">
                                <x-base.lucide class="w-4 h-4" icon="Eye" />
                            </a>
                            <a href="{{ route('admin.hos.documents.download', $report->id) }}"
                               class="p-1.5 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="Download">
                                <x-base.lucide class="w-4 h-4" icon="Download" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Quick Stats -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-slate-600" icon="BarChart3" />
                <h2 class="text-lg font-semibold text-slate-800">Record Info</h2>
            </div>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-500">Violation ID</span>
                    <span class="text-sm font-mono text-slate-700">#{{ $violation->id }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-500">Created</span>
                    <span class="text-sm text-slate-700">{{ $violation->created_at?->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-500">Last Updated</span>
                    <span class="text-sm text-slate-700">{{ $violation->updated_at?->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
