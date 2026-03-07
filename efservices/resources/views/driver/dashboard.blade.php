@extends('../themes/' . $activeTheme)
@section('title', 'Dashboard - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Welcome Header -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                Welcome back, {{ $driver->user->name ?? 'Driver' }}! 👋
            </h1>
            <p class="text-slate-500 mt-1">Here's what's happening with your account today.</p>
        </div>
        <div class="flex items-center gap-2">
            @php $effectiveStatus = $driver->getEffectiveStatus(); @endphp
            @switch($effectiveStatus)
                @case('active')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-success/10 text-success">
                        <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                        Active
                    </span>
                    @break
                @case('pending_review')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-warning/10 text-warning">
                        <span class="w-2 h-2 rounded-full bg-warning"></span>
                        Pending Review
                    </span>
                    @break
                @case('draft')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-slate-200/80 text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        Draft
                    </span>
                    @break
                @case('rejected')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-danger/10 text-danger">
                        <span class="w-2 h-2 rounded-full bg-danger"></span>
                        Rejected
                    </span>
                    @break
                @default
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full bg-red-100 text-red-600">
                        <span class="w-2 h-2 rounded-full bg-red-600"></span>
                        Inactive
                    </span>
            @endswitch
        </div>
    </div>
</div>

<!-- Alerts Section -->
@if(count($alerts) > 0)
<div id="alerts-container" class="mb-6 space-y-3" role="region" aria-label="Alerts">
    @foreach($alerts as $index => $alert)
        <div class="alert-item box box--stacked p-4 border-l-4 border-{{ $alert['type'] }} transition-all duration-300" 
             data-alert-id="alert-{{ $index }}" role="alert">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-{{ $alert['type'] }}/10 rounded-lg flex-shrink-0">
                    <x-base.lucide class="w-5 h-5 text-{{ $alert['type'] }}" icon="{{ $alert['icon'] }}" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-800">{{ $alert['title'] }}</p>
                    <p class="text-sm text-slate-500">{{ $alert['message'] }}</p>
                </div>
                <a href="{{ $alert['link'] }}" 
                   class="flex-shrink-0 text-{{ $alert['type'] }} hover:underline text-sm font-medium hidden sm:block">
                    {{ $alert['link_text'] }} →
                </a>
                <button type="button" class="dismiss-alert flex-shrink-0 p-1 text-slate-400 hover:text-slate-600 rounded"
                        data-alert-id="alert-{{ $index }}" aria-label="Dismiss">
                    <x-base.lucide class="w-4 h-4" icon="X" />
                </button>
            </div>
        </div>
    @endforeach
</div>
@endif

<!-- Document Completion Progress -->
@if(isset($documentProgress) && $documentProgress['percentage'] < 100)
<div class="box box--stacked p-5 mb-6" role="region" aria-label="Document completion progress">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary/10 rounded-lg">
                <x-base.lucide class="w-5 h-5 text-primary" icon="ClipboardCheck" aria-hidden="true" />
            </div>
            <div>
                <h3 class="font-semibold text-slate-800">Document Completion</h3>
                <p class="text-sm text-slate-500">{{ $documentProgress['completed'] }} of {{ $documentProgress['total'] }} required documents</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-2xl font-bold text-primary">{{ $documentProgress['percentage'] }}%</span>
        </div>
    </div>
    
    <!-- Progress Bar -->
    <div class="mb-4">
        <div class="w-full bg-slate-200 rounded-full h-3" role="progressbar" aria-valuenow="{{ $documentProgress['percentage'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Document completion progress">
            <div class="h-3 rounded-full transition-all duration-500 {{ $documentProgress['percentage'] >= 80 ? 'bg-success' : ($documentProgress['percentage'] >= 50 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ $documentProgress['percentage'] }}%"></div>
        </div>
    </div>
    
    <!-- Document Checklist -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach($documentProgress['documents'] as $doc)
        <a href="{{ $doc['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $doc['completed'] ? 'bg-success/10' : 'bg-slate-50 hover:bg-slate-100' }} transition-colors group" aria-label="{{ $doc['name'] }}: {{ $doc['completed'] ? 'Completed' : 'Pending' }}">
            <div class="p-1.5 rounded {{ $doc['completed'] ? 'bg-success/20' : 'bg-slate-200 group-hover:bg-slate-300' }}">
                @if($doc['completed'])
                    <x-base.lucide class="w-4 h-4 text-success" icon="Check" aria-hidden="true" />
                @else
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="{{ $doc['icon'] }}" aria-hidden="true" />
                @endif
            </div>
            <span class="text-sm {{ $doc['completed'] ? 'text-success font-medium' : 'text-slate-600' }} truncate">{{ $doc['name'] }}</span>
        </a>
        @endforeach
    </div>
    
    @if(count($documentProgress['missing']) > 0)
    <div class="mt-4 pt-4 border-t border-slate-100">
        <p class="text-sm text-slate-500">
            <x-base.lucide class="w-4 h-4 inline text-warning" icon="AlertTriangle" aria-hidden="true" />
            Missing: 
            @foreach($documentProgress['missing'] as $index => $missing)
                <a href="{{ $missing['link'] }}" class="text-primary hover:underline">{{ $missing['name'] }}</a>{{ $index < count($documentProgress['missing']) - 1 ? ', ' : '' }}
            @endforeach
        </p>
    </div>
    @endif
</div>
@endif

<!-- Statistics Cards -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-4 mb-6" role="region" aria-label="Statistics overview">
    <!-- Documents -->
    <a href="{{ route('driver.documents.index') }}" class="box box--stacked p-4 hover:shadow-lg transition-all group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg" aria-label="View {{ $stats['total_documents'] }} documents">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary/10 rounded-lg group-hover:bg-primary/20 transition-colors">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" aria-hidden="true" />
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['total_documents'] }}</p>
                <p class="text-xs text-slate-500">Documents</p>
            </div>
        </div>
    </a>

    <!-- Licenses -->
    <a href="{{ route('driver.licenses.index') }}" class="box box--stacked p-4 hover:shadow-lg transition-all group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg" aria-label="View {{ $stats['licenses_count'] }} licenses">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-info/10 rounded-lg group-hover:bg-info/20 transition-colors">
                <x-base.lucide class="w-5 h-5 text-info" icon="CreditCard" aria-hidden="true" />
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['licenses_count'] }}</p>
                <p class="text-xs text-slate-500">Licenses</p>
            </div>
        </div>
    </a>

    <!-- Medical -->
    @php
        $medicalColor = $stats['medical_status'] == 'Valid' ? 'success' : ($stats['medical_status'] == 'Expiring Soon' ? 'warning' : 'danger');
    @endphp
    <a href="{{ route('driver.medical.index') }}" class="box box--stacked p-4 hover:shadow-lg transition-all group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg" aria-label="Medical status: {{ $stats['medical_status'] }}">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-{{ $medicalColor }}/10 rounded-lg group-hover:bg-{{ $medicalColor }}/20 transition-colors">
                <x-base.lucide class="w-5 h-5 text-{{ $medicalColor }}" icon="Heart" aria-hidden="true" />
            </div>
            <div>
                <p class="text-lg font-bold text-{{ $medicalColor }}">{{ $stats['medical_status'] }}</p>
                <p class="text-xs text-slate-500">Medical</p>
            </div>
        </div>
    </a>

    <!-- Vehicles -->
    <a href="{{ route('driver.vehicles.index') }}" class="box box--stacked p-4 hover:shadow-lg transition-all group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg" aria-label="View {{ $stats['vehicles_count'] }} vehicles">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-slate-100 rounded-lg group-hover:bg-slate-200 transition-colors">
                <x-base.lucide class="w-5 h-5 text-slate-600" icon="Truck" aria-hidden="true" />
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['vehicles_count'] }}</p>
                <p class="text-xs text-slate-500">Vehicles</p>
            </div>
        </div>
    </a>

    <!-- Maintenance -->
    @php
        $maintenanceColor = $stats['maintenance_status'] == 'Up to Date' ? 'success' : ($stats['maintenance_status'] == 'Pending' ? 'warning' : ($stats['maintenance_status'] == 'Overdue' ? 'danger' : 'slate'));
    @endphp
    <a href="{{ route('driver.maintenance.index') }}" class="box box--stacked p-4 hover:shadow-lg transition-all group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg" aria-label="Maintenance status: {{ $stats['maintenance_status'] }}">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-{{ $maintenanceColor }}/10 rounded-lg group-hover:bg-{{ $maintenanceColor }}/20 transition-colors">
                <x-base.lucide class="w-5 h-5 text-{{ $maintenanceColor }}" icon="Wrench" aria-hidden="true" />
            </div>
            <div>
                <p class="text-lg font-bold text-{{ $maintenanceColor }}">{{ $stats['maintenance_status'] }}</p>
                <p class="text-xs text-slate-500">Maintenance</p>
            </div>
        </div>
    </a>

    <!-- Emergency Repairs -->
    @php
        $repairsColor = $stats['emergency_repairs_status'] == 'No Repairs' ? 'success' : ($stats['emergency_repairs_status'] == 'All Completed' ? 'primary' : ($stats['emergency_repairs_status'] == 'Active Repairs' ? 'danger' : 'slate'));
    @endphp
    <a href="{{ route('driver.emergency-repairs.index') }}" class="box box--stacked p-4 hover:shadow-lg transition-all group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg" aria-label="Emergency repairs: {{ $stats['emergency_repairs_count'] }}">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-{{ $repairsColor }}/10 rounded-lg group-hover:bg-{{ $repairsColor }}/20 transition-colors">
                <x-base.lucide class="w-5 h-5 text-{{ $repairsColor }}" icon="AlertTriangle" aria-hidden="true" />
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['emergency_repairs_count'] }}</p>
                <p class="text-xs text-slate-500">Emergency Repairs</p>
            </div>
        </div>
    </a>

    <!-- Trainings -->
    <a href="{{ route('driver.profile') }}#trainings" class="box box--stacked p-4 hover:shadow-lg transition-all group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg" aria-label="View {{ $stats['trainings_count'] }} trainings">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-success/10 rounded-lg group-hover:bg-success/20 transition-colors">
                <x-base.lucide class="w-5 h-5 text-success" icon="GraduationCap" aria-hidden="true" />
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['trainings_count'] }}</p>
                <p class="text-xs text-slate-500">Trainings</p>
            </div>
        </div>
    </a>

    <!-- Testing -->
    <a href="{{ route('driver.testing.index') }}" class="box box--stacked p-4 hover:shadow-lg transition-all group focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg" aria-label="View {{ $stats['testing_count'] }} tests">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-warning/10 rounded-lg group-hover:bg-warning/20 transition-colors">
                <x-base.lucide class="w-5 h-5 text-warning" icon="TestTube" aria-hidden="true" />
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['testing_count'] }}</p>
                <p class="text-xs text-slate-500">Tests</p>
            </div>
        </div>
    </a>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Quick Actions -->
    <div class="box box--stacked p-5">
        <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Zap" />
            Quick Actions
        </h3>
        <div class="space-y-2">
            <a href="{{ route('driver.profile') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group">
                <div class="p-2 bg-primary/10 rounded-lg group-hover:bg-primary/20">
                    <x-base.lucide class="w-4 h-4 text-primary" icon="User" />
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-700">View My Profile</p>
                    <p class="text-xs text-slate-500">See all your information</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400" icon="ChevronRight" />
            </a>
            
            @if($stats['total_documents'] > 0)
            <a href="{{ route('driver.profile.download-documents') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group">
                <div class="p-2 bg-success/10 rounded-lg group-hover:bg-success/20">
                    <x-base.lucide class="w-4 h-4 text-success" icon="Download" />
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-700">Download Documents</p>
                    <p class="text-xs text-slate-500">Get all your files as ZIP</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400" icon="ChevronRight" />
            </a>
            @endif
            
            <a href="{{ route('driver.licenses.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group">
                <div class="p-2 bg-info/10 rounded-lg group-hover:bg-info/20">
                    <x-base.lucide class="w-4 h-4 text-info" icon="CreditCard" />
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-700">My Licenses</p>
                    <p class="text-xs text-slate-500">Manage your licenses</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400" icon="ChevronRight" />
            </a>
            
            <a href="{{ route('driver.medical.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group">
                <div class="p-2 bg-danger/10 rounded-lg group-hover:bg-danger/20">
                    <x-base.lucide class="w-4 h-4 text-danger" icon="Heart" />
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-700">Medical Certificate</p>
                    <p class="text-xs text-slate-500">View medical info</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400" icon="ChevronRight" />
            </a>
            
            <a href="{{ route('driver.trainings.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group">
                <div class="p-2 bg-warning/10 rounded-lg group-hover:bg-warning/20">
                    <x-base.lucide class="w-4 h-4 text-warning" icon="BookOpen" />
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-700">My Trainings</p>
                    <p class="text-xs text-slate-500">View assigned trainings</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400" icon="ChevronRight" />
            </a>
            
            <a href="{{ route('driver.maintenance.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group">
                <div class="p-2 bg-{{ $stats['maintenance_status'] == 'Overdue' ? 'danger' : ($stats['maintenance_status'] == 'Pending' ? 'warning' : 'success') }}/10 rounded-lg group-hover:bg-{{ $stats['maintenance_status'] == 'Overdue' ? 'danger' : ($stats['maintenance_status'] == 'Pending' ? 'warning' : 'success') }}/20">
                    <x-base.lucide class="w-4 h-4 text-{{ $stats['maintenance_status'] == 'Overdue' ? 'danger' : ($stats['maintenance_status'] == 'Pending' ? 'warning' : 'success') }}" icon="Wrench" />
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-700">Vehicle Maintenance</p>
                    <p class="text-xs text-slate-500">View maintenance tasks</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400" icon="ChevronRight" />
            </a>

            <a href="{{ route('driver.emergency-repairs.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group">
                <div class="p-2 bg-{{ $stats['emergency_repairs_status'] == 'Active Repairs' ? 'danger' : 'slate' }}/10 rounded-lg group-hover:bg-{{ $stats['emergency_repairs_status'] == 'Active Repairs' ? 'danger' : 'slate' }}/20">
                    <x-base.lucide class="w-4 h-4 text-{{ $stats['emergency_repairs_status'] == 'Active Repairs' ? 'danger' : 'slate' }}" icon="AlertTriangle" />
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-700">Emergency Repairs</p>
                    <p class="text-xs text-slate-500">Manage vehicle repairs</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400" icon="ChevronRight" />
            </a>
        </div>
    </div>

    <!-- Profile Summary -->
    <div class="box box--stacked p-5">
        <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <x-base.lucide class="w-5 h-5 text-primary" icon="User" />
            Profile Summary
        </h3>
        <div class="flex items-center gap-4 mb-4">
            @if ($driver->getFirstMediaUrl('profile_photo_driver'))
                <img class="w-16 h-16 rounded-full object-cover border-2 border-slate-200"
                     src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}"
                     alt="{{ $driver->user->name ?? 'Driver' }}" />
            @else
                <div class="w-16 h-16 bg-gradient-to-br from-primary/20 to-primary/10 rounded-full flex items-center justify-center">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="User" />
                </div>
            @endif
            <div>
                <p class="font-semibold text-slate-800">{{ $driver->user->name ?? 'Unknown' }} {{ $driver->last_name }}</p>
                <p class="text-sm text-slate-500">{{ $driver->carrier->name ?? 'No carrier' }}</p>
            </div>
        </div>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="text-slate-500">Email</span>
                <span class="text-slate-700">{{ $driver->user->email ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-100">
                <span class="text-slate-500">Phone</span>
                <span class="text-slate-700">{{ $driver->phone ?? 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-slate-500">Member since</span>
                <span class="text-slate-700">{{ $driver->created_at->format('M Y') }}</span>
            </div>
        </div>
        <a href="{{ route('driver.profile') }}" class="mt-4 block text-center text-sm text-primary hover:underline">
            View Full Profile →
        </a>
    </div>

    <!-- Compliance Status -->
    <div class="box box--stacked p-5">
        <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Shield" />
            Compliance Status
        </h3>
        <div class="space-y-3">
            <!-- Medical -->
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-500" icon="Heart" />
                    <span class="text-sm text-slate-700">Medical Certificate</span>
                </div>
                @if($stats['medical_status'] == 'Valid')
                    <x-base.badge variant="success">Valid</x-base.badge>
                @elseif($stats['medical_status'] == 'Expiring Soon')
                    <x-base.badge variant="warning">Expiring</x-base.badge>
                @else
                    <x-base.badge variant="danger">Expired</x-base.badge>
                @endif
            </div>
            
            <!-- License -->
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-500" icon="CreditCard" />
                    <span class="text-sm text-slate-700">Driver License</span>
                </div>
                @if($stats['licenses_count'] > 0)
                    <x-base.badge variant="success">{{ $stats['licenses_count'] }} Active</x-base.badge>
                @else
                    <x-base.badge variant="danger">None</x-base.badge>
                @endif
            </div>
            
            <!-- Documents -->
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-500" icon="FileText" />
                    <span class="text-sm text-slate-700">Documents</span>
                </div>
                @if($stats['total_documents'] > 0)
                    <x-base.badge variant="success">{{ $stats['total_documents'] }} Files</x-base.badge>
                @else
                    <x-base.badge variant="secondary">None</x-base.badge>
                @endif
            </div>
            
            <!-- Maintenance -->
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-500" icon="Wrench" />
                    <span class="text-sm text-slate-700">Maintenance</span>
                </div>
                @if($stats['maintenance_status'] == 'Up to Date')
                    <x-base.badge variant="success">Up to Date</x-base.badge>
                @elseif($stats['maintenance_status'] == 'Pending')
                    <x-base.badge variant="warning">{{ $stats['maintenance_count'] }} Pending</x-base.badge>
                @elseif($stats['maintenance_status'] == 'Overdue')
                    <x-base.badge variant="danger">{{ $stats['maintenance_count'] }} Overdue</x-base.badge>
                @else
                    <x-base.badge variant="secondary">No Vehicle</x-base.badge>
                @endif
            </div>
        </div>
    </div>
</div>

@if($driver->vehicles && $driver->vehicles->count() > 0)
<!-- Assigned Vehicles -->
<div class="box box--stacked p-5 mt-6">
    <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
        <x-base.lucide class="w-5 h-5 text-primary" icon="Truck" />
        Assigned Vehicles
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($driver->vehicles->take(3) as $vehicle)
        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-lg">
            <div class="p-3 bg-white rounded-lg shadow-sm">
                <x-base.lucide class="w-6 h-6 text-slate-600" icon="Truck" />
            </div>
            <div>
                <p class="font-medium text-slate-800">{{ $vehicle->make ?? '' }} {{ $vehicle->model ?? 'Vehicle' }}</p>
                <p class="text-sm text-slate-500">{{ $vehicle->year ?? '' }} • {{ $vehicle->license_plate ?? 'N/A' }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @if($driver->vehicles->count() > 3)
    <a href="{{ route('driver.vehicles.index') }}" class="mt-4 block text-center text-sm text-primary hover:underline">
        View all {{ $driver->vehicles->count() }} vehicles →
    </a>
    @endif
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Alert dismissal
    const dismissButtons = document.querySelectorAll('.dismiss-alert');
    const dismissed = JSON.parse(sessionStorage.getItem('dismissedAlerts') || '[]');

    dismissed.forEach(id => {
        const el = document.querySelector(`[data-alert-id="${id}"]`);
        if (el) el.style.display = 'none';
    });

    dismissButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-alert-id');
            const el = document.querySelector(`.alert-item[data-alert-id="${id}"]`);
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    el.style.display = 'none';
                    const list = JSON.parse(sessionStorage.getItem('dismissedAlerts') || '[]');
                    if (!list.includes(id)) {
                        list.push(id);
                        sessionStorage.setItem('dismissedAlerts', JSON.stringify(list));
                    }
                }, 300);
            }
        });
    });
});
</script>
@endpush
