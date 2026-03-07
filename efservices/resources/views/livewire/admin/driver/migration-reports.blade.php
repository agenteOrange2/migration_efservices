<div>
    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="ArrowRightLeft" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Migration Reports</h1>
                    <p class="text-slate-600">View and analyze driver migration history and statistics</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button wire:click="exportExcel" variant="outline-success" class="w-full sm:w-auto gap-2">
                    <x-base.lucide class="w-4 h-4" icon="FileSpreadsheet" />
                    Export Excel
                </x-base.button>
                <x-base.button wire:click="exportPdf" variant="outline-danger" class="w-full sm:w-auto gap-2">
                    <x-base.lucide class="w-4 h-4" icon="FileText" />
                    Export PDF
                </x-base.button>
                <x-base.button as="a" href="{{ route('admin.reports.index') }}" variant="outline-secondary" class="w-full sm:w-auto gap-2">
                    <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                    Back to Reports
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <!-- Total Migrations -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-primary/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="ArrowRightLeft" />
                </div>
                <x-base.badge variant="primary" class="text-xs">Total</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Migrations</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($statistics['total_migrations']) }}</div>
        </div>

        <!-- Completed -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-success/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                </div>
                <x-base.badge variant="success" class="text-xs">Completed</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Completed</div>
            <div class="text-3xl font-bold text-success">{{ number_format($statistics['completed_migrations']) }}</div>
        </div>

        <!-- Rolled Back -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-warning/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-warning" icon="RotateCcw" />
                </div>
                <x-base.badge variant="warning" class="text-xs">Rolled Back</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Rolled Back</div>
            <div class="text-3xl font-bold text-warning">{{ number_format($statistics['rolled_back_migrations']) }}</div>
        </div>

        <!-- Rollback Rate -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-info/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-info" icon="TrendingDown" />
                </div>
                <x-base.badge variant="info" class="text-xs">Rate</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Rollback Rate</div>
            <div class="text-3xl font-bold text-info">{{ number_format($statistics['rollback_rate'], 1) }}%</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Filter" />
            <h2 class="text-lg font-semibold text-slate-800">Filters</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <!-- Search -->
            <div>
                <x-base.form-label for="search">Search Driver</x-base.form-label>
                <x-base.form-input 
                    id="search"
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Name or email..." />
            </div>

            <!-- Source Carrier -->
            <div>
                <x-base.form-label for="sourceCarrierId">Source Carrier</x-base.form-label>
                <x-base.form-select id="sourceCarrierId" wire:model.live="sourceCarrierId">
                    <option value="">All Carriers</option>
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
                    @endforeach
                </x-base.form-select>
            </div>

            <!-- Target Carrier -->
            <div>
                <x-base.form-label for="targetCarrierId">Target Carrier</x-base.form-label>
                <x-base.form-select id="targetCarrierId" wire:model.live="targetCarrierId">
                    <option value="">All Carriers</option>
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
                    @endforeach
                </x-base.form-select>
            </div>

            <!-- Date From -->
            <div>
                <x-base.form-label for="dateFrom">From Date</x-base.form-label>
                <x-base.form-input 
                    id="dateFrom"
                    type="date" 
                    wire:model.live="dateFrom" />
            </div>

            <!-- Date To -->
            <div>
                <x-base.form-label for="dateTo">To Date</x-base.form-label>
                <x-base.form-input 
                    id="dateTo"
                    type="date" 
                    wire:model.live="dateTo" />
            </div>

            <!-- Status -->
            <div>
                <x-base.form-label for="status">Status</x-base.form-label>
                <x-base.form-select id="status" wire:model.live="status">
                    <option value="">All Statuses</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-base.form-select>
            </div>
        </div>

        <!-- Clear Filters -->
        @if($search || $sourceCarrierId || $targetCarrierId || $dateFrom || $dateTo || $status)
            <div class="mt-4 pt-4 border-t border-slate-200/60">
                <x-base.button wire:click="clearFilters" variant="outline-secondary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="X" />
                    Clear all filters
                </x-base.button>
            </div>
        @endif
    </div>

    <!-- Migrations Table -->
    <div class="box box--stacked">
        <div class="box-header flex flex-col md:flex-row items-start md:items-center justify-between p-5 border-b border-slate-200/60 gap-4">
            <h2 class="text-lg font-semibold text-slate-800">Migration History</h2>
            <div class="text-sm text-slate-500">
                Showing {{ $migrations->firstItem() ?? 0 }} to {{ $migrations->lastItem() ?? 0 }} of {{ $migrations->total() }} records
            </div>
        </div>
        <div class="box-body p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-slate-500 border-b border-slate-200/60 bg-slate-50/50">
                        <tr>
                            <th class="px-6 py-4 font-medium">Driver</th>
                            <th class="px-6 py-4 font-medium">From Carrier</th>
                            <th class="px-6 py-4 font-medium">To Carrier</th>
                            <th class="px-6 py-4 font-medium">Migration Date</th>
                            <th class="px-6 py-4 font-medium">Migrated By</th>
                            <th class="px-6 py-4 font-medium">Reason</th>
                            <th class="px-6 py-4 font-medium text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($migrations as $migration)
                            <tr class="border-b border-slate-200/60 hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                            <span class="text-sm font-semibold text-primary">
                                                {{ strtoupper(substr($migration->driverUser->name ?? 'U', 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-800">
                                                {{ $migration->driverUser->name ?? 'Unknown' }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $migration->driverUser->email ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <x-base.lucide class="w-4 h-4 text-slate-400" icon="ArrowRight" />
                                        <span class="font-medium text-slate-800">{{ $migration->sourceCarrier->name ?? 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <x-base.lucide class="w-4 h-4 text-success" icon="ArrowRight" />
                                        <span class="font-medium text-slate-800">{{ $migration->targetCarrier->name ?? 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800">
                                        {{ $migration->migrated_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $migration->migrated_at->format('g:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-slate-800">{{ $migration->migratedByUser->name ?? 'Unknown' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-slate-700 max-w-xs truncate" title="{{ $migration->reason ?? 'N/A' }}">
                                        {{ $migration->reason ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($migration->status === 'completed')
                                        <x-base.badge variant="success" class="gap-1.5">
                                            <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                            Completed
                                        </x-base.badge>
                                    @elseif($migration->status === 'rolled_back')
                                        <x-base.badge variant="warning" class="gap-1.5">
                                            <span class="w-1.5 h-1.5 bg-warning rounded-full"></span>
                                            Rolled Back
                                        </x-base.badge>
                                        @if($migration->rolled_back_at)
                                            <div class="text-xs text-slate-500 mt-1">
                                                {{ $migration->rolled_back_at->format('M d, Y') }}
                                            </div>
                                        @endif
                                    @else
                                        <x-base.badge variant="secondary">
                                            {{ ucfirst(str_replace('_', ' ', $migration->status)) }}
                                        </x-base.badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="FileX" />
                                        <h3 class="text-lg font-semibold text-slate-800 mb-2">No Migration Records Found</h3>
                                        <p class="text-slate-500 text-sm">Try adjusting your filters to see more results.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($migrations->hasPages())
            <div class="box-footer p-5 border-t border-slate-200/60">
                {{ $migrations->links() }}
            </div>
        @endif
    </div>

    <!-- Top Carriers Section -->
    @if($statistics['total_migrations'] > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Top Source Carriers -->
        <div class="box box--stacked">
            <div class="box-header flex items-center gap-3 p-5 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-primary" icon="ArrowRight" />
                <h3 class="text-lg font-semibold text-slate-800">Top Source Carriers</h3>
            </div>
            <div class="box-body p-5">
                <div class="space-y-4">
                    @forelse($statistics['top_source_carriers'] as $index => $carrier)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50/50 hover:bg-slate-100/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-primary">{{ $index + 1 }}</span>
                                </div>
                                <span class="font-medium text-slate-800">{{ $carrier['carrier'] }}</span>
                            </div>
                            <x-base.badge variant="primary" class="gap-1.5">
                                {{ $carrier['count'] }} {{ $carrier['count'] === 1 ? 'migration' : 'migrations' }}
                            </x-base.badge>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <x-base.lucide class="w-12 h-12 mx-auto text-slate-300 mb-3" icon="BarChart3" />
                            <p class="text-sm text-slate-500">No data available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Top Target Carriers -->
        <div class="box box--stacked">
            <div class="box-header flex items-center gap-3 p-5 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-success" icon="ArrowRight" />
                <h3 class="text-lg font-semibold text-slate-800">Top Target Carriers</h3>
            </div>
            <div class="box-body p-5">
                <div class="space-y-4">
                    @forelse($statistics['top_target_carriers'] as $index => $carrier)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50/50 hover:bg-slate-100/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-success/10 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-success">{{ $index + 1 }}</span>
                                </div>
                                <span class="font-medium text-slate-800">{{ $carrier['carrier'] }}</span>
                            </div>
                            <x-base.badge variant="success" class="gap-1.5">
                                {{ $carrier['count'] }} {{ $carrier['count'] === 1 ? 'migration' : 'migrations' }}
                            </x-base.badge>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <x-base.lucide class="w-12 h-12 mx-auto text-slate-300 mb-3" icon="BarChart3" />
                            <p class="text-sm text-slate-500">No data available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
