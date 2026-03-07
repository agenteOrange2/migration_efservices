@php
    $migrationInfo = $migrationInfo ?? null;
@endphp

<div class="space-y-6">
    <div>
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="ArrowRightLeft" />
            <h3 class="text-lg font-semibold text-slate-800">Migration Details</h3>
        </div>
        
        @if($migrationInfo)
            <div class="space-y-6">
                <!-- Migration Summary Card -->
                <div class="bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/20 rounded-lg p-5">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-6 h-6 text-primary" icon="ArrowRightLeft" />
                        <div>
                            <p class="text-sm font-medium text-primary">
                                Migrated from <strong class="text-slate-900">{{ $migrationInfo['source_carrier'] }}</strong> 
                                to <strong class="text-slate-900">{{ $migrationInfo['target_carrier'] }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-start gap-3 p-4 bg-slate-50 rounded-lg border border-slate-200/60">
                        <x-base.lucide class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" icon="Calendar" />
                        <div class="flex-1">
                            <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Migration Date</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $migrationInfo['migrated_at'] }}</dd>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-4 bg-slate-50 rounded-lg border border-slate-200/60">
                        <x-base.lucide class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" icon="User" />
                        <div class="flex-1">
                            <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Migrated By</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $migrationInfo['migrated_by'] }}</dd>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-4 bg-slate-50 rounded-lg border border-slate-200/60">
                        <x-base.lucide class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" icon="CheckCircle" />
                        <div class="flex-1">
                            <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Status</dt>
                            <dd class="mt-1">
                                <x-base.badge variant="{{ $migrationInfo['status'] === 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst(str_replace('_', ' ', $migrationInfo['status'])) }}
                                </x-base.badge>
                            </dd>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-4 bg-slate-50 rounded-lg border border-slate-200/60">
                        <x-base.lucide class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" icon="FileText" />
                        <div class="flex-1">
                            <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Reason</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $migrationInfo['reason'] ?? 'Not specified' }}</dd>
                        </div>
                    </div>
                    
                    @if($migrationInfo['notes'])
                        <div class="md:col-span-2 flex items-start gap-3 p-4 bg-slate-50 rounded-lg border border-slate-200/60">
                            <x-base.lucide class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" icon="StickyNote" />
                            <div class="flex-1">
                                <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Notes</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ $migrationInfo['notes'] }}</dd>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Rollback Info -->
                @if($migrationInfo['rolled_back'])
                    <div class="bg-gradient-to-r from-warning/10 to-warning/5 border border-warning/20 rounded-lg p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <x-base.lucide class="w-5 h-5 text-warning" icon="RotateCcw" />
                            <h4 class="text-sm font-semibold text-warning">Rollback Information</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-xs font-medium text-warning uppercase tracking-wide">Rolled Back At</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $migrationInfo['rolled_back_at'] }}</dd>
                            </div>
                            @if($migrationInfo['rollback_reason'])
                                <div>
                                    <dt class="text-xs font-medium text-warning uppercase tracking-wide">Rollback Reason</dt>
                                    <dd class="mt-1 text-sm text-slate-700">{{ $migrationInfo['rollback_reason'] }}</dd>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @else
            @include('livewire.admin.driver.partials.empty-state', ['message' => 'No migration information available. This archive may have been created for a different reason.'])
        @endif
    </div>
</div>
