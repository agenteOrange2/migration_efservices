@php
    $data = $data ?? [];
@endphp

<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Medical Qualifications</h3>
        
        @if(!empty($data) && is_array($data))
            <div class="space-y-4">
                @foreach($data as $medical)
                    <div class="border border-slate-200 rounded-lg p-5 bg-slate-50/50">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">
                                    {{ $medical['exam_type'] ?? 'DOT Medical Examination' }}
                                </h4>
                                @if(isset($medical['examiner_name']))
                                    <p class="text-sm text-slate-600 mt-1">Examiner: {{ $medical['examiner_name'] }}</p>
                                @endif
                            </div>
                            
                            @if(isset($medical['status']))
                                @php
                                    $statusColors = [
                                        'qualified' => 'bg-green-100 text-green-700',
                                        'certified' => 'bg-green-100 text-green-700',
                                        'expired' => 'bg-red-100 text-red-700',
                                        'disqualified' => 'bg-red-100 text-red-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                    ];
                                    $statusColor = $statusColors[strtolower($medical['status'])] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 {{ $statusColor }} text-xs font-medium rounded-full">
                                    {{ ucfirst($medical['status']) }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Exam Date -->
                            @if(isset($medical['exam_date']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Exam Date</label>
                                <div class="text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($medical['exam_date'])->format('M j, Y') }}
                                </div>
                            </div>
                            @endif

                            <!-- Expiration Date -->
                            @if(isset($medical['expiration_date']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Expiration Date</label>
                                <div class="text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($medical['expiration_date'])->format('M j, Y') }}
                                    @php
                                        $expirationDate = \Carbon\Carbon::parse($medical['expiration_date']);
                                        $isExpired = $expirationDate->isPast();
                                    @endphp
                                    @if($isExpired)
                                        <span class="text-red-600 text-xs ml-1">(Expired)</span>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Certificate Number -->
                            @if(isset($medical['certificate_number']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Certificate Number</label>
                                <div class="text-sm text-slate-900">
                                    {{ $medical['certificate_number'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Medical Examiner License -->
                            @if(isset($medical['examiner_license']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Examiner License</label>
                                <div class="text-sm text-slate-900">
                                    {{ $medical['examiner_license'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Certification Type -->
                            @if(isset($medical['certification_type']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Certification Type</label>
                                <div class="text-sm text-slate-900">
                                    {{ $medical['certification_type'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Medical Variance -->
                            @if(isset($medical['has_variance']) && $medical['has_variance'])
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Medical Variance</label>
                                <div class="text-sm text-slate-900">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">
                                        <x-base.lucide class="h-3 w-3" icon="AlertCircle" />
                                        Variance Granted
                                    </span>
                                </div>
                            </div>
                            @endif

                            <!-- Restrictions -->
                            @if(isset($medical['restrictions']) && !empty($medical['restrictions']))
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Medical Restrictions</label>
                                <div class="text-sm text-slate-700 bg-amber-50 p-3 rounded border border-amber-200">
                                    @if(is_array($medical['restrictions']))
                                        <ul class="list-disc list-inside space-y-1">
                                            @foreach($medical['restrictions'] as $restriction)
                                                <li>{{ $restriction }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        {{ $medical['restrictions'] }}
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($medical['notes']) && !empty($medical['notes']))
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $medical['notes'] }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 border border-slate-200 rounded-lg">
                <x-base.lucide class="h-12 w-12 text-slate-300 mx-auto mb-3" icon="Heart" />
                <p class="text-slate-500">No medical qualification information available</p>
            </div>
        @endif
    </div>
</div>
