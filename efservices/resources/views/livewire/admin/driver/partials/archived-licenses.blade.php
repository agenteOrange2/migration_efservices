@php
    $data = $data ?? [];
@endphp

<div class="space-y-6">
    <div>
        <div class="flex items-center gap-3 mb-6">
            <x-base.lucide class="w-5 h-5 text-primary" icon="CreditCard" />
            <h3 class="text-lg font-semibold text-slate-800">Driver Licenses</h3>
        </div>
        
        @if(!empty($data) && is_array($data))
            <div class="space-y-4">
                @foreach($data as $license)
                    <div class="box box--stacked p-6">
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-primary/10 rounded-lg">
                                    <x-base.lucide class="w-5 h-5 text-primary" icon="CreditCard" />
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-slate-800">
                                        @php
                                            $licenseType = $license['license_type'] 
                                                ?? (isset($license['is_cdl']) && $license['is_cdl'] ? 'CDL' : 'License');
                                        @endphp
                                        {{ $licenseType }}
                                        @if(isset($license['class']) || isset($license['license_class']))
                                            <span class="text-slate-500 font-normal">- Class {{ $license['class'] ?? $license['license_class'] }}</span>
                                        @endif
                                    </h4>
                                    @if(isset($license['license_number']))
                                        <p class="text-sm text-slate-600 mt-1">License #: <strong>{{ $license['license_number'] }}</strong></p>
                                    @endif
                                    @if(isset($license['license_number']) && $license['license_number'])
                                        <p class="text-xs text-slate-500">Current #: {{ $license['license_number'] }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            @if(isset($license['status']))
                                @php
                                    $statusVariant = match(strtolower($license['status'])) {
                                        'active' => 'success',
                                        'expired' => 'danger',
                                        'suspended' => 'warning',
                                        'revoked' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <x-base.badge variant="{{ $statusVariant }}" class="gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-{{ $statusVariant === 'success' ? 'success' : ($statusVariant === 'danger' ? 'danger' : ($statusVariant === 'warning' ? 'warning' : 'slate-400')) }} rounded-full"></span>
                                    {{ ucfirst($license['status']) }}
                                </x-base.badge>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Issue Date -->
                            @if(isset($license['issue_date']))
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Issue Date</label>
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ \Carbon\Carbon::parse($license['issue_date'])->format('M d, Y') }}
                                    </p>
                                </div>
                            </div>
                            @endif

                            <!-- Expiration Date -->
                            @if(isset($license['expiration_date']))
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Expiration Date</label>
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ \Carbon\Carbon::parse($license['expiration_date'])->format('M d, Y') }}
                                        </p>
                                        @php
                                            $expirationDate = \Carbon\Carbon::parse($license['expiration_date']);
                                            $isExpired = $expirationDate->isPast();
                                        @endphp
                                        @if($isExpired)
                                            <x-base.badge variant="danger" class="text-xs mt-1">Expired</x-base.badge>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- State -->
                            @if(isset($license['state']))
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">State</label>
                                <p class="text-sm font-semibold text-slate-800">{{ $license['state'] }}</p>
                            </div>
                            @endif

                            <!-- Endorsements -->
                            @if(isset($license['endorsements']) && !empty($license['endorsements']))
                            <div class="md:col-span-3 bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Endorsements</label>
                                <div class="flex flex-wrap gap-2">
                                    @if(is_array($license['endorsements']))
                                        @foreach($license['endorsements'] as $endorsement)
                                            <x-base.badge variant="primary" class="gap-1.5">
                                                {{ $endorsement }}
                                            </x-base.badge>
                                        @endforeach
                                    @else
                                        <p class="text-sm font-semibold text-slate-800">{{ $license['endorsements'] }}</p>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Restrictions -->
                            @if(isset($license['restrictions']) && !empty($license['restrictions']))
                            <div class="md:col-span-3 bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Restrictions</label>
                                <div class="flex flex-wrap gap-2">
                                    @if(is_array($license['restrictions']))
                                        @foreach($license['restrictions'] as $restriction)
                                            <x-base.badge variant="warning" class="gap-1.5">
                                                {{ $restriction }}
                                            </x-base.badge>
                                        @endforeach
                                    @else
                                        <p class="text-sm font-semibold text-slate-800">{{ $license['restrictions'] }}</p>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Notes -->
                            @if(isset($license['notes']) && !empty($license['notes']))
                            <div class="md:col-span-3 bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Notes</label>
                                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $license['notes'] }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="CreditCard" />
                <h3 class="text-lg font-semibold text-slate-800 mb-2">No License Information Available</h3>
                <p class="text-slate-500 text-sm">This archived record does not contain license information.</p>
            </div>
        @endif
    </div>
</div>
