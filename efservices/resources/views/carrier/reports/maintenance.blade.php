@extends('../themes/' . $activeTheme)
@section('title', 'Maintenance Reports')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Reports', 'url' => route('carrier.reports.index')],
    ['label' => 'Maintenance', 'active' => true],
];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center md:justify-between">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Maintenance Reports
            </div>
            <div class="flex gap-2">
                <a href="{{ route('carrier.reports.maintenance.export-pdf', request()->query()) }}" 
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
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-5">
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Total Records</div>
                    <div class="text-2xl font-medium">{{ $stats['count'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Completed</div>
                    <div class="text-2xl font-medium text-success">{{ $stats['completed'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Pending</div>
                    <div class="text-2xl font-medium text-warning">{{ $stats['pending'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Total Cost</div>
                    <div class="text-2xl font-medium text-primary">${{ number_format($stats['total_cost'] ?? 0, 2) }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Average Cost</div>
                    <div class="text-2xl font-medium">${{ number_format($stats['average_cost'] ?? 0, 2) }}</div>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="box box--stacked p-5 mb-5">
                <h3 class="text-lg font-medium mb-4">Filters</h3>
                <form action="{{ route('carrier.reports.maintenance') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Search -->
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Search</label>
                        <input type="text" 
                               name="search" 
                               value="{{ $filters['search'] ?? '' }}" 
                               placeholder="Service tasks, vendor, unit..."
                               class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent [&[readonly]]:bg-slate-100 [&[readonly]]:cursor-not-allowed [&[readonly]]:dark:bg-darkmode-800/50 [&[readonly]]:dark:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Status</label>
                        <select name="status" 
                                class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent [&[readonly]]:bg-slate-100 [&[readonly]]:cursor-not-allowed [&[readonly]]:dark:bg-darkmode-800/50 [&[readonly]]:dark:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                            <option value="">All Statuses</option>
                            <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
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
                        <a href="{{ route('carrier.reports.maintenance') }}" class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none dark:focus:ring-slate-700 dark:focus:ring-opacity-50 [&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90 [&:not(button)]:text-center disabled:opacity-70 disabled:cursor-not-allowed bg-secondary/70 border-secondary/70 text-slate-500 dark:border-darkmode-400 dark:bg-darkmode-400 dark:text-slate-300 [&:hover:not(:disabled)]:bg-slate-100 [&:hover:not(:disabled)]:border-slate-100">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="X" />
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Maintenance Table -->
            <div class="box box--stacked">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-b border-slate-200/60">
                        <thead>
                            <tr class="border-b border-slate-200/60 bg-slate-50">
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Vehicle
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Service Tasks
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Service Date
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Vendor/Mechanic
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Cost
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($maintenanceRecords as $maintenance)
                                <tr class="border-b border-slate-200/60">
                                    <td class="px-5 py-4 border-slate-200/60">
                                        <div>
                                            <div class="font-medium">{{ $maintenance->vehicle->company_unit_number ?? 'N/A' }}</div>
                                            <div class="text-xs text-slate-500">{{ $maintenance->vehicle->make ?? '' }} {{ $maintenance->vehicle->model ?? '' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        <div class="font-medium">{{ $maintenance->service_tasks ?? 'N/A' }}</div>
                                        @if($maintenance->description)
                                            <div class="text-xs text-slate-500 mt-1">{{ Str::limit($maintenance->description, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        {{ $maintenance->service_date ? $maintenance->service_date->format('m/d/Y') : 'N/A' }}
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        {{ $maintenance->vendor_mechanic ?? 'N/A' }}
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        <span class="font-medium">${{ number_format($maintenance->cost ?? 0, 2) }}</span>
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        @if($maintenance->status)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                                Completed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-500">
                                        <div class="flex flex-col items-center">
                                            <x-base.lucide class="h-12 w-12 text-slate-300 mb-3" icon="Wrench" />
                                            <div class="text-base font-medium">No maintenance records found</div>
                                            <div class="text-sm mt-1">Try adjusting your filters or date range</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($maintenanceRecords->hasPages())
                    <div class="flex flex-col md:flex-row items-center justify-between p-5 border-t border-slate-200/60">
                        <div class="text-sm text-slate-500 mb-3 md:mb-0">
                            Showing {{ $maintenanceRecords->firstItem() }} to {{ $maintenanceRecords->lastItem() }} of {{ $maintenanceRecords->total() }} records
                        </div>
                        <div>
                            {{ $maintenanceRecords->appends($filters)->links() }}
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
