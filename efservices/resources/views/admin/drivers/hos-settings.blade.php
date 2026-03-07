@extends('../themes/' . $activeTheme)
@section('title', 'Driver HOS Settings')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'HOS Management', 'url' => route('admin.hos.dashboard')],
        ['label' => 'Driver HOS Settings', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Settings" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Driver HOS Settings</h1>
                <p class="text-slate-600">Manage driver weekly cycle configurations across all carriers</p>
            </div>
        </div>
        @if($pendingCount > 0)
            <div class="flex items-center gap-2 px-4 py-2 bg-warning/10 rounded-lg border border-warning/20">
                <x-base.lucide class="w-5 h-5 text-warning" icon="Bell" />
                <span class="font-medium text-warning">{{ $pendingCount }} pending request{{ $pendingCount > 1 ? 's' : '' }}</span>
            </div>
        @endif
    </div>
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

<!-- Filters -->
<div class="box box--stacked mb-6">
    <div class="p-6 border-b border-slate-200/60">
        <div class="flex flex-wrap gap-4 items-end">
            <!-- Carrier Filter -->
            <div class="flex-1 min-w-[200px]">
                <x-base.form-label for="carrier_filter">Filter by Carrier</x-base.form-label>
                <x-base.form-select id="carrier_filter" class="w-full">
                    <option value="">All Carriers</option>
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}" {{ $selectedCarrierId == $carrier->id ? 'selected' : '' }}>
                            {{ $carrier->name }}
                        </option>
                    @endforeach
                </x-base.form-select>
            </div>

            <!-- Driver Search -->
            <div class="flex-1 min-w-[200px]">
                <x-base.form-label for="driver_search">Search Driver</x-base.form-label>
                <div class="relative">
                    <x-base.lucide class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" icon="Search" />
                    <x-base.form-input id="driver_search" type="text" class="w-full pl-10" placeholder="Search by name or email..." />
                </div>
            </div>
            
            <!-- Status Filter -->
            <div class="flex gap-2">
                <x-base.button type="button" id="filter_all" data-filter="all"
                    variant="{{ $currentFilter === 'all' ? 'primary' : 'outline-primary' }}"
                    class="gap-2 filter-btn" size="sm">
                    <x-base.lucide class="w-4 h-4" icon="Users" />
                    All Drivers
                </x-base.button>
                <x-base.button type="button" id="filter_pending" data-filter="pending"
                    variant="{{ $currentFilter === 'pending' ? 'warning' : 'outline-warning' }}"
                    class="gap-2 filter-btn" size="sm">
                    <x-base.lucide class="w-4 h-4" icon="Clock" />
                    Pending
                    @if($pendingCount > 0)
                        <span class="ml-1 px-2 py-0.5 text-xs bg-white/50 rounded-full">{{ $pendingCount }}</span>
                    @endif
                </x-base.button>
            </div>
        </div>
    </div>
</div>

