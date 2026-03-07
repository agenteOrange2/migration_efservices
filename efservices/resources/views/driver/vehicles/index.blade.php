@extends('../themes/' . $activeTheme)
@section('title', 'My Vehicles - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Vehicles', 'active' => true],
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
                <x-base.lucide class="w-8 h-8 text-primary" icon="Truck" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">My Vehicles</h1>
                <p class="text-slate-600">View and manage your assigned vehicles</p>
            </div>
        </div>
    </div>
</div>

@if($vehicles->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($vehicles as $vehicle)
    <div class="box box--stacked p-6 hover:shadow-lg transition-all">
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 bg-primary/10 rounded-xl">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Truck" />
            </div>
            @if($vehicle->status ?? false)
                <x-base.badge variant="{{ $vehicle->status == 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($vehicle->status) }}
                </x-base.badge>
            @endif
        </div>
        
        <h3 class="font-semibold text-lg text-slate-800 mb-1">
            {{ $vehicle->make ?? '' }} {{ $vehicle->model ?? 'Vehicle' }}
        </h3>
        <p class="text-slate-500 mb-4">{{ $vehicle->year ?? '' }}</p>
        
        <div class="space-y-2 text-sm">
            @if($vehicle->company_unit_number)
            <div class="flex justify-between">
                <span class="text-slate-500">Unit #</span>
                <span class="text-slate-700 font-semibold">{{ $vehicle->company_unit_number }}</span>
            </div>
            @endif
            @if($vehicle->vin)
            <div class="flex justify-between">
                <span class="text-slate-500">VIN</span>
                <span class="text-slate-700 font-mono text-xs">{{ $vehicle->vin }}</span>
            </div>
            @endif
            @if($vehicle->license_plate)
            <div class="flex justify-between">
                <span class="text-slate-500">License Plate</span>
                <span class="text-slate-700 font-semibold">{{ $vehicle->license_plate }}</span>
            </div>
            @endif
            @if($vehicle->color)
            <div class="flex justify-between">
                <span class="text-slate-500">Color</span>
                <span class="text-slate-700">{{ $vehicle->color }}</span>
            </div>
            @endif
        </div>
        
        <div class="mt-4 pt-4 border-t border-slate-100">
            <a href="{{ route('driver.vehicles.show', $vehicle->id) }}" 
               class="flex items-center justify-center gap-2 text-primary hover:underline text-sm font-medium">
                View Details
                <x-base.lucide class="w-4 h-4" icon="ArrowRight" />
            </a>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="box box--stacked p-12 text-center">
    <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="Truck" />
    <h3 class="text-lg font-semibold text-slate-700 mb-2">No Vehicles Assigned</h3>
    <p class="text-slate-500">You don't have any vehicles assigned to you yet.</p>
    <p class="text-slate-400 text-sm mt-2">Contact your carrier if you believe this is an error.</p>
</div>
@endif

@endsection
