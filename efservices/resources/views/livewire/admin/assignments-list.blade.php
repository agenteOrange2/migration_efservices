<div class="space-y-6">
    {{-- Toolbar --}}
    <div class="box p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <div class="relative">
                    <x-base.lucide class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" icon="Search" />
                    <input type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Search driver or training..." 
                        class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            {{-- Filters --}}
            <div>
                <select wire:model.live="trainingFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Trainings</option>
                    @foreach($trainings as $training)
                        <option value="{{ $training->id }}">{{ Str::limit($training->title, 30) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="carrierFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Carriers</option>
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}">{{ Str::limit($carrier->name, 30) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="statusFilter" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Status</option>
                    <option value="assigned">Assigned</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
        </div>

        {{-- Clear Filters --}}
        @if($search || $trainingFilter || $carrierFilter || $statusFilter || $dateFrom || $dateTo)
            <div class="mt-3 flex justify-end">
                <button wire:click="clearFilters" class="flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors text-sm">
                    <x-base.lucide class="w-4 h-4" icon="X" />
                    Clear All Filters
                </button>
            </div>
        @endif

        {{-- Bulk Actions Bar --}}
        @if(count($selectedAssignments) > 0)
            <div class="mt-4 flex items-center justify-between p-3 bg-primary/10 rounded-lg border border-primary/20">
                <span class="text-sm font-medium text-slate-700">{{ count($selectedAssignments) }} assignment(s) selected</span>
                <div class="flex gap-2">
                    <button wire:click="bulkMarkInProgress" class="px-3 py-1.5 bg-info text-white rounded hover:bg-info/90 text-sm flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Clock" />
                        Mark In Progress
                    </button>
                    <button wire:click="bulkMarkComplete" class="px-3 py-1.5 bg-success text-white rounded hover:bg-success/90 text-sm flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4" icon="CheckCircle2" />
                        Mark Complete
                    </button>
                    <button wire:click="bulkDelete" 
                        onclick="return confirm('Are you sure you want to delete the selected assignments?')"
                        class="px-3 py-1.5 bg-danger text-white rounded hover:bg-danger/90 text-sm flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Trash2" />
                        Delete
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

    {{-- Assignments Table --}}
    @if($assignments->count() > 0)
        <div class="box overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="p-4 text-left">
                            <input type="checkbox" 
                                wire:model.live="selectAll"
                                class="rounded border-slate-300 text-primary focus:ring-primary">
                        </th>
                        <th class="p-4 text-left text-sm font-semibold text-slate-700">Driver</th>
                        <th class="p-4 text-left text-sm font-semibold text-slate-700">Training</th>
                        <th class="p-4 text-left text-sm font-semibold text-slate-700">Carrier</th>
                        <th class="p-4 text-left text-sm font-semibold text-slate-700">Status</th>
                        <th class="p-4 text-left text-sm font-semibold text-slate-700">Assigned</th>
                        <th class="p-4 text-left text-sm font-semibold text-slate-700">Due Date</th>
                        <th class="p-4 text-right text-sm font-semibold text-slate-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $assignment)
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <td class="p-4">
                                <input type="checkbox" 
                                    wire:model.live="selectedAssignments" 
                                    value="{{ $assignment->id }}"
                                    class="rounded border-slate-300 text-primary focus:ring-primary">
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10">
                                        <x-base.lucide class="w-5 h-5 text-primary" icon="User" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-800">{{ $assignment->driver->user->name ?? 'N/A' }}</div>
                                        <div class="text-sm text-slate-500">{{ $assignment->driver->user->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="font-medium text-slate-800">{{ Str::limit($assignment->training->title ?? 'N/A', 40) }}</div>
                                <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                                    @if($assignment->training->content_type === 'video')
                                        <x-base.lucide class="w-3 h-3 text-purple-600" icon="Video" />
                                        <span>Video</span>
                                    @elseif($assignment->training->content_type === 'url')
                                        <x-base.lucide class="w-3 h-3 text-blue-600" icon="ExternalLink" />
                                        <span>URL</span>
                                    @else
                                        <x-base.lucide class="w-3 h-3 text-green-600" icon="FileText" />
                                        <span>File</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 text-sm text-slate-700">
                                {{ $assignment->driver->carrier->name ?? 'N/A' }}
                            </td>
                            <td class="p-4">
                                @if($assignment->status === 'completed')
                                    <x-base.badge variant="success" class="flex items-center gap-1 w-fit">
                                        <x-base.lucide class="w-3 h-3" icon="CheckCircle2" />
                                        Completed
                                    </x-base.badge>
                                @elseif($assignment->status === 'in_progress')
                                    <x-base.badge variant="info" class="flex items-center gap-1 w-fit">
                                        <x-base.lucide class="w-3 h-3" icon="Clock" />
                                        In Progress
                                    </x-base.badge>
                                @elseif($assignment->status === 'overdue')
                                    <x-base.badge variant="danger" class="flex items-center gap-1 w-fit">
                                        <x-base.lucide class="w-3 h-3" icon="AlertCircle" />
                                        Overdue
                                    </x-base.badge>
                                @else
                                    <x-base.badge variant="warning" class="flex items-center gap-1 w-fit">
                                        <x-base.lucide class="w-3 h-3" icon="Circle" />
                                        Assigned
                                    </x-base.badge>
                                @endif
                            </td>
                            <td class="p-4 text-sm text-slate-600">
                                {{ $assignment->assigned_date ? $assignment->assigned_date->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="p-4">
                                @if($assignment->due_date)
                                    @php
                                        $daysRemaining = \Carbon\Carbon::now()->diffInDays($assignment->due_date, false);
                                        $isOverdue = $daysRemaining < 0 && $assignment->status !== 'completed';
                                        $isDueSoon = $daysRemaining >= 0 && $daysRemaining <= 3;
                                    @endphp
                                    <div class="text-sm {{ $isOverdue ? 'text-danger font-medium' : ($isDueSoon ? 'text-warning font-medium' : 'text-slate-600') }}">
                                        {{ $assignment->due_date->format('M d, Y') }}
                                    </div>
                                    @if($isOverdue)
                                        <div class="text-xs text-danger">{{ abs($daysRemaining) }} days overdue</div>
                                    @elseif($isDueSoon)
                                        <div class="text-xs text-warning">{{ $daysRemaining }} days left</div>
                                    @endif
                                @else
                                    <span class="text-sm text-slate-500">No due date</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.training-assignments.show', $assignment->id) }}" 
                                        class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 rounded hover:bg-slate-50 text-sm flex items-center gap-1">
                                        <x-base.lucide class="w-3 h-3" icon="Eye" />
                                        View
                                    </a>
                                    @if($assignment->status !== 'completed')
                                        <form action="{{ route('admin.training-assignments.destroy', $assignment->id) }}" 
                                            method="POST" 
                                            onsubmit="return confirm('Are you sure you want to delete this assignment?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 bg-danger text-white rounded hover:bg-danger/90 text-sm flex items-center gap-1">
                                                <x-base.lucide class="w-3 h-3" icon="Trash2" />
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $assignments->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="box p-12 text-center">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="Inbox" />
            <h3 class="text-lg font-semibold text-slate-700 mb-2">No Assignments Found</h3>
            <p class="text-slate-500 mb-4">
                @if($search || $trainingFilter || $carrierFilter || $statusFilter)
                    No assignments match your filters. Try adjusting your search criteria.
                @else
                    No training assignments have been created yet.
                @endif
            </p>
            @if($search || $trainingFilter || $carrierFilter || $statusFilter)
                <button wire:click="clearFilters" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    Clear Filters
                </button>
            @else
                <a href="{{ route('admin.select-training') }}" class="inline-flex items-center gap-2 px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    <x-base.lucide class="w-5 h-5" icon="Plus" />
                    Create Assignment
                </a>
            @endif
        </div>
    @endif
</div>

