@extends('../themes/' . $activeTheme)
@section('title', 'Create Traffic Conviction')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Traffic Convictions', 'url' => route('carrier.traffic.index')],
        ['label' => 'Create', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="py-5">
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="alert alert-success flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
                {{ session('error') }}
            </div>
        @endif

        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="FileCheck" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Conviction Details</h1>
                        <p class="text-slate-600">Manage traffic convictions for your drivers</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    
                    <x-base.button as="a" href="{{ route('carrier.traffic.index') }}" variant="primary" class="w-full sm:w-auto"
                        class="flex items-center">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="box box--stacked">            
            <div class="box-body p-5">
                <form action="{{ route('carrier.traffic.store') }}" method="POST" enctype="multipart/form-data" id="createForm">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Driver -->
                        <div>
                            <x-base.form-label for="user_driver_detail_id">
                                Driver <span class="text-danger">*</span>
                            </x-base.form-label>
                            <select id="user_driver_detail_id" name="user_driver_detail_id"
                                class="tom-select w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('user_driver_detail_id') border-danger @enderror"
                                required>
                                <option value="">Select Driver</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}"
                                        {{ old('user_driver_detail_id') == $driver->id ? 'selected' : '' }}>                                        
                                        {{ implode(' ', array_filter([$driver->user->name, $driver->middle_name, $driver->last_name])) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_driver_detail_id')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Conviction Date -->
                        <div>
                            <x-base.form-label for="conviction_date">
                                Conviction Date <span class="text-danger">*</span>
                            </x-base.form-label>
                            <x-base.litepicker id="conviction_date" name="conviction_date"
                                value="{{ old('conviction_date') }}"
                                class="@error('conviction_date') border-danger @enderror" 
                                placeholder="MM/DD/YYYY"
                                required />
                            @error('conviction_date')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div>
                            <x-base.form-label for="location">
                                Location <span class="text-danger">*</span>
                            </x-base.form-label>
                            <x-base.form-input id="location" name="location" type="text" 
                                placeholder="Enter location"
                                value="{{ old('location') }}"
                                class="@error('location') border-danger @enderror"
                                required />
                            @error('location')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Charge -->
                        <div>
                            <x-base.form-label for="charge">
                                Charge <span class="text-danger">*</span>
                            </x-base.form-label>
                            <x-base.form-input id="charge" name="charge" type="text" 
                                placeholder="Enter charge"
                                value="{{ old('charge') }}"
                                class="@error('charge') border-danger @enderror"
                                required />
                            @error('charge')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Penalty -->
                        <div class="md:col-span-2">
                            <x-base.form-label for="penalty">
                                Penalty <span class="text-danger">*</span>
                            </x-base.form-label>
                            <x-base.form-input id="penalty" name="penalty" type="text" 
                                placeholder="Enter penalty"
                                value="{{ old('penalty') }}"
                                class="@error('penalty') border-danger @enderror"
                                required />
                            @error('penalty')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Documents Upload -->
                        <div class="md:col-span-2">
                            <x-base.form-label for="documents">
                                Documents (Optional)
                            </x-base.form-label>
                            <div class="border-2 border-dashed border-slate-200 rounded-md p-6 mt-2">
                                <div class="text-center">
                                    <input type="file" name="documents[]" id="documents" multiple
                                        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                        class="hidden"
                                        onchange="handleFileSelect(event)">
                                    <label for="documents" class="cursor-pointer">
                                        <x-base.lucide class="mx-auto h-12 w-12 text-slate-400 mb-3" icon="upload-cloud" />
                                        <p class="text-sm text-slate-600">
                                            <span class="text-primary font-medium">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">
                                            PDF, JPG, PNG, DOC, DOCX (Max 10MB each)
                                        </p>
                                    </label>
                                </div>
                                <div id="file-preview" class="mt-4 hidden">
                                    <div class="text-sm font-medium text-slate-700 mb-2">Selected Files:</div>
                                    <div id="file-list" class="space-y-2"></div>
                                </div>
                            </div>
                            @error('documents')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                            @error('documents.*')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-2 mt-6 pt-6 border-t border-slate-200">
                        <x-base.button as="a" href="{{ route('carrier.traffic.index') }}" 
                            variant="outline-secondary">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary" id="submit-btn">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="save" />
                            Create Conviction
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Store selected files in a DataTransfer object to allow removal
            let selectedFiles = new DataTransfer();
            
            // File selection handler - Define globally
            window.handleFileSelect = function(event) {
                const files = event.target.files;
                
                // Add new files to selectedFiles
                Array.from(files).forEach(file => {
                    selectedFiles.items.add(file);
                });
                
                // Update the file input with the new FileList
                document.getElementById('documents').files = selectedFiles.files;
                
                // Update preview
                updateFilePreview();
            };
            
            // Function to update file preview
            function updateFilePreview() {
                const filePreview = document.getElementById('file-preview');
                const fileList = document.getElementById('file-list');
                const files = selectedFiles.files;
                
                if (files.length > 0) {
                    filePreview.classList.remove('hidden');
                    fileList.innerHTML = '';
                    
                    Array.from(files).forEach((file, index) => {
                        const fileSize = (file.size / 1024).toFixed(2);
                        const fileItem = document.createElement('div');
                        fileItem.className = 'flex items-center justify-between p-2 bg-slate-50 rounded';
                        fileItem.innerHTML = `
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-sm text-slate-700">${file.name}</span>
                                <span class="text-xs text-slate-500">(${fileSize} KB)</span>
                            </div>
                            <button type="button" onclick="removeFile(${index})" class="text-danger hover:text-danger/80">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        `;
                        fileList.appendChild(fileItem);
                    });
                } else {
                    filePreview.classList.add('hidden');
                }
            }
            
            // Function to remove a file from selection
            window.removeFile = function(index) {
                const dt = new DataTransfer();
                const files = selectedFiles.files;
                
                // Add all files except the one at the specified index
                Array.from(files).forEach((file, i) => {
                    if (i !== index) {
                        dt.items.add(file);
                    }
                });
                
                // Update selectedFiles
                selectedFiles = dt;
                
                // Update the file input
                document.getElementById('documents').files = selectedFiles.files;
                
                // Update preview
                updateFilePreview();
            };
            
            document.addEventListener('DOMContentLoaded', function() {
                // Form submission protection
                const form = document.getElementById('createForm');
                const submitBtn = document.getElementById('submit-btn');
                let isSubmitting = false;

                form.addEventListener('submit', function(e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    
                    isSubmitting = true;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Creating...';
                    
                    // Re-enable after 10 seconds as safety measure
                    setTimeout(() => {
                        isSubmitting = false;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>Create Conviction';
                    }, 10000);
                });
            });
        </script>
    @endpush
@endsection

@pushOnce('scripts')
    @vite('resources/js/components/base/tom-select.js')
@endPushOnce