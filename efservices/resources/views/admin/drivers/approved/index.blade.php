@extends('../themes/' . $activeTheme)
@section('title', 'Approved Drivers')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Approved Drivers', 'active' => true],
];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <!-- Header -->
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center md:justify-between">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Approved Drivers
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.drivers.index') }}" 
                   class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none dark:focus:ring-slate-700 dark:focus:ring-opacity-50 [&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90 [&:not(button)]:text-center disabled:opacity-70 disabled:cursor-not-allowed bg-secondary/70 border-secondary/70 text-slate-500 dark:border-darkmode-400 dark:bg-darkmode-400 dark:text-slate-300 [&:hover:not(:disabled)]:bg-slate-100 [&:hover:not(:disabled)]:border-slate-100">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    All Drivers
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-5">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-success/10">
                        <x-base.lucide class="h-6 w-6 text-success" icon="UserCheck" />
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 uppercase">Total Approved</div>
                        <div class="text-2xl font-medium mt-1">{{ $drivers->total() }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-primary/10">
                        <x-base.lucide class="h-6 w-6 text-primary" icon="Building2" />
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 uppercase">Carriers</div>
                        <div class="text-2xl font-medium mt-1">{{ $carriers->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-warning/10">
                        <x-base.lucide class="h-6 w-6 text-warning" icon="AlertCircle" />
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 uppercase">Expiring Licenses</div>
                        <div class="text-2xl font-medium mt-1">
                            {{ $drivers->filter(fn($d) => $d->primaryLicense && $d->primaryLicense->expiration_date && $d->primaryLicense->expiration_date->diffInDays(now()) <= 30)->count() }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-info/10">
                        <x-base.lucide class="h-6 w-6 text-info" icon="Calendar" />
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 uppercase">New This Month</div>
                        <div class="text-2xl font-medium mt-1">
                            {{ $drivers->filter(fn($d) => $d->hire_date && $d->hire_date->isCurrentMonth())->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked p-5 mt-5">
            <h3 class="text-lg font-medium mb-4">Filters</h3>
            <form action="{{ route('admin.drivers.approved.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="inline-block mb-2 text-sm font-medium">Search</label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Name, email, license..."
                           class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md placeholder:text-slate-400/90 focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50 dark:placeholder:text-slate-500/80">
                </div>

                <div>
                    <label class="inline-block mb-2 text-sm font-medium">Carrier</label>
                    <select name="carrier_id" 
                            class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50">
                        <option value="">All Carriers</option>
                        @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}" {{ request('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                {{ $carrier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="inline-block mb-2 text-sm font-medium">From Date</label>
                    <input type="date" 
                           name="date_from" 
                           value="{{ request('date_from') }}"
                           class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50">
                </div>

                <div>
                    <label class="inline-block mb-2 text-sm font-medium">To Date</label>
                    <input type="date" 
                           name="date_to" 
                           value="{{ request('date_to') }}"
                           class="disabled:bg-slate-100 disabled:cursor-not-allowed dark:disabled:bg-darkmode-800/50 dark:disabled:border-transparent transition duration-200 ease-in-out w-full text-sm border-slate-200 shadow-sm rounded-md focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus:border-primary focus:border-opacity-40 dark:bg-darkmode-800 dark:border-transparent dark:focus:ring-slate-700 dark:focus:ring-opacity-50">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none dark:focus:ring-slate-700 dark:focus:ring-opacity-50 [&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90 [&:not(button)]:text-center disabled:opacity-70 disabled:cursor-not-allowed bg-primary border-primary text-white dark:border-primary w-full">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="Filter" />
                        Apply
                    </button>
                    <a href="{{ route('admin.drivers.approved.index') }}" class="transition duration-200 border shadow-sm inline-flex items-center justify-center py-2 px-3 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20 focus-visible:outline-none dark:focus:ring-slate-700 dark:focus:ring-opacity-50 [&:hover:not(:disabled)]:bg-opacity-90 [&:hover:not(:disabled)]:border-opacity-90 [&:not(button)]:text-center disabled:opacity-70 disabled:cursor-not-allowed bg-secondary/70 border-secondary/70 text-slate-500 dark:border-darkmode-400 dark:bg-darkmode-400 dark:text-slate-300 [&:hover:not(:disabled)]:bg-slate-100 [&:hover:not(:disabled)]:border-slate-100">
                        <x-base.lucide class="h-4 w-4" icon="X" />
                    </a>
                </div>
            </form>
        </div>

        <!-- Drivers Table -->
        <div class="box box--stacked mt-5">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-b border-slate-200/60">
                    <thead>
                        <tr class="border-b border-slate-200/60 bg-slate-50">
                            <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">Driver</th>
                            <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">Carrier</th>
                            <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">License</th>
                            <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">Medical Card</th>
                            <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">Hire Date</th>
                            <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drivers as $driver)
                            <tr class="border-b border-slate-200/60 hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <div class="w-11 h-11 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center">
                                                @if($driver->getFirstMediaUrl('profile_photo_driver'))
                                                    <img class="w-full h-full object-cover" 
                                                         src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}" 
                                                         alt="{{ $driver->full_name }}">
                                                @else
                                                    <span class="text-primary font-semibold text-sm">
                                                        {{ strtoupper(substr($driver->user->name ?? 'D', 0, 1)) }}{{ strtoupper(substr($driver->last_name ?? 'R', 0, 1)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-success border-2 border-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-700">{{ $driver->full_name }}</div>
                                            <div class="text-xs text-slate-500 flex items-center gap-1">
                                                <x-base.lucide class="h-3 w-3" icon="Mail" />
                                                {{ $driver->user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1">
                                        <x-base.lucide class="h-3.5 w-3.5 text-slate-400" icon="Building2" />
                                        <span class="text-slate-700">{{ $driver->carrier->name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @if($driver->primaryLicense)
                                        <div class="font-mono text-sm text-slate-700">{{ $driver->primaryLicense->license_number }}</div>
                                        <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                            <x-base.lucide class="h-3 w-3" icon="Calendar" />
                                            Exp: {{ $driver->primaryLicense->expiration_date?->format('m/d/Y') ?? 'N/A' }}
                                        </div>
                                    @else
                                        <span class="text-slate-400">No license</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if($driver->medicalQualification)
                                        <div class="text-sm text-slate-700">{{ $driver->medicalQualification->card_number ?? 'N/A' }}</div>
                                        <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                            <x-base.lucide class="h-3 w-3" icon="Calendar" />
                                            Exp: {{ $driver->medicalQualification->expiration_date?->format('m/d/Y') ?? 'N/A' }}
                                        </div>
                                    @else
                                        <span class="text-slate-400">No medical card</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1 text-slate-600">
                                        <x-base.lucide class="h-3.5 w-3.5 text-slate-400" icon="Briefcase" />
                                        {{ $driver->hire_date?->format('m/d/Y') ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="{{ route('admin.drivers.approved.show', $driver->id) }}" 
                                           class="flex items-center justify-center w-8 h-8 rounded-lg border border-primary/20 bg-primary/5 hover:bg-primary/10 transition-colors"
                                           title="View Details">
                                            <x-base.lucide class="w-4 h-4 text-primary" icon="Eye" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                            <x-base.lucide class="h-8 w-8 text-slate-400" icon="Users" />
                                        </div>
                                        <div class="text-base font-medium text-slate-700">No approved drivers found</div>
                                        <div class="text-sm mt-1 text-slate-500">Try adjusting your filters</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($drivers->hasPages())
                <div class="flex flex-col md:flex-row items-center justify-between px-5 py-4 border-t border-slate-200/60">
                    <div class="text-sm text-slate-500 mb-3 md:mb-0">
                        Showing {{ $drivers->firstItem() }} to {{ $drivers->lastItem() }} of {{ $drivers->total() }} drivers
                    </div>
                    <div>
                        {{ $drivers->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
