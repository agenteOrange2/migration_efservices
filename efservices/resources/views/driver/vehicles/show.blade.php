@extends('../themes/' . $activeTheme)
@section('title', 'Vehicle Details - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Vehicles', 'url' => route('driver.vehicles.index')],
        ['label' => ($vehicle->make ?? '') . ' ' . ($vehicle->model ?? 'Vehicle'), 'active' => true],
    ];
@endphp

@section('subcontent')

<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('driver.vehicles.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
        <x-base.lucide class="w-5 h-5 text-slate-500" icon="ArrowLeft" />
    </a>
    <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ $vehicle->make ?? '' }} {{ $vehicle->model ?? 'Vehicle' }}</h1>
        <p class="text-slate-500">{{ $vehicle->year ?? '' }} • {{ $vehicle->license_plate ?? 'N/A' }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Truck" />
                Vehicle Information
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                @if($vehicle->company_unit_number)
                <div>
                    <p class="text-sm text-slate-500 mb-1">Unit #</p>
                    <p class="font-semibold text-slate-800">{{ $vehicle->company_unit_number }}</p>
                </div>
                @endif
                <div>
                    <p class="text-sm text-slate-500 mb-1">Make</p>
                    <p class="font-semibold text-slate-800">{{ $vehicle->make ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Model</p>
                    <p class="font-semibold text-slate-800">{{ $vehicle->model ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Year</p>
                    <p class="font-semibold text-slate-800">{{ $vehicle->year ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">VIN</p>
                    <p class="font-semibold text-slate-800 font-mono text-sm">{{ $vehicle->vin ?? 'N/A' }}</p>
                </div>
                @if($vehicle->type)
                <div>
                    <p class="text-sm text-slate-500 mb-1">Type</p>
                    <p class="font-semibold text-slate-800">{{ $vehicle->type }}</p>
                </div>
                @endif
                @if($vehicle->fuel_type)
                <div>
                    <p class="text-sm text-slate-500 mb-1">Fuel Type</p>
                    <p class="font-semibold text-slate-800">{{ $vehicle->fuel_type }}</p>
                </div>
                @endif
            </div>
        </div>

        @if($vehicle->getMedia('vehicle_photos')->count() > 0)
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Image" />
                Vehicle Photos
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach($vehicle->getMedia('vehicle_photos') as $photo)
                <a href="{{ $photo->getUrl() }}" target="_blank" class="block">
                    <img src="{{ $photo->getUrl() }}" alt="Vehicle photo"
                         class="w-full h-32 object-cover rounded-lg border border-slate-200 hover:shadow-lg transition-shadow" />
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Vehicle Documents Section -->
        <div class="box box--stacked">
            <div class="p-5 border-b border-slate-200/80 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                    Vehicle Documents
                </h3>
                <span class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded">
                    {{ $vehicleDocuments->count() }} {{ Str::plural('document', $vehicleDocuments->count()) }}
                </span>
            </div>

            <div class="p-5">
                @if($vehicleDocuments->count() > 0)
                <div class="space-y-3">
                    @foreach($vehicleDocuments as $document)
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200 hover:bg-slate-100 transition-colors">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="flex-shrink-0">
                                @php
                                    $media = $document->getFirstMedia('document_files');
                                    $isPdf = $media && str_contains($media->mime_type, 'pdf');
                                @endphp
                                <div class="w-12 h-12 rounded-lg {{ $isPdf ? 'bg-danger/10' : 'bg-primary/10' }} flex items-center justify-center">
                                    @if($isPdf)
                                        <x-base.lucide class="w-6 h-6 text-danger" icon="FileText" />
                                    @else
                                        <x-base.lucide class="w-6 h-6 text-primary" icon="Image" />
                                    @endif
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ $document->documentTypeName }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    @if($document->document_number)
                                    <span class="text-xs text-slate-500">#{{ $document->document_number }}</span>
                                    @endif
                                    @if($document->expiration_date)
                                    <span class="text-xs px-2 py-0.5 rounded {{ $document->isExpired() ? 'bg-danger/10 text-danger' : ($document->isAboutToExpire() ? 'bg-warning/10 text-warning' : 'bg-success/10 text-success') }}">
                                        @if($document->isExpired())
                                            Expired
                                        @elseif($document->isAboutToExpire())
                                            Exp: {{ $document->expiration_date->format('M d, Y') }}
                                        @else
                                            Valid until {{ $document->expiration_date->format('M d, Y') }}
                                        @endif
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0 ml-4">
                            @if($document->getFirstMedia('document_files'))
                            <a href="{{ route('driver.vehicles.documents.preview', [$vehicle->id, $document->id]) }}"
                               target="_blank"
                               class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors"
                               title="View Document">
                                <x-base.lucide class="w-4 h-4" icon="Eye" />
                            </a>
                            <a href="{{ route('driver.vehicles.documents.download', [$vehicle->id, $document->id]) }}"
                               class="flex items-center justify-center w-9 h-9 rounded-lg bg-success/10 text-success hover:bg-success hover:text-white transition-colors"
                               title="Download Document">
                                <x-base.lucide class="w-4 h-4" icon="Download" />
                            </a>
                            @else
                            <span class="text-xs text-slate-400">No file</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-start gap-2">
                        <x-base.lucide class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" icon="Info" />
                        <p class="text-xs text-blue-700">
                            These documents are available for you to download or show during roadside inspections. Keep copies on your device for quick access.
                        </p>
                    </div>
                </div>
                @else
                <!-- No Documents -->
                <div class="text-center py-8">
                    <x-base.lucide class="w-12 h-12 text-slate-300 mx-auto mb-3" icon="FileX" />
                    <p class="text-slate-500 text-sm">No documents available for this vehicle</p>
                    <p class="text-slate-400 text-xs mt-1">Contact your carrier if you need vehicle documents</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Maintenance Section -->
        <div class="box box--stacked">
            <div class="p-5 border-b border-slate-200/80 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Wrench" />
                    Vehicle Maintenance
                </h3>
                <a href="{{ route('driver.maintenance.index') }}" class="text-primary hover:underline text-sm">
                    View All →
                </a>
            </div>
            
            <div class="p-5">
                @if($overdueMaintenances->count() > 0)
                <!-- Overdue Maintenance -->
                <div class="mb-5">
                    <h4 class="text-sm font-semibold text-danger mb-3 flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                        Overdue ({{ $overdueMaintenances->count() }})
                    </h4>
                    <div class="space-y-2">
                        @foreach($overdueMaintenances as $maintenance)
                        <a href="{{ route('driver.maintenance.show', $maintenance->id) }}" class="flex items-center justify-between p-3 bg-danger/5 border border-danger/20 rounded-lg hover:bg-danger/10 transition-colors">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <x-base.lucide class="w-4 h-4 text-danger flex-shrink-0" icon="Wrench" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ $maintenance->service_tasks }}</p>
                                    <p class="text-xs text-danger">Due: {{ $maintenance->next_service_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <x-base.lucide class="w-4 h-4 text-slate-400 flex-shrink-0" icon="ChevronRight" />
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($upcomingMaintenances->count() > 0)
                <!-- Upcoming Maintenance -->
                <div class="mb-5">
                    <h4 class="text-sm font-semibold text-warning mb-3 flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Clock" />
                        Upcoming ({{ $upcomingMaintenances->count() }})
                    </h4>
                    <div class="space-y-2">
                        @foreach($upcomingMaintenances as $maintenance)
                        <a href="{{ route('driver.maintenance.show', $maintenance->id) }}" class="flex items-center justify-between p-3 bg-warning/5 border border-warning/20 rounded-lg hover:bg-warning/10 transition-colors">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <x-base.lucide class="w-4 h-4 text-warning flex-shrink-0" icon="Wrench" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ $maintenance->service_tasks }}</p>
                                    <p class="text-xs text-slate-500">Due: {{ $maintenance->next_service_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <x-base.lucide class="w-4 h-4 text-slate-400 flex-shrink-0" icon="ChevronRight" />
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($recentMaintenances->count() > 0)
                <!-- Recent Completed Maintenance -->
                <div>
                    <h4 class="text-sm font-semibold text-success mb-3 flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4" icon="CheckCircle" />
                        Recent Completed
                    </h4>
                    <div class="space-y-2">
                        @foreach($recentMaintenances as $maintenance)
                        <a href="{{ route('driver.maintenance.show', $maintenance->id) }}" class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <x-base.lucide class="w-4 h-4 text-success flex-shrink-0" icon="CheckCircle" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate">{{ $maintenance->service_tasks }}</p>
                                    <p class="text-xs text-slate-500">Completed: {{ $maintenance->service_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <x-base.lucide class="w-4 h-4 text-slate-400 flex-shrink-0" icon="ChevronRight" />
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($overdueMaintenances->count() == 0 && $upcomingMaintenances->count() == 0 && $recentMaintenances->count() == 0)
                <!-- No Maintenance -->
                <div class="text-center py-8">
                    <x-base.lucide class="w-12 h-12 text-slate-300 mx-auto mb-3" icon="CheckCircle2" />
                    <p class="text-slate-500 text-sm">No maintenance records found for this vehicle</p>
                </div>
                @endif

                @if($overdueMaintenances->count() > 0 || $upcomingMaintenances->count() > 0 || $recentMaintenances->count() > 0)
                <div class="mt-5 pt-5 border-t border-slate-200">
                    <a href="{{ route('driver.maintenance.index') }}" class="block text-center text-sm text-primary hover:underline">
                        View All Maintenance Records →
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Status -->
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4">Status</h3>
            <div class="text-center py-4">
                <div class="w-16 h-16 bg-success/10 rounded-full flex items-center justify-center mx-auto mb-3">
                    <x-base.lucide class="w-8 h-8 text-success" icon="CheckCircle" />
                </div>
                <p class="font-semibold text-success">Assigned to You</p>
                <p class="text-sm text-slate-500 mt-1">This vehicle is currently assigned to your account</p>
            </div>
        </div>

        <!-- Quick Info -->
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4">Quick Info</h3>
            <div class="space-y-3 text-sm">
                @if($vehicle->registration_expiry)
                <div class="flex justify-between">
                    <span class="text-slate-500">Registration Expires</span>
                    <span class="text-slate-700">{{ \Carbon\Carbon::parse($vehicle->registration_expiry)->format('M d, Y') }}</span>
                </div>
                @endif
                @if($vehicle->insurance_expiry)
                <div class="flex justify-between">
                    <span class="text-slate-500">Insurance Expires</span>
                    <span class="text-slate-700">{{ \Carbon\Carbon::parse($vehicle->insurance_expiry)->format('M d, Y') }}</span>
                </div>
                @endif
                @if($vehicle->created_at)
                <div class="flex justify-between">
                    <span class="text-slate-500">Added</span>
                    <span class="text-slate-700">{{ $vehicle->created_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Maintenance Actions -->
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-warning" icon="Wrench" />
                Maintenance
            </h3>
            <div class="space-y-2">
                <a href="{{ route('driver.maintenance.index') }}" class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors">
                    <span class="text-sm font-medium text-slate-700">View All Records</span>
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="ChevronRight" />
                </a>
                <a href="{{ route('driver.maintenance.create') }}" class="flex items-center justify-center gap-2 p-3 rounded-lg bg-warning text-white hover:bg-warning/90 transition-colors">
                    <x-base.lucide class="w-4 h-4" icon="Plus" />
                    <span class="text-sm font-medium">Create Maintenance</span>
                </a>
            </div>
        </div>

        <!-- Emergency Repairs Actions -->
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-danger" icon="AlertTriangle" />
                Emergency Repairs
            </h3>
            <div class="space-y-2">
                <a href="{{ route('driver.emergency-repairs.index') }}" class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors">
                    <span class="text-sm font-medium text-slate-700">View All Repairs</span>
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="ChevronRight" />
                </a>
                <a href="{{ route('driver.emergency-repairs.create') }}" class="flex items-center justify-center gap-2 p-3 rounded-lg bg-danger text-white hover:bg-danger/90 transition-colors">
                    <x-base.lucide class="w-4 h-4" icon="Plus" />
                    <span class="text-sm font-medium">Report Repair</span>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
