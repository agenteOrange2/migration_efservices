@extends('../themes/' . $activeTheme)
@section('title', 'License Documents')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('carrier.dashboard')],
['label' => 'Licenses', 'url' => route('carrier.licenses.index')],
['label' => 'License Details', 'url' => route('carrier.licenses.show', $license->id)],
['label' => 'Documents', 'active' => true],
];
@endphp

@section('subcontent')
<div>
    <!-- Flash Messages -->
    @if(session('success'))
        <x-base.alert variant="success" dismissible class="flex items-center gap-3 mb-5">
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
        <x-base.alert variant="danger" dismissible class="mb-5">
            <span class="text-white">
                {{ session('error') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
    @endif

    <!-- Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="file-text" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">License Documents</h1>
                    <p class="text-slate-600">
                        Documents for {{ implode(' ', array_filter([$license->driverDetail->user->name ?? '', $license->driverDetail->middle_name, $license->driverDetail->last_name])) }} - 
                        License #{{ $license->license_number }}
                    </p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('carrier.licenses.show', $license->id) }}" class="w-full sm:w-auto" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                    Back to License
                </x-base.button>
                <x-base.button as="a" href="{{ route('carrier.licenses.edit', $license->id) }}" class="w-full sm:w-auto" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="upload" />
                    Upload Documents
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Document Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Total Documents -->
        <a href="{{ route('carrier.licenses.docs.show', ['license' => $license->id, 'collection' => 'all']) }}" 
           class="box box--stacked p-5 hover:shadow-lg transition-shadow {{ $currentCollection === 'all' ? 'ring-2 ring-primary' : '' }}">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-slate-500 text-xs uppercase tracking-widest mb-1">Total Documents</div>
                    <div class="text-3xl font-bold text-slate-800">{{ $totalDocuments }}</div>
                </div>
                <div class="p-3 bg-primary/10 rounded-xl">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="files" />
                </div>
            </div>
        </a>

        <!-- Front Images -->
        <a href="{{ route('carrier.licenses.docs.show', ['license' => $license->id, 'collection' => 'license_front']) }}" 
           class="box box--stacked p-5 hover:shadow-lg transition-shadow {{ $currentCollection === 'license_front' ? 'ring-2 ring-info' : '' }}">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-slate-500 text-xs uppercase tracking-widest mb-1">Front Images</div>
                    <div class="text-3xl font-bold text-slate-800">{{ $licenseFrontImages }}</div>
                </div>
                <div class="p-3 bg-info/10 rounded-xl">
                    <x-base.lucide class="w-8 h-8 text-info" icon="credit-card" />
                </div>
            </div>
        </a>

        <!-- Back Images -->
        <a href="{{ route('carrier.licenses.docs.show', ['license' => $license->id, 'collection' => 'license_back']) }}" 
           class="box box--stacked p-5 hover:shadow-lg transition-shadow {{ $currentCollection === 'license_back' ? 'ring-2 ring-success' : '' }}">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-slate-500 text-xs uppercase tracking-widest mb-1">Back Images</div>
                    <div class="text-3xl font-bold text-slate-800">{{ $licenseBackImages }}</div>
                </div>
                <div class="p-3 bg-success/10 rounded-xl">
                    <x-base.lucide class="w-8 h-8 text-success" icon="credit-card" />
                </div>
            </div>
        </a>

        <!-- Additional Documents -->
        <a href="{{ route('carrier.licenses.docs.show', ['license' => $license->id, 'collection' => 'additional']) }}" 
           class="box box--stacked p-5 hover:shadow-lg transition-shadow {{ $currentCollection === 'additional' ? 'ring-2 ring-warning' : '' }}">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-slate-500 text-xs uppercase tracking-widest mb-1">Additional</div>
                    <div class="text-3xl font-bold text-slate-800">{{ $additionalDocuments }}</div>
                </div>
                <div class="p-3 bg-warning/10 rounded-xl">
                    <x-base.lucide class="w-8 h-8 text-warning" icon="paperclip" />
                </div>
            </div>
        </a>
    </div>

    <!-- Filters -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <h3 class="box-title">Filter Documents</h3>
        </div>
        <div class="box-body p-5">
            <form action="{{ route('carrier.licenses.docs.show', $license->id) }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-base.form-label for="document_type">Document Type</x-base.form-label>
                    <select id="document_type" name="document_type" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Types</option>
                        @foreach($documentTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('document_type') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <x-base.form-label for="date_from">Date (from)</x-base.form-label>
                            <x-base.litepicker name="date_from" value="{{ request('date_from') }}" placeholder="Select a date" />
                        </div>
                        <div>
                            <x-base.form-label for="date_to">Date (to)</x-base.form-label>
                            <x-base.litepicker name="date_to" value="{{ request('date_to') }}" placeholder="Select a date" />
                        </div>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <x-base.button type="submit" variant="primary" class="flex-1">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="filter" />
                        Apply Filters
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.licenses.docs.show', $license->id) }}" variant="outline-secondary" class="flex-1">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="x" />
                        Clear
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>

    <!-- Documents List -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h3 class="box-title">Documents ({{ $documents->total() ?? 0 }})</h3>
            </div>
        </div>

        @if($documents->count() > 0)
        <div class="box-body p-0">
            <div class="overflow-x-auto">
                <x-base.table class="border-separate border-spacing-y-[10px]">
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th class="whitespace-nowrap">
                                Document Name
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Type
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Size
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Upload Date
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Actions
                            </x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach($documents as $document)
                        <x-base.table.tr>
                            <x-base.table.td class="px-6 py-4">
                                <div class="flex items-center">
                                    @php
                                        $extension = strtolower(pathinfo($document->file_name, PATHINFO_EXTENSION));
                                        $iconMap = [
                                            'pdf' => 'file-text',
                                            'doc' => 'file-text',
                                            'docx' => 'file-text',
                                            'jpg' => 'image',
                                            'jpeg' => 'image',
                                            'png' => 'image',
                                            'gif' => 'image',
                                        ];
                                        $icon = $iconMap[$extension] ?? 'file';
                                    @endphp
                                    <x-base.lucide class="mr-2 h-4 w-4 text-slate-500" icon="{{ $icon }}" />
                                    <span class="font-medium">{{ $document->name }}</span>
                                </div>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @php
                                    $collectionLabels = [
                                        'license_front' => ['label' => 'Front Image', 'color' => 'info'],
                                        'license_back' => ['label' => 'Back Image', 'color' => 'success'],
                                        'licenses' => ['label' => 'Additional', 'color' => 'warning'],
                                    ];
                                    $collectionInfo = $collectionLabels[$document->collection_name] ?? ['label' => 'Other', 'color' => 'slate'];
                                @endphp
                                <div class="inline-flex items-center rounded-full bg-{{ $collectionInfo['color'] }}/10 px-2 py-1 text-xs font-medium text-{{ $collectionInfo['color'] }}">
                                    {{ $collectionInfo['label'] }}
                                </div>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <span class="text-slate-500">{{ $document->human_readable_size }}</span>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <span class="text-slate-500">{{ $document->created_at->format('M d, Y H:i') }}</span>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <x-base.button
                                        as="a"
                                        href="{{ route('carrier.licenses.doc.preview', $document->id) }}"
                                        target="_blank"
                                        variant="outline-primary"
                                        size="sm"
                                        title="View document"
                                    >
                                        <x-base.lucide class="h-4 w-4" icon="eye" />
                                    </x-base.button>
                                    <x-base.button
                                        as="a"
                                        href="{{ route('carrier.licenses.doc.preview', ['id' => $document->id, 'download' => true]) }}"
                                        variant="outline-secondary"
                                        size="sm"
                                        title="Download document"
                                    >
                                        <x-base.lucide class="h-4 w-4" icon="download" />
                                    </x-base.button>
                                    <x-base.button
                                        type="button"
                                        onclick="confirmDelete({{ $document->id }})"
                                        variant="outline-danger"
                                        size="sm"
                                        title="Delete document"
                                    >
                                        <x-base.lucide class="h-4 w-4" icon="trash-2" />
                                    </x-base.button>
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>
        </div>
        <!-- Pagination -->
        <div class="box-footer py-5 px-8">
            {{ $documents->links('custom.pagination') }}
        </div>
        @else
        <div class="box-body p-10 text-center">
            <div class="flex flex-col items-center justify-center py-8">
                <x-base.lucide class="w-16 h-16 text-slate-300" icon="folder-open" />
                <div class="mt-5 text-slate-500">
                    No documents found.
                </div>
                <x-base.button as="a" href="{{ route('carrier.licenses.edit', $license->id) }}" class="mt-5">
                    <x-base.lucide class="w-4 h-4 mr-1" icon="upload" />
                    Upload Documents
                </x-base.button>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Delete Document Confirmation Modal -->
<x-base.dialog id="deleteModal">
    <x-base.dialog.panel>
        <div class="p-5 text-center">
            <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="x-circle" />
            <div class="mt-5 text-3xl">Are you sure?</div>
            <div class="mt-2 text-slate-500">
                Do you really want to delete this document? <br>
                This process cannot be undone.
            </div>
        </div>
        <div class="px-5 pb-8 text-center">
            <x-base.button
                class="mr-1 w-24"
                data-tw-dismiss="modal"
                type="button"
                variant="outline-secondary"
            >
                Cancel
            </x-base.button>
            <form id="deleteForm" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <x-base.button class="w-24" type="submit" variant="danger">
                    Delete
                </x-base.button>
            </form>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>
@endsection

@section('script')
<script>
function confirmDelete(documentId) {
    const form = document.getElementById('deleteForm');
    form.action = `{{ url('carrier/licenses/documents') }}/${documentId}`;
    
    const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteModal'));
    modal.show();
}
</script>
@endsection
