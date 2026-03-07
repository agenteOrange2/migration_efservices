@extends('../themes/' . $activeTheme)
@section('title', 'Driver Accident History')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Accidents', 'url' => route('carrier.drivers.accidents.index')],
    ['label' => 'Driver History', 'active' => true],
];
@endphp

@section('subcontent')
<div>
    <!-- Alerts -->
    <div class="pb-4">
        <!-- Flash Messages -->
        @if(session('success'))
        <x-base.alert variant="success" dismissible class="flex items-center gap-3">
            <x-base.lucide class="w-8 h-8 text-white" icon="check-circle" />
            <span class="text-white">
                {{ session('success') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif

        @if(session('error'))
        <x-base.alert variant="danger" dismissible>
            <span class="text-white">
                {{ session('error') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif
    </div>

    <!-- Driver Information Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="User" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">
                        {{ implode(' ', array_filter([$driver->user->name ?? '', $driver->middle_name, $driver->last_name])) }}
                    </h1>
                    <p class="text-slate-600">Accident History</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('carrier.drivers.accidents.index') }}" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                    Back to Accidents
                </x-base.button>
                <x-base.button as="a" href="{{ route('carrier.drivers.accidents.create') }}" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                    Add Accident
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="box box--stacked p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-lg">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="AlertTriangle" />
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-800">{{ $totalAccidents }}</div>
                    <div class="text-sm text-slate-500">Total Accidents</div>
                </div>
            </div>
        </div>
        <div class="box box--stacked p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-warning/10 rounded-lg">
                    <x-base.lucide class="w-6 h-6 text-warning" icon="Activity" />
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-800">{{ $totalInjuries }}</div>
                    <div class="text-sm text-slate-500">Total Injuries</div>
                </div>
            </div>
        </div>
        <div class="box box--stacked p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-danger/10 rounded-lg">
                    <x-base.lucide class="w-6 h-6 text-danger" icon="XCircle" />
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-800">{{ $totalFatalities }}</div>
                    <div class="text-sm text-slate-500">Total Fatalities</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <h3 class="box-title">Filter Accidents</h3>
        </div>
        <div class="box-body p-5">
            <form action="{{ route('carrier.drivers.accidents.driver_history', $driver) }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <x-base.form-label for="search_term">Search</x-base.form-label>
                    <x-base.form-input type="text" name="search_term" id="search_term" value="{{ request('search_term') }}" placeholder="Nature of accident, comments..." />
                </div>
                <div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <x-base.form-label for="date_from">Date From</x-base.form-label>
                            <x-base.litepicker name="date_from" value="{{ request('date_from') }}" placeholder="MM/DD/YYYY" />
                        </div>
                        <div>
                            <x-base.form-label for="date_to">Date To</x-base.form-label>
                            <x-base.litepicker name="date_to" value="{{ request('date_to') }}" placeholder="MM/DD/YYYY" />
                        </div>
                    </div>
                </div>
                <div>
                    <x-base.form-label for="had_injuries">Injuries</x-base.form-label>
                    <select id="had_injuries" name="had_injuries" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All</option>
                        <option value="1" {{ request('had_injuries') === '1' ? 'selected' : '' }}>With Injuries</option>
                        <option value="0" {{ request('had_injuries') === '0' ? 'selected' : '' }}>Without Injuries</option>
                    </select>
                </div>
                <div>
                    <x-base.form-label for="had_fatalities">Fatalities</x-base.form-label>
                    <select id="had_fatalities" name="had_fatalities" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All</option>
                        <option value="1" {{ request('had_fatalities') === '1' ? 'selected' : '' }}>With Fatalities</option>
                        <option value="0" {{ request('had_fatalities') === '0' ? 'selected' : '' }}>Without Fatalities</option>
                    </select>
                </div>
                <div class="flex items-end md:col-span-4">
                    <x-base.button type="submit" variant="primary" class="w-full">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="filter" />
                        Apply Filters
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de accidentes -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h3 class="box-title">Accident Records ({{ $accidents->total() ?? 0 }})</h3>
            </div>
        </div>

        @if($accidents->count() > 0)
        <div class="box-body p-0">
            <div class="overflow-x-auto">
                <x-base.table class="border-separate border-spacing-y-[10px]">
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.drivers.accidents.driver_history', array_merge(['driver' => $driver->id], request()->except(['sort_field', 'sort_direction']), ['sort_field' => 'created_at', 'sort_direction' => request('sort_field') == 'created_at' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                    Registration Date
                                    @if (request('sort_field') == 'created_at')
                                        @if (request('sort_direction') == 'asc')
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @else
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Nature of Accident
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.drivers.accidents.driver_history', array_merge(['driver' => $driver->id], request()->except(['sort_field', 'sort_direction']), ['sort_field' => 'accident_date', 'sort_direction' => request('sort_field') == 'accident_date' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                    Accident Date
                                    @if (request('sort_field') == 'accident_date')
                                        @if (request('sort_direction') == 'asc')
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @else
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Injuries
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Fatalities
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Actions
                            </x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @forelse ($accidents as $accident)
                        <x-base.table.tr>
                            <x-base.table.td class="px-6 py-4">
                                {{ $accident->created_at->format('m/d/Y') }}
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <div class="max-w-xs truncate" title="{{ $accident->nature_of_accident }}">
                                    {{ $accident->nature_of_accident }}
                                </div>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                {{ \Carbon\Carbon::parse($accident->accident_date)->format('M d, Y') }}
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @if($accident->had_injuries)
                                    <span class="bg-warning/20 text-warning rounded px-2 py-1 text-xs font-medium">
                                        {{ $accident->number_of_injuries ?? 0 }}
                                    </span>
                                @else
                                    <span class="text-slate-400">None</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @if($accident->had_fatalities)
                                    <span class="bg-danger/20 text-danger rounded px-2 py-1 text-xs font-medium">
                                        {{ $accident->number_of_fatalities ?? 0 }}
                                    </span>
                                @else
                                    <span class="text-slate-400">None</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td>
                                <x-base.menu class="h-5">
                                    <x-base.menu.button class="h-5 w-5 text-slate-500">
                                        <x-base.lucide class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70" icon="MoreVertical" />
                                    </x-base.menu.button>

                                    <x-base.menu.items class="w-48">
                                        <div class="flex flex-col gap-3">
                                            <a href="{{ route('carrier.drivers.accidents.documents.show', $accident->id) }}" class="flex mr-1 text-primary" title="View Documents">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="file-text" />
                                                View Documents
                                            </a>
                                            <a href="{{ route('carrier.drivers.accidents.edit', $accident->id) }}" class="btn btn-sm btn-primary flex">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="edit" />
                                                Edit
                                            </a>
                                            <button type="button" onclick="confirmDeleteAccident({{ $accident->id }})" class="btn btn-sm text-red-600 flex">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="trash-2" />
                                                Delete
                                            </button>
                                        </div>
                                    </x-base.menu.items>
                                </x-base.menu>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @empty
                        <x-base.table.tr>
                            <x-base.table.td colspan="6" class="text-center">
                                <div class="flex flex-col items-center justify-center py-16">
                                    <x-base.lucide class="h-8 w-8 text-slate-400" icon="AlertTriangle" />
                                    No accidents found
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @endforelse
                    </x-base.table.tbody>
                </x-base.table>
            </div>
        </div>
        <!-- Paginación -->
        <div class="box-footer py-5 px-8">
            {{ $accidents->links('custom.pagination') }}
        </div>
        @else
        <div class="box-body p-10 text-center">
            <div class="flex flex-col items-center justify-center py-8">
                <x-base.lucide class="w-16 h-16 text-slate-300" icon="AlertTriangle" />
                <div class="mt-5 text-slate-500">
                    No accident records found for this driver.
                </div>
                <x-base.button as="a" href="{{ route('carrier.drivers.accidents.create') }}" class="mt-5">
                    <x-base.lucide class="w-4 h-4 mr-1" icon="plus" />
                    Add Accident
                </x-base.button>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Delete Accident Confirmation Modal -->
<x-base.dialog id="deleteAccidentModal">
    <x-base.dialog.panel>
        <div class="p-5 text-center">
            <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="alert-triangle" />
            <div class="mt-5 text-3xl">Delete Accident Record?</div>
            <div class="mt-2 text-slate-500">
                Are you sure you want to delete this accident record? <br>
                This will permanently delete the accident and all associated documents. <br>
                <strong>This action cannot be undone.</strong>
            </div>
        </div>
        <div class="px-5 pb-8 text-center">
            <x-base.button
                class="mr-1 w-24"
                data-tw-dismiss="modal"
                type="button"
                variant="outline-secondary"
            >
                Cancel
            </x-base.button>
            <form id="deleteAccidentForm" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <x-base.button class="w-24" type="submit" variant="danger">
                    Delete
                </x-base.button>
            </form>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>
@endsection

@section('script')
<script>
function confirmDeleteAccident(accidentId) {
    const form = document.getElementById('deleteAccidentForm');
    form.action = `{{ url('carrier/carrier-driver-accidents') }}/${accidentId}`;
    
    const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteAccidentModal'));
    modal.show();
}
</script>
@endsection
