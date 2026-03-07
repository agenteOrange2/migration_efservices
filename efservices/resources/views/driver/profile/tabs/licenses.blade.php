{{-- Licenses Tab Content --}}
<div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-800 mb-4">Driver Licenses</h3>
    
    @if($driver->licenses && $driver->licenses->count() > 0)
        <div class="space-y-4">
            @foreach($driver->licenses as $license)
                @php
                    $isExpired = $license->expiration_date && $license->expiration_date->isPast();
                    $isExpiringSoon = $license->expiration_date && !$isExpired && $license->expiration_date->diffInDays(now()) <= 30;
                    $borderClass = $isExpired ? 'border-l-4 border-danger' : ($isExpiringSoon ? 'border-l-4 border-warning' : '');
                @endphp
                <div class="bg-slate-50/50 rounded-lg p-5 border border-slate-100 {{ $borderClass }}">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="CreditCard" />
                                <h4 class="font-semibold text-slate-800">{{ $license->license_number ?? 'N/A' }}</h4>
                                @if($license->is_primary)
                                    <x-base.badge variant="primary">Primary</x-base.badge>
                                @endif
                                @if($isExpired)
                                    <x-base.badge variant="danger">Expired</x-base.badge>
                                @elseif($isExpiringSoon)
                                    <x-base.badge variant="warning">Expiring Soon</x-base.badge>
                                @else
                                    <x-base.badge variant="success">Valid</x-base.badge>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">State</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $license->state_of_issue ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Class</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $license->license_class ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">Expiration</label>
                                    <p class="text-sm font-semibold {{ $isExpired ? 'text-danger' : ($isExpiringSoon ? 'text-warning' : 'text-slate-800') }}">
                                        {{ $license->expiration_date ? $license->expiration_date->format('M d, Y') : 'N/A' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-500 uppercase">CDL</label>
                                    <p class="text-sm font-semibold text-slate-800">{{ $license->is_cdl ? 'Yes' : 'No' }}</p>
                                </div>
                            </div>
                            @if($license->endorsements && $license->endorsements->count() > 0)
                                <div class="mt-3">
                                    <label class="text-xs font-medium text-slate-500 uppercase">Endorsements</label>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @foreach($license->endorsements as $endorsement)
                                            <x-base.badge variant="secondary">{{ $endorsement->code ?? $endorsement->name }}</x-base.badge>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            @if($license->getFirstMediaUrl('license_front'))
                                <a href="{{ $license->getFirstMediaUrl('license_front') }}" target="_blank" class="text-sm text-primary hover:underline">
                                    <x-base.lucide class="w-4 h-4 inline" icon="Eye" /> Front
                                </a>
                            @endif
                            @if($license->getFirstMediaUrl('license_back'))
                                <a href="{{ $license->getFirstMediaUrl('license_back') }}" target="_blank" class="text-sm text-primary hover:underline">
                                    <x-base.lucide class="w-4 h-4 inline" icon="Eye" /> Back
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="CreditCard" />
            <h4 class="text-lg font-semibold text-slate-700 mb-2">No Licenses Found</h4>
            <p class="text-slate-500">You don't have any licenses on file.</p>
        </div>
    @endif
</div>
