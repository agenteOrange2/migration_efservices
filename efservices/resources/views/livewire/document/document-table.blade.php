<div>
    <!-- Analytics Cards -->
    @if(isset($analytics))
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
        <!-- Total Carriers Card -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="Users" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Total Carriers</p>
                    <p class="text-2xl font-bold text-slate-800">{{ $analytics['total_carriers'] }}</p>
                </div>
            </div>
        </div>
        
        <!-- Active Carriers Card -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-success/10 rounded-xl border border-success/20">
                    <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Active Carriers</p>
                    <p class="text-2xl font-bold text-slate-800">{{ $analytics['active_carriers'] }}</p>
                </div>
            </div>
        </div>
        
        <!-- Pending Carriers Card -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-warning/10 rounded-xl border border-warning/20">
                    <x-base.lucide class="w-6 h-6 text-warning" icon="Clock" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Pending Carriers</p>
                    <p class="text-2xl font-bold text-slate-800">{{ $analytics['pending_carriers'] }}</p>
                </div>
            </div>
        </div>
        
        <!-- Completion Rate Card -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-info/10 rounded-xl border border-info/20">
                    <x-base.lucide class="w-6 h-6 text-info" icon="BarChart3" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Completion Rate</p>
                    <p class="text-2xl font-bold text-slate-800">{{ $analytics['completion_rate'] }}%</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <!-- Búsqueda mejorada -->
            <div class="flex-1 w-full lg:w-auto">
                <div class="relative">
                    <x-base.lucide class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 text-slate-400" icon="Search" />
                    <input type="text" 
                           wire:model.live.debounce.500ms="search"
                           placeholder="Search by name, email, phone or user..."
                           class="form-control w-full lg:w-80 pl-9 rounded-lg border-slate-200 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <!-- Botones de exportación -->
                <div class="flex gap-2">
                    <x-base.button 
                        type="button"
                        wire:click="exportData('excel')" 
                        variant="success" 
                        class="gap-2 text-sm">
                        <x-base.lucide class="w-4 h-4" icon="FileSpreadsheet" />
                        Excel
                    </x-base.button>
                    <x-base.button 
                        type="button"
                        wire:click="exportData('pdf')" 
                        variant="danger" 
                        class="gap-2 text-sm">
                        <x-base.lucide class="w-4 h-4" icon="FileText" />
                        PDF
                    </x-base.button>
                </div>

                        <!-- Filtros avanzados -->
                        <div x-data="{ open: $wire.entangle('openPopover').live }" class="relative inline-block w-full">
                            <!-- Botón para abrir/cerrar el popover -->
                            <x-base.button 
                                type="button"
                                @click="open = !open"
                                variant="outline-secondary" 
                                class="gap-2 w-full sm:w-auto">
                                <x-base.lucide class="w-4 h-4" icon="Filter" />
                                Filters
                                @if($filters['status'] || $filters['date_range']['start'] || $filters['expiring_soon'])
                                    <span class="px-1.5 py-0.5 text-xs bg-primary text-white rounded-full">
                                        {{ array_filter($filters, fn($v) => $v && $v !== [] && ($v['start'] ?? true)) ? count(array_filter($filters, fn($v) => $v && $v !== [] && ($v['start'] ?? true))) : 0 }}
                                    </span>
                                @endif
                            </x-base.button>

                            <!-- Panel de filtros mejorado -->
                            <div x-show="open"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-2" 
                                @click.away="open = false"
                                class="absolute right-0 bg-white border border-slate-200 rounded-lg shadow-xl mt-2 w-80 z-50">
                                <div class="p-5 space-y-5">
                                    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                                        <x-base.lucide class="w-5 h-5 text-primary" icon="Filter" />
                                        <h3 class="font-semibold text-slate-800">Advanced Filters</h3>
                                    </div>
                                    
                                    <!-- Filtro de estado -->
                                    <div>
                                        <x-base.form-label>Status</x-base.form-label>
                                        <select wire:model.live="filters.status" class="form-select w-full rounded-lg border-slate-200">
                                            <option value="">All Status</option>
                                            <option value="active">Active (Complete)</option>
                                            <option value="pending">Pending</option>
                                            <option value="incomplete">Incomplete</option>
                                        </select>
                                    </div>

                                    <!-- Rango de fechas -->
                                    <div>
                                        <x-base.form-label>Date Range</x-base.form-label>
                                        <input id="date-range-picker" type="text"
                                            class="form-control w-full rounded-lg border-slate-200"
                                            placeholder="Select date range" />
                                    </div>

                                    <!-- Documentos que expiran pronto -->
                                    <div class="flex items-center p-3 bg-slate-50 rounded-lg border border-slate-200">
                                        <input type="checkbox" wire:model.live="filters.expiring_soon" 
                                            class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary" 
                                            id="expiring-soon">
                                        <label for="expiring-soon" class="ml-2 text-sm font-medium text-slate-700 cursor-pointer">
                                            Documents expiring in 30 days
                                        </label>
                                    </div>

                                    <!-- Tipos de documentos -->
                                    @if(isset($documentTypes) && $documentTypes->count() > 0)
                                    <div>
                                        <x-base.form-label>Document Types</x-base.form-label>
                                        <div class="space-y-2 max-h-32 overflow-y-auto p-2 bg-slate-50 rounded-lg border border-slate-200">
                                            @foreach($documentTypes as $docType)
                                            <div class="flex items-center">
                                                <input type="checkbox" wire:model.live="filters.document_types" 
                                                    value="{{ $docType->id }}" 
                                                    class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary" 
                                                    id="doc-type-{{ $docType->id }}">
                                                <label for="doc-type-{{ $docType->id }}" class="ml-2 text-sm text-slate-700 cursor-pointer">
                                                    {{ $docType->name }}
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Botones de acción -->
                                    <div class="flex gap-3 pt-4 border-t border-slate-200">
                                        <x-base.button 
                                            type="button"
                                            wire:click="resetFilters" 
                                            @click="open = false"
                                            variant="outline-secondary" 
                                            class="flex-1">
                                            Clear Filters
                                        </x-base.button>
                                        <x-base.button 
                                            type="button"
                                            @click="open = false"
                                            variant="primary" 
                                            class="flex-1">
                                            Apply
                                        </x-base.button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Tabla responsiva -->
                <div class="overflow-auto xl:overflow-visible">
                    <!-- Vista de escritorio -->
                    <div class="hidden md:block">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td wire:click="sortBy('name')" 
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 cursor-pointer hover:bg-slate-100 transition-colors">
                                        <div class="flex items-center gap-2">
                                            Carrier Name
                                            @if($sortField === 'name')
                                                @if($sortDirection === 'asc')
                                                    <x-base.lucide class="w-4 h-4 text-primary" icon="ChevronUp" />
                                                @else
                                                    <x-base.lucide class="w-4 h-4 text-primary" icon="ChevronDown" />
                                                @endif
                                            @endif
                                        </div>
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">                                        
                                        User Carrier
                                    </x-base.table.td>
                                    <x-base.table.td wire:click="sortBy('completion_percentage')"
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 cursor-pointer hover:bg-slate-100 transition-colors">
                                        <div class="flex items-center gap-2">
                                            Progress
                                            @if($sortField === 'completion_percentage')
                                                @if($sortDirection === 'asc')
                                                    <x-base.lucide class="w-4 h-4 text-primary" icon="ChevronUp" />
                                                @else
                                                    <x-base.lucide class="w-4 h-4 text-primary" icon="ChevronDown" />
                                                @endif
                                            @endif
                                        </div>
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                        Status
                                    </x-base.table.td>
                                    <x-base.table.td wire:click="sortBy('created_at')"
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 cursor-pointer hover:bg-slate-100 transition-colors">
                                        <div class="flex items-center gap-2">
                                            Register Date
                                            @if($sortField === 'created_at')
                                                @if($sortDirection === 'asc')
                                                    <x-base.lucide class="w-4 h-4 text-primary" icon="ChevronUp" />
                                                @else
                                                    <x-base.lucide class="w-4 h-4 text-primary" icon="ChevronDown" />
                                                @endif
                                            @endif
                                        </div>
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="w-20 border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500">
                                        Actions
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @forelse ($carriers as $carrier)
                                <x-base.table.tr class="hover:bg-slate-50 transition-colors">
                                    <x-base.table.td class="border-dashed py-4">
                                        <div class="flex items-center">
                                            <div class="image-fit zoom-in h-10 w-10">
                                                <img class="rounded-full shadow-md border-2 border-white"
                                                    src="{{ $carrier->getFirstMediaUrl('logo_carrier') ?: asset('build/default_profile.png') }}"
                                                    alt="Logo {{ $carrier->name }}">
                                            </div>
                                            <div class="ml-3.5">
                                                <a class="whitespace-nowrap font-medium text-primary hover:underline"
                                                    href="{{ route('admin.carrier.documents', $carrier->slug) }}">
                                                    {{ $carrier->name }}
                                                </a>                                                
                                                @if($carrier->expiring_documents > 0)
                                                    <div class="text-xs text-red-600 font-medium">
                                                        ⚠️ {{ $carrier->expiring_documents }} doc(s) expiring soon
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </x-base.table.td>

                                    <x-base.table.td>
                                        @if($carrier->userCarriers->first())
                                            <div>
                                                <a class="whitespace-nowrap font-medium text-primary hover:underline">
                                                    {{ $carrier->userCarriers->first()->user->name ?? 'N/A' }}
                                                </a>
                                                <div class="text-xs text-slate-500">
                                                    {{ $carrier->userCarriers->first()->user->email ?? '' }}
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">Unassigned</span>
                                        @endif
                                    </x-base.table.td>

                                    <x-base.table.td class="border-dashed py-4">
                                        <div class="w-48">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-xs text-slate-500">
                                                    {{ $carrier->completion_percentage }}%
                                                </span>
                                                <span class="text-xs text-slate-500">
                                                    {{ $carrier->documents_summary['approved'] }}/{{ $carrier->documents_summary['total'] }}
                                                </span>
                                            </div>
                                            <div class="flex h-2 rounded-full border bg-slate-50 overflow-hidden">
                                                <div class="bg-gradient-to-r from-green-400 to-green-600 transition-all duration-500 ease-out"
                                                    style="width: {{ $carrier->completion_percentage }}%;"></div>
                                            </div>
                                            <div class="flex justify-between text-xs text-slate-400 mt-1">
                                                <span>Approved: {{ $carrier->documents_summary['approved'] }}</span>
                                                <span>Pending: {{ $carrier->documents_summary['pending'] }}</span>
                                            </div>
                                        </div>
                                    </x-base.table.td>

                                    <x-base.table.td>
                                        <div class="flex items-center justify-center">
                                            @if ($carrier->document_status == 'active')
                                                <x-base.badge variant="success" class="gap-1.5">
                                                    <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                                                    Active
                                                </x-base.badge>
                                            @elseif ($carrier->document_status == 'pending')
                                                <x-base.badge variant="warning" class="gap-1.5">
                                                    <x-base.lucide class="w-3 h-3" icon="Clock" />
                                                    Pending
                                                </x-base.badge>
                                            @else
                                                <x-base.badge variant="danger" class="gap-1.5">
                                                    <x-base.lucide class="w-3 h-3" icon="XCircle" />
                                                    Incomplete
                                                </x-base.badge>
                                            @endif
                                        </div>
                                    </x-base.table.td>

                                    <x-base.table.td>
                                        <div class="text-sm text-slate-600">
                                            {{ $carrier->created_at->format('d M Y') }}
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            {{ $carrier->created_at->diffForHumans() }}
                                        </div>
                                    </x-base.table.td>

                                    <x-base.table.td>
                                        <div class="flex items-center justify-center">
                                            <div x-data="{ open: false }" class="relative">
                                                <button @click="open = !open" @click.outside="open = false"
                                                    class="cursor-pointer h-8 w-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="text-slate-500">
                                                        <circle cx="12" cy="12" r="1"></circle>
                                                        <circle cx="12" cy="5" r="1"></circle>
                                                        <circle cx="12" cy="19" r="1"></circle>
                                                    </svg>
                                                </button>

                                                <div x-show="open" x-transition
                                                    class="absolute right-0 z-10 w-48 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg">
                                                    <div class="py-1">
                                                        <a href="{{ route('admin.carrier.admin_documents.review', $carrier->slug) }}"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                            </svg>
                                                            Review Documents
                                                        </a>
                                                        <a href="{{ route('admin.carrier.documents', $carrier->slug) }}"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                            View Documents
                                                        </a>
                                                        <button wire:click="viewCarrierDocuments({{ $carrier->id }})"
                                                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                            </svg>
                                                            Manage Carrier
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </x-base.table.td>
                                </x-base.table.tr>
                                @empty
                                <x-base.table.tr>
                                    <x-base.table.td colspan="6" class="text-center py-12">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">There are no registered carriers.</h3>
                                            <p class="text-gray-500 text-center max-w-sm">
                                                No carriers found that match the current search criteria.
                                            </p>
                                            @if($search || array_filter($filters))
                                            <button wire:click="resetFilters" class="mt-4 px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
                                                Clear Filters
                                            </button>
                                            @endif
                                        </div>
                                    </x-base.table.td>
                                </x-base.table.tr>
                                @endforelse
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>

                    <!-- Vista móvil (tarjetas) -->
                    <div class="md:hidden space-y-4 p-4">
                        @forelse ($carriers as $carrier)
                        <div class="box box--stacked p-4 hover:shadow-lg transition-shadow">
                            <div class="flex items-start space-x-3">
                                <div class="image-fit h-12 w-12">
                                    <img class="rounded-full shadow-md border-2 border-white"
                                        src="{{ $carrier->getFirstMediaUrl('logo_carrier') ?: asset('build/default_profile.png') }}"
                                        alt="Logo {{ $carrier->name }}">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-medium text-gray-900 truncate">
                                            {{ $carrier->name }}
                                        </h3>
                                        @if ($carrier->document_status == 'active')
                                            <x-base.badge variant="success" class="gap-1">
                                                Active
                                            </x-base.badge>
                                        @elseif ($carrier->document_status == 'pending')
                                            <x-base.badge variant="warning" class="gap-1">
                                                Pending
                                            </x-base.badge>
                                        @else
                                            <x-base.badge variant="danger" class="gap-1">
                                                Incomplete
                                            </x-base.badge>
                                        @endif
                                    </div>
                                    
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $carrier->email ?? 'Sin email' }}
                                    </p>
                                    
                                    @if($carrier->userCarriers->first())
                                    <p class="text-xs text-gray-500">
                                        User: {{ $carrier->userCarriers->first()->user->name ?? 'N/A' }}
                                    </p>
                                    @endif

                                    <!-- Progreso -->
                                    <div class="mt-3">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs text-gray-500">Progress</span>
                                            <span class="text-xs font-medium">{{ $carrier->completion_percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-500"
                                                style="width: {{ $carrier->completion_percentage }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                                            <span>{{ $carrier->documents_summary['approved'] }}/{{ $carrier->documents_summary['total'] }}</span>
                                            <span>{{ $carrier->created_at->format('d M Y') }}</span>
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="flex space-x-2 mt-3">
                                        <a href="{{ route('admin.carrier.admin_documents.review', $carrier->slug) }}"
                                            class="flex-1 text-center px-3 py-1 text-xs bg-primary text-white rounded hover:bg-primary-dark transition-colors">
                                            Review
                                        </a>
                                        <a href="{{ route('admin.carrier.documents', $carrier->slug) }}"
                                            class="flex-1 text-center px-3 py-1 text-xs bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                                            View Docs
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No carriers found</h3>
                            <p class="text-gray-500 text-sm">No carriers found that match your search.</p>
                        </div>
                        @endforelse
                    </div>

                    <!-- Paginación mejorada -->
                    @if($carriers->hasPages())
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                @if ($carriers->onFirstPage())
                                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md">
                                        Previous
                                    </span>
                                @else
                                    <button wire:click="previousPage" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Previous
                                    </button>
                                @endif

                                @if ($carriers->hasMorePages())
                                    <button wire:click="nextPage" class="ml-3 relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Next
                                    </button>
                                @else
                                    <span class="ml-3 relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-md">
                                        Next
                                    </span>
                                @endif
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing
                                        <span class="font-medium">{{ $carriers->firstItem() }}</span>
                                        a
                                        <span class="font-medium">{{ $carriers->lastItem() }}</span>
                                        de
                                        <span class="font-medium">{{ $carriers->total() }}</span>
                                        resultadosresults
                                    </p>
                                </div>
                                <div>
                                    {{ $carriers->links('custom.livewire-pagination') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <!-- Loading Overlay -->
                <div wire:loading class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-50 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                        <span class="text-gray-600">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@pushOnce('styles')
@vite('resources/css/vendors/litepicker.css')
@endPushOnce

@pushOnce('vendors')
@vite('resources/js/vendors/dayjs.js')
@vite('resources/js/vendors/litepicker.js')
@endPushOnce

@pushOnce('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const datePicker = document.getElementById('date-range-picker');

        if (datePicker) {
            // Limpiar instancia existente si existe
            if (datePicker._litepicker) {
                datePicker._litepicker.destroy();
            }

            const litepickerInstance = new Litepicker({
                element: datePicker,
                singleMode: false,
                format: 'YYYY-MM-DD',
                autoApply: true,
                showTooltip: true,
                tooltipText: {
                    one: 'día',
                    other: 'días'
                },
                dropdowns: {
                    minYear: 2000,
                    maxYear: new Date().getFullYear(),
                    months: true,
                    years: true,
                },
                buttonText: {
                    apply: 'Aplicar',
                    cancel: 'Cancelar',
                    previousMonth: 'Mes anterior',
                    nextMonth: 'Mes siguiente',
                },
                setup: (picker) => {
                    picker.on('selected', (startDate, endDate) => {
                        if (startDate && endDate) {
                            console.log('Rango seleccionado:', {
                                start: startDate.format('YYYY-MM-DD'),
                                end: endDate.format('YYYY-MM-DD'),
                            });
                            
                            // Enviar evento a Livewire
                            Livewire.dispatch('updateDateRange', {
                                dates: {
                                    start: startDate.format('YYYY-MM-DD'),
                                    end: endDate.format('YYYY-MM-DD'),
                                }
                            });
                        }
                    });

                    picker.on('clear', () => {
                        console.log('Fechas limpiadas');
                        Livewire.dispatch('updateDateRange', {
                            dates: {
                                start: null,
                                end: null,
                            }
                        });
                    });
                },
            });

            // Guardar referencia para poder destruir después
            datePicker._litepicker = litepickerInstance;
        }
    });

    // Escuchar eventos de Livewire para actualizar el picker
    document.addEventListener('livewire:navigated', function() {
        // Reinicializar el date picker después de navegación
        setTimeout(() => {
            const datePicker = document.getElementById('date-range-picker');
            if (datePicker && !datePicker._litepicker) {
                // Reinicializar si no existe
                window.dispatchEvent(new Event('DOMContentLoaded'));
            }
        }, 100);
    });
</script>
@endPushOnce