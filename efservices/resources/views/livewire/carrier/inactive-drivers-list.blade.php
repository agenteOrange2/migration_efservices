<div>
    <!-- Filters Section -->
    <div class="box box--stacked mb-5 p-5">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search Input -->
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Search</label>
                    <x-base.form-input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name or email..." 
                        class="w-full"
                    />
                </div>

                <!-- Date From -->
                <div class="w-full sm:w-48">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">From Date</label>
                    <x-base.litepicker 
                        wire:model.live="dateFrom"
                        placeholder="Select start date"
                        class="w-full"
                    />
                </div>

                <!-- Date To -->
                <div class="w-full sm:w-48">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">To Date</label>
                    <x-base.litepicker 
                        wire:model.live="dateTo"
                        placeholder="Select end date"
                        class="w-full"
                    />
                </div>


            </div>

            <!-- Clear Filters Button -->
            @if($search || $dateFrom || $dateTo)
                <div class="flex justify-end">
                    <x-base.button 
                        wire:click="clearFilters" 
                        variant="outline-secondary"
                        size="sm"
                    >
                        <x-base.lucide class="h-4 w-4 mr-2" icon="X" />
                        Clear Filters
                    </x-base.button>
                </div>
            @endif
        </div>
    </div>

    <!-- Inactive Drivers Table -->
    <div class="box box--stacked">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b-2 border-slate-200/60 bg-slate-50">
                        <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">
                            <button 
                                wire:click="sortBy('driver_data_snapshot')" 
                                class="flex items-center gap-1 hover:text-primary transition-colors"
                            >
                                Driver Name
                                @if($sortField === 'driver_data_snapshot')
                                    <x-base.lucide 
                                        class="h-4 w-4" 
                                        icon="{{ $sortDirection === 'asc' ? 'ChevronUp' : 'ChevronDown' }}" 
                                    />
                                @endif
                            </button>
                        </th>
                        <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">Email</th>
                        <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap">
                            <button 
                                wire:click="sortBy('archived_at')" 
                                class="flex items-center gap-1 hover:text-primary transition-colors"
                            >
                                Inactivation Date
                                @if($sortField === 'archived_at')
                                    <x-base.lucide 
                                        class="h-4 w-4" 
                                        icon="{{ $sortDirection === 'asc' ? 'ChevronUp' : 'ChevronDown' }}" 
                                    />
                                @endif
                            </button>
                        </th>
                        <th class="px-5 py-4 font-medium text-slate-700 whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inactiveDrivers as $archive)
                        @php
                            // Get profile photo URL from snapshot
                            $profilePhotoUrl = null;
                            if (isset($archive->driver_data_snapshot['profile_photo_url'])) {
                                $profilePhotoUrl = $archive->driver_data_snapshot['profile_photo_url'];
                            }
                        @endphp
                        <tr class="border-b border-slate-200/60 hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if($profilePhotoUrl)
                                        <img 
                                            src="{{ $profilePhotoUrl }}" 
                                            alt="{{ $archive->full_name }}"
                                            class="w-10 h-10 rounded-full object-cover border-2 border-slate-200"
                                            onerror="this.onerror=null; this.src='/default_profile.png';"
                                        >
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center border-2 border-primary/20">
                                            <span class="text-primary font-semibold text-sm">
                                                {{ strtoupper(substr($archive->full_name, 0, 2)) }}
                                            </span>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-slate-700">{{ $archive->full_name }}</div>
                                        <div class="text-slate-500 text-xs flex items-center gap-1 mt-0.5">
                                            <x-base.lucide class="h-3 w-3" icon="Archive" />
                                            Archived Record
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1 text-slate-600 text-sm">
                                    <x-base.lucide class="h-3.5 w-3.5 text-slate-400" icon="Mail" />
                                    {{ $archive->email ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1 text-slate-600">
                                    <x-base.lucide class="h-3.5 w-3.5 text-slate-400" icon="Clock" />
                                    {{ $archive->archived_at->format('m/d/Y') }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $archive->archived_at->diffForHumans() }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end items-center gap-2">
                                    <a 
                                        href="{{ route('carrier.drivers.inactive.show', $archive->id) }}" 
                                        class="flex items-center justify-center w-8 h-8 rounded-lg border border-primary/20 bg-primary/5 hover:bg-primary/10 transition-colors"
                                        title="View Archive"
                                    >
                                        <x-base.lucide class="w-4 h-4 text-primary" icon="Eye" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                        <x-base.lucide class="h-8 w-8 text-slate-400" icon="Archive" />
                                    </div>
                                    <div class="text-base font-medium text-slate-700">No inactive drivers found</div>
                                    <div class="text-sm mt-1 text-slate-500">
                                        @if($search || $dateFrom || $dateTo)
                                            Try adjusting your search or filters
                                        @else
                                            You don't have any archived drivers yet
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($inactiveDrivers->hasPages())
            <div class="flex flex-col md:flex-row items-center justify-between px-5 py-4 border-t border-slate-200/60">
                <div class="text-sm text-slate-500 mb-3 md:mb-0">
                    Showing {{ $inactiveDrivers->firstItem() }} to {{ $inactiveDrivers->lastItem() }} of {{ $inactiveDrivers->total() }} inactive drivers
                </div>
                <div>
                    {{ $inactiveDrivers->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
