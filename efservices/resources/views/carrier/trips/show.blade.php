@extends('../themes/' . $activeTheme)
@section('title', 'Trip Details')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')], 
        ['label' => 'Trips', 'url' => route('carrier.trips.index')],
        ['label' => 'Trip Details', 'active' => true],
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
        <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
        {{ session('error') }}
    </div>
@endif

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Truck" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $trip->trip_number ?: 'Trip #' . $trip->id }}</h1>
                <p class="text-slate-600">Trip Details & Information</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            @if($trip->status === 'pending')
                <x-base.badge variant="warning" class="gap-1.5 px-4 py-2">
                    <span class="w-1.5 h-1.5 bg-warning rounded-full"></span>
                    {{ $trip->status_name }}
                </x-base.badge>
            @elseif($trip->status === 'in_progress')
                <x-base.badge variant="primary" class="gap-1.5 px-4 py-2">
                    <span class="w-1.5 h-1.5 bg-primary rounded-full"></span>
                    {{ $trip->status_name }}
                </x-base.badge>
            @elseif($trip->status === 'paused')
                <x-base.badge variant="warning" class="gap-1.5 px-4 py-2">
                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                    Paused
                </x-base.badge>
            @elseif($trip->status === 'completed')
                <x-base.badge variant="success" class="gap-1.5 px-4 py-2">
                    <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                    {{ $trip->status_name }}
                </x-base.badge>
            @else
                <x-base.badge variant="secondary" class="gap-1.5 px-4 py-2">
                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                    {{ $trip->status_name }}
                </x-base.badge>
            @endif
            @if($trip->has_violations)
                <x-base.badge variant="danger" class="gap-1.5 px-4 py-2">
                    <x-base.lucide class="w-4 h-4" icon="AlertTriangle" />
                    Has Violations
                </x-base.badge>
            @endif
            <x-base.button as="a" href="{{ route('carrier.trips.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Trips
            </x-base.button>
        </div>
    </div>
</div>