<!-- Drivers Table -->
<div class="box box--stacked">
    <div class="p-6">
        <!-- Results Counter -->
        <div class="mb-4 flex items-center justify-between">
            <div class="text-sm text-slate-600">
                Showing <span id="visible-count" class="font-semibold text-slate-800">{{ $drivers->count() }}</span> of <span class="font-semibold text-slate-800">{{ $drivers->count() }}</span> drivers
            </div>
        </div>

        @if($drivers->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Driver</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Carrier</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Current Cycle</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Pending Request</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="drivers-table-body">
                        @foreach($drivers as $driver)
                            <tr class="hover:bg-slate-50/50 driver-row {{ $driver->hos_cycle_change_requested ? 'bg-warning/5' : '' }}"
                                data-carrier-id="{{ $driver->carrier_id }}"
                                data-driver-name="{{ strtolower($driver->full_name) }}"
                                data-driver-email="{{ strtolower($driver->user->email ?? '') }}"
                                data-has-pending="{{ $driver->hos_cycle_change_requested ? '1' : '0' }}">
                                <!-- Driver Info -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center overflow-hidden">
                                            @if($driver->profile_photo_url)
                                                <img src="{{ $driver->profile_photo_url }}" alt="{{ $driver->full_name }}" class="w-full h-full object-cover">
                                            @else
                                                <x-base.lucide class="w-5 h-5 text-slate-400" icon="User" />
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-800">{{ $driver->full_name }}</div>
                                            <div class="text-xs text-slate-500">{{ $driver->user->email ?? 'No email' }}</div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Carrier -->
                                <td class="py-4 px-4">
                                    <span class="text-sm text-slate-700">{{ $driver->carrier->name ?? 'N/A' }}</span>
                                </td>
                                
                                <!-- Current Cycle -->
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium 
                                        {{ $driver->getEffectiveHosCycleType() === '70_8' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $driver->getEffectiveHosCycleType() === '70_8' ? '70h / 8d' : '60h / 7d' }}
                                    </span>
                                </td>
                                
                                <!-- Pending Request -->
                                <td class="py-4 px-4">
                                    @if($driver->hos_cycle_change_requested)
                                        <div class="flex flex-col gap-1">
                                            <span class="text-sm font-medium text-warning">
                                                Requesting: {{ $driver->hos_cycle_change_requested_to === '70_8' ? '70h / 8d' : '60h / 7d' }}
                                            </span>
                                            <span class="text-xs text-slate-500">
                                                {{ $driver->hos_cycle_change_requested_at ? $driver->hos_cycle_change_requested_at->diffForHumans() : '' }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-sm text-slate-400">—</span>
                                    @endif
                                </td>
                                
                                <!-- Actions -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($driver->hos_cycle_change_requested)
                                            <form action="{{ route('admin.drivers.hos.approve', $driver) }}" method="POST" class="inline">
                                                @csrf
                                                <x-base.button type="submit" variant="success" size="sm" class="gap-1">
                                                    <x-base.lucide class="w-4 h-4" icon="Check" />
                                                    Approve
                                                </x-base.button>
                                            </form>
                                            <form action="{{ route('admin.drivers.hos.reject', $driver) }}" method="POST" class="inline">
                                                @csrf
                                                <x-base.button type="submit" variant="danger" size="sm" class="gap-1"
                                                    onclick="return confirm('Are you sure you want to reject this request?');">
                                                    <x-base.lucide class="w-4 h-4" icon="X" />
                                                    Reject
                                                </x-base.button>
                                            </form>
                                        @else
                                            <!-- Quick Change Dropdown -->
                                            <div class="relative" x-data="{ open: false }">
                                                <x-base.button @click="open = !open" variant="outline-secondary" size="sm" class="gap-1">
                                                    <x-base.lucide class="w-4 h-4" icon="Settings" />
                                                    Change
                                                </x-base.button>
                                                <div x-show="open" @click.away="open = false" 
                                                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 z-50">
                                                    <div class="p-2">
                                                        <form action="{{ route('admin.drivers.hos.update', $driver) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="hos_cycle_type" value="60_7">
                                                            <button type="submit" 
                                                                class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 rounded {{ $driver->getEffectiveHosCycleType() === '60_7' ? 'text-primary font-medium' : 'text-slate-700' }}">
                                                                60 hours / 7 days
                                                                @if($driver->getEffectiveHosCycleType() === '60_7')
                                                                    <x-base.lucide class="w-4 h-4 inline ml-2" icon="Check" />
                                                                @endif
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('admin.drivers.hos.update', $driver) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="hos_cycle_type" value="70_8">
                                                            <button type="submit" 
                                                                class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 rounded {{ $driver->getEffectiveHosCycleType() === '70_8' ? 'text-primary font-medium' : 'text-slate-700' }}">
                                                                70 hours / 8 days
                                                                @if($driver->getEffectiveHosCycleType() === '70_8')
                                                                    <x-base.lucide class="w-4 h-4 inline ml-2" icon="Check" />
                                                                @endif
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="flex flex-col items-center">
                    <div class="bg-slate-100 rounded-full p-4 mb-4">
                        <x-base.lucide class="w-8 h-8 text-slate-400" icon="Users" />
                    </div>
                    <p class="text-slate-600 font-medium mb-1">No drivers found</p>
                    <p class="text-sm text-slate-500">No active drivers available.</p>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const carrierFilter = document.getElementById('carrier_filter');
    const driverSearch = document.getElementById('driver_search');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const driverRows = document.querySelectorAll('.driver-row');
    const tableBody = document.getElementById('drivers-table-body');
    const visibleCountEl = document.getElementById('visible-count');
    const totalDrivers = driverRows.length;
    
    let currentStatusFilter = 'all';
    
    // Function to apply all filters
    function applyFilters() {
        const selectedCarrier = carrierFilter.value;
        const searchTerm = driverSearch.value.toLowerCase().trim();
        let visibleCount = 0;
        
        driverRows.forEach(row => {
            let show = true;
            
            // Carrier filter
            if (selectedCarrier && row.dataset.carrierId !== selectedCarrier) {
                show = false;
            }
            
            // Status filter
            if (currentStatusFilter === 'pending' && row.dataset.hasPending !== '1') {
                show = false;
            }
            
            // Search filter
            if (searchTerm) {
                const driverName = row.dataset.driverName;
                const driverEmail = row.dataset.driverEmail;
                if (!driverName.includes(searchTerm) && !driverEmail.includes(searchTerm)) {
                    show = false;
                }
            }
            
            // Show/hide row
            if (show) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update counter
        if (visibleCountEl) {
            visibleCountEl.textContent = visibleCount;
        }
        
        // Show/hide empty state
        showEmptyState(visibleCount === 0);
    }
    
    // Function to show/hide empty state
    function showEmptyState(show) {
        let emptyState = document.getElementById('empty-state');
        
        if (show) {
            if (!emptyState) {
                emptyState = document.createElement('tr');
                emptyState.id = 'empty-state';
                emptyState.innerHTML = `
                    <td colspan="5" class="py-12 text-center">
                        <div class="flex flex-col items-center">
                            <div class="bg-slate-100 rounded-full p-4 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                            <p class="text-slate-600 font-medium mb-1">No drivers found</p>
                            <p class="text-sm text-slate-500">Try adjusting your filters</p>
                        </div>
                    </td>
                `;
                tableBody.appendChild(emptyState);
            }
        } else {
            if (emptyState) {
                emptyState.remove();
            }
        }
    }
    
    // Carrier filter change
    carrierFilter.addEventListener('change', function() {
        applyFilters();
    });
    
    // Driver search input
    driverSearch.addEventListener('input', function() {
        applyFilters();
    });
    
    // Status filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            currentStatusFilter = filter;
            
            // Update button styles
            filterButtons.forEach(btn => {
                if (btn.dataset.filter === filter) {
                    if (filter === 'pending') {
                        btn.classList.remove('btn-outline-warning');
                        btn.classList.add('btn-warning');
                    } else {
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.add('btn-primary');
                    }
                } else {
                    if (btn.dataset.filter === 'pending') {
                        btn.classList.remove('btn-warning');
                        btn.classList.add('btn-outline-warning');
                    } else {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline-primary');
                    }
                }
            });
            
            applyFilters();
        });
    });
    
    // Initial filter application
    applyFilters();
});
</script>
@endpush
