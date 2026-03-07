@php
    $data = $data ?? [];
@endphp

<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Vehicle Assignment History</h3>
        
        @if(!empty($data) && is_array($data))
            <div class="space-y-4">
                @foreach($data as $assignment)
                    <div class="border border-slate-200 rounded-lg p-5 bg-slate-50/50">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">
                                    {{ $assignment['vehicle_number'] ?? $assignment['vehicle_id'] ?? 'Vehicle' }}
                                </h4>
                                @if(isset($assignment['vehicle_make']) || isset($assignment['vehicle_model']))
                                    <p class="text-sm text-slate-600 mt-1">
                                        {{ $assignment['vehicle_make'] ?? '' }} {{ $assignment['vehicle_model'] ?? '' }}
                                        @if(isset($assignment['vehicle_year']))
                                            ({{ $assignment['vehicle_year'] }})
                                        @endif
                                    </p>
                                @endif
                            </div>
                            
                            @if(isset($assignment['status']))
                                @php
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-700',
                                        'completed' => 'bg-slate-100 text-slate-700',
                                        'terminated' => 'bg-red-100 text-red-700',
                                    ];
                                    $statusColor = $statusColors[strtolower($assignment['status'])] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 {{ $statusColor }} text-xs font-medium rounded-full">
                                    {{ ucfirst($assignment['status']) }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Assignment Period -->
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Assignment Period</label>
                                <div class="text-sm text-slate-900">
                                    @if(isset($assignment['start_date']))
                                        {{ \Carbon\Carbon::parse($assignment['start_date'])->format('M j, Y') }}
                                    @else
                                        N/A
                                    @endif
                                    -
                                    @if(isset($assignment['end_date']))
                                        {{ \Carbon\Carbon::parse($assignment['end_date'])->format('M j, Y') }}
                                    @else
                                        Present
                                    @endif
                                </div>
                            </div>

                            <!-- VIN -->
                            @if(isset($assignment['vin']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">VIN</label>
                                <div class="text-sm text-slate-900 font-mono">
                                    {{ $assignment['vin'] }}
                                </div>
                            </div>
                            @endif

                            <!-- License Plate -->
                            @if(isset($assignment['license_plate']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">License Plate</label>
                                <div class="text-sm text-slate-900">
                                    {{ $assignment['license_plate'] }}
                                    @if(isset($assignment['plate_state']))
                                        ({{ $assignment['plate_state'] }})
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Vehicle Type -->
                            @if(isset($assignment['vehicle_type']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Vehicle Type</label>
                                <div class="text-sm text-slate-900">
                                    {{ ucfirst(str_replace('_', ' ', $assignment['vehicle_type'])) }}
                                </div>
                            </div>
                            @endif

                            <!-- Odometer Start -->
                            @if(isset($assignment['odometer_start']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Starting Odometer</label>
                                <div class="text-sm text-slate-900">
                                    {{ number_format($assignment['odometer_start']) }} miles
                                </div>
                            </div>
                            @endif

                            <!-- Odometer End -->
                            @if(isset($assignment['odometer_end']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Ending Odometer</label>
                                <div class="text-sm text-slate-900">
                                    {{ number_format($assignment['odometer_end']) }} miles
                                </div>
                            </div>
                            @endif

                            <!-- Total Miles -->
                            @if(isset($assignment['odometer_start']) && isset($assignment['odometer_end']))
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Total Miles Driven</label>
                                <div class="text-sm text-slate-900 font-medium">
                                    {{ number_format($assignment['odometer_end'] - $assignment['odometer_start']) }} miles
                                </div>
                            </div>
                            @endif

                            <!-- Primary Driver -->
                            @if(isset($assignment['is_primary']) && $assignment['is_primary'])
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Assignment Type</label>
                                <div class="text-sm text-slate-900">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">
                                        <x-base.lucide class="h-3 w-3" icon="Star" />
                                        Primary Driver
                                    </span>
                                </div>
                            </div>
                            @endif

                            <!-- Reason for End -->
                            @if(isset($assignment['end_reason']) && !empty($assignment['end_reason']))
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Reason for Assignment End</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $assignment['end_reason'] }}
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($assignment['notes']) && !empty($assignment['notes']))
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
                                <div class="text-sm text-slate-700 bg-white p-3 rounded border border-slate-200">
                                    {{ $assignment['notes'] }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 border border-slate-200 rounded-lg">
                <x-base.lucide class="h-12 w-12 text-slate-300 mx-auto mb-3" icon="Truck" />
                <p class="text-slate-500">No vehicle assignment history available</p>
            </div>
        @endif
    </div>
</div>
