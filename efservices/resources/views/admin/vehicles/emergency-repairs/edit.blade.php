@extends('../themes/' . $activeTheme)
@section('title', 'Edit Emergency Repair')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
        ['label' => 'Emergency Repairs', 'url' => route('admin.vehicles.emergency-repairs.index')],
        ['label' => 'Edit', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Edit Emergency Repair: {{ $emergencyRepair->repair_name }}
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.show', $emergencyRepair) }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Eye" />
                        View Details
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                </div>
            </div>

            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    <form action="{{ route('admin.vehicles.emergency-repairs.update', $emergencyRepair) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 ">
                            <!-- Left Column -->                            
                                <!-- Carrier Selection -->
                                <div>
                                    <x-base.form-label for="carrier_id">Carrier *</x-base.form-label>
                                    <x-base.tom-select id="carrier_id" name="carrier_id" class="w-full @error('carrier_id') border-red-500 @enderror" data-placeholder="Select Carrier" required>
                                        <option value="">Select Carrier</option>
                                        @foreach ($carriers as $carrier)
                                            <option value="{{ $carrier->id }}" {{ (old('carrier_id', $emergencyRepair->vehicle->carrier_id) == $carrier->id) ? 'selected' : '' }}>
                                                {{ $carrier->name }}
                                            </option>
                                        @endforeach
                                    </x-base.tom-select>
                                    @error('carrier_id')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Vehicle Selection -->
                                <div>
                                    <x-base.form-label for="vehicle_id">Vehicle *</x-base.form-label>
                                    <x-base.tom-select id="vehicle_id" name="vehicle_id" class="w-full @error('vehicle_id') border-red-500 @enderror" data-placeholder="Select Vehicle" required>
                                        <option value="">Select Vehicle</option>
                                        @foreach ($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}" {{ (old('vehicle_id', $emergencyRepair->vehicle_id) == $vehicle->id) ? 'selected' : '' }}>
                                                {{ $vehicle->make }} {{ $vehicle->model }} - {{ $vehicle->company_unit_number ?? $vehicle->vin }}
                                            </option>
                                        @endforeach
                                    </x-base.tom-select>
                                    @error('vehicle_id')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
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
                                        value="{{ old('repair_date', $emergencyRepair->repair_date->format('m/d/Y')) }}" 
                                        placeholder="MM/DD/YYYY" data-single-mode="true" data-format="MM/DD/YYYY" required />
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
                                        placeholder="Enter odometer reading" value="{{ old('odometer', $emergencyRepair->odometer) }}" />
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
                                <div class="mt-5 p-4 bg-slate-50 dark:bg-darkmode-800 rounded-lg border border-slate-200/60">
                                    <x-base.form-label for="repair_files" class="flex items-center gap-2 mb-3">
                                        <x-base.lucide class="w-4 h-4 text-primary" icon="Upload" />
                                        Add New Photos/Documents (Optional)
                                    </x-base.form-label>
                                    <div class="relative">
                                        <input id="repair_files" name="repair_files[]" type="file" multiple 
                                            accept="image/*,.pdf,.doc,.docx,.txt" 
                                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer border border-slate-200 rounded-lg @error('repair_files.*') border-danger @enderror" />
                                    </div>
                                    <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                                        <x-base.lucide class="w-3 h-3" icon="Info" />
                                        <span>Accepted: Images, PDF, DOC, DOCX, TXT (Max 10MB each)</span>
                                    </div>
                                    <div id="file-list" class="mt-3 space-y-2"></div>
                                    @error('repair_files')
                                        <div class="text-danger mt-2 text-sm flex items-center gap-1">
                                            <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    @error('repair_files.*')
                                        <div class="text-danger mt-2 text-sm flex items-center gap-1">
                                            <x-base.lucide class="w-4 h-4" icon="AlertCircle" />
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-slate-200/60 dark:border-darkmode-400">
                            <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.show', $emergencyRepair) }}" 
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
                const carrierSelect = document.getElementById('carrier_id');
                const vehicleSelect = document.getElementById('vehicle_id');
                const fileInput = document.getElementById('repair_files');
                const fileList = document.getElementById('file-list');

                // Load vehicles when carrier changes
                carrierSelect.addEventListener('change', function () {
                    const carrierId = this.value;
                    const currentVehicleId = '{{ $emergencyRepair->vehicle_id }}';
                    
                    // Clear vehicle options
                    vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>';
                    
                    if (carrierId) {
                        fetch(`{{ route('admin.vehicles.emergency-repairs.index') }}/vehicles-by-carrier?carrier_id=${carrierId}`)
                            .then(response => response.json())
                            .then(vehicles => {
                                vehicles.forEach(vehicle => {
                                    const option = document.createElement('option');
                                    option.value = vehicle.id;
                                    option.textContent = `${vehicle.make} ${vehicle.model} - ${vehicle.company_unit_number || vehicle.vin}`;
                                    if (vehicle.id == currentVehicleId) {
                                        option.selected = true;
                                    }
                                    vehicleSelect.appendChild(option);
                                });
                            })
                            .catch(error => console.error('Error loading vehicles:', error));
                    }
                });

                // File upload handling
                if (fileInput) {
                    fileInput.addEventListener('change', function () {
                        displaySelectedFiles(this.files);
                    });

                    function displaySelectedFiles(files) {
                        if (!fileList) return;
                        fileList.innerHTML = '';
                        
                        if (files.length === 0) return;
                        
                        Array.from(files).forEach((file, index) => {
                            const fileItem = document.createElement('div');
                            fileItem.className = 'flex items-center justify-between p-2 bg-white dark:bg-darkmode-600 rounded border border-slate-200 dark:border-darkmode-400';
                            
                            const fileInfo = document.createElement('div');
                            fileInfo.className = 'flex items-center gap-2';
                            
                            const fileIcon = getFileIcon(file.type);
                            fileInfo.innerHTML = `
                                <i class="${fileIcon}"></i>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">${file.name}</span>
                                <span class="text-xs text-slate-500">(${formatFileSize(file.size)})</span>
                            `;
                            
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'text-danger hover:text-danger/80 ml-2';
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
                }
            });

            // Delete existing file function
            function deleteFile(mediaId) {
                if (confirm('Are you sure you want to delete this file?')) {
                    fetch(`/admin/vehicles/emergency-repairs/{{ $emergencyRepair->id }}/files/${mediaId}`, {
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