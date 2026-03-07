@extends('../themes/' . $activeTheme)
@section('title', 'My Trips')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],     
        ['label' => 'My Trips', 'active' => true],
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
                <h1 class="text-3xl font-bold text-slate-800 mb-2">My Trips</h1>
                <p class="text-slate-600">View and manage your assigned trips</p>
            </div>
        </div>
        <x-base.button as="a" href="{{ route('driver.trips.create') }}" variant="success" class="gap-2">
            <x-base.lucide class="w-4 h-4" icon="Plus" />
            Create New Trip
        </x-base.button>
    </div>
</div>

<!-- Status Filter Tabs -->
<div class="box box--stacked mb-6">
    <div class="p-6 border-b border-slate-200/60">
        <div class="flex flex-wrap gap-2">
            <x-base.button 
                as="a" 
                href="{{ route('driver.trips.index', ['status' => 'pending']) }}" 
                variant="{{ $currentStatus === 'pending' ? 'primary' : 'outline-primary' }}"
                class="gap-2"
                size="sm">
                <x-base.lucide class="w-4 h-4" icon="Clock" />
                Pending
            </x-base.button>
            <x-base.button 
                as="a" 
                href="{{ route('driver.trips.index', ['status' => 'accepted']) }}" 
                variant="{{ $currentStatus === 'accepted' ? 'primary' : 'outline-primary' }}"
                class="gap-2"
                size="sm">
                <x-base.lucide class="w-4 h-4" icon="CheckCircle" />
                Accepted
            </x-base.button>
            <x-base.button 
                as="a" 
                href="{{ route('driver.trips.index', ['status' => 'in_progress']) }}" 
                variant="{{ $currentStatus === 'in_progress' ? 'primary' : 'outline-primary' }}"
                class="gap-2"
                size="sm">
                <x-base.lucide class="w-4 h-4" icon="Activity" />
                In Progress
            </x-base.button>
            <x-base.button 
                as="a" 
                href="{{ route('driver.trips.index', ['status' => 'completed']) }}" 
                variant="{{ $currentStatus === 'completed' ? 'primary' : 'outline-primary' }}"
                class="gap-2"
                size="sm">
                <x-base.lucide class="w-4 h-4" icon="CheckCircle2" />
                Completed
            </x-base.button>
            <x-base.button 
                as="a" 
                href="{{ route('driver.trips.index', ['status' => 'all']) }}" 
                variant="{{ $currentStatus === 'all' ? 'primary' : 'outline-primary' }}"
                class="gap-2"
                size="sm">
                <x-base.lucide class="w-4 h-4" icon="List" />
                All
            </x-base.button>
        </div>
    </div>
</div>

