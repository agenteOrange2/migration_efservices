<div class="box box--stacked p-0  hover:shadow-lg transition-shadow duration-300">
    {{-- Card Header with Status Badge --}}
    <div class="relative p-4 pb-3 {{ $assignment->status === 'completed' ? 'bg-success/5' : ($assignment->status === 'overdue' ? 'bg-danger/5' : 'bg-primary/5') }}">
        <div class="flex items-start justify-between gap-2 mb-2">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <x-base.lucide class="w-5 h-5 {{ $this->contentTypeInfo['color'] }} flex-shrink-0" :icon="$this->contentTypeInfo['icon']" />
                <span class="text-xs text-slate-600 truncate">{{ $this->contentTypeInfo['label'] }}</span>
            </div>
            @if($assignment->status === 'completed')
                <x-base.badge variant="success" class="flex-shrink-0">
                    <x-base.lucide class="w-3 h-3 mr-1" icon="CheckCircle2" />
                    Completed
                </x-base.badge>
            @elseif($assignment->status === 'in_progress')
                <x-base.badge variant="info" class="flex-shrink-0">
                    <x-base.lucide class="w-3 h-3 mr-1" icon="Clock" />
                    In Progress
                </x-base.badge>
            @elseif($assignment->status === 'overdue')
                <x-base.badge variant="danger" class="flex-shrink-0">
                    <x-base.lucide class="w-3 h-3 mr-1" icon="AlertCircle" />
                    Overdue
                </x-base.badge>
            @else
                <x-base.badge variant="warning" class="flex-shrink-0">
                    <x-base.lucide class="w-3 h-3 mr-1" icon="Circle" />
                    Pending
                </x-base.badge>
            @endif
        </div>
        
        <h4 class="text-base font-semibold text-slate-800 mb-1 line-clamp-2">
            {{ $assignment->training->title ?? 'Training' }}
        </h4>
    </div>

    {{-- Card Body --}}
    <div class="p-4 space-y-3">
        {{-- Description --}}
        @if($assignment->training->description)
            <p class="text-sm text-slate-600 line-clamp-3">
                {{ $assignment->training->description }}
            </p>
        @endif

        {{-- Dates Info --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2 text-xs text-slate-600">
                <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                <span>Assigned: {{ $assignment->assigned_date ? $assignment->assigned_date->format('M d, Y') : 'N/A' }}</span>
            </div>
            
            @if($assignment->due_date)
                <div class="flex items-center gap-2 text-xs">
                    <x-base.lucide class="w-4 h-4 {{ $this->dueDateStatus['color'] === 'danger' ? 'text-danger' : ($this->dueDateStatus['color'] === 'warning' ? 'text-warning' : 'text-success') }}" icon="Clock" />
                    <span class="font-medium {{ $this->dueDateStatus['color'] === 'danger' ? 'text-danger' : ($this->dueDateStatus['color'] === 'warning' ? 'text-warning' : 'text-slate-600') }}">
                        Due: {{ $assignment->due_date->format('M d, Y') }}
                        @if($this->dueDateStatus['days'] !== null)
                            <span class="ml-1">({{ $this->dueDateStatus['days'] }} {{ $this->dueDateStatus['days'] === 1 ? 'day' : 'days' }} {{ $this->dueDateStatus['color'] === 'danger' && $assignment->status !== 'completed' ? 'overdue' : 'remaining' }})</span>
                        @endif
                    </span>
                </div>
            @endif

            @if($assignment->completed_date)
                <div class="flex items-center gap-2 text-xs text-success">
                    <x-base.lucide class="w-4 h-4" icon="CheckCircle" />
                    <span>Completed: {{ $assignment->completed_date->format('M d, Y') }}</span>
                </div>
            @endif
        </div>

        {{-- Progress Indicator for In Progress --}}
        @if($assignment->status === 'in_progress')
            <div class="pt-2">
                <div class="flex items-center gap-2 mb-1">
                    <x-base.lucide class="w-3 h-3 text-info animate-pulse" icon="Activity" />
                    <span class="text-xs text-info font-medium">Training in progress</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Card Footer with Actions --}}
    <div class="border-t border-slate-200 p-4 bg-slate-50/50 space-y-2">
        <div class="flex gap-2">
            {{-- View Details Button --}}
            <a href="{{ route('driver.trainings.show', $assignment->id) }}" 
                class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-medium min-h-[44px]">
                <x-base.lucide class="w-4 h-4" icon="Eye" />
                View Details
            </a>

            {{-- Action Button based on status --}}
            @if($assignment->status === 'assigned' || $assignment->status === 'overdue')
                <button wire:click="startTraining" 
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium min-h-[44px]">
                    <x-base.lucide class="w-4 h-4" icon="Play" />
                    Start Training
                </button>
            @elseif($assignment->status === 'in_progress')
                <button wire:click="openCompletionModal" 
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-success text-white rounded-lg hover:bg-success/90 transition-colors text-sm font-medium min-h-[44px]">
                    <x-base.lucide class="w-4 h-4" icon="CheckCircle2" />
                    Mark Complete
                </button>
            @elseif($assignment->status === 'completed')
                <div class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-success/10 text-success rounded-lg text-sm font-medium min-h-[44px]">
                    <x-base.lucide class="w-4 h-4" icon="CheckCircle2" />
                    Completed
                </div>
            @endif
        </div>
    </div>
</div>

