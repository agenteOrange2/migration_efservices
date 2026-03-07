@extends('../themes/' . $activeTheme)
@section('title', 'Edit Maintenance Record')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Maintenance', 'url' => route('carrier.maintenance.index')],
        ['label' => 'Edit Maintenance #' . $maintenance->id, 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Edit Maintenance Record #{{ $maintenance->id }}
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('carrier.maintenance.show', $maintenance->id) }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to Details
                    </x-base.button>
                </div>
            </div>

            <div class="intro-y box p-5 mt-5">
                <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                    <div class="font-medium text-base truncate">Maintenance Information</div>                    
                </div>

                <form action="{{ route('carrier.maintenance.update', $maintenance->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="mt-3">
                        <x-base.form-label for="vehicle_id">Vehicle *</x-base.form-label>
                        <select id="vehicle_id" name="vehicle_id"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('vehicle_id') border-danger @enderror" required>
                            <option value="">Select Vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" 
                                    {{ (old('vehicle_id', $maintenance->vehicle_id) == $vehicle->id) ? 'selected' : '' }}>
                                    @if($vehicle->company_unit_number)
                                        {{ $vehicle->company_unit_number }} - 
                                    @endif
                                    {{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}
                                    @if($vehicle->vin)
                                        (VIN: {{ substr($vehicle->vin, -6) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <x-base.form-label for="service_tasks">Maintenance Type *</x-base.form-label>
                        <select id="service_tasks" name="service_tasks"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('service_tasks') border-danger @enderror" required>
                            <option value="">Select Maintenance Type</option>
                            @foreach ($maintenanceTypes as $type)
                                <option value="{{ $type }}" {{ old('service_tasks', $maintenance->service_tasks) == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('service_tasks')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="unit">Unit *</x-base.form-label>
                            <x-base.form-input id="unit" name="unit" type="text"
                                class="w-full @error('unit') border-danger @enderror" 
                                placeholder="Unit number or identifier" 
                                value="{{ old('unit', $maintenance->unit) }}" required />
                            @error('unit')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="vendor_mechanic">Vendor/Mechanic *</x-base.form-label>
                            <x-base.form-input id="vendor_mechanic" name="vendor_mechanic" type="text"
                                class="w-full @error('vendor_mechanic') border-danger @enderror" 
                                placeholder="e.g., ABC Auto Shop" 
                                value="{{ old('vendor_mechanic', $maintenance->vendor_mechanic) }}" required />
                            @error('vendor_mechanic')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="service_date">Service Date *</x-base.form-label>
                            <x-base.litepicker id="service_date" name="service_date" 
                                value="{{ old('service_date', $maintenance->service_date ? $maintenance->service_date->format('m/d/Y') : '') }}"
                                class="@error('service_date') border-danger @enderror" 
                                placeholder="MM/DD/YYYY"
                                required />
                            @error('service_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <x-base.form-label for="next_service_date">Next Service Date</x-base.form-label>
                            <x-base.litepicker id="next_service_date" name="next_service_date" 
                                value="{{ old('next_service_date', $maintenance->next_service_date ? $maintenance->next_service_date->format('m/d/Y') : '') }}"
                                class="@error('next_service_date') border-danger @enderror" 
                                placeholder="MM/DD/YYYY" />
                            @error('next_service_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="cost">Cost *</x-base.form-label>
                            <x-base.form-input id="cost" name="cost" type="number"
                                class="w-full @error('cost') border-danger @enderror" 
                                placeholder="e.g., 500.00" 
                                step="0.01" min="0" 
                                value="{{ old('cost', $maintenance->cost) }}" required />
                            @error('cost')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="odometer">Odometer Reading *</x-base.form-label>
                            <x-base.form-input id="odometer" name="odometer" type="number"
                                class="w-full @error('odometer') border-danger @enderror" 
                                placeholder="e.g., 50000" 
                                min="0" 
                                value="{{ old('odometer', $maintenance->odometer) }}" required />
                            @error('odometer')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <x-base.form-label for="description">Description/Notes</x-base.form-label>
                        <x-base.form-textarea id="description" name="description" 
                            class="w-full @error('description') border-danger @enderror"
                            rows="4" 
                            maxlength="1000"
                            placeholder="Additional notes or details about the maintenance">{{ old('description', $maintenance->description) }}</x-base.form-textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Existing documents section -->
                    @php
                        $existingDocuments = $maintenance->getMedia('maintenance_files');
                    @endphp
                    @if($existingDocuments->count() > 0)
                        <div class="mt-8 pt-5 border-t border-slate-200/60 dark:border-darkmode-400">
                            <h3 class="text-lg font-medium mb-3">Existing Documents</h3>
                            <p class="text-sm text-slate-500 mb-4">Click the delete button to remove a document</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($existingDocuments as $media)
                                    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden document-item" data-media-id="{{ $media->id }}">
                                        <div class="p-4">
                                            <div class="flex items-center mb-3">
                                                @if(str_contains($media->mime_type, 'image'))
                                                    <div class="w-10 h-10 flex-shrink-0 mr-3 bg-primary/10 rounded-lg flex items-center justify-center">
                                                        <x-base.lucide class="w-5 h-5 text-primary" icon="Image" />
                                                    </div>
                                                @elseif(str_contains($media->mime_type, 'pdf'))
                                                    <div class="w-10 h-10 flex-shrink-0 mr-3 bg-danger/10 rounded-lg flex items-center justify-center">
                                                        <x-base.lucide class="w-5 h-5 text-danger" icon="FileText" />
                                                    </div>
                                                @else
                                                    <div class="w-10 h-10 flex-shrink-0 mr-3 bg-warning/10 rounded-lg flex items-center justify-center">
                                                        <x-base.lucide class="w-5 h-5 text-warning" icon="File" />
                                                    </div>
                                                @endif
                                                <div class="flex-grow overflow-hidden">
                                                    <p class="font-medium text-sm truncate">{{ $media->file_name }}</p>
                                                    <p class="text-xs text-slate-500">{{ $media->human_readable_size }}</p>
                                                </div>
                                            </div>
                                            <div class="flex space-x-2">
                                                <a href="{{ $media->getUrl() }}" target="_blank" 
                                                    class="btn btn-sm btn-outline-secondary flex-1 flex items-center justify-center">
                                                    <x-base.lucide class="w-4 h-4 mr-1" icon="Eye" /> View
                                                </a>
                                                <button type="button" 
                                                    class="btn btn-sm btn-outline-danger flex-1 flex items-center justify-center delete-document-btn"
                                                    data-media-id="{{ $media->id }}">
                                                    <x-base.lucide class="w-4 h-4 mr-1" icon="Trash2" /> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- New file upload section using Livewire -->
                    <div class="mt-8 pt-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <h3 class="text-lg font-medium mb-5">Add New Attachments</h3>
                        <p class="text-sm text-slate-500 mb-4">Upload additional invoices, receipts, or inspection reports</p>

                        <!-- Hidden field to store file information -->
                        <input type="hidden" name="livewire_files" id="livewire_files" value="[]">

                        <!-- Livewire file uploader component -->
                        <livewire:components.file-uploader 
                            model-name="maintenance_files" 
                            :model-index="$maintenance->id"
                            :auto-upload="true"
                            class="border-2 border-dashed border-gray-300 rounded-lg p-6 cursor-pointer" />
                    </div>

                    <div class="mt-5 pt-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <div class="flex items-center mb-3">
                            <input id="status" type="checkbox" name="status" value="1"
                                {{ old('status', $maintenance->status) ? 'checked' : '' }} 
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded">
                            <label for="status" class="ml-2 form-label mb-0">Mark as Completed</label>
                        </div>
                        @error('status')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror

                        <div class="flex items-center">
                            <input id="is_historical" type="checkbox" name="is_historical" value="1"
                                {{ old('is_historical', $maintenance->is_historical) ? 'checked' : '' }} 
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded">
                            <label for="is_historical" class="ml-2 form-label mb-0">Historical Service (Past Maintenance)</label>
                        </div>
                        <p class="text-xs text-slate-500 ml-6 mt-1">Check this if entering past maintenance records with flexible dates</p>
                        @error('is_historical')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex justify-end mt-5">
                        <x-base.button as="a" href="{{ route('carrier.maintenance.show', $maintenance->id) }}"
                            variant="outline-secondary" class="mr-2">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
                            Update Maintenance Record
                        </x-base.button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const vehicleSelect = document.getElementById('vehicle_id');
                const unitInput = document.getElementById('unit');
                const filesInput = document.getElementById('livewire_files');

                // Auto-calculate next_service_date = service_date + 3 months
                const serviceDateInput = document.getElementById('service_date');
                const nextServiceDateInput = document.getElementById('next_service_date');
                let userEditedNextDate = false;

                function addThreeMonths(dateStr) {
                    const parts = dateStr.split('/');
                    if (parts.length !== 3) return null;
                    const month = parseInt(parts[0]) - 1;
                    const day = parseInt(parts[1]);
                    const year = parseInt(parts[2]);
                    const date = new Date(year, month, day, 12, 0, 0);
                    date.setMonth(date.getMonth() + 3);
                    const mm = String(date.getMonth() + 1).padStart(2, '0');
                    const dd = String(date.getDate()).padStart(2, '0');
                    const yyyy = date.getFullYear();
                    return mm + '/' + dd + '/' + yyyy;
                }

                function setNextServiceDate(dateStr) {
                    const nextDate = addThreeMonths(dateStr);
                    if (!nextDate) return;
                    setTimeout(function() {
                        if (nextServiceDateInput._litepicker) {
                            nextServiceDateInput._litepicker.setDate(nextDate);
                        } else {
                            nextServiceDateInput.value = nextDate;
                        }
                    }, 300);
                }

                nextServiceDateInput.addEventListener('change', function() {
                    userEditedNextDate = true;
                });

                serviceDateInput.addEventListener('change', function() {
                    if (!userEditedNextDate) {
                        setNextServiceDate(this.value);
                    }
                });

                // Vehicle data for auto-filling unit field
                const vehiclesData = @json(
                    $vehicles->map(function ($vehicle) {
                        return [
                            'id' => $vehicle->id,
                            'unit' => $vehicle->company_unit_number ?? '',
                        ];
                    }));

                // Auto-fill unit field when vehicle is selected
                if (vehicleSelect && unitInput) {
                    vehicleSelect.addEventListener('change', function() {
                        const selectedVehicleId = parseInt(this.value);
                        if (!selectedVehicleId) {
                            unitInput.value = '';
                            return;
                        }

                        // Always update unit field when vehicle changes
                        const selectedVehicle = vehiclesData.find(v => v.id === selectedVehicleId);
                        if (selectedVehicle) {
                            unitInput.value = selectedVehicle.unit || '';
                        }
                    });
                }

                // Handle Livewire file upload events
                if (filesInput) {
                    let uploadedFiles = [];

                    // Listen for file uploaded event
                    window.addEventListener('fileUploaded', (event) => {
                        const fileData = event.detail;
                        uploadedFiles.push(fileData);
                        filesInput.value = JSON.stringify(uploadedFiles);
                        console.log('File uploaded:', fileData);
                    });

                    // Listen for file removed event
                    window.addEventListener('fileRemoved', (event) => {
                        const fileId = event.detail;
                        uploadedFiles = uploadedFiles.filter(file => {
                            return file.id !== fileId && file.path !== fileId;
                        });
                        filesInput.value = JSON.stringify(uploadedFiles);
                        console.log('File removed:', fileId);
                    });
                }

                // Handle document deletion
                const deleteButtons = document.querySelectorAll('.delete-document-btn');
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const mediaId = this.dataset.mediaId;
                        const documentItem = this.closest('.document-item');
                        
                        if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                            // Disable button to prevent double-clicks
                            this.disabled = true;
                            this.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Deleting...';
                            
                            // Send AJAX request to delete document
                            fetch(`{{ route('carrier.maintenance.ajax-delete-document', '') }}/${mediaId}`, {
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
                                    // Remove the document item from DOM with animation
                                    documentItem.style.opacity = '0';
                                    documentItem.style.transform = 'scale(0.9)';
                                    documentItem.style.transition = 'all 0.3s ease';
                                    
                                    setTimeout(() => {
                                        documentItem.remove();
                                        
                                        // Check if there are any documents left
                                        const remainingDocs = document.querySelectorAll('.document-item');
                                        if (remainingDocs.length === 0) {
                                            // Hide the existing documents section
                                            const existingDocsSection = documentItem.closest('.mt-8');
                                            if (existingDocsSection) {
                                                existingDocsSection.remove();
                                            }
                                        }
                                    }, 300);
                                    
                                    // Show success message
                                    alert('Document deleted successfully.');
                                } else {
                                    alert('Failed to delete document: ' + (data.message || 'Unknown error'));
                                    // Re-enable button
                                    this.disabled = false;
                                    this.innerHTML = '<x-base.lucide class="w-4 h-4 mr-1" icon="Trash2" /> Delete';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while deleting the document. Please try again.');
                                // Re-enable button
                                this.disabled = false;
                                this.innerHTML = '<x-base.lucide class="w-4 h-4 mr-1" icon="Trash2" /> Delete';
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
