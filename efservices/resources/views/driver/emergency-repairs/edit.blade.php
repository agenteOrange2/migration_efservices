@extends('../themes/' . $activeTheme)
@section('title', 'Edit Emergency Repair')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Emergency Repairs', 'url' => route('driver.emergency-repairs.index')],
        ['label' => 'Edit Emergency Repair', 'active' => true],
    ];
@endphp
@section('subcontent')
    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success flex items-center mb-5">
            <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger flex items-center mb-5">
            <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-5">
            <div class="flex items-center mb-2">
                <x-base.lucide class="w-6 h-6 mr-2" icon="AlertTriangle" />
                <span class="font-semibold">Please correct the following errors:</span>
            </div>
            <ul class="list-disc list-inside ml-8">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Edit Emergency Repair: {{ $emergencyRepair->repair_name }}
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('driver.emergency-repairs.show', $emergencyRepair->id) }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Eye" />
                        View Details
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('driver.emergency-repairs.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                </div>
            </div>

            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    <!-- Vehicle Information -->
                    <div class="bg-slate-50 dark:bg-darkmode-800 p-4 rounded-lg mb-6 border border-slate-200/60 dark:border-darkmode-400">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <x-base.lucide class="w-6 h-6 text-primary" icon="Truck" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800 dark:text-slate-200">
                                    @if($vehicle->company_unit_number)
                                        {{ $vehicle->company_unit_number }} - 
                                    @endif
                                    {{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}
                                </h3>
                                <p class="text-sm text-slate-500 mt-1">
                                    VIN: {{ $vehicle->vin ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <form id="emergency-repair-form" action="{{ route('driver.emergency-repairs.update', $emergencyRepair->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <!-- Repair Name -->
                            <div>
                                <x-base.form-label for="repair_name">Repair Name *</x-base.form-label>
                                <x-base.form-input id="repair_name" name="repair_name" type="text" 
                                    class="w-full @error('repair_name') border-primary @enderror" 
                                    placeholder="e.g., Brake Failure, Engine Problem" 
                                    value="{{ old('repair_name', $emergencyRepair->repair_name) }}" required />
                                @error('repair_name')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Repair Date -->
                            <div>
                                <x-base.form-label for="repair_date">Repair Date *</x-base.form-label>
                                <input type="text" id="repair_date" name="repair_date" 
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 datepicker @error('repair_date') border-red-500 @enderror"
                                    data-single-mode="true"
                                    data-format="MM/DD/YYYY"
                                    value="{{ old('repair_date', $emergencyRepair->repair_date ? \Carbon\Carbon::parse($emergencyRepair->repair_date)->format('m/d/Y') : '') }}" 
                                    placeholder="MM/DD/YYYY" />
                                @error('repair_date')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Cost -->
                            <div>
                                <x-base.form-label for="cost">Cost *</x-base.form-label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500">$</span>
                                    <x-base.form-input id="cost" name="cost" type="number" step="0.01" min="0"
                                        class="w-full pl-8 @error('cost') border-red-500 @enderror" 
                                        placeholder="0.00" 
                                        value="{{ old('cost', $emergencyRepair->cost) }}" required />
                                </div>
                                @error('cost')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Odometer -->
                            <div>
                                <x-base.form-label for="odometer">Odometer (miles)</x-base.form-label>
                                <x-base.form-input id="odometer" name="odometer" type="number" min="0"
                                    class="w-full @error('odometer') border-red-500 @enderror" 
                                    placeholder="e.g., 125000" 
                                    value="{{ old('odometer', $emergencyRepair->odometer) }}" />
                                @error('odometer')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <x-base.form-label for="status">Status *</x-base.form-label>
                                <select id="status" name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('status') border-red-500 @enderror" required>
                                    <option value="pending" {{ (old('status', $emergencyRepair->status) == 'pending') ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ (old('status', $emergencyRepair->status) == 'in_progress') ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ (old('status', $emergencyRepair->status) == 'completed') ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('status')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 mt-6">
                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Description -->
                                <div>
                                    <x-base.form-label for="description">Description</x-base.form-label>
                                    <x-base.form-textarea id="description" name="description" rows="4"
                                        class="w-full @error('description') border-red-500 @enderror" 
                                        placeholder="Describe the emergency repair...">{{ old('description', $emergencyRepair->description) }}</x-base.form-textarea>
                                    @error('description')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div>
                                    <x-base.form-label for="notes">Additional Notes</x-base.form-label>
                                    <x-base.form-textarea id="notes" name="notes" rows="4"
                                        class="w-full @error('notes') border-red-500 @enderror" 
                                        placeholder="Any additional notes...">{{ old('notes', $emergencyRepair->notes) }}</x-base.form-textarea>
                                    @error('notes')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Existing Files - Display only, delete handled separately -->
                                @if($emergencyRepair->getMedia('emergency_repair_files')->count() > 0)
                                    <div>
                                        <x-base.form-label>Current Files</x-base.form-label>
                                        <div class="space-y-2 mt-2">
                                            @foreach($emergencyRepair->getMedia('emergency_repair_files') as $media)
                                                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-darkmode-800 rounded-lg border border-slate-200/60 dark:border-darkmode-400">
                                                    <div class="flex items-center gap-3">
                                                        @if(str_starts_with($media->mime_type, 'image/'))
                                                            <x-base.lucide class="w-5 h-5 text-blue-500" icon="Image" />
                                                        @elseif($media->mime_type === 'application/pdf')
                                                            <x-base.lucide class="w-5 h-5 text-red-500" icon="FileText" />
                                                        @else
                                                            <x-base.lucide class="w-5 h-5 text-slate-500" icon="File" />
                                                        @endif
                                                        <div>
                                                            <div class="text-sm font-medium text-slate-800 dark:text-slate-200">
                                                                <a href="{{ $media->getUrl() }}" target="_blank" class="text-primary hover:underline">
                                                                    {{ $media->file_name }}
                                                                </a>
                                                            </div>
                                                            <div class="text-xs text-slate-500">
                                                                {{ $media->human_readable_size }}
                                                                @if($media->getCustomProperty('uploaded_by_driver'))
                                                                    • Uploaded by you
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <a href="{{ $media->getUrl() }}" target="_blank" 
                                                           class="text-blue-500 hover:text-blue-700" title="View">
                                                            <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                                                        </a>
                                                        @if($media->getCustomProperty('uploaded_by_driver') && $media->getCustomProperty('driver_id') == $driver->id)
                                                        <button type="button" class="text-red-500 hover:text-red-700 delete-doc-btn" 
                                                                data-url="{{ route('driver.emergency-repairs.delete-document', [$emergencyRepair->id, $media->id]) }}"
                                                                title="Delete">
                                                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                                        </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- File Upload -->
                                <div>
                                    <x-base.form-label for="repair_files">Add New Photos/Documents</x-base.form-label>
                                    <div class="border-2 border-dashed border-slate-200/60 dark:border-darkmode-400 rounded-md p-5 mt-2" id="file-upload-area">
                                        <div class="h-32 relative w-full cursor-pointer">
                                            <input type="file" id="repair_files" name="repair_files[]" multiple 
                                                accept="image/*,.pdf,.doc,.docx" 
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                                            <div class="flex flex-col items-center justify-center h-full text-slate-500 pointer-events-none">
                                                <x-base.lucide class="w-8 h-8 mb-2" icon="Upload" />
                                                <div class="text-sm font-medium">Arrastra archivos aquí o haz clic para subir</div>
                                                <div class="text-xs mt-1">Formatos soportados: Imágenes, PDF, DOC, DOCX (Máx. 10MB cada uno)</div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- File list OUTSIDE the upload area to avoid z-index issues -->
                                    <div id="file-list" class="mt-3 space-y-2"></div>
                                    @error('repair_files')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                    @error('repair_files.*')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-slate-200/60 dark:border-darkmode-400">
                            <a href="{{ route('driver.emergency-repairs.show', $emergencyRepair->id) }}" 
                                class="btn btn-outline-secondary w-full sm:w-24 text-center">
                                Cancelar
                            </a>
                            <button type="submit" id="submit-btn" class="btn btn-primary w-full sm:w-64 flex items-center justify-center gap-2">
                                <x-base.lucide class="h-4 w-4 stroke-[1.3]" icon="Save" />
                                Actualizar Reparación
                            </button>
                        </div>
                    </form>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Handle delete document buttons (to avoid nested forms)
                            document.querySelectorAll('.delete-doc-btn').forEach(function(btn) {
                                btn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    
                                    if (confirm('¿Estás seguro de que deseas eliminar este documento?')) {
                                        const url = this.getAttribute('data-url');
                                        const deleteForm = document.createElement('form');
                                        deleteForm.method = 'POST';
                                        deleteForm.action = url;
                                        deleteForm.style.display = 'none';
                                        
                                        const csrfInput = document.createElement('input');
                                        csrfInput.type = 'hidden';
                                        csrfInput.name = '_token';
                                        csrfInput.value = '{{ csrf_token() }}';
                                        deleteForm.appendChild(csrfInput);
                                        
                                        const methodInput = document.createElement('input');
                                        methodInput.type = 'hidden';
                                        methodInput.name = '_method';
                                        methodInput.value = 'DELETE';
                                        deleteForm.appendChild(methodInput);
                                        
                                        document.body.appendChild(deleteForm);
                                        deleteForm.submit();
                                    }
                                });
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const fileInput = document.getElementById('repair_files');
                const fileList = document.getElementById('file-list');
                const uploadArea = document.getElementById('file-upload-area');

                // Only add event listeners if elements exist
                if (fileInput) {
                    // File upload handling
                    fileInput.addEventListener('change', function () {
                        displaySelectedFiles(this.files);
                    });
                }

                if (uploadArea) {
                    // Drag and drop functionality
                    uploadArea.addEventListener('dragover', function (e) {
                        e.preventDefault();
                        this.classList.add('border-primary');
                    });

                    uploadArea.addEventListener('dragleave', function (e) {
                        e.preventDefault();
                        this.classList.remove('border-primary');
                    });

                    uploadArea.addEventListener('drop', function (e) {
                        e.preventDefault();
                        this.classList.remove('border-primary');
                        if (fileInput) {
                            fileInput.files = e.dataTransfer.files;
                            displaySelectedFiles(e.dataTransfer.files);
                        }
                    });
                }

                function displaySelectedFiles(files) {
                    if (!fileList) return;
                    fileList.innerHTML = '';
                    
                    Array.from(files).forEach((file, index) => {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'flex items-center justify-between p-3 bg-slate-50 dark:bg-darkmode-800 rounded-lg border border-slate-200/60';
                        
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'flex items-center gap-2';
                        
                        const iconClass = getFileIcon(file.type);
                        fileInfo.innerHTML = `
                            <i class="${iconClass}"></i>
                            <div>
                                <div class="text-sm font-medium text-slate-800 dark:text-slate-200">${file.name}</div>
                                <div class="text-xs text-slate-500">${formatFileSize(file.size)}</div>
                            </div>
                        `;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'text-red-500 hover:text-red-700 ml-2';
                        removeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>';
                        removeBtn.onclick = () => removeFile(index);
                        
                        fileItem.appendChild(fileInfo);
                        fileItem.appendChild(removeBtn);
                        fileList.appendChild(fileItem);
                    });
                }

                function getFileIcon(fileType) {
                    if (fileType.startsWith('image/')) return 'lucide lucide-image w-5 h-5 text-blue-500';
                    if (fileType === 'application/pdf') return 'lucide lucide-file-text w-5 h-5 text-red-500';
                    if (fileType.includes('word') || fileType.includes('document')) return 'lucide lucide-file w-5 h-5 text-blue-600';
                    return 'lucide lucide-file w-5 h-5 text-slate-500';
                }

                function formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }

                function removeFile(index) {
                    const dt = new DataTransfer();
                    const files = Array.from(fileInput.files);
                    
                    files.forEach((file, i) => {
                        if (i !== index) dt.items.add(file);
                    });
                    
                    fileInput.files = dt.files;
                    displaySelectedFiles(fileInput.files);
                }
            });
        </script>
    @endpush
@endsection

