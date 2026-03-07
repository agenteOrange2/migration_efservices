@extends('../themes/' . $activeTheme)
@section('title', 'Prospect Drivers Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Prospect Drivers Report', 'active' => true],
    ];
    
    $activeFiltersCount = 0;
    if (!empty(request('carrier_id'))) $activeFiltersCount++;
    if (!empty(request('status'))) $activeFiltersCount++;
    if (!empty(request('date_from'))) $activeFiltersCount++;
    if (!empty(request('date_to'))) $activeFiltersCount++;
    if (!empty(request('search'))) $activeFiltersCount++;
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Prospect Drivers Report</h1>
                    <p class="text-slate-600 mt-1">Track and manage driver applications</p>
                </div>
            </div>
            <div class="flex gap-3">
                <x-base.button as="a" href="{{ route('admin.reports.index') }}" variant="outline-secondary" class="gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Reports
                </x-base.button>
                <x-base.button id="export-pdf-inline" variant="primary" class="gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Export PDF
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <!-- Total Prospects -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="primary" class="text-xs">Total</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Prospects</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($prospects->total()) }}</div>
            <div class="text-xs text-slate-500 mt-2">All applications</div>
        </div>

        <!-- Draft -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 8v4M12 16h.01" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="warning" class="text-xs">Draft</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Draft</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($prospects->where('status', 'draft')->count()) }}</div>
            <div class="text-xs text-slate-500 mt-2">Incomplete applications</div>
        </div>

        <!-- Pending -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-info/10 rounded-lg">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="info" class="text-xs">Pending</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Pending</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($prospects->where('status', 'pending')->count()) }}</div>
            <div class="text-xs text-slate-500 mt-2">Under review</div>
        </div>

        <!-- Rejected -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="danger" class="text-xs">Rejected</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Rejected</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($prospects->where('status', 'rejected')->count()) }}</div>
            <div class="text-xs text-slate-500 mt-2">Not approved</div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <!-- Filters Bar -->
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <!-- Search -->
                <div class="flex-1 w-full lg:w-auto">
                    <form action="{{ route('admin.reports.driver-prospects') }}" method="GET" id="search-form">
                        @if(!empty(request('carrier_id')))
                            <input type="hidden" name="carrier_id" value="{{ request('carrier_id') }}">
                        @endif
                        @if(!empty(request('status')))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        @if(!empty(request('date_from')))
                            <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        @endif
                        @if(!empty(request('date_to')))
                            <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        @endif
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 21l-4.35-4.35" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <x-base.form-input 
                                id="table-search"
                                class="pl-10 w-full lg:w-80" 
                                type="text" 
                                name="search"
                                value="{{ request('search') }}" 
                                placeholder="Search prospects..." />
                        </div>
                    </form>
                </div>

                <!-- Filter Buttons -->
                <div class="flex gap-3">
                    <x-base.popover class="inline-block">
                        <x-base.popover.button as="x-base.button" variant="outline-secondary" class="gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6h18M7 12h10M5 18h14" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Filters
                            @if($activeFiltersCount > 0)
                                <span class="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-medium text-white">
                                    {{ $activeFiltersCount }}
                                </span>
                            @endif
                        </x-base.popover.button>
                        <x-base.popover.panel>
                            <div class="p-4 w-80">
                                <form method="GET" action="{{ route('admin.reports.driver-prospects') }}">
                                    <div class="mb-4">
                                        <x-base.form-label>Carrier</x-base.form-label>
                                        <x-base.form-select name="carrier_id" class="mt-2">
                                            <option value="">All Carriers</option>
                                            @foreach($carriers as $carrier)
                                                <option value="{{ $carrier->id }}" {{ request('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                                    {{ $carrier->name }}
                                                </option>
                                            @endforeach
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Status</x-base.form-label>
                                        <x-base.form-select name="status" class="mt-2">
                                            <option value="">All Statuses</option>
                                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Date Range</x-base.form-label>
                                        <div class="grid grid-cols-2 gap-2 mt-2">
                                            <div>
                                                <x-base.form-label class="text-xs">From</x-base.form-label>
                                                <x-base.form-input type="date" name="date_from" value="{{ request('date_from') }}" />
                                            </div>
                                            <div>
                                                <x-base.form-label class="text-xs">To</x-base.form-label>
                                                <x-base.form-input type="date" name="date_to" value="{{ request('date_to') }}" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Search</x-base.form-label>
                                        <x-base.form-input
                                            type="text"
                                            name="search"
                                            value="{{ request('search') }}"
                                            placeholder="Name, email, phone..."
                                            class="mt-2"
                                        />
                                    </div>

                                    <div class="flex gap-2">
                                        <x-base.button class="flex-1" variant="outline-secondary" as="a"
                                            href="{{ route('admin.reports.driver-prospects') }}">
                                            Clear
                                        </x-base.button>
                                        <x-base.button class="flex-1" variant="primary" type="submit">
                                            Apply
                                        </x-base.button>
                                    </div>
                                </form>
                            </div>
                        </x-base.popover.panel>
                    </x-base.popover>
                </div>
            </div>
        </div>

        @if($prospects->total() > 0)
            <!-- Table -->
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Driver</x-base.table.th>
                            <x-base.table.th>Email</x-base.table.th>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th>Type</x-base.table.th>
                            <x-base.table.th>Status</x-base.table.th>
                            <x-base.table.th>Registration Date</x-base.table.th>
                            <x-base.table.th class="text-center">Actions</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach($prospects as $prospect)
                            @php
                                $driverName = trim(($prospect->user->name ?? 'N/A') . ' ' . ($prospect->userDriverDetail->middle_name ?? '') . ' ' . ($prospect->userDriverDetail->last_name ?? ''));
                                $driverPhone = $prospect->userDriverDetail->phone ?? ($prospect->user->phone ?? '');
                                $driverImageUrl = null;
                                if ($prospect->userDriverDetail) {
                                    $driverImageUrl = $prospect->userDriverDetail->getFirstMediaUrl('profile_photo_driver') ?: null;
                                }
                            @endphp
                            <x-base.table.tr>
                                <x-base.table.td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            @if($driverImageUrl)
                                                <img src="{{ $driverImageUrl }}" alt="{{ $driverName }}" class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $driverName }}</div>
                                            @if($driverPhone)
                                                <div class="text-xs text-slate-500">{{ $driverPhone }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">{{ $prospect->user->email ?? 'N/A' }}</div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        {{ $prospect->userDriverDetail->carrier->name ?? 'No carrier' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if($prospect->isOwnerOperator())
                                        <x-base.badge variant="warning" class="text-xs">
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v1M5 17l6-6m0 0l6 6m-6-6v12" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Owner Operator
                                        </x-base.badge>
                                    @elseif($prospect->isThirdPartyDriver())
                                        <x-base.badge variant="info" class="text-xs">
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="9" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Third Party
                                        </x-base.badge>
                                    @else
                                        <x-base.badge variant="info" class="text-xs">
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Company Driver
                                        </x-base.badge>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    @switch($prospect->status)
                                        @case('draft')
                                            <x-base.badge variant="warning" class="text-xs">
                                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M12 8v4M12 16h.01" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Draft
                                            </x-base.badge>
                                            @break
                                        @case('pending')
                                            <x-base.badge variant="info" class="text-xs">
                                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Pending
                                            </x-base.badge>
                                            @break
                                        @case('rejected')
                                            <x-base.badge variant="danger" class="text-xs">
                                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Rejected
                                            </x-base.badge>
                                            @break
                                        @default
                                            <x-base.badge variant="info" class="text-xs">{{ ucfirst($prospect->status) }}</x-base.badge>
                                    @endswitch
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">{{ $prospect->created_at->format('M d, Y') }}</div>
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    @if($prospect->userDriverDetail && $prospect->userDriverDetail->id)
                                        <x-base.button as="a" href="{{ route('admin.driver-recruitment.show', $prospect->userDriverDetail->id) }}" 
                                            variant="outline-primary" 
                                            size="sm"
                                            class="gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            View
                                        </x-base.button>
                                    @else
                                        <span class="text-slate-400 text-xs">N/A</span>
                                    @endif
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>

            <!-- Pagination -->
            @if($prospects->hasPages())
                <div class="p-6 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-slate-600">
                            Showing <span class="font-medium">{{ $prospects->firstItem() }}</span> to 
                            <span class="font-medium">{{ $prospects->lastItem() }}</span> of 
                            <span class="font-medium">{{ $prospects->total() }}</span> results
                        </div>
                        <div>
                            {{ $prospects->appends(request()->except('page'))->links() }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No prospects found</h3>
                <p class="text-slate-600 mb-6 max-w-md mx-auto">
                    @if($activeFiltersCount > 0)
                        No driver prospects match your current filters. Try adjusting your search criteria.
                    @else
                        There are no driver prospect records in the system yet.
                    @endif
                </p>
                @if($activeFiltersCount > 0)
                    <x-base.button as="a" href="{{ route('admin.reports.driver-prospects') }}" variant="primary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Clear Filters
                    </x-base.button>
                @endif
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const exportPdfInlineBtn = document.getElementById('export-pdf-inline');
                
                if (exportPdfInlineBtn) {
                    exportPdfInlineBtn.addEventListener('click', function() {
                        const button = this;
                        const originalHTML = button.innerHTML;
                        
                        button.innerHTML = `
                            <svg class="animate-spin h-4 w-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Generating PDF...
                        `;
                        button.disabled = true;
                        
                        const currentParams = new URLSearchParams(window.location.search);
                        const params = new URLSearchParams();
                        
                        if (currentParams.has('carrier_id')) params.append('carrier_id', currentParams.get('carrier_id'));
                        if (currentParams.has('status')) params.append('status', currentParams.get('status'));
                        if (currentParams.has('date_from')) params.append('date_from', currentParams.get('date_from'));
                        if (currentParams.has('date_to')) params.append('date_to', currentParams.get('date_to'));
                        if (currentParams.has('search')) params.append('search', currentParams.get('search'));
                        
                        let url = '{{ route("admin.reports.driver-prospects.pdf") }}';
                        const queryString = params.toString();
                        const exportUrl = queryString ? `${url}?${queryString}` : url;
                        
                        window.open(exportUrl, '_blank');
                        
                        setTimeout(() => {
                            button.innerHTML = originalHTML;
                            button.disabled = false;
                        }, 3000);
                    });
                }
                
                const searchInput = document.getElementById('table-search');
                if (searchInput) {
                    searchInput.addEventListener('keypress', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            document.getElementById('search-form').submit();
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
