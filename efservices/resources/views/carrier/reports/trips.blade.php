@extends('../themes/' . $activeTheme)
@section('title', 'Trip Reports')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Reports', 'url' => route('carrier.reports.index')],
    ['label' => 'Trips', 'active' => true],
];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center md:justify-between">
            <div class="text-base font-medium group-[.mode--light]:text-white">Trip Reports</div>
            <div class="flex gap-2">
                <a href="{{ route('carrier.reports.trips.export-pdf', request()->query()) }}" 
                   class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer bg-success border-success text-white">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="Download" />
                    Export to PDF
                </a>
                <a href="{{ route('carrier.reports.index') }}" class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer bg-secondary/70 border-secondary/70 text-slate-500">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back
                </a>
            </div>
        </div>
        
        <div class="mt-3.5">
            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-5">
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Total Trips</div>
                    <div class="text-2xl font-medium">{{ $stats['total_trips'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Completed</div>
                    <div class="text-2xl font-medium text-success">{{ $stats['completed_trips'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">In Progress</div>
                    <div class="text-2xl font-medium text-primary">{{ $stats['in_progress_trips'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Cancelled</div>
                    <div class="text-2xl font-medium text-slate-500">{{ $stats['cancelled_trips'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">With Violations</div>
                    <div class="text-2xl font-medium text-danger">{{ $stats['trips_with_violations'] ?? 0 }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="box box--stacked p-5 mb-5">
                <form action="{{ route('carrier.reports.trips') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Driver</label>
                        <select name="driver_id" class="w-full text-sm border-slate-200 shadow-sm rounded-md">
                            <option value="">All Drivers</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver['id'] }}" {{ request('driver_id') == $driver['id'] ? 'selected' : '' }}>
                                    {{ $driver['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Status</label>
                        <select name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md">
                            <option value="">All Status</option>
                            @foreach (\App\Models\Trip::STATUSES as $status)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">From Date</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full text-sm border-slate-200 shadow-sm rounded-md">
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">To Date</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full text-sm border-slate-200 shadow-sm rounded-md">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="py-2 px-3 rounded-md bg-primary text-white">Apply</button>
                        <a href="{{ route('carrier.reports.trips') }}" class="py-2 px-3 rounded-md bg-secondary/70 text-slate-500">Clear</a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="box box--stacked">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-b border-slate-200/60">
                        <thead>
                            <tr class="border-b border-slate-200/60 bg-slate-50">
                                <th class="px-5 py-4 font-medium text-slate-700">Trip #</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Driver</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Vehicle</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Origin</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Destination</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Status</th>
                                <th class="px-5 py-4 font-medium text-slate-700">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trips as $trip)
                                <tr class="border-b border-slate-200/60 hover:bg-slate-50">
                                    <td class="px-5 py-4 font-medium text-primary">{{ $trip->trip_number }}</td>
                                    <td class="px-5 py-4">{{ $trip->driver?->full_name ?? 'N/A' }}</td>
                                    <td class="px-5 py-4">{{ $trip->vehicle?->company_unit_number ?? 'N/A' }}</td>
                                    <td class="px-5 py-4">{{ \Illuminate\Support\Str::limit($trip->origin_address, 20) ?? 'N/A' }}</td>
                                    <td class="px-5 py-4">{{ \Illuminate\Support\Str::limit($trip->destination_address, 20) ?? 'N/A' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="px-2 py-1 rounded text-xs bg-{{ $trip->status_color }}/10 text-{{ $trip->status_color }}">
                                            {{ $trip->status_name }}
                                        </span>
                                        @if($trip->has_violations)
                                            <span class="ml-1 px-2 py-1 rounded text-xs bg-danger/10 text-danger">!</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">{{ $trip->scheduled_start_date?->format('m/d/Y') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center">
                                            <x-base.lucide class="h-8 w-8 text-slate-400 mb-2" icon="Truck" />
                                            <div>No trips found</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($trips->hasPages())
                    <div class="flex items-center justify-between p-5 border-t border-slate-200/60">
                        <div class="text-sm text-slate-500">
                            Showing {{ $trips->firstItem() }} to {{ $trips->lastItem() }} of {{ $trips->total() }}
                        </div>
                        <div>{{ $trips->appends(request()->query())->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
