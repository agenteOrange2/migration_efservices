@extends('../themes/' . $activeTheme)
@section('title', 'My Documents - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Documents', 'active' => true],
    ];
@endphp

@section('subcontent')

<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">My Documents</h1>
        <p class="text-slate-500 mt-1">{{ $totalDocuments }} documents in your library</p>
    </div>
    <div class="flex gap-2">
        <x-base.button as="a" href="{{ route('driver.documents.create') }}" variant="outline-primary" class="gap-2">
            <x-base.lucide class="w-4 h-4" icon="Upload" />
            Upload
        </x-base.button>
        @if($totalDocuments > 0)
        <x-base.button as="a" href="{{ route('driver.documents.download-all') }}" variant="primary" class="gap-2">
            <x-base.lucide class="w-4 h-4" icon="Download" />
            Download All
        </x-base.button>
        @endif
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

@if(count($documentCategories) > 0)
<div class="space-y-6">
    @foreach($documentCategories as $category => $documents)
    <div class="box box--stacked p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Folder" />
                {{ $category }}
                <span class="text-sm font-normal text-slate-500">({{ $documents->count() }})</span>
            </h3>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($documents as $doc)
            <div class="bg-slate-50 rounded-lg p-4 hover:bg-slate-100 transition-colors group">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-white rounded-lg shadow-sm">
                        @if(str_contains($doc->mime_type ?? '', 'pdf'))
                            <x-base.lucide class="w-6 h-6 text-danger" icon="FileText" />
                        @elseif(str_contains($doc->mime_type ?? '', 'image'))
                            <x-base.lucide class="w-6 h-6 text-info" icon="Image" />
                        @else
                            <x-base.lucide class="w-6 h-6 text-slate-500" icon="File" />
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate" title="{{ $doc->file_name }}">
                            {{ $doc->file_name }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ $doc->human_readable_size ?? 'N/A' }} • {{ $doc->created_at->format('M d, Y') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-200">
                    <a href="{{ $doc->getUrl() }}" target="_blank" 
                       class="flex-1 text-center py-1.5 text-xs text-slate-600 hover:text-primary hover:bg-white rounded transition-colors">
                        View
                    </a>
                    <a href="{{ $doc->getUrl() }}" download 
                       class="flex-1 text-center py-1.5 text-xs text-slate-600 hover:text-primary hover:bg-white rounded transition-colors">
                        Download
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@else
<div class="box box--stacked p-12 text-center">
    <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="FileText" />
    <h3 class="text-lg font-semibold text-slate-700 mb-2">No Documents</h3>
    <p class="text-slate-500 mb-6">You don't have any documents uploaded yet.</p>
    <x-base.button as="a" href="{{ route('driver.documents.create') }}" variant="primary" class="gap-2">
        <x-base.lucide class="w-4 h-4" icon="Upload" />
        Upload Your First Document
    </x-base.button>
</div>
@endif

@endsection
