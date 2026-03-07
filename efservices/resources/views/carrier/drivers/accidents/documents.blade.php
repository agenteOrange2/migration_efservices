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

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="FileText" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Accident Documents</h1>
                    <p class="text-slate-600">View and manage all accident-related documents</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('carrier.drivers.accidents.index') }}" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                    Back to Accidents
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <h3 class="box-title">Filter Documents</h3>
        </div>
        <div class="box-body p-5">
            <form action="{{ route('carrier.drivers.accidents.documents.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <x-base.form-label for="driver_id">Driver</x-base.form-label>
                    <select id="driver_id" name="driver_id" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Drivers</option>
                        @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                            {{ implode(' ', array_filter([$driver->user->name ?? '', $driver->middle_name, $driver->last_name])) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-base.form-label for="file_type">File Type</x-base.form-label>
                    <select id="file_type" name="file_type" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Types</option>
                        <option value="image" {{ request('file_type') == 'image' ? 'selected' : '' }}>Images</option>
                        <option value="pdf" {{ request('file_type') == 'pdf' ? 'selected' : '' }}>PDFs</option>
                        <option value="document" {{ request('file_type') == 'document' ? 'selected' : '' }}>Documents</option>
                    </select>
                </div>
                <div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <x-base.form-label for="start_date">Date From</x-base.form-label>
                            <x-base.litepicker name="start_date" value="{{ request('start_date') }}" placeholder="MM-DD-YYYY" />
                        </div>
                        <div>
                            <x-base.form-label for="end_date">Date To</x-base.form-label>
                            <x-base.litepicker name="end_date" value="{{ request('end_date') }}" placeholder="MM-DD-YYYY" />
                        </div>
                    </div>
                </div>
                <div>
                    <x-base.form-label for="accident_id">Specific Accident</x-base.form-label>
                    <select id="accident_id" name="accident_id" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Accidents</option>
                        @foreach ($accidents as $accident)
                        <option value="{{ $accident->id }}" {{ request('accident_id') == $accident->id ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($accident->accident_date)->format('m/d/Y') }} - 
                            {{ implode(' ', array_filter([$accident->userDriverDetail->user->name ?? '', $accident->userDriverDetail->middle_name, $accident->userDriverDetail->last_name])) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end md:col-span-4">
                    <x-base.button type="submit" variant="primary" class="w-full">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="filter" />
                        Apply Filters
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de documentos -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h3 class="box-title">Documents ({{ $documents->total() ?? 0 }})</h3>
            </div>
        </div>

        @if($documents->count() > 0)
        <div class="box-body p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($documents as $document)
                <div class="border border-gray-200 rounded-lg p-4 bg-white hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            @if(str_starts_with($document['mime_type'], 'image/'))
                                @if($document['type'] === 'media')
                                    <img src="{{ $document['media_object']->getUrl() }}" alt="{{ $document['name'] }}" class="w-full h-40 object-cover rounded mb-3">
                                @else
                                    <div class="w-full h-40 bg-gray-100 rounded mb-3 flex items-center justify-center">
                                        <x-base.lucide class="w-16 h-16 text-gray-400" icon="image" />
                                    </div>
                                @endif
                            @elseif($document['mime_type'] === 'application/pdf')
                                <div class="w-full h-40 bg-red-50 rounded mb-3 flex items-center justify-center">
                                    <x-base.lucide class="w-16 h-16 text-red-500" icon="file-text" />
                                </div>
                            @else
                                <div class="w-full h-40 bg-blue-50 rounded mb-3 flex items-center justify-center">
                                    <x-base.lucide class="w-16 h-16 text-blue-500" icon="file" />
                                </div>
                            @endif
                            
                            <h4 class="text-sm font-semibold text-gray-900 truncate mb-1" title="{{ $document['name'] }}">
                                {{ $document['name'] }}
                            </h4>
                            
                            <div class="space-y-1 text-xs text-gray-600">
                                <div class="flex items-center gap-1">
                                    <x-base.lucide class="w-3 h-3" icon="user" />
                                    <span class="truncate">
                                        {{ implode(' ', array_filter([$document['driver']->user->name ?? '', $document['driver']->middle_name, $document['driver']->last_name])) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <x-base.lucide class="w-3 h-3" icon="calendar" />
                                    <span>{{ \Carbon\Carbon::parse($document['accident']->accident_date)->format('M d, Y') }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <x-base.lucide class="w-3 h-3" icon="alert-triangle" />
                                    <span class="truncate" title="{{ $document['accident']->nature_of_accident }}">
                                        {{ Str::limit($document['accident']->nature_of_accident, 30) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <x-base.lucide class="w-3 h-3" icon="hard-drive" />
                                    <span>{{ number_format($document['size'] / 1024, 2) }} KB</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
                        <x-base.button type="button" variant="outline-primary" size="sm" class="flex-1" onclick="previewDocument('{{ $document['id'] }}')">
                            <x-base.lucide class="w-3 h-3 mr-1" icon="eye" />
                            Preview
                        </x-base.button>
                        @if($document['type'] === 'media')
                            <x-base.button type="button" as="a" href="{{ $document['media_object']->getUrl() }}" download variant="outline-secondary" size="sm">
                                <x-base.lucide class="w-3 h-3" icon="download" />
                            </x-base.button>
                        @endif
                        <x-base.button type="button" variant="outline-danger" size="sm" onclick="deleteDocument('{{ $document['id'] }}', '{{ $document['type'] }}')">
                            <x-base.lucide class="w-3 h-3" icon="trash-2" />
                        </x-base.button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- Paginación -->
        <div class="box-footer py-5 px-8">
            {{ $documents->links('custom.pagination') }}
        </div>
        @else
        <div class="box-body p-10 text-center">
            <div class="flex flex-col items-center justify-center py-8">
                <x-base.lucide class="w-16 h-16 text-slate-300" icon="file-text" />
                <div class="mt-5 text-slate-500">
                    No documents found.
                </div>
                <p class="text-sm text-slate-400 mt-2">
                    Try adjusting your filters or add documents to accident records.
                </p>
            </div>
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
