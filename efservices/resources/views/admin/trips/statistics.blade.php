@extends('../themes/' . $activeTheme)
@section('title', 'Trips Statistics')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Trips Management', 'url' => route('admin.trips.index')],
        ['label' => 'Statistics', 'active' => true],
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
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Trips Statistics</h1>
                <p class="text-slate-600">Overview of all trips across the system</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('admin.trips.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Trips
            </x-base.button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-12 gap-6">
    <!-- Total Trips -->
    <div class="col-span-12 sm:col-span-6 lg:col-span-4 xl:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="Truck" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">Total Trips</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Trips -->
    <div class="col-span-12 sm:col-span-6 lg:col-span-4 xl:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-warning/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-warning" icon="Clock" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">Pending</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- In Progress Trips -->
    <div class="col-span-12 sm:col-span-6 lg:col-span-4 xl:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-info/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-info" icon="Activity" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">In Progress</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['in_progress']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Trips -->
    <div class="col-span-12 sm:col-span-6 lg:col-span-4 xl:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-success/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">Completed</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['completed']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancelled Trips -->
    <div class="col-span-12 sm:col-span-6 lg:col-span-4 xl:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-danger/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-danger" icon="XCircle" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">Cancelled</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['cancelled']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trips with Violations -->
    <div class="col-span-12 sm:col-span-6 lg:col-span-4 xl:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-danger/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-danger" icon="AlertTriangle" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">With Violations</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['with_violations']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ghost Logs -->
    <div class="col-span-12 sm:col-span-6 lg:col-span-4 xl:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-slate-100 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-slate-600" icon="Ghost" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">Ghost Logs</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['ghost_logs']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

