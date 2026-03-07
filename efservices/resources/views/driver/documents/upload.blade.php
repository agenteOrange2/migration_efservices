@extends('../themes/' . $activeTheme)
@section('title', 'Upload Document - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Documents', 'url' => route('driver.documents.index')],
        ['label' => 'Upload', 'active' => true],
    ];
@endphp

@section('subcontent')

<div class="mb-6 block sm:hidden sm:px-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('driver.documents.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
        <x-base.lucide class="w-5 h-5 text-slate-500" icon="ArrowLeft" />
    </a>
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Upload Document</h1>
        <p class="text-slate-500">Add a new document to your library</p>
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

<div class="max-w-full">
    <form action="{{ route('driver.documents.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="box box--stacked p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Upload" />
                Document Details
            </h3>
            
            <div class="space-y-6">
                @php
                    $selectedCategory = old('category', request('category'));
                @endphp
                <div>
                    <x-base.form-label for="category">Document Category *</x-base.form-label>
                    <x-base.form-select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="driving_records" {{ $selectedCategory == 'driving_records' ? 'selected' : '' }}>Driving Records</option>
                        <option value="medical_records" {{ $selectedCategory == 'medical_records' ? 'selected' : '' }}>Medical Records</option>
                        <option value="criminal_records" {{ $selectedCategory == 'criminal_records' ? 'selected' : '' }}>Criminal Records</option>
                        <option value="clearing_house" {{ $selectedCategory == 'clearing_house' ? 'selected' : '' }}>Clearing House</option>
                        <option value="other" {{ $selectedCategory == 'other' ? 'selected' : '' }}>Other</option>
                    </x-base.form-select>
                    <p class="text-xs text-slate-500 mt-1">Select the category that best describes this document</p>
                </div>
                
                <div>
                    <x-base.form-label for="document">Document File *</x-base.form-label>
                    <div class="border-2 border-dashed border-slate-200 rounded-lg p-8 text-center hover:border-primary/50 transition-colors">
                        <x-base.lucide class="w-12 h-12 text-slate-300 mx-auto mb-4" icon="Upload" />
                        <p class="text-slate-600 mb-2">Drag and drop your file here, or</p>
                        <input type="file" id="document" name="document" required accept=".pdf,.jpg,.jpeg,.png"
                            class="tex-center text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                        <p class="text-xs text-slate-400 mt-4">Accepted formats: PDF, JPG, PNG. Max size: 10MB</p>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6 pt-6 border-t border-slate-100">
                <x-base.button type="submit" variant="primary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="Upload" />
                    Upload Document
                </x-base.button>
                <x-base.button as="a" href="{{ route('driver.documents.index') }}" variant="outline-secondary">
                    Cancel
                </x-base.button>
            </div>
        </div>
    </form>
</div>

@endsection
