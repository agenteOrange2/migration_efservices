@extends('../themes/' . $activeTheme)
@section('title', 'New Maintenance Record')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Maintenance', 'url' => route('carrier.maintenance.index')],
        ['label' => 'New Maintenance Record', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    New Maintenance Record
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('carrier.maintenance.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                </div>
            </div>

            <div class="intro-y box p-5 mt-5">
                <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                    <div class="font-medium text-base truncate">Maintenance Information</div>                    
                </div>

                <form action="{{ route('carrier.maintenance.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mt-3">
                        <x-base.form-label for="vehicle_id">Vehicle *</x-base.form-label>
                        <select id="vehicle_id" name="vehicle_id"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('vehicle_id') border-danger @enderror" required>
                            <option value="">Select Vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    @if($vehicle->company_unit_number)
                                        {{ $vehicle->company_unit_number }} - 
                                    @endif
                                    {{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}
                                    @if($vehicle->vin)
                                        (VIN: {{ substr($vehicle->vin, -6) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <x-base.form-label for="service_tasks">Maintenance Type *</x-base.form-label>
                        <select id="service_tasks" name="service_tasks"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('service_tasks') border-danger @enderror" required>
                            <option value="">Select Maintenance Type</option>
                            @foreach ($maintenanceTypes as $type)
                                <option value="{{ $type }}" {{ old('service_tasks') == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('service_tasks')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="unit">Unit *</x-base.form-label>
                            <x-base.form-input id="unit" name="unit" type="text"
                                class="w-full @error('unit') border-danger @enderror" 
                                placeholder="Unit number or identifier" 
                                value="{{ old('unit') }}" required />
                            @error('unit')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="vendor_mechanic">Vendor/Mechanic *</x-base.form-label>
                            <x-base.form-input id="vendor_mechanic" name="vendor_mechanic" type="text"
                                class="w-full @error('vendor_mechanic') border-danger @enderror" 
                                placeholder="e.g., ABC Auto Shop" 
                                value="{{ old('vendor_mechanic') }}" required />
                            @error('vendor_mechanic')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="service_date">Service Date *</x-base.form-label>
                            <x-base.litepicker id="service_date" name="service_date" 
                                value="{{ old('service_date', now()->format('m/d/Y')) }}"
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
                            <x-base.form-label for="cost">Cost *</x-base.form-label>
                            <x-base.form-input id="cost" name="cost" type="number"
                                class="w-full @error('cost') border-danger @enderror" 
                                placeholder="e.g., 500.00" 
                                step="0.01" min="0" 
                                value="{{ old('cost') }}" required />
                            @error('cost')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="odometer">Odometer Reading *</x-base.form-label>
                            <x-base.form-input id="odometer" name="odometer" type="number"
                                class="w-full @error('odometer') border-danger @enderror" 
                                placeholder="e.g., 50000" 
                                min="0" 
                                value="{{ old('odometer') }}" required />
                            @error('odometer')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <x-base.form-label for="description">Description/Notes</x-base.form-label>
                        <x-base.form-textarea id="description" name="description" 
                            class="w-full @error('description') border-danger @enderror"
                            rows="4" 
                            maxlength="1000"
                            placeholder="Additional notes or details about the maintenance">{{ old('description') }}</x-base.form-textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- File upload section using Livewire -->
                    <div class="mt-8 pt-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <h3 class="text-lg font-medium mb-5">Attachments</h3>
                        <p class="text-sm text-slate-500 mb-4">Upload invoices, receipts, or inspection reports</p>

                        <!-- Hidden field to store file information -->
                        <input type="hidden" name="livewire_files" id="livewire_files" value="[]">

                        <!-- Livewire file uploader component -->
                        <livewire:components.file-uploader 
                            model-name="maintenance_files" 
                            :model-index="0"
                            :auto-upload="true"
                            class="border-2 border-dashed border-gray-300 rounded-lg p-6 cursor-pointer" />
                    </div>

                    <div class="mt-5 pt-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <div class="flex items-center mb-3">
                            <input id="status" type="checkbox" name="status" value="1"
                                {{ old('status') ? 'checked' : '' }} 
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded">
                            <label for="status" class="ml-2 form-label mb-0">Mark as Completed</label>
                        </div>
                        @error('status')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror

                        <div class="flex items-center">
                            <input id="is_historical" type="checkbox" name="is_historical" value="1"
                                {{ old('is_historical') ? 'checked' : '' }} 
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded">
                            <label for="is_historical" class="ml-2 form-label mb-0">Historical Service (Past Maintenance)</label>
                        </div>
                        <p class="text-xs text-slate-500 ml-6 mt-1">Check this if entering past maintenance records with flexible dates</p>
                        @error('is_historical')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex justify-end mt-5">
                        <x-base.button as="a" href="{{ route('carrier.maintenance.index') }}"
                            variant="outline-secondary" class="mr-2">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
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
                const vehicleSelect = document.getElementById('vehicle_id');
                const unitInput = document.getElementById('unit');
                const filesInput = document.getElementById('livewire_files');

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

                // Listen for changes on service_date
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

                // Vehicle data for auto-filling unit field
                const vehiclesData = @json(
                    $vehicles->map(function ($vehicle) {
                        return [
                            'id' => $vehicle->id,
                            'unit' => $vehicle->company_unit_number ?? '',
                        ];
                    }));

                // Auto-fill unit field when vehicle is selected
                if (vehicleSelect && unitInput) {
                    vehicleSelect.addEventListener('change', function() {
                        const selectedVehicleId = parseInt(this.value);
                        if (!selectedVehicleId) {
                            unitInput.value = '';
                            return;
                        }

                        // Always update unit field when vehicle changes
                        const selectedVehicle = vehiclesData.find(v => v.id === selectedVehicleId);
                        if (selectedVehicle) {
                            unitInput.value = selectedVehicle.unit || '';
                        }
                    });
                }

                // Handle Livewire file upload events
                if (filesInput) {
                    let uploadedFiles = [];

                    // Listen for file uploaded event
                    window.addEventListener('fileUploaded', (event) => {
                        const fileData = event.detail;
                        uploadedFiles.push(fileData);
                        filesInput.value = JSON.stringify(uploadedFiles);
                        console.log('File uploaded:', fileData);
                    });

                    // Listen for file removed event
                    window.addEventListener('fileRemoved', (event) => {
                        const fileId = event.detail;
                        uploadedFiles = uploadedFiles.filter(file => {
                            return file.id !== fileId && file.path !== fileId;
                        });
                        filesInput.value = JSON.stringify(uploadedFiles);
                        console.log('File removed:', fileId);
                    });
                }
            });
        </script>
    @endpush
@endsection
