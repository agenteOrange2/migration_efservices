@php
    $data = $data ?? [];
@endphp

<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-slate-800 mb-4">DOT Inspection Records</h3>
        
        @if(!empty($data) && is_array($data))
            <div class="space-y-4">
                @foreach($data as $inspection)
                    <div class="border border-slate-200 rounded-lg p-5 bg-slate-50/50">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">
                                    {{ $inspection['inspection_level'] ?? 'DOT Inspection' }}
                                    @if(isset($inspection['inspection_number']))
                                        - #{{ $inspection['inspection_number'] }}
                                    @endif
                                </h4>
                                @if(isset($inspection['inspection_date']))
                                    <p class="text-sm text-slate-600 mt-1">
                                        Date: {{ \Carbon\Carbon::parse($inspection['inspection_date'])->format('M j, Y') }}
                                    </p>
                                @endif
                            </div>
                            
                            @if(isset($inspection['result']))
                                @php
                                    $resultColors = [
                                        'pass' => 'bg-green-100 text-green-700',
                                        'passed' => 'bg-green-100 text-green-700',
                                        'fail' => 'bg-red-100 text-red-700',
                                        'failed' => 'bg-red-100 text-red-700',
                                        'warning' => 'bg-yellow-100 text-yellow-700',
                                    ];
                                    $resultColor = $resultColors[strtolower($inspection['result'])] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 {{ $resultColor }} text-xs font-medium rounded-full">
                                    {{ ucfirst($inspection['result']) }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Inspection Type -->
                            @if(isset($inspection['inspection_type']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Inspection Type</label>
                                <div class="text-sm text-slate-900">
                                    {{ ucfirst(str_replace('_', ' ', $inspection['inspection_type'])) }}
                                </div>
                            </div>
                            @endif

                            <!-- Location -->
                            @if(isset($inspection['location']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Location</label>
                                <div class="text-sm text-slate-900">
                                    {{ $inspection['location'] }}
                                </div>
                            </div>
                            @endif

                            <!-- State -->
                            @if(isset($inspection['state']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">State</label>
                                <div class="text-sm text-slate-900">
                                    {{ $inspection['state'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Inspector Name -->
                            @if(isset($inspection['inspector_name']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Inspector</label>
                                <div class="text-sm text-slate-900">
                                    {{ $inspection['inspector_name'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Inspector Badge -->
                            @if(isset($inspection['inspector_badge']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Inspector Badge</label>
                                <div class="text-sm text-slate-900">
                                    {{ $inspection['inspector_badge'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Vehicle Unit -->
                            @if(isset($inspection['vehicle_unit']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Vehicle Unit</label>
                                <div class="text-sm text-slate-900">
                                    {{ $inspection['vehicle_unit'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Out of Service -->
                            @if(isset($inspection['out_of_service']) && $inspection['out_of_service'])
                            <div class="md:col-span-3">
                                <div class="bg-red-50 border border-red-200 rounded p-3">
                                    <div class="flex items-start gap-2">
                                        <x-base.lucide class="h-4 w-4 text-red-600 mt-0.5" icon="XCircle" />
                                        <div>
                                            <p class="text-sm font-medium text-red-900">Out of Service Order Issued</p>
                                            @if(isset($inspection['oos_reason']))
                                                <p class="text-sm text-red-700 mt-1">{{ $inspection['oos_reason'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Violations -->
                            @if(isset($inspection['violations']) && !empty($inspection['violations']))
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-slate-500 mb-2">Violations</label>
                                <div class="space-y-2">
                                    @if(is_array($inspection['violations']))
                                        @foreach($inspection['violations'] as $violation)
                                            <div class="bg-amber-50 border border-amber-200 rounded p-3">
                                                <div class="flex items-start gap-2">
                                                    <x-base.lucide class="h-4 w-4 text-amber-600 mt-0.5 flex-shrink-0" icon="AlertTriangle" />
                                                    <div class="flex-1">
                                                        @if(is_array($violation))
                                                            <p class="text-sm font-medium text-amber-900">
                                                                {{ $violation['code'] ?? 'Violation' }}
                                                            </p>
                                                            @if(isset($violation['description']))
                                                                <p class="text-sm text-amber-700 mt-1">{{ $violation['description'] }}</p>
                                                            @endif
                                                            @if(isset($violation['severity']))
                                                                <span class="inline-block mt-1 px-2 py-0.5 bg-amber-100 text-amber-800 text-xs rounded">
                                                                    {{ ucfirst($violation['severity']) }}
                                                                </span>
                                                            @endif
                                                        @else
                                                            <p class="text-sm text-amber-900">{{ $violation }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                            {{ $inspection['violations'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($inspection['notes']) && !empty($inspection['notes']))
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $inspection['notes'] }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 border border-slate-200 rounded-lg">
                <x-base.lucide class="h-12 w-12 text-slate-300 mx-auto mb-3" icon="ClipboardCheck" />
                <p class="text-slate-500">No inspection records available</p>
            </div>
        @endif
    </div>
</div>
