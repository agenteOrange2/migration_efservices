@php
    $accidents = $accidents ?? [];
    $convictions = $convictions ?? [];
@endphp

<div class="space-y-8">
    <!-- Accidents Section -->
    <div>
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Accident Records</h3>
        
        @if(!empty($accidents) && is_array($accidents))
            <div class="space-y-4">
                @foreach($accidents as $accident)
                    <div class="border border-slate-200 rounded-lg p-5 bg-slate-50/50">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">
                                    Accident - {{ isset($accident['accident_date']) ? \Carbon\Carbon::parse($accident['accident_date'])->format('M j, Y') : 'Date Unknown' }}
                                </h4>
                                @if(isset($accident['location']))
                                    <p class="text-sm text-slate-600 mt-1">Location: {{ $accident['location'] }}</p>
                                @endif
                            </div>
                            
                            @if(isset($accident['severity']))
                                @php
                                    $severityColors = [
                                        'minor' => 'bg-yellow-100 text-yellow-700',
                                        'moderate' => 'bg-orange-100 text-orange-700',
                                        'major' => 'bg-red-100 text-red-700',
                                        'fatal' => 'bg-red-200 text-red-900',
                                    ];
                                    $severityColor = $severityColors[strtolower($accident['severity'])] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 {{ $severityColor }} text-xs font-medium rounded-full">
                                    {{ ucfirst($accident['severity']) }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Accident Type -->
                            @if(isset($accident['accident_type']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Accident Type</label>
                                <div class="text-sm text-slate-900">
                                    {{ ucfirst(str_replace('_', ' ', $accident['accident_type'])) }}
                                </div>
                            </div>
                            @endif

                            <!-- Preventable -->
                            @if(isset($accident['preventable']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Preventable</label>
                                <div class="text-sm text-slate-900">
                                    @if($accident['preventable'])
                                        <span class="text-red-600">Yes</span>
                                    @else
                                        <span class="text-green-600">No</span>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Injuries -->
                            @if(isset($accident['injuries']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Injuries</label>
                                <div class="text-sm text-slate-900">
                                    {{ $accident['injuries'] ? 'Yes' : 'No' }}
                                    @if(isset($accident['injury_count']) && $accident['injury_count'] > 0)
                                        ({{ $accident['injury_count'] }})
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Fatalities -->
                            @if(isset($accident['fatalities']) && $accident['fatalities'])
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Fatalities</label>
                                <div class="text-sm text-red-600 font-medium">
                                    Yes
                                    @if(isset($accident['fatality_count']))
                                        ({{ $accident['fatality_count'] }})
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Hazmat Involved -->
                            @if(isset($accident['hazmat_involved']) && $accident['hazmat_involved'])
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Hazmat Involved</label>
                                <div class="text-sm text-orange-600 font-medium">
                                    Yes
                                </div>
                            </div>
                            @endif

                            <!-- Police Report Number -->
                            @if(isset($accident['police_report_number']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Police Report Number</label>
                                <div class="text-sm text-slate-900">
                                    {{ $accident['police_report_number'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Description -->
                            @if(isset($accident['description']) && !empty($accident['description']))
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Description</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $accident['description'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($accident['notes']) && !empty($accident['notes']))
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $accident['notes'] }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 border border-slate-200 rounded-lg">
                <x-base.lucide class="h-10 w-10 text-slate-300 mx-auto mb-2" icon="AlertTriangle" />
                <p class="text-slate-500 text-sm">No accident records available</p>
            </div>
        @endif
    </div>

    <!-- Traffic Violations Section -->
    <div class="border-t border-slate-200 pt-8">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Traffic Violations & Convictions</h3>
        
        @if(!empty($convictions) && is_array($convictions))
            <div class="space-y-4">
                @foreach($convictions as $conviction)
                    <div class="border border-slate-200 rounded-lg p-5 bg-slate-50/50">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">
                                    {{ $conviction['violation_type'] ?? 'Traffic Violation' }}
                                </h4>
                                @if(isset($conviction['violation_date']))
                                    <p class="text-sm text-slate-600 mt-1">
                                        Date: {{ \Carbon\Carbon::parse($conviction['violation_date'])->format('M j, Y') }}
                                    </p>
                                @endif
                            </div>
                            
                            @if(isset($conviction['severity']))
                                @php
                                    $severityColors = [
                                        'minor' => 'bg-yellow-100 text-yellow-700',
                                        'serious' => 'bg-orange-100 text-orange-700',
                                        'major' => 'bg-red-100 text-red-700',
                                    ];
                                    $severityColor = $severityColors[strtolower($conviction['severity'])] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 {{ $severityColor }} text-xs font-medium rounded-full">
                                    {{ ucfirst($conviction['severity']) }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Location -->
                            @if(isset($conviction['location']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Location</label>
                                <div class="text-sm text-slate-900">
                                    {{ $conviction['location'] }}
                                </div>
                            </div>
                            @endif

                            <!-- State -->
                            @if(isset($conviction['state']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">State</label>
                                <div class="text-sm text-slate-900">
                                    {{ $conviction['state'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Citation Number -->
                            @if(isset($conviction['citation_number']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Citation Number</label>
                                <div class="text-sm text-slate-900">
                                    {{ $conviction['citation_number'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Conviction Date -->
                            @if(isset($conviction['conviction_date']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Conviction Date</label>
                                <div class="text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($conviction['conviction_date'])->format('M j, Y') }}
                                </div>
                            </div>
                            @endif

                            <!-- Fine Amount -->
                            @if(isset($conviction['fine_amount']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Fine Amount</label>
                                <div class="text-sm text-slate-900">
                                    ${{ number_format($conviction['fine_amount'], 2) }}
                                </div>
                            </div>
                            @endif

                            <!-- Points -->
                            @if(isset($conviction['points']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Points</label>
                                <div class="text-sm text-slate-900">
                                    {{ $conviction['points'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Description -->
                            @if(isset($conviction['description']) && !empty($conviction['description']))
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Description</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $conviction['description'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($conviction['notes']) && !empty($conviction['notes']))
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $conviction['notes'] }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 border border-slate-200 rounded-lg">
                <x-base.lucide class="h-10 w-10 text-slate-300 mx-auto mb-2" icon="FileWarning" />
                <p class="text-slate-500 text-sm">No traffic violation records available</p>
            </div>
        @endif
    </div>
</div>
