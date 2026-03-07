@extends('../themes/' . $activeTheme)
@section('title', 'Add Training')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Trainings', 'url' => route('carrier.trainings.index')],
        ['label' => 'Add', 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success-soft show flex items-center mb-5" role="alert">
            <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
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
                    <span class="text-slate-600">Add New Training</span>
                </div>
                <div class="flex flex-wrap gap-2 mt-4 md:mt-0">
                    <x-base.button as="a" href="{{ route('carrier.trainings.index') }}" variant="outline-secondary"
                        class="w-full sm:w-auto">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                        Back to Trainings
                    </x-base.button>
                </div>
            </div>

            <!-- Form -->
            <div class="box box--stacked mt-5">
                <form id="trainingForm" action="{{ route('carrier.trainings.store') }}" method="POST"
                    enctype="multipart/form-data" data-training-form="creating">
                    @csrf

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
                                <x-base.form-label for="title">Title <span
                                        class="text-danger">*</span></x-base.form-label>
                                <x-base.form-input type="text" id="title" name="title"
                                    class="@error('title')  @enderror" value="{{ old('title') }}"
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
                                        required>{{ old('description') }}</x-base.form-textarea>
                                    @error('description')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <x-base.form-label for="content_type">Content Type <span
                                        class="text-danger">*</span></x-base.form-label>
                                <x-base.form-select id="content_type" name="content_type"
                                    class="@error('content_type')  @enderror" required>
                                    <option value="">Select Content Type</option>
                                    <option value="file" {{ old('content_type') == 'file' ? 'selected' : '' }}>File
                                    </option>
                                    <option value="video" {{ old('content_type') == 'video' ? 'selected' : '' }}>Video
                                    </option>
                                    <option value="url" {{ old('content_type') == 'url' ? 'selected' : '' }}>URL
                                    </option>
                                </x-base.form-select>
                                @error('content_type')
                                    <div class="text-danger text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-base.form-label for="status">Status <span
                                        class="text-danger">*</span></x-base.form-label>
                                <x-base.form-select id="status" name="status"
                                    class="@error('status')  @enderror" required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                        Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive
                                    </option>
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
                                class="@error('video_url')  @enderror" value="{{ old('video_url') }}"
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
                                class="@error('url')  @enderror" value="{{ old('url') }}"
                                placeholder="https://example.com/training-material" />
                            <p class="text-slate-500 text-xs mt-1">Enter a valid external URL for the training material</p>
                            @error('url')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- File Upload -->
                        <div id="files_container" style="display: none;">
                            @livewire('components.file-uploader', [
                                'modelName' => 'training_files',
                                'modelIndex' => 0,
                                'label' => 'Upload Training Files',
                                'existingFiles' => [],
                            ])
                            <input type="hidden" id="files_data" name="files_data" value="">
                            @error('files_data')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="no_content_type_message" class="text-slate-500 text-sm p-3 bg-slate-50 rounded-lg">
                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="Info" />
                            Please select a content type above to configure the training content.
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="p-5 border-t border-slate-200/60 bg-slate-50 flex justify-end gap-3">
                        <x-base.button as="a" href="{{ route('carrier.trainings.index') }}"
                            variant="outline-secondary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="X" />
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Save" />
                            Save Training
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
            });

            let uploadedFiles = [];
            window.addEventListener('livewire:initialized', () => {
                Livewire.on('fileUploaded', (data) => {
                    const fileData = data[0];
                    if (fileData.modelName === 'training_files') {
                        uploadedFiles.push({
                            name: fileData.originalName,
                            original_name: fileData.originalName,
                            mime_type: fileData.mimeType,
                            size: fileData.size,
                            path: fileData.tempPath,
                            tempPath: fileData.tempPath,
                            is_temp: true
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
