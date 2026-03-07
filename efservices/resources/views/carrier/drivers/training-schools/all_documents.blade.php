@extends('../themes/' . $activeTheme)
@section('title', 'All Training School Documents')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Training Schools', 'url' => route('carrier.training-schools.index')],
    ['label' => 'All Documents', 'active' => true],
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

        @if(session('warning'))
        <x-base.alert variant="warning" dismissible>
            <span class="text-white">
                {{ session('warning') }}
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
                    <x-base.lucide class="w-8 h-8 text-primary" icon="file-text" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">All Training School Documents</h1>
                    <p class="text-slate-600">View and manage all training school documents</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('carrier.training-schools.index') }}" class="w-full sm:w-auto" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                    Back to Schools
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
            <form action="{{ route('carrier.training-schools.docs.all') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <x-base.form-label for="search_term">Search</x-base.form-label>
                    <x-base.form-input type="text" name="search_term" id="search_term" value="{{ request('search_term') }}" placeholder="Document name..." />
                </div>
                <div>
                    <x-base.form-label for="driver_filter">Driver</x-base.form-label>
                    <select id="driver_filter" name="driver_filter" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Drivers</option>
                        @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ request('driver_filter') == $driver->id ? 'selected' : '' }}>
                            {{ implode(' ', array_filter([$driver->user->name ?? '', $driver->middle_name, $driver->last_name])) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-base.form-label for="school_filter">School</x-base.form-label>
                    <select id="school_filter" name="school_filter" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Schools</option>
                        @foreach ($schools as $school)
                        <option value="{{ $school->id }}" {{ request('school_filter') == $school->id ? 'selected' : '' }}>
                            {{ $school->school_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <x-base.form-label for="date_from">Date From (MM/DD/YYYY)</x-base.form-label>
                            <x-base.litepicker name="date_from" value="{{ request('date_from') }}" placeholder="MM/DD/YYYY" />
                        </div>
                        <div>
                            <x-base.form-label for="date_to">Date To (MM/DD/YYYY)</x-base.form-label>
                            <x-base.litepicker name="date_to" value="{{ request('date_to') }}" placeholder="MM/DD/YYYY" />
                        </div>
                    </div>
                </div>
                <div class="flex items-end gap-2 md:col-span-4">
                    <x-base.button type="submit" variant="primary" class="w-full md:w-auto">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="filter" />
                        Apply Filters
                    </x-base.button>
                    @if(request()->hasAny(['search_term', 'driver_filter', 'school_filter', 'date_from', 'date_to']))
                    <x-base.button as="a" href="{{ route('carrier.training-schools.docs.all') }}" variant="outline-secondary" class="w-full md:w-auto">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="x" />
                        Clear
                    </x-base.button>
                    @endif
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
        <div class="box-body p-0">
            <div class="overflow-x-auto">
                <x-base.table class="border-separate border-spacing-y-[10px]">
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.training-schools.docs.all', array_merge(request()->except(['sort_field', 'sort_direction']), ['sort_field' => 'name', 'sort_direction' => request('sort_field') == 'name' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                    Document Name
                                    @if (request('sort_field') == 'name')
                                        @if (request('sort_direction') == 'asc')
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @else
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                School
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Driver
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Type
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Size
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.training-schools.docs.all', array_merge(request()->except(['sort_field', 'sort_direction']), ['sort_field' => 'created_at', 'sort_direction' => request('sort_field') == 'created_at' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                    Upload Date
                                    @if (request('sort_field') == 'created_at')
                                        @if (request('sort_direction') == 'asc')
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @else
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap text-center">
                                Actions
                            </x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @forelse ($documents as $document)
                        @php
                            $school = \App\Models\Admin\Driver\DriverTrainingSchool::find($document->model_id);
                        @endphp
                        <x-base.table.tr data-document-row="{{ $document->id }}">
                            <x-base.table.td class="px-6 py-4">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-5 h-5 text-primary mr-3" icon="file-text" />
                                    <span class="font-medium text-gray-900">{{ $document->name }}</span>
                                </div>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @if($school)
                                    <div class="max-w-xs truncate" title="{{ $school->school_name }}">
                                        {{ $school->school_name }}
                                    </div>
                                @else
                                    <span class="text-slate-400">---</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @if($school && $school->userDriverDetail)
                                    {{ implode(' ', array_filter([$school->userDriverDetail->user->name ?? '', $school->userDriverDetail->middle_name, $school->userDriverDetail->last_name])) ?: '---' }}
                                @else
                                    <span class="text-slate-400">---</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ strtoupper(pathinfo($document->file_name, PATHINFO_EXTENSION)) }}</span>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ number_format($document->size / 1024, 2) }} KB</span>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $document->created_at->format('m/d/Y') }}</span>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <x-base.button type="button" as="a" href="{{ $document->getUrl() }}" target="_blank" variant="outline-primary" size="sm">
                                        <x-base.lucide class="w-4 h-4 mr-1" icon="eye" />
                                        View
                                    </x-base.button>
                                    <x-base.button type="button" as="a" href="{{ $document->getUrl() }}" download variant="outline-secondary" size="sm">
                                        <x-base.lucide class="w-4 h-4 mr-1" icon="download" />
                                        Download
                                    </x-base.button>
                                    <x-base.button 
                                        type="button" 
                                        variant="outline-danger" 
                                        size="sm" 
                                        class="delete-document-btn" 
                                        data-document-id="{{ $document->id }}" 
                                        data-document-name="{{ $document->name }}"
                                        onclick="confirmDelete({{ $document->id }}, '{{ addslashes($document->name) }}')">
                                        <x-base.lucide class="w-4 h-4 mr-1" icon="trash-2" />
                                        Delete
                                    </x-base.button>
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @empty
                        <x-base.table.tr>
                            <x-base.table.td colspan="7" class="text-center">
                                <div class="flex flex-col items-center justify-center py-16">
                                    <x-base.lucide class="h-8 w-8 text-slate-400" icon="file-text" />
                                    No documents found
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @endforelse
                    </x-base.table.tbody>
                </x-base.table>
            </div>
        </div>
        <!-- Paginación -->
        <div class="box-footer py-5 px-8">
            {{ $documents->appends(request()->except('page'))->links('custom.pagination') }}
        </div>
        @else
        <div class="box-body p-10 text-center">
            <div class="flex flex-col items-center justify-center py-8">
                <x-base.lucide class="w-16 h-16 text-slate-300" icon="file-text" />
                <div class="mt-5 text-slate-500">
                    No documents found.
                </div>
                <x-base.button as="a" href="{{ route('carrier.training-schools.index') }}" class="mt-5">
                    <x-base.lucide class="w-4 h-4 mr-1" icon="arrow-left" />
                    Back to Schools
                </x-base.button>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title font-semibold text-lg">Confirm Deletion</h3>
                <button type="button" class="btn-close" onclick="closeDeleteModal()">
                    <x-base.lucide class="w-4 h-4" icon="x" />
                </button>
            </div>
            <div class="modal-body p-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-red-100 rounded-full">
                        <x-base.lucide class="w-6 h-6 text-red-600" icon="alert-triangle" />
                    </div>
                    <div>
                        <p class="text-gray-900 font-medium mb-2">Are you sure you want to delete this document?</p>
                        <p class="text-gray-600 text-sm mb-1">Document: <span id="documentNameDisplay" class="font-medium"></span></p>
                        <p class="text-gray-600 text-sm">This action cannot be undone.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <x-base.button type="button" variant="outline-secondary" onclick="closeDeleteModal()">
                    Cancel
                </x-base.button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <x-base.button type="submit" variant="danger">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="trash-2" />
                        Delete Document
                    </x-base.button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(documentId, documentName) {
        // Set the document name in the modal
        document.getElementById('documentNameDisplay').textContent = documentName;
        
        // Set the form action
        const form = document.getElementById('deleteForm');
        form.action = '{{ route("carrier.training-schools.documents.delete", ":id") }}'.replace(':id', documentId);
        
        // Show the modal
        const modal = document.getElementById('deleteModal');
        modal.style.display = 'block';
        modal.classList.add('show');
        
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'modalBackdrop';
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.style.display = 'none';
        modal.classList.remove('show');
        
        // Remove backdrop
        const backdrop = document.getElementById('modalBackdrop');
        if (backdrop) {
            backdrop.remove();
        }
        document.body.classList.remove('modal-open');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endsection
