@extends('../themes/' . $activeTheme)
@section('title', 'My Licenses - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Licenses', 'active' => true],
    ];
@endphp

@section('subcontent')

<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="UserCheck" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">My Licenses</h1>
                    <p class="text-slate-600">View your driver licenses and endorsements</p>
                </div>
            </div>
        </div>
    </div>

@if(session('success'))
<div class="box p-4 mb-6 border-l-4 border-success bg-success/10">
    <div class="flex items-center gap-3">
        <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
        <p class="text-success font-medium">{{ session('success') }}</p>
    </div>
</div>
@endif

@if($licenses->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @foreach($licenses as $license)
    @php
        $isExpired = $license->expiration_date && $license->expiration_date->isPast();
        $daysLeft = $license->expiration_date ? (int) now()->diffInDays($license->expiration_date, false) : 0;
        $isExpiringSoon = !$isExpired && $license->expiration_date && $daysLeft <= 30 && $daysLeft > 0;
    @endphp
    <div class="box box--stacked p-6 {{ $isExpired ? 'border-l-4 border-danger' : ($isExpiringSoon ? 'border-l-4 border-warning' : '') }}">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-primary/10 rounded-xl">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="CreditCard" />
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">{{ $license->license_number }}</h3>
                    <p class="text-sm text-slate-500">{{ $license->state_of_issue }} - Class {{ $license->license_class }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($license->is_primary)
                    <x-base.badge variant="primary">Primary</x-base.badge>
                @endif
                @if($license->is_cdl)
                    <x-base.badge variant="info">CDL</x-base.badge>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-xs text-slate-500 mb-1">Expiration Date</p>
                <p class="font-medium {{ $isExpired ? 'text-danger' : ($isExpiringSoon ? 'text-warning' : 'text-slate-700') }}">
                    {{ $license->expiration_date ? $license->expiration_date->format('M d, Y') : 'N/A' }}
                    @if($isExpired)
                        <span class="text-xs">(Expired)</span>
                    @elseif($isExpiringSoon)
                        <span class="text-xs">({{ $daysLeft }} days left)</span>
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs text-slate-500 mb-1">Status</p>
                @if($isExpired)
                    <x-base.badge variant="danger">Expired</x-base.badge>
                @elseif($isExpiringSoon)
                    <x-base.badge variant="warning">Expiring Soon</x-base.badge>
                @else
                    <x-base.badge variant="success">Active</x-base.badge>
                @endif
            </div>
        </div>

        @if($license->endorsements->count() > 0)
        <div class="mb-4">
            <p class="text-xs text-slate-500 mb-2">Endorsements</p>
            <div class="flex flex-wrap gap-1">
                @foreach($license->endorsements as $endorsement)
                    <x-base.badge variant="secondary" class="text-xs">{{ $endorsement->code ?? $endorsement->name }}</x-base.badge>
                @endforeach
            </div>
        </div>
        @endif

        @if($license->restrictions)
        <div class="mb-4">
            <p class="text-xs text-slate-500 mb-1">Restrictions</p>
            <p class="text-sm text-slate-700">{{ $license->restrictions }}</p>
        </div>
        @endif

        <!-- License Images -->
        <div class="flex gap-2 mb-4">
            @if($license->getFirstMediaUrl('license_front'))
                <a href="{{ $license->getFirstMediaUrl('license_front') }}" target="_blank" 
                   class="flex items-center gap-1 text-xs text-primary hover:underline">
                    <x-base.lucide class="w-3 h-3" icon="Image" />
                    Front
                </a>
            @endif
            @if($license->getFirstMediaUrl('license_back'))
                <a href="{{ $license->getFirstMediaUrl('license_back') }}" target="_blank"
                   class="flex items-center gap-1 text-xs text-primary hover:underline">
                    <x-base.lucide class="w-3 h-3" icon="Image" />
                    Back
                </a>
            @endif
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-4 border-t border-slate-100">
            <a href="{{ route('driver.licenses.show', $license) }}" 
               class="flex-1 text-center py-2 text-sm text-slate-600 hover:text-primary hover:bg-slate-50 rounded-lg transition-colors">
                View Details
            </a>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="box box--stacked p-12 text-center">
    <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="CreditCard" />
    <h3 class="text-lg font-semibold text-slate-700 mb-2">No Licenses Found</h3>
    <p class="text-slate-500 mb-4">You don't have any driver licenses on file yet.</p>
    <div class="flex items-center justify-center gap-2 text-sm text-slate-500 bg-slate-50 rounded-lg p-4 max-w-md mx-auto">
        <x-base.lucide class="w-4 h-4 text-info" icon="Info" />
        <span>Your license information will be added by your carrier administrator.</span>
    </div>
</div>
@endif

@endsection
