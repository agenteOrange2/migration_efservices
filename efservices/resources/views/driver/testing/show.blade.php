@extends('../themes/' . $activeTheme)
@section('title', 'Test Details - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Testing', 'url' => route('driver.testing.index')],
        ['label' => 'Test Details', 'active' => true],
    ];
@endphp

@section('subcontent')

<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Flash Messages -->
@if (session()->has('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
        {{ session('success') }}
    </div>
@endif

@if (session()->has('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="alert-triangle" />
        {{ session('error') }}
    </div>
@endif

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Activity" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Drug & Alcohol Test Details</h1>
                <div class="flex items-center gap-3">
                    <p class="text-slate-600">Test ID: #{{ $testing->id }}</p>
                    @if($testing->status == 'Pending Review')
                        <x-base.badge variant="warning" class="gap-1.5">
                            <span class="w-2 h-2 bg-warning rounded-full"></span>
                            {{ $testing->status }}
                        </x-base.badge>
                    @elseif($testing->status == 'Completed')
                        <x-base.badge variant="success" class="gap-1.5">
                            <span class="w-2 h-2 bg-success rounded-full"></span>
                            {{ $testing->status }}
                        </x-base.badge>
                    @else
                        <x-base.badge variant="secondary" class="gap-1.5">
                            <span class="w-2 h-2 bg-slate-400 rounded-full"></span>
                            {{ $testing->status }}
                        </x-base.badge>
                    @endif
                    
                    @if(in_array(strtolower($testing->test_result ?? ''), ['negative', 'passed']))
                        <x-base.badge variant="success" class="gap-1.5">
                            <span class="w-2 h-2 bg-success rounded-full"></span>
                            {{ ucfirst($testing->test_result) }}
                        </x-base.badge>
                    @elseif(in_array(strtolower($testing->test_result ?? ''), ['positive', 'failed']))
                        <x-base.badge variant="danger" class="gap-1.5">
                            <span class="w-2 h-2 bg-danger rounded-full"></span>
                            {{ ucfirst($testing->test_result) }}
                        </x-base.badge>
                    @else
                        <x-base.badge variant="warning" class="gap-1.5">
                            <span class="w-2 h-2 bg-warning rounded-full"></span>
                            {{ $testing->test_result ?? 'Pending' }}
                        </x-base.badge>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            @if($testing->hasMedia('drug_test_pdf'))
                <x-base.button as="a" href="{{ $testing->getFirstMediaUrl('drug_test_pdf') }}" target="_blank" variant="outline-primary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="Download" />
                    Download PDF
                </x-base.button>
            @endif
            <x-base.button as="a" href="{{ route('driver.testing.index') }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Tests
            </x-base.button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content (2/3) -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Test Details Card -->
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="ClipboardCheck" />
                <h2 class="text-lg font-semibold text-slate-800">Test Details</h2>
            </div>
            <div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Left Column -->
                    <div class="space-y-3">
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Test Date</label>
                            <p class="text-sm font-semibold text-slate-800">
                                {{ $testing->test_date ? $testing->test_date->format('m/d/Y') : 'Not specified' }}
                            </p>
                        </div>
                        
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Test Type</label>
                            <p class="text-sm font-semibold text-slate-800">{{ $testing->test_type ?: 'Not specified' }}</p>
                        </div>
                        
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Location</label>
                            <p class="text-sm font-semibold text-slate-800">{{ $testing->location ?: 'Not specified' }}</p>
                        </div>
                        
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Administered By</label>
                            <p class="text-sm font-semibold text-slate-800">{{ $testing->administered_by ?: 'Not specified' }}</p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-3">
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Requested By</label>
                            <p class="text-sm font-semibold text-slate-800">{{ $testing->requester_name ?: 'Not specified' }}</p>
                        </div>
                        
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Scheduled Time</label>
                            <p class="text-sm font-semibold text-slate-800">
                                {{ $testing->scheduled_time ? $testing->scheduled_time->format('m/d/Y h:i A') : 'Not scheduled' }}
                            </p>
                        </div>
                        
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Next Test Due</label>
                            <p class="text-sm font-semibold text-slate-800">
                                {{ $testing->next_test_due ? $testing->next_test_due->format('m/d/Y') : 'Not specified' }}
                            </p>
                        </div>
                        
                        @if($testing->mro)
                            <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                                <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">MRO</label>
                                <p class="text-sm font-semibold text-slate-800">{{ $testing->mro }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Test Categories -->
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-3 block">Test Categories</label>
                    <div class="flex flex-wrap gap-2">
                        @if ($testing->is_random_test)
                            <x-base.badge variant="primary" class="gap-1.5">
                                <x-base.lucide class="w-3 h-3" icon="Shuffle" />
                                Random
                            </x-base.badge>
                        @endif
                        @if ($testing->is_post_accident_test)
                            <x-base.badge variant="warning" class="gap-1.5">
                                <x-base.lucide class="w-3 h-3" icon="AlertTriangle" />
                                Post Accident
                            </x-base.badge>
                        @endif
                        @if ($testing->is_reasonable_suspicion_test)
                            <x-base.badge variant="danger" class="gap-1.5">
                                <x-base.lucide class="w-3 h-3" icon="AlertCircle" />
                                Reasonable Suspicion
                            </x-base.badge>
                        @endif
                        @if ($testing->is_pre_employment_test)
                            <x-base.badge variant="success" class="gap-1.5">
                                <x-base.lucide class="w-3 h-3" icon="UserPlus" />
                                Pre-Employment
                            </x-base.badge>
                        @endif
                        @if ($testing->is_follow_up_test)
                            <x-base.badge variant="primary" class="gap-1.5">
                                <x-base.lucide class="w-3 h-3" icon="Repeat" />
                                Follow-Up
                            </x-base.badge>
                        @endif
                        @if ($testing->is_return_to_duty_test)
                            <x-base.badge variant="primary" class="gap-1.5">
                                <x-base.lucide class="w-3 h-3" icon="ArrowLeftCircle" />
                                Return-To-Duty
                            </x-base.badge>
                        @endif
                        @if ($testing->is_other_reason_test)
                            <x-base.badge variant="secondary" class="gap-1.5">
                                <x-base.lucide class="w-3 h-3" icon="MoreHorizontal" />
                                Other
                            </x-base.badge>
                        @endif
                        @if (!$testing->is_random_test && !$testing->is_post_accident_test && 
                             !$testing->is_reasonable_suspicion_test && !$testing->is_pre_employment_test &&
                             !$testing->is_follow_up_test && !$testing->is_return_to_duty_test && 
                             !$testing->is_other_reason_test)
                            <span class="text-sm text-slate-500">None specified</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Section Card -->
        @if($testing->notes)
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="StickyNote" />
                    <h2 class="text-lg font-semibold text-slate-800">Notes & Additional Information</h2>
                </div>
                <div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 min-h-[100px]">
                        <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $testing->notes }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Test History Card -->
        @if($testHistory->count() > 0)
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="History" />
                    <h2 class="text-lg font-semibold text-slate-800">Previous Tests</h2>
                </div>
                <div class="space-y-3">
                    @foreach ($testHistory as $test)
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 hover:border-primary/30 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ $test->test_date ? $test->test_date->format('m/d/Y') : 'No date' }}
                                        </p>
                                        @if ($test->test_result)
                                            @if (in_array(strtolower($test->test_result), ['passed', 'negative']))
                                                <x-base.badge variant="success" class="text-xs">
                                                    {{ ucfirst($test->test_result) }}
                                                </x-base.badge>
                                            @elseif (in_array(strtolower($test->test_result), ['failed', 'positive']))
                                                <x-base.badge variant="danger" class="text-xs">
                                                    {{ ucfirst($test->test_result) }}
                                                </x-base.badge>
                                            @else
                                                <x-base.badge variant="warning" class="text-xs">
                                                    {{ ucfirst($test->test_result) }}
                                                </x-base.badge>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="space-y-1 text-xs text-slate-600">
                                        @if ($test->test_type)
                                            <p><span class="font-medium">Type:</span> {{ $test->test_type }}</p>
                                        @endif
                                        @if ($test->location)
                                            <p><span class="font-medium">Location:</span> {{ $test->location }}</p>
                                        @endif
                                    </div>
                                </div>
                                <x-base.button 
                                    as="a" 
                                    href="{{ route('driver.testing.show', $test->id) }}"
                                    variant="outline-secondary" 
                                    size="sm"
                                    class="flex-shrink-0">
                                    <x-base.lucide class="w-3 h-3" icon="Eye" />
                                </x-base.button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Sidebar (1/3) -->
    <div class="space-y-6">
        <!-- Upload Test Results Card -->
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Upload" />
                <h2 class="text-lg font-semibold text-slate-800">Upload Your Results</h2>
            </div>
            
            <form action="{{ route('driver.testing.upload-results', $testing->id) }}" method="POST" enctype="multipart/form-data" id="upload-results-form">
                @csrf
                <div class="space-y-4">
                    <!-- Drag and Drop Area -->
                    <div class="border-2 border-dashed border-slate-300 rounded-lg p-6 text-center hover:border-primary transition-colors cursor-pointer" id="drop-zone">
                        <input type="file" name="results[]" id="results-input" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden">
                        <x-base.lucide class="w-12 h-12 mx-auto text-slate-400 mb-3" icon="UploadCloud" />
                        <p class="text-sm font-medium text-slate-700 mb-1">Click to upload or drag and drop</p>
                        <p class="text-xs text-slate-500">PDF, JPG, PNG, DOC, DOCX (Max 10MB each)</p>
                    </div>
                    
                    <!-- Selected Files Preview -->
                    <div id="files-preview" class="space-y-2 hidden"></div>
                    
                    <!-- Upload Button -->
                    <x-base.button type="submit" variant="primary" class="w-full justify-center gap-2" id="upload-btn" disabled>
                        <x-base.lucide class="w-4 h-4" icon="Upload" />
                        Upload Files
                    </x-base.button>
                    
                    <p class="text-xs text-slate-500 text-center">
                        Status will change to <span class="font-medium">Pending Review</span> after upload
                    </p>
                </div>
            </form>
        </div>

        <!-- Uploaded Documents Card -->
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Paperclip" />
                <h2 class="text-lg font-semibold text-slate-800">Documents</h2>
            </div>
            @php
                $docs = collect();
                foreach(['drug_test_pdf', 'test_results', 'test_certificates', 'document_attachments'] as $collection) {
                    $docs = $docs->merge($testing->getMedia($collection));
                }
            @endphp
            
            @if($docs->count() > 0)
                <div class="space-y-2">
                    @foreach($docs as $doc)
                        <a href="{{ $doc->getUrl() }}" target="_blank" class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                            @if (str_contains($doc->mime_type, 'pdf'))
                                <x-base.lucide class="w-5 h-5 text-danger" icon="FileText" />
                            @elseif (str_contains($doc->mime_type, 'image'))
                                <x-base.lucide class="w-5 h-5 text-success" icon="Image" />
                            @else
                                <x-base.lucide class="w-5 h-5 text-primary" icon="File" />
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-700 font-medium truncate">{{ $doc->name }}</p>
                                <p class="text-xs text-slate-500">{{ human_filesize($doc->size) }}</p>
                            </div>
                            <x-base.lucide class="w-4 h-4 text-slate-400 flex-shrink-0" icon="ExternalLink" />
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-slate-400 text-sm text-center py-4">No documents available</p>
            @endif
        </div>
        
        <!-- Carrier Information Card -->
        @if($testing->carrier)
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Building2" />
                    <h2 class="text-lg font-semibold text-slate-800">Carrier Information</h2>
                </div>
                <div class="space-y-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Carrier Name</label>
                        <p class="text-sm font-semibold text-slate-800">{{ $testing->carrier->name ?? 'N/A' }}</p>
                    </div>
                    @if($testing->carrier->usdot)
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">DOT Number</label>
                            <p class="text-sm font-semibold text-slate-800">{{ $testing->carrier->usdot }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('results-input');
    const filesPreview = document.getElementById('files-preview');
    const uploadBtn = document.getElementById('upload-btn');
    const form = document.getElementById('upload-results-form');
    let selectedFiles = [];

    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function(e) {
        handleFiles(Array.from(e.target.files));
    });

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-primary', 'bg-primary/5');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-primary', 'bg-primary/5');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-primary', 'bg-primary/5');
        handleFiles(Array.from(e.dataTransfer.files));
    });

    function handleFiles(files) {
        const validFiles = files.filter(file => {
            const validTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            const maxSize = 10 * 1024 * 1024;
            
            if (!validTypes.includes(file.type)) {
                alert(`File "${file.name}" has invalid type. Only PDF, JPG, PNG, DOC, DOCX allowed.`);
                return false;
            }
            
            if (file.size > maxSize) {
                alert(`File "${file.name}" is too large. Maximum size is 10MB.`);
                return false;
            }
            
            return true;
        });

        if (validFiles.length > 0) {
            selectedFiles = validFiles;
            displayFiles();
            uploadBtn.disabled = false;
        }
    }

    function displayFiles() {
        filesPreview.innerHTML = '';
        filesPreview.classList.remove('hidden');
        
        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200';
            fileItem.innerHTML = `
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <svg class="w-5 h-5 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">${file.name}</p>
                        <p class="text-xs text-slate-500">${formatFileSize(file.size)}</p>
                    </div>
                </div>
                <button type="button" class="text-danger hover:text-danger/80 p-1" onclick="removeFile(${index})">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            filesPreview.appendChild(fileItem);
        });
    }

    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        if (selectedFiles.length === 0) {
            filesPreview.classList.add('hidden');
            uploadBtn.disabled = true;
            fileInput.value = '';
        } else {
            displayFiles();
        }
    };

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    form.addEventListener('submit', function(e) {
        if (selectedFiles.length === 0) {
            e.preventDefault();
            alert('Please select at least one file to upload.');
            return;
        }
        
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = `
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Uploading...
        `;
    });
});
</script>
@endpush
