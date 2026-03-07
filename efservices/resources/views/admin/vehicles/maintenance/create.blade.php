@extends('../themes/' . $activeTheme)
@section('title', 'New Maintenance Record')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
        ['label' => 'Maintenance', 'url' => route('admin.maintenance.index')],
        ['label' => 'New Maintenance Record', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Professional Header -->
            <div class="box box--stacked p-8 mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-8 h-8 text-primary" icon="PlusCircle" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">New Maintenance Record</h1>
                            <p class="text-slate-600">Add a new maintenance record</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        <x-base.button as="a" href="{{ route('admin.maintenance.index') }}"
                            variant="outline-primary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                            Back to Maintenance Records
                        </x-base.button>
                    </div>
                </div>
            </div>

            <div class="intro-y box p-5 mt-5">
                <div class="flex items-center border-b border-slate-200/60 dark:border-darkmode-400 pb-5 mb-5">
                    <div class="font-medium text-base truncate">Maintenance Information</div>
                </div>

                <form action="{{ route('admin.maintenance.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mt-3">
                        <x-base.form-label for="vehicle_id">Vehicle</x-base.form-label>
                        <x-base.tom-select id="vehicle_id" name="vehicle_id"
                            class="w-full @error('vehicle_id') border-danger @enderror" data-placeholder="Select Vehicle">
                            <option value="">Select Vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">
                                    {{ $vehicle->make }} {{ $vehicle->model }}
                                    ({{ $vehicle->company_unit_number ?? $vehicle->vin }})
                                </option>
                            @endforeach
                        </x-base.tom-select>
                        @error('vehicle_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <x-base.form-label for="service_tasks">Maintenance Type</x-base.form-label>
                        <x-base.tom-select id="service_tasks" name="service_tasks"
                            class="w-full @error('service_tasks') border-danger @enderror" data-placeholder="Select Maintenance Type">
                            <option value="">Select Maintenance Type</option>
                            @foreach ($maintenanceTypes as $type)
                                <option value="{{ $type }}" {{ old('service_tasks') == $type ? 'selected' : '' }}>
                                    {{ $type }}</option>
                            @endforeach
                        </x-base.tom-select>
                        @error('service_tasks')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="service_date">Service Date</x-base.form-label>
                            <x-base.litepicker id="service_date" name="service_date"
                                value="{{ old('service_date', request('date')) }}"
                                class="@error('service_date') border-danger @enderror" placeholder="MM/DD/YYYY" 
                                data-single-mode="true" data-format="MM/DD/YYYY" required />
                            @error('service_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <x-base.form-label for="next_service_date">Next Service Date</x-base.form-label>
                            <x-base.litepicker id="next_service_date" name="next_service_date"
                                value="{{ old('next_service_date') }}"
                                class="@error('next_service_date') border-danger @enderror" placeholder="MM/DD/YYYY"
                                data-single-mode="true" data-format="MM/DD/YYYY" required />
                            @error('next_service_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="unit">Unit</x-base.form-label>
                            <x-base.form-input id="unit" name="unit" type="text" class="w-full"
                                placeholder="Número de unidad o identificador" value="{{ old('unit') }}" required />
                            @error('unit')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="vendor_mechanic">Vendor/Mechanic</x-base.form-label>
                            <x-base.form-input id="vendor_mechanic" name="vendor_mechanic" type="text" class="w-full"
                                placeholder="Ej: Taller Automotriz XYZ" value="{{ old('vendor_mechanic') }}" required />
                            @error('vendor_mechanic')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="cost">Cost *</x-base.form-label>
                            <x-base.form-input id="cost" name="cost" type="number" class="w-full"
                                placeholder="Ex: 5000" step="0.01" min="0" value="{{ old('cost') }}"
                                required />
                            @error('cost')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="odometer">Odometer Reading *</x-base.form-label>
                            <x-base.form-input id="odometer" name="odometer" type="number" class="w-full"
                                placeholder="Ej: 50000" min="0" value="{{ old('odometer') }}" required />
                            @error('odometer')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <div class="mt-6">
                        <x-base.form-label for="description">Description/Notes</x-base.form-label>
                        <x-base.form-textarea id="description" name="description" class="w-full" rows="4"
                            maxlength="1000">{{ old('description') }}</x-base.form-textarea>
                        @error('description')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sección de documentos adjuntos usando Livewire file-uploader -->
                    <div class="mt-8 pt-5 border-t border-slate-200/60 dark:border-darkmode-400">
                        <h3 class="text-lg font-medium mb-5">Attachments</h3>

                        <!-- Campo oculto para almacenar la información de los archivos -->
                        <input type="hidden" name="livewire_files" id="livewire_files" value="[]">

                        <!-- Componente Livewire para carga de archivos -->
                        <livewire:components.file-uploader model-name="maintenance_files" :model-index="0"
                            :auto-upload="true"
                            class="border-2 border-dashed border-gray-300 rounded-lg p-6 cursor-pointer" />
                    </div>

                    <div class="mt-3">
                        <div class="flex items-center">
                            <input id="status" type="checkbox" name="status" value="1"
                                {{ old('status') ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2">
                            <label for="status" class="ml-2 form-label">Mark as Completed</label>
                        </div>
                        @error('status')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <div class="flex items-center">
                            <input id="is_historical" type="checkbox" name="is_historical" value="1"
                                {{ old('is_historical') ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2">
                            <label for="is_historical" class="ml-2 form-label">Historical Service (Past
                                Maintenance)</label>
                        </div>
                        <p class="text-xs text-slate-500 ml-6 mt-1">Check this if entering past maintenance records with
                            flexible dates</p>
                        @error('is_historical')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex justify-end mt-5">
                        <x-base.button as="a" href="{{ route('admin.maintenance.index') }}"
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
            document.addEventListener('DOMContentLoaded', function() {
                const vehicleSelect = document.getElementById('vehicle_id');
                const unitInput = document.getElementById('unit');

                const uploadedFilesInput = document.getElementById('livewire_files');

                // Auto-calculate next_service_date = service_date + 3 months
                const serviceDateInput = document.getElementById('service_date');
                const nextServiceDateInput = document.getElementById('next_service_date');

                function addThreeMonths(dateStr) {
                    // Parse MM/DD/YYYY
                    const parts = dateStr.split('/');
                    if (parts.length !== 3) return null;
                    const month = parseInt(parts[0]) - 1;
                    const day = parseInt(parts[1]);
                    const year = parseInt(parts[2]);
                    const date = new Date(year, month, day, 12, 0, 0);
                    date.setMonth(date.getMonth() + 3);
                    // Format back to MM/DD/YYYY
                    const mm = String(date.getMonth() + 1).padStart(2, '0');
                    const dd = String(date.getDate()).padStart(2, '0');
                    const yyyy = date.getFullYear();
                    return mm + '/' + dd + '/' + yyyy;
                }

                function setNextServiceDate(dateStr) {
                    const nextDate = addThreeMonths(dateStr);
                    if (!nextDate) return;
                    // Set via Litepicker API if available, otherwise set value directly
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
                    // Only auto-set if next_service_date is empty
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
                    const todayStr = mm + '/' + dd + '/' + yyyy;
                    setNextServiceDate(todayStr);
                }

                // Script para obtener la fecha de la URL con manejo de zona horaria
                const urlParams = new URLSearchParams(window.location.search);
                const dateParam = urlParams.get('date');

                if (dateParam) {
                    // Crear la fecha a partir del parámetro, pero asegurando que sea la misma fecha
                    // independientemente de la zona horaria
                    const dateParts = dateParam.split('-');
                    if (dateParts.length === 3) {
                        const year = parseInt(dateParts[0]);
                        const month = parseInt(dateParts[1]) - 1; // JS months are 0-indexed
                        const day = parseInt(dateParts[2]);

                        // Crear un objeto de fecha con hora 12:00 para evitar problemas de zona horaria
                        const dateObj = new Date(year, month, day, 12, 0, 0);

                        // Esperar a que Litepicker se inicialice
                        setTimeout(function() {
                            if (serviceDateInput && serviceDateInput._litepicker) {
                                serviceDateInput._litepicker.setDate(dateObj);
                                // Also auto-set next_service_date from this URL date
                                const mm = String(dateObj.getMonth() + 1).padStart(2, '0');
                                const dd = String(dateObj.getDate()).padStart(2, '0');
                                const yyyy = dateObj.getFullYear();
                                setNextServiceDate(mm + '/' + dd + '/' + yyyy);
                            }
                        }, 500);
                    }
                }

                // Datos de vehículos para autocompletar el campo de unidad
                const vehiclesData = @json(
                    $vehicles->map(function ($vehicle) {
                        return [
                            'id' => $vehicle->id,
                            'unit' => $vehicle->company_unit_number ?? '',
                        ];
                    }));

                // Función para actualizar el campo de unidad cuando se selecciona un vehículo
                vehicleSelect.addEventListener('change', function() {
                    const selectedVehicleId = parseInt(this.value);
                    if (!selectedVehicleId) {
                        unitInput.value = '';
                        return;
                    }

                    // Siempre actualizar el campo de unidad cuando cambia el vehículo
                    const selectedVehicle = vehiclesData.find(v => v.id === selectedVehicleId);
                    if (selectedVehicle) {
                        unitInput.value = selectedVehicle.unit || '';
                    }
                });

                // Manejar eventos de archivos subidos por Livewire
                let uploadedFiles = [];

                // Escuchar evento cuando un archivo es subido
                window.addEventListener('fileUploaded', (event) => {
                    const fileData = event.detail;
                    uploadedFiles.push(fileData);
                    uploadedFilesInput.value = JSON.stringify(uploadedFiles);
                });

                // Escuchar evento cuando un archivo es eliminado
                window.addEventListener('fileRemoved', (event) => {
                    const fileId = event.detail;
                    uploadedFiles = uploadedFiles.filter(file => {
                        return file.id !== fileId && file.path !== fileId;
                    });
                    uploadedFilesInput.value = JSON.stringify(uploadedFiles);
                });
            });
        </script>
    @endpush
@endsection
