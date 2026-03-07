@extends('../themes/' . $activeTheme)
@section('title', 'Trips Management')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Trips Management', 'active' => true],
    ];
@endphp
@section('subcontent')

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="Truck" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Trips Management</h1>
                    <p class="text-slate-600">Monitor and manage all trips across carriers</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('admin.trips.statistics') }}"
                    class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                    variant="outline-primary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="BarChart3" />
                    Statistics
                </x-base.button>
                <x-base.button as="a" href="{{ route('admin.trips.create') }}"
                    class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                    variant="primary">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="PenLine" />
                    Add New Trip
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-12 gap-6 mb-8">
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-slate-100 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-slate-600" icon="Truck" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Total Trips</div>
                        <div class="text-2xl font-bold text-slate-800">{{ $trips->total() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-warning/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-warning" icon="Clock" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Pending</div>
                        <div class="text-2xl font-bold text-warning">{{ $trips->where('status', 'pending')->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-primary" icon="PlayCircle" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">In Progress</div>
                        <div class="text-2xl font-bold text-primary">{{ $trips->where('status', 'in_progress')->count() + $trips->where('status', 'paused')->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-success/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Completed</div>
                        <div class="text-2xl font-bold text-success">{{ $trips->where('status', 'completed')->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked flex flex-col">
                <form method="GET" action="{{ route('admin.trips.index') }}" id="filterForm">
                    <!-- Hidden filter fields -->
                    <input type="hidden" name="carrier_id" id="hidden_carrier_id" value="{{ $filters['carrier_id'] ?? '' }}">
                    <input type="hidden" name="status" id="hidden_status" value="{{ $filters['status'] ?? '' }}">

                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center">
                        <div>
                            <div class="relative">
                                <x-base.lucide
                                    class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500"
                                    icon="Search" />
                                <x-base.form-input class="rounded-[0.5rem] pl-9 sm:w-64" type="text" name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Search by trip number, driver..." />
                            </div>
                        </div>
                        <div class="flex flex-col gap-x-3 gap-y-2 sm:ml-auto sm:flex-row">
                            <x-base.menu>
                                <x-base.menu.button type="button" class="w-full sm:w-auto" as="x-base.button"
                                    variant="outline-secondary">
                                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Download" />
                                    Export
                                    <x-base.lucide class="ml-2 h-4 w-4 stroke-[1.3]" icon="ChevronDown" />
                                </x-base.menu.button>
                                <x-base.menu.items class="w-40">
                                    <x-base.menu.item>
                                        <x-base.lucide class="mr-2 h-4 w-4" icon="FileBarChart" />
                                        PDF
                                    </x-base.menu.item>
                                    <x-base.menu.item>
                                        <x-base.lucide class="mr-2 h-4 w-4" icon="FileBarChart" />
                                        CSV
                                    </x-base.menu.item>
                                </x-base.menu.items>
                            </x-base.menu>
                            <x-base.popover class="inline-block">
                                <x-base.popover.button type="button" class="w-full sm:w-auto" as="x-base.button"
                                    variant="outline-secondary">
                                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowDownWideNarrow" />
                                    Filter
                                    @php
                                        $activeFilters = 0;
                                        if (!empty($filters['carrier_id'])) $activeFilters++;
                                        if (!empty($filters['status'])) $activeFilters++;
                                        if (!empty($filters['start_date'])) $activeFilters++;
                                    @endphp
                                    @if ($activeFilters > 0)
                                        <span class="ml-2 flex h-5 items-center justify-center rounded-full border bg-slate-100 px-1.5 text-xs font-medium">
                                            {{ $activeFilters }}
                                        </span>
                                    @endif
                                </x-base.popover.button>
                                <x-base.popover.panel>
                                    <div class="p-2">
                                        <div>
                                            <div class="text-left text-slate-500">Carrier</div>
                                            <x-base.form-select id="popover_carrier_id" class="mt-2 flex-1"
                                                onchange="updateHiddenField('carrier_id', this.value)">
                                                <option value="">All Carriers</option>
                                                @foreach ($carriers as $carrier)
                                                    <option value="{{ $carrier->id }}"
                                                        {{ ($filters['carrier_id'] ?? '') == $carrier->id ? 'selected' : '' }}>
                                                        {{ $carrier->name }}
                                                    </option>
                                                @endforeach
                                            </x-base.form-select>
                                        </div>
                                        <div class="mt-3">
                                            <div class="text-left text-slate-500">Status</div>
                                            <x-base.form-select id="popover_status" class="mt-2 flex-1"
                                                onchange="updateHiddenField('status', this.value)">
                                                <option value="">All Statuses</option>
                                                <option value="pending" {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="accepted" {{ ($filters['status'] ?? '') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                                <option value="in_progress" {{ ($filters['status'] ?? '') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                                <option value="paused" {{ ($filters['status'] ?? '') == 'paused' ? 'selected' : '' }}>Paused</option>
                                                <option value="completed" {{ ($filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled" {{ ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </x-base.form-select>
                                        </div>
                                        <div class="mt-3">
                                            <div class="text-left text-slate-500">From Date</div>
                                            <x-base.form-input type="date" name="start_date" class="mt-2 flex-1"
                                                value="{{ $filters['start_date'] ?? '' }}" />
                                        </div>
                                        <div class="mt-3">
                                            <div class="text-left text-slate-500">To Date</div>
                                            <x-base.form-input type="date" name="end_date" class="mt-2 flex-1"
                                                value="{{ $filters['end_date'] ?? '' }}" />
                                        </div>
                                        <div class="mt-4 flex items-center">
                                            <x-base.button type="button" onclick="clearFilters()"
                                                class="ml-auto w-32" variant="secondary">
                                                Clear
                                            </x-base.button>
                                            <x-base.button type="submit" class="ml-2 w-32" variant="primary">
                                                Apply
                                            </x-base.button>
                                        </div>
                                    </div>
                                </x-base.popover.panel>
                            </x-base.popover>
                        </div>
                    </div>
                </form>

                <div class="overflow-auto xl:overflow-visible">
                    <x-base.table class="border-b border-slate-200/60">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Trip #
                                </x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Carrier
                                </x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Driver
                                </x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Route
                                </x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Scheduled
                                </x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                    Status
                                </x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                    Quick Actions
                                </x-base.table.td>
                                <x-base.table.td class="w-36 border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                    Action
                                </x-base.table.td>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @forelse ($trips as $trip)
                                <x-base.table.tr class="[&_td]:last:border-b-0">
                                    <x-base.table.td class="border-dashed py-4">
                                        <a href="{{ route('admin.trips.show', $trip) }}" class="font-medium text-primary whitespace-nowrap">
                                            {{ $trip->trip_number }}
                                        </a>
                                        @if($trip->has_violations)
                                            <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-danger rounded-full">!</span>
                                        @endif
                                        @if($trip->forgot_to_close)
                                            <span class="ml-1 px-2 py-0.5 text-xs font-medium bg-warning/20 text-warning rounded">Ghost</span>
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed py-4">
                                        <div class="font-medium whitespace-nowrap">{{ $trip->carrier->name ?? 'N/A' }}</div>
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed py-4">
                                        <div class="whitespace-nowrap">{{ $trip->driver->user->name ?? 'N/A' }}</div>
                                        @if($trip->vehicle)
                                            <div class="mt-0.5 text-xs text-slate-500">{{ $trip->vehicle->unit_number ?? $trip->vehicle->company_unit_number ?? '' }}</div>
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="max-w-[200px]">
                                                <div class="text-xs text-slate-500 truncate" title="{{ $trip->origin_address }}">
                                                    <x-base.lucide class="inline w-3 h-3 mr-1" icon="MapPin" />
                                                    {{ Str::limit($trip->origin_address, 25) }}
                                                </div>
                                                <div class="text-xs text-slate-500 truncate mt-1" title="{{ $trip->destination_address }}">
                                                    <x-base.lucide class="inline w-3 h-3 mr-1" icon="Flag" />
                                                    {{ Str::limit($trip->destination_address, 25) }}
                                                </div>
                                            </div>
                                        </div>
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed py-4">
                                        <div class="whitespace-nowrap">
                                            {{ $trip->scheduled_start_date?->format('M d, Y') ?? 'N/A' }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $trip->scheduled_start_date?->format('H:i') ?? '' }}
                                        </div>
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed py-4">
                                        <div @class([
                                            'flex items-center justify-center',
                                            'text-warning' => $trip->status === 'pending',
                                            'text-info' => $trip->status === 'accepted',
                                            'text-primary' => $trip->status === 'in_progress',
                                            'text-amber-500' => $trip->status === 'paused',
                                            'text-success' => $trip->status === 'completed',
                                            'text-slate-500' => $trip->status === 'cancelled',
                                        ])>
                                            <x-base.lucide class="h-3.5 w-3.5 stroke-[1.7]"
                                                icon="{{ match($trip->status) {
                                                    'pending' => 'Clock',
                                                    'accepted' => 'CheckCircle',
                                                    'in_progress' => 'PlayCircle',
                                                    'paused' => 'PauseCircle',
                                                    'completed' => 'CheckCircle2',
                                                    'cancelled' => 'XCircle',
                                                    default => 'Circle'
                                                } }}" />
                                            <div class="ml-1.5 whitespace-nowrap">
                                                {{ $trip->status_name }}
                                            </div>
                                        </div>
                                    </x-base.table.td>
                                    {{-- Quick Actions Column --}}
                                    <x-base.table.td class="border-dashed py-4">
                                        <div class="flex items-center justify-center gap-1">
                                            @if($trip->status === 'accepted')
                                                <form action="{{ route('admin.trips.force-start', $trip) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Are you sure you want to force start this trip?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-primary rounded hover:bg-primary/80 transition-colors" title="Force Start">
                                                        <x-base.lucide class="w-3 h-3 mr-1" icon="PlayCircle" />
                                                        Start
                                                    </button>
                                                </form>
                                            @elseif($trip->status === 'in_progress')
                                                <form action="{{ route('admin.trips.force-pause', $trip) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Are you sure you want to pause this trip?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-warning rounded hover:bg-warning/80 transition-colors" title="Pause">
                                                        <x-base.lucide class="w-3 h-3 mr-1" icon="PauseCircle" />
                                                        Pause
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.trips.force-end', $trip) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Are you sure you want to end this trip?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-success rounded hover:bg-success/80 transition-colors" title="End">
                                                        <x-base.lucide class="w-3 h-3 mr-1" icon="StopCircle" />
                                                        End
                                                    </button>
                                                </form>
                                            @elseif($trip->status === 'paused')
                                                <form action="{{ route('admin.trips.force-resume', $trip) }}" method="POST" class="inline"
                                                    onsubmit="return confirm('Are you sure you want to resume this trip?')">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-primary rounded hover:bg-primary/80 transition-colors" title="Resume">
                                                        <x-base.lucide class="w-3 h-3 mr-1" icon="PlayCircle" />
                                                        Resume
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.trips.force-end', $trip) }}" method="POST" class="inline"
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
                                    </x-base.table.td>
                                    <x-base.table.td class="relative border-dashed py-4">
                                        <div class="flex items-center justify-center">
                                            <x-base.menu class="h-5">
                                                <x-base.menu.button class="h-5 w-5 text-slate-500">
                                                    <x-base.lucide class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70" icon="MoreVertical" />
                                                </x-base.menu.button>
                                                <x-base.menu.items class="w-40">
                                                    <x-base.menu.item href="{{ route('admin.trips.show', $trip) }}">
                                                        <x-base.lucide class="mr-2 h-4 w-4" icon="Eye" />
                                                        View Details
                                                    </x-base.menu.item>
                                                    @if($trip->isPending() || $trip->isAccepted())
                                                        <x-base.menu.item href="{{ route('admin.trips.edit', $trip) }}">
                                                            <x-base.lucide class="mr-2 h-4 w-4" icon="CheckSquare" />
                                                            Edit
                                                        </x-base.menu.item>
                                                    @endif
                                                    @if(!$trip->isInProgress())
                                                        <x-base.menu.divider />
                                                        <x-base.menu.item class="text-danger" data-tw-toggle="modal"
                                                            data-tw-target="#delete-modal-{{ $trip->id }}">
                                                            <x-base.lucide class="mr-2 h-4 w-4" icon="Trash2" />
                                                            Delete
                                                        </x-base.menu.item>
                                                    @endif
                                                </x-base.menu.items>
                                            </x-base.menu>
                                        </div>
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @empty
                                <x-base.table.tr>
                                    <x-base.table.td colspan="8" class="py-8 text-center text-slate-500">
                                        <x-base.lucide class="mx-auto h-12 w-12 text-slate-300" icon="Truck" />
                                        <p class="mt-2">No trips found.</p>
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @endforelse
                        </x-base.table.tbody>
                    </x-base.table>
                </div>
                
                <!-- Delete Modals -->
                @foreach($trips as $trip)
                    @if(!$trip->isInProgress())
                        <x-base.dialog id="delete-modal-{{ $trip->id }}" size="md">
                            <x-base.dialog.panel>
                                <div class="p-5 text-center">
                                    <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="XCircle" />
                                    <div class="mt-5 text-2xl">Are you sure?</div>
                                    <div class="mt-2 text-slate-500">
                                        Do you really want to delete trip <strong>{{ $trip->trip_number }}</strong>?<br>
                                        This process cannot be undone.
                                    </div>
                                </div>
                                <div class="px-5 pb-8 text-center">
                                    <form action="{{ route('admin.trips.destroy', $trip) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <x-base.button class="mr-1 w-24" data-tw-dismiss="modal" type="button" variant="outline-secondary">
                                            Cancel
                                        </x-base.button>
                                        <x-base.button class="w-24" type="submit" variant="danger">
                                            Delete
                                        </x-base.button>
                                    </form>
                                </div>
                            </x-base.dialog.panel>
                        </x-base.dialog>
                    @endif
                @endforeach
                
                @if($trips->hasPages())
                    <div class="flex-reverse flex flex-col-reverse flex-wrap items-center gap-y-2 p-5 sm:flex-row">
                        <div class="w-full">
                            {{ $trips->withQueryString()->links('custom.pagination') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 500);
            });
        }
    });

    function updateHiddenField(fieldName, value) {
        const hiddenField = document.getElementById('hidden_' + fieldName);
        if (hiddenField) {
            hiddenField.value = value;
        }
    }

    function clearFilters() {
        window.location.href = '{{ route("admin.trips.index") }}';
    }

    window.clearFilters = clearFilters;
    window.updateHiddenField = updateHiddenField;
</script>
@endpush
