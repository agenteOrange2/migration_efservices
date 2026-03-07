@extends('../themes/' . $activeTheme)
@section('title', 'Inspection Details - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Inspections', 'url' => route('driver.inspections.index')],
        ['label' => 'Details', 'active' => true],
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
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Vehicle Inspection Details</h1>
                    <p class="text-slate-600">View the details of your vehicle inspection</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button  as="a" href="{{ route('driver.inspections.index') }}" variant="primary" class="gap-2">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                    Back to Inspections
                </x-base.button>
            </div>
        </div>
    </div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Search" />
                Inspection Information
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Inspection Date</p>
                    <p class="font-semibold text-slate-800">{{ $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('F d, Y') : 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Type</p>
                    <p class="font-semibold text-slate-800">{{ $inspection->inspection_type ?? 'Vehicle Inspection' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Result</p>
                    @if(strtolower($inspection->result ?? '') == 'pass' || strtolower($inspection->result ?? '') == 'passed')
                        <x-base.badge variant="success">Passed</x-base.badge>
                    @elseif(strtolower($inspection->result ?? '') == 'fail' || strtolower($inspection->result ?? '') == 'failed')
                        <x-base.badge variant="danger">Failed</x-base.badge>
                    @else
                        <x-base.badge variant="secondary">{{ $inspection->result ?? 'N/A' }}</x-base.badge>
                    @endif
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Inspector</p>
                    <p class="font-semibold text-slate-800">{{ $inspection->inspector_name ?? $inspection->inspector ?? 'N/A' }}</p>
                </div>
                @if($inspection->location)
                <div class="sm:col-span-2">
                    <p class="text-sm text-slate-500 mb-1">Location</p>
                    <p class="font-semibold text-slate-800">{{ $inspection->location }}</p>
                </div>
                @endif
                @if($inspection->notes)
                <div class="sm:col-span-2">
                    <p class="text-sm text-slate-500 mb-1">Notes</p>
                    <p class="text-slate-800">{{ $inspection->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4">Documents</h3>
            @php
                $docs = $inspection->getMedia('inspection_documents');
            @endphp
            
            @if($docs->count() > 0)
            <div class="space-y-2">
                @foreach($docs as $doc)
                <a href="{{ $doc->getUrl() }}" target="_blank" class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                    <span class="text-sm text-slate-700 truncate flex-1">{{ $doc->file_name }}</span>
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="ExternalLink" />
                </a>
                @endforeach
            </div>
            @else
            <p class="text-slate-400 text-sm text-center py-4">No documents available</p>
            @endif
        </div>
    </div>
</div>

@endsection
