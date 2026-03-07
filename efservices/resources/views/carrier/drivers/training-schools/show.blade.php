@extends('../themes/' . $activeTheme)
@section('title', 'Training School Documents')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Training Schools', 'url' => route('carrier.training-schools.index')],
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
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Training School Documents</h1>
                    <p class="text-slate-600">View and manage documents for this training school</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('carrier.training-schools.index') }}" class="w-full sm:w-auto" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                    Back to List
                </x-base.button>
                <x-base.button as="a" href="{{ route('carrier.training-schools.edit', $school->id) }}" class="w-full sm:w-auto" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="edit" />
                    Edit School
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Training School Information -->
    <div class="box box--stacked p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4 text-gray-800 border-b pb-2">Training School Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">Driver</p>
                <p class="font-medium text-gray-900">
                    {{ implode(' ', array_filter([
                        $school->userDriverDetail->user->name ?? '', 
                        $school->userDriverDetail->middle_name, 
                        $school->userDriverDetail->last_name
                    ])) }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">School Name</p>
                <p class="font-medium text-gray-900">{{ $school->school_name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Location</p>
                <p class="font-medium text-gray-900">{{ $school->city }}, {{ $school->state }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Start Date</p>
                <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($school->date_start)->format('m/d/Y') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">End Date</p>
                <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($school->date_end)->format('m/d/Y') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 mb-1">Status</p>
                <p class="font-medium text-gray-900">
                    @if($school->graduated)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <x-base.lucide class="w-3 h-3 mr-1" icon="check-circle" />
                            Graduated
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <x-base.lucide class="w-3 h-3 mr-1" icon="clock" />
                            In Progress
                        </span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="box box--stacked">
        <div class="box-header p-5 border-b border-slate-200/60">
            <h3 class="box-title text-lg font-semibold">Documents</h3>
        </div>
        <div class="box-body">
            @php
                $documents = $school->getMedia('school_certificates');
            @endphp

            @if($documents->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-spacing-y-[10px] border-separate -mt-2">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 font-medium border-b-0 whitespace-nowrap text-slate-700">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="file" />
                                    Document Name
                                </div>
                            </th>
                            <th class="px-5 py-3 font-medium border-b-0 whitespace-nowrap text-slate-700">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="file-type" />
                                    Type
                                </div>
                            </th>
                            <th class="px-5 py-3 font-medium border-b-0 whitespace-nowrap text-slate-700">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="hard-drive" />
                                    Size
                                </div>
                            </th>
                            <th class="px-5 py-3 font-medium border-b-0 whitespace-nowrap text-slate-700">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="calendar" />
                                    Upload Date
                                </div>
                            </th>
                            <th class="px-5 py-3 font-medium border-b-0 whitespace-nowrap text-slate-700 text-center">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $document)
                        <tr class="intro-x" data-document-row="{{ $document->id }}">
                            <td class="px-5 py-3 border-b border-slate-200/60 bg-white shadow-[5px_3px_5px_#00000005] first:rounded-l-md last:rounded-r-md">
                                <div class="flex items-center">
                                    <x-base.lucide class="w-5 h-5 text-primary mr-3" icon="file-text" />
                                    <span class="font-medium text-gray-900">{{ $document->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 border-b border-slate-200/60 bg-white shadow-[5px_3px_5px_#00000005] first:rounded-l-md last:rounded-r-md">
                                <span class="text-sm text-gray-600">{{ strtoupper(pathinfo($document->file_name, PATHINFO_EXTENSION)) }}</span>
                            </td>
                            <td class="px-5 py-3 border-b border-slate-200/60 bg-white shadow-[5px_3px_5px_#00000005] first:rounded-l-md last:rounded-r-md">
                                <span class="text-sm text-gray-600">{{ number_format($document->size / 1024, 2) }} KB</span>
                            </td>
                            <td class="px-5 py-3 border-b border-slate-200/60 bg-white shadow-[5px_3px_5px_#00000005] first:rounded-l-md last:rounded-r-md">
                                <span class="text-sm text-gray-600">{{ $document->created_at->format('m/d/Y') }}</span>
                            </td>
                            <td class="px-5 py-3 border-b border-slate-200/60 bg-white shadow-[5px_3px_5px_#00000005] first:rounded-l-md last:rounded-r-md">
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
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-16 px-4">
                <div class="p-4 bg-slate-100 rounded-full mb-4">
                    <x-base.lucide class="w-12 h-12 text-slate-400" icon="file-x" />
                </div>
                <h3 class="text-lg font-semibold text-slate-700 mb-2">No Documents Found</h3>
                <p class="text-slate-500 text-center mb-6 max-w-md">
                    There are no documents uploaded for this training school yet. You can add documents by editing the training school record.
                </p>
                <x-base.button as="a" href="{{ route('carrier.training-schools.edit', $school->id) }}" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="upload" />
                    Upload Documents
                </x-base.button>
            </div>
            @endif
        </div>
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
