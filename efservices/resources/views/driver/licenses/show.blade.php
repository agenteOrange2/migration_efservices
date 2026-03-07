@extends('../themes/' . $activeTheme)
@section('title', 'License Details - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Licenses', 'url' => route('driver.licenses.index')],
        ['label' => $license->license_number, 'active' => true],
    ];
    $isExpired = $license->expiration_date && $license->expiration_date->isPast();
    $isExpiringSoon = !$isExpired && $license->expiration_date && $license->expiration_date->diffInDays(now()) <= 30;
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
                    <x-base.lucide class="w-8 h-8 text-primary" icon="CreditCard" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">License Details</h1>
                    <p class="text-slate-600">License Number: #{{ $license->license_number }}</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('driver.licenses.index') }}" variant="outline-secondary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                    Back to Licenses
                </x-base.button>
            </div>
        </div>
    </div>

@if(session('success'))
<div class="box box--stacked p-4 mb-6 border-l-4 border-success bg-success/10">
    <div class="flex items-center gap-3">
        <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
        <p class="text-success font-medium">{{ session('success') }}</p>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <div class="box box--stacked p-6 {{ $isExpired ? 'border-l-4 border-danger' : ($isExpiringSoon ? 'border-l-4 border-warning' : '') }}">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="CreditCard" />
                License Information
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-slate-500 mb-1">License Number</p>
                    <p class="font-semibold text-slate-800">{{ $license->license_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">State of Issue</p>
                    <p class="font-semibold text-slate-800">{{ $license->state_of_issue }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">License Class</p>
                    <p class="font-semibold text-slate-800">Class {{ $license->license_class }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Expiration Date</p>
                    <p class="font-semibold {{ $isExpired ? 'text-danger' : ($isExpiringSoon ? 'text-warning' : 'text-slate-800') }}">
                        {{ $license->expiration_date ? $license->expiration_date->format('F d, Y') : 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">CDL License</p>
                    <p class="font-semibold text-slate-800">{{ $license->is_cdl ? 'Yes' : 'No' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Status</p>
                    @if($isExpired)
                        <x-base.badge variant="danger">Expired</x-base.badge>
                    @elseif($isExpiringSoon)
                        <x-base.badge variant="warning">Expiring Soon</x-base.badge>
                    @else
                        <x-base.badge variant="success">Active</x-base.badge>
                    @endif
                </div>
            </div>

            @if($license->restrictions)
            <div class="mt-6 pt-6 border-t border-slate-100">
                <p class="text-sm text-slate-500 mb-1">Restrictions</p>
                <p class="text-slate-800">{{ $license->restrictions }}</p>
            </div>
            @endif
        </div>

        @if($license->endorsements->count() > 0)
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Award" />
                Endorsements
            </h3>
            <div class="space-y-3">
                @foreach($license->endorsements as $endorsement)
                <div class="flex items-center justify-between border border-slate-200 p-3 bg-slate-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white rounded-lg">
                            <x-base.lucide class="w-4 h-4 text-primary" icon="CheckCircle" />
                        </div>
                        <div>
                            <p class="font-medium text-slate-800">{{ $endorsement->name }}</p>
                            @if($endorsement->code)
                            <p class="text-xs text-slate-500">Code: {{ $endorsement->code }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Badges -->
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4">Badges</h3>
            <div class="flex flex-wrap gap-2">
                @if($license->is_primary)
                    <x-base.badge variant="primary" class="gap-1">
                        <x-base.lucide class="w-3 h-3" icon="Star" />
                        Primary License
                    </x-base.badge>
                @endif
                @if($license->is_cdl)
                    <x-base.badge variant="info" class="gap-1">
                        <x-base.lucide class="w-3 h-3" icon="Truck" />
                        CDL
                    </x-base.badge>
                @endif
            </div>
        </div>

        <!-- License Images -->
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4">License Images</h3>
            <div class="space-y-4">
                @if($license->getFirstMediaUrl('license_front'))
                <div>
                    <p class="text-sm text-slate-500 mb-2">Front</p>
                    <a href="{{ $license->getFirstMediaUrl('license_front') }}" target="_blank">
                        <img src="{{ $license->getFirstMediaUrl('license_front') }}" 
                             alt="License Front" 
                             class="w-full rounded-lg border border-slate-200 hover:shadow-lg transition-shadow" />
                    </a>
                </div>
                @endif
                @if($license->getFirstMediaUrl('license_back'))
                <div>
                    <p class="text-sm text-slate-500 mb-2">Back</p>
                    <a href="{{ $license->getFirstMediaUrl('license_back') }}" target="_blank">
                        <img src="{{ $license->getFirstMediaUrl('license_back') }}" 
                             alt="License Back" 
                             class="w-full rounded-lg border border-slate-200 hover:shadow-lg transition-shadow" />
                    </a>
                </div>
                @endif
                @if(!$license->getFirstMediaUrl('license_front') && !$license->getFirstMediaUrl('license_back'))
                <div class="text-center py-6 text-slate-400">
                    <x-base.lucide class="w-10 h-10 mx-auto mb-2" icon="Image" />
                    <p class="text-sm">No images uploaded</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Timestamps -->
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4">Record Info</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Created</span>
                    <span class="text-slate-700">{{ $license->created_at->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Last Updated</span>
                    <span class="text-slate-700">{{ $license->updated_at->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
