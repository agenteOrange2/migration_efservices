@extends('../themes/' . $activeTheme)
@section('title', 'Add License - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Licenses', 'url' => route('driver.licenses.index')],
        ['label' => 'Add License', 'active' => true],
    ];
@endphp

@section('subcontent')

<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

 <!-- Professional Header -->
 <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="CreditCard" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Add New License</h1>
                    <p class="text-slate-600">Enter your driver license information</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button  as="a" href="{{ route('driver.licenses.index') }}" variant="primary" class="gap-2">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                    Back to Licenses
                </x-base.button>
            </div>
        </div>
    </div>

@if($errors->any())
<div class="box box--stacked p-4 mb-6 border-l-4 border-danger bg-danger/10">
    <div class="flex items-start gap-3">
        <x-base.lucide class="w-5 h-5 text-danger mt-0.5" icon="AlertCircle" />
        <div>
            <p class="text-danger font-medium mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside text-sm text-danger">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

<form id="licenseForm" action="{{ route('driver.licenses.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="space-y-6">
        <!-- License Information -->
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="CreditCard" />
                License Information
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-base.form-label for="license_number">License Number *</x-base.form-label>
                    <x-base.form-input type="text" id="license_number" name="license_number" 
                        value="{{ old('license_number') }}" required placeholder="Enter license number" />
                </div>

                <div>
                    <x-base.form-label for="license_class">License Class *</x-base.form-label>
                    <x-base.form-select id="license_class" name="license_class" required>
                        <option value="">Select Class</option>
                        <option value="A" {{ old('license_class') == 'A' ? 'selected' : '' }}>Class A</option>
                        <option value="B" {{ old('license_class') == 'B' ? 'selected' : '' }}>Class B</option>
                        <option value="C" {{ old('license_class') == 'C' ? 'selected' : '' }}>Class C</option>                        
                    </x-base.form-select>
                </div>
                
                <div>
                    <x-base.form-label for="state_of_issue">State of Issue *</x-base.form-label>
                    <x-base.form-select id="state_of_issue" name="state_of_issue" required>
                        <option value="">Select State</option>
                        @foreach($states as $code => $name)
                            <option value="{{ $code }}" {{ old('state_of_issue') == $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </x-base.form-select>
                </div>                
                
                <div>
                    <x-base.form-label for="expiration_date">Expiration Date *</x-base.form-label>
                    <x-base.litepicker id="expiration_date" name="expiration_date" 
                        value="{{ old('expiration_date') }}" placeholder="MM/DD/YYYY" required />
                </div>
                
                <div class="sm:col-span-2">
                    <x-base.form-label for="restrictions">Restrictions</x-base.form-label>
                    <x-base.form-input type="text" id="restrictions" name="restrictions" 
                        value="{{ old('restrictions') }}" placeholder="Enter any restrictions (optional)" />
                </div>
            </div>
        </div>

        <!-- CDL Information -->
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Award" />
                CDL Information
            </h3>
            
            <div class="space-y-6">
                <!-- CDL Checkbox -->
                <div class="flex items-center gap-3">
                    <input id="is_cdl" name="is_cdl" type="checkbox" value="1" {{ old('is_cdl') ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary focus:ring-offset-0" />
                    <label for="is_cdl" class="text-slate-700 font-medium cursor-pointer">
                        This is a CDL (Commercial Driver's License)
                    </label>
                </div>

                <!-- CDL Endorsements (hidden by default) -->
                @if($endorsements->count() > 0)
                <div id="cdl_endorsements" class="hidden mt-6 py-6 border-t border-b border-slate-200">
                    <x-base.form-label class="mb-3">CDL Endorsements</x-base.form-label>
                    <p class="text-sm text-slate-500 mb-4">Select all endorsements that apply to this license</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($endorsements as $endorsement)
                        <div class="flex items-center gap-3">
                            <input id="endorsement_{{ $endorsement->id }}" name="endorsements[]" type="checkbox" 
                                value="{{ $endorsement->id }}" 
                                {{ in_array($endorsement->id, old('endorsements', [])) ? 'checked' : '' }}
                                class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary focus:ring-offset-0" />
                            <label for="endorsement_{{ $endorsement->id }}" class="text-slate-700 cursor-pointer">
                                <span class="font-medium">{{ $endorsement->code }}</span> - {{ $endorsement->name }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Primary License Checkbox -->
                <div class="flex items-center gap-3">
                    <input id="is_primary" name="is_primary" type="checkbox" value="1" {{ old('is_primary') ? 'checked' : '' }}
                        class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary focus:ring-offset-0" />
                    <label for="is_primary" class="text-slate-700 font-medium cursor-pointer">
                        Set as primary license
                    </label>
                </div>
            </div>
        </div>

        <!-- License Images -->
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Image" />
                License Images
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-base.form-label for="license_front">Front of License</x-base.form-label>
                    <input type="file" id="license_front" name="license_front" accept="image/*"
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                    <p class="text-xs text-slate-400 mt-1">Max 5MB, JPG/PNG</p>
                    <div id="front_image_preview" class="mt-2 hidden">
                        <img id="front_preview_img" src="" alt="Front Preview" class="max-w-[200px] max-h-[150px] rounded-lg border border-slate-200">
                    </div>
                </div>
                
                <div>
                    <x-base.form-label for="license_back">Back of License</x-base.form-label>
                    <input type="file" id="license_back" name="license_back" accept="image/*"
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                    <p class="text-xs text-slate-400 mt-1">Max 5MB, JPG/PNG</p>
                    <div id="back_image_preview" class="mt-2 hidden">
                        <img id="back_preview_img" src="" alt="Back Preview" class="max-w-[200px] max-h-[150px] rounded-lg border border-slate-200">
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Documents -->
        <div class="box box--stacked p-6">
            <h3 class="text-lg font-semibold mb-4 text-slate-800 border-b pb-2 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                Additional Documents
            </h3>
            <p class="text-sm text-slate-500 mb-4">Upload any additional documents related to this license (tickets, violations, etc.)</p>
            
            <div>
                <x-base.form-label for="documents">Documents</x-base.form-label>
                <input type="file" id="documents" name="documents[]" accept=".pdf,.jpg,.jpeg,.png,.gif" multiple
                    class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                <p class="text-xs text-slate-400 mt-1">Max 10MB per file, PDF/JPG/PNG. You can select multiple files.</p>
                <div id="documents_preview" class="mt-3 space-y-2"></div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
            <x-base.button as="a" href="{{ route('driver.licenses.index') }}" variant="outline-secondary">
                Cancel
            </x-base.button>
            <x-base.button type="submit" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Save" />
                Save License
            </x-base.button>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle CDL endorsements visibility
    const cdlCheckbox = document.getElementById('is_cdl');
    const endorsementsSection = document.getElementById('cdl_endorsements');
    
    function toggleEndorsements() {
        if (endorsementsSection) {
            if (cdlCheckbox.checked) {
                endorsementsSection.classList.remove('hidden');
            } else {
                endorsementsSection.classList.add('hidden');
                // Uncheck all endorsements when CDL is unchecked
                const endorsementCheckboxes = endorsementsSection.querySelectorAll('input[type="checkbox"]');
                endorsementCheckboxes.forEach(checkbox => checkbox.checked = false);
            }
        }
    }
    
    toggleEndorsements();
    cdlCheckbox.addEventListener('change', toggleEndorsements);
    
    // Image preview
    function setupImagePreview(inputId, previewId, imgId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const img = document.getElementById(imgId);
        
        if (input && preview && img) {
            input.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                        preview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.classList.add('hidden');
                }
            });
        }
    }
    
    setupImagePreview('license_front', 'front_image_preview', 'front_preview_img');
    setupImagePreview('license_back', 'back_image_preview', 'back_preview_img');
    
    // Documents preview
    const documentsInput = document.getElementById('documents');
    const documentsPreview = document.getElementById('documents_preview');
    
    if (documentsInput && documentsPreview) {
        documentsInput.addEventListener('change', function(event) {
            documentsPreview.innerHTML = '';
            const files = event.target.files;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileDiv = document.createElement('div');
                fileDiv.className = 'flex items-center gap-2 p-2 bg-slate-50 rounded-lg';
                
                const icon = file.type === 'application/pdf' 
                    ? '<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V6h-4V2H4v16zm8-16l4 4h-4V2z"/></svg>'
                    : '<svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
                
                fileDiv.innerHTML = icon + '<span class="text-sm text-slate-700">' + file.name + '</span><span class="text-xs text-slate-400">(' + (file.size / 1024).toFixed(1) + ' KB)</span>';
                documentsPreview.appendChild(fileDiv);
            }
        });
    }
});
</script>
@endpush
