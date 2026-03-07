@php
    $data = $data ?? [];
@endphp

<div class="space-y-6">
    <div>
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="GraduationCap" />
            <h3 class="text-lg font-semibold text-slate-800">Training Records</h3>
        </div>
        
        @if(!empty($data) && is_array($data))
            <div class="space-y-4">
                @foreach($data as $course)
                    <div class="box box--stacked p-6">
                        <div class="flex items-start justify-between mb-6">
                            <div>
                                <h4 class="text-lg font-semibold text-slate-800">
                                    {{ $course['name'] ?? $course['course_name'] ?? 'Training Course' }}
                                </h4>
                                @if(isset($course['provider']) || isset($course['type']))
                                    <p class="text-sm text-slate-600 mt-1">
                                        {{ ucfirst($course['type'] ?? 'training') }}
                                        @if(isset($course['provider']))
                                            - {{ $course['provider'] }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                            
                            @if(isset($course['status']))
                                @php
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-700',
                                        'in_progress' => 'bg-blue-100 text-blue-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'failed' => 'bg-red-100 text-red-700',
                                    ];
                                    $statusColor = $statusColors[strtolower($course['status'])] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 {{ $statusColor }} text-xs font-medium rounded-full">
                                    {{ ucfirst(str_replace('_', ' ', $course['status'])) }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @if(isset($course['start_date']) || isset($course['assigned_date']))
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Start Date</label>
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ isset($course['start_date']) ? \Carbon\Carbon::parse($course['start_date'])->format('M j, Y') : (\Carbon\Carbon::parse($course['assigned_date'])->format('M j, Y') ?? 'N/A') }}
                                </p>
                            </div>
                            @endif

                            @if(isset($course['completion_date']))
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Completion Date</label>
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ \Carbon\Carbon::parse($course['completion_date'])->format('M j, Y') }}
                                </p>
                            </div>
                            @endif

                            @if(isset($course['due_date']))
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Due Date</label>
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ \Carbon\Carbon::parse($course['due_date'])->format('M j, Y') }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            @include('livewire.admin.driver.partials.empty-state', ['message' => 'No training records available.'])
        @endif
    </div>
</div>
