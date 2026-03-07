@extends('../themes/' . $activeTheme)
@section('title', 'Add Course Record')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Driver Courses Management', 'url' => route('carrier.courses.index')],
    ['label' => 'Add Course Record', 'active' => true],
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
            Add New Course Record
        </h2>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <x-base.button as="a" href="{{ route('carrier.courses.index') }}" variant="outline-secondary">
                <x-base.lucide class="w-4 h-4 mr-1" icon="arrow-left" />
                Back to Driver Courses Management
            </x-base.button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="box box--stacked mt-5">
        <div class="box-body p-5">
            <form id="courseForm" action="{{ route('carrier.courses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

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
                                <option value="{{ $driver->id }}" {{ old('user_driver_detail_id') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->user->name . ' ' . $driver->middle_name . ' ' . $driver->last_name }}
                                </option>
                                @endforeach
                            </x-base.form-select>
                            @error('user_driver_detail_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Organization Name -->
                        <div class="lg:col-span-2">
                            <x-base.form-label for="organization_name" class="form-label required">Organization Name</x-base.form-label>
                            <x-base.form-select id="organization_name" name="organization_name" class="form-select @error('organization_name') is-invalid @enderror" required>
                                <option value="">Select Organization</option>
                                <option value="ACME Corp" {{ old('organization_name') == 'ACME Corp' ? 'selected' : '' }}>ACME Corp</option>
                                <option value="Safety First Training" {{ old('organization_name') == 'Safety First Training' ? 'selected' : '' }}>Safety First Training</option>
                                <option value="Transport Training Institute" {{ old('organization_name') == 'Transport Training Institute' ? 'selected' : '' }}>Transport Training Institute</option>
                                <option value="Professional Drivers Academy" {{ old('organization_name') == 'Professional Drivers Academy' ? 'selected' : '' }}>Professional Drivers Academy</option>
                                <option value="Other" {{ old('organization_name') == 'Other' ? 'selected' : '' }}>Other</option>
                            </x-base.form-select>
                            @error('organization_name')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Other Organization Name (conditional) -->
                        <div class="lg:col-span-2" id="other_organization_container" style="display: none;">
                            <x-base.form-label for="other_organization_name" class="form-label">Other Organization Name</x-base.form-label>
                            <x-base.form-input type="text" id="other_organization_name" name="other_organization_name" class="form-control @error('other_organization_name') is-invalid @enderror" value="{{ old('other_organization_name') }}" placeholder="Enter the name of the organization" />
                            @error('other_organization_name')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- City -->
                        <div>
                            <x-base.form-label for="city" class="form-label">City</x-base.form-label>
                            <x-base.form-input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}" placeholder="Enter city" />
                            @error('city')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- State -->
                        <div>
                            <x-base.form-label for="state">State</x-base.form-label>
                            <select id="state" name="state" 
                                class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">Select State</option>
                                @foreach(\App\Helpers\Constants::usStates() as $code => $name)
                                    <option value="{{ $code }}" {{ old('state') == $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('state')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        

                        <!-- Certification Date -->
                        <div>
                            <x-base.form-label for="certification_date" class="form-label">Certification Date</x-base.form-label>
                            <x-base.litepicker id="certification_date" name="certification_date" value="{{ old('certification_date') }}" placeholder="MM/DD/YYYY" />
                            @error('certification_date')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Expiration Date -->
                        <div>
                            <x-base.form-label for="expiration_date" class="form-label">Expiration Date</x-base.form-label>
                            <x-base.litepicker id="expiration_date" name="expiration_date" value="{{ old('expiration_date') }}" placeholder="MM/DD/YYYY" />
                            @error('expiration_date')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Experience -->
                        <div>
                            <x-base.form-label for="experience" class="form-label">Experience/Course Type</x-base.form-label>
                            <x-base.form-input type="text" id="experience" name="experience" class="form-control @error('experience') is-invalid @enderror" value="{{ old('experience') }}" placeholder="Enter experience/course type" />
                            @error('experience')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <x-base.form-label for="status" class="form-label">Status</x-base.form-label>
                            <x-base.form-select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="Active" {{ old('status', 'Active') == 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ old('status', 'Inactive') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </x-base.form-select>
                            @error('status')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 2: Course Certificates -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Course Certificates</h4>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            @livewire('components.file-uploader', [
                                'modelName' => 'course_files',
                                'modelIndex' => 0,
                                'label' => 'Subir Certificados y Documentos del Curso',
                                'existingFiles' => []
                            ])
                            
                            <!-- Hidden input to store file data -->
                            <input type="hidden" id="course_files" name="course_files" value="">
                            
                            @error('course_files')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                            @error('course_files.*')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 mt-8 pt-5 border-t">
                    <x-base.button as="a" href="{{ route('carrier.courses.index') }}" variant="outline-secondary">
                        <x-base.lucide class="w-4 h-4 mr-1" icon="x" />
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-1" icon="save" />
                        Save Course
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle organization name change to show/hide "Other" field
        const organizationSelect = document.getElementById('organization_name');
        const otherContainer = document.getElementById('other_organization_container');
        const otherInput = document.getElementById('other_organization_name');
        
        function toggleOtherField() {
            if (organizationSelect.value === 'Other') {
                otherContainer.style.display = 'block';
                otherInput.required = true;
            } else {
                otherContainer.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        }
        
        // Initial check
        toggleOtherField();
        
        // Listen for changes
        organizationSelect.addEventListener('change', toggleOtherField);
    });
    
    // Handle file uploader - Initialize array to store uploaded files
    let uploadedFiles = [];
    
    // Listen to Livewire events
    window.addEventListener('livewire:initialized', () => {
        Livewire.on('fileUploaded', (data) => {
            const fileData = data[0]; // Livewire passes data as array
            
            if (fileData.modelName === 'course_files') {
                // Add file to array
                uploadedFiles.push({
                    name: fileData.originalName,
                    original_name: fileData.originalName,
                    mime_type: fileData.mimeType,
                    size: fileData.size,
                    path: fileData.tempPath,
                    tempPath: fileData.tempPath,
                    is_temp: true
                });
                
                // Update hidden input
                document.getElementById('course_files').value = JSON.stringify(uploadedFiles);
                console.log('File uploaded:', fileData.originalName);
            }
        });
        
        Livewire.on('fileRemoved', (data) => {
            const fileData = data[0];
            
            if (fileData.modelName === 'course_files') {
                // Remove file from array
                uploadedFiles = uploadedFiles.filter(file => file.tempPath !== fileData.tempPath);
                
                // Update hidden input
                document.getElementById('course_files').value = JSON.stringify(uploadedFiles);
                console.log('File removed');
            }
        });
    });
</script>
@endPush

@endsection
