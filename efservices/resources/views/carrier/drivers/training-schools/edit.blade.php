@extends('../themes/' . $activeTheme)
@section('title', 'Edit Training School')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Training Schools', 'url' => route('carrier.training-schools.index')],
    ['label' => 'Edit', 'active' => true],
];
@endphp

@section('subcontent')
<div>
    <!-- Flash Messages -->
    @if (session('success'))
    <x-base.alert variant="success" dismissible class="flex items-center gap-3 mb-5">
        <x-base.lucide class="w-8 h-8 text-white" icon="check-circle" />
        <span class="text-white">
            {{ session('success') }}
        </span>
        <x-base.alert.dismiss-button class="btn-close">
            <x-base.lucide class="h-4 w-4 text-white" icon="X" />
        </x-base.alert.dismiss-button>
    </x-base.alert>
    @endif

    @if (session('error'))
    <x-base.alert variant="danger" dismissible class="mb-5">
        <span class="text-white">
            {{ session('error') }}
        </span>
        <x-base.alert.dismiss-button class="btn-close">
            <x-base.lucide class="h-4 w-4 text-white" icon="X" />
        </x-base.alert.dismiss-button>
    </x-base.alert>
    @endif

    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center justify-between mt-8">
        <h2 class="text-lg font-medium">
            Edit Training School
        </h2>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <x-base.button as="a" href="{{ route('carrier.training-schools.index') }}" variant="outline-secondary">
                <x-base.lucide class="w-4 h-4 mr-1" icon="arrow-left" />
                Back to Training Schools
            </x-base.button>
            <x-base.button as="a" href="{{ route('carrier.training-schools.docs.show', $trainingSchool->id) }}" variant="outline-primary">
                <x-base.lucide class="w-4 h-4 mr-1" icon="file-text" />
                View Documents
            </x-base.button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="box box--stacked mt-5">
        <div class="box-body p-5">
            <form id="trainingSchoolForm" action="{{ route('carrier.training-schools.update', $trainingSchool->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Section 1: Basic Information -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Basic Information</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Driver -->
                        <div class="lg:col-span-2">
                            <x-base.form-label for="user_driver_detail_id" class="form-label required">Driver</x-base.form-label>
                            <x-base.form-select id="user_driver_detail_id" name="user_driver_detail_id" class="form-select @error('user_driver_detail_id') is-invalid @enderror" required>
                                <option value="">Select Driver</option>
                                @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ (old('user_driver_detail_id', $trainingSchool->user_driver_detail_id) == $driver->id) ? 'selected' : '' }}>
                                    {{ implode(' ', array_filter([$driver->user->name, $driver->middle_name, $driver->last_name])) }}
                                </option>
                                @endforeach
                            </x-base.form-select>
                            @error('user_driver_detail_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- School Name -->
                        <div class="lg:col-span-2">
                            <x-base.form-label for="school_name" class="form-label required">School Name</x-base.form-label>
                            <x-base.form-input type="text" id="school_name" name="school_name" class="form-control @error('school_name') is-invalid @enderror" value="{{ old('school_name', $trainingSchool->school_name) }}" placeholder="Enter training school name" required />
                            @error('school_name')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- City -->
                        <div>
                            <x-base.form-label for="city" class="form-label required">City</x-base.form-label>
                            <x-base.form-input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $trainingSchool->city) }}" placeholder="Enter city" required />
                            @error('city')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- State -->
                        <div>
                            <x-base.form-label for="state" class="form-label required">State</x-base.form-label>
                            <x-base.form-input type="text" id="state" name="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $trainingSchool->state) }}" placeholder="Enter state" required />
                            @error('state')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Start Date -->
                        <div>
                            <x-base.form-label for="date_start" class="form-label required">Start Date</x-base.form-label>
                            <x-base.litepicker id="date_start" name="date_start" value="{{ old('date_start', $trainingSchool->date_start ? \Carbon\Carbon::parse($trainingSchool->date_start)->format('m/d/Y') : '') }}" placeholder="MM/DD/YYYY" required />
                            @error('date_start')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- End Date -->
                        <div>
                            <x-base.form-label for="date_end" class="form-label required">End Date</x-base.form-label>
                            <x-base.litepicker id="date_end" name="date_end" value="{{ old('date_end', $trainingSchool->date_end ? \Carbon\Carbon::parse($trainingSchool->date_end)->format('m/d/Y') : '') }}" placeholder="MM/DD/YYYY" required />
                            @error('date_end')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                            <div id="date_error" class="text-danger mt-2" style="display: none;">End date must be equal to or after start date</div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Training Status -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Training Status</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Graduated -->
                        <div>
                            <div class="flex items-center">
                                <input id="graduated" name="graduated" type="checkbox" value="1" {{ old('graduated', $trainingSchool->graduated) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="graduated" class="form-check-label ml-2">
                                    Graduated
                                </label>
                            </div>
                        </div>

                        <!-- Subject to Safety Regulations -->
                        <div>
                            <div class="flex items-center">
                                <input id="subject_to_safety_regulations" name="subject_to_safety_regulations" type="checkbox" value="1" {{ old('subject_to_safety_regulations', $trainingSchool->subject_to_safety_regulations) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="subject_to_safety_regulations" class="form-check-label ml-2">
                                    Subject to Safety Regulations
                                </label>
                            </div>
                        </div>

                        <!-- Performed Safety Functions -->
                        <div>
                            <div class="flex items-center">
                                <input id="performed_safety_functions" name="performed_safety_functions" type="checkbox" value="1" {{ old('performed_safety_functions', $trainingSchool->performed_safety_functions) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="performed_safety_functions" class="form-check-label ml-2">
                                    Performed Safety Functions
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Training Skills -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Training Skills</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Double Trailer -->
                        <div>
                            <div class="flex items-center">
                                <input id="skill_double_trailer" name="training_skills[]" type="checkbox" value="double_trailer" 
                                {{ (is_array(old('training_skills')) && in_array('double_trailer', old('training_skills'))) || (!old('training_skills') && in_array('double_trailer', $trainingSkills)) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="skill_double_trailer" class="form-check-label ml-2">
                                    Double Trailer
                                </label>
                            </div>
                        </div>

                        <!-- Passenger Vehicle -->
                        <div>
                            <div class="flex items-center">
                                <input id="skill_passenger_vehicle" name="training_skills[]" type="checkbox" value="passenger_vehicle" 
                                {{ (is_array(old('training_skills')) && in_array('passenger_vehicle', old('training_skills'))) || (!old('training_skills') && in_array('passenger_vehicle', $trainingSkills)) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="skill_passenger_vehicle" class="form-check-label ml-2">
                                    Passenger Vehicle
                                </label>
                            </div>
                        </div>

                        <!-- Tank Vehicle -->
                        <div>
                            <div class="flex items-center">
                                <input id="skill_tank_vehicle" name="training_skills[]" type="checkbox" value="tank_vehicle" 
                                {{ (is_array(old('training_skills')) && in_array('tank_vehicle', old('training_skills'))) || (!old('training_skills') && in_array('tank_vehicle', $trainingSkills)) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="skill_tank_vehicle" class="form-check-label ml-2">
                                    Tank Vehicle
                                </label>
                            </div>
                        </div>

                        <!-- Hazardous Materials -->
                        <div>
                            <div class="flex items-center">
                                <input id="skill_hazardous_materials" name="training_skills[]" type="checkbox" value="hazardous_materials" 
                                {{ (is_array(old('training_skills')) && in_array('hazardous_materials', old('training_skills'))) || (!old('training_skills') && in_array('hazardous_materials', $trainingSkills)) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="skill_hazardous_materials" class="form-check-label ml-2">
                                    Hazardous Materials
                                </label>
                            </div>
                        </div>

                        <!-- Air Brakes -->
                        <div>
                            <div class="flex items-center">
                                <input id="skill_air_brakes" name="training_skills[]" type="checkbox" value="air_brakes" 
                                {{ (is_array(old('training_skills')) && in_array('air_brakes', old('training_skills'))) || (!old('training_skills') && in_array('air_brakes', $trainingSkills)) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="skill_air_brakes" class="form-check-label ml-2">
                                    Air Brakes
                                </label>
                            </div>
                        </div>

                        <!-- Combination Vehicle -->
                        <div>
                            <div class="flex items-center">
                                <input id="skill_combination_vehicle" name="training_skills[]" type="checkbox" value="combination_vehicle" 
                                {{ (is_array(old('training_skills')) && in_array('combination_vehicle', old('training_skills'))) || (!old('training_skills') && in_array('combination_vehicle', $trainingSkills)) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="skill_combination_vehicle" class="form-check-label ml-2">
                                    Combination Vehicle
                                </label>
                            </div>
                        </div>

                        <!-- School Bus -->
                        <div>
                            <div class="flex items-center">
                                <input id="skill_school_bus" name="training_skills[]" type="checkbox" value="school_bus" 
                                {{ (is_array(old('training_skills')) && in_array('school_bus', old('training_skills'))) || (!old('training_skills') && in_array('school_bus', $trainingSkills)) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="skill_school_bus" class="form-check-label ml-2">
                                    School Bus
                                </label>
                            </div>
                        </div>

                        <!-- Flatbed -->
                        <div>
                            <div class="flex items-center">
                                <input id="skill_flatbed" name="training_skills[]" type="checkbox" value="flatbed" 
                                {{ (is_array(old('training_skills')) && in_array('flatbed', old('training_skills'))) || (!old('training_skills') && in_array('flatbed', $trainingSkills)) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="skill_flatbed" class="form-check-label ml-2">
                                    Flatbed
                                </label>
                            </div>
                        </div>

                        <!-- Refrigerated -->
                        <div>
                            <div class="flex items-center">
                                <input id="skill_refrigerated" name="training_skills[]" type="checkbox" value="refrigerated" 
                                {{ (is_array(old('training_skills')) && in_array('refrigerated', old('training_skills'))) || (!old('training_skills') && in_array('refrigerated', $trainingSkills)) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="skill_refrigerated" class="form-check-label ml-2">
                                    Refrigerated
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Existing Documents -->
                @if(count($existingFilesArray) > 0)
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Existing Documents</h4>
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($existingFilesArray as $file)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200" data-document-id="{{ $file['id'] }}">
                            <div class="flex items-center gap-3">
                                <x-base.lucide class="w-5 h-5 text-primary" icon="file-text" />
                                <div>
                                    <p class="font-medium text-gray-900">{{ $file['original_name'] }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ number_format($file['size'] / 1024, 2) }} KB
                                        @if(isset($file['created_at']))
                                        • Uploaded {{ \Carbon\Carbon::parse($file['created_at'])->format('M d, Y') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-base.button type="button" as="a" href="{{ $file['url'] }}" target="_blank" variant="outline-primary" size="sm">
                                    <x-base.lucide class="w-4 h-4 mr-1" icon="eye" />
                                    View
                                </x-base.button>
                                <x-base.button type="button" variant="outline-danger" size="sm" class="delete-document-btn" data-document-id="{{ $file['id'] }}" data-document-name="{{ $file['original_name'] }}">
                                    <x-base.lucide class="w-4 h-4 mr-1" icon="trash-2" />
                                    Delete
                                </x-base.button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Section 5: Upload New Documents -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Upload New Documents</h4>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            @livewire('components.file-uploader', [
                                'modelName' => 'training_files',
                                'modelIndex' => 0,
                                'label' => 'Upload Training Certificates and Documents',
                                'existingFiles' => []
                            ])
                            
                            <!-- Hidden input to store file data -->
                            <input type="hidden" id="training_files" name="training_files" value="">
                            
                            @error('training_files')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                            @error('training_files.*')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="flex justify-end mt-8 space-x-4">
                    <x-base.button type="button" variant="outline-secondary" as="a" href="{{ route('carrier.training-schools.index') }}">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="save" />
                        Update Training School
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" tabindex="-1" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the document "<span id="documentNameToDelete"></span>"?</p>
                <p class="text-danger mt-2">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date validation
    const dateStart = document.getElementById('date_start');
    const dateEnd = document.getElementById('date_end');
    const dateError = document.getElementById('date_error');
    const form = document.getElementById('trainingSchoolForm');

    function validateDates() {
        if (dateStart.value && dateEnd.value) {
            const start = new Date(dateStart.value);
            const end = new Date(dateEnd.value);
            
            if (end < start) {
                dateError.style.display = 'block';
                return false;
            } else {
                dateError.style.display = 'none';
                return true;
            }
        }
        return true;
    }

    dateStart.addEventListener('change', validateDates);
    dateEnd.addEventListener('change', validateDates);

    // Form submission validation
    form.addEventListener('submit', function(e) {
        if (!validateDates()) {
            e.preventDefault();
            dateEnd.focus();
            return false;
        }
    });

    // Handle file uploads from Livewire component
    let uploadedFiles = [];

    // Add existing files to the array (marked as existing so they won't be processed again)
    @if(count($existingFilesArray) > 0)
    @foreach($existingFilesArray as $file)
    uploadedFiles.push({
        id: {{ $file['id'] }},
        name: "{{ $file['original_name'] }}",
        is_existing: true,
        document_id: {{ $file['id'] }}
    });
    @endforeach
    @endif

    // Listen to Livewire events
    Livewire.on('fileUploaded', (data) => {
        const fileData = data[0]; // Livewire passes data as array
        
        // Add file to our array
        uploadedFiles.push({
            name: fileData.originalName,
            tempPath: fileData.tempPath,
            path: fileData.tempPath,
            mime_type: fileData.mimeType,
            size: fileData.size
        });
        
        // Update hidden input with JSON data
        document.getElementById('training_files').value = JSON.stringify(uploadedFiles);
        
        console.log('File uploaded:', fileData);
        console.log('Total files:', uploadedFiles.length);
    });

    Livewire.on('fileRemoved', (data) => {
        const fileData = data[0]; // Livewire passes data as array
        
        // Remove file from our array
        if (fileData.isTemp) {
            // For temporary files, filter by tempPath
            uploadedFiles = uploadedFiles.filter(f => f.tempPath !== fileData.tempPath);
        } else {
            // For existing files, filter by ID
            uploadedFiles = uploadedFiles.filter(f => f.id !== fileData.fileId);
        }
        
        // Update hidden input
        document.getElementById('training_files').value = JSON.stringify(uploadedFiles);
        
        console.log('File removed:', fileData);
        console.log('Remaining files:', uploadedFiles.length);
    });

    // AJAX Document Deletion
    let documentIdToDelete = null;
    const deleteModal = document.getElementById('deleteModal');
    const documentNameSpan = document.getElementById('documentNameToDelete');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    // Handle delete button clicks
    document.querySelectorAll('.delete-document-btn').forEach(button => {
        button.addEventListener('click', function() {
            documentIdToDelete = this.getAttribute('data-document-id');
            const documentName = this.getAttribute('data-document-name');
            
            documentNameSpan.textContent = documentName;
            deleteModal.style.display = 'block';
        });
    });

    // Handle modal close
    document.querySelectorAll('[data-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            deleteModal.style.display = 'none';
            documentIdToDelete = null;
        });
    });

    // Handle confirm delete
    confirmDeleteBtn.addEventListener('click', function() {
        if (!documentIdToDelete) return;

        // Show loading state
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span>Deleting...';

        // Send AJAX request
        fetch(`{{ route('carrier.training-schools.documents.ajax-delete', '') }}/${documentIdToDelete}`, {
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
                // Remove the document from the DOM
                const documentElement = document.querySelector(`[data-document-id="${documentIdToDelete}"]`);
                if (documentElement) {
                    documentElement.remove();
                }

                // Remove from uploadedFiles array
                uploadedFiles = uploadedFiles.filter(f => f.id != documentIdToDelete);
                document.getElementById('training_files').value = JSON.stringify(uploadedFiles);

                // Close modal
                deleteModal.style.display = 'none';

                // Show success message
                showAlert('success', data.message || 'Document deleted successfully');
            } else {
                showAlert('danger', data.message || 'Error deleting document');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while deleting the document');
        })
        .finally(() => {
            // Reset button state
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = 'Delete';
            documentIdToDelete = null;
        });
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show mb-5`;
        alertDiv.innerHTML = `
            <span class="text-white">${message}</span>
            <button type="button" class="btn-close" data-dismiss="alert">
                <span class="text-white">×</span>
            </button>
        `;
        
        const container = document.querySelector('.subcontent > div');
        container.insertBefore(alertDiv, container.firstChild);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});
</script>
@endpush
