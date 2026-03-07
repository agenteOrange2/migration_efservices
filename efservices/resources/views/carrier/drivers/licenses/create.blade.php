@extends('../themes/' . $activeTheme)
@section('title', 'Add License')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('carrier.dashboard')],
['label' => 'Licenses', 'url' => route('carrier.licenses.index')],
['label' => 'Add', 'active' => true],
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
            Add New License
        </h2>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <x-base.button as="a" href="{{ route('carrier.licenses.index') }}" variant="outline-secondary">
                <x-base.lucide class="w-4 h-4 mr-1" icon="arrow-left" />
                Back to Licenses
            </x-base.button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="box box--stacked mt-5">
        <div class="box-body p-5">
            <form id="licenseForm" action="{{ route('carrier.licenses.store') }}" method="POST" enctype="multipart/form-data">
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
                                    {{ implode(' ', array_filter([$driver->user->name, $driver->middle_name, $driver->last_name])) }}
                                </option>
                                @endforeach
                            </x-base.form-select>
                            @error('user_driver_detail_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 2: License Information -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">License Information</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- License Number -->
                        <div>
                            <x-base.form-label for="license_number" class="form-label required">License Number</x-base.form-label>
                            <x-base.form-input type="text" id="license_number" name="license_number" class="form-control @error('license_number') is-invalid @enderror" value="{{ old('license_number') }}" required />
                            @error('license_number')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- License Class -->
                        <div>
                            <x-base.form-label for="license_class" class="form-label required">License Class</x-base.form-label>
                            <x-base.form-select id="license_class" name="license_class" class="form-select @error('license_class') is-invalid @enderror" required>
                                <option value="">Select License Class</option>
                                <option value="A" {{ old('license_class') == 'A' ? 'selected' : '' }}>Class A</option>
                                <option value="B" {{ old('license_class') == 'B' ? 'selected' : '' }}>Class B</option>
                                <option value="C" {{ old('license_class') == 'C' ? 'selected' : '' }}>Class C</option>
                            </x-base.form-select>
                            @error('license_class')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- State of Issue -->
                        <div>
                            <x-base.form-label for="state_of_issue" class="form-label required">State of Issue</x-base.form-label>
                            <x-base.form-select id="state_of_issue" name="state_of_issue" class="form-select @error('state_of_issue') is-invalid @enderror" required>
                                <option value="">Select State</option>
                                <option value="AL" {{ old('state_of_issue') == 'AL' ? 'selected' : '' }}>Alabama</option>
                                <option value="AK" {{ old('state_of_issue') == 'AK' ? 'selected' : '' }}>Alaska</option>
                                <option value="AZ" {{ old('state_of_issue') == 'AZ' ? 'selected' : '' }}>Arizona</option>
                                <option value="AR" {{ old('state_of_issue') == 'AR' ? 'selected' : '' }}>Arkansas</option>
                                <option value="CA" {{ old('state_of_issue') == 'CA' ? 'selected' : '' }}>California</option>
                                <option value="CO" {{ old('state_of_issue') == 'CO' ? 'selected' : '' }}>Colorado</option>
                                <option value="CT" {{ old('state_of_issue') == 'CT' ? 'selected' : '' }}>Connecticut</option>
                                <option value="DE" {{ old('state_of_issue') == 'DE' ? 'selected' : '' }}>Delaware</option>
                                <option value="FL" {{ old('state_of_issue') == 'FL' ? 'selected' : '' }}>Florida</option>
                                <option value="GA" {{ old('state_of_issue') == 'GA' ? 'selected' : '' }}>Georgia</option>
                                <option value="HI" {{ old('state_of_issue') == 'HI' ? 'selected' : '' }}>Hawaii</option>
                                <option value="ID" {{ old('state_of_issue') == 'ID' ? 'selected' : '' }}>Idaho</option>
                                <option value="IL" {{ old('state_of_issue') == 'IL' ? 'selected' : '' }}>Illinois</option>
                                <option value="IN" {{ old('state_of_issue') == 'IN' ? 'selected' : '' }}>Indiana</option>
                                <option value="IA" {{ old('state_of_issue') == 'IA' ? 'selected' : '' }}>Iowa</option>
                                <option value="KS" {{ old('state_of_issue') == 'KS' ? 'selected' : '' }}>Kansas</option>
                                <option value="KY" {{ old('state_of_issue') == 'KY' ? 'selected' : '' }}>Kentucky</option>
                                <option value="LA" {{ old('state_of_issue') == 'LA' ? 'selected' : '' }}>Louisiana</option>
                                <option value="ME" {{ old('state_of_issue') == 'ME' ? 'selected' : '' }}>Maine</option>
                                <option value="MD" {{ old('state_of_issue') == 'MD' ? 'selected' : '' }}>Maryland</option>
                                <option value="MA" {{ old('state_of_issue') == 'MA' ? 'selected' : '' }}>Massachusetts</option>
                                <option value="MI" {{ old('state_of_issue') == 'MI' ? 'selected' : '' }}>Michigan</option>
                                <option value="MN" {{ old('state_of_issue') == 'MN' ? 'selected' : '' }}>Minnesota</option>
                                <option value="MS" {{ old('state_of_issue') == 'MS' ? 'selected' : '' }}>Mississippi</option>
                                <option value="MO" {{ old('state_of_issue') == 'MO' ? 'selected' : '' }}>Missouri</option>
                                <option value="MT" {{ old('state_of_issue') == 'MT' ? 'selected' : '' }}>Montana</option>
                                <option value="NE" {{ old('state_of_issue') == 'NE' ? 'selected' : '' }}>Nebraska</option>
                                <option value="NV" {{ old('state_of_issue') == 'NV' ? 'selected' : '' }}>Nevada</option>
                                <option value="NH" {{ old('state_of_issue') == 'NH' ? 'selected' : '' }}>New Hampshire</option>
                                <option value="NJ" {{ old('state_of_issue') == 'NJ' ? 'selected' : '' }}>New Jersey</option>
                                <option value="NM" {{ old('state_of_issue') == 'NM' ? 'selected' : '' }}>New Mexico</option>
                                <option value="NY" {{ old('state_of_issue') == 'NY' ? 'selected' : '' }}>New York</option>
                                <option value="NC" {{ old('state_of_issue') == 'NC' ? 'selected' : '' }}>North Carolina</option>
                                <option value="ND" {{ old('state_of_issue') == 'ND' ? 'selected' : '' }}>North Dakota</option>
                                <option value="OH" {{ old('state_of_issue') == 'OH' ? 'selected' : '' }}>Ohio</option>
                                <option value="OK" {{ old('state_of_issue') == 'OK' ? 'selected' : '' }}>Oklahoma</option>
                                <option value="OR" {{ old('state_of_issue') == 'OR' ? 'selected' : '' }}>Oregon</option>
                                <option value="PA" {{ old('state_of_issue') == 'PA' ? 'selected' : '' }}>Pennsylvania</option>
                                <option value="RI" {{ old('state_of_issue') == 'RI' ? 'selected' : '' }}>Rhode Island</option>
                                <option value="SC" {{ old('state_of_issue') == 'SC' ? 'selected' : '' }}>South Carolina</option>
                                <option value="SD" {{ old('state_of_issue') == 'SD' ? 'selected' : '' }}>South Dakota</option>
                                <option value="TN" {{ old('state_of_issue') == 'TN' ? 'selected' : '' }}>Tennessee</option>
                                <option value="TX" {{ old('state_of_issue') == 'TX' ? 'selected' : '' }}>Texas</option>
                                <option value="UT" {{ old('state_of_issue') == 'UT' ? 'selected' : '' }}>Utah</option>
                                <option value="VT" {{ old('state_of_issue') == 'VT' ? 'selected' : '' }}>Vermont</option>
                                <option value="VA" {{ old('state_of_issue') == 'VA' ? 'selected' : '' }}>Virginia</option>
                                <option value="WA" {{ old('state_of_issue') == 'WA' ? 'selected' : '' }}>Washington</option>
                                <option value="WV" {{ old('state_of_issue') == 'WV' ? 'selected' : '' }}>West Virginia</option>
                                <option value="WI" {{ old('state_of_issue') == 'WI' ? 'selected' : '' }}>Wisconsin</option>
                                <option value="WY" {{ old('state_of_issue') == 'WY' ? 'selected' : '' }}>Wyoming</option>
                            </x-base.form-select>
                            @error('state_of_issue')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Expiration Date -->
                        <div>
                            <x-base.form-label for="expiration_date" class="form-label required">Expiration Date</x-base.form-label>
                            <x-base.litepicker id="expiration_date" name="expiration_date" value="{{ old('expiration_date') }}" placeholder="MM/DD/YYYY" required />
                            @error('expiration_date')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Restrictions -->
                        <div class="lg:col-span-2">
                            <x-base.form-label for="restrictions" class="form-label">Restrictions</x-base.form-label>
                            <x-base.form-textarea id="restrictions" name="restrictions" class="form-control @error('restrictions') is-invalid @enderror" rows="3" placeholder="Enter any license restrictions">{{ old('restrictions') }}</x-base.form-textarea>
                            @error('restrictions')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 3: CDL and Endorsements -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">CDL Information</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- CDL Checkbox -->
                        <div>
                            <x-base.form-label class="form-label">Commercial Driver's License (CDL)</x-base.form-label>
                            <div class="flex items-center mb-2">
                                <input id="is_cdl" name="is_cdl" type="checkbox" value="1" {{ old('is_cdl') ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="is_cdl" class="form-check-label ml-2">
                                    This is a CDL License
                                </label>
                            </div>
                            @error('is_cdl')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Primary License Checkbox -->
                        <div>
                            <x-base.form-label class="form-label">Primary License</x-base.form-label>
                            <div class="flex items-center mb-2">
                                <input id="is_primary" name="is_primary" type="checkbox" value="1" {{ old('is_primary') ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="is_primary" class="form-check-label ml-2">
                                    Set as primary license
                                </label>
                            </div>
                            <p class="text-xs text-slate-500">If checked, this will be set as the driver's primary license.</p>
                            @error('is_primary')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- CDL Endorsements (hidden by default) -->
                        <div id="cdl_endorsements" class="hidden">
                            <x-base.form-label class="form-label">CDL Endorsements</x-base.form-label>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                <div class="flex items-center">
                                    <input id="endorsement_n" name="endorsement_n" type="checkbox" value="1" {{ old('endorsement_n') ? 'checked' : '' }}
                                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                    <label for="endorsement_n" class="form-check-label ml-2">
                                        N - Tank Vehicle
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="endorsement_h" name="endorsement_h" type="checkbox" value="1" {{ old('endorsement_h') ? 'checked' : '' }}
                                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                    <label for="endorsement_h" class="form-check-label ml-2">
                                        H - Hazardous Materials
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="endorsement_x" name="endorsement_x" type="checkbox" value="1" {{ old('endorsement_x') ? 'checked' : '' }}
                                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                    <label for="endorsement_x" class="form-check-label ml-2">
                                        X - Hazmat & Tank
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="endorsement_t" name="endorsement_t" type="checkbox" value="1" {{ old('endorsement_t') ? 'checked' : '' }}
                                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                    <label for="endorsement_t" class="form-check-label ml-2">
                                        T - Double/Triple Trailers
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="endorsement_p" name="endorsement_p" type="checkbox" value="1" {{ old('endorsement_p') ? 'checked' : '' }}
                                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                    <label for="endorsement_p" class="form-check-label ml-2">
                                        P - Passenger
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input id="endorsement_s" name="endorsement_s" type="checkbox" value="1" {{ old('endorsement_s') ? 'checked' : '' }}
                                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                    <label for="endorsement_s" class="form-check-label ml-2">
                                        S - School Bus
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: License Images -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">License Images</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- License Front Image -->
                        <div>
                            <x-base.form-label for="license_front_image" class="form-label">License Front Image</x-base.form-label>
                            <x-base.form-input type="file" id="license_front_image" name="license_front_image" class="form-control @error('license_front_image') is-invalid @enderror" accept="image/*" />
                            <small class="form-text text-muted">Upload the front side of the driver's license (JPG, PNG, GIF - Max 2MB)</small>
                            @error('license_front_image')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                            <!-- Preview -->
                            <div id="front_image_preview" class="mt-2" style="display: none;">
                                <img id="front_preview_img" src="" alt="Front Preview" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                            </div>
                        </div>

                        <!-- License Back Image -->
                        <div>
                            <x-base.form-label for="license_back_image" class="form-label">License Back Image</x-base.form-label>
                            <x-base.form-input type="file" id="license_back_image" name="license_back_image" class="form-control @error('license_back_image') is-invalid @enderror" accept="image/*" />
                            <small class="form-text text-muted">Upload the back side of the driver's license (JPG, PNG, GIF - Max 2MB)</small>
                            @error('license_back_image')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                            <!-- Preview -->
                            <div id="back_image_preview" class="mt-2" style="display: none;">
                                <img id="back_preview_img" src="" alt="Back Preview" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Additional Documents -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Additional Documents</h4>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <x-base.form-label class="form-label">Upload Additional Documents</x-base.form-label>
                            <div id="file-upload-area" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary transition-colors">
                                <x-base.lucide class="w-12 h-12 mx-auto text-gray-400 mb-3" icon="upload-cloud" />
                                <p class="text-sm text-gray-600 mb-2">Drag and drop files here or click to browse</p>
                                <p class="text-xs text-gray-500">Supported formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB per file)</p>
                                <input type="file" id="additional_documents" name="additional_documents[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden" />
                                <x-base.button type="button" variant="outline-primary" class="mt-3" onclick="document.getElementById('additional_documents').click()">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="file-plus" />
                                    Select Files
                                </x-base.button>
                            </div>
                            <div id="file-list" class="mt-4 space-y-2"></div>
                            <!-- Hidden input to store file data for submission -->
                            <input type="hidden" id="uploaded_files_data" name="uploaded_files" value="">
                        </div>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="flex justify-end mt-8 space-x-4">
                    <x-base.button type="button" variant="outline-secondary" as="a" href="{{ route('carrier.licenses.index') }}">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="save" />
                        Save License
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Form initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Handle CDL checkbox and show/hide endorsements
        const cdlCheckbox = document.getElementById('is_cdl');
        const endorsementsSection = document.getElementById('cdl_endorsements');
        
        function toggleEndorsements() {
            if (cdlCheckbox.checked) {
                endorsementsSection.classList.remove('hidden');
            } else {
                endorsementsSection.classList.add('hidden');
                // Uncheck all endorsements when CDL is unchecked
                const endorsementCheckboxes = endorsementsSection.querySelectorAll('input[type="checkbox"]');
                endorsementCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }
        }
        
        // Initialize endorsements state
        toggleEndorsements();
        
        // Listen for changes in CDL checkbox
        cdlCheckbox.addEventListener('change', toggleEndorsements);
        
        // Handle image preview
        function setupImagePreview(inputId, previewId, imgId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const img = document.getElementById(imgId);
            
            input.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
                }
            });
        }
        
        // Setup previews for both images
        setupImagePreview('license_front_image', 'front_image_preview', 'front_preview_img');
        setupImagePreview('license_back_image', 'back_image_preview', 'back_preview_img');
        
        // Handle additional documents upload
        const additionalDocsInput = document.getElementById('additional_documents');
        const fileList = document.getElementById('file-list');
        const uploadArea = document.getElementById('file-upload-area');
        let selectedFiles = [];
        
        // Click to upload
        uploadArea.addEventListener('click', function(e) {
            if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'INPUT') {
                additionalDocsInput.click();
            }
        });
        
        // Drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('border-primary', 'bg-primary/5');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('border-primary', 'bg-primary/5');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('border-primary', 'bg-primary/5');
            
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });
        
        additionalDocsInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });
        
        function handleFiles(files) {
            files.forEach(file => {
                // Validate file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    alert(`File ${file.name} is too large. Maximum size is 10MB.`);
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    alert(`File ${file.name} has an unsupported format.`);
                    return;
                }
                
                selectedFiles.push(file);
                displayFile(file);
            });
        }
        
        function displayFile(file) {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200';
            
            const fileInfo = document.createElement('div');
            fileInfo.className = 'flex items-center gap-3';
            
            const icon = document.createElement('div');
            icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>';
            
            const fileDetails = document.createElement('div');
            fileDetails.innerHTML = `
                <p class="text-sm font-medium text-gray-700">${file.name}</p>
                <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
            `;
            
            fileInfo.appendChild(icon);
            fileInfo.appendChild(fileDetails);
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'text-red-500 hover:text-red-700';
            removeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
            removeBtn.onclick = function() {
                selectedFiles = selectedFiles.filter(f => f !== file);
                fileItem.remove();
            };
            
            fileItem.appendChild(fileInfo);
            fileItem.appendChild(removeBtn);
            fileList.appendChild(fileItem);
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        // Form validation
        document.getElementById('licenseForm').addEventListener('submit', function(event) {
            const expirationDateEl = document.querySelector('input[name="expiration_date"]');
            
            // Verify expiration date is not in the past
            if (expirationDateEl.value) {
                const expirationDate = new Date(expirationDateEl.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (expirationDate < today) {
                    event.preventDefault();
                    alert('Expiration date cannot be in the past');
                    return;
                }
            }
            
            // Handle file uploads - create FormData to include files
            if (selectedFiles.length > 0) {
                // Note: Files will be handled by the browser's FormData automatically
                // when the form is submitted with enctype="multipart/form-data"
                // We just need to add them to the form
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach(file => {
                    dataTransfer.items.add(file);
                });
                additionalDocsInput.files = dataTransfer.files;
            }
        });
    });
</script>
@endpush
