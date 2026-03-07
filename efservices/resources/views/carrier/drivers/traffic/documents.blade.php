@extends('../themes/' . $activeTheme)
@section('title', 'Traffic Conviction Documents')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Traffic Convictions', 'url' => route('carrier.traffic.index')],
        ['label' => 'Documents', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="py-5">
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="alert alert-success flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
                {{ session('error') }}
            </div>
        @endif

        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-center mb-8">
            <div class="mr-auto">
                <h2 class="text-2xl font-medium">Traffic Conviction Documents</h2>
                <div class="mt-2 text-slate-500">
                    {{ $conviction->charge }} ({{ $conviction->conviction_date->format('m/d/Y') }})
                </div>
            </div>
            <div class="w-full sm:w-auto flex gap-2 mt-4 sm:mt-0">
                <x-base.button as="a" href="{{ route('carrier.traffic.index') }}" class="w-full sm:w-auto"
                    variant="outline-secondary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back to List
                </x-base.button>
                <x-base.button as="a" href="{{ route('carrier.traffic.edit', $conviction->id) }}"
                    class="w-full sm:w-auto" variant="outline-primary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="edit" />
                    Edit Conviction
                </x-base.button>
            </div>
        </div>

        <!-- Conviction Information -->
        <div class="box box--stacked mb-5">
            <div class="box-header">
                <h3 class="box-title">Conviction Details</h3>
            </div>
            <div class="box-body p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <span class="text-slate-500 text-sm">Driver:</span>
                        <p class="font-medium text-slate-800">
                            {{ $conviction->userDriverDetail->user->name }}
                            {{ $conviction->userDriverDetail->user->last_name ?? '' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-500 text-sm">Conviction Date:</span>
                        <p class="font-medium text-slate-800">
                            {{ $conviction->conviction_date->format('m/d/Y') }}
                        </p>
                    </div>
                    <div>
                        <span class="text-slate-500 text-sm">Location:</span>
                        <p class="font-medium text-slate-800">{{ $conviction->location }}</p>
                    </div>
                    <div>
                        <span class="text-slate-500 text-sm">Charge:</span>
                        <p class="font-medium text-slate-800">{{ $conviction->charge }}</p>
                    </div>
                    <div>
                        <span class="text-slate-500 text-sm">Penalty:</span>
                        <p class="font-medium text-slate-800">{{ $conviction->penalty }}</p>
                    </div>
                    <div>
                        <span class="text-slate-500 text-sm">Registration Date:</span>
                        <p class="font-medium text-slate-800">
                            {{ $conviction->created_at->format('m/d/Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Documents Form -->
        <div class="box box--stacked mb-5">
            <div class="box-header">
                <h3 class="box-title">Upload Documents</h3>
            </div>
            <div class="box-body p-5">
                <form action="{{ route('carrier.traffic.documents.store', $conviction->id) }}" method="POST" 
                    enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <div class="border-2 border-dashed border-slate-200 rounded-md p-6">
                        <div class="text-center">
                            <input type="file" name="documents[]" id="documents" multiple
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                class="hidden"
                                onchange="handleFileSelect(event)" required>
                            <label for="documents" class="cursor-pointer">
                                <x-base.lucide class="mx-auto h-12 w-12 text-slate-400 mb-3" icon="upload-cloud" />
                                <p class="text-sm text-slate-600">
                                    <span class="text-primary font-medium">Click to upload</span> or drag and drop
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    PDF, JPG, PNG, DOC, DOCX (Max 10MB each)
                                </p>
                            </label>
                        </div>
                        <div id="file-preview" class="mt-4 hidden">
                            <div class="text-sm font-medium text-slate-700 mb-2">Selected Files:</div>
                            <div id="file-list" class="space-y-2"></div>
                        </div>
                    </div>
                    @error('documents')
                        <div class="text-danger mt-2 text-sm">{{ $message }}</div>
                    @enderror
                    @error('documents.*')
                        <div class="text-danger mt-2 text-sm">{{ $message }}</div>
                    @enderror
                    <div class="flex justify-end mt-4">
                        <x-base.button type="submit" variant="primary" id="upload-btn">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="upload" />
                            Upload Documents
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Documents List -->
        <div class="box box--stacked">
            <div class="box-header flex items-center justify-between">
                <h3 class="box-title">Documents</h3>
                <span class="text-sm text-slate-500">
                    {{ $documents->count() }} {{ Str::plural('document', $documents->count()) }}
                </span>
            </div>
            <div class="box-body p-5">
                @if ($documents->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($documents as $media)
                            <div class="border border-slate-200 rounded-lg p-4 hover:shadow-md transition-shadow bg-white"
                                id="document-{{ $media->id }}">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0">
                                        @if (str_contains($media->mime_type, 'image'))
                                            <div class="w-16 h-16 rounded-lg overflow-hidden border border-slate-200">
                                                <img src="{{ $media->getUrl() }}" 
                                                    alt="{{ $media->file_name }}"
                                                    class="w-full h-full object-cover">
                                            </div>
                                        @elseif(str_contains($media->mime_type, 'pdf'))
                                            <div class="w-16 h-16 flex items-center justify-center bg-red-50 rounded-lg border border-red-200">
                                                <x-base.lucide class="w-8 h-8 text-red-600" icon="file-text" />
                                            </div>
                                        @elseif(str_contains($media->mime_type, 'word') || str_contains($media->mime_type, 'doc'))
                                            <div class="w-16 h-16 flex items-center justify-center bg-blue-50 rounded-lg border border-blue-200">
                                                <x-base.lucide class="w-8 h-8 text-blue-600" icon="file" />
                                            </div>
                                        @else
                                            <div class="w-16 h-16 flex items-center justify-center bg-slate-50 rounded-lg border border-slate-200">
                                                <x-base.lucide class="w-8 h-8 text-slate-600" icon="file" />
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-800 truncate mb-1" 
                                            title="{{ $media->file_name }}">
                                            {{ $media->file_name }}
                                        </p>
                                        <p class="text-xs text-slate-500 mb-1">
                                            {{ round($media->size / 1024, 2) }} KB
                                        </p>
                                        <p class="text-xs text-slate-500 mb-3">
                                            {{ $media->created_at->format('m/d/Y H:i') }}
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            @if (in_array($media->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/gif']))
                                                <button type="button" 
                                                    onclick="previewDocument({{ $media->id }}, '{{ $media->file_name }}', '{{ $media->mime_type }}')"
                                                    class="text-xs text-primary hover:text-primary/80 font-medium flex items-center gap-1">
                                                    <x-base.lucide class="w-3 h-3" icon="eye" />
                                                    Preview
                                                </button>
                                            @endif
                                            <a href="{{ $media->getUrl() }}" download
                                                class="text-xs text-success hover:text-success/80 font-medium flex items-center gap-1">
                                                <x-base.lucide class="w-3 h-3" icon="download" />
                                                Download
                                            </a>
                                            <button type="button" 
                                                onclick="deleteDocument({{ $media->id }})"
                                                class="text-xs text-danger hover:text-danger/80 font-medium flex items-center gap-1">
                                                <x-base.lucide class="w-3 h-3" icon="trash" />
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-16">
                        <x-base.lucide class="h-16 w-16 text-slate-300 mb-4" icon="file-question" />
                        <p class="text-slate-500 mb-4">No documents found for this traffic conviction</p>
                        <x-base.button as="a" href="{{ route('carrier.traffic.edit', $conviction->id) }}"
                            variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="upload" />
                            Upload Documents
                        </x-base.button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Document Preview Modal -->
    <x-base.dialog id="preview-modal" size="xl" staticBackdrop>
        <x-base.dialog.panel>
            <x-base.dialog.title>
                <h2 class="mr-auto text-base font-medium" id="preview-title">Document Preview</h2>
                <button type="button" data-tw-dismiss="modal" class="text-slate-500 hover:text-slate-700">
                    <x-base.lucide class="w-5 h-5" icon="x" />
                </button>
            </x-base.dialog.title>
            <x-base.dialog.description>
                <div id="preview-content" class="w-full" style="min-height: 500px;">
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <x-base.lucide class="w-12 h-12 text-slate-400 mx-auto mb-3 animate-spin" icon="loader" />
                            <p class="text-slate-500">Loading preview...</p>
                        </div>
                    </div>
                </div>
            </x-base.dialog.description>
            <x-base.dialog.footer>
                <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Close
                </x-base.button>
            </x-base.dialog.footer>
        </x-base.dialog.panel>
    </x-base.dialog>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // File selection handler
                window.handleFileSelect = function(event) {
                    const files = event.target.files;
                    const filePreview = document.getElementById('file-preview');
                    const fileList = document.getElementById('file-list');
                    
                    if (files.length > 0) {
                        filePreview.classList.remove('hidden');
                        fileList.innerHTML = '';
                        
                        Array.from(files).forEach((file, index) => {
                            const fileSize = (file.size / 1024).toFixed(2);
                            const fileItem = document.createElement('div');
                            fileItem.className = 'flex items-center justify-between p-2 bg-slate-50 rounded';
                            fileItem.innerHTML = `
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm text-slate-700">${file.name}</span>
                                    <span class="text-xs text-slate-500">(${fileSize} KB)</span>
                                </div>
                            `;
                            fileList.appendChild(fileItem);
                        });
                    } else {
                        filePreview.classList.add('hidden');
                    }
                };

                // Form submission protection
                const uploadForm = document.getElementById('uploadForm');
                const uploadBtn = document.getElementById('upload-btn');
                let isUploading = false;

                if (uploadForm) {
                    uploadForm.addEventListener('submit', function(e) {
                        if (isUploading) {
                            e.preventDefault();
                            return false;
                        }
                        
                        const fileInput = document.getElementById('documents');
                        if (!fileInput.files || fileInput.files.length === 0) {
                            e.preventDefault();
                            alert('Please select at least one file to upload.');
                            return false;
                        }
                        
                        isUploading = true;
                        uploadBtn.disabled = true;
                        uploadBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Uploading...';
                    });
                }

                // Preview document handler
                window.previewDocument = function(mediaId, fileName, mimeType) {
                    const modal = tailwind.Modal.getOrCreateInstance(document.querySelector("#preview-modal"));
                    const previewTitle = document.getElementById('preview-title');
                    const previewContent = document.getElementById('preview-content');
                    
                    previewTitle.textContent = fileName;
                    previewContent.innerHTML = '<div class="flex items-center justify-center h-full"><div class="text-center"><svg class="w-12 h-12 text-slate-400 mx-auto mb-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><p class="text-slate-500">Loading preview...</p></div></div>';
                    
                    modal.show();
                    
                    const previewUrl = `{{ route('carrier.traffic.documents.preview', '') }}/${mediaId}`;
                    
                    if (mimeType.includes('image')) {
                        previewContent.innerHTML = `<img src="${previewUrl}" alt="${fileName}" class="max-w-full h-auto mx-auto rounded-lg">`;
                    } else if (mimeType === 'application/pdf') {
                        previewContent.innerHTML = `<iframe src="${previewUrl}" class="w-full rounded-lg" style="height: 600px;" frameborder="0"></iframe>`;
                    }
                };

                // Delete document handler
                window.deleteDocument = function(mediaId) {
                    if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                        return;
                    }

                    fetch(`{{ route('carrier.traffic.documents.delete', '') }}/${mediaId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the document element from DOM
                            const documentElement = document.getElementById(`document-${mediaId}`);
                            if (documentElement) {
                                documentElement.remove();
                            }
                            
                            // Update document count
                            const remainingDocs = document.querySelectorAll('[id^="document-"]').length;
                            
                            // If no documents left, show empty state
                            if (remainingDocs === 0) {
                                location.reload();
                            }
                            
                            // Show success message
                            showNotification('Document deleted successfully', 'success');
                        } else {
                            showNotification(data.message || 'Error deleting document', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error deleting document', 'error');
                    });
                };

                // Show notification
                function showNotification(message, type) {
                    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                    const icon = type === 'success' ? 'check-circle' : 'alert-circle';
                    
                    const notification = document.createElement('div');
                    notification.className = `alert ${alertClass} flex items-center mb-5`;
                    notification.innerHTML = `
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            ${type === 'success' ? 
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' :
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                            }
                        </svg>
                        ${message}
                    `;
                    
                    const container = document.querySelector('.py-5');
                    container.insertBefore(notification, container.firstChild);
                    
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                }
            });
        </script>
    @endpush
@endsection
