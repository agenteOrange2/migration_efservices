<div class="space-y-6">
    {{-- Stats Summary Card --}}
    <div class="box box--stacked p-5 bg-gradient-to-br from-primary/5 to-primary/10 border-primary/20">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-800">Your Training Progress</h3>
            <div class="flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="TrendingUp" />
                <span class="text-2xl font-bold text-primary">{{ $stats['completion_percentage'] }}%</span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                <div class="text-2xl font-bold text-slate-800">{{ $stats['total'] }}</div>
                <div class="text-xs text-slate-500 uppercase mt-1">Total</div>
            </div>
            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                <div class="text-2xl font-bold text-success">{{ $stats['completed'] }}</div>
                <div class="text-xs text-slate-500 uppercase mt-1">Completed</div>
            </div>
            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                <div class="text-2xl font-bold text-info">{{ $stats['in_progress'] }}</div>
                <div class="text-xs text-slate-500 uppercase mt-1">In Progress</div>
            </div>
            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                <div class="text-2xl font-bold text-warning">{{ $stats['pending'] }}</div>
                <div class="text-xs text-slate-500 uppercase mt-1">Pending</div>
            </div>
            <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                <div class="text-2xl font-bold text-danger">{{ $stats['overdue'] }}</div>
                <div class="text-xs text-slate-500 uppercase mt-1">Overdue</div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="flex flex-wrap gap-2">
        <button wire:click="$set('statusFilter', 'all')" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $statusFilter === 'all' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            All Trainings
        </button>
        <button wire:click="$set('statusFilter', 'assigned')" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $statusFilter === 'assigned' ? 'bg-warning text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Pending
        </button>
        <button wire:click="$set('statusFilter', 'in_progress')" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $statusFilter === 'in_progress' ? 'bg-info text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            In Progress
        </button>
        <button wire:click="$set('statusFilter', 'completed')" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $statusFilter === 'completed' ? 'bg-success text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Completed
        </button>
        <button wire:click="$set('statusFilter', 'overdue')" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $statusFilter === 'overdue' ? 'bg-danger text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Overdue
        </button>
    </div>

    {{-- Trainings Grid --}}
    @if($trainings->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($trainings as $assignment)
                <livewire:driver.training-card :assignment="$assignment" :key="'training-'.$assignment->id" />
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $trainings->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="box box--stacked p-12 text-center">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="GraduationCap" />
            <h3 class="text-lg font-semibold text-slate-700 mb-2">
                @if($statusFilter === 'all')
                    No Trainings Assigned
                @else
                    No {{ ucfirst($statusFilter) }} Trainings
                @endif
            </h3>
            <p class="text-slate-500 max-w-md mx-auto">
                @if($statusFilter === 'all')
                    You don't have any trainings assigned yet. Your assigned trainings will appear here.
                @else
                    You don't have any trainings with this status. Try selecting a different filter.
                @endif
            </p>
            @if($statusFilter !== 'all')
                <button wire:click="$set('statusFilter', 'all')" 
                    class="mt-4 px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    View All Trainings
                </button>
            @endif
        </div>
    @endif

    {{-- Completion Modal --}}
    @if($showCompletionModal && $selectedTrainingId)
        <livewire:driver.training-completion-modal :trainingId="$selectedTrainingId" :key="'modal-'.$selectedTrainingId" />
    @endif
</div>

