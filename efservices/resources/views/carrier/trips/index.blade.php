@extends('../themes/' . $activeTheme)
@section('title', 'Trips Management')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],        
        ['label' => 'Trips Management', 'active' => true],
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
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Trips Management</h1>
                <p class="text-slate-600">Manage and monitor all your trips</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('carrier.trips.dashboard') }}" variant="outline-primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="BarChart3" />
                Dashboard
            </x-base.button>
            <x-base.button as="a" href="{{ route('carrier.trips.create') }}" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Plus" />
                Create Trip
            </x-base.button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="box box--stacked mb-6">
    <div class="p-6 border-b border-slate-200/60">
        <div class="flex items-center gap-3">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Filter" />
            <h2 class="text-lg font-semibold text-slate-800">Filter Trips</h2>
        </div>
    </div>
    <div class="p-6">
        <form method="GET" action="{{ route('carrier.trips.index') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div>
                <x-base.form-label for="status">Status</x-base.form-label>
                <select name="status" id="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="accepted" {{ ($filters['status'] ?? '') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="in_progress" {{ ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="paused" {{ ($filters['status'] ?? '') === 'paused' ? 'selected' : '' }}>Paused</option>
                    <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <x-base.form-label for="driver_id">Driver</x-base.form-label>
                <select name="driver_id" id="driver_id" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3">
                    <option value="">All Drivers</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ ($filters['driver_id'] ?? '') == $driver->id ? 'selected' : '' }}>
                            {{ implode(' ', array_filter([$driver->user->name, $driver->middle_name, $driver->last_name])) ?: 'Unknown' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-base.form-label for="start_date">From Date</x-base.form-label>
                <x-base.litepicker id="start_date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" placeholder="Select a date" />
            </div>
            <div>
                <x-base.form-label for="end_date">To Date</x-base.form-label>
                <x-base.litepicker id="end_date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" placeholder="Select a date" />
            </div>
            <div class="flex items-end gap-2">
                <x-base.button type="submit" variant="primary" class="gap-2 flex-1">
                    <x-base.lucide class="w-4 h-4" icon="Search" />
                    Filter
                </x-base.button>
                <x-base.button as="a" href="{{ route('carrier.trips.index') }}" variant="outline-primary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="RefreshCw" />
                    Reset
                </x-base.button>
            </div>
        </form>
    </div>
</div>

<!-- Trips Table -->
<div class="box box--stacked">
    <div class="p-6 border-b border-slate-200/60">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-base.lucide class="w-5 h-5 text-primary" icon="List" />
                <h2 class="text-lg font-semibold text-slate-800">Trips</h2>
                @if(method_exists($trips, 'total'))
                    <x-base.badge variant="primary" class="px-3 py-1.5">
                        {{ $trips->total() }} Total
                    </x-base.badge>
                @endif
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        @if(method_exists($trips, 'count') && $trips->count() > 0 || (is_array($trips) && count($trips) > 0) || (is_object($trips) && $trips->isNotEmpty()))
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/60">
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Trip #</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Driver</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Origin</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Destination</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Scheduled</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Quick Actions</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @foreach($trips as $trip)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('carrier.trips.show', $trip) }}"
                                       class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                                        {{ $trip->trip_number ?: 'Trip #' . $trip->id }}
                                    </a>
                                    @if($trip->isQuickTrip())
                                        <span class="px-1.5 py-0.5 text-xs font-medium rounded bg-{{ $trip->needsCompletion() ? 'warning' : 'success' }}/10 text-{{ $trip->needsCompletion() ? 'warning' : 'success' }}" title="Quick Trip{{ $trip->needsCompletion() ? ' - Needs Info' : ' - Complete' }}">
                                            <x-base.lucide class="w-3 h-3 inline" icon="Zap" />
                                            @if($trip->needsCompletion())
                                                <span class="ml-0.5">Needs Info</span>
                                            @endif
                                        </span>
                                    @endif
                                    @if($trip->has_violations)
                                        <x-base.badge variant="danger" size="sm" class="gap-1">
                                            <x-base.lucide class="w-3 h-3" icon="AlertTriangle" />
                                            Violations
                                        </x-base.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700">
                                    {{ implode(' ', array_filter([$trip->driver->user->name ?? 'N/A', $trip->driver->middle_name ?? '', $trip->driver->last_name ?? ''])) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="MapPin" />
                                    <span class="text-sm text-slate-700">{{ Str::limit($trip->origin_address, 35) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="MapPin" />
                                    <span class="text-sm text-slate-700">{{ Str::limit($trip->destination_address, 35) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                                    <span class="text-sm text-slate-700">{{ $trip->scheduled_start_date->format('M d, Y H:i') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($trip->status === 'pending')
                                    <x-base.badge variant="warning" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-warning rounded-full"></span>
                                        {{ $trip->status_name }}
                                    </x-base.badge>
                                @elseif($trip->status === 'in_progress')
                                    <x-base.badge variant="primary" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-primary rounded-full"></span>
                                        {{ $trip->status_name }}
                                    </x-base.badge>
                                @elseif($trip->status === 'paused')
                                    <x-base.badge variant="warning" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                        Paused
                                    </x-base.badge>
                                @elseif($trip->status === 'completed')
                                    <x-base.badge variant="success" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                        {{ $trip->status_name }}
                                    </x-base.badge>
                                @elseif($trip->status === 'cancelled')
                                    <x-base.badge variant="danger" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-danger rounded-full"></span>
                                        {{ $trip->status_name }}
                                    </x-base.badge>
                                @else
                                    <x-base.badge variant="secondary" class="gap-1.5">
                                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                        {{ $trip->status_name }}
                                    </x-base.badge>
                                @endif
                            </td>
                            {{-- Quick Actions Column --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    @if($trip->status === 'in_progress')
                                        <form action="{{ route('carrier.trips.force-pause', $trip) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to pause this trip?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-warning rounded hover:bg-warning/80 transition-colors" title="Pause">
                                                <x-base.lucide class="w-3 h-3 mr-1" icon="PauseCircle" />
                                                Pause
                                            </button>
                                        </form>
                                        <form action="{{ route('carrier.trips.force-end', $trip) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to end this trip?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-success rounded hover:bg-success/80 transition-colors" title="End">
                                                <x-base.lucide class="w-3 h-3 mr-1" icon="StopCircle" />
                                                End
                                            </button>
                                        </form>
                                    @elseif($trip->status === 'paused')
                                        <form action="{{ route('carrier.trips.force-resume', $trip) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to resume this trip?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-primary rounded hover:bg-primary/80 transition-colors" title="Resume">
                                                <x-base.lucide class="w-3 h-3 mr-1" icon="PlayCircle" />
                                                Resume
                                            </button>
                                        </form>
                                        <form action="{{ route('carrier.trips.force-end', $trip) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to end this trip?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-success rounded hover:bg-success/80 transition-colors" title="End">
                                                <x-base.lucide class="w-3 h-3 mr-1" icon="StopCircle" />
                                                End
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <x-base.button as="a" 
                                        href="{{ route('carrier.trips.show', $trip) }}" 
                                        variant="primary" 
                                        size="sm"
                                        class="gap-1.5"
                                        title="View">
                                        <x-base.lucide class="w-4 h-4" icon="Eye" />
                                    </x-base.button>
                                    @if($trip->isPending() || $trip->isAccepted())
                                        <x-base.button as="a" 
                                            href="{{ route('carrier.trips.edit', $trip) }}" 
                                            variant="warning" 
                                            size="sm"
                                            class="gap-1.5"
                                            title="Edit">
                                            <x-base.lucide class="w-4 h-4" icon="Edit" />
                                        </x-base.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-12 text-center">
                <div class="flex flex-col items-center justify-center py-8">
                    <div class="bg-slate-100 rounded-full p-4 mb-4 w-16 h-16 flex items-center justify-center">
                        <x-base.lucide class="w-8 h-8 text-slate-400" icon="Truck" />
                    </div>
                    <p class="text-slate-600 font-medium mb-1">No trips found</p>
                    <p class="text-sm text-slate-500 mb-5">No trips match your current filters.</p>
                    <x-base.button as="a" href="{{ route('carrier.trips.create') }}" variant="primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Plus" />
                        Create New Trip
                    </x-base.button>
                </div>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if(method_exists($trips, 'hasPages') && $trips->hasPages())
        <div class="p-6 border-t border-slate-200/60">
            {{ $trips->appends(request()->query())->links('custom.pagination') }}
        </div>
    @endif
</div>

@endsection
