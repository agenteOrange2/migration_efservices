@extends('../themes/' . $activeTheme)
@section('title', 'Edit Repair')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Repairs', 'url' => route('carrier.emergency-repairs.index')],
        ['label' => 'Edit', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Edit Repair: {{ $emergencyRepair->repair_name }}
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('carrier.emergency-repairs.show', $emergencyRepair) }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Eye" />
                        View Details
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.emergency-repairs.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                </div>
            </div>

            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    <form action="{{ route('carrier.emergency-repairs.update', $emergencyRepair) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 ">
                            <!-- Left Column -->                            
                                <!-- Vehicle Selection -->
                                <div>
                                    <x-base.form-label for="vehicle_id">Vehicle *</x-base.form-label>
                                    <select id="vehicle_id" name="vehicle_id" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('vehicle_id') border-red-500 @enderror" required>
                                        <option value="">Select Vehicle</option>
                                        @foreach ($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}" {{ (old('vehicle_id', $emergencyRepair->vehicle_id) == $vehicle->id) ? 'selected' : '' }}>
                                                {{ $vehicle->make }} {{ $vehicle->model }} - {{ $vehicle->company_unit_number ?? $vehicle->vin }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vehicle_id')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                    
                                    <!-- Driver Information Display -->
                                    <div id="driver-info" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-md hidden">
                                        <div class="text-sm font-medium text-blue-900 mb-2">
                                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="User" />
                                            Assigned Driver
                                        </div>
                                        <div class="space-y-1 text-sm text-slate-600">
                                            <div><span class="font-medium">Name:</span> <span id="driver-name">-</span></div>
                                            <div><span class="font-medium">Email:</span> <span id="driver-email">-</span></div>
                                            <div><span class="font-medium">Phone:</span> <span id="driver-phone">-</span></div>
                                        </div>
                                    </div>
                                    <div id="no-driver-info" class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-md hidden">
                                        <div class="text-sm text-slate-600">
                                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="AlertCircle" />
                                            No driver assigned to this vehicle
                                        </div>
                                    </div>
                                </div>

                                <!-- Repair Name -->
                                <div>
                                    <x-base.form-label for="repair_name">Repair Name *</x-base.form-label>
                                    <x-base.form-input id="repair_name" name="repair_name" type="text" 
                                        class="w-full @error('repair_name') border-primary @enderror" 
                                        placeholder="Enter repair name" value="{{ old('repair_name', $emergencyRepair->repair_name) }}" required />
                                    @error('repair_name')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Repair Date -->
                                <div>
                                    <x-base.form-label for="repair_date">Repair Date *</x-base.form-label>
                                    <x-base.litepicker id="repair_date" name="repair_date" 
                                        class="@error('repair_date') border-red-500 @enderror"
                                        value="{{ old('repair_date', $emergencyRepair->repair_date->format('m/d/Y')) }}" placeholder="MM/DD/YYYY" required />
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
                                            placeholder="0.00" value="{{ old('cost', $emergencyRepair->cost) }}" required />
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
                                        placeholder="e.g., 125000" value="{{ old('odometer', $emergencyRepair->odometer) }}" />
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
                                        placeholder="Enter repair description">{{ old('description', $emergencyRepair->description) }}</x-base.form-textarea>
                                    @error('description')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div>
                                    <x-base.form-label for="notes">Notes</x-base.form-label>
                                    <x-base.form-textarea id="notes" name="notes" rows="4"
                                        class="w-full @error('notes') border-red-500 @enderror" 
                                        placeholder="Enter additional notes">{{ old('notes', $emergencyRepair->notes) }}</x-base.form-textarea>
                                    @error('notes')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Existing Files -->
                                @if($emergencyRepair->getMedia('emergency_repair_files')->count() > 0)
                                    <div>
                                        <x-base.form-label>Current Files</x-base.form-label>
                                        <div class="space-y-2">
                                            @foreach($emergencyRepair->getMedia('emergency_repair_files') as $media)
                                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded border">
                                                    <div class="flex items-center">
                                                        @if(str_starts_with($media->mime_type, 'image/'))
                                                            <x-base.lucide class="w-5 h-5 text-blue-500 mr-2" icon="Image" />
                                                        @elseif($media->mime_type === 'application/pdf')
                                                            <x-base.lucide class="w-5 h-5 text-red-500 mr-2" icon="FileText" />
                                                        @else
                                                            <x-base.lucide class="w-5 h-5 text-slate-500 mr-2" icon="File" />
                                                        @endif
                                                        <div>
                                                            <div class="text-sm font-medium">{{ $media->name }}</div>
                                                            <div class="text-xs text-slate-500">{{ $media->human_readable_size }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <a href="{{ $media->getUrl() }}" target="_blank" 
                                                           class="text-blue-500 hover:text-blue-700">
                                                            <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                                                        </a>
                                                        <button type="button" onclick="deleteFile({{ $media->id }})" 
                                                                class="text-red-500 hover:text-red-700">
                                                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- File Upload -->
                                <div>
                                    <x-base.form-label for="repair_files">Add New Photos/Documents</x-base.form-label>
                                    <div class="border-2 border-dashed border-slate-200/60 dark:border-darkmode-400 rounded-md p-5">
                                        <div class="h-40 relative w-full cursor-pointer" id="file-upload-area">
                                            <input type="file" id="repair_files" name="repair_files[]" multiple 
                                                accept="image/*,.pdf,.doc,.docx,.txt" 
                                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                            <div class="flex flex-col items-center justify-center h-full text-slate-500">
                                                <x-base.lucide class="w-8 h-8 mb-2" icon="Upload" />
                                                <div class="text-sm font-medium">Drop files here or click to upload</div>
                                                <div class="text-xs mt-1">Supports: Images, PDF, DOC, DOCX, TXT</div>
                                            </div>
                                        </div>
                                        <div id="file-list" class="mt-3 space-y-2"></div>
                                    </div>
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
                            <x-base.button as="a" href="{{ route('carrier.emergency-repairs.show', $emergencyRepair) }}" 
                                variant="outline-secondary" class="w-full sm:w-24">
                                Cancel
                            </x-base.button>
                            <x-base.button type="submit" variant="primary" class="w-64">
                                <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Save" />
                                Update Repair
                            </x-base.button>
                        </div>
                    </form>
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
                const vehicleSelect = document.getElementById('vehicle_id');
                const driverInfo = document.getElementById('driver-info');
                const noDriverInfo = document.getElementById('no-driver-info');

                // Handle vehicle selection change
                vehicleSelect.addEventListener('change', function() {
                    const vehicleId = this.value;
                    
                    if (!vehicleId) {
                        driverInfo.classList.add('hidden');
                        noDriverInfo.classList.add('hidden');
                        return;
                    }

                    // Fetch vehicle details including driver info
                    fetch(`/carrier/emergency-repairs/vehicle/${vehicleId}/details`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.driver) {
                            // Show driver information
                            document.getElementById('driver-name').textContent = data.driver.name;
                            document.getElementById('driver-email').textContent = data.driver.email;
                            document.getElementById('driver-phone').textContent = data.driver.phone;
                            driverInfo.classList.remove('hidden');
                            noDriverInfo.classList.add('hidden');
                        } else {
                            // No driver assigned
                            driverInfo.classList.add('hidden');
                            noDriverInfo.classList.remove('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching vehicle details:', error);
                        driverInfo.classList.add('hidden');
                        noDriverInfo.classList.add('hidden');
                    });
                });

                // Trigger change event on page load to show current vehicle's driver
                if (vehicleSelect.value) {
                    vehicleSelect.dispatchEvent(new Event('change'));
                }

                // File upload handling
                fileInput.addEventListener('change', function () {
                    displaySelectedFiles(this.files);
                });

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
                    fileInput.files = e.dataTransfer.files;
                    displaySelectedFiles(e.dataTransfer.files);
                });

                function displaySelectedFiles(files) {
                    fileList.innerHTML = '';
                    
                    Array.from(files).forEach((file, index) => {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'flex items-center justify-between p-2 bg-slate-50 rounded border';
                        
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'flex items-center';
                        
                        const fileIcon = getFileIcon(file.type);
                        fileInfo.innerHTML = `
                            <i class="${fileIcon} mr-2"></i>
                            <span class="text-sm font-medium">${file.name}</span>
                            <span class="text-xs text-slate-500 ml-2">(${formatFileSize(file.size)})</span>
                        `;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'text-red-500 hover:text-red-700';
                        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                        removeBtn.onclick = () => removeFile(index);
                        
                        fileItem.appendChild(fileInfo);
                        fileItem.appendChild(removeBtn);
                        fileList.appendChild(fileItem);
                    });
                }

                function getFileIcon(fileType) {
                    if (fileType.startsWith('image/')) return 'fas fa-image text-blue-500';
                    if (fileType === 'application/pdf') return 'fas fa-file-pdf text-red-500';
                    if (fileType.includes('word')) return 'fas fa-file-word text-blue-600';
                    return 'fas fa-file text-slate-500';
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

            // Delete existing file function
            function deleteFile(mediaId) {
                if (confirm('Are you sure you want to delete this file?')) {
                    fetch(`/carrier/emergency-repairs/{{ $emergencyRepair->id }}/files/${mediaId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting file');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting file');
                    });
                }
            }
        </script>
    @endpush
@endsection
