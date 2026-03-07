@extends('../themes/' . $activeTheme)
@section('title', 'Vehicle Documents Overview')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Vehicles', 'url' => route('carrier.vehicles.index')],
        ['label' => 'Documents Overview', 'active' => true],
    ];
@endphp
@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Vehicle Documents Overview
            </div>
            <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                <x-base.button as="a"
                    class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                    variant="outline-secondary" href="{{ route('carrier.vehicles.index') }}">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                    Back to Vehicles
                </x-base.button>
            </div>
        </div>

        {{-- Summary Statistics Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 mt-5">
            <div class="box box--stacked p-4 md:p-5 bg-white rounded-lg shadow transition-all duration-200 hover:shadow-md">
                <div class="flex items-center">
                    <div class="rounded-full bg-success/20 p-2.5 md:p-3 mr-3 flex-shrink-0">
                        <x-base.lucide class="h-4 w-4 md:h-5 md:w-5 text-success" icon="CheckCircle" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-slate-500 text-xs truncate">Active Documents</div>
                        <div class="font-medium text-lg md:text-xl">{{ $statistics['active'] }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-4 md:p-5 bg-white rounded-lg shadow transition-all duration-200 hover:shadow-md">
                <div class="flex items-center">
                    <div class="rounded-full bg-danger/20 p-2.5 md:p-3 mr-3 flex-shrink-0">
                        <x-base.lucide class="h-4 w-4 md:h-5 md:w-5 text-danger" icon="AlertOctagon" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-slate-500 text-xs truncate">Expired Documents</div>
                        <div class="font-medium text-lg md:text-xl">{{ $statistics['expired'] }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-4 md:p-5 bg-white rounded-lg shadow transition-all duration-200 hover:shadow-md">
                <div class="flex items-center">
                    <div class="rounded-full bg-warning/20 p-2.5 md:p-3 mr-3 flex-shrink-0">
                        <x-base.lucide class="h-4 w-4 md:h-5 md:w-5 text-warning" icon="Clock" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-slate-500 text-xs truncate">Pending Documents</div>
                        <div class="font-medium text-lg md:text-xl">{{ $statistics['pending'] }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-4 md:p-5 bg-white rounded-lg shadow transition-all duration-200 hover:shadow-md">
                <div class="flex items-center">
                    <div class="rounded-full bg-primary/20 p-2.5 md:p-3 mr-3 flex-shrink-0">
                        <x-base.lucide class="h-4 w-4 md:h-5 md:w-5 text-primary" icon="FileText" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-slate-500 text-xs truncate">Total Documents</div>
                        <div class="font-medium text-lg md:text-xl">{{ $statistics['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3.5">
            <div class="box box--stacked flex flex-col">
                <form method="GET" action="{{ route('carrier.vehicles-documents.index') }}" id="filterForm">
                    <!-- Hidden filter fields -->
                    <input type="hidden" name="vehicle_status" id="hidden_vehicle_status" value="{{ request('vehicle_status') }}">
                    <input type="hidden" name="document_type" id="hidden_document_type" value="{{ request('document_type') }}">
                    <input type="hidden" name="document_status" id="hidden_document_status" value="{{ request('document_status') }}">
                    
                    <div class="flex flex-col gap-y-2 p-4 md:p-5 sm:flex-row sm:items-center">
                        <div class="w-full sm:w-auto">
                            <div class="relative">
                                <x-base.lucide
                                    class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500"
                                    icon="Search" />
                                <x-base.form-input class="rounded-[0.5rem] pl-9 w-full sm:w-64 transition-all duration-150 focus:ring-2 focus:ring-primary/20" type="text"
                                    id="vehicle-search"
                                    placeholder="Search by make, model, year, VIN..." />
                            </div>
                        </div>
                        <div class="flex flex-col gap-x-3 gap-y-2 sm:ml-auto sm:flex-row">
                            <!-- Advanced Filter Popover -->
                            <x-base.popover class="inline-block">
                                <x-base.popover.button type="button" class="w-full sm:w-auto transition-all duration-150 hover:shadow-sm" as="x-base.button"
                                    variant="outline-secondary">
                                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowDownWideNarrow" />
                                    Filter
                                    @php
                                    $activeFilters = 0;
                                    if (request('vehicle_status')) $activeFilters++;
                                    if (request('document_type')) $activeFilters++;
                                    if (request('document_status')) $activeFilters++;
                                    @endphp
                                    @if($activeFilters > 0)
                                    <span
                                        class="ml-2 flex h-5 items-center justify-center rounded-full border bg-slate-100 px-1.5 text-xs font-medium transition-colors duration-150">
                                        {{ $activeFilters }}
                                    </span>
                                    @endif
                                </x-base.popover.button>
                                <x-base.popover.panel>
                                    <div class="p-3 md:p-4">
                                        <!-- Vehicle Status Filter -->
                                        <div>
                                            <div class="text-left text-slate-500 text-sm font-medium">
                                                Vehicle Status
                                            </div>
                                            <x-base.form-select id="popover_vehicle_status" class="mt-2 flex-1 transition-all duration-150 focus:ring-2 focus:ring-primary/20" onchange="updateHiddenField('vehicle_status', this.value)">
                                                <option value="">All Status</option>
                                                <option value="active" {{ request('vehicle_status') == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="out_of_service" {{ request('vehicle_status') == 'out_of_service' ? 'selected' : '' }}>Out Of Service</option>
                                                <option value="suspended" {{ request('vehicle_status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                            </x-base.form-select>
                                        </div>
                                        
                                        <!-- Document Type Filter -->
                                        <div class="mt-3">
                                            <div class="text-left text-slate-500 text-sm font-medium">
                                                Document Type
                                            </div>
                                            <x-base.form-select id="popover_document_type" class="mt-2 flex-1 transition-all duration-150 focus:ring-2 focus:ring-primary/20" onchange="updateHiddenField('document_type', this.value)">
                                                <option value="">All Types</option>
                                                @foreach ($documentTypes as $value => $label)
                                                <option value="{{ $value }}" {{ request('document_type') == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                                @endforeach
                                            </x-base.form-select>
                                        </div>
                                        
                                        <!-- Document Status Filter -->
                                        <div class="mt-3">
                                            <div class="text-left text-slate-500 text-sm font-medium">
                                                Document Status
                                            </div>
                                            <x-base.form-select id="popover_document_status" class="mt-2 flex-1 transition-all duration-150 focus:ring-2 focus:ring-primary/20" onchange="updateHiddenField('document_status', this.value)">
                                                <option value="">All Status</option>
                                                <option value="active" {{ request('document_status') == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="pending" {{ request('document_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="expired" {{ request('document_status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                                <option value="rejected" {{ request('document_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            </x-base.form-select>
                                        </div>
                                        
                                        <!-- Filter Actions -->
                                        <div class="mt-4 flex items-center gap-2">
                                            <x-base.button type="button" onclick="clearAllFilters()" class="ml-auto flex-1 sm:flex-none sm:w-32 transition-all duration-150 hover:shadow-sm" variant="secondary">
                                                Clear All
                                            </x-base.button>
                                            <x-base.button type="button" onclick="applyFilters()" class="flex-1 sm:flex-none sm:w-32 transition-all duration-150 hover:shadow-sm" variant="primary">
                                                Apply
                                            </x-base.button>
                                        </div>
                                    </div>
                                </x-base.popover.panel>
                            </x-base.popover>
                        </div>
                    </div>
                </form>  
              
                <!-- Responsive Vehicle Table -->
                <div class="overflow-x-auto -mx-5 sm:mx-0">
                    <x-base.table class="border-b border-slate-200/60">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-3 md:py-4 font-medium text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                    Vehicle
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-3 md:py-4 font-medium text-slate-500 text-xs md:text-sm whitespace-nowrap hidden lg:table-cell">
                                    VIN
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-3 md:py-4 text-center font-medium text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                    Status
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-3 md:py-4 text-center font-medium text-slate-500 text-xs md:text-sm whitespace-nowrap hidden md:table-cell">
                                    Total Docs
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-3 md:py-4 text-center font-medium text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                    Expired
                                </x-base.table.td>
                                <x-base.table.td
                                    class="border-t border-slate-200/60 bg-slate-50 py-3 md:py-4 text-center font-medium text-slate-500 text-xs md:text-sm whitespace-nowrap hidden sm:table-cell">
                                    Expiring Soon
                                </x-base.table.td>
                                <x-base.table.td
                                    class="w-24 md:w-36 border-t border-slate-200/60 bg-slate-50 py-3 md:py-4 text-center font-medium text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                    Actions
                                </x-base.table.td>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @forelse ($vehicles as $vehicle)
                            <x-base.table.tr class="[&_td]:last:border-b-0 vehicle-row transition-colors duration-150 hover:bg-slate-50/50"
                                data-make="{{ $vehicle->make }}"
                                data-model="{{ $vehicle->model }}"
                                data-year="{{ $vehicle->year }}"
                                data-vin="{{ $vehicle->vin }}"
                                data-carrier="{{ $vehicle->carrier->name ?? '' }}">
                                <!-- Vehicle Column -->
                                <x-base.table.td class="border-dashed py-3 md:py-4">
                                    <a class="font-medium text-primary hover:underline transition-colors duration-150" 
                                       href="{{ route('carrier.vehicles.show', $vehicle->id) }}">
                                        {{ $vehicle->make }} {{ $vehicle->model }}
                                    </a>
                                    <div class="mt-0.5 whitespace-nowrap text-xs text-slate-500">
                                        Year: {{ $vehicle->year }}
                                    </div>
                                    <div class="mt-0.5 lg:hidden text-xs text-slate-400 font-mono">
                                        {{ substr($vehicle->vin, -8) }}
                                    </div>
                                </x-base.table.td>
                                
                                <!-- VIN Column -->
                                <x-base.table.td class="border-dashed py-3 md:py-4 hidden lg:table-cell">
                                    <div class="whitespace-nowrap font-mono text-xs">
                                        {{ $vehicle->vin }}
                                    </div>
                                </x-base.table.td>
                                
                                <!-- Status Column with Indicators -->
                                <x-base.table.td class="border-dashed py-3 md:py-4">
                                    <div @class([
                                        'flex items-center justify-center',
                                        'text-success' => !$vehicle->out_of_service && !$vehicle->suspended,
                                        'text-warning' => $vehicle->suspended,
                                        'text-danger' => $vehicle->out_of_service,
                                    ])>
                                        <x-base.lucide class="h-3 w-3 md:h-3.5 md:w-3.5 stroke-[1.7]"
                                            icon="{{ !$vehicle->out_of_service && !$vehicle->suspended ? 'CheckCircle' : ($vehicle->suspended ? 'AlertTriangle' : 'XCircle') }}" />
                                        <div class="ml-1 md:ml-1.5 whitespace-nowrap text-xs md:text-sm">
                                            @if ($vehicle->out_of_service)
                                                <span class="hidden sm:inline">Out Of Service</span>
                                                <span class="sm:hidden">OOS</span>
                                            @elseif($vehicle->suspended)
                                                Suspended
                                            @else
                                                Active
                                            @endif
                                        </div>
                                    </div>
                                </x-base.table.td>
                                
                                <!-- Total Documents Column -->
                                <x-base.table.td class="border-dashed py-3 md:py-4 text-center hidden md:table-cell">
                                    <div class="font-medium text-sm">{{ $vehicle->documents->count() }}</div>
                                </x-base.table.td>
                                
                                <!-- Expired Documents Column -->
                                <x-base.table.td class="border-dashed py-3 md:py-4 text-center">
                                    @php
                                        $expiredCount = $vehicle->documents->filter(function($doc) {
                                            return $doc->isExpired();
                                        })->count();
                                    @endphp
                                    @if($expiredCount > 0)
                                        <div class="flex items-center justify-center text-danger">
                                            <x-base.lucide class="h-3 w-3 md:h-3.5 md:w-3.5 stroke-[1.7] mr-0.5 md:mr-1" icon="AlertOctagon" />
                                            <span class="font-medium text-xs md:text-sm">{{ $expiredCount }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs md:text-sm">0</span>
                                    @endif
                                </x-base.table.td>
                                
                                <!-- Expiring Soon Documents Column -->
                                <x-base.table.td class="border-dashed py-3 md:py-4 text-center hidden sm:table-cell">
                                    @php
                                        $expiringSoonCount = $vehicle->documents->filter(function($doc) {
                                            return $doc->isAboutToExpire() && !$doc->isExpired();
                                        })->count();
                                    @endphp
                                    @if($expiringSoonCount > 0)
                                        <div class="flex items-center justify-center text-warning">
                                            <x-base.lucide class="h-3 w-3 md:h-3.5 md:w-3.5 stroke-[1.7] mr-0.5 md:mr-1" icon="Clock" />
                                            <span class="font-medium text-xs md:text-sm">{{ $expiringSoonCount }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs md:text-sm">0</span>
                                    @endif
                                </x-base.table.td>
                                
                                <!-- Actions Column -->
                                <x-base.table.td class="relative border-dashed py-3 md:py-4">
                                    <div class="flex items-center justify-center gap-1 md:gap-2">
                                        <x-base.button as="a"
                                            href="{{ route('carrier.vehicles.documents.index', $vehicle->id) }}"
                                            variant="outline-primary"
                                            class="px-2 py-1 md:px-3 md:py-1.5 text-xs transition-all duration-150 hover:shadow-sm">
                                            <x-base.lucide class="h-3 w-3 md:h-3.5 md:w-3.5 md:mr-1" icon="FileText" />
                                            <span class="hidden md:inline">Documents</span>
                                        </x-base.button>
                                        <x-base.button as="a"
                                            href="{{ route('carrier.vehicles.show', $vehicle->id) }}"
                                            variant="outline-secondary"
                                            class="px-2 py-1 md:px-3 md:py-1.5 text-xs transition-all duration-150 hover:shadow-sm">
                                            <x-base.lucide class="h-3 w-3 md:h-3.5 md:w-3.5 md:mr-1" icon="Eye" />
                                            <span class="hidden lg:inline">Details</span>
                                        </x-base.button>
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                            @empty
                            <x-base.table.tr>
                                <x-base.table.td colspan="7" class="border-dashed py-8 md:py-12 text-center">
                                    <div class="text-slate-500 px-4">
                                        <x-base.lucide class="mx-auto h-12 w-12 md:h-16 md:w-16 text-slate-300 mb-3 md:mb-4" icon="Truck" />
                                        <div class="text-base md:text-lg font-medium">No vehicles found</div>
                                        <div class="mt-1 text-sm md:text-base">
                                            @if(request()->hasAny(['vehicle_status', 'document_type', 'document_status']))
                                                Try adjusting your search criteria or 
                                                <button type="button" onclick="clearAllFilters()" class="text-primary hover:underline transition-colors duration-150">clear all filters</button>
                                            @else
                                                <a href="{{ route('carrier.vehicles.create') }}" class="text-primary hover:underline transition-colors duration-150">Add your first vehicle</a>
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
                <div class="flex-reverse flex flex-col-reverse flex-wrap items-center gap-y-3 p-4 md:p-5 sm:flex-row">
                    <div class="mr-auto w-full sm:w-auto text-center sm:text-left">
                        <div class="text-xs md:text-sm text-slate-500">
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
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        initializeSearch();
        addLoadingIndicators();
        addFocusStates();
    });

    // Client-side search filtering with visual feedback
    function initializeSearch() {
        const searchInput = document.getElementById('vehicle-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const searchTerm = this.value.toLowerCase();
                    const vehicleRows = document.querySelectorAll('.vehicle-row');
                    let visibleCount = 0;
                    
                    vehicleRows.forEach(row => {
                        const make = row.dataset.make.toLowerCase();
                        const model = row.dataset.model.toLowerCase();
                        const year = row.dataset.year.toLowerCase();
                        const vin = row.dataset.vin.toLowerCase();
                        const carrier = row.dataset.carrier.toLowerCase();
                        
                        const matches = make.includes(searchTerm) || 
                                      model.includes(searchTerm) || 
                                      year.includes(searchTerm) || 
                                      vin.includes(searchTerm) ||
                                      carrier.includes(searchTerm);
                        
                        if (matches) {
                            row.style.display = '';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.style.transition = 'opacity 0.2s ease-in-out';
                                row.style.opacity = '1';
                            }, 10);
                            visibleCount++;
                        } else {
                            row.style.transition = 'opacity 0.15s ease-in-out';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.style.display = 'none';
                            }, 150);
                        }
                    });
                    
                    // Update empty state visibility
                    updateEmptyState(visibleCount);
                }, 150); // Debounce for better performance
            });
        }
    }

    // Update empty state based on visible rows
    function updateEmptyState(visibleCount) {
        const emptyRow = document.querySelector('x-base\\.table\\.tr:has(x-base\\.table\\.td[colspan])');
        if (emptyRow && visibleCount === 0) {
            emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }
    }

    // Add loading indicators to buttons
    function addLoadingIndicators() {
        const buttons = document.querySelectorAll('x-base\\.button[type="button"]');
        buttons.forEach(button => {
            button.addEventListener('click', function() {
                if (this.onclick && this.onclick.toString().includes('Filters')) {
                    this.classList.add('opacity-75', 'cursor-wait');
                }
            });
        });
    }

    // Add focus states for accessibility
    function addFocusStates() {
        const searchInput = document.getElementById('vehicle-search');
        if (searchInput) {
            searchInput.addEventListener('focus', function() {
                this.parentElement.classList.add('ring-2', 'ring-primary/20');
            });
            searchInput.addEventListener('blur', function() {
                this.parentElement.classList.remove('ring-2', 'ring-primary/20');
            });
        }
    }

    // Update hidden field when filter changes
    function updateHiddenField(fieldName, value) {
        const hiddenField = document.getElementById('hidden_' + fieldName);
        if (hiddenField) {
            hiddenField.value = value;
        }
    }

    // Apply filters and submit form (reset to page 1)
    function applyFilters() {
        // Show loading state
        const applyButton = event.target;
        applyButton.classList.add('opacity-75', 'cursor-wait');
        applyButton.disabled = true;
        
        // Remove any existing page parameter to reset to page 1
        const form = document.getElementById('filterForm');
        const url = new URL(form.action);
        
        // Add filter values to URL
        const vehicleStatus = document.getElementById('hidden_vehicle_status').value;
        const documentType = document.getElementById('hidden_document_type').value;
        const documentStatus = document.getElementById('hidden_document_status').value;
        
        if (vehicleStatus) url.searchParams.set('vehicle_status', vehicleStatus);
        if (documentType) url.searchParams.set('document_type', documentType);
        if (documentStatus) url.searchParams.set('document_status', documentStatus);
        
        // Explicitly remove page parameter to reset to page 1
        url.searchParams.delete('page');
        
        // Navigate to the new URL
        window.location.href = url.toString();
    }

    // Clear all filters (reset to page 1)
    function clearAllFilters() {
        // Show loading state
        const clearButton = event.target;
        clearButton.classList.add('opacity-75', 'cursor-wait');
        clearButton.disabled = true;
        
        // Simply navigate to the base URL without any parameters
        window.location.href = document.getElementById('filterForm').action;
    }
</script>
@endpush