<!-- Trips Grid -->
@if($trips->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($trips as $trip)
            <div class="box box--stacked flex flex-col">
                <!-- Card Header -->
                <div class="p-6 border-b border-slate-200/60">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="Truck" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-slate-800">{{ $trip->trip_number ?: 'Trip #' . $trip->id }}</h3>
                                    @if($trip->isQuickTrip())
                                        <span class="px-1.5 py-0.5 text-xs font-medium rounded bg-warning/10 text-warning" title="Quick Trip{{ $trip->needsCompletion() ? ' - Needs Info' : '' }}">
                                            <x-base.lucide class="w-3 h-3 inline" icon="Zap" />
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500">Trip ID: {{ $trip->id }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
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
                            @elseif($trip->status === 'completed')
                                <x-base.badge variant="success" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                    {{ $trip->status_name }}
                                </x-base.badge>
                            @else
                                <x-base.badge variant="secondary" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full"></span>
                                    {{ $trip->status_name }}
                                </x-base.badge>
                            @endif
                            @if($trip->isQuickTrip() && $trip->needsCompletion())
                                <span class="text-xs text-warning">Needs Info</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-6 flex-1 space-y-4">
                    <!-- Origin -->
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Origin</label>
                        <div class="flex items-start gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                            <p class="text-sm font-medium text-slate-800">{{ Str::limit($trip->origin_address, 60) }}</p>
                        </div>
                    </div>

                    <!-- Destination -->
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Destination</label>
                        <div class="flex items-start gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                            <p class="text-sm font-medium text-slate-800">{{ Str::limit($trip->destination_address, 60) }}</p>
                        </div>
                    </div>

                    <!-- Schedule Info -->
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-200/60">
                        <div>
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Start Date</label>
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                                <p class="text-sm text-slate-700">{{ $trip->scheduled_start_date->format('M d, Y') }}</p>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $trip->scheduled_start_date->format('h:i A') }}</p>
                        </div>
                    </div>

                    @if($trip->vehicle)
                        <div class="pt-4 border-t border-slate-200/60">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Vehicle</label>
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400" icon="Truck" />
                                <p class="text-sm text-slate-700">
                                    {{ $trip->vehicle->make ?? '' }} {{ $trip->vehicle->model ?? '' }}
                                    @if($trip->vehicle->company_unit_number)
                                        <span class="text-slate-500">({{ $trip->vehicle->company_unit_number }})</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Card Footer -->
                <div class="p-6 border-t border-slate-200/60 bg-slate-50/50">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <x-base.button 
                            as="a" 
                            href="{{ route('driver.trips.show', $trip) }}" 
                            variant="primary"
                            size="sm"
                            class="flex-1 gap-2">
                            <x-base.lucide class="w-4 h-4" icon="Eye" />
                            View Details
                        </x-base.button>
                        
                        @if($trip->isPending())
                            <form action="{{ route('driver.trips.accept', $trip) }}" method="POST" class="flex-1">
                                @csrf
                                <x-base.button type="submit" variant="success" size="sm" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="CheckCircle" />
                                    Accept
                                </x-base.button>
                            </form>
                            <form action="{{ route('driver.trips.reject', $trip) }}" method="POST" class="flex-1" 
                                  onsubmit="return confirm('Are you sure you want to reject this trip?');">
                                @csrf
                                <x-base.button type="submit" variant="danger" size="sm" class="w-full gap-2">
                                    <x-base.lucide class="w-4 h-4" icon="XCircle" />
                                    Reject
                                </x-base.button>
                            </form>
                        @elseif($trip->canBeStarted())
                            <x-base.button 
                                as="a" 
                                href="{{ route('driver.trips.start.form', $trip) }}" 
                                variant="primary"
                                size="sm"
                                class="flex-1 gap-2">
                                <x-base.lucide class="w-4 h-4" icon="Play" />
                                Start Trip
                            </x-base.button>
                        @elseif($trip->canBeEnded())
                            <x-base.button 
                                as="a" 
                                href="{{ route('driver.trips.end.form', $trip) }}" 
                                variant="warning"
                                size="sm"
                                class="flex-1 gap-2">
                                <x-base.lucide class="w-4 h-4" icon="Square" />
                                End Trip
                            </x-base.button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    @if(method_exists($trips, 'links'))
        @if($trips->hasPages())
            <div class="mt-6">
                {{ $trips->links('custom.pagination') }}
            </div>
        @endif
    @endif
@else
    <!-- Empty State -->
    <div class="box box--stacked">
        <div class="p-12 text-center">
            <div class="flex flex-col items-center justify-center py-8">
                <div class="bg-slate-100 rounded-full p-4 mb-4 w-16 h-16 flex items-center justify-center">
                    <x-base.lucide class="w-8 h-8 text-slate-400" icon="Truck" />
                </div>
                <p class="text-slate-600 font-medium mb-1">No trips found</p>
                <p class="text-sm text-slate-500">
                    @if($currentStatus !== 'all')
                        No {{ $currentStatus }} trips available at the moment.
                    @else
                        You don't have any trips assigned yet.
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif

@endsection
