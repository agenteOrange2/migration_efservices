@extends('../themes/' . $activeTheme)
@section('title', 'Course Documents')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Courses', 'url' => route('admin.courses.index')],
        ['label' => $course->organization_name, 'url' => route('admin.courses.edit', $course)],
        ['label' => 'Documents', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="alert alert-success flex items-center mb-5">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger flex items-center mb-5">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
                    {{ session('error') }}
                </div>
            @endif

            <!-- Professional Header -->
            <div class="box box--stacked p-4 sm:p-6 lg:p-8 mb-6 lg:mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 lg:gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-3 lg:gap-4">
                        <div class="p-2 sm:p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-6 h-6 sm:w-8 sm:h-8 text-primary" icon="FileText" />
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-800 mb-1 sm:mb-2">
                                {{ $course->organization_name }}
                            </h1>
                            <p class="text-sm sm:text-base text-slate-600">
                                Course Documents
                                @if($course->driverDetail && $course->driverDetail->user)
                                    <span class="text-slate-400">•</span>
                                    <span class="text-primary">
                                        {{ $course->driverDetail->user->name ?? '' }}
                                        {{ $course->driverDetail->middle_name ?? '' }}
                                        {{ $course->driverDetail->last_name ?? '' }}
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-2 w-full justify-end">
                        <x-base.button as="a" href="{{ route('admin.courses.all-documents') }}"
                            variant="outline-primary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Files" />
                            All Documents
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.courses.edit', $course) }}"
                            variant="outline-secondary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                            Back to Course
                        </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Course Info Card -->
            <div class="box box--stacked p-5 mb-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="User" />
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Driver</div>
                            <div class="font-medium text-slate-800">
                                @if($course->driverDetail && $course->driverDetail->user)
                                    {{ $course->driverDetail->user->name ?? '' }}
                                    {{ $course->driverDetail->middle_name ?? '' }}
                                    {{ $course->driverDetail->last_name ?? '' }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="Calendar" />
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Certification Date</div>
                            <div class="font-medium text-slate-800">
                                {{ $course->certification_date ? $course->certification_date->format('M d, Y') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="Clock" />
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Expiration Date</div>
                            <div class="font-medium text-slate-800">
                                {{ $course->expiration_date ? $course->expiration_date->format('M d, Y') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="Files" />
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Total Documents</div>
                            <div class="font-medium text-slate-800">{{ $documents->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Grid/Table -->
            <div class="box box--stacked">
                <div class="box-body p-5">
                    @if($documents->count() > 0)
                        <!-- Grid View for Documents -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($documents as $document)
                                @php
                                    $extension = strtolower(pathinfo($document->file_name, PATHINFO_EXTENSION));
                                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $isPdf = $extension === 'pdf';
                                    $iconName = 'File';
                                    $iconColor = 'text-slate-500';
                                    $bgColor = 'bg-slate-100';
                                    
                                    if ($isImage) {
                                        $iconName = 'Image';
                                        $iconColor = 'text-success';
                                        $bgColor = 'bg-success/10';
                                    } elseif ($isPdf) {
                                        $iconName = 'FileText';
                                        $iconColor = 'text-danger';
                                        $bgColor = 'bg-danger/10';
                                    } elseif (in_array($extension, ['doc', 'docx'])) {
                                        $iconName = 'FileType';
                                        $iconColor = 'text-primary';
                                        $bgColor = 'bg-primary/10';
                                    }
                                @endphp
                                <div class="border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-3">
                                        <!-- File Preview/Icon -->
                                        <div class="w-16 h-16 rounded-lg {{ $bgColor }} flex items-center justify-center flex-shrink-0 overflow-hidden">
                                            @if($isImage)
                                                <img src="{{ $document->getUrl() }}" alt="{{ $document->file_name }}" 
                                                    class="w-full h-full object-cover rounded-lg">
                                            @else
                                                <x-base.lucide class="w-8 h-8 {{ $iconColor }}" icon="{{ $iconName }}" />
                                            @endif
                                        </div>
                                        
                                        <!-- File Info -->
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-slate-800 truncate" title="{{ $document->file_name }}">
                                                {{ Str::limit($document->file_name, 25) }}
                                            </h4>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="px-2 py-0.5 text-xs font-medium rounded {{ $bgColor }} {{ $iconColor }}">
                                                    {{ strtoupper($extension) }}
                                                </span>
                                                <span class="text-xs text-slate-500">
                                                    {{ number_format($document->size / 1024, 1) }} KB
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-400 mt-1">
                                                {{ $document->created_at ? $document->created_at->format('M d, Y') : '' }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex items-center justify-end gap-2 mt-3 pt-3 border-t border-slate-100">
                                        <a href="{{ route('admin.courses.documents.preview', $document->id) }}" 
                                            target="_blank"
                                            class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                            title="View">
                                            <x-base.lucide class="w-4 h-4" icon="Eye" />
                                        </a>
                                        <a href="{{ $document->getUrl() }}" 
                                            download
                                            class="inline-flex items-center justify-center w-8 h-8 text-success hover:bg-success/10 rounded-lg transition-colors"
                                            title="Download">
                                            <x-base.lucide class="w-4 h-4" icon="Download" />
                                        </a>
                                        <button type="button" 
                                            onclick="confirmDelete({{ $document->id }}, '{{ addslashes($document->file_name) }}')"
                                            class="inline-flex items-center justify-center w-8 h-8 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                            title="Delete">
                                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="text-center py-12">
                            <div class="flex flex-col items-center">
                                <x-base.lucide class="w-16 h-16 text-slate-300 mb-4" icon="FileX" />
                                <h3 class="text-lg font-medium text-slate-600 mb-2">No documents found</h3>
                                <p class="text-slate-400 text-center max-w-sm mb-4">
                                    This course doesn't have any documents attached yet.
                                </p>
                                <x-base.button as="a" href="{{ route('admin.courses.edit', $course) }}"
                                    variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Upload" />
                                    Upload Documents
                                </x-base.button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <x-base.dialog id="deleteModal">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="w-16 h-16 mx-auto mt-3 text-danger" icon="XCircle" />
                <div class="mt-5 text-3xl">Are you sure?</div>
                <div class="mt-2 text-slate-500" id="delete-message">
                    Do you really want to delete this document? <br>
                    This process cannot be undone.
                </div>
            </div>
            <div class="px-5 pb-8 text-center">
                <x-base.button class="w-24 mr-1" data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Cancel
                </x-base.button>
                <x-base.button class="w-24" type="button" variant="danger" id="confirmDeleteBtn">
                    Delete
                </x-base.button>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>
@endsection

@push('scripts')
<script>
    let deleteDocumentId = null;

    function confirmDelete(id, fileName) {
        deleteDocumentId = id;
        document.getElementById('delete-message').innerHTML = 
            `Do you really want to delete "<strong>${fileName}</strong>"? <br>This process cannot be undone.`;
        const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteModal'));
        modal.show();
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteDocumentId) {
            fetch('{{ route("api.documents.delete.post") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ mediaId: deleteDocumentId })
            })
            .then(response => response.json())
            .then(data => {
                const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteModal'));
                modal.hide();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error deleting document');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing request');
            });
        }
    });
</script>
@endpush
