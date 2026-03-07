@extends('../themes/' . $activeTheme)
@section('title', 'Reports Dashboard')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports Dashboard', 'active' => true],
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
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="BarChart3" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Reports Dashboard</h1>
                <p class="text-slate-600">Real-time system statistics and insights</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-sm text-slate-500">Last updated</p>
            <p class="text-sm font-medium text-slate-700">{{ now()->format('M d, Y H:i') }}</p>
        </div>
    </div>
</div>

<!-- Professional Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <!-- Carriers Card -->
    <div class="box box--stacked p-6 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-500 mb-1">Total Carriers</p>
                <h3 class="text-3xl font-bold text-slate-800 group-hover:text-blue-600 transition-colors">{{ $stats['carriers']['total'] }}</h3>
                <div class="mt-3">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                        <span>Active</span>
                        <span>{{ $stats['carriers']['percentage_active'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all duration-300" style="width: {{ $stats['carriers']['percentage_active'] }}%"></div>
                    </div>
                </div>
            </div>
            <div class="p-3 bg-blue-500/10 rounded-xl group-hover:bg-blue-500/20 transition-colors">
                <x-base.lucide class="w-7 h-7 text-primary" icon="Truck" />
            </div>
        </div>
    </div>

    <!-- Drivers Card -->
    <div class="box box--stacked p-6 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-500 mb-1">Total Drivers</p>
                <h3 class="text-3xl font-bold text-slate-800 group-hover:text-green-600 transition-colors">{{ $stats['drivers']['total'] }}</h3>
                <div class="mt-3">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                        <span>Active</span>
                        <span>{{ $stats['drivers']['percentage_active'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-success h-2 rounded-full transition-all duration-300" style="width: {{ $stats['drivers']['percentage_active'] }}%"></div>
                    </div>
                </div>
            </div>
            <div class="p-3 bg-green-500/10 rounded-xl group-hover:bg-green-500/20 transition-colors">
                <x-base.lucide class="w-7 h-7 text-success" icon="Users" />
            </div>
        </div>
    </div>

    <!-- Vehicles Card -->
    <div class="box box--stacked p-6 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-500 mb-1">Total Vehicles</p>
                <h3 class="text-3xl font-bold text-slate-800 group-hover:text-cyan-600 transition-colors">{{ $stats['vehicles']['total'] }}</h3>
                <div class="mt-3">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                        <span>Active</span>
                        <span>{{ $stats['vehicles']['percentage_active'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all duration-300" style="width: {{ $stats['vehicles']['percentage_active'] }}%"></div>
                    </div>
                </div>
            </div>
            <div class="p-3 bg-cyan-500/10 rounded-xl group-hover:bg-cyan-500/20 transition-colors">
                <x-base.lucide class="w-7 h-7 text-primary" icon="Car" />
            </div>
        </div>
    </div>

    <!-- Documents Card -->
    <div class="box box--stacked p-6 hover:shadow-lg transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-500 mb-1">Total Documents</p>
                <h3 class="text-3xl font-bold text-slate-800 group-hover:text-purple-600 transition-colors">{{ $stats['documents']['total'] }}</h3>
                <div class="mt-3">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                        <span>Approved</span>
                        <span>{{ $stats['documents']['percentage_approved'] }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-warning h-2 rounded-full transition-all duration-300" style="width: {{ $stats['documents']['percentage_approved'] }}%"></div>
                    </div>
                </div>
            </div>
            <div class="p-3 bg-purple-500/10 rounded-xl group-hover:bg-purple-500/20 transition-colors">
                <x-base.lucide class="w-7 h-7 text-warning" icon="FileText" />
            </div>
        </div>
    </div>
</div>

<!-- Second Row Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
    <!-- Maintenances Card -->
    <a href="{{ route('admin.reports.maintenances') }}" class="box box--stacked p-6 hover:shadow-lg transition-all duration-200 group cursor-pointer">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-500 mb-1">Maintenances</p>
                <h3 class="text-3xl font-bold text-slate-800 group-hover:text-orange-600 transition-colors">{{ $stats['maintenances']['total'] ?? 0 }}</h3>
                <div class="mt-3 flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ $stats['maintenances']['completed'] ?? 0 }} completed
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        {{ $stats['maintenances']['pending'] ?? 0 }} pending
                    </span>
                </div>
            </div>
            <div class="p-3 bg-orange-500/10 rounded-xl group-hover:bg-orange-500/20 transition-colors">
                <x-base.lucide class="w-7 h-7 text-orange-500" icon="Wrench" />
            </div>
        </div>
    </a>

    <!-- Emergency Repairs Card -->
    <a href="{{ route('admin.reports.emergency-repairs') }}" class="box box--stacked p-6 hover:shadow-lg transition-all duration-200 group cursor-pointer">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-500 mb-1">Emergency Repairs</p>
                <h3 class="text-3xl font-bold text-slate-800 group-hover:text-red-600 transition-colors">{{ $stats['emergency_repairs']['total'] ?? 0 }}</h3>
                <div class="mt-3">
                    <p class="text-sm text-slate-500">Total Cost: <span class="font-medium text-red-600">${{ number_format($stats['emergency_repairs']['total_cost'] ?? 0, 2) }}</span></p>
                </div>
            </div>
            <div class="p-3 bg-red-500/10 rounded-xl group-hover:bg-red-500/20 transition-colors">
                <x-base.lucide class="w-7 h-7 text-red-500" icon="AlertOctagon" />
            </div>
        </div>
    </a>

    <!-- Trainings Card -->
    <a href="{{ route('admin.reports.trainings') }}" class="box box--stacked p-6 hover:shadow-lg transition-all duration-200 group cursor-pointer">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-500 mb-1">Trainings</p>
                <h3 class="text-3xl font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ $stats['trainings']['total'] ?? 0 }}</h3>
                <div class="mt-3">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                        <span>Completion Rate</span>
                        <span>{{ $stats['trainings']['completion_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-indigo-500 h-2 rounded-full transition-all duration-300" style="width: {{ $stats['trainings']['completion_rate'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
            <div class="p-3 bg-indigo-500/10 rounded-xl group-hover:bg-indigo-500/20 transition-colors">
                <x-base.lucide class="w-7 h-7 text-indigo-500" icon="GraduationCap" />
            </div>
        </div>
    </a>
</div>

<!-- Accident Alert -->
@if($stats['accidents']['recent'] > 0)
<div class="box box--stacked p-6 mb-8 bg-yellow-50 border-l-4 border-yellow-500">
    <div class="flex items-start gap-4">
        <div class="p-2 bg-yellow-100 rounded-lg">
            <x-base.lucide class="w-6 h-6 text-yellow-600" icon="AlertTriangle" />
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-slate-800 mb-1">Recent Accidents Alert</h3>
            <p class="text-slate-600">
                <strong>{{ $stats['accidents']['recent'] }}</strong> accident{{ $stats['accidents']['recent'] !== 1 ? 's have' : ' has' }} been registered in the last 30 days.
            </p>
        </div>
        <x-base.button as="a" href="{{ route('admin.reports.accidents') }}" variant="warning" class="gap-2">
            <x-base.lucide class="w-4 h-4" icon="Eye" />
            View Details
        </x-base.button>
    </div>
</div>
@endif

<!-- Quick Access Reports -->
<div class="box box--stacked p-6 mb-8">
    <div class="flex items-center gap-3 mb-6">
        <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
        <h2 class="text-lg font-semibold text-primary">Quick Access Reports</h2>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Active Drivers Report -->
        <a href="{{ route('admin.reports.active-drivers') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 rounded-lg group-hover:bg-green-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-success" icon="UserCheck" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">Active Drivers</h3>
                    <p class="text-sm text-slate-500">{{ $stats['drivers']['active'] }} active drivers</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Equipment List Report -->
        <a href="{{ route('admin.reports.equipment-list') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-cyan-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Car" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">Equipment List</h3>
                    <p class="text-sm text-slate-500">{{ $stats['vehicles']['total'] }} vehicles</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Accidents Report -->
        <a href="{{ route('admin.reports.accidents') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 rounded-lg group-hover:bg-red-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-danger" icon="AlertTriangle" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">Accidents Report</h3>
                    <p class="text-sm text-slate-500">{{ $stats['accidents']['total'] }} total accidents</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Inactive Drivers Report -->
        <a href="{{ route('admin.reports.inactive-drivers') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-danger hover:bg-danger/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 rounded-lg group-hover:bg-red-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-danger" icon="UserMinus" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-danger transition-colors">Inactive Drivers</h3>
                    <p class="text-sm text-slate-500">{{ $stats['drivers']['total'] - $stats['drivers']['active'] }} inactive drivers</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-danger transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Driver Prospects Report -->
        <a href="{{ route('admin.reports.driver-prospects') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Users" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">Driver Prospects</h3>
                    <p class="text-sm text-slate-500">{{ $stats['drivers']['pending'] }} pending applications</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Carrier Documents Report -->
        <a href="{{ route('admin.reports.carrier-documents') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-purple-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">Carrier Documents</h3>
                    <p class="text-sm text-slate-500">{{ $stats['documents']['pending'] }} pending documents</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Maintenances Report -->
        <a href="{{ route('admin.reports.maintenances') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 rounded-lg group-hover:bg-orange-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-orange-500" icon="Wrench" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">Maintenances Report</h3>
                    <p class="text-sm text-slate-500">{{ $stats['maintenances']['total'] ?? 0 }} total maintenances</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Emergency Repairs Report -->
        <a href="{{ route('admin.reports.emergency-repairs') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 rounded-lg group-hover:bg-red-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-danger" icon="AlertOctagon" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">Emergency Repairs</h3>
                    <p class="text-sm text-slate-500">{{ $stats['emergency_repairs']['total'] ?? 0 }} total repairs</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Trainings Report -->
        <a href="{{ route('admin.reports.trainings') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 rounded-lg group-hover:bg-indigo-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-indigo-500" icon="GraduationCap" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">Trainings Report</h3>
                    <p class="text-sm text-slate-500">{{ $stats['trainings']['completion_rate'] ?? 0 }}% completion rate</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Trip Report -->
        <a href="{{ route('admin.reports.trips') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-blue-500" icon="MapPin" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">Trip Report</h3>
                    <p class="text-sm text-slate-500">View all trips and routes</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- HOS Report -->
        <a href="{{ route('admin.reports.hos') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-primary hover:bg-primary/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-cyan-100 rounded-lg group-hover:bg-cyan-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-cyan-500" icon="Clock" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-primary transition-colors">HOS Report</h3>
                    <p class="text-sm text-slate-500">Hours of Service compliance</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" icon="ChevronRight" />
            </div>
        </a>

        <!-- Violations Report -->
        <a href="{{ route('admin.reports.violations') }}" class="group p-4 rounded-lg border border-slate-200 hover:border-danger hover:bg-danger/5 transition-all duration-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 rounded-lg group-hover:bg-red-200 transition-colors">
                    <x-base.lucide class="w-5 h-5 text-danger" icon="AlertOctagon" />
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-slate-800 group-hover:text-danger transition-colors">Violations Report</h3>
                    <p class="text-sm text-slate-500">HOS violations tracking</p>
                </div>
                <x-base.lucide class="w-4 h-4 text-slate-400 group-hover:text-danger transition-colors" icon="ChevronRight" />
            </div>
        </a>
    </div>
</div>

<!-- Status Overview -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Driver Status -->
    <div class="box box--stacked p-6">
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Users" />
            <h2 class="text-lg font-semibold text-primary">Driver Status</h2>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                <p class="text-sm text-slate-600 mb-1">Approved</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['drivers']['approved'] }}</p>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <p class="text-sm text-slate-600 mb-1">Pending</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['drivers']['pending'] }}</p>
            </div>
        </div>
    </div>

    <!-- Document Status -->
    <div class="box box--stacked p-6">
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
            <h2 class="text-lg font-semibold text-slate-800">Document Status</h2>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                <p class="text-xs text-slate-600 mb-1">Approved</p>
                <p class="text-xl font-bold text-green-600">{{ $stats['documents']['approved'] }}</p>
            </div>
            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <p class="text-xs text-slate-600 mb-1">Pending</p>
                <p class="text-xl font-bold text-yellow-600">{{ $stats['documents']['pending'] }}</p>
            </div>
            <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                <p class="text-xs text-slate-600 mb-1">Rejected</p>
                <p class="text-xl font-bold text-red-600">{{ $stats['documents']['rejected'] }}</p>
            </div>
        </div>
    </div>
</div>

@endsection
