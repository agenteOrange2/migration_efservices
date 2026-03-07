@extends('../themes/' . $activeTheme)
@section('title', 'License Reports')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Reports', 'url' => route('carrier.reports.index')],
    ['label' => 'Licenses', 'active' => true],
];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center md:justify-between">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                License Reports
            </div>
            <div class="flex gap-2">
                <a href="{{ route('carrier.reports.licenses.export-pdf', request()->query()) }}" 
                   class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none dark:focus:ring-slate-700 dark:focus:ring-opacity-50 [&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90 [&:not(button)]:text-center disabled:opacity-70 disabled:cursor-not-allowed bg-success border-success text-white dark:border-success [&:hover:not(:disabled)]:bg-success/90 [&:hover:not(:disabled)]:border-success/90">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="Download" />
                    Export to PDF
                </a>
                <a href="{{ route('carrier.reports.index') }}" class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none dark:focus:ring-slate-700 dark:focus:ring-opacity-50 [&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90 [&:not(button)]:text-center disabled:opacity-70 disabled:cursor-not-allowed bg-secondary/70 border-secondary/70 text-slate-500 dark:border-darkmode-400 dark:bg-darkmode-400 dark:text-slate-300 [&:hover:not(:disabled)]:bg-slate-100 [&:hover:not(:disabled)]:border-slate-100 [&:hover:not(:disabled)]:dark:border-darkmode-300/80 [&:hover:not(:disabled)]:dark:bg-darkmode-300/80">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back to Dashboard
                </a>
            </div>
        </div>
        
        <div class="mt-3.5">
            <!-- Statistics Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Total Licenses</div>
                    <div class="text-2xl font-medium">{{ $stats['total'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Expiring Soon (30 days)</div>
                    <div class="text-2xl font-medium text-warning">{{ $stats['expiring_soon'] ?? 0 }}</div>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="box box--stacked p-5 mb-5">
                <h3 class="text-lg font-medium mb-4">Filters</h3>
                <form action="{{ route('carrier.reports.licenses') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Search -->
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Search</label>
                        <input type="text" 
                               name="search" 
                               value="{{ $filters['search'] ?? '' }}" 
                               placeholder="License number, driver name..."
                               class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent [&[readonly]]:bg-slate-100 [&[readonly]]:cursor-not-allowed [&[readonly]]:dark:bg-darkmode-800/50 [&[readonly]]:dark:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                    </div>

                    <!-- License Type -->
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">License Type</label>
                        <select name="license_type" 
                                class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent [&[readonly]]:bg-slate-100 [&[readonly]]:cursor-not-allowed [&[readonly]]:dark:bg-darkmode-800/50 [&[readonly]]:dark:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                            <option value="">All Types</option>
                            <option value="cdl" {{ ($filters['license_type'] ?? '') === 'cdl' ? 'selected' : '' }}>CDL</option>
                            <option value="non_cdl" {{ ($filters['license_type'] ?? '') === 'non_cdl' ? 'selected' : '' }}>Non-CDL</option>
                            <option value="primary" {{ ($filters['license_type'] ?? '') === 'primary' ? 'selected' : '' }}>Primary Only</option>
                        </select>
                    </div>

                    <!-- Expiration Status -->
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Expiration Status</label>
                        <select name="expiration_status" 
                                class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent [&[readonly]]:bg-slate-100 [&[readonly]]:cursor-not-allowed [&[readonly]]:dark:bg-darkmode-800/50 [&[readonly]]:dark:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                            <option value="">All Statuses</option>
                            <option value="valid" {{ ($filters['expiration_status'] ?? '') === 'valid' ? 'selected' : '' }}>Valid</option>
                            <option value="expiring_soon" {{ ($filters['expiration_status'] ?? '') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
                            <option value="expired" {{ ($filters['expiration_status'] ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">From Date</label>
                        <input type="date" 
                               name="date_from" 
                               value="{{ $filters['date_from'] ?? '' }}"
                               class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent [&[readonly]]:bg-slate-100 [&[readonly]]:cursor-not-allowed [&[readonly]]:dark:bg-darkmode-800/50 [&[readonly]]:dark:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">To Date</label>
                        <input type="date" 
                               name="date_to" 
                               value="{{ $filters['date_to'] ?? '' }}"
                               class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent [&[readonly]]:bg-slate-100 [&[readonly]]:cursor-not-allowed [&[readonly]]:dark:bg-darkmode-800/50 [&[readonly]]:dark:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                    </div>

                    <!-- Buttons -->
                    <div class="col-span-full flex gap-2">
                        <button type="submit" class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none dark:focus:ring-slate-700 dark:focus:ring-opacity-50 [&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90 [&:not(button)]:text-center disabled:opacity-70 disabled:cursor-not-allowed bg-primary border-primary text-white dark:border-primary">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="Filter" />
                            Apply Filters
                        </button>
                        <a href="{{ route('carrier.reports.licenses') }}" class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none dark:focus:ring-slate-700 dark:focus:ring-opacity-50 [&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90 [&:not(button)]:text-center disabled:opacity-70 disabled:cursor-not-allowed bg-secondary/70 border-secondary/70 text-slate-500 dark:border-darkmode-400 dark:bg-darkmode-400 dark:text-slate-300 [&:hover:not(:disabled)]:bg-slate-100 [&:hover:not(:disabled)]:border-slate-100">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="X" />
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Licenses Table -->
            <div class="box box--stacked">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-b border-slate-200/60">
                        <thead>
                            <tr class="border-b border-slate-200/60 bg-slate-50">
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Driver Name
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    License Number
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    License Type
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    State
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Issue Date
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Expiration Date
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($licenses as $license)
                                <tr class="border-b border-slate-200/60 {{ $license->is_expiring ? 'bg-warning/5' : '' }} {{ $license->is_expired ? 'bg-danger/5' : '' }}">
                                    <td class="px-5 py-4 border-slate-200/60">
                                        <div class="flex items-center">
                                            @if($license->is_expiring)
                                                <x-base.lucide class="mr-2 h-4 w-4 text-warning" icon="AlertCircle" title="License expiring soon" />
                                            @elseif($license->is_expired)
                                                <x-base.lucide class="mr-2 h-4 w-4 text-danger" icon="XCircle" title="License expired" />
                                            @endif
                                            <div>
                                                <div class="font-medium">
                                                    {{ $license->driverDetail->full_name ?? 'N/A' }}
                                                </div>
                                                @if($license->is_primary)
                                                    <div class="text-xs text-primary">Primary License</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        <div class="font-medium">{{ $license->license_number ?? 'N/A' }}</div>
                                        @if($license->license_number && $license->license_number !== $license->license_number)
                                            <div class="text-xs text-slate-500">Current: {{ $license->license_number }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        <div>
                                            @if($license->is_cdl)
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                                    CDL
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-600">
                                                    Non-CDL
                                                </span>
                                            @endif
                                        </div>
                                        @if($license->license_class)
                                            <div class="text-xs text-slate-500 mt-1">Class: {{ $license->license_class }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        {{ $license->state_of_issue ?? 'N/A' }}
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        {{ $license->created_at ? $license->created_at->format('m/d/Y') : 'N/A' }}
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        @if($license->expiration_date)
                                            <div class="{{ $license->is_expiring ? 'text-warning font-medium' : '' }} {{ $license->is_expired ? 'text-danger font-medium' : '' }}">
                                                {{ $license->expiration_date->format('m/d/Y') }}
                                            </div>
                                            @if($license->is_expiring)
                                                <div class="text-xs text-warning">Expiring soon</div>
                                            @elseif($license->is_expired)
                                                <div class="text-xs text-danger">Expired</div>
                                            @endif
                                        @else
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        @if($license->is_expired)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                                                Expired
                                            </span>
                                        @elseif($license->is_expiring)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                                Expiring Soon
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                                Valid
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                                        <div class="flex flex-col items-center">
                                            <x-base.lucide class="h-12 w-12 text-slate-300 mb-3" icon="FileText" />
                                            <div class="text-base font-medium">No licenses found</div>
                                            <div class="text-sm mt-1">Try adjusting your filters or date range</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($licenses->hasPages())
                    <div class="flex flex-col md:flex-row items-center justify-between p-5 border-t border-slate-200/60">
                        <div class="text-sm text-slate-500 mb-3 md:mb-0">
                            Showing {{ $licenses->firstItem() }} to {{ $licenses->lastItem() }} of {{ $licenses->total() }} licenses
                        </div>
                        <div>
                            {{ $licenses->appends($filters)->links() }}
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
