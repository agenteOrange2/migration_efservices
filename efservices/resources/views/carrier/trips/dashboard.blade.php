@extends('../themes/' . $activeTheme)
@section('title', 'Trips Dashboard')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')], 
        ['label' => 'Trips Dashboard', 'active' => true],
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
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Trips Dashboard</h1>
                <p class="text-slate-600">Monitor and manage your trips overview</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('carrier.trips.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="List" />
                View All Trips
            </x-base.button>
            <x-base.button as="a" href="{{ route('carrier.trips.create') }}" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Plus" />
                Create Trip
            </x-base.button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-12 gap-6 mb-8">
    <div class="col-span-12 sm:col-span-6 lg:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="Truck" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">Total Trips</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['total'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-span-12 sm:col-span-6 lg:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-warning/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-warning" icon="Clock" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">Pending</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['pending'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-span-12 sm:col-span-6 lg:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-info/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-info" icon="Activity" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">In Progress</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['in_progress'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-span-12 sm:col-span-6 lg:col-span-3">
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-success/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                </div>
                <div>
                    <div class="text-slate-500 text-sm font-medium mb-1">Completed Today</div>
                    <div class="text-3xl font-bold text-slate-800">{{ number_format($stats['completed_today'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Trips Table -->
<div class="box box--stacked">
    <div class="p-6 border-b border-slate-200/60">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Activity" />
                <h2 class="text-lg font-semibold text-slate-800">Active Trips</h2>
                @if(isset($activeTrips) && $activeTrips->count() > 0)
                    <x-base.badge variant="primary" class="px-3 py-1.5">
                        {{ $activeTrips->count() }} Active
                    </x-base.badge>
                @endif
            </div>
            <x-base.button as="a" href="{{ route('carrier.trips.index', ['status' => 'in_progress']) }}" variant="outline-primary" size="sm" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowRight" />
                View All
            </x-base.button>
        </div>
    </div>

    <div class="overflow-x-auto">
        @if(!isset($activeTrips) || $activeTrips->isEmpty())
            <div class="p-12 text-center">
                <div class="flex flex-col items-center justify-center py-8">
                    <div class="bg-slate-100 rounded-full p-4 mb-4 w-16 h-16 flex items-center justify-center">
                        <x-base.lucide class="w-8 h-8 text-slate-400" icon="Truck" />
                    </div>
                    <p class="text-slate-600 font-medium mb-1">No active trips</p>
                    <p class="text-sm text-slate-500 mb-5">No trips are currently in progress at the moment.</p>
                    <x-base.button as="a" href="{{ route('carrier.trips.create') }}" variant="primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Plus" />
                        Create New Trip
                    </x-base.button>
                </div>
            </div>
        @else
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/60">
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Trip #</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Driver</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Route</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Started</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @foreach($activeTrips as $trip)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('carrier.trips.show', $trip) }}" 
                                   class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                                    {{ $trip->trip_number ?: 'Trip #' . $trip->id }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">
                                    {{ $trip->driver->user->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-sm text-slate-700">
                                    <span class="truncate max-w-xs">{{ Str::limit($trip->origin_address, 25) }}</span>
                                    <x-base.lucide class="w-4 h-4 text-slate-400 flex-shrink-0" icon="ArrowRight" />
                                    <span class="truncate max-w-xs">{{ Str::limit($trip->destination_address, 25) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">
                                    {{ $trip->started_at ? $trip->started_at->diffForHumans() : 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">
                                    @if($trip->started_at)
                                        {{ $trip->started_at->diffForHumans(now(), true) }}
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center">
                                    <x-base.button as="a" 
                                        href="{{ route('carrier.trips.show', $trip) }}" 
                                        variant="primary" 
                                        size="sm"
                                        class="gap-1.5">
                                        <x-base.lucide class="w-4 h-4" icon="Eye" />
                                        View
                                    </x-base.button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