<div class="grid grid-cols-12 gap-6">
    <!-- Main Content -->
    <div class="col-span-12 lg:col-span-8">
        <!-- Trip Information -->
        <div class="box box--stacked flex flex-col p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                <h2 class="text-lg font-semibold text-slate-800">Trip Information</h2>
            </div>

            <div class="space-y-6">
                <!-- Route Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Origin</label>
                        <div class="flex items-start gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $trip->origin_address ?: 'N/A' }}</p>
                                @if($trip->origin_latitude && $trip->origin_longitude)
                                    <a href="https://www.google.com/maps?q={{ $trip->origin_latitude }},{{ $trip->origin_longitude }}" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 mt-1">
                                        <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                                        View on Maps
                                    </a>
                                @elseif($trip->origin_address)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($trip->origin_address) }}" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 mt-1">
                                        <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                                        View on Maps
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Destination</label>
                        <div class="flex items-start gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $trip->destination_address ?: 'N/A' }}</p>
                                @if($trip->destination_latitude && $trip->destination_longitude)
                                    <a href="https://www.google.com/maps?q={{ $trip->destination_latitude }},{{ $trip->destination_longitude }}" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 mt-1">
                                        <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                                        View on Maps
                                    </a>
                                @elseif($trip->destination_address)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($trip->destination_address) }}" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 mt-1">
                                        <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                                        View on Maps
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Driver & Vehicle -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Driver</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="User" />
                            <p class="text-sm font-semibold text-slate-800">
                                {{ implode(' ', array_filter([$trip->driver->user->name ?? 'N/A', $trip->driver->middle_name ?? '', $trip->driver->last_name ?? ''])) }}
                            </p>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Vehicle</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Truck" />
                            <p class="text-sm font-semibold text-slate-800">
                                @if($trip->vehicle)
                                    {{ $trip->vehicle->company_unit_number ?? ($trip->vehicle->make . ' ' . $trip->vehicle->model) }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Schedule Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Scheduled Start</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                            <p class="text-sm font-semibold text-slate-800">{{ $trip->scheduled_start_date?->format('M d, Y H:i') ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Estimated Duration</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Clock" />
                            <p class="text-sm font-semibold text-slate-800">{{ $trip->formatted_duration ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actual Times (if available) -->
                @if($trip->actual_start_time || $trip->actual_end_time)
                    <div class="border-t border-slate-200/60 pt-6">
                        <h3 class="text-sm font-semibold text-slate-700 mb-4">Actual Times</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @if($trip->actual_start_time)
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Actual Start</label>
                                    <div class="flex items-center gap-2">
                                        <x-base.lucide class="w-4 h-4 text-slate-400" icon="Play" />
                                        <p class="text-sm font-semibold text-slate-800">{{ $trip->actual_start_time->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            @endif
                            @if($trip->actual_end_time)
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Actual End</label>
                                    <div class="flex items-center gap-2">
                                        <x-base.lucide class="w-4 h-4 text-slate-400" icon="Square" />
                                        <p class="text-sm font-semibold text-slate-800">{{ $trip->actual_end_time->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            @endif
                            @if($trip->actual_duration_minutes)
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Actual Duration</label>
                                    <div class="flex items-center gap-2">
                                        <x-base.lucide class="w-4 h-4 text-slate-400" icon="Clock" />
                                        <p class="text-sm font-semibold text-slate-800">{{ floor($trip->actual_duration_minutes / 60) }}h {{ $trip->actual_duration_minutes % 60 }}m</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Description -->
                @if($trip->description)
                    <div class="border-t border-slate-200/60 pt-6">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Description</label>
                        <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $trip->description }}</p>
                    </div>
                @endif

                <!-- Driver Notes -->
                @if($trip->driver_notes)
                    <div class="border-t border-slate-200/60 pt-6">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Driver Notes</label>
                        <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $trip->driver_notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- GPS Statistics -->
        @if(isset($gpsStats) && $gpsStats)
            <div class="box box--stacked flex flex-col p-6 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Navigation" />
                    <h2 class="text-lg font-semibold text-slate-800">GPS Statistics</h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 text-center">
                        <div class="text-2xl font-bold text-slate-800 mb-1">{{ $gpsStats['total_points'] ?? 0 }}</div>
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">GPS Points</div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 text-center">
                        <div class="text-2xl font-bold text-slate-800 mb-1">{{ number_format($gpsStats['total_distance_miles'] ?? 0, 1) }}</div>
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Miles</div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 text-center">
                        <div class="text-2xl font-bold text-slate-800 mb-1">{{ number_format($gpsStats['average_speed_mph'] ?? 0, 1) }}</div>
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Avg Speed (mph)</div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 text-center">
                        <div class="text-2xl font-bold text-slate-800 mb-1">{{ number_format($gpsStats['max_speed_mph'] ?? 0, 1) }}</div>
                        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Max Speed (mph)</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Violations -->
        @if($trip->violations && $trip->violations->isNotEmpty())
            <div class="box box--stacked flex flex-col p-6 border-l-4 border-danger">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-danger" icon="AlertTriangle" />
                    <h2 class="text-lg font-semibold text-slate-800">Violations</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200/60">
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Severity</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Reference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            @foreach($trip->violations as $violation)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-700">{{ $violation->violation_type_name ?? $violation->violation_type ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $severityColor = $violation->severity_color ?? 'secondary';
                                            if($severityColor === 'danger' || strtolower($violation->violation_severity ?? '') === 'high') {
                                                $severityColor = 'danger';
                                            } elseif($severityColor === 'warning' || strtolower($violation->violation_severity ?? '') === 'medium') {
                                                $severityColor = 'warning';
                                            } else {
                                                $severityColor = 'secondary';
                                            }
                                        @endphp
                                        <x-base.badge variant="{{ $severityColor }}">
                                            {{ $violation->severity_name ?? $violation->violation_severity ?? 'N/A' }}
                                        </x-base.badge>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-700">{{ $violation->violation_date?->format('M d, Y') ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-700">{{ $violation->fmcsa_rule_reference ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="col-span-12 lg:col-span-4">
        <!-- Actions -->
        <div class="box box--stacked flex flex-col p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Zap" />
                <h2 class="text-lg font-semibold text-slate-800">Actions</h2>
            </div>

            <div class="space-y-3">
                <!-- PDF Downloads -->
                @if($trip->isCompleted())
                    @php
                        $existingPdf = $trip->driver->getTripReportPdf($trip->id);
                        $preTripPdf = $trip->driver->getPreTripInspectionPdf($trip->id);
                        $postTripPdf = $trip->driver->getPostTripInspectionPdf($trip->id);
                    @endphp
                    
                    @if($existingPdf || $preTripPdf || $postTripPdf)
                        <!-- Trip Summary PDF -->
                        @if($existingPdf)
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div class="flex-shrink-0 w-8 h-8 bg-danger/10 rounded-lg flex items-center justify-center">
                                        <x-base.lucide class="w-4 h-4 text-danger" icon="FileText" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-800 truncate">Trip Summary</p>
                                        <p class="text-xs text-slate-500">
                                            @if($existingPdf->getCustomProperty('signed_at'))
                                                <span class="text-success">Signed</span>
                                            @else
                                                Unsigned
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ $existingPdf->getUrl() }}" target="_blank" class="flex-shrink-0 w-8 h-8 bg-primary hover:bg-primary/90 rounded-lg flex items-center justify-center transition-colors">
                                    <x-base.lucide class="w-4 h-4 text-white" icon="Download" />
                                </a>
                            </div>
                        </div>
                        @endif

                        <!-- Pre-Trip Inspection PDF -->
                        @if($preTripPdf)
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div class="flex-shrink-0 w-8 h-8 bg-success/10 rounded-lg flex items-center justify-center">
                                        <x-base.lucide class="w-4 h-4 text-success" icon="ClipboardCheck" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-800 truncate">Pre-Trip Inspection</p>
                                        <p class="text-xs text-slate-500">
                                            @if($preTripPdf->getCustomProperty('signed_at'))
                                                <span class="text-success">Signed</span>
                                            @else
                                                Unsigned
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ $preTripPdf->getUrl() }}" target="_blank" class="flex-shrink-0 w-8 h-8 bg-success hover:bg-success/90 rounded-lg flex items-center justify-center transition-colors">
                                    <x-base.lucide class="w-4 h-4 text-white" icon="Download" />
                                </a>
                            </div>
                        </div>
                        @endif

                        <!-- Post-Trip Inspection PDF -->
                        @if($postTripPdf)
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div class="flex-shrink-0 w-8 h-8 bg-warning/10 rounded-lg flex items-center justify-center">
                                        <x-base.lucide class="w-4 h-4 text-warning" icon="ClipboardList" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-800 truncate">Post-Trip Inspection</p>
                                        <p class="text-xs text-slate-500">
                                            @if($postTripPdf->getCustomProperty('signed_at'))
                                                <span class="text-success">Signed</span>
                                            @else
                                                Unsigned
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ $postTripPdf->getUrl() }}" target="_blank" class="flex-shrink-0 w-8 h-8 bg-warning hover:bg-warning/90 rounded-lg flex items-center justify-center transition-colors">
                                    <x-base.lucide class="w-4 h-4 text-white" icon="Download" />
                                </a>
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="bg-info/10 border border-info/20 rounded-lg p-3">
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-info flex-shrink-0 mt-0.5" icon="Info" />
                                <div class="text-xs text-slate-700">
                                    <p class="font-semibold text-info mb-1">PDFs Not Generated</p>
                                    <p>The driver hasn't generated the trip reports yet.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                @if($trip->isPending() || $trip->isAccepted())
                    <x-base.button as="a" href="{{ route('carrier.trips.edit', $trip) }}" variant="primary" class="w-full gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Edit" />
                        Edit Trip
                    </x-base.button>
                    <x-base.button 
                        type="button" 
                        variant="danger" 
                        class="w-full gap-2"
                        data-tw-toggle="modal"
                        data-tw-target="#cancel-modal">
                        <x-base.lucide class="w-4 h-4" icon="XCircle" />
                        Cancel Trip
                    </x-base.button>
                @endif

                <!-- Emergency Trip Control -->
                @if($trip->isAccepted() || $trip->isInProgress() || $trip->isPaused())
                    <div class="border-t border-slate-200/60 pt-3 mt-3">
                        <div class="bg-warning/10 border border-warning/20 rounded-lg p-3 mb-3">
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-warning flex-shrink-0 mt-0.5" icon="Shield" />
                                <div class="text-xs text-slate-700">
                                    <p class="font-semibold text-warning mb-1">Emergency Control</p>
                                    <p>Control driver's trip in case of emergency (lost phone, accident, etc.)</p>
                                </div>
                            </div>
                        </div>

                        @if($trip->canBeStarted())
                            <form action="{{ route('carrier.trips.force-start', $trip) }}" method="POST" onsubmit="return confirm('Are you sure you want to start this trip for the driver?');">
                                @csrf
                                <x-base.button type="submit" variant="outline-primary" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Play" />
                                    Force Start Trip
                                </x-base.button>
                            </form>
                        @elseif($trip->isPaused())
                            <form action="{{ route('carrier.trips.force-resume', $trip) }}" method="POST" class="mb-2" onsubmit="return confirm('Are you sure you want to resume this trip for the driver?');">
                                @csrf
                                <x-base.button type="submit" variant="outline-success" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Play" />
                                    Force Resume Trip
                                </x-base.button>
                            </form>
                            <form action="{{ route('carrier.trips.force-end', $trip) }}" method="POST" onsubmit="return confirm('Are you sure you want to end this trip for the driver? This action cannot be undone.');">
                                @csrf
                                <x-base.button type="submit" variant="outline-danger" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Square" />
                                    Force End Trip
                                </x-base.button>
                            </form>
                        @elseif($trip->canBeEnded())
                            <form action="{{ route('carrier.trips.force-pause', $trip) }}" method="POST" class="mb-2" onsubmit="return confirm('Are you sure you want to pause this trip for the driver?');">
                                @csrf
                                <x-base.button type="submit" variant="outline-warning" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Pause" />
                                    Force Pause Trip
                                </x-base.button>
                            </form>
                            <form action="{{ route('carrier.trips.force-end', $trip) }}" method="POST" onsubmit="return confirm('Are you sure you want to end this trip for the driver? This action cannot be undone.');">
                                @csrf
                                <x-base.button type="submit" variant="outline-danger" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Square" />
                                    Force End Trip
                                </x-base.button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Timeline -->
        <div class="box box--stacked flex flex-col p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Clock" />
                <h2 class="text-lg font-semibold text-slate-800">Timeline</h2>
            </div>

            <div class="relative">
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-200"></div>
                
                @forelse($timeline ?? [] as $event)
                    <div class="relative flex items-start gap-4 pb-6 last:pb-0">
                        <div @class([
                            'relative z-10 flex items-center justify-center w-8 h-8 rounded-full',
                            'bg-success' => in_array($event['color'] ?? '', ['success']),
                            'bg-info' => ($event['color'] ?? '') === 'info',
                            'bg-primary' => ($event['color'] ?? '') === 'primary',
                            'bg-warning' => ($event['color'] ?? '') === 'warning',
                            'bg-danger' => ($event['color'] ?? '') === 'danger',
                            'bg-slate-400' => !in_array($event['color'] ?? '', ['success', 'info', 'primary', 'warning', 'danger']),
                        ])>
                            <x-base.lucide class="w-4 h-4 text-white" icon="{{ $event['icon'] ?? 'Circle' }}" />
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-slate-800">{{ $event['title'] ?? 'Event' }}</div>
                            <div class="text-sm text-slate-500">{{ $event['timestamp']?->format('M d, Y H:i') ?? 'N/A' }}</div>
                            @if(!empty($event['description']))
                                <div class="text-sm text-slate-600 mt-1">{{ $event['description'] }}</div>
                            @endif
                            @if(!empty($event['location']))
                                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                    <x-base.lucide class="w-3 h-3" icon="MapPin" />
                                    {{ $event['location'] }}
                                </div>
                            @endif
                            @if(!empty($event['coordinates']))
                                <a href="https://www.google.com/maps?q={{ $event['coordinates']['lat'] }},{{ $event['coordinates']['lng'] }}" 
                                   target="_blank" 
                                   class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 mt-1">
                                    <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                                    Ver en Maps
                                </a>
                            @endif
                            @if(!empty($event['forced_by']))
                                <div class="text-xs text-warning mt-1">
                                    <x-base.lucide class="w-3 h-3 inline" icon="Shield" />
                                    Forzado por: {{ $event['forced_by'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    {{-- Fallback to basic timeline if no timeline data --}}
                    <div class="flex items-start gap-3 pb-4">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="w-8 h-8 rounded-full bg-success/10 flex items-center justify-center">
                                <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                            </div>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-800 text-sm">Created</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $trip->created_at->format('M d, Y H:i') }}</div>
                        </div>
                    </div>

                    @if($trip->accepted_at)
                        <div class="flex items-start gap-3 pb-4">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-8 h-8 rounded-full bg-success/10 flex items-center justify-center">
                                    <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                                </div>
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800 text-sm">Accepted</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $trip->accepted_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                    @endif

                    @if($trip->started_at)
                        <div class="flex items-start gap-3 pb-4">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                                    <x-base.lucide class="w-4 h-4 text-primary" icon="Play" />
                                </div>
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800 text-sm">Started</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $trip->started_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                    @endif

                    @if($trip->completed_at)
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-8 h-8 rounded-full bg-success/10 flex items-center justify-center">
                                    <x-base.lucide class="w-4 h-4 text-success" icon="CheckCircle" />
                                </div>
                            </div>
                            <div>
                                <div class="font-semibold text-slate-800 text-sm">Completed</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $trip->completed_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                    @endif
                @endforelse
            </div>
        </div>

        <!-- Ubicaciones HOS y Verificación -->
        <div class="box box--stacked flex flex-col p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="MapPin" />
                <h2 class="text-lg font-semibold text-slate-800">Ubicaciones HOS</h2>
            </div>
            
            @if($trip->isCompleted() && isset($destinationVerification))
                @if($destinationVerification['has_gps_data'] ?? false)
                    @if($destinationVerification['verified'] ?? false)
                        <div @class([
                            'p-4 rounded-xl border mb-4',
                            'bg-success/10 border-success/20' => $destinationVerification['arrived'] ?? false,
                            'bg-danger/10 border-danger/20' => !($destinationVerification['arrived'] ?? false),
                        ])>
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-10 h-10 rounded-full flex items-center justify-center',
                                    'bg-success' => $destinationVerification['arrived'] ?? false,
                                    'bg-danger' => !($destinationVerification['arrived'] ?? false),
                                ])>
                                    <x-base.lucide class="w-5 h-5 text-white" icon="{{ ($destinationVerification['arrived'] ?? false) ? 'CheckCircle' : 'XCircle' }}" />
                                </div>
                                <div>
                                    <div @class([
                                        'font-semibold',
                                        'text-success' => $destinationVerification['arrived'] ?? false,
                                        'text-danger' => !($destinationVerification['arrived'] ?? false),
                                    ])>
                                        {{ $destinationVerification['message'] ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-slate-600">
                                        Distancia: {{ $destinationVerification['distance_formatted'] ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-xl border bg-warning/10 border-warning/20 mb-4">
                            <div class="flex items-center gap-2 text-warning">
                                <x-base.lucide class="w-5 h-5" icon="AlertTriangle" />
                                <span>{{ $destinationVerification['message'] ?? 'No se pudo verificar' }}</span>
                            </div>
                        </div>
                    @endif
                @endif
            @endif

            {{-- Recent HOS Locations --}}
            @if(isset($recentHosLocations) && $recentHosLocations->isNotEmpty())
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-slate-700">Últimas Ubicaciones HOS</h3>
                        <a href="{{ route('carrier.hos.driver.log', $trip->user_driver_detail_id) }}" 
                           class="text-xs text-primary hover:text-primary/80 flex items-center gap-1">
                            Ver Log HOS Completo
                            <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                        </a>
                    </div>
                    <div class="space-y-2">
                        @foreach($recentHosLocations as $location)
                            <a href="{{ $location['maps_url'] }}" target="_blank" 
                                   class="flex items-center gap-3 p-2 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors text-sm">
                                    <div @class([
                                        'w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0',
                                        'bg-success/10' => $location['status'] === 'on_duty_driving',
                                        'bg-warning/10' => $location['status'] === 'on_duty_not_driving',
                                        'bg-slate-200' => $location['status'] === 'off_duty',
                                    ])>
                                        <x-base.lucide @class([
                                            'w-3 h-3',
                                            'text-success' => $location['status'] === 'on_duty_driving',
                                            'text-warning' => $location['status'] === 'on_duty_not_driving',
                                            'text-slate-500' => $location['status'] === 'off_duty',
                                        ]) icon="MapPin" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs text-slate-600 truncate">
                                            {{ number_format($location['latitude'], 6) }}, {{ number_format($location['longitude'], 6) }}
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            {{ $location['start_time']->format('M d, H:i') }} - {{ $location['status_name'] }}
                                        </div>
                                    </div>
                                    <x-base.lucide class="w-3 h-3 text-slate-400 flex-shrink-0" icon="ExternalLink" />
                                </a>
                            @endforeach
                        </div>
                    </div>
            @elseif(!isset($recentHosLocations) || $recentHosLocations->isEmpty())
                {{-- Show link to HOS log when no coordinates available --}}
                <div class="mb-4">
                    <div class="p-4 rounded-xl border bg-slate-100 border-slate-200 mb-3">
                        <div class="flex items-center gap-2 text-slate-600">
                            <x-base.lucide class="w-5 h-5" icon="Info" />
                            <span>No hay coordenadas GPS disponibles en los registros HOS</span>
                        </div>
                    </div>
                    <a href="{{ route('carrier.hos.driver.log', $trip->user_driver_detail_id) }}" 
                       class="flex items-center gap-2 p-3 bg-info/5 rounded-lg hover:bg-info/10 transition-colors border border-info/20">
                        <div class="w-8 h-8 bg-info/10 rounded-full flex items-center justify-center">
                            <x-base.lucide class="w-4 h-4 text-info" icon="Clock" />
                        </div>
                        <span class="text-sm text-info font-medium">Ver Log HOS del Conductor</span>
                        <x-base.lucide class="w-4 h-4 text-info ml-auto" icon="ExternalLink" />
                    </a>
                </div>
            @endif

            {{-- Google Maps Links --}}
            <div class="space-y-2">
                @if(!empty($googleMapsUrls['origin']))
                    <a href="{{ $googleMapsUrls['origin'] }}" target="_blank" 
                       class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                        <div class="w-8 h-8 bg-success/10 rounded-full flex items-center justify-center">
                            <x-base.lucide class="w-4 h-4 text-success" icon="MapPin" />
                        </div>
                        <span class="text-sm text-slate-700">Ver Origen en Maps</span>
                        <x-base.lucide class="w-4 h-4 text-slate-400 ml-auto" icon="ExternalLink" />
                    </a>
                @endif
                @if(!empty($googleMapsUrls['destination']))
                    <a href="{{ $googleMapsUrls['destination'] }}" target="_blank" 
                       class="flex items-center gap-2 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                        <div class="w-8 h-8 bg-danger/10 rounded-full flex items-center justify-center">
                            <x-base.lucide class="w-4 h-4 text-danger" icon="Flag" />
                        </div>
                        <span class="text-sm text-slate-700">Ver Destino en Maps</span>
                        <x-base.lucide class="w-4 h-4 text-slate-400 ml-auto" icon="ExternalLink" />
                    </a>
                @endif
                @if(!empty($googleMapsUrls['route']))
                    <a href="{{ $googleMapsUrls['route'] }}" target="_blank" 
                       class="flex items-center gap-2 p-3 bg-primary/5 rounded-lg hover:bg-primary/10 transition-colors border border-primary/20">
                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                            <x-base.lucide class="w-4 h-4 text-primary" icon="Route" />
                        </div>
                        <span class="text-sm text-primary font-medium">Ver Ruta Completa</span>
                        <x-base.lucide class="w-4 h-4 text-primary ml-auto" icon="ExternalLink" />
                    </a>
                @endif
            </div>
        </div>

        <!-- Trip Documents -->
        @if($tripReportPdfs && $tripReportPdfs->isNotEmpty())
            <div class="box box--stacked p-6 mt-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-slate-100 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-slate-600" icon="FileText" />
                    </div>
                    <h2 class="text-lg font-semibold text-slate-800">Trip Documents</h2>
                    <span class="ml-auto text-sm text-slate-500">{{ $tripReportPdfs->count() }} document(s)</span>
                </div>
                
                <div class="space-y-3">
                    @foreach($tripReportPdfs as $pdf)
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="flex-shrink-0 w-10 h-10 bg-danger/10 rounded-lg flex items-center justify-center">
                                    <x-base.lucide class="w-5 h-5 text-danger" icon="FileText" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-800 truncate">{{ $pdf->file_name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $pdf->created_at->format('M d, Y H:i') }}
                                        @if($pdf->getCustomProperty('signed_at'))
                                            <span class="text-success">• Signed</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                <a href="{{ $pdf->getUrl() }}" target="_blank" class="w-9 h-9 bg-primary/10 hover:bg-primary/20 rounded-lg flex items-center justify-center transition-colors" title="View">
                                    <x-base.lucide class="w-4 h-4 text-primary" icon="Eye" />
                                </a>
                                <a href="{{ $pdf->getUrl() }}" download class="w-9 h-9 bg-success/10 hover:bg-success/20 rounded-lg flex items-center justify-center transition-colors" title="Download">
                                    <x-base.lucide class="w-4 h-4 text-success" icon="Download" />
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Cancel Trip Modal -->
<x-base.dialog id="cancel-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5 text-center">
            <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="XCircle" />
            <div class="mt-5 text-2xl font-semibold text-slate-800">Cancel Trip</div>
            <div class="mt-2 text-slate-500">
                Are you sure you want to cancel this trip? This action cannot be undone.
            </div>
        </div>
        <form action="{{ route('carrier.trips.destroy', $trip) }}" method="POST" class="px-5 pb-8">
            @csrf
            @method('DELETE')
            <div class="mb-4">
                <x-base.form-label for="reason">Reason for cancellation *</x-base.form-label>
                <x-base.form-textarea id="reason" name="reason" rows="3" required placeholder="Please provide a reason for cancelling this trip..."></x-base.form-textarea>
            </div>
            <div class="flex justify-end gap-2">
                <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Close
                </x-base.button>
                <x-base.button type="submit" variant="danger">
                    Cancel Trip
                </x-base.button>
            </div>
        </form>
    </x-base.dialog.panel>
</x-base.dialog>

@endsection
