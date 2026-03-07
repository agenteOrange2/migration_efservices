@extends('../themes/' . $activeTheme)
@section('title', 'Inactive Drivers Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Inactive Drivers Report', 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-danger/10 rounded-xl border border-danger/20">
                    <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 11h-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Inactive Drivers Report</h1>
                    <p class="text-slate-600 mt-1">View and manage inactive driver information</p>
                </div>
            </div>
            <x-base.button as="a" href="{{ route('admin.reports.index') }}" variant="outline-secondary" class="gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back to Reports
            </x-base.button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg border-danger border-2 bg-danger/5">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 11h-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="danger" class="text-xs">Total</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Inactive</div>
            <div class="text-3xl font-bold text-danger">{{ number_format($totalInactiveCount) }}</div>
        </div>

        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="warning" class="text-xs">Recent</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Last 30 Days</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($recentlyInactivatedCount) }}</div>
        </div>

        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <rect x="1" y="3" width="15" height="13" rx="2" ry="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 8h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="primary" class="text-xs">Assigned</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">With Carrier</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($byCarrierCount) }}</div>
        </div>

        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-slate-200 rounded-lg">
                    <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 15h8M9.5 9h.01M14.5 9h.01" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="secondary" class="text-xs">Unassigned</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">No Carrier</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($noCarrierCount) }}</div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <!-- Filters Bar -->
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <!-- Search -->
                <div class="flex-1 w-full lg:w-auto">
                    <form action="{{ route('admin.reports.inactive-drivers') }}" method="GET" id="search-form">
                        @if (!empty($carrierFilter))
                            <input type="hidden" name="carrier" value="{{ $carrierFilter }}">
                        @endif
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 21l-4.35-4.35" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <x-base.form-input 
                                class="pl-10 w-full lg:w-80" 
                                type="text" 
                                name="search"
                                value="{{ $search }}" 
                                placeholder="Search inactive drivers..."
                                onchange="this.form.submit()" />
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
                            @if (!empty($carrierFilter) || !empty($dateFrom) || !empty($dateTo))
                                <span class="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-medium text-white">
                                    {{ (!empty($carrierFilter) ? 1 : 0) + (!empty($dateFrom) || !empty($dateTo) ? 1 : 0) }}
                                </span>
                            @endif
                        </x-base.popover.button>
                        <x-base.popover.panel>
                            <div class="p-4 w-80">
                                <form method="GET" action="{{ route('admin.reports.inactive-drivers') }}">
                                    @if (!empty($search))
                                        <input type="hidden" name="search" value="{{ $search }}">
                                    @endif
                                    
                                    <div class="mb-4">
                                        <x-base.form-label>Carrier</x-base.form-label>
                                        <x-base.form-select name="carrier" class="mt-2">
                                            <option value="">All Carriers</option>
                                            @foreach ($carriers as $carrier)
                                                <option value="{{ $carrier->id }}" {{ $carrierFilter == $carrier->id ? 'selected' : '' }}>
                                                    {{ $carrier->name }}
                                                </option>
                                            @endforeach
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Date Range (Last Updated)</x-base.form-label>
                                        <div class="grid grid-cols-2 gap-2 mt-2">
                                            <div>
                                                <x-base.form-label class="text-xs">From</x-base.form-label>
                                                <x-base.form-input type="date" name="date_from" value="{{ $dateFrom }}" />
                                            </div>
                                            <div>
                                                <x-base.form-label class="text-xs">To</x-base.form-label>
                                                <x-base.form-input type="date" name="date_to" value="{{ $dateTo }}" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <x-base.button class="flex-1" variant="outline-secondary" as="a"
                                            href="{{ route('admin.reports.inactive-drivers') }}">
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

                    <x-base.button id="export-pdf-inline" variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Export PDF
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Table -->
        @if ($drivers->count() > 0)
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Driver</x-base.table.th>
                            <x-base.table.th>Email</x-base.table.th>
                            <x-base.table.th>Phone</x-base.table.th>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th>License</x-base.table.th>
                            <x-base.table.th>Last Updated</x-base.table.th>
                            <x-base.table.th>Status</x-base.table.th>
                            <x-base.table.th>Actions</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach ($drivers as $driver)
                            <x-base.table.tr>
                                <x-base.table.td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            @if ($driver->getFirstMediaUrl('profile_photo_driver'))
                                                <img src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}"
                                                    alt="{{ $driver->user?->name ?? 'Driver' }}" 
                                                    class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $driver->full_name ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">{{ $driver->user?->email ?? 'N/A' }}</div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">{{ $driver->phone ?? 'N/A' }}</div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if ($driver->carrier)
                                        <x-base.badge variant="primary" class="text-xs">{{ $driver->carrier->name }}</x-base.badge>
                                    @else
                                        <span class="text-sm text-slate-500">Not assigned</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        {{ $driver->primaryLicense?->license_number ?? 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">{{ $driver->updated_at->format('M d, Y') }}</div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <x-base.badge variant="danger">Inactive</x-base.badge>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <x-base.button as="a" href="{{ route('admin.drivers.show', $driver->id) }}" 
                                        variant="outline-primary" 
                                        size="sm"
                                        class="gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        View
                                    </x-base.button>
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>

            <!-- Pagination -->
            @if ($drivers->hasPages())
                <div class="p-6 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-slate-600">
                            Showing <span class="font-medium">{{ $drivers->firstItem() }}</span> to 
                            <span class="font-medium">{{ $drivers->lastItem() }}</span> of 
                            <span class="font-medium">{{ $drivers->total() }}</span> results
                        </div>
                        <div>
                            {{ $drivers->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 11h-6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No inactive drivers found</h3>
                <p class="text-slate-600 mb-6">No inactive drivers match the current filters. Try adjusting your search criteria.</p>
                <x-base.button as="a" href="{{ route('admin.reports.inactive-drivers') }}" variant="primary">
                    Clear Filters
                </x-base.button>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const exportPdfInlineBtn = document.getElementById('export-pdf-inline');

                function getExportUrl() {
                    const params = new URLSearchParams(window.location.search);
                    if (params.has('page')) {
                        params.delete('page');
                    }
                    let url = '{{ route('admin.reports.inactive-drivers.pdf') }}';
                    const queryString = params.toString();
                    return queryString ? `${url}?${queryString}` : url;
                }

                function handleExport() {
                    const button = this;
                    const originalHTML = button.innerHTML;
                    button.innerHTML = `
                        <svg class="animate-spin w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Generating...
                    `;
                    button.disabled = true;
                    window.open(getExportUrl(), '_blank');
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    }, 2000);
                }

                if (exportPdfInlineBtn) {
                    exportPdfInlineBtn.addEventListener('click', handleExport);
                }
            });
        </script>
    @endpush
@endsection
