@extends('../themes/' . $activeTheme)
@section('title', 'Driver Inspections Management')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('carrier.dashboard')],
['label' => 'Driver Inspections Management', 'active' => true],
];
@endphp
@section('subcontent')
<div>
    <!-- Mensajes Flash -->
    @if (session()->has('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
        {{ session('success') }}
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="alert-triangle" />
        {{ session('error') }}
    </div>
    @endif
    
    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="FileCheck" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Driver Inspections Management</h1>
                    <p class="text-slate-600">Manage and track driver inspections</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
            <x-base.button as="a" href="{{ route('carrier.drivers.inspections.create') }}" variant="primary"
                class="flex items-center">
                <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                Add Inspection
            </x-base.button>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="box box--stacked mt-5">
        <div class="box-body p-5">
            <form action="{{ route('carrier.drivers.inspections.index') }}" method="GET" id="filter-form"
                class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <x-base.lucide
                            class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500"
                            icon="Search" />
                        <x-base.form-input class="rounded-[0.5rem] pl-9" name="search_term"
                            value="{{ request('search_term') }}" type="text" placeholder="Search inspections..." />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Driver</label>
                    <select name="driver_filter" id="driver_filter"
                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Drivers</option>
                        @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}"
                            {{ request('driver_filter') == $driver->id ? 'selected' : '' }}>
                            {{ $driver->user->name }} {{ $driver->last_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Vehicle</label>
                    <select name="vehicle_filter" id="vehicle_filter"
                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Vehicles</option>
                        @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}"
                            {{ request('vehicle_filter') == $vehicle->id ? 'selected' : '' }}>
                            {{ $vehicle->company_unit_number ?? 'N/A' }} - {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <x-base.litepicker id="date_from" name="date_from" class="w-full"
                        value="{{ request('date_from') }}" data-format="MM-DD-YYYY" placeholder="MM-DD-YYYY" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <x-base.litepicker id="date_to" name="date_to" class="w-full"
                        value="{{ request('date_to') }}" data-format="MM-DD-YYYY" placeholder="MM-DD-YYYY" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inspection Type</label>
                    <select name="inspection_type" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Types</option>
                        @foreach ($inspectionTypes as $type)
                        <option value="{{ $type }}" {{ request('inspection_type') == $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" {{ request('status') == $statusOption ? 'selected' : '' }}>
                            {{ $statusOption }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <x-base.button type="submit" variant="primary" class="mr-2">
                        <x-base.lucide class="w-4 h-4 mr-1" icon="filter" />
                        Apply Filters
                    </x-base.button>

                    <x-base.button type="button" id="clear-filters" class="btn btn-outline-secondary" variant="outline-primary">
                        <x-base.lucide class="w-4 h-4 mr-1" icon="x" />
                        Clear Filters
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="box box--stacked mt-5">
        <div class="box-body p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead>
                        <tr>
                            <th scope="col" class="px-6 py-3">Created At</th>
                            <th scope="col" class="px-6 py-3">Driver</th>
                            <th scope="col" class="px-6 py-3">Vehicle</th>
                            <th scope="col" class="px-6 py-3">Inspection Type</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">
                                <a href="{{ route(
                                        'carrier.drivers.inspections.index',
                                        array_merge(request()->query(), [
                                            'sort_field' => 'inspection_date',
                                            'sort_direction' => request('sort_field') == 'inspection_date' && request('sort_direction') == 'asc' ? 'desc' : 'asc',
                                        ]),
                                    ) }}" class="flex items-center">
                                    Inspection Date
                                    @if (request('sort_field') == 'inspection_date')
                                    <x-base.lucide class="w-4 h-4 ml-1" icon="{{ request('sort_direction') == 'asc' ? 'arrow-up' : 'arrow-down' }}" />
                                    @endif
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inspections as $inspection)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4">{{ $inspection->created_at->format('m/d/Y') }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($inspection->userDriverDetail)
                                <div class="flex items-center">
                                    <span>{{ $inspection->userDriverDetail->user->name }} {{ $inspection->userDriverDetail->last_name }}</span>
                                </div>
                                @else
                                <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($inspection->vehicle)
                                {{ $inspection->vehicle->company_unit_number ?? 'N/A' }} -
                                {{ $inspection->vehicle->year }}
                                {{ $inspection->vehicle->make }}
                                {{ $inspection->vehicle->model }}
                                @else
                                N/A
                                @endif
                            </td>
                            <td class="px-6 py-4">{{ $inspection->inspection_type }}</td>
                            <td class="px-6 py-4">
                                @if ($inspection->status == 'Pass')
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-success/20 text-success">
                                    Pass
                                </span>
                                @elseif ($inspection->status == 'Fail')
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-danger/20 text-danger">
                                    Fail
                                </span>
                                @else
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-warning/20 text-warning">
                                    {{ $inspection->status }}
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">{{ $inspection->inspection_date->format('m/d/Y') }}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center">
                                    <a href="{{ route('carrier.drivers.inspections.edit', $inspection) }}"
                                        class="btn btn-primary mr-2 p-1">
                                        <x-base.lucide class="w-4 h-4" icon="edit" />
                                    </a>
                                    <button type="button" class="btn btn-danger mr-2 p-1 delete-inspection" 
                                        data-inspection-id="{{ $inspection->id }}">
                                        <x-base.lucide class="w-4 h-4" icon="trash" />
                                    </button>
                                    <a href="{{ route('carrier.drivers.inspections.driver_history', $inspection->userDriverDetail->id) }}"
                                        class="btn btn-outline-secondary p-1">
                                        <x-base.lucide class="w-4 h-4" icon="eye" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="bg-white border-b">
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No inspection records found.
                                <x-base.button as="a" href="{{ route('carrier.drivers.inspections.create') }}"
                                    variant="outline-primary" class="mt-2">
                                    <x-base.lucide class="w-4 h-4 mr-1" icon="plus" />
                                    Add First Inspection Record
                                </x-base.button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Paginación -->
            <div class="mt-5 px-5 pb-5">
                {{ $inspections->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar Inspección -->
<x-base.dialog id="delete-inspection-modal">
    <x-base.dialog.panel>
        <x-base.dialog.title>
            <h2 class="mr-auto text-base font-medium">Delete Inspection Record</h2>
        </x-base.dialog.title>
        <form id="delete_inspection_form" action="" method="POST">
            @csrf
            @method('DELETE')
            <x-base.dialog.description>
                Are you sure you want to delete this inspection record? This action cannot be undone.
            </x-base.dialog.description>
            <x-base.dialog.footer>
                <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="mr-1 w-20">
                    Cancel
                </x-base.button>
                <x-base.button type="submit" variant="danger" class="w-20">
                    Delete
                </x-base.button>
            </x-base.dialog.footer>
        </form>
    </x-base.dialog.panel>
</x-base.dialog>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manejar el botón de limpiar filtros
        document.getElementById('clear-filters').addEventListener('click', function() {
            // Seleccionar todos los inputs y selects del formulario de filtros
            const form = document.getElementById('filter-form');
            const inputs = form.querySelectorAll('input:not([type="submit"]), select');

            // Resetear el valor de cada campo
            inputs.forEach(input => {
                if (input.type === 'date' || input.type === 'text') {
                    input.value = '';
                } else if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                }
            });

            // Enviar el formulario con valores limpios
            form.submit();
        });

        // Configuración del modal de eliminación
        const deleteButtons = document.querySelectorAll('.delete-inspection');
        const deleteModal = tailwind.Modal.getOrCreateInstance(document.querySelector("#delete-inspection-modal"));
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const inspectionId = this.getAttribute('data-inspection-id');
                document.getElementById('delete_inspection_form').action =
                    `/carrier/carrier-driver-inspections/${inspectionId}`;
                deleteModal.show();
            });
        });
    });
</script>
@endpush
@endsection
