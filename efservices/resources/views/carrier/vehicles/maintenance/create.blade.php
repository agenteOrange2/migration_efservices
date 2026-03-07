@extends('../themes/' . $activeTheme)
@section('title', 'New Maintenance Record')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Vehicles', 'url' => route('carrier.vehicles.index')],
        ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('carrier.vehicles.show', $vehicle)],
        ['label' => 'Maintenance', 'url' => route('carrier.vehicles.maintenance.index', $vehicle)],
        ['label' => 'New Record', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    New Maintenance Record - {{ $vehicle->make }} {{ $vehicle->model }}
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('carrier.vehicles.maintenance.index', $vehicle) }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to Maintenance List
                    </x-base.button>
                </div>
            </div>

            <div class="intro-y box p-5 mt-5">
                <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                    <div class="font-medium text-base truncate">Maintenance Information</div>                    
                </div>

                <form action="{{ route('carrier.vehicles.maintenance.store', $vehicle) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mt-3">
                        <x-base.form-label for="unit">Unit <span class="text-danger">*</span></x-base.form-label>
                        <x-base.form-input id="unit" name="unit" type="text"
                            class="w-full @error('unit') border-danger @enderror" 
                            placeholder="Unit number or identifier" 
                            value="{{ old('unit', $vehicle->company_unit_number ?? '') }}" 
                            required />
                        @error('unit')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <x-base.form-label for="service_tasks">Service Tasks <span class="text-danger">*</span></x-base.form-label>
                        <x-base.form-textarea id="service_tasks" name="service_tasks" 
                            class="w-full @error('service_tasks') border-danger @enderror"
                            rows="3" 
                            placeholder="Describe the maintenance work performed"
                            required>{{ old('service_tasks') }}</x-base.form-textarea>
                        @error('service_tasks')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="service_date">Service Date <span class="text-danger">*</span></x-base.form-label>
                            <x-base.litepicker id="service_date" name="service_date" 
                                value="{{ old('service_date', date('m/d/Y')) }}"
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
                                value="{{ old('next_service_date') }}"
                                class="@error('next_service_date') border-danger @enderror" 
                                placeholder="MM/DD/YYYY" />
                            @error('next_service_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="vendor_mechanic">Vendor/Mechanic <span class="text-danger">*</span></x-base.form-label>
                            <x-base.form-input id="vendor_mechanic" name="vendor_mechanic" type="text"
                                class="w-full @error('vendor_mechanic') border-danger @enderror" 
                                placeholder="e.g., ABC Auto Shop" 
                                value="{{ old('vendor_mechanic') }}" 
                                required />
                            @error('vendor_mechanic')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="cost">Cost <span class="text-danger">*</span></x-base.form-label>
                            <x-base.form-input id="cost" name="cost" type="number"
                                class="w-full @error('cost') border-danger @enderror" 
                                placeholder="0.00" 
                                step="0.01" 
                                min="0" 
                                value="{{ old('cost') }}" 
                                required />
                            @error('cost')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <x-base.form-label for="odometer">Odometer Reading <span class="text-danger">*</span></x-base.form-label>
                        <x-base.form-input id="odometer" name="odometer" type="number"
                            class="w-full @error('odometer') border-danger @enderror" 
                            placeholder="e.g., 50000" 
                            min="0" 
                            value="{{ old('odometer') }}" 
                            required />
                        @error('odometer')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <x-base.form-label for="description">Description</x-base.form-label>
                        <x-base.form-textarea id="description" name="description" 
                            class="w-full @error('description') border-danger @enderror"
                            rows="4" 
                            placeholder="Additional notes or details about the maintenance">{{ old('description') }}</x-base.form-textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- File upload section using Livewire -->
                    <div class="mt-6 pt-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <h3 class="text-lg font-medium mb-3">Attachments</h3>
                        <p class="text-sm text-slate-500 mb-4">Upload invoices, receipts, or inspection reports</p>

                        <!-- Hidden field to store file data -->
                        <input type="hidden" name="files" id="files_data" value="">

                        <!-- Livewire file uploader component -->
                        @livewire('components.file-uploader', [
                            'modelName' => 'maintenance_files',
                            'modelIndex' => 0,
                            'autoUpload' => true
                        ])
                    </div>

                    <div class="mt-5 pt-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <div class="flex items-center mb-3">
                            <input id="status" type="checkbox" name="status" value="1"
                                {{ old('status') ? 'checked' : '' }} 
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded">
                            <label for="status" class="ml-2 text-sm">Mark as Completed</label>
                        </div>
                        @error('status')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror

                        <div class="flex items-center">
                            <input id="is_historical" type="checkbox" name="is_historical" value="1"
                                {{ old('is_historical') ? 'checked' : '' }} 
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded">
                            <label for="is_historical" class="ml-2 text-sm">
                                Historical Record (allows flexible date validation)
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 ml-6 mt-1">
                            Check this if entering past maintenance records where dates may not follow normal validation rules
                        </p>
                        @error('is_historical')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex justify-end mt-6 pt-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <x-base.button as="a" href="{{ route('carrier.vehicles.maintenance.index', $vehicle) }}"
                            variant="outline-secondary" class="mr-2">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Save" />
                            Create Maintenance Record
                        </x-base.button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const filesDataInput = document.getElementById('files_data');
                let uploadedFiles = [];

                // Auto-calculate next_service_date = service_date + 3 months
                const serviceDateInput = document.getElementById('service_date');
                const nextServiceDateInput = document.getElementById('next_service_date');

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

                serviceDateInput.addEventListener('change', function() {
                    if (!nextServiceDateInput.value || nextServiceDateInput.value.trim() === '') {
                        setNextServiceDate(this.value);
                    }
                });

                // Set initial default: today + 3 months (only if no old value)
                if (!nextServiceDateInput.value || nextServiceDateInput.value.trim() === '') {
                    const today = new Date();
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const dd = String(today.getDate()).padStart(2, '0');
                    const yyyy = today.getFullYear();
                    setNextServiceDate(mm + '/' + dd + '/' + yyyy);
                }

                // Listen for file uploaded event from Livewire
                window.addEventListener('fileUploaded', (event) => {
                    const fileData = event.detail;
                    
                    // Extract the relevant file information
                    const fileInfo = {
                        tempPath: fileData.tempPath || fileData.path,
                        originalName: fileData.originalName || fileData.name,
                        mimeType: fileData.mimeType,
                        size: fileData.size,
                        id: fileData.previewData?.id || 'temp_' + Date.now()
                    };
                    
                    uploadedFiles.push(fileInfo);
                    filesDataInput.value = JSON.stringify(uploadedFiles);
                    console.log('File uploaded:', fileInfo);
                });

                // Listen for file removed event from Livewire
                window.addEventListener('fileRemoved', (event) => {
                    const eventData = event.detail;
                    const fileId = eventData.fileId || eventData;
                    
                    uploadedFiles = uploadedFiles.filter(file => {
                        return file.id !== fileId && file.tempPath !== fileId;
                    });
                    filesDataInput.value = JSON.stringify(uploadedFiles);
                    console.log('File removed:', fileId);
                });
            });
        </script>
    @endpush
@endsection
