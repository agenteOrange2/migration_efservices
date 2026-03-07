@extends('../themes/' . $activeTheme)
@section('title', 'Edit Traffic Conviction')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Traffic Convictions', 'url' => route('carrier.traffic.index')],
        ['label' => 'Edit', 'active' => true],
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
                <h2 class="text-2xl font-medium">Edit Traffic Conviction</h2>
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
                <x-base.button as="a" href="{{ route('carrier.traffic.documents', $conviction->id) }}"
                    class="w-full sm:w-auto" variant="outline-primary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="FileText" />
                    View Documents
                </x-base.button>
            </div>
        </div>

        <!-- Form -->
        <div class="box box--stacked">
            <div class="box-header">
                <h3 class="box-title">Conviction Details</h3>
            </div>
            <div class="box-body p-5">
                <form action="{{ route('carrier.traffic.update', $conviction) }}" method="POST" 
                    enctype="multipart/form-data" id="editForm">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Driver -->
                        <div>
                            <x-base.form-label for="user_driver_detail_id">
                                Driver <span class="text-danger">*</span>
                            </x-base.form-label>
                            <select id="user_driver_detail_id" name="user_driver_detail_id"
                                class="tom-select w-full @error('user_driver_detail_id') border-danger @enderror"
                                required>
                                <option value="">Select Driver</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}"
                                        {{ old('user_driver_detail_id', $conviction->user_driver_detail_id) == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->user->name . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? '') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_driver_detail_id')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Conviction Date -->
                        <div>
                            <x-base.form-label for="conviction_date">
                                Conviction Date <span class="text-danger">*</span>
                            </x-base.form-label>
                            <x-base.litepicker id="conviction_date" name="conviction_date"
                                value="{{ old('conviction_date', $conviction->conviction_date->format('m/d/Y')) }}"
                                class="@error('conviction_date') border-danger @enderror" 
                                placeholder="MM/DD/YYYY"
                                required />
                            @error('conviction_date')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div>
                            <x-base.form-label for="location">
                                Location <span class="text-danger">*</span>
                            </x-base.form-label>
                            <x-base.form-input id="location" name="location" type="text" 
                                placeholder="Enter location"
                                value="{{ old('location', $conviction->location) }}"
                                class="@error('location') border-danger @enderror"
                                required />
                            @error('location')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Charge -->
                        <div>
                            <x-base.form-label for="charge">
                                Charge <span class="text-danger">*</span>
                            </x-base.form-label>
                            <x-base.form-input id="charge" name="charge" type="text" 
                                placeholder="Enter charge"
                                value="{{ old('charge', $conviction->charge) }}"
                                class="@error('charge') border-danger @enderror"
                                required />
                            @error('charge')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Penalty -->
                        <div class="md:col-span-2">
                            <x-base.form-label for="penalty">
                                Penalty <span class="text-danger">*</span>
                            </x-base.form-label>
                            <x-base.form-input id="penalty" name="penalty" type="text" 
                                placeholder="Enter penalty"
                                value="{{ old('penalty', $conviction->penalty) }}"
                                class="@error('penalty') border-danger @enderror"
                                required />
                            @error('penalty')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Existing Documents -->
                        @php
                            $existingDocuments = $conviction->getMedia('traffic_images');
                        @endphp
                        @if ($existingDocuments->count() > 0)
                            <div class="md:col-span-2">
                                <x-base.form-label>Existing Documents ({{ $existingDocuments->count() }})</x-base.form-label>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
                                    @foreach ($existingDocuments as $media)
                                        <div class="border border-slate-200 rounded-md p-3 bg-slate-50" 
                                            id="media-{{ $media->id }}">
                                            <div class="flex items-start gap-3">
                                                <div class="flex-shrink-0">
                                                    @if (str_contains($media->mime_type, 'image'))
                                                        <img src="{{ $media->getUrl() }}" 
                                                            alt="{{ $media->file_name }}"
                                                            class="w-12 h-12 object-cover rounded">
                                                    @elseif(str_contains($media->mime_type, 'pdf'))
                                                        <div class="w-12 h-12 flex items-center justify-center bg-red-100 rounded">
                                                            <x-base.lucide class="w-6 h-6 text-red-600" icon="file-text" />
                                                        </div>
                                                    @else
                                                        <div class="w-12 h-12 flex items-center justify-center bg-slate-200 rounded">
                                                            <x-base.lucide class="w-6 h-6 text-slate-600" icon="file" />
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-slate-700 truncate" 
                                                        title="{{ $media->file_name }}">
                                                        {{ $media->file_name }}
                                                    </p>
                                                    <p class="text-xs text-slate-500">
                                                        {{ round($media->size / 1024, 2) }} KB
                                                    </p>
                                                    <div class="flex gap-2 mt-2">
                                                        <a href="{{ $media->getUrl() }}" target="_blank"
                                                            class="text-xs text-primary hover:text-primary/80">
                                                            View
                                                        </a>
                                                        <button type="button" 
                                                            onclick="deleteDocument({{ $media->id }})"
                                                            class="text-xs text-danger hover:text-danger/80">
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Upload New Documents -->
                        <div class="md:col-span-2">
                            <x-base.form-label for="documents">
                                Upload New Documents (Optional)
                            </x-base.form-label>
                            <div class="border-2 border-dashed border-slate-200 rounded-md p-6 mt-2">
                                <div class="text-center">
                                    <input type="file" name="documents[]" id="documents" multiple
                                        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                        class="hidden"
                                        onchange="handleFileSelect(event)">
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
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                            @error('documents.*')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-2 mt-6 pt-6 border-t border-slate-200">
                        <x-base.button as="a" href="{{ route('carrier.traffic.index') }}" 
                            variant="outline-secondary">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary" id="submit-btn">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="save" />
                            Update Conviction
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación para Eliminar Documento -->
    <x-base.dialog id="delete-document-modal" size="md">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="x-circle" />
                <div class="mt-5 text-2xl">Are you sure?</div>
                <div class="mt-2 text-slate-500">
                    Do you really want to delete this document? <br>
                    This process cannot be undone.
                </div>
            </div>
            <div class="px-5 pb-8 text-center">
                <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="mr-1 w-24">
                    Cancel
                </x-base.button>
                <x-base.button type="button" variant="danger" class="w-24" id="confirm-delete-btn">
                    Delete
                </x-base.button>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>

    @push('scripts')
        <script>
            // File selection handler - Define globally
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
            
            // Delete document handler - Define globally
            let documentToDelete = null;
            
            window.deleteDocument = function(mediaId) {
                console.log('deleteDocument called with mediaId:', mediaId);
                // Store the media ID to delete
                documentToDelete = mediaId;
                
                // Show the modal
                const modalElement = document.querySelector("#delete-document-modal");
                if (!modalElement) {
                    console.error('Modal element not found!');
                    return;
                }
                
                const modal = tailwind.Modal.getOrCreateInstance(modalElement);
                modal.show();
                console.log('Modal shown');
            };

            // Show notification - Define globally
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
                if (container) {
                    container.insertBefore(notification, container.firstChild);
                    
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                }
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                // Handle confirm delete button in modal
                const confirmBtn = document.getElementById('confirm-delete-btn');
                if (!confirmBtn) {
                    console.error('Confirm delete button not found!');
                    return;
                }
                
                confirmBtn.addEventListener('click', function() {
                    console.log('Delete button clicked, documentToDelete:', documentToDelete);
                    if (!documentToDelete) {
                        console.warn('No document to delete');
                        return;
                    }
                    
                    // Close the modal
                    const modal = tailwind.Modal.getInstance(document.querySelector("#delete-document-modal"));
                    modal.hide();
                    
                    // Perform the delete
                    fetch(`{{ route('carrier.traffic.documents.delete', '') }}/${documentToDelete}`, {
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
                            const mediaElement = document.getElementById(`media-${documentToDelete}`);
                            if (mediaElement) {
                                mediaElement.remove();
                            }
                            
                            // Show success message
                            showNotification('Document deleted successfully', 'success');
                        } else {
                            showNotification(data.message || 'Error deleting document', 'error');
                        }
                        
                        // Reset the document to delete
                        documentToDelete = null;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error deleting document', 'error');
                        documentToDelete = null;
                    });
                });

                // Form submission protection
                const form = document.getElementById('editForm');
                const submitBtn = document.getElementById('submit-btn');
                let isSubmitting = false;

                form.addEventListener('submit', function(e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    
                    isSubmitting = true;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Updating...';
                    
                    // Re-enable after 10 seconds as safety measure
                    setTimeout(() => {
                        isSubmitting = false;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>Update Conviction';
                    }, 10000);
                });
            });
        </script>
    @endpush
@endsection

@pushOnce('scripts')
    @vite('resources/js/components/base/tom-select.js')
@endPushOnce
