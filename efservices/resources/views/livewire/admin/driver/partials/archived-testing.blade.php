@php
    $data = $data ?? [];
@endphp

<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Drug & Alcohol Testing Records</h3>
        
        @if(!empty($data) && is_array($data))
            <div class="space-y-4">
                @foreach($data as $test)
                    <div class="border border-slate-200 rounded-lg p-5 bg-slate-50/50">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">
                                    {{ $test['test_type'] ?? 'Drug/Alcohol Test' }}
                                </h4>
                                @if(isset($test['test_reason']))
                                    <p class="text-sm text-slate-600 mt-1">Reason: {{ ucfirst(str_replace('_', ' ', $test['test_reason'])) }}</p>
                                @endif
                            </div>
                            
                            @if(isset($test['result']))
                                @php
                                    $resultColors = [
                                        'negative' => 'bg-green-100 text-green-700',
                                        'positive' => 'bg-red-100 text-red-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'refused' => 'bg-red-100 text-red-700',
                                        'cancelled' => 'bg-slate-100 text-slate-700',
                                    ];
                                    $resultColor = $resultColors[strtolower($test['result'])] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 {{ $resultColor }} text-xs font-medium rounded-full">
                                    {{ ucfirst($test['result']) }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Test Date -->
                            @if(isset($test['test_date']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Test Date</label>
                                <div class="text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($test['test_date'])->format('M j, Y') }}
                                </div>
                            </div>
                            @endif

                            <!-- Collection Site -->
                            @if(isset($test['collection_site']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Collection Site</label>
                                <div class="text-sm text-slate-900">
                                    {{ $test['collection_site'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Specimen ID -->
                            @if(isset($test['specimen_id']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Specimen ID</label>
                                <div class="text-sm text-slate-900">
                                    {{ $test['specimen_id'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Laboratory -->
                            @if(isset($test['laboratory']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Laboratory</label>
                                <div class="text-sm text-slate-900">
                                    {{ $test['laboratory'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Medical Review Officer -->
                            @if(isset($test['mro_name']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Medical Review Officer</label>
                                <div class="text-sm text-slate-900">
                                    {{ $test['mro_name'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Result Date -->
                            @if(isset($test['result_date']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Result Date</label>
                                <div class="text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($test['result_date'])->format('M j, Y') }}
                                </div>
                            </div>
                            @endif

                            <!-- Substances Tested -->
                            @if(isset($test['substances_tested']) && !empty($test['substances_tested']))
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Substances Tested</label>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    @if(is_array($test['substances_tested']))
                                        @foreach($test['substances_tested'] as $substance)
                                            <span class="inline-flex items-center px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">
                                                {{ $substance }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-sm text-slate-900">{{ $test['substances_tested'] }}</span>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Follow-up Required -->
                            @if(isset($test['follow_up_required']) && $test['follow_up_required'])
                            <div class="md:col-span-3">
                                <div class="bg-amber-50 border border-amber-200 rounded p-3">
                                    <div class="flex items-start gap-2">
                                        <x-base.lucide class="h-4 w-4 text-amber-600 mt-0.5" icon="AlertCircle" />
                                        <div>
                                            <p class="text-sm font-medium text-amber-900">Follow-up Required</p>
                                            @if(isset($test['follow_up_notes']))
                                                <p class="text-sm text-amber-700 mt-1">{{ $test['follow_up_notes'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($test['notes']) && !empty($test['notes']))
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $test['notes'] }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 border border-slate-200 rounded-lg">
                <x-base.lucide class="h-12 w-12 text-slate-300 mx-auto mb-3" icon="TestTube" />
                <p class="text-slate-500">No drug/alcohol testing records available</p>
            </div>
        @endif
    </div>
</div>
