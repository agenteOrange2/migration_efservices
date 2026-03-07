@extends('../themes/' . $activeTheme)
@section('title', 'My Inspections - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Inspections', 'active' => true],
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
                    <x-base.lucide class="w-8 h-8 text-primary" icon="FileCheck" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Vehicle Inspections</h1>
                    <p class="text-slate-600">View and manage your vehicle inspections</p>
                </div>
            </div>
        </div>    
</div>

@if($inspections->count() > 0)
<!-- Desktop Table View -->
<div class="box box--stacked overflow-hidden hidden md:block" role="region" aria-label="Inspection records table">
    <div class="overflow-x-auto">
        <table class="w-full text-left" role="table">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Date</th>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Type</th>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Result</th>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Inspector</th>
                    <th scope="col" class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($inspections as $inspection)
                <tr class="hover:bg-slate-50" tabindex="0">
                    <td class="px-6 py-4">
                        <p class="font-medium text-slate-800">{{ $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('M d, Y') : 'N/A' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-slate-600">{{ $inspection->inspection_type ?? 'Vehicle Inspection' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        @if(strtolower($inspection->result ?? '') == 'pass' || strtolower($inspection->result ?? '') == 'passed')
                            <x-base.badge variant="success">Passed</x-base.badge>
                        @elseif(strtolower($inspection->result ?? '') == 'fail' || strtolower($inspection->result ?? '') == 'failed')
                            <x-base.badge variant="danger">Failed</x-base.badge>
                        @else
                            <x-base.badge variant="secondary">{{ $inspection->result ?? 'N/A' }}</x-base.badge>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-slate-600">{{ $inspection->inspector_name ?? $inspection->inspector ?? 'N/A' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('driver.inspections.show', $inspection->id) }}" class="text-primary hover:underline text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded" aria-label="View details for inspection on {{ $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('M d, Y') : 'N/A' }}">
                            View Details
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile Card View -->
<div class="md:hidden space-y-4" role="list" aria-label="Inspection records">
    @foreach($inspections as $inspection)
    <div class="box box--stacked p-4" role="listitem">
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-info/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-info" icon="Search" aria-hidden="true" />
                </div>
                <div>
                    <p class="font-semibold text-slate-800">{{ $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('M d, Y') : 'N/A' }}</p>
                    <p class="text-sm text-slate-500">{{ $inspection->inspection_type ?? 'Vehicle Inspection' }}</p>
                </div>
            </div>
            @if(strtolower($inspection->result ?? '') == 'pass' || strtolower($inspection->result ?? '') == 'passed')
                <x-base.badge variant="success">Passed</x-base.badge>
            @elseif(strtolower($inspection->result ?? '') == 'fail' || strtolower($inspection->result ?? '') == 'failed')
                <x-base.badge variant="danger">Failed</x-base.badge>
            @else
                <x-base.badge variant="secondary">{{ $inspection->result ?? 'N/A' }}</x-base.badge>
            @endif
        </div>
        <div class="text-sm text-slate-600 mb-3">
            <span class="text-slate-500">Inspector:</span> {{ $inspection->inspector_name ?? $inspection->inspector ?? 'N/A' }}
        </div>
        <a href="{{ route('driver.inspections.show', $inspection->id) }}" class="block w-full text-center py-2 text-sm text-primary bg-primary/5 hover:bg-primary/10 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2" aria-label="View details for inspection on {{ $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('M d, Y') : 'N/A' }}">
            View Details
        </a>
    </div>
    @endforeach
</div>
@else
<div class="box box--stacked p-8 text-center">
    <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="Search" />
    <h3 class="text-lg font-semibold text-slate-700 mb-2">No Inspection Records</h3>
    <p class="text-slate-500">You don't have any vehicle inspection records yet.</p>
</div>
@endif

@endsection
