@php
    $data = $data ?? [];
@endphp

<div class="space-y-6">
    <div>
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="Clock" />
            <h3 class="text-lg font-semibold text-slate-800">Hours of Service Summary</h3>
        </div>
        
        @if($data && ($data['entries_count'] ?? 0) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg p-5 border border-slate-200/60">
                    <div class="flex items-center gap-3 mb-2">
                        <x-base.lucide class="w-5 h-5 text-slate-400" icon="FileText" />
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Entries</dt>
                    </div>
                    <dd class="text-2xl font-bold text-slate-900">{{ $data['entries_count'] ?? 0 }}</dd>
                </div>
                
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-5 border border-red-200/60">
                    <div class="flex items-center gap-3 mb-2">
                        <x-base.lucide class="w-5 h-5 text-red-400" icon="AlertCircle" />
                        <dt class="text-xs font-medium text-red-600 uppercase tracking-wide">Violations</dt>
                    </div>
                    <dd class="text-2xl font-bold {{ ($data['violations_count'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' }}">
                        {{ $data['violations_count'] ?? 0 }}
                    </dd>
                </div>
                
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-lg p-5 border border-slate-200/60">
                    <div class="flex items-center gap-3 mb-2">
                        <x-base.lucide class="w-5 h-5 text-slate-400" icon="Calendar" />
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Last Entry</dt>
                    </div>
                    <dd class="text-sm font-semibold text-slate-900">{{ $data['last_entry_date'] ?? 'N/A' }}</dd>
                </div>
                
                @if(isset($data['total_drive_hours']))
                <div class="bg-gradient-to-br from-primary/10 to-primary/5 rounded-lg p-5 border border-primary/20">
                    <div class="flex items-center gap-3 mb-2">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Clock" />
                        <dt class="text-xs font-medium text-primary uppercase tracking-wide">Total Drive Time</dt>
                    </div>
                    <dd class="text-2xl font-bold text-primary">{{ $data['total_drive_hours'] ?? 'N/A' }} <span class="text-sm font-normal text-slate-600">hrs</span></dd>
                </div>
                @endif
            </div>
        @else
            @include('livewire.admin.driver.partials.empty-state', ['message' => 'No HOS records available.'])
        @endif
    </div>
</div>
