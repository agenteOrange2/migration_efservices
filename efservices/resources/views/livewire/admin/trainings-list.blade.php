<div class="space-y-6">
    {{-- Toolbar --}}
    <div class="box p-4">
        <div class="flex flex-col md:flex-row gap-4">
            {{-- Search --}}
            <div class="flex-1">
                <div class="relative">
                    <x-base.lucide class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" icon="Search" />
                    <input type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search by title or description..." 
                        class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            {{-- Filters --}}
            <div class="flex gap-2">
                <select wire:model.live="statusFilter" class="border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <select wire:model.live="contentTypeFilter" class="border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Types</option>
                    <option value="file">File</option>
                    <option value="video">Video</option>
                    <option value="url">URL</option>
                </select>

                @if($search || $statusFilter || $contentTypeFilter)
                    <button wire:click="clearFilters" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="w-5 h-5" icon="X" />
                    </button>
                @endif
            </div>

            {{-- View Toggle --}}
            <button wire:click="toggleViewMode" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                <x-base.lucide class="w-5 h-5" :icon="$viewMode === 'cards' ? 'List' : 'LayoutGrid'" />
            </button>
        </div>

        {{-- Bulk Actions --}}
        @if(count($selectedTrainings) > 0)
            <div class="mt-4 flex items-center gap-3 p-3 bg-primary/10 rounded-lg border border-primary/20">
                <span class="text-sm font-medium text-slate-700">{{ count($selectedTrainings) }} selected</span>
                <div class="flex gap-2">
                    <button wire:click="bulkActivate" class="px-3 py-1 bg-success text-white rounded hover:bg-success/90 text-sm">
                        Activate
                    </button>
                    <button wire:click="bulkDeactivate" class="px-3 py-1 bg-warning text-white rounded hover:bg-warning/90 text-sm">
                        Deactivate
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div class="box p-4 bg-success/10 border border-success/20">
            <div class="flex items-center gap-2 text-success">
                <x-base.lucide class="w-5 h-5" icon="CheckCircle2" />
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="box p-4 bg-danger/10 border border-danger/20">
            <div class="flex items-center gap-2 text-danger">
                <x-base.lucide class="w-5 h-5" icon="AlertCircle" />
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Content --}}
    @if($trainings->count() > 0)
        @if($viewMode === 'cards')
            {{-- Card View --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($trainings as $training)
                    <div class="box p-0 overflow-hidden hover:shadow-lg transition-shadow">
                        {{-- Card Header --}}
                        <div class="p-4 {{ $training->status === 'active' ? 'bg-success/5' : 'bg-slate-100' }}">
                            <div class="flex items-start justify-between mb-2">
                                <input type="checkbox" 
                                    wire:model.live="selectedTrainings" 
                                    value="{{ $training->id }}"
                                    class="rounded border-slate-300 text-primary focus:ring-primary mt-1">
                                <x-base.badge :variant="$training->status === 'active' ? 'success' : 'secondary'">
                                    {{ ucfirst($training->status) }}
                                </x-base.badge>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-800 mb-1 line-clamp-2">
                                {{ $training->title }}
                            </h3>
                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                @if($training->content_type === 'video')
                                    <x-base.lucide class="w-4 h-4 text-purple-600" icon="Video" />
                                    <span>Video Training</span>
                                @elseif($training->content_type === 'url')
                                    <x-base.lucide class="w-4 h-4 text-blue-600" icon="ExternalLink" />
                                    <span>Online Training</span>
                                @else
                                    <x-base.lucide class="w-4 h-4 text-green-600" icon="FileText" />
                                    <span>Document Training</span>
                                @endif
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="p-4">
                            <p class="text-sm text-slate-600 mb-4 line-clamp-3">
                                {{ $training->description ?? 'No description available' }}
                            </p>

                            {{-- Stats --}}
                            <div class="flex items-center justify-between mb-4 text-sm">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <x-base.lucide class="w-4 h-4" icon="Users" />
                                    <span>{{ $training->driver_assignments_count }} assignments</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $training->created_at->format('M d, Y') }}
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex gap-2">
                                <a href="{{ route('admin.trainings.show', $training->id) }}" 
                                    class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm">
                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                    View
                                </a>
                                <a href="{{ route('admin.trainings.edit', $training->id) }}" 
                                    class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm">
                                    <x-base.lucide class="w-4 h-4" icon="Edit" />
                                    Edit
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Table View --}}
            <div class="box overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="p-4 text-left">
                                <input type="checkbox" 
                                    wire:model.live="selectAll"
                                    class="rounded border-slate-300 text-primary focus:ring-primary">
                            </th>
                            <th class="p-4 text-left text-sm font-semibold text-slate-700">Title</th>
                            <th class="p-4 text-left text-sm font-semibold text-slate-700">Type</th>
                            <th class="p-4 text-left text-sm font-semibold text-slate-700">Status</th>
                            <th class="p-4 text-left text-sm font-semibold text-slate-700">Assignments</th>
                            <th class="p-4 text-left text-sm font-semibold text-slate-700">Created</th>
                            <th class="p-4 text-right text-sm font-semibold text-slate-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trainings as $training)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="p-4">
                                    <input type="checkbox" 
                                        wire:model.live="selectedTrainings" 
                                        value="{{ $training->id }}"
                                        class="rounded border-slate-300 text-primary focus:ring-primary">
                                </td>
                                <td class="p-4">
                                    <div class="font-medium text-slate-800">{{ $training->title }}</div>
                                    <div class="text-sm text-slate-500 line-clamp-1">{{ $training->description }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        @if($training->content_type === 'video')
                                            <x-base.lucide class="w-4 h-4 text-purple-600" icon="Video" />
                                        @elseif($training->content_type === 'url')
                                            <x-base.lucide class="w-4 h-4 text-blue-600" icon="ExternalLink" />
                                        @else
                                            <x-base.lucide class="w-4 h-4 text-green-600" icon="FileText" />
                                        @endif
                                        <span class="text-sm text-slate-700">{{ ucfirst($training->content_type) }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <x-base.badge :variant="$training->status === 'active' ? 'success' : 'secondary'">
                                        {{ ucfirst($training->status) }}
                                    </x-base.badge>
                                </td>
                                <td class="p-4 text-sm text-slate-700">
                                    {{ $training->driver_assignments_count }}
                                </td>
                                <td class="p-4 text-sm text-slate-600">
                                    {{ $training->created_at->format('M d, Y') }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.trainings.show', $training->id) }}" 
                                            class="px-3 py-1 bg-white border border-slate-300 text-slate-700 rounded hover:bg-slate-50 text-sm">
                                            View
                                        </a>
                                        <a href="{{ route('admin.trainings.edit', $training->id) }}" 
                                            class="px-3 py-1 bg-primary text-white rounded hover:bg-primary/90 text-sm">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $trainings->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="box p-12 text-center">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="Inbox" />
            <h3 class="text-lg font-semibold text-slate-700 mb-2">No Trainings Found</h3>
            <p class="text-slate-500 mb-4">
                @if($search || $statusFilter || $contentTypeFilter)
                    No trainings match your filters. Try adjusting your search criteria.
                @else
                    Get started by creating your first training.
                @endif
            </p>
            @if($search || $statusFilter || $contentTypeFilter)
                <button wire:click="clearFilters" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    Clear Filters
                </button>
            @else
                <a href="{{ route('admin.trainings.create') }}" class="inline-flex items-center gap-2 px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    <x-base.lucide class="w-5 h-5" icon="Plus" />
                    Create Training
                </a>
            @endif
        </div>
    @endif
</div>

