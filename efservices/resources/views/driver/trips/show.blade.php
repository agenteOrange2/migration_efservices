@extends('../themes/' . $activeTheme)
@section('title', 'Trip Details')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],    
        ['label' => 'My Trips', 'url' => route('driver.trips.index')],
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
                <x-base.badge variant="amber" class="gap-1.5 px-4 py-2">
                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                    {{ $trip->status_name }}
                    @if($trip->isAutoStopped())
                        (HOS Violation)
                    @endif
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
            <x-base.button as="a" href="{{ route('driver.trips.index') }}" variant="secondary" class="gap-2">
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
                            <p class="text-sm font-semibold text-slate-800">{{ $trip->origin_address ?: 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Destination</label>
                        <div class="flex items-start gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                            <p class="text-sm font-semibold text-slate-800">{{ $trip->destination_address ?: 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Schedule & Vehicle -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Scheduled Start</label>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                            <p class="text-sm font-semibold text-slate-800">{{ $trip->scheduled_start_date->format('M d, Y H:i') }}</p>
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

                <!-- Load Information -->
                @if($trip->load_type || $trip->load_weight)
                    <div class="border-t border-slate-200/60 pt-6">
                        <h3 class="text-sm font-semibold text-slate-700 mb-4">Load Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($trip->load_type)
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Load Type</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $trip->load_type }}</p>
                                </div>
                            @endif
                            @if($trip->load_weight)
                                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Load Weight</label>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ number_format($trip->load_weight) }} {{ $trip->load_unit ?? 'lbs' }}
                                    </p>
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
            </div>
        </div>

        <!-- Trip Documents Section -->
        @if($trip->canUploadDocuments() || $trip->getTripDocuments()->count() > 0)
        <div class="box box--stacked flex flex-col p-6 mb-6">
            <div class="flex items-center justify-between gap-3 mb-6">
                <div class="flex items-center gap-3">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Paperclip" />
                    <h2 class="text-lg font-semibold text-slate-800">Trip Documents</h2>
                </div>
                @php
                    $tripDocuments = $trip->getTripDocuments();
                @endphp
                @if($tripDocuments->count() > 0)
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-1 rounded">
                        {{ $tripDocuments->count() }} document(s)
                    </span>
                @endif
            </div>

            <!-- Existing Documents -->
            @if($tripDocuments->count() > 0)
                <div class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($tripDocuments as $document)
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 hover:border-slate-300 transition-colors">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    @if(str_starts_with($document->mime_type, 'image/'))
                                        <div class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden bg-slate-100">
                                            <img src="{{ $document->getUrl('thumb') }}" alt="{{ $document->file_name }}" class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-danger/10">
                                            <x-base.lucide class="w-6 h-6 text-danger" icon="FileText" />
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-slate-800 text-sm truncate" title="{{ $document->file_name }}">
                                            {{ Str::limit($document->file_name, 25) }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ $document->getCustomProperty('document_type_name', 'Document') }}
                                        </p>
                                        @if($document->getCustomProperty('notes'))
                                            <p class="text-xs text-slate-400 truncate" title="{{ $document->getCustomProperty('notes') }}">
                                                {{ Str::limit($document->getCustomProperty('notes'), 30) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 ml-2">
                                    <a href="{{ route('driver.trips.documents.preview', [$trip, $document->id]) }}" 
                                       target="_blank"
                                       class="w-8 h-8 bg-slate-200 hover:bg-slate-300 rounded-lg flex items-center justify-center transition-colors"
                                       title="Preview">
                                        <x-base.lucide class="w-4 h-4 text-slate-600" icon="Eye" />
                                    </a>
                                    <a href="{{ route('driver.trips.documents.download', [$trip, $document->id]) }}" 
                                       class="w-8 h-8 bg-primary/10 hover:bg-primary/20 rounded-lg flex items-center justify-center transition-colors"
                                       title="Download">
                                        <x-base.lucide class="w-4 h-4 text-primary" icon="Download" />
                                    </a>
                                    @if($trip->canDeleteDocuments())
                                        <form action="{{ route('driver.trips.documents.delete', [$trip, $document->id]) }}" 
                                              method="POST" 
                                              onsubmit="return confirm('Are you sure you want to delete this document?');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="w-8 h-8 bg-danger/10 hover:bg-danger/20 rounded-lg flex items-center justify-center transition-colors"
                                                    title="Delete">
                                                <x-base.lucide class="w-4 h-4 text-danger" icon="Trash2" />
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-6 bg-slate-50/50 rounded-lg border border-dashed border-slate-200 mb-6">
                    <x-base.lucide class="w-10 h-10 text-slate-300 mx-auto mb-2" icon="FileQuestion" />
                    <p class="text-sm text-slate-500">No documents uploaded yet</p>
                </div>
            @endif

            <!-- Upload Button (only if can upload) -->
            @if($trip->canUploadDocuments())
                <div class="border-t border-slate-200 pt-6">
                    <x-base.button 
                        type="button" 
                        variant="outline-primary" 
                        class="gap-2"
                        data-tw-toggle="modal"
                        data-tw-target="#upload-documents-modal">
                        <x-base.lucide class="w-4 h-4" icon="Plus" />
                        Add Documents
                    </x-base.button>
                </div>
            @endif
        </div>
        @endif

        <!-- Actions -->
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Zap" />
                <h2 class="text-lg font-semibold text-slate-800">Trip Actions</h2>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                @if($trip->isPending())
                    <form action="{{ route('driver.trips.accept', $trip) }}" method="POST" class="flex-1">
                        @csrf
                        <x-base.button type="submit" variant="success" class="w-full gap-2">
                            <x-base.lucide class="w-4 h-4" icon="CheckCircle" />
                            Accept Trip
                        </x-base.button>
                    </form>
                    <x-base.button 
                        type="button" 
                        variant="danger" 
                        class="flex-1 gap-2"
                        data-tw-toggle="modal"
                        data-tw-target="#reject-modal">
                        <x-base.lucide class="w-4 h-4" icon="XCircle" />
                        Reject Trip
                    </x-base.button>
                @elseif($trip->canBeStarted())
                    <x-base.button 
                        as="a" 
                        href="{{ route('driver.trips.start.form', $trip) }}" 
                        variant="primary" 
                        class="w-full gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Play" />
                        Start Trip
                    </x-base.button>
                @elseif($trip->canBeEnded())
                    <!-- Trip in Progress or Paused - Show appropriate actions -->
                    <div class="w-full space-y-3">
                        @if($trip->isPaused() && $trip->isAutoStopped())
                            <!-- HOS Violation - Trip Auto-Paused -->
                            <div class="bg-danger/10 border border-danger/20 rounded-lg p-4 mb-3">
                                <div class="flex items-start gap-3">
                                    <x-base.lucide class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" icon="AlertTriangle" />
                                    <div class="text-sm text-slate-700">
                                        <p class="font-semibold text-danger mb-1">⚠️ Trip Paused - HOS Violation</p>
                                        <p class="mb-2">Your trip was automatically paused because you exceeded the HOS driving limit.</p>
                                        <p class="text-xs text-slate-500">
                                            <strong>Reason:</strong> {{ ucwords(str_replace('_', ' ', $trip->auto_stop_reason)) }}<br>
                                            <strong>Paused at:</strong> {{ $trip->auto_stopped_at->format('M d, Y H:i') }}<br>
                                            @if($trip->hos_penalty_end_time)
                                                <strong>Rest required until:</strong> {{ $trip->hos_penalty_end_time->format('M d, Y H:i') }}
                                                @if(!$trip->canBeResumed())
                                                    <br><span class="text-danger font-semibold">{{ $trip->formatted_remaining_penalty }}</span>
                                                @else
                                                    <br><span class="text-success font-semibold">✓ Rest period completed</span>
                                                @endif
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-3">
                                @if($trip->canBeResumed())
                                    <form id="resumeForm" action="{{ route('driver.trips.resume', $trip) }}" method="POST" class="flex-1">
                                        @csrf
                                        <input type="hidden" name="latitude" id="resume_latitude">
                                        <input type="hidden" name="longitude" id="resume_longitude">
                                        <input type="hidden" name="address" id="resume_address">
                                        <x-base.button type="submit" variant="success" class="w-full gap-2">
                                            <x-base.lucide class="w-4 h-4" icon="Play" />
                                            Resume Trip
                                        </x-base.button>
                                    </form>
                                @else
                                    <x-base.button type="button" variant="secondary" class="flex-1 gap-2" disabled>
                                        <x-base.lucide class="w-4 h-4" icon="Clock" />
                                        Resume Blocked ({{ $trip->formatted_remaining_penalty }})
                                    </x-base.button>
                                @endif
                                <x-base.button 
                                    as="a" 
                                    href="{{ route('driver.trips.end.form', $trip) }}" 
                                    variant="warning" 
                                    class="flex-1 gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Square" />
                                    End Trip
                                </x-base.button>
                            </div>
                        @elseif($isOnBreak ?? false)
                            <!-- On Break - Show Resume button -->
                            <div class="bg-warning/10 border border-warning/20 rounded-lg p-4 mb-3">
                                <div class="flex items-start gap-3">
                                    <x-base.lucide class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" icon="Coffee" />
                                    <div class="text-sm text-slate-700">
                                        <p class="font-semibold text-warning mb-1">Trip Paused</p>
                                        <p>Your trip is currently paused. Resume driving when ready or end the trip.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-3">
                                <form id="resumeForm" action="{{ route('driver.trips.resume', $trip) }}" method="POST" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="latitude" id="resume_latitude">
                                    <input type="hidden" name="longitude" id="resume_longitude">
                                    <input type="hidden" name="address" id="resume_address">
                                    <x-base.button type="submit" variant="success" class="w-full gap-2">
                                        <x-base.lucide class="w-4 h-4" icon="Play" />
                                        Resume Driving
                                    </x-base.button>
                                </form>
                                <x-base.button 
                                    as="a" 
                                    href="{{ route('driver.trips.end.form', $trip) }}" 
                                    variant="warning" 
                                    class="flex-1 gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Square" />
                                    End Trip
                                </x-base.button>
                            </div>
                        @else
                            <!-- Driving - Show Pause button -->
                            <div class="bg-info/10 border border-info/20 rounded-lg p-4 mb-3">
                                <div class="flex items-start gap-3">
                                    <x-base.lucide class="w-5 h-5 text-info flex-shrink-0 mt-0.5" icon="Info" />
                                    <div class="text-sm text-slate-700">
                                        <p class="font-semibold text-info mb-1">Trip in Progress</p>
                                        <p>You can pause this trip or end it when completed.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-3">
                                <form id="pauseForm" action="{{ route('driver.trips.pause', $trip) }}" method="POST" class="flex-1">
                                    @csrf
                                    <input type="hidden" name="latitude" id="pause_latitude">
                                    <input type="hidden" name="longitude" id="pause_longitude">
                                    <input type="hidden" name="address" id="pause_address">
                                    <x-base.button type="submit" variant="outline-warning" class="w-full gap-2">
                                        <x-base.lucide class="w-4 h-4" icon="Pause" />
                                        Pause Trip
                                    </x-base.button>
                                </form>
                                <x-base.button 
                                    as="a" 
                                    href="{{ route('driver.trips.end.form', $trip) }}" 
                                    variant="warning" 
                                    class="flex-1 gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="Square" />
                                    End Trip
                                </x-base.button>
                            </div>
                        @endif
                    </div>
                @elseif($trip->isCompleted())
                    <!-- Completed Trip - Show PDF Actions -->
                    <div class="w-full space-y-3">
                        @php
                            $existingPdf = $trip->driver->getTripReportPdf($trip->id);
                            $preTripPdf = $trip->driver->getPreTripInspectionPdf($trip->id);
                            $postTripPdf = $trip->driver->getPostTripInspectionPdf($trip->id);
                        @endphp
                        
                        @if($existingPdf)
                            <!-- Trip Summary PDF -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="flex-shrink-0 w-10 h-10 bg-danger/10 rounded-lg flex items-center justify-center">
                                            <x-base.lucide class="w-5 h-5 text-danger" icon="FileText" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-slate-800 truncate">Trip Summary Report</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $existingPdf->created_at->format('M d, Y H:i') }}
                                                @if($existingPdf->getCustomProperty('signed_at'))
                                                    <span class="text-success">• Signed</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ $existingPdf->getUrl() }}" download class="flex-shrink-0 w-9 h-9 bg-primary hover:bg-primary/90 rounded-lg flex items-center justify-center transition-colors">
                                        <x-base.lucide class="w-4 h-4 text-white" icon="Download" />
                                    </a>
                                </div>
                            </div>

                            <!-- Pre-Trip Inspection PDF -->
                            @if($preTripPdf)
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="flex-shrink-0 w-10 h-10 bg-success/10 rounded-lg flex items-center justify-center">
                                            <x-base.lucide class="w-5 h-5 text-success" icon="ClipboardCheck" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-slate-800 truncate">Pre-Trip Inspection</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $preTripPdf->created_at->format('M d, Y H:i') }}
                                                @if($preTripPdf->getCustomProperty('signed_at'))
                                                    <span class="text-success">• Signed</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ $preTripPdf->getUrl() }}" download class="flex-shrink-0 w-9 h-9 bg-success hover:bg-success/90 rounded-lg flex items-center justify-center transition-colors">
                                        <x-base.lucide class="w-4 h-4 text-white" icon="Download" />
                                    </a>
                                </div>
                            </div>
                            @endif

                            <!-- Post-Trip Inspection PDF -->
                            @if($postTripPdf)
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="flex-shrink-0 w-10 h-10 bg-warning/10 rounded-lg flex items-center justify-center">
                                            <x-base.lucide class="w-5 h-5 text-warning" icon="ClipboardList" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-slate-800 truncate">Post-Trip Inspection</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $postTripPdf->created_at->format('M d, Y H:i') }}
                                                @if($postTripPdf->getCustomProperty('signed_at'))
                                                    <span class="text-success">• Signed</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ $postTripPdf->getUrl() }}" download class="flex-shrink-0 w-9 h-9 bg-warning hover:bg-warning/90 rounded-lg flex items-center justify-center transition-colors">
                                        <x-base.lucide class="w-4 h-4 text-white" icon="Download" />
                                    </a>
                                </div>
                            </div>
                            @endif

                            <x-base.button 
                                type="button" 
                                onclick="Livewire.dispatch('openTripSignatureModal')"
                                variant="outline-primary" 
                                class="w-full gap-2">
                                <x-base.lucide class="w-4 h-4" icon="PenTool" />
                                Re-sign & Regenerate PDFs
                            </x-base.button>
                        @else
                            <!-- No PDF - show sign button -->
                            <div class="bg-info/10 border border-info/20 rounded-lg p-4 mb-3">
                                <div class="flex items-start gap-3">
                                    <x-base.lucide class="w-5 h-5 text-info flex-shrink-0 mt-0.5" icon="FileText" />
                                    <div class="text-sm text-slate-700">
                                        <p class="font-semibold text-info mb-1">Trip Reports Ready</p>
                                        <p>Sign to generate: Trip Summary, Pre-Trip Inspection, and Post-Trip Inspection reports.</p>
                                    </div>
                                </div>
                            </div>
                            <x-base.button 
                                type="button" 
                                onclick="Livewire.dispatch('openTripSignatureModal')"
                                variant="primary" 
                                class="w-full gap-2">
                                <x-base.lucide class="w-4 h-4" icon="PenTool" />
                                Sign Trip Reports
                            </x-base.button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar - FMCSA Status -->
    <div class="col-span-12 lg:col-span-4">
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Shield" />
                <h2 class="text-lg font-semibold text-slate-800">FMCSA Status</h2>
            </div>

            <div class="space-y-6">
                <!-- Driving Hours -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide">Daily Driving (12h max)</label>
                        <span class="text-sm font-semibold text-slate-700">
                            {{ round($fmcsaStatus['driving_limit']['remaining_hours'], 1) }}h left
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full {{ $fmcsaStatus['driving_limit']['percentage_used'] >= 90 ? 'bg-danger' : ($fmcsaStatus['driving_limit']['percentage_used'] >= 75 ? 'bg-warning' : 'bg-success') }}" 
                             style="width: {{ min($fmcsaStatus['driving_limit']['percentage_used'], 100) }}%"></div>
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                        {{ round($fmcsaStatus['driving_limit']['percentage_used'], 1) }}% used
                    </div>
                </div>

                <!-- Duty Period -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide">Duty Period (14h max)</label>
                        <span class="text-sm font-semibold text-slate-700">
                            {{ $fmcsaStatus['duty_period']['duty_period_active'] ? round($fmcsaStatus['duty_period']['remaining_hours'], 1) . 'h left' : 'Not started' }}
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2.5">
                        @php
                            $dutyPercentage = $fmcsaStatus['duty_period']['percentage_used'] ?? 0;
                            $dutyColor = $dutyPercentage >= 90 ? 'bg-danger' : ($dutyPercentage >= 75 ? 'bg-warning' : 'bg-success');
                        @endphp
                        <div class="h-2.5 rounded-full {{ $dutyColor }}" 
                             style="width: {{ min($dutyPercentage, 100) }}%"></div>
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                        {{ round($dutyPercentage, 1) }}% used
                    </div>
                </div>

                <!-- Weekly Cycle -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide">
                            Weekly Cycle ({{ $fmcsaStatus['weekly_cycle']['hours_limit'] }}h)
                        </label>
                        <span class="text-sm font-semibold text-slate-700">
                            {{ round($fmcsaStatus['weekly_cycle']['hours_remaining'], 1) }}h left
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2.5">
                        @php
                            $weeklyPercentage = $fmcsaStatus['weekly_cycle']['percentage_used'];
                            $weeklyColor = $weeklyPercentage >= 90 ? 'bg-danger' : ($weeklyPercentage >= 75 ? 'bg-warning' : 'bg-success');
                        @endphp
                        <div class="h-2.5 rounded-full {{ $weeklyColor }}" 
                             style="width: {{ min($weeklyPercentage, 100) }}%"></div>
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                        {{ round($fmcsaStatus['weekly_cycle']['percentage_used'], 1) }}% used
                    </div>
                </div>

                <!-- Status Alert -->
                @if(!$fmcsaStatus['can_drive']['can_operate'])
                    <div class="bg-danger/10 border border-danger/20 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <x-base.lucide class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" icon="AlertTriangle" />
                            <div>
                                <h4 class="font-semibold text-danger mb-2">Cannot Drive</h4>
                                <ul class="list-disc list-inside text-sm text-slate-700 space-y-1">
                                    @foreach($fmcsaStatus['can_drive']['reasons'] as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-success/10 border border-success/20 rounded-lg p-4">
                        <div class="flex items-center gap-3">
                            <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                            <span class="font-semibold text-success">Ready to drive</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Upload Documents Modal -->
@if($trip->canUploadDocuments())
<x-base.dialog id="upload-documents-modal" size="lg">
    <x-base.dialog.panel>
        <form action="{{ route('driver.trips.documents.upload', $trip) }}" method="POST" enctype="multipart/form-data" id="uploadDocumentsForm">
            @csrf
            <x-base.dialog.title class="border-b border-slate-200 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Upload" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Upload Trip Documents</h3>
                        <p class="text-sm text-slate-500">Add BOL, POD, receipts, photos and more</p>
                    </div>
                </div>
            </x-base.dialog.title>

            <x-base.dialog.description class="py-4">
                <div id="modalDocumentInputs" class="max-h-[400px] overflow-y-auto space-y-4 pr-2">
                    <!-- Document Input Template -->
                    <div class="modal-document-group p-4 bg-slate-50/50 rounded-lg border border-slate-200" data-index="0">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                            <div>
                                <x-base.form-label>Document Type *</x-base.form-label>
                                <select name="document_types[]" class="w-full form-select rounded-lg border-slate-200" required>
                                    @foreach(\App\Models\Trip::DOCUMENT_TYPES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-base.form-label>File *</x-base.form-label>
                                <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif" required
                                    class="w-full form-control rounded-lg border-slate-200 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                            </div>
                        </div>
                        <div>
                            <x-base.form-label>Notes (optional)</x-base.form-label>
                            <input type="text" name="document_notes[]" placeholder="Brief description..." 
                                class="w-full form-control rounded-lg border-slate-200">
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-200">
                    <button type="button" id="addModalDocumentBtn" 
                        class="flex items-center gap-2 text-sm text-primary hover:text-primary/80 font-medium">
                        <x-base.lucide class="w-4 h-4" icon="Plus" />
                        Add Another Document
                    </button>
                    <p class="text-xs text-slate-400 mt-2">Max 10 documents per upload. Supported: PDF, JPG, PNG, WebP, GIF (max 10MB each)</p>
                </div>
            </x-base.dialog.description>

            <x-base.dialog.footer class="border-t border-slate-200 pt-4">
                <div class="flex gap-3 justify-end w-full">
                    <x-base.button type="button" variant="outline-secondary" data-tw-dismiss="modal">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Upload" />
                        Upload Documents
                    </x-base.button>
                </div>
            </x-base.dialog.footer>
        </form>
    </x-base.dialog.panel>
</x-base.dialog>
@endif

<!-- Reject Trip Modal -->
<x-base.dialog id="reject-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5 text-center">
            <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="XCircle" />
            <div class="mt-5 text-2xl font-semibold text-slate-800">Reject Trip</div>
            <div class="mt-2 text-slate-500">
                Are you sure you want to reject this trip? Please provide a reason below.
            </div>
        </div>
        <form action="{{ route('driver.trips.reject', $trip) }}" method="POST" class="px-5 pb-8">
            @csrf
            <div class="mb-4">
                <x-base.form-label for="reason">Reason for rejection *</x-base.form-label>
                <x-base.form-textarea id="reason" name="reason" rows="3" required placeholder="Please provide a reason for rejecting this trip..."></x-base.form-textarea>
            </div>
            <div class="flex justify-end gap-2">
                <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Cancel
                </x-base.button>
                <x-base.button type="submit" variant="danger">
                    Reject Trip
                </x-base.button>
            </div>
        </form>
    </x-base.dialog.panel>
</x-base.dialog>

<!-- Trip Signature Modal -->
@if($trip->isCompleted())
    @php
        $existingPdf = $trip->driver->getTripReportPdf($trip->id);
        $autoOpen = !$existingPdf; // Auto-open if no PDF exists
    @endphp
    @livewire('driver.trip.trip-signature-modal', ['trip' => $trip, 'autoOpen' => $autoOpen], key('trip-signature-' . $trip->id))
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to get address from coordinates using Nominatim
    async function getAddressFromCoordinates(lat, lon) {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`);
            const data = await response.json();
            return data.display_name || null;
        } catch (error) {
            console.log('Geocoding error:', error.message);
            return null;
        }
    }

    // Function to capture GPS location
    function captureLocation(callback) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                async function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    const address = await getAddressFromCoordinates(lat, lon);
                    callback({ latitude: lat, longitude: lon, address: address });
                },
                function(error) {
                    console.log('GPS error:', error.message);
                    callback(null);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            callback(null);
        }
    }

    // Handle pause form submission
    const pauseForm = document.getElementById('pauseForm');
    if (pauseForm) {
        pauseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            captureLocation(function(location) {
                if (location) {
                    document.getElementById('pause_latitude').value = location.latitude;
                    document.getElementById('pause_longitude').value = location.longitude;
                    document.getElementById('pause_address').value = location.address || '';
                }
                pauseForm.submit();
            });
        });
    }

    // Handle resume form submission
    const resumeForm = document.getElementById('resumeForm');
    if (resumeForm) {
        resumeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            captureLocation(function(location) {
                if (location) {
                    document.getElementById('resume_latitude').value = location.latitude;
                    document.getElementById('resume_longitude').value = location.longitude;
                    document.getElementById('resume_address').value = location.address || '';
                }
                resumeForm.submit();
            });
        });
    }

    // Modal document upload functionality
    const addModalDocumentBtn = document.getElementById('addModalDocumentBtn');
    const modalDocumentInputs = document.getElementById('modalDocumentInputs');
    let modalDocumentIndex = 1;
    const maxModalDocuments = 10;

    if (addModalDocumentBtn && modalDocumentInputs) {
        addModalDocumentBtn.addEventListener('click', function() {
            const currentCount = modalDocumentInputs.querySelectorAll('.modal-document-group').length;
            
            if (currentCount >= maxModalDocuments) {
                alert('Maximum ' + maxModalDocuments + ' documents allowed per upload.');
                return;
            }

            const template = `
                <div class="modal-document-group p-4 bg-slate-50/50 rounded-lg border border-slate-200 relative" data-index="${modalDocumentIndex}">
                    <button type="button" class="remove-modal-doc-btn absolute top-2 right-2 w-6 h-6 bg-danger/10 hover:bg-danger/20 rounded-full flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4 text-danger" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3 pr-8">
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-1.5 block">Document Type *</label>
                            <select name="document_types[]" class="w-full form-select rounded-lg border-slate-200" required>
                                @foreach(\App\Models\Trip::DOCUMENT_TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-1.5 block">File *</label>
                            <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif" required
                                class="w-full form-control rounded-lg border-slate-200 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                        </div>
                    </div>
                    <div class="pr-8">
                        <label class="text-sm font-medium text-slate-700 mb-1.5 block">Notes (optional)</label>
                        <input type="text" name="document_notes[]" placeholder="Brief description..." 
                            class="w-full form-control rounded-lg border-slate-200">
                    </div>
                </div>
            `;
            
            modalDocumentInputs.insertAdjacentHTML('beforeend', template);
            modalDocumentIndex++;
        });

        // Event delegation for remove buttons in modal
        modalDocumentInputs.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-modal-doc-btn');
            if (removeBtn) {
                const group = removeBtn.closest('.modal-document-group');
                if (group && modalDocumentInputs.querySelectorAll('.modal-document-group').length > 1) {
                    group.remove();
                }
            }
        });
    }
});
</script>
@endpush
