@extends('../themes/' . $activeTheme)
@section('title', 'Driver Reports')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Reports', 'url' => route('carrier.reports.index')],
    ['label' => 'Drivers', 'active' => true],
];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center md:justify-between">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Driver Reports
            </div>
            <div class="flex gap-2">
                <a href="{{ route('carrier.reports.drivers.export-pdf', request()->query()) }}" 
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Total Drivers</div>
                    <div class="text-2xl font-medium">{{ $stats['total'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Active</div>
                    <div class="text-2xl font-medium text-success">{{ $stats['active'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">Inactive</div>
                    <div class="text-2xl font-medium text-slate-500">{{ $stats['inactive'] ?? 0 }}</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-xs text-slate-500 uppercase mb-1">New (30 days)</div>
                    <div class="text-2xl font-medium text-primary">{{ $stats['recent'] ?? 0 }}</div>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="box box--stacked p-5 mb-5">
                <h3 class="text-lg font-medium mb-4">Filters</h3>
                <form action="{{ route('carrier.reports.drivers') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Search -->
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Search</label>
                        <input type="text" 
                               name="search" 
                               value="{{ $filters['search'] ?? '' }}" 
                               placeholder="Name, email, phone..."
                               class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent [&[readonly]]:bg-slate-100 [&[readonly]]:cursor-not-allowed [&[readonly]]:dark:bg-darkmode-800/50 [&[readonly]]:dark:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium">Status</label>
                        <select name="status" 
                                class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent [&[readonly]]:bg-slate-100 [&[readonly]]:cursor-not-allowed [&[readonly]]:dark:bg-darkmode-800/50 [&[readonly]]:dark:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                            <option value="">All Statuses</option>
                            <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="pending_review" {{ ($filters['status'] ?? '') === 'pending_review' ? 'selected' : '' }}>Pending Review</option>
                            <option value="draft" {{ ($filters['status'] ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                        <a href="{{ route('carrier.reports.drivers') }}" class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none dark:focus:ring-slate-700 dark:focus:ring-opacity-50 [&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90 [&:not(button)]:text-center disabled:opacity-70 disabled:cursor-not-allowed bg-secondary/70 border-secondary/70 text-slate-500 dark:border-darkmode-400 dark:bg-darkmode-400 dark:text-slate-300 [&:hover:not(:disabled)]:bg-slate-100 [&:hover:not(:disabled)]:border-slate-100">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="X" />
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Drivers Table -->
            <div class="box box--stacked">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-b border-slate-200/60">
                        <thead>
                            <tr class="border-b border-slate-200/60 bg-slate-50">
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Driver Name
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Email
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Phone
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    License Number
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    License State
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    License Exp.
                                </th>
                                <th class="px-5 py-4 font-medium text-slate-700 border-slate-200/60 whitespace-nowrap">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($drivers as $driver)
                                @php
                                    $license = $driver->primaryLicense;
                                    $licenseNumber = $license->license_number ?? $license->license_number ?? null;
                                    $licenseState = $license->state_of_issue ?? null;
                                    $licenseExp = $license->expiration_date ?? null;
                                @endphp
                                <tr class="border-b border-slate-200/60 hover:bg-slate-50 transition-colors {{ $driver->has_expiring_license ? 'bg-warning/5' : '' }}">
                                    <td class="px-5 py-4 border-slate-200/60">
                                        <div class="flex items-center gap-3">
                                            @if($driver->has_expiring_license)
                                                <x-base.lucide class="h-4 w-4 text-warning flex-shrink-0" icon="AlertCircle" title="License expiring soon" />
                                            @endif
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                    @if($driver->getFirstMediaUrl('profile_photo_driver'))
                                                        <img src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}" 
                                                             alt="{{ $driver->full_name }}" 
                                                             class="w-10 h-10 rounded-full object-cover">
                                                    @else
                                                        <span class="text-primary font-semibold text-sm">
                                                            {{ strtoupper(substr($driver->user->name ?? 'D', 0, 1)) }}{{ strtoupper(substr($driver->last_name ?? 'R', 0, 1)) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-medium text-slate-700">{{ $driver->full_name }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        <div class="flex items-center gap-1 text-slate-600">
                                            <x-base.lucide class="h-3 w-3 text-slate-400" icon="Mail" />
                                            {{ $driver->user->email ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        @if($driver->phone)
                                            <div class="flex items-center gap-1 text-slate-600">
                                                <x-base.lucide class="h-3 w-3 text-slate-400" icon="Phone" />
                                                {{ $driver->formatted_phone }}
                                            </div>
                                        @else
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        @if($licenseNumber)
                                            <div class="flex items-center gap-1">
                                                <x-base.lucide class="h-3 w-3 text-slate-400" icon="CreditCard" />
                                                <span class="font-mono text-sm">{{ $licenseNumber }}</span>
                                            </div>
                                        @else
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        @if($licenseState)
                                            <span class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-700 font-medium">{{ $licenseState }}</span>
                                        @else
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        @if($licenseExp)
                                            <div class="flex items-center gap-1 {{ $driver->has_expiring_license ? 'text-warning font-medium' : 'text-slate-600' }}">
                                                <x-base.lucide class="h-3 w-3" icon="Calendar" />
                                                {{ $licenseExp->format('m/d/Y') }}
                                            </div>
                                            @if($driver->has_expiring_license)
                                                <div class="text-xs text-warning mt-1">⚠ Expiring soon</div>
                                            @endif
                                        @else
                                            <span class="text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 border-slate-200/60">
                                        @php $effectiveStatus = $driver->getEffectiveStatus(); @endphp
                                        @switch($effectiveStatus)
                                            @case('active')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                                    Active
                                                </span>
                                                @break
                                            @case('pending_review')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                                    Pending Review
                                                </span>
                                                @break
                                            @case('draft')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-slate-200/80 text-slate-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                    Draft
                                                </span>
                                                @break
                                            @case('rejected')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-danger"></span>
                                                    Rejected
                                                </span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                                    Inactive
                                                </span>
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                                <x-base.lucide class="h-8 w-8 text-slate-400" icon="Users" />
                                            </div>
                                            <div class="text-base font-medium text-slate-700">No drivers found</div>
                                            <div class="text-sm mt-1 text-slate-500">Try adjusting your filters or date range</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($drivers->hasPages())
                    <div class="flex flex-col md:flex-row items-center justify-between p-5 border-t border-slate-200/60">
                        <div class="text-sm text-slate-500 mb-3 md:mb-0">
                            Showing {{ $drivers->firstItem() }} to {{ $drivers->lastItem() }} of {{ $drivers->total() }} drivers
                        </div>
                        <div>
                            {{ $drivers->appends($filters)->links() }}
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
