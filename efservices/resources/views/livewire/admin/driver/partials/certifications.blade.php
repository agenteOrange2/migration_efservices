@php
    $certifications = $certifications ?? [];
    $training = $training ?? [];
@endphp

<div class="space-y-8">
    <!-- Certifications Section -->
    <div>
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Certifications</h3>
        
        @if(!empty($certifications) && is_array($certifications))
            <div class="space-y-4">
                @foreach($certifications as $cert)
                    <div class="border border-slate-200 rounded-lg p-5 bg-slate-50/50">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">
                                    {{ $cert['certification_name'] ?? $cert['name'] ?? 'Certification' }}
                                </h4>
                                @if(isset($cert['issuing_organization']))
                                    <p class="text-sm text-slate-600 mt-1">Issued by: {{ $cert['issuing_organization'] }}</p>
                                @endif
                            </div>
                            
                            @if(isset($cert['status']))
                                @php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-700',
                                        'valid' => 'bg-green-100 text-green-700',
                                        'expired' => 'bg-red-100 text-red-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                    ];
                                    $statusColor = $statusColors[strtolower($cert['status'])] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 {{ $statusColor }} text-xs font-medium rounded-full">
                                    {{ ucfirst($cert['status']) }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Issue Date -->
                            @if(isset($cert['issue_date']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Issue Date</label>
                                <div class="text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($cert['issue_date'])->format('M j, Y') }}
                                </div>
                            </div>
                            @endif

                            <!-- Expiration Date -->
                            @if(isset($cert['expiration_date']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Expiration Date</label>
                                <div class="text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($cert['expiration_date'])->format('M j, Y') }}
                                    @php
                                        $expirationDate = \Carbon\Carbon::parse($cert['expiration_date']);
                                        $isExpired = $expirationDate->isPast();
                                    @endphp
                                    @if($isExpired)
                                        <span class="text-red-600 text-xs ml-1">(Expired)</span>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Certificate Number -->
                            @if(isset($cert['certificate_number']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Certificate Number</label>
                                <div class="text-sm text-slate-900">
                                    {{ $cert['certificate_number'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($cert['notes']) && !empty($cert['notes']))
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $cert['notes'] }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 border border-slate-200 rounded-lg">
                <x-base.lucide class="h-10 w-10 text-slate-300 mx-auto mb-2" icon="Award" />
                <p class="text-slate-500 text-sm">No certifications available</p>
            </div>
        @endif
    </div>

    <!-- Training Section -->
    <div class="border-t border-slate-200 pt-8">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Training Records</h3>
        
        @if(!empty($training) && is_array($training))
            <div class="space-y-4">
                @foreach($training as $course)
                    <div class="border border-slate-200 rounded-lg p-5 bg-slate-50/50">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">
                                    {{ $course['course_name'] ?? $course['name'] ?? 'Training Course' }}
                                </h4>
                                @if(isset($course['provider']))
                                    <p class="text-sm text-slate-600 mt-1">Provider: {{ $course['provider'] }}</p>
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
                            <!-- Start Date -->
                            @if(isset($course['start_date']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Start Date</label>
                                <div class="text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($course['start_date'])->format('M j, Y') }}
                                </div>
                            </div>
                            @endif

                            <!-- Completion Date -->
                            @if(isset($course['completion_date']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Completion Date</label>
                                <div class="text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($course['completion_date'])->format('M j, Y') }}
                                </div>
                            </div>
                            @endif

                            <!-- Duration -->
                            @if(isset($course['duration_hours']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Duration</label>
                                <div class="text-sm text-slate-900">
                                    {{ $course['duration_hours'] }} hours
                                </div>
                            </div>
                            @endif

                            <!-- Score -->
                            @if(isset($course['score']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Score</label>
                                <div class="text-sm text-slate-900">
                                    {{ $course['score'] }}%
                                </div>
                            </div>
                            @endif

                            <!-- Instructor -->
                            @if(isset($course['instructor']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Instructor</label>
                                <div class="text-sm text-slate-900">
                                    {{ $course['instructor'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Certificate Number -->
                            @if(isset($course['certificate_number']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Certificate Number</label>
                                <div class="text-sm text-slate-900">
                                    {{ $course['certificate_number'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Description -->
                            @if(isset($course['description']) && !empty($course['description']))
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Description</label>
                                <div class="text-sm text-slate-700">
                                    {{ $course['description'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($course['notes']) && !empty($course['notes']))
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $course['notes'] }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 border border-slate-200 rounded-lg">
                <x-base.lucide class="h-10 w-10 text-slate-300 mx-auto mb-2" icon="GraduationCap" />
                <p class="text-slate-500 text-sm">No training records available</p>
            </div>
        @endif
    </div>
</div>
