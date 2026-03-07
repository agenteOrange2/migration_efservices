@extends('../themes/' . $activeTheme)
@section('title', 'Create Repair')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Repairs', 'url' => route('carrier.emergency-repairs.index')],
        ['label' => 'Create', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Create New Repair
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('carrier.emergency-repairs.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                </div>
            </div>

            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    @if(session('error'))
                        <div class="alert alert-danger mb-5">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('carrier.emergency-repairs.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Vehicle Selection -->
                            <div>
                                <x-base.form-label for="vehicle_id">Vehicle *</x-base.form-label>
                                <select id="vehicle_id" name="vehicle_id" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('vehicle_id') border-red-500 @enderror" required>
                                    <option value="">Select Vehicle</option>
                                    @foreach ($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->make }} {{ $vehicle->model }} - {{ $vehicle->company_unit_number ?? $vehicle->vin }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                                
                                <!-- Driver Information Display -->
                                <div id="driver-info" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-md hidden">
                                    <div class="text-sm font-medium text-blue-900 mb-2">
                                        <x-base.lucide class="w-4 h-4 inline mr-1" icon="User" />
                                        Assigned Driver
                                    </div>
                                    <div class="space-y-1 text-sm text-slate-600">
                                        <div><span class="font-medium">Name:</span> <span id="driver-name">-</span></div>
                                        <div><span class="font-medium">Email:</span> <span id="driver-email">-</span></div>
                                        <div><span class="font-medium">Phone:</span> <span id="driver-phone">-</span></div>
                                    </div>
                                </div>
                                <div id="no-driver-info" class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-md hidden">
                                    <div class="text-sm text-slate-600">
                                        <x-base.lucide class="w-4 h-4 inline mr-1" icon="AlertCircle" />
                                        No driver assigned to this vehicle
                                    </div>
                                </div>
                            </div>

                            <!-- Repair Name -->
                            <div>
                                <x-base.form-label for="repair_name">Repair Name *</x-base.form-label>
                                <x-base.form-input id="repair_name" name="repair_name" type="text" 
                                    class="w-full @error('repair_name') border-red-500 @enderror" 
                                    placeholder="Enter repair name" value="{{ old('repair_name') }}" required />
                                @error('repair_name')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Repair Date -->
                            <div>
                                <x-base.form-label for="repair_date">Repair Date *</x-base.form-label>
                                <x-base.litepicker id="repair_date" name="repair_date" 
                                    class="@error('repair_date') border-red-500 @enderror"
                                    value="{{ old('repair_date') }}" placeholder="MM/DD/YYYY" required />
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
                                        placeholder="0.00" value="{{ old('cost') }}" required />
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
                                    placeholder="e.g., 125000" value="{{ old('odometer') }}" />
                                @error('odometer')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <x-base.form-label for="status">Status *</x-base.form-label>
                                <select id="status" name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('status') border-red-500 @enderror" required>
                                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('status')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>                            
                        </div>

                        <div class="grid grid-cols-1 gap-6 mt-6">
                            <!-- Description -->
                            <div>
                                <x-base.form-label for="description">Description</x-base.form-label>
                                <x-base.form-textarea id="description" name="description" rows="4"
                                    class="w-full @error('description') border-red-500 @enderror" 
                                    placeholder="Enter repair description">{{ old('description') }}</x-base.form-textarea>
                                @error('description')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <x-base.form-label for="notes">Notes</x-base.form-label>
                                <x-base.form-textarea id="notes" name="notes" rows="4"
                                    class="w-full @error('notes') border-red-500 @enderror" 
                                    placeholder="Enter additional notes">{{ old('notes') }}</x-base.form-textarea>
                                @error('notes')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- File Upload -->
                            <div>
                                <x-base.form-label for="repair_files">Upload Documents (Optional)</x-base.form-label>
                                <div class="border-2 border-dashed border-slate-200/60 dark:border-darkmode-400 rounded-md p-5" id="file-upload-area">
                                    <div class="h-32 relative w-full cursor-pointer">
                                        <input type="file" id="repair_files" name="repair_files[]" multiple 
                                            accept="image/*,.pdf,.doc,.docx" 
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                                        <div class="flex flex-col items-center justify-center h-full text-slate-500 pointer-events-none">
                                            <x-base.lucide class="w-8 h-8 mb-2" icon="Upload" />
                                            <div class="text-sm font-medium">Drop files here or click to upload</div>
                                            <div class="text-xs mt-1">Supports: Images, PDF, DOC, DOCX (Max 10MB each)</div>
                                        </div>
                                    </div>
                                </div>
                                <div id="file-list" class="mt-3 space-y-2"></div>
                                @error('repair_files')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                                @error('repair_files.*')
                                    <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-slate-200/60 dark:border-darkmode-400">
                            <x-base.button as="a" href="{{ route('carrier.emergency-repairs.index') }}" 
                                variant="outline-secondary" class="w-full sm:w-24">
                                Cancel
                            </x-base.button>
                            <x-base.button type="submit" variant="primary" class="w-64">
                                <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Plus" />
                                Create Repair
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
                const fileInput = document.getElementById('repair_files');
                const fileList = document.getElementById('file-list');
                const uploadArea = document.getElementById('file-upload-area');
                const vehicleSelect = document.getElementById('vehicle_id');
                const driverInfo = document.getElementById('driver-info');
                const noDriverInfo = document.getElementById('no-driver-info');

                // Handle vehicle selection change
                if (vehicleSelect) {
                    vehicleSelect.addEventListener('change', function() {
                        const vehicleId = this.value;
                        
                        if (!vehicleId) {
                            driverInfo.classList.add('hidden');
                            noDriverInfo.classList.add('hidden');
                            return;
                        }

                        // Fetch vehicle details including driver info
                        fetch(`/carrier/emergency-repairs/vehicle/${vehicleId}/details`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.driver) {
                                document.getElementById('driver-name').textContent = data.driver.name;
                                document.getElementById('driver-email').textContent = data.driver.email;
                                document.getElementById('driver-phone').textContent = data.driver.phone;
                                driverInfo.classList.remove('hidden');
                                noDriverInfo.classList.add('hidden');
                            } else {
                                driverInfo.classList.add('hidden');
                                noDriverInfo.classList.remove('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching vehicle details:', error);
                            driverInfo.classList.add('hidden');
                            noDriverInfo.classList.add('hidden');
                        });
                    });
                }

                // File upload handling
                if (fileInput) {
                    fileInput.addEventListener('change', function () {
                        displaySelectedFiles(this.files);
                    });
                }

                // Drag and drop functionality
                if (uploadArea) {
                    uploadArea.addEventListener('dragover', function (e) {
                        e.preventDefault();
                        this.classList.add('border-primary');
                    });

                    uploadArea.addEventListener('dragleave', function (e) {
                        e.preventDefault();
                        this.classList.remove('border-primary');
                    });

                    uploadArea.addEventListener('drop', function (e) {
                        e.preventDefault();
                        this.classList.remove('border-primary');
                        if (fileInput) {
                            fileInput.files = e.dataTransfer.files;
                            displaySelectedFiles(e.dataTransfer.files);
                        }
                    });
                }

                function displaySelectedFiles(files) {
                    if (!fileList) return;
                    fileList.innerHTML = '';
                    
                    Array.from(files).forEach((file, index) => {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200/60';
                        
                        const ext = (file.name.split('.').pop() || '').toLowerCase();
                        let iconClass = 'text-slate-500';
                        if (['jpg','jpeg','png','gif','webp'].includes(ext)) iconClass = 'text-blue-500';
                        else if (ext === 'pdf') iconClass = 'text-red-500';
                        
                        fileItem.innerHTML = `
                            <div class="flex items-center gap-2 min-w-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="${iconClass}"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-slate-800 truncate">${file.name}</div>
                                    <div class="text-xs text-slate-500">${formatFileSize(file.size)}</div>
                                </div>
                            </div>
                            <button type="button" class="ml-2 p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" data-index="${index}" title="Remove">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        `;
                        fileList.appendChild(fileItem);
                    });

                    fileList.querySelectorAll('button[data-index]').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            removeFile(parseInt(this.getAttribute('data-index'), 10));
                        });
                    });
                }

                function formatFileSize(bytes) {
                    if (bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }

                function removeFile(index) {
                    const dt = new DataTransfer();
                    const files = Array.from(fileInput.files);
                    files.forEach((f, i) => { if (i !== index) dt.items.add(f); });
                    fileInput.files = dt.files;
                    displaySelectedFiles(fileInput.files);
                }
            });
        </script>
    @endpush
@endsection
