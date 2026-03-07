@extends('../themes/' . $activeTheme)
@section('title', 'Trip Details')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Trip Report', 'url' => route('admin.reports.trips')],
        ['label' => 'Trip #' . $trip->trip_number, 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Trip #{{ $trip->trip_number }}</h1>
                    <p class="text-slate-600 mt-1">Trip details and timeline</p>
                </div>
            </div>
            <div class="flex gap-3">
                <x-base.button as="a" href="{{ route('admin.reports.trips') }}" variant="outline-secondary" class="gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Trip Report
                </x-base.button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <!-- Trip Info -->
        <div class="col-span-12 lg:col-span-8">
            <div class="box box--stacked p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Trip Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-slate-500">Trip Number</label>
                        <p class="font-medium">{{ $trip->trip_number }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">Status</label>
                        <p><x-base.badge variant="{{ $trip->status_color }}">{{ $trip->status_name }}</x-base.badge></p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">Scheduled Start</label>
                        <p class="font-medium">{{ $trip->scheduled_start_date?->format('M d, Y H:i') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">Scheduled End</label>
                        <p class="font-medium">{{ $trip->scheduled_end_date?->format('M d, Y H:i') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">Actual Start</label>
                        <p class="font-medium">{{ $trip->actual_start_date?->format('M d, Y H:i') ?? 'Not started' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-500">Actual End</label>
                        <p class="font-medium">{{ $trip->actual_end_date?->format('M d, Y H:i') ?? 'Not completed' }}</p>
                    </div>
                </div>
            </div>

            <!-- Route Info -->
            <div class="box box--stacked p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Route Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-success/10 rounded-lg">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <label class="text-sm text-slate-500">Origin</label>
                            <p class="font-medium">{{ $trip->origin_address ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-danger/10 rounded-lg">
                            <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <label class="text-sm text-slate-500">Destination</label>
                            <p class="font-medium">{{ $trip->destination_address ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
                @if($trip->estimated_distance || $trip->actual_distance)
                    <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t">
                        <div>
                            <label class="text-sm text-slate-500">Estimated Distance</label>
                            <p class="font-medium">{{ $trip->estimated_distance ? number_format($trip->estimated_distance, 1) . ' mi' : 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-slate-500">Actual Distance</label>
                            <p class="font-medium">{{ $trip->actual_distance ? number_format($trip->actual_distance, 1) . ' mi' : 'N/A' }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- HOS Violations -->
            @if($trip->hosViolations && $trip->hosViolations->count() > 0)
                <div class="box box--stacked p-6">
                    <h3 class="text-lg font-semibold mb-4 border-b pb-3 text-danger">HOS Violations ({{ $trip->hosViolations->count() }})</h3>
                    <div class="space-y-3">
                        @foreach($trip->hosViolations as $violation)
                            <div class="flex items-center justify-between p-3 bg-danger/5 rounded-lg border border-danger/20">
                                <div>
                                    <p class="font-medium">{{ $violation->violation_type_name }}</p>
                                    <p class="text-sm text-slate-500">{{ $violation->violation_date->format('M d, Y H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <x-base.badge variant="{{ $violation->severity_color }}">{{ $violation->severity_name }}</x-base.badge>
                                    <p class="text-sm text-slate-500 mt-1">{{ $violation->formatted_hours_exceeded }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-span-12 lg:col-span-4">
            <!-- Driver Info -->
            <div class="box box--stacked p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Driver</h3>
                @if($trip->driver)
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                            <span class="text-primary font-semibold">
                                {{ strtoupper(substr($trip->driver->user->name ?? 'D', 0, 1)) }}{{ strtoupper(substr($trip->driver->last_name ?? 'R', 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <p class="font-medium">{{ $trip->driver->full_name ?? 'N/A' }}</p>
                            <p class="text-sm text-slate-500">{{ $trip->driver->user->email ?? '' }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-slate-500">No driver assigned</p>
                @endif
            </div>

            <!-- Carrier Info -->
            <div class="box box--stacked p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Carrier</h3>
                @if($trip->carrier)
                    <p class="font-medium">{{ $trip->carrier->name }}</p>
                    <p class="text-sm text-slate-500">DOT: {{ $trip->carrier->dot_number ?? 'N/A' }}</p>
                    <p class="text-sm text-slate-500">MC: {{ $trip->carrier->mc_number ?? 'N/A' }}</p>
                @else
                    <p class="text-slate-500">No carrier assigned</p>
                @endif
            </div>

            <!-- Vehicle Info -->
            <div class="box box--stacked p-6">
                <h3 class="text-lg font-semibold mb-4 border-b pb-3">Vehicle</h3>
                @if($trip->vehicle)
                    <p class="font-medium">{{ $trip->vehicle->company_unit_number ?? 'Unit N/A' }}</p>
                    <p class="text-sm text-slate-500">VIN: {{ $trip->vehicle->vin ?? 'N/A' }}</p>
                    <p class="text-sm text-slate-500">{{ $trip->vehicle->year ?? '' }} {{ $trip->vehicle->make?->name ?? '' }} {{ $trip->vehicle->model ?? '' }}</p>
                @else
                    <p class="text-slate-500">No vehicle assigned</p>
                @endif
            </div>
        </div>
    </div>
@endsection
