@extends('../themes/' . $activeTheme)
@section('title', 'My Vehicles')
@php
$breadcrumbLinks = [
['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
['label' => 'My Vehicles', 'active' => true],
];
@endphp
@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                My Vehicles
            </div>
            <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                <x-base.button as="a"
                    class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                    variant="outline-secondary" href="{{ route('carrier.vehicles-documents.index') }}">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="FileText" />
                    Documents Overview
                </x-base.button>
                <x-base.button as="a"
                    class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                    variant="primary" href="{{ route('carrier.vehicles.create') }}">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="PenLine" />
                    Add New Vehicle
                </x-base.button>
            </div>
        </div>
        <div class="mt-3.5">
            <div class="box box--stacked flex flex-col">
                <form method="GET" action="{{ route('carrier.vehicles.index') }}" id="filterForm">
                    <!-- Hidden filter fields -->
                    <input type="hidden" name="status" id="hidden_status" value="{{ request('status') }}">
                    <input type="hidden" name="type" id="hidden_type" value="{{ request('type') }}">
                    <input type="hidden" name="make" id="hidden_make" value="{{ request('make') }}">
                    <input type="hidden" name="driver_id" id="hidden_driver_id" value="{{ request('driver_id') }}">
                    <input type="hidden" name="per_page" id="hidden_per_page" value="{{ request('per_page', 10) }}">
                    
                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center">
                        <div>
                            <div class="relative">
                                <x-base.lucide
                                    class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500"
                                    icon="Search" />
                                <x-base.form-input class="rounded-[0.5rem] pl-9 sm:w-64" type="text"
                                    name="search" value="{{ request('search') }}"
                                    placeholder="Search by unit, make, model, VIN, registration..." />
                            </div>
                        </div>
                        <div class="flex flex-col gap-x-3 gap-y-2 sm:ml-auto sm:flex-row">
                            <!-- Export Dropdown -->
                            <x-base.menu>
                                <x-base.menu.button type="button" class="w-full sm:w-auto" as="x-base.button" variant="outline-secondary">
                                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Download" />
                                    Export
                                    <x-base.lucide class="ml-2 h-4 w-4 stroke-[1.3]" icon="ChevronDown" />
                                </x-base.menu.button>
                                <x-base.menu.items class="w-40">
                                    <x-base.menu.item onclick="exportData('pdf')">
                                        <x-base.lucide class="mr-2 h-4 w-4" icon="FileBarChart" />
                                        PDF
                                    </x-base.menu.item>
                                    <x-base.menu.item onclick="exportData('csv')">
                                        <x-base.lucide class="mr-2 h-4 w-4" icon="FileBarChart" />
                                        CSV
                                    </x-base.menu.item>
                                </x-base.menu.items>
                            </x-base.menu>
                            
                            <!-- Advanced Filter Popover -->
                            <x-base.popover class="inline-block">
                                <x-base.popover.button type="button" class="w-full sm:w-auto" as="x-base.button"
                                    variant="outline-secondary">
                                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowDownWideNarrow" />
                                    Filter
                                    @php
                                    $activeFilters = 0;
                                    if (request('status')) $activeFilters++;
                                    if (request('type')) $activeFilters++;
                                    if (request('make')) $activeFilters++;
                                    if (request('driver_id')) $activeFilters++;
                                    @endphp
                                    @if($activeFilters > 0)
                                    <span
                                        class="ml-2 flex h-5 items-center justify-center rounded-full border bg-slate-100 px-1.5 text-xs font-medium">
                                        {{ $activeFilters }}
                                    </span>
                                    @endif
                                </x-base.popover.button>
                                <x-base.popover.panel>
                                    <div class="p-2">
                                        <!-- Status Filter -->
                                        <div>
                                            <div class="text-left text-slate-500">
                                                Status
                                            </div>
                                            <x-base.form-select id="popover_status" class="mt-2 flex-1" onchange="updateHiddenField('status', this.value)">
                                                <option value="">All Status</option>
                                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="out_of_service" {{ request('status') == 'out_of_service' ? 'selected' : '' }}>Out Of Service</option>
                                                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                            </x-base.form-select>
                                        </div>
                                        
                                        <!-- Vehicle Type Filter -->
                                        <div class="mt-3">
                                            <div class="text-left text-slate-500">
                                                Vehicle Type
                                            </div>
                                            <x-base.form-select id="popover_type" class="mt-2 flex-1" onchange="updateHiddenField('type', this.value)">
                                                <option value="">All Types</option>
                                                <!-- Types will be loaded dynamically -->
                                            </x-base.form-select>
                                        </div>
                                        
                                        <!-- Vehicle Make Filter -->
                                        <div class="mt-3">
                                            <div class="text-left text-slate-500">
                                                Vehicle Make
                                            </div>
                                            <x-base.form-select id="popover_make" class="mt-2 flex-1" onchange="updateHiddenField('make', this.value)">
                                                <option value="">All Makes</option>
                                                <!-- Makes will be loaded dynamically -->
                                            </x-base.form-select>
                                        </div>
                                        
                                        <!-- Driver Filter -->
                                        <div class="mt-3">
                                            <div class="text-left text-slate-500">
                                                Assigned Driver
                                            </div>
                                            <x-base.form-select id="popover_driver_id" class="mt-2 flex-1" onchange="updateHiddenField('driver_id', this.value)">
                                                <option value="">All Drivers</option>
                                                @foreach ($drivers as $driver)
                                                <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                                    {{ $driver->user->name ?? 'Unknown Driver' }}
                                                </option>
                                                @endforeach
                                            </x-base.form-select>
                                        </div>
                                        
                                        <!-- Filter Actions -->
                                        <div class="mt-4 flex items-center">
                                            <x-base.button type="button" onclick="clearAllFilters()" class="ml-auto w-32" variant="secondary">
                                                Clear All
                                            </x-base.button>
                                            <x-base.button type="button" onclick="applyFilters()" class="ml-2 w-32" variant="primary">
                                                Apply
                                            </x-base.button>
                                        </div>
                                    </div>
                                </x-base.popover.panel>
                            </x-base.popover>
                            
                            <!-- Page Size Selector -->
                            <x-base.form-select class="w-20" onchange="changePageSize(this.value)">
                                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100</option>
                            </x-base.form-select>
                        </div>
                    </div>
                </form>  
              
                <!-- Responsive Vehicle Table -->
                <div class="overflow-auto xl:overflow-visible">
                    <x-base.table class="border-b border-slate-200/60">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Make/Model
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Unit
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Type
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    VIN
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Registration
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                    Assigned Driver
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                    Status
                                </x-base.table.td>
                                <x-base.table.td
                                    class="w-36 border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                    Actions
                                </x-base.table.td>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @forelse ($vehicles as $vehicle)
                            <x-base.table.tr class="[&_td]:last:border-b-0">
                                <!-- Make/Model Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <a class="font-medium text-primary hover:underline" 
                                       href="{{ route('carrier.vehicles.show', $vehicle->id) }}">
                                        {{ $vehicle->make }} {{ $vehicle->model }}
                                    </a>
                                    <div class="mt-0.5 whitespace-nowrap text-xs text-slate-500">
                                        Year: {{ $vehicle->year }}
                                    </div>
                                </x-base.table.td>
                                
                                <!-- Unit Number Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <div class="font-medium">{{ $vehicle->company_unit_number ?: 'N/A' }}</div>
                                </x-base.table.td>
                                
                                <!-- Type Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <div class="whitespace-nowrap">
                                        {{ $vehicle->type ?: 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                
                                <!-- VIN Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <div class="whitespace-nowrap font-mono text-xs">
                                        {{ $vehicle->vin }}
                                    </div>
                                </x-base.table.td>
                                
                                <!-- Registration Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <div>
                                        <div class="whitespace-nowrap">{{ $vehicle->registration_number }}</div>
                                        <div class="mt-0.5 whitespace-nowrap text-xs text-slate-500">
                                            @if($vehicle->registration_expiration_date)
                                                Expires: {{ $vehicle->registration_expiration_date->format('m/d/Y') }}
                                            @else
                                                No expiration date
                                            @endif
                                        </div>
                                    </div>
                                </x-base.table.td>
                                
                                <!-- Driver Assignment Column -->
                                <x-base.table.td class="border-dashed py-4">
                                    <div class="whitespace-nowrap">
                                        @if ($vehicle->activeDriverAssignment && $vehicle->activeDriverAssignment->driver && $vehicle->activeDriverAssignment->driver->user)
                                            <div class="font-medium">{{ $vehicle->activeDriverAssignment->driver->user->name }}</div>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                @php
                                                    $assignmentType = $vehicle->activeDriverAssignment->driver_type ?? 'company_driver';
                                                    $typeLabels = [
                                                        'owner_operator' => 'Owner Operator',
                                                        'company_driver' => 'Company Driver', 
                                                        'third_party' => 'Third Party'
                                                    ];
                                                @endphp
                                                {{ $typeLabels[$assignmentType] ?? ucfirst(str_replace('_', ' ', $assignmentType)) }}
                                            </div>
                                        @elseif($vehicle->driver && $vehicle->driver->user)
                                            <div class="font-medium">{{ $vehicle->driver->user->name }}</div>
                                            <div class="text-xs text-slate-500 mt-0.5">Company Driver</div>
                                        @else
                                            <span class="text-slate-400">Not assigned</span>
                                        @endif
                                    </div>
                                </x-base.table.td>
                                
                                <!-- Status Column with Indicators -->
                                <x-base.table.td class="border-dashed py-4">
                                    <div @class([
                                        'flex items-center justify-center',
                                        'text-success' => !$vehicle->out_of_service && !$vehicle->suspended,
                                        'text-warning' => $vehicle->suspended,
                                        'text-danger' => $vehicle->out_of_service,
                                    ])>
                                        <x-base.lucide class="h-3.5 w-3.5 stroke-[1.7]"
                                            icon="{{ !$vehicle->out_of_service && !$vehicle->suspended ? 'CheckCircle' : ($vehicle->suspended ? 'AlertTriangle' : 'XCircle') }}" />
                                        <div class="ml-1.5 whitespace-nowrap">
                                            @if ($vehicle->out_of_service)
                                                Out Of Service
                                            @elseif($vehicle->suspended)
                                                Suspended
                                            @else
                                                Active
                                            @endif
                                        </div>
                                    </div>
                                </x-base.table.td>
                                
                                <!-- Actions Column -->
                                <x-base.table.td class="relative border-dashed py-4">
                                    <div class="flex items-center justify-center">
                                        <x-base.menu class="h-5">
                                            <x-base.menu.button class="h-5 w-5 text-slate-500">
                                                <x-base.lucide
                                                    class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70"
                                                    icon="MoreVertical" />
                                            </x-base.menu.button>
                                            <x-base.menu.items class="w-40">
                                                <x-base.menu.item
                                                    href="{{ route('carrier.vehicles.show', $vehicle->id) }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Eye" />
                                                    View Details
                                                </x-base.menu.item>
                                                <x-base.menu.item
                                                    href="{{ route('carrier.vehicles.edit', $vehicle->id) }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="CheckSquare" />
                                                    Edit
                                                </x-base.menu.item>
                                                <x-base.menu.item
                                                    href="{{ route('carrier.vehicles.documents.index', $vehicle->id) }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="FileText" />
                                                    Documents
                                                </x-base.menu.item>
                                                <x-base.menu.item
                                                    href="{{ route('carrier.vehicles.maintenance.index', $vehicle->id) }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Tool" />
                                                    Maintenance
                                                </x-base.menu.item>
                                                <x-base.menu.divider />
                                                <x-base.menu.item class="text-danger" data-tw-toggle="modal"
                                                    data-tw-target="#delete-confirmation-modal-{{ $vehicle->id }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Trash2" />
                                                    Delete
                                                </x-base.menu.item>
                                            </x-base.menu.items>
                                        </x-base.menu>
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>

                            <!-- Delete Confirmation Modal -->
                            <x-base.dialog id="delete-confirmation-modal-{{ $vehicle->id }}" size="md">
                                <x-base.dialog.panel>
                                    <div class="p-5 text-center">
                                        <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger"
                                            icon="XCircle" />
                                        <div class="mt-5 text-2xl">Are you sure?</div>
                                        <div class="mt-2 text-slate-500">
                                            Do you really want to delete this vehicle? <br>
                                            This process cannot be undone and will remove all associated documents and maintenance records.
                                        </div>
                                    </div>
                                    <div class="px-5 pb-8 text-center">
                                        <form action="{{ route('carrier.vehicles.destroy', $vehicle->id) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <x-base.button class="mr-1 w-24" data-tw-dismiss="modal"
                                                type="button" variant="outline-secondary">
                                                Cancel
                                            </x-base.button>
                                            <x-base.button class="w-24" type="submit" variant="danger">
                                                Delete
                                            </x-base.button>
                                        </form>
                                    </div>
                                </x-base.dialog.panel>
                            </x-base.dialog>
                            @empty
                            <x-base.table.tr>
                                <x-base.table.td colspan="8" class="border-dashed py-8 text-center">
                                    <div class="text-slate-500">
                                        <x-base.lucide class="mx-auto h-16 w-16 text-slate-300 mb-4" icon="Truck" />
                                        <div class="text-lg font-medium">No vehicles found</div>
                                        <div class="mt-1">
                                            @if(request()->hasAny(['search', 'status', 'type', 'make', 'driver_id']))
                                                Try adjusting your search criteria or 
                                                <button type="button" onclick="clearAllFilters()" class="text-primary hover:underline">clear all filters</button>
                                            @else
                                                <a href="{{ route('carrier.vehicles.create') }}" class="text-primary hover:underline">Add your first vehicle</a>
                                            @endif
                                        </div>
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                            @endforelse
                        </x-base.table.tbody>
                    </x-base.table>
                </div>        
        
                <!-- Pagination Section -->
                <div class="flex-reverse flex flex-col-reverse flex-wrap items-center gap-y-2 p-5 sm:flex-row">
                    <div class="mr-auto">
                        <div class="text-xs text-slate-500">
                            Showing {{ $vehicles->firstItem() ?? 0 }} to {{ $vehicles->lastItem() ?? 0 }} 
                            of {{ $vehicles->total() }} vehicles
                        </div>
                    </div>
                    <div class="w-full sm:w-auto">
                        {{ $vehicles->appends(request()->query())->links('custom.pagination') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Global variables for filter management
    let filterOptions = {};
    
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        initializeSearch();
        loadFilterOptions();
    });

    // Auto-submit search form on input with debounce
    function initializeSearch() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 500);
            });
        }
    }

    // Load filter options dynamically
    async function loadFilterOptions() {
        try {
            const response = await fetch('{{ route("carrier.vehicles.filter-options") }}');
            const data = await response.json();
            filterOptions = data;
            
            // Populate filter dropdowns
            populateFilterDropdown('popover_type', data.types, '{{ request("type") }}');
            populateFilterDropdown('popover_make', data.makes, '{{ request("make") }}');
            
        } catch (error) {
            console.error('Error loading filter options:', error);
        }
    }

    // Populate filter dropdown with options
    function populateFilterDropdown(selectId, options, selectedValue) {
        const select = document.getElementById(selectId);
        if (!select || !options) return;
        
        // Clear existing options except the first one (All...)
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }
        
        // Add new options
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;
            if (option.value === selectedValue) {
                optionElement.selected = true;
            }
            select.appendChild(optionElement);
        });
    }

    // Update hidden field function
    function updateHiddenField(fieldName, value) {
        const hiddenField = document.getElementById('hidden_' + fieldName);
        if (hiddenField) {
            hiddenField.value = value;
        }
    }

    // Clear all filters function
    function clearAllFilters() {
        const form = document.getElementById('filterForm');
        if (form) {
            // Clear search input
            const searchInput = form.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Clear hidden fields (except per_page)
            const hiddenFields = form.querySelectorAll('input[type="hidden"]');
            hiddenFields.forEach(field => {
                if (field.name !== '_token' && field.name !== 'per_page') {
                    field.value = '';
                }
            });
            
            // Clear popover selects
            const popoverSelects = [
                document.getElementById('popover_status'),
                document.getElementById('popover_type'),
                document.getElementById('popover_make'),
                document.getElementById('popover_driver_id')
            ];
            
            popoverSelects.forEach(select => {
                if (select) {
                    select.selectedIndex = 0;
                }
            });
            
            form.submit();
        }
    }

    // Apply filters function
    function applyFilters() {
        // Update hidden fields with popover values
        updateHiddenField('status', document.getElementById('popover_status').value);
        updateHiddenField('type', document.getElementById('popover_type').value);
        updateHiddenField('make', document.getElementById('popover_make').value);
        updateHiddenField('driver_id', document.getElementById('popover_driver_id').value);
        
        // Submit form
        document.getElementById('filterForm').submit();
    }

    // Change page size function
    function changePageSize(perPage) {
        updateHiddenField('per_page', perPage);
        document.getElementById('filterForm').submit();
    }

    // Export data function
    function exportData(format) {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        
        // Build query string from form data
        const params = new URLSearchParams();
        for (let [key, value] of formData.entries()) {
            if (value && key !== '_token') {
                params.append(key, value);
            }
        }
        
        // Create export URL
        const exportUrl = format === 'pdf' 
            ? '{{ route("carrier.vehicles.export.pdf") }}'
            : '{{ route("carrier.vehicles.export.csv") }}';
        
        // Open export in new window
        window.open(`${exportUrl}?${params.toString()}`, '_blank');
    }

    // Make functions available globally
    window.clearAllFilters = clearAllFilters;
    window.applyFilters = applyFilters;
    window.updateHiddenField = updateHiddenField;
    window.changePageSize = changePageSize;
    window.exportData = exportData;
</script>
@endpush