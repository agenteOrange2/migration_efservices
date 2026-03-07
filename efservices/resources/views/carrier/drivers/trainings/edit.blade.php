@extends('../themes/' . $activeTheme)
@section('title', 'Edit Training')
@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Trainings', 'url' => route('carrier.trainings.index')],
    ['label' => 'Edit', 'active' => true],
];
@endphp

@section('subcontent')
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success-soft show flex items-center mb-5" role="alert">
            <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger-soft show flex items-center mb-5" role="alert">
            <x-base.lucide class="w-6 h-6 mr-2" icon="AlertOctagon" />
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-y-10">
        <div class="col-span-12">
            <!-- Header -->
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="text-xl font-medium">
                    <span class="text-slate-600">Edit Training</span>
                </div>
                <div class="flex flex-wrap gap-2 mt-4 md:mt-0">
                    <x-base.button as="a" href="{{ route('carrier.trainings.show', $training->id) }}" variant="outline-secondary" class="w-full sm:w-auto">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                        Back to Training
                    </x-base.button>
                </div>
            </div>

            <!-- Form -->
            <div class="box box--stacked mt-5">
                <form id="trainingForm" action="{{ route('carrier.trainings.update', $training->id) }}" method="POST" enctype="multipart/form-data" data-training-form="updating">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information -->
                    <div class="box-header">
                        <div class="box-title p-5 border-b border-slate-200/60 bg-slate-50">
                            <div class="flex items-center">
                                <x-base.lucide class="w-5 h-5 mr-2 text-primary" icon="Info" />
                                <span class="text-base font-medium">Basic Information</span>
                            </div>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            <div class="lg:col-span-2">
                                <x-base.form-label for="title">Title <span class="text-danger">*</span></x-base.form-label>
                                <x-base.form-input type="text" id="title" name="title" 
                                    class="@error('title')  @enderror" 
                                    value="{{ old('title', $training->title) }}" 
                                    placeholder="Enter training title" maxlength="255" required />
                                @error('title')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="lg:col-span-2">
                                <div class="mt-3">
                                <x-base.form-label for="description">Description <span
                                        class="text-danger">*</span></x-base.form-label>
                                    <x-base.form-textarea id="description" name="description"
                                        class="w-full @error('description')  @enderror" rows="3"
                                        placeholder="Description"
                                        required>{{ old('description', $training->description) }}</x-base.form-textarea>
                                    @error('description')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <x-base.form-label for="content_type">Content Type <span class="text-danger">*</span></x-base.form-label>
                                <x-base.form-select id="content_type" name="content_type" 
                                    class="@error('content_type')  @enderror" required>
                                    <option value="">Select Content Type</option>
                                    <option value="file" {{ old('content_type', $training->content_type) == 'file' ? 'selected' : '' }}>File</option>
                                    <option value="video" {{ old('content_type', $training->content_type) == 'video' ? 'selected' : '' }}>Video</option>
                                    <option value="url" {{ old('content_type', $training->content_type) == 'url' ? 'selected' : '' }}>URL</option>
                                </x-base.form-select>
                                @error('content_type')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-base.form-label for="status">Status <span class="text-danger">*</span></x-base.form-label>
                                <x-base.form-select id="status" name="status" 
                                    class="@error('status')  @enderror" required>
                                    <option value="active" {{ old('status', $training->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $training->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </x-base.form-select>
                                @error('status')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Content Details -->
                    <div class="box-header">
                        <div class="box-title p-5 border-b border-t border-slate-200/60 bg-slate-50">
                            <div class="flex items-center">
                                <x-base.lucide class="w-5 h-5 mr-2 text-primary" icon="FileText" />
                                <span class="text-base font-medium">Content Details</span>
                            </div>
                        </div>
                    </div>
                    <div class="p-5">
                        <!-- Video URL -->
                        <div id="video_url_container" class="mb-5" style="display: none;">
                            <x-base.form-label for="video_url">Video URL</x-base.form-label>
                            <x-base.form-input type="url" id="video_url" name="video_url" 
                                class="@error('video_url')  @enderror" 
                                value="{{ old('video_url', $training->video_url) }}" 
                                placeholder="https://www.youtube.com/watch?v=..." />
                            <p class="text-slate-500 text-xs mt-1">Enter a valid video URL (YouTube, Vimeo, etc.)</p>
                            @error('video_url')
                            <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- External URL -->
                        <div id="url_container" class="mb-5" style="display: none;">
                            <x-base.form-label for="url">External URL</x-base.form-label>
                            <x-base.form-input type="url" id="url" name="url" 
                                class="@error('url')  @enderror" 
                                value="{{ old('url', $training->url) }}" 
                                placeholder="https://example.com/training-material" />
                            <p class="text-slate-500 text-xs mt-1">Enter a valid external URL for the training material</p>
                            @error('url')
                            <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- File Upload -->
                        <div id="files_container" style="display: none;">
                            @if($existingFiles->count() > 0)
                            <div class="mb-5">
                                <div class="text-sm font-medium text-slate-600 mb-3">Existing Files</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($existingFiles as $file)
                                    <div class="border border-slate-200/60 rounded-lg p-4 bg-slate-50/30 hover:bg-slate-50 transition-colors" data-file-id="{{ $file->id }}">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="p-2 bg-primary/10 rounded-lg">
                                                <x-base.lucide class="w-4 h-4 text-primary" icon="FileText" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-slate-800 truncate" title="{{ $file->file_name }}">{{ $file->file_name }}</p>
                                                <p class="text-xs text-slate-500">{{ number_format($file->size / 1024, 2) }} KB</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="{{ route('carrier.trainings.documents.preview', $file->id) }}" target="_blank"
                                               class="flex-1 inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-primary bg-primary/10 rounded-md hover:bg-primary/20 transition-colors">
                                                <x-base.lucide class="w-3 h-3 mr-1" icon="Eye" />
                                                Preview
                                            </a>
                                            <button type="button" 
                                                    class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium text-danger bg-danger/10 rounded-md hover:bg-danger/20 transition-colors delete-file-btn"
                                                    data-file-id="{{ $file->id }}"
                                                    data-file-name="{{ $file->file_name }}">
                                                <x-base.lucide class="w-3 h-3 mr-1" icon="Trash2" />
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div class="mb-4">
                                <div class="text-sm font-medium text-slate-600 mb-3">Add New Files</div>
                                @livewire('components.file-uploader', [
                                    'modelName' => 'training_files',
                                    'modelIndex' => 0,
                                    'label' => 'Upload Additional Training Files',
                                    'existingFiles' => []
                                ])
                                <input type="hidden" id="files_data" name="files_data" value="">
                                @error('files_data')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div id="no_content_type_message" class="text-slate-500 text-sm p-3 bg-slate-50 rounded-lg">
                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="Info" />
                            Please select a content type above to configure the training content.
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="p-5 border-t border-slate-200/60 bg-slate-50 flex justify-end gap-3">
                        <x-base.button as="a" href="{{ route('carrier.trainings.show', $training->id) }}" variant="outline-secondary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="X" />
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Save" />
                            Update Training
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const contentTypeSelect = document.getElementById('content_type');
        const videoUrlContainer = document.getElementById('video_url_container');
        const urlContainer = document.getElementById('url_container');
        const filesContainer = document.getElementById('files_container');
        const noContentTypeMessage = document.getElementById('no_content_type_message');
        const videoUrlInput = document.getElementById('video_url');
        const urlInput = document.getElementById('url');
        
        function toggleContentFields() {
            const contentType = contentTypeSelect.value;
            videoUrlContainer.style.display = 'none';
            urlContainer.style.display = 'none';
            filesContainer.style.display = 'none';
            noContentTypeMessage.style.display = 'none';
            videoUrlInput.required = false;
            urlInput.required = false;
            
            if (contentType === 'video') {
                videoUrlContainer.style.display = 'block';
                videoUrlInput.required = true;
            } else if (contentType === 'url') {
                urlContainer.style.display = 'block';
                urlInput.required = true;
            } else if (contentType === 'file') {
                filesContainer.style.display = 'block';
            } else {
                noContentTypeMessage.style.display = 'block';
            }
        }
        
        toggleContentFields();
        contentTypeSelect.addEventListener('change', toggleContentFields);

        // Handle existing file deletion via AJAX
        document.querySelectorAll('.delete-file-btn').forEach(button => {
            button.addEventListener('click', function() {
                const fileId = this.getAttribute('data-file-id');
                const fileName = this.getAttribute('data-file-name');
                
                if (!confirm(`Are you sure you want to delete "${fileName}"?`)) return;
                
                this.disabled = true;
                this.innerHTML = 'Deleting...';
                
                fetch(`{{ url('carrier/trainings/documents') }}/${fileId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const fileCard = document.querySelector(`[data-file-id="${fileId}"]`);
                        if (fileCard) {
                            fileCard.style.transition = 'opacity 0.3s';
                            fileCard.style.opacity = '0';
                            setTimeout(() => fileCard.remove(), 300);
                        }
                    } else {
                        alert(data.message || 'Failed to delete file');
                        this.disabled = false;
                        this.innerHTML = 'Delete';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the file');
                    this.disabled = false;
                    this.innerHTML = 'Delete';
                });
            });
        });
    });
    
    let uploadedFiles = [];
    window.addEventListener('livewire:initialized', () => {
        Livewire.on('fileUploaded', (data) => {
            const fileData = data[0];
            if (fileData.modelName === 'training_files') {
                uploadedFiles.push({
                    name: fileData.originalName, original_name: fileData.originalName,
                    mime_type: fileData.mimeType, size: fileData.size,
                    path: fileData.tempPath, tempPath: fileData.tempPath, is_temp: true
                });
                document.getElementById('files_data').value = JSON.stringify(uploadedFiles);
            }
        });
        Livewire.on('fileRemoved', (data) => {
            const fileData = data[0];
            if (fileData.modelName === 'training_files') {
                uploadedFiles = uploadedFiles.filter(file => file.tempPath !== fileData.tempPath);
                document.getElementById('files_data').value = JSON.stringify(uploadedFiles);
            }
        });
    });
</script>
@endPush

@endsection
