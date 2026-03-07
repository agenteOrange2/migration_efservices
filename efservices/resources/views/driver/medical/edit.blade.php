@extends('../themes/' . $activeTheme)
@section('title', 'Edit Medical Information - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Medical', 'url' => route('driver.medical.index')],
        ['label' => 'Edit', 'active' => true],
    ];
@endphp

@section('subcontent')

<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Header -->
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('driver.medical.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
        <x-base.lucide class="w-5 h-5 text-slate-500" icon="ArrowLeft" />
    </a>
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Edit Medical Information</h1>
        <p class="text-slate-500">Update your DOT medical certificate details</p>
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

<form action="{{ route('driver.medical.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Social Security Information -->
            <div class="box box--stacked p-6">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="CreditCard" />
                    Social Security Information
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-base.form-label for="social_security_number">Social Security Number</x-base.form-label>
                        <x-base.form-input type="text" id="social_security_number" name="social_security_number" 
                            value="{{ old('social_security_number', $medical->social_security_number ?? '') }}" 
                            placeholder="XXX-XX-XXXX" />
                        @error('social_security_number')
                            <div class="text-danger text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div>
                        <x-base.form-label for="hire_date">Hire Date</x-base.form-label>
                        @php
                            $hireDateValue = old('hire_date');
                            if (!$hireDateValue && $medical && $medical->hire_date) {
                                $hireDateValue = \Carbon\Carbon::parse($medical->hire_date)->format('m/d/Y');
                            }
                        @endphp
                        <x-base.litepicker id="hire_date" name="hire_date" 
                            value="{{ $hireDateValue }}" 
                            placeholder="MM/DD/YYYY" />
                        @error('hire_date')
                            <div class="text-danger text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="sm:col-span-2">
                        <x-base.form-label for="location">Location</x-base.form-label>
                        <x-base.form-input type="text" id="location" name="location" 
                            value="{{ old('location', $medical->location ?? '') }}" 
                            placeholder="City, State" />
                        @error('location')
                            <div class="text-danger text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Medical Certificate Details -->
            <div class="box box--stacked p-6">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Heart" />
                    Medical Certificate Details
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-base.form-label for="medical_examiner_name">Medical Examiner Name</x-base.form-label>
                        <x-base.form-input type="text" id="medical_examiner_name" name="medical_examiner_name" 
                            value="{{ old('medical_examiner_name', $medical->medical_examiner_name ?? '') }}" 
                            placeholder="Dr. John Smith" />
                    </div>
                    
                    <div>
                        <x-base.form-label for="medical_examiner_registry_number">Registry Number</x-base.form-label>
                        <x-base.form-input type="text" id="medical_examiner_registry_number" name="medical_examiner_registry_number" 
                            value="{{ old('medical_examiner_registry_number', $medical->medical_examiner_registry_number ?? '') }}" 
                            placeholder="Enter registry number" />
                    </div>
                    
                    <!-- Medical Card Expiration Date -->
                    <div class="sm:col-span-2">
                        <x-base.form-label for="medical_card_expiration_date">Medical Card Expiration Date</x-base.form-label>
                        @php
                            $expiryValue = old('medical_card_expiration_date');
                            if (!$expiryValue && $medical) {
                                $dateField = $medical->medical_card_expiration_date;
                                if ($dateField) {
                                    $expiryValue = \Carbon\Carbon::parse($dateField)->format('m/d/Y');
                                }
                            }
                        @endphp
                        <x-base.litepicker id="medical_card_expiration_date" name="medical_card_expiration_date" 
                            value="{{ $expiryValue }}" 
                            placeholder="MM/DD/YYYY" />
                        @error('medical_card_expiration_date')
                            <div class="text-danger text-sm mt-1">{{ $message }}</div>
                        @enderror
                        <p class="text-xs text-slate-500 mt-1">When does your DOT medical certificate expire?</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Social Security Card Upload -->
            <div class="box box--stacked p-6">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="CreditCard" />
                    Upload Social Security Card
                </h3>
                
                <div>
                    <x-base.form-label>Social Security Card</x-base.form-label>
                    @php
                        $socialSecurityCardUrl = null;
                        if ($medical) {
                            $socialSecurityCardUrl = $medical->getFirstMediaUrl('social_security_card');
                        }
                    @endphp
                    @if($socialSecurityCardUrl)
                    <div class="mb-2 p-2 bg-slate-50 rounded-lg flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4 text-success" icon="CheckCircle" />
                        <span class="text-sm text-slate-600">Current file uploaded</span>
                        <a href="{{ $socialSecurityCardUrl }}" target="_blank" class="text-primary text-sm hover:underline ml-auto">View</a>
                    </div>
                    @endif
                    <input type="file" name="social_security_card" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                    <p class="text-xs text-slate-400 mt-2">Max 10MB. PDF, JPG, PNG accepted.</p>
                </div>
            </div>

            <!-- Medical Card Upload -->
            <div class="box box--stacked p-6">
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Upload" />
                    Upload Medical Card
                </h3>
                
                <div>
                    <x-base.form-label>Medical Card</x-base.form-label>
                    @php
                        $medicalCardUrl = null;
                        if ($medical) {
                            $medicalCardUrl = $medical->getFirstMediaUrl('medical_card') ?: $medical->getFirstMediaUrl('medical_certificate');
                        }
                    @endphp
                    @if($medicalCardUrl)
                    <div class="mb-2 p-2 bg-slate-50 rounded-lg flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4 text-success" icon="CheckCircle" />
                        <span class="text-sm text-slate-600">Current file uploaded</span>
                        <a href="{{ $medicalCardUrl }}" target="_blank" class="text-primary text-sm hover:underline ml-auto">View</a>
                    </div>
                    @endif
                    <input type="file" name="medical_card" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                    <p class="text-xs text-slate-400 mt-2">Max 10MB. PDF, JPG, PNG accepted.</p>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h3 class="font-semibold text-slate-800 mb-4">Actions</h3>
                <div class="space-y-3">
                    <x-base.button type="submit" variant="primary" class="w-full gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Save" />
                        Save Changes
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('driver.medical.index') }}" variant="outline-secondary" class="w-full">
                        Cancel
                    </x-base.button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
