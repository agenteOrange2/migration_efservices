@extends('../themes/' . $activeTheme)
@section('title', 'Carrier Documents Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Carrier Documents Report', 'active' => true],
    ];
    
    $activeFiltersCount = 0;
    if($search) $activeFiltersCount++;
    if($statusFilter !== '') $activeFiltersCount++;
    if($dateFrom) $activeFiltersCount++;
    if($dateTo) $activeFiltersCount++;
    if($perPage != 20) $activeFiltersCount++;
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Carrier Documents Report</h1>
                    <p class="text-slate-600 mt-1">Track and manage carrier documentation</p>
                </div>
            </div>
            <div class="flex gap-3">
                <x-base.button as="a" href="{{ route('admin.reports.index') }}" variant="outline-secondary" class="gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Reports
                </x-base.button>
                <x-base.button onclick="exportToPDF()" id="exportPdfBtn" variant="primary" class="gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span id="exportBtnText">Export PDF</span>
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <!-- Total Carriers -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM19 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13 16V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1m8-1a1 1 0 0 1-1 1H9m4-1V8a1 1 0 0 1 1-1h2.586a1 1 0 0 1 .707.293l3.414 3.414a1 1 0 0 1 .293.707V16a1 1 0 0 1-1 1h-1m-6-1a1 1 0 0 0 1 1h1M5 17a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0m6 0a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="primary" class="text-xs">Total</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Carriers</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($totalCarriers) }}</div>
            <div class="text-xs text-slate-500 mt-2">{{ number_format($totalDocuments) }} documents</div>
        </div>

        <!-- Active Carriers -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="success" class="text-xs">Active</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Active</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($activeCarriers) }}</div>
            <div class="text-xs text-slate-500 mt-2">{{ $totalCarriers > 0 ? round(($activeCarriers / $totalCarriers) * 100, 1) : 0 }}% of total</div>
        </div>

        <!-- Pending Carriers -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="warning" class="text-xs">Pending</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Pending</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($pendingCarriers) }}</div>
            <div class="text-xs text-slate-500 mt-2">{{ $totalCarriers > 0 ? round(($pendingCarriers / $totalCarriers) * 100, 1) : 0 }}% of total</div>
        </div>

        <!-- Documents -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-info/10 rounded-lg">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="info" class="text-xs">Documents</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Documents</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($totalDocuments) }}</div>
            <div class="text-xs text-slate-500 mt-2">{{ number_format($approvedDocuments) }} approved</div>
        </div>
    </div>

    <!-- Document Statistics -->
    <div class="box box--stacked p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Document Status Counts -->
            <div class="col-span-1">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 3v18h18M7 16l4-8 4 8M7 12h10" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-slate-800">Document Status</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 transition-colors border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-success rounded-full"></div>
                            <span class="text-sm font-medium text-slate-700">Approved</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-slate-900">{{ number_format($approvedDocuments) }}</span>
                            <span class="text-xs text-slate-500">
                                ({{ $totalDocuments > 0 ? round(($approvedDocuments / $totalDocuments) * 100, 1) : 0 }}%)
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 transition-colors border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-warning rounded-full"></div>
                            <span class="text-sm font-medium text-slate-700">Pending</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-slate-900">{{ number_format($pendingDocuments) }}</span>
                            <span class="text-xs text-slate-500">
                                ({{ $totalDocuments > 0 ? round(($pendingDocuments / $totalDocuments) * 100, 1) : 0 }}%)
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 transition-colors border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 bg-danger rounded-full"></div>
                            <span class="text-sm font-medium text-slate-700">Rejected</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-slate-900">{{ number_format($rejectedDocuments) }}</span>
                            <span class="text-xs text-slate-500">
                                ({{ $totalDocuments > 0 ? round(($rejectedDocuments / $totalDocuments) * 100, 1) : 0 }}%)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Document Types -->
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-6l-2-2H5a2 2 0 0 0-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-slate-800">Document Types</h3>
                    <span class="ml-auto text-xs text-slate-500">{{ $documentTypes->count() }} types</span>
                </div>
                <div class="overflow-auto max-h-[200px] pr-2 custom-scrollbar">
                    @if($documentTypes->count() > 0)
                        <div class="space-y-2">
                            @foreach($documentTypes as $docType)
                                <div class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 transition-colors border border-slate-100">
                                    <div class="flex items-center gap-2 flex-1">
                                        <svg class="h-4 w-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span class="text-sm text-slate-700 truncate">{{ $docType->name }}</span>
                                    </div>
                                    <x-base.badge variant="secondary" class="text-xs ml-3">
                                        {{ $docType->carrier_documents_count }}
                                    </x-base.badge>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="h-12 w-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <p class="text-sm text-slate-500">No document types found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <!-- Filters Bar -->
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <!-- Search -->
                <div class="flex-1 w-full lg:w-auto">
                    <form method="GET" action="{{ route('admin.reports.carrier-documents') }}" id="search-form">
                        @if($statusFilter !== '')
                            <input type="hidden" name="status" value="{{ $statusFilter }}">
                        @endif
                        @if($dateFrom)
                            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        @endif
                        @if($dateTo)
                            <input type="hidden" name="date_to" value="{{ $dateTo }}">
                        @endif
                        @if($perPage != 20)
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
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
                                value="{{ $search }}" 
                                placeholder="Search carriers..." />
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
                                <form method="GET" action="{{ route('admin.reports.carrier-documents') }}">
                                    <div class="mb-4">
                                        <x-base.form-label>Status</x-base.form-label>
                                        <x-base.form-select name="status" class="mt-2">
                                            <option value="">All Status</option>
                                            <option value="1" {{ $statusFilter == '1' ? 'selected' : '' }}>Active</option>
                                            <option value="2" {{ $statusFilter == '2' ? 'selected' : '' }}>Pending</option>
                                            <option value="0" {{ $statusFilter == '0' ? 'selected' : '' }}>Inactive</option>
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Date Range</x-base.form-label>
                                        <div class="grid grid-cols-2 gap-2 mt-2">
                                            <div>
                                                <x-base.form-label class="text-xs">From</x-base.form-label>
                                                <x-base.form-input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" />
                                            </div>
                                            <div>
                                                <x-base.form-label class="text-xs">To</x-base.form-label>
                                                <x-base.form-input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Per Page</x-base.form-label>
                                        <x-base.form-select name="per_page" class="mt-2">
                                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                            <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Search</x-base.form-label>
                                        <x-base.form-input
                                            type="text"
                                            name="search"
                                            value="{{ $search }}"
                                            placeholder="Name, DOT, MC, EIN..."
                                            class="mt-2"
                                        />
                                    </div>

                                    <div class="flex gap-2">
                                        <x-base.button class="flex-1" variant="outline-secondary" as="a"
                                            href="{{ route('admin.reports.carrier-documents') }}">
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

        @if($carriers->count() > 0)
            <!-- Table Header -->
            <div class="p-6 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-slate-800">Carriers List</h2>
                    <x-base.badge variant="primary" class="text-xs">
                        {{ $carriers->total() }} {{ $carriers->total() === 1 ? 'Carrier' : 'Carriers' }}
                    </x-base.badge>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th class="text-center">DOT</x-base.table.th>
                            <x-base.table.th class="text-center">MC</x-base.table.th>
                            <x-base.table.th class="text-center">Status</x-base.table.th>
                            <x-base.table.th class="text-center">Documents</x-base.table.th>
                            <x-base.table.th class="text-center">Progress</x-base.table.th>
                            <x-base.table.th class="text-center">Actions</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach($carriers as $carrier)
                            <x-base.table.tr>
                                <x-base.table.td>
                                    <a href="{{ route('admin.carrier.show', $carrier->slug) }}" class="hover:text-primary transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0 border-2 border-slate-200">
                                                @if($carrier->getFirstMediaUrl('logo_carrier'))
                                                    <img src="{{ $carrier->getFirstMediaUrl('logo_carrier') }}" alt="{{ $carrier->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M9 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM19 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <path d="M13 16V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1m8-1a1 1 0 0 1-1 1H9m4-1V8a1 1 0 0 1 1-1h2.586a1 1 0 0 1 .707.293l3.414 3.414a1 1 0 0 1 .293.707V16a1 1 0 0 1-1 1h-1m-6-1a1 1 0 0 0 1 1h1M5 17a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0m6 0a2 2 0 1 0 4 0m-4 0a2 2 0 1 1 4 0" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-medium text-slate-900">{{ $carrier->name }}</div>
                                                <div class="text-xs text-slate-500">EIN: {{ $carrier->ein_number }}</div>
                                            </div>
                                        </div>
                                    </a>
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    <span class="text-sm text-slate-700">{{ $carrier->dot_number ?: 'N/A' }}</span>
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    <span class="text-sm text-slate-700">{{ $carrier->mc_number ?: 'N/A' }}</span>
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    @if($carrier->status == 1)
                                        <x-base.badge variant="success" class="text-xs">Active</x-base.badge>
                                    @elseif($carrier->status == 2)
                                        <x-base.badge variant="warning" class="text-xs">Pending</x-base.badge>
                                    @else
                                        <x-base.badge variant="danger" class="text-xs">Inactive</x-base.badge>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="text-lg font-medium text-slate-800">{{ $carrier->documents_count }}</span>
                                        <div class="flex flex-wrap gap-1 justify-center">
                                            @if($carrier->approved_documents_count > 0)
                                                <x-base.badge variant="success" class="text-xs">{{ $carrier->approved_documents_count }}</x-base.badge>
                                            @endif
                                            @if($carrier->pending_documents_count > 0)
                                                <x-base.badge variant="warning" class="text-xs">{{ $carrier->pending_documents_count }}</x-base.badge>
                                            @endif
                                            @if($carrier->rejected_documents_count > 0)
                                                <x-base.badge variant="danger" class="text-xs">{{ $carrier->rejected_documents_count }}</x-base.badge>
                                            @endif
                                        </div>
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    @php
                                        $totalDocumentTypes = $documentTypes->count();
                                        $progress = $totalDocumentTypes > 0 ? ($carrier->approved_documents_count / $totalDocumentTypes) * 100 : 0;
                                    @endphp
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-20 bg-slate-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-success to-success h-2 rounded-full transition-all duration-300" style="width: {{ min($progress, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs text-slate-600 font-medium">{{ round($progress) }}%</span>
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td class="text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <x-base.button as="a" href="{{ route('admin.carrier.admin_documents.review', $carrier->slug) }}" 
                                            variant="outline-primary" 
                                            size="sm"
                                            class="gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            View
                                        </x-base.button>
                                        
                                        @if($carrier->documents_count > 0)
                                            <x-base.button as="a" href="{{ route('admin.reports.download-carrier-documents', $carrier) }}" 
                                                variant="outline-success" 
                                                size="sm"
                                                class="gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Download
                                            </x-base.button>
                                        @else
                                            <x-base.button 
                                                variant="outline-secondary" 
                                                size="sm"
                                                disabled
                                                class="gap-2 opacity-50 cursor-not-allowed">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                Download
                                            </x-base.button>
                                        @endif
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>
    
            <!-- Pagination -->
            <div class="p-6 border-t border-slate-200">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-slate-600">
                        Showing <span class="font-medium">{{ $carriers->firstItem() }}</span> to 
                        <span class="font-medium">{{ $carriers->lastItem() }}</span> of 
                        <span class="font-medium">{{ $carriers->total() }}</span> results
                    </div>
                    <div>
                        {{ $carriers->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0h-2.586a1 1 0 0 0-.707.293l-2.414 2.414a1 1 0 0 1-.707.293h-3.172a1 1 0 0 1-.707-.293l-2.414-2.414A1 1 0 0 0 6.586 13H4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No carriers found</h3>
                <p class="text-slate-600 mb-6 max-w-md mx-auto">
                    @if($activeFiltersCount > 0)
                        No carriers match your current filters. Try adjusting your search criteria.
                    @else
                        There are no carrier records in the system yet.
                    @endif
                </p>
                @if($activeFiltersCount > 0)
                    <x-base.button as="a" href="{{ route('admin.reports.carrier-documents') }}" variant="primary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Clear Filters
                    </x-base.button>
                @endif
            </div>
        @endif
    </div>

    @push('styles')
        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
            }
            
            .custom-scrollbar::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 3px;
            }
            
            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 3px;
            }
            
            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            function exportToPDF() {
                const exportBtn = document.getElementById('exportPdfBtn');
                const exportBtnText = document.getElementById('exportBtnText');
                const originalText = exportBtnText.textContent;
                
                exportBtn.disabled = true;
                exportBtn.classList.add('opacity-75', 'cursor-not-allowed');
                exportBtnText.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>Generating PDF...';
                
                const params = new URLSearchParams(window.location.search);
                params.set('timestamp', new Date().toISOString());
                
                const pdfUrl = '{{ route("admin.reports.carrier-documents.pdf") }}?' + params.toString();
                
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = pdfUrl;
                document.body.appendChild(iframe);
                
                setTimeout(() => {
                    exportBtn.disabled = false;
                    exportBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                    exportBtnText.textContent = originalText;
                    
                    setTimeout(() => {
                        if (iframe.parentNode) {
                            document.body.removeChild(iframe);
                        }
                    }, 1000);
                }, 3000);
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('table-search');
                if (searchInput) {
                    searchInput.addEventListener('keypress', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            document.getElementById('search-form').submit();
                        }
                    });
                }
                
                const dateFrom = document.getElementById('date_from');
                const dateTo = document.getElementById('date_to');
                
                if (dateFrom && dateTo) {
                    const validateDates = () => {
                        if (dateFrom.value && dateTo.value) {
                            const fromDate = new Date(dateFrom.value);
                            const toDate = new Date(dateTo.value);
                            
                            if (fromDate > toDate) {
                                return false;
                            }
                        }
                        return true;
                    };
                    
                    const forms = document.querySelectorAll('form');
                    forms.forEach(form => {
                        form.addEventListener('submit', function(e) {
                            if (!validateDates()) {
                                e.preventDefault();
                                alert('From date must be before or equal to To date');
                                return false;
                            }
                        });
                    });
                    
                    dateFrom.addEventListener('change', function() {
                        if (!validateDates()) {
                            dateFrom.setCustomValidity('From date must be before or equal to To date');
                        } else {
                            dateFrom.setCustomValidity('');
                        }
                    });
                    
                    dateTo.addEventListener('change', function() {
                        if (!validateDates()) {
                            dateTo.setCustomValidity('To date must be after or equal to From date');
                        } else {
                            dateTo.setCustomValidity('');
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
