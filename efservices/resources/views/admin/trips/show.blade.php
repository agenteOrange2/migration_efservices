@extends('../themes/' . $activeTheme)
@section('title', 'Trip Details - ' . $trip->trip_number)
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Trips', 'url' => route('admin.trips.index')],
        ['label' => 'Trip ' . $trip->trip_number, 'active' => true],
    ];
@endphp
@section('subcontent')

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="Truck" />
                </div>
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-3xl font-bold text-slate-800">{{ $trip->trip_number }}</h1>
                    </div>
                    <p class="text-slate-600">Carrier: {{ $trip->carrier->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <div class="flex items-center justify-center gap-3">
                                            <span @class([
                            'px-3 py-1 rounded-full text-sm font-medium',
                            'bg-warning/20 text-warning' => $trip->status === 'pending',
                            'bg-info/20 text-info' => $trip->status === 'accepted',
                            'bg-primary/20 text-primary' => $trip->status === 'in_progress',
                            'bg-amber-100 text-amber-600' => $trip->status === 'paused',
                            'bg-success/20 text-success' => $trip->status === 'completed',
                            'bg-slate-200 text-slate-600' => $trip->status === 'cancelled',
                        ])>
                            {{ $trip->status_name }}
                        </span>
                        @if($trip->has_violations)
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-danger/20 text-danger">
                                <x-base.lucide class="inline w-4 h-4 mr-1" icon="AlertTriangle" />
                                Has Violations
                            </span>
                        @endif
                </div>
                <x-base.button as="a" href="{{ route('admin.trips.index') }}"
                    variant="outline-secondary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back to Trips
                </x-base.button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <!-- Main Content -->
        <div class="col-span-12 xl:col-span-8">
            <!-- Trip Information -->
            <div class="box box--stacked p-6 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-slate-100 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-slate-600" icon="MapPin" />
                    </div>
                    <h2 class="text-lg font-semibold text-slate-800">Trip Information</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-3 h-3 bg-success rounded-full"></div>
                            <span class="text-sm font-medium text-slate-600">Origin</span>
                        </div>
                        <p class="text-slate-800">{{ $trip->origin_address ?? 'N/A' }}</p>
                        @if($trip->origin_latitude && $trip->origin_longitude)
                            <a href="https://www.google.com/maps?q={{ $trip->origin_latitude }},{{ $trip->origin_longitude }}" 
                               target="_blank" 
                               class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 mt-2">
                                <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                                View on Maps
                            </a>
                        @elseif($trip->origin_address)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($trip->origin_address) }}" 
                               target="_blank" 
                               class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 mt-2">
                                <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                                View on Maps
                            </a>
                        @endif
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-3 h-3 bg-danger rounded-full"></div>
                            <span class="text-sm font-medium text-slate-600">Destination</span>
                        </div>
                        <p class="text-slate-800">{{ $trip->destination_address ?? 'N/A' }}</p>
                        @if($trip->destination_latitude && $trip->destination_longitude)
                            <a href="https://www.google.com/maps?q={{ $trip->destination_latitude }},{{ $trip->destination_longitude }}" 
                               target="_blank" 
                               class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 mt-2">
                                <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                                View on Maps
                            </a>
                        @elseif($trip->destination_address)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($trip->destination_address) }}" 
                               target="_blank" 
                               class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 mt-2">
                                <x-base.lucide class="w-3 h-3" icon="ExternalLink" />
                                View on Maps
                            </a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    <div>
                        <div class="text-sm text-slate-500 mb-1">Driver</div>
                        <div class="font-medium text-slate-800">{{ $trip->driver->user->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-slate-500 mb-1">Vehicle</div>
                        <div class="font-medium text-slate-800">{{ $trip->vehicle->unit_number ?? $trip->vehicle->company_unit_number ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-slate-500 mb-1">Scheduled Start</div>
                        <div class="font-medium text-slate-800">{{ $trip->scheduled_start_date?->format('M d, Y H:i') ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-slate-500 mb-1">Est. Duration</div>
                        <div class="font-medium text-slate-800">{{ $trip->formatted_duration ?? 'N/A' }}</div>
                    </div>
                </div>

                @if($trip->started_at || $trip->completed_at)
                    <div class="border-t border-slate-200 mt-6 pt-6">
                        <h3 class="text-sm font-semibold text-slate-600 mb-4">Actual Times</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @if($trip->started_at)
                                <div>
                                    <div class="text-sm text-slate-500 mb-1">Actual Start</div>
                                    <div class="font-medium text-slate-800">{{ $trip->started_at->format('M d, Y H:i') }}</div>
                                </div>
                            @endif
                            @if($trip->completed_at)
                                <div>
                                    <div class="text-sm text-slate-500 mb-1">Actual End</div>
                                    <div class="font-medium text-slate-800">{{ $trip->completed_at->format('M d, Y H:i') }}</div>
                                </div>
                            @endif
                            @if($trip->actual_duration_minutes)
                                <div>
                                    <div class="text-sm text-slate-500 mb-1">Actual Duration</div>
                                    <div class="font-medium text-slate-800">{{ floor($trip->actual_duration_minutes / 60) }}h {{ $trip->actual_duration_minutes % 60 }}m</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Violations -->
            @if($trip->violations && $trip->violations->isNotEmpty())
                <div class="box box--stacked p-6 mb-6 border-danger/30">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-danger/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-danger" icon="AlertTriangle" />
                        </div>
                        <h2 class="text-lg font-semibold text-danger">Violations ({{ $trip->violations->count() }})</h2>
                    </div>
                    
                    <div class="overflow-auto">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-3 font-medium text-slate-500">Type</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-3 font-medium text-slate-500">Severity</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-3 font-medium text-slate-500">Date</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-3 font-medium text-slate-500">Reference</x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @foreach($trip->violations as $violation)
                                    <x-base.table.tr>
                                        <x-base.table.td class="border-dashed py-3">
                                            {{ $violation->violation_type_name ?? $violation->violation_type }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-3">
                                            <span @class([
                                                'px-2 py-1 rounded text-xs font-medium',
                                                'bg-warning/20 text-warning' => ($violation->violation_severity ?? '') === 'warning',
                                                'bg-danger/20 text-danger' => ($violation->violation_severity ?? '') === 'critical',
                                                'bg-slate-200 text-slate-600' => !in_array($violation->violation_severity ?? '', ['warning', 'critical']),
                                            ])>
                                                {{ ucfirst($violation->violation_severity ?? 'N/A') }}
                                            </span>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-3">
                                            {{ $violation->violation_date?->format('M d, Y') ?? 'N/A' }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-3 font-mono text-xs">
                                            {{ $violation->fmcsa_rule_reference ?? 'N/A' }}
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforeach
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                </div>
            @endif
                        <!-- HOS Entries -->
            @if(isset($hosEntries) && $hosEntries->isNotEmpty())
                <div class="box box--stacked p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="Activity" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">HOS Entries</h2>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($hosEntries as $entry)
                            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                                <div @class([
                                    'w-8 h-8 rounded-full flex items-center justify-center',
                                    'bg-primary/10' => $entry['status'] === 'on_duty_driving',
                                    'bg-warning/10' => $entry['status'] === 'on_duty_not_driving',
                                    'bg-slate-200' => $entry['status'] === 'off_duty',
                                    'bg-info/10' => $entry['status'] === 'sleeper_berth',
                                ])>
                                    <x-base.lucide @class([
                                        'w-4 h-4',
                                        'text-primary' => $entry['status'] === 'on_duty_driving',
                                        'text-warning' => $entry['status'] === 'on_duty_not_driving',
                                        'text-slate-500' => $entry['status'] === 'off_duty',
                                        'text-info' => $entry['status'] === 'sleeper_berth',
                                    ]) icon="{{ match($entry['status']) {
                                        'on_duty_driving' => 'Truck',
                                        'on_duty_not_driving' => 'Clock',
                                        'off_duty' => 'Moon',
                                        'sleeper_berth' => 'Bed',
                                        default => 'Circle'
                                    } }}" />
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-slate-800 text-sm">{{ $entry['status_name'] }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $entry['start_time']->format('H:i') }}
                                        @if($entry['end_time'])
                                            - {{ $entry['end_time']->format('H:i') }}
                                        @else
                                            - En curso
                                        @endif
                                        ({{ $entry['duration_minutes'] }}min)
                                    </div>
                                </div>
                                @if($entry['is_active'])
                                    <span class="px-2 py-1 text-xs font-medium bg-success/20 text-success rounded">Activo</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Notes -->
            @if($trip->notes || $trip->driver_notes)
                <div class="box box--stacked p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="FileText" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Notes</h2>
                    </div>
                    
                    @if($trip->notes)
                        <div class="mb-4">
                            <div class="text-sm font-medium text-slate-600 mb-2">Trip Notes</div>
                            <p class="text-slate-700 bg-slate-50 p-3 rounded-lg">{{ $trip->notes }}</p>
                        </div>
                    @endif
                    
                    @if($trip->driver_notes)
                        <div>
                            <div class="text-sm font-medium text-slate-600 mb-2">Driver Notes</div>
                            <p class="text-slate-700 bg-slate-50 p-3 rounded-lg">{{ $trip->driver_notes }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-span-12 xl:col-span-4">
            <!-- PDF Download -->
            @if($trip->isCompleted())
                <div class="box box--stacked p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="FileText" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Trip Reports</h2>
                    </div>

                    @php
                        $existingPdf = $trip->driver->getTripReportPdf($trip->id);
                        $preTripPdf = $trip->driver->getPreTripInspectionPdf($trip->id);
                        $postTripPdf = $trip->driver->getPostTripInspectionPdf($trip->id);
                    @endphp
                    
                    @if($existingPdf || $preTripPdf || $postTripPdf)
                        <div class="space-y-3">
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
                                                    <span class="text-success">Signed {{ \Carbon\Carbon::parse($existingPdf->getCustomProperty('signed_at'))->format('M d, H:i') }}</span>
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
                        </div>
                    @else
                        <div class="bg-info/10 border border-info/20 rounded-lg p-4">
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-info flex-shrink-0 mt-0.5" icon="Info" />
                                <div class="text-xs text-slate-700">
                                    <p class="font-semibold text-info mb-1">PDFs Not Generated</p>
                                    <p>The driver hasn't generated the trip reports yet.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Timeline -->
            <div class="box box--stacked p-6 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-slate-100 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-slate-600" icon="Clock" />
                    </div>
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
                        <div class="relative flex items-start gap-4 pb-6">
                            <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-success rounded-full">
                                <x-base.lucide class="w-4 h-4 text-white" icon="Plus" />
                            </div>
                            <div>
                                <div class="font-medium text-slate-800">Created</div>
                                <div class="text-sm text-slate-500">{{ $trip->created_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                        
                        @if($trip->accepted_at)
                            <div class="relative flex items-start gap-4 pb-6">
                                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-info rounded-full">
                                    <x-base.lucide class="w-4 h-4 text-white" icon="CheckCircle" />
                                </div>
                                <div>
                                    <div class="font-medium text-slate-800">Accepted</div>
                                    <div class="text-sm text-slate-500">{{ $trip->accepted_at->format('M d, Y H:i') }}</div>
                                </div>
                            </div>
                        @endif
                        
                        @if($trip->started_at)
                            <div class="relative flex items-start gap-4 pb-6">
                                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-primary rounded-full">
                                    <x-base.lucide class="w-4 h-4 text-white" icon="PlayCircle" />
                                </div>
                                <div>
                                    <div class="font-medium text-slate-800">Started</div>
                                    <div class="text-sm text-slate-500">{{ $trip->started_at->format('M d, Y H:i') }}</div>
                                </div>
                            </div>
                        @endif
                        
                        @if($trip->completed_at)
                            <div class="relative flex items-start gap-4">
                                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-success rounded-full">
                                    <x-base.lucide class="w-4 h-4 text-white" icon="CheckCircle2" />
                                </div>
                                <div>
                                    <div class="font-medium text-slate-800">Completed</div>
                                    <div class="text-sm text-slate-500">{{ $trip->completed_at->format('M d, Y H:i') }}</div>
                                </div>
                            </div>
                        @endif
                        
                        @if($trip->status === 'cancelled')
                            <div class="relative flex items-start gap-4">
                                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-danger rounded-full">
                                    <x-base.lucide class="w-4 h-4 text-white" icon="XCircle" />
                                </div>
                                <div>
                                    <div class="font-medium text-slate-800">Cancelled</div>
                                    <div class="text-sm text-slate-500">{{ $trip->cancelled_at?->format('M d, Y H:i') ?? 'N/A' }}</div>
                                    @if($trip->cancellation_reason)
                                        <div class="text-sm text-danger mt-1">{{ $trip->cancellation_reason }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforelse
                </div>
            </div>

            <!-- Ubicaciones HOS y Verificación -->
            <div class="box box--stacked p-6 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-slate-100 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-slate-600" icon="MapPin" />
                    </div>
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
                                            (Threshold: {{ $destinationVerification['threshold_meters'] ?? 500 }}m)
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
                                <a href="{{ route('admin.hos.driver.log', $trip->user_driver_detail_id) }}" 
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
                            <a href="{{ route('admin.hos.driver.log', $trip->user_driver_detail_id) }}" 
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

            <!-- Emergency Trip Control -->
            @if($trip->isAccepted() || $trip->isInProgress() || $trip->isPaused())
                <div class="box box--stacked p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-warning/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-warning" icon="Shield" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Emergency Control</h2>
                    </div>

                    <div class="bg-warning/10 border border-warning/20 rounded-lg p-3 mb-4">
                        <div class="flex items-start gap-2">
                            <x-base.lucide class="w-4 h-4 text-warning flex-shrink-0 mt-0.5" icon="AlertTriangle" />
                            <div class="text-xs text-slate-700">
                                <p class="font-semibold text-warning mb-1">Admin Emergency Control</p>
                                <p>Control driver's trip in case of emergency (lost phone, accident, etc.)</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        @if($trip->canBeStarted())
                            <form action="{{ route('admin.trips.force-start', $trip) }}" method="POST" onsubmit="return confirm('Are you sure you want to start this trip for the driver?');">
                                @csrf
                                <x-base.button type="submit" variant="outline-primary" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Play" />
                                    Force Start Trip
                                </x-base.button>
                            </form>
                        @elseif($trip->isPaused())
                            <form action="{{ route('admin.trips.force-resume', $trip) }}" method="POST" class="mb-2" onsubmit="return confirm('Are you sure you want to resume this trip for the driver?');">
                                @csrf
                                <x-base.button type="submit" variant="outline-success" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Play" />
                                    Force Resume Trip
                                </x-base.button>
                            </form>
                            <form action="{{ route('admin.trips.force-end', $trip) }}" method="POST" onsubmit="return confirm('Are you sure you want to end this trip for the driver? This action cannot be undone.');">
                                @csrf
                                <x-base.button type="submit" variant="outline-danger" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Square" />
                                    Force End Trip
                                </x-base.button>
                            </form>
                        @elseif($trip->canBeEnded())
                            <form action="{{ route('admin.trips.force-pause', $trip) }}" method="POST" class="mb-2" onsubmit="return confirm('Are you sure you want to pause this trip for the driver?');">
                                @csrf
                                <x-base.button type="submit" variant="outline-warning" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Pause" />
                                    Force Pause Trip
                                </x-base.button>
                            </form>
                            <form action="{{ route('admin.trips.force-end', $trip) }}" method="POST" onsubmit="return confirm('Are you sure you want to end this trip for the driver? This action cannot be undone.');">
                                @csrf
                                <x-base.button type="submit" variant="outline-danger" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Square" />
                                    Force End Trip
                                </x-base.button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Load Information -->
            @if($trip->load_type || $trip->load_weight)
                <div class="box box--stacked p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="Package" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Load Information</h2>
                    </div>
                    
                    <div class="space-y-4">
                        @if($trip->load_type)
                            <div>
                                <div class="text-sm text-slate-500 mb-1">Load Type</div>
                                <div class="font-medium text-slate-800">{{ $trip->load_type }}</div>
                            </div>
                        @endif
                        @if($trip->load_weight)
                            <div>
                                <div class="text-sm text-slate-500 mb-1">Weight</div>
                                <div class="font-medium text-slate-800">{{ number_format($trip->load_weight, 2) }} {{ $trip->load_unit ?? 'lbs' }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

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
@endsection
