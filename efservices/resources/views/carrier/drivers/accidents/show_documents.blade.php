@extends('../themes/' . $activeTheme)
@section('title', 'Accident Documents')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Accidents', 'url' => route('carrier.drivers.accidents.index')],
    ['label' => 'Documents', 'active' => true],
];
@endphp

@section('subcontent')
<div>
    <!-- Alerts -->
    <div class="pb-4">
        <!-- Flash Messages -->
        @if(session('success'))
        <x-base.alert variant="success" dismissible class="flex items-center gap-3">
            <x-base.lucide class="w-8 h-8 text-white" icon="check-circle" />
            <span class="text-white">
                {{ session('success') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif

        @if(session('error'))
        <x-base.alert variant="danger" dismissible>
            <span class="text-white">
                {{ session('error') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif
    </div>

    <!-- Accident Information Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-start gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="FileText" />
                </div>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Accident Documents</h1>
                    <div class="space-y-2 text-sm text-slate-600">
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4" icon="user" />
                            <span class="font-medium">Driver:</span>
                            <span>{{ implode(' ', array_filter([$accident->userDriverDetail->user->name ?? '', $accident->userDriverDetail->middle_name, $accident->userDriverDetail->last_name])) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4" icon="calendar" />
                            <span class="font-medium">Accident Date:</span>
                            <span>{{ \Carbon\Carbon::parse($accident->accident_date)->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <x-base.lucide class="w-4 h-4 mt-0.5" icon="alert-triangle" />
                            <span class="font-medium">Nature:</span>
                            <span class="flex-1">{{ $accident->nature_of_accident }}</span>
                        </div>
                        @if($accident->had_injuries)
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4" icon="activity" />
                            <span class="font-medium">Injuries:</span>
                            <span class="bg-warning/20 text-warning rounded px-2 py-0.5 text-xs font-medium">
                                {{ $accident->number_of_injuries ?? 0 }}
                            </span>
                        </div>
                        @endif
                        @if($accident->had_fatalities)
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4" icon="x-circle" />
                            <span class="font-medium">Fatalities:</span>
                            <span class="bg-danger/20 text-danger rounded px-2 py-0.5 text-xs font-medium">
                                {{ $accident->number_of_fatalities ?? 0 }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('carrier.drivers.accidents.index') }}" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                    Back to Accidents
                </x-base.button>
                <x-base.button type="button" variant="primary" onclick="document.getElementById('upload_files').click()">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="upload" />
                    Upload Documents
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Upload Form (Hidden) -->
    <form id="uploadForm" action="{{ route('carrier.drivers.accidents.documents.store', $accident) }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
        <input type="file" id="upload_files" name="accident_files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="handleFileUpload(event)">
    </form>

    <!-- Documents Grid -->
    <div class="box box--stacked mt-5 p-5">
        <div class="box-header mb-5">
            <h3 class="box-title">
                Documents 
                ({{ ($mediaDocuments ? $mediaDocuments->count() : 0) + ($oldDocuments ? $oldDocuments->count() : 0) }})
            </h3>
        </div>

        @if(($mediaDocuments && $mediaDocuments->count() > 0) || ($oldDocuments && $oldDocuments->count() > 0))
            <!-- Media Library Documents -->
            @if($mediaDocuments && $mediaDocuments->count() > 0)
            <div class="mb-8">
                <h4 class="text-lg font-medium mb-4 text-gray-700">Current Documents</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($mediaDocuments as $media)
                    <div class="border border-gray-200 rounded-lg p-4 bg-white hover:shadow-lg transition-shadow">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                @if(str_starts_with($media->mime_type, 'image/'))
                                    <img src="{{ $media->getUrl() }}" alt="{{ $media->file_name }}" class="w-full h-40 object-cover rounded mb-3">
                                @elseif($media->mime_type === 'application/pdf')
                                    <div class="w-full h-40 bg-red-50 rounded mb-3 flex items-center justify-center">
                                        <x-base.lucide class="w-16 h-16 text-red-500" icon="file-text" />
                                    </div>
                                @else
                                    <div class="w-full h-40 bg-blue-50 rounded mb-3 flex items-center justify-center">
                                        <x-base.lucide class="w-16 h-16 text-blue-500" icon="file" />
                                    </div>
                                @endif
                                
                                <h4 class="text-sm font-semibold text-gray-900 truncate mb-1" title="{{ $media->file_name }}">
                                    {{ $media->file_name }}
                                </h4>
                                
                                <div class="space-y-1 text-xs text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <x-base.lucide class="w-3 h-3" icon="calendar" />
                                        <span>{{ $media->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <x-base.lucide class="w-3 h-3" icon="hard-drive" />
                                        <span>{{ number_format($media->size / 1024, 2) }} KB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                            <x-base.button type="button" variant="outline-primary" size="sm" class="flex-1" onclick="previewDocument('media_{{ $media->id }}')">
                                <x-base.lucide class="w-3 h-3 mr-1" icon="eye" />
                                Preview
                            </x-base.button>
                            <x-base.button type="button" as="a" href="{{ $media->getUrl() }}" download variant="outline-secondary" size="sm">
                                <x-base.lucide class="w-3 h-3" icon="download" />
                            </x-base.button>
                            <x-base.button type="button" variant="outline-danger" size="sm" onclick="deleteMediaDocument({{ $media->id }})">
                                <x-base.lucide class="w-3 h-3" icon="trash-2" />
                            </x-base.button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Old System Documents -->
            @if($oldDocuments && $oldDocuments->count() > 0)
            <div class="mb-8">
                <h4 class="text-lg font-medium mb-4 text-gray-700">Legacy Documents</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($oldDocuments as $doc)
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 hover:shadow-lg transition-shadow">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <div class="w-full h-40 bg-gray-200 rounded mb-3 flex items-center justify-center">
                                    <x-base.lucide class="w-16 h-16 text-gray-500" icon="file" />
                                </div>
                                
                                <h4 class="text-sm font-semibold text-gray-900 truncate mb-1" title="{{ $doc->original_name }}">
                                    {{ $doc->original_name }}
                                </h4>
                                
                                <div class="space-y-1 text-xs text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <x-base.lucide class="w-3 h-3" icon="calendar" />
                                        <span>{{ $doc->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <x-base.lucide class="w-3 h-3" icon="hard-drive" />
                                        <span>{{ number_format($doc->size / 1024, 2) }} KB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                            <x-base.button type="button" variant="outline-primary" size="sm" class="flex-1" onclick="previewDocument('doc_{{ $doc->id }}')">
                                <x-base.lucide class="w-3 h-3 mr-1" icon="eye" />
                                Preview
                            </x-base.button>
                            <x-base.button type="button" variant="outline-danger" size="sm" onclick="deleteOldDocument({{ $doc->id }})">
                                <x-base.lucide class="w-3 h-3" icon="trash-2" />
                            </x-base.button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @else
        <div class="flex flex-col items-center justify-center py-16">
            <x-base.lucide class="w-16 h-16 text-slate-300 mb-4" icon="file-x" />
            <h3 class="text-lg font-medium text-slate-500 mb-2">No documents found</h3>
            <p class="text-slate-400 mb-6 text-center max-w-md">
                This accident doesn't have any documents yet. Click the button above to upload documents.
            </p>
        </div>
        @endif
    </div>
</div>

<!-- Preview Modal -->
<x-base.dialog id="previewModal" size="xl">
    <x-base.dialog.panel>
        <x-base.dialog.title>
            <h2 class="mr-auto text-base font-medium">Document Preview</h2>
        </x-base.dialog.title>
        <x-base.dialog.description class="p-0">
            <div id="preview-content" class="w-full" style="min-height: 500px;">
                <div class="flex items-center justify-center h-96">
                    <div class="text-center">
                        <x-base.lucide class="w-12 h-12 mx-auto text-gray-400 mb-3" icon="loader" />
                        <p class="text-gray-500">Loading preview...</p>
                    </div>
                </div>
            </div>
        </x-base.dialog.description>
        <x-base.dialog.footer class="text-right">
            <x-base.button type="button" variant="outline-secondary" data-tw-dismiss="modal">
                Close
            </x-base.button>
        </x-base.dialog.footer>
    </x-base.dialog.panel>
</x-base.dialog>
@endsection

@push('scripts')
<script src="{{ asset('js/carrier-accidents-documents.js') }}"></script>
@endpush
