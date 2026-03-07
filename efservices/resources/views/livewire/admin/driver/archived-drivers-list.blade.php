<div>
    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-slate-500/10 rounded-xl border border-slate-500/20">
                    <x-base.lucide class="w-8 h-8 text-slate-600" icon="Archive" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Archived Drivers</h1>
                    <p class="text-slate-600">View historical records of drivers who have been migrated or terminated</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="box box--stacked flex flex-col p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <x-base.form-label for="search">Search</x-base.form-label>
                <x-base.form-input 
                    id="search"
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search by name..."
                    class="w-full" />
            </div>

            <!-- Carrier Filter (Superadmin only) -->
            @if(auth()->user()->hasRole('superadmin'))
                <div>
                    <x-base.form-label for="carrier">Carrier</x-base.form-label>
                    <x-base.form-select 
                        id="carrier"
                        wire:model.live="carrierId" 
                        class="w-full">
                        <option value="">All Carriers</option>
                        @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
                        @endforeach
                    </x-base.form-select>
                </div>
            @endif

            <!-- Date Range -->
            <div>
                <x-base.form-label for="dateFrom">From Date</x-base.form-label>
                <x-base.form-input 
                    type="date"
                    wire:model.live="dateFrom"
                    id="dateFrom"
                    class="w-full" />
            </div>

            <div>
                <x-base.form-label for="dateTo">To Date</x-base.form-label>
                <x-base.form-input 
                    type="date"
                    wire:model.live="dateTo"
                    id="dateTo"
                    class="w-full" />
            </div>

            <!-- Archive Reason -->
            <div>
                <x-base.form-label for="archiveReason">Reason</x-base.form-label>
                <x-base.form-select 
                    id="archiveReason"
                    wire:model.live="archiveReason" 
                    class="w-full">
                    <option value="">All Reasons</option>
                    @foreach($archiveReasons as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-base.form-select>
            </div>
        </div>

        <!-- Clear Filters -->
        @if($search || $carrierId || $dateFrom || $dateTo || $archiveReason)
            <div class="mt-4">
                <x-base.button wire:click="clearFilters" variant="outline-secondary" size="sm">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="X" />
                    Clear all filters
                </x-base.button>
            </div>
        @endif
    </div>

    <!-- Table -->
    <div class="box box--stacked flex flex-col">
        <div class="overflow-auto xl:overflow-visible">
            <x-base.table class="border-b border-slate-200/60">
                <x-base.table.thead>
                    <x-base.table.tr>
                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 cursor-pointer"
                            wire:click="sortBy('driver_data_snapshot->name')">
                            Driver
                        </x-base.table.td>
                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                            Carrier
                        </x-base.table.td>
                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 cursor-pointer"
                            wire:click="sortBy('archived_at')">
                            Archived Date
                            @if($sortField === 'archived_at')
                                <x-base.lucide class="w-3 h-3 inline ml-1" icon="{{ $sortDirection === 'asc' ? 'ChevronUp' : 'ChevronDown' }}" />
                            @endif
                        </x-base.table.td>
                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                            Reason
                        </x-base.table.td>
                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                            Migrated To
                        </x-base.table.td>
                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                            Status
                        </x-base.table.td>
                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                            Actions
                        </x-base.table.td>
                    </x-base.table.tr>
                </x-base.table.thead>
                <x-base.table.tbody>
                    @forelse($archivedDrivers as $archive)
                        <x-base.table.tr class="[&_td]:last:border-b-0">
                            <x-base.table.td class="border-dashed py-4">
                                <div class="flex items-center">
                                    <div class="image-fit zoom-in h-9 w-9">
                                        <div class="h-9 w-9 rounded-full bg-slate-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-slate-600">
                                                {{ substr($archive->driver_data_snapshot['name'] ?? 'U', 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3.5">
                                        <div class="whitespace-nowrap font-medium">
                                            {{ $archive->full_name }}
                                        </div>
                                        <div class="mt-0.5 whitespace-nowrap text-xs text-slate-500">
                                            {{ $archive->email }}
                                        </div>
                                    </div>
                                </div>
                            </x-base.table.td>
                            <x-base.table.td class="border-dashed py-4">
                                <div class="whitespace-nowrap">{{ $archive->carrier->name }}</div>
                            </x-base.table.td>
                            <x-base.table.td class="border-dashed py-4">
                                <div class="whitespace-nowrap">
                                    {{ $archive->archived_at->format('M j, Y') }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $archive->archived_at->format('g:i A') }}
                                </div>
                            </x-base.table.td>
                            <x-base.table.td class="border-dashed py-4">
                                <x-base.badge 
                                    variant="{{ $archive->archive_reason === 'migration' ? 'primary' : ($archive->archive_reason === 'termination' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($archive->archive_reason) }}
                                </x-base.badge>
                            </x-base.table.td>
                            <x-base.table.td class="border-dashed py-4">
                                @if($archive->migrationRecord && $archive->migrationRecord->targetCarrier)
                                    <div class="whitespace-nowrap">
                                        {{ $archive->migrationRecord->targetCarrier->name }}
                                    </div>
                                @else
                                    <span class="text-slate-400">N/A</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="border-dashed py-4">
                                <x-base.badge 
                                    variant="{{ $archive->status === 'archived' ? 'success' : 'warning' }}">
                                    {{ ucfirst($archive->status) }}
                                </x-base.badge>
                            </x-base.table.td>
                            <x-base.table.td class="relative border-dashed py-4">
                                <div class="flex items-center justify-center">
                                    <x-base.button 
                                        wire:click="viewArchive({{ $archive->id }})" 
                                        variant="primary"
                                        size="sm">
                                        <x-base.lucide class="w-4 h-4 mr-1" icon="Eye" />
                                        View Details
                                    </x-base.button>
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                    @empty
                        <x-base.table.tr>
                            <x-base.table.td colspan="7" class="text-center py-12">
                                <div class="flex flex-col items-center">
                                    <x-base.lucide class="w-16 h-16 text-slate-400 mb-4" icon="Archive" />
                                    <p class="text-slate-500 text-lg font-medium">No archived drivers found</p>
                                    <p class="text-slate-400 text-sm mt-1">Try adjusting your filters</p>
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                    @endforelse
                </x-base.table.tbody>
            </x-base.table>
        </div>

        <!-- Pagination -->
        @if($archivedDrivers->hasPages())
            <div class="w-full p-5 border-t border-slate-200/60">
                {{ $archivedDrivers->links('custom.pagination') }}
            </div>
        @endif
    </div>
</div>
