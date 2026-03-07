@extends('../themes/' . $activeTheme)
@section('title', 'Edit Inspection Record')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver Inspections Management', 'url' => route('carrier.drivers.inspections.index')],
        ['label' => 'Edit Inspection Record', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div>
        <!-- Mensajes Flash -->
        @if (session()->has('success'))
            <div class="alert alert-success flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
                {{ session('error') }}
            </div>
        @endif

        <!-- Cabecera -->
        <div class="flex flex-col sm:flex-row items-center mt-8">
            <h2 class="text-lg font-medium mr-auto">
                Edit Inspection Record
            </h2>
            <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
                <x-base.button as="a" href="{{ route('carrier.drivers.inspections.index') }}" class="w-full sm:w-auto"
                    variant="outline-primary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back to Inspections
                </x-base.button>
            </div>
        </div>

        <!-- Formulario de Edición -->
        <div class="box box--stacked mt-5">
            <div class="box-header">
                <h3 class="box-title">Inspection Details</h3>
            </div>
            
            <div class="box-body p-5">
                <form action="{{ route('carrier.drivers.inspections.update', $inspection) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Driver Selection -->
                        <div>
                            <x-base.form-label for="user_driver_detail_id">Driver <span class="text-danger">*</span></x-base.form-label>
                            <select id="user_driver_detail_id" name="user_driver_detail_id"
                                class="tom-select w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">Select Driver</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ old('user_driver_detail_id', $inspection->user_driver_detail_id) == $driver->id ? 'selected' : '' }}>
                                        {{ implode(' ', array_filter([$driver->user->name, $driver->middle_name, $driver->last_name])) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_driver_detail_id')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Vehicle Selection (Optional) -->
                        <div>
                            <x-base.form-label for="vehicle_id">Vehicle (Optional)</x-base.form-label>
                            <select id="vehicle_id" name="vehicle_id"
                                class="tom-select w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">Select Vehicle</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $inspection->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->company_unit_number ?? 'N/A' }} - {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <x-base.form-label for="inspection_date">Inspection Date <span class="text-danger">*</span></x-base.form-label>
                            <x-base.litepicker id="inspection_date" name="inspection_date" 
                                value="{{ old('inspection_date', $inspection->inspection_date ? $inspection->inspection_date->format('Y-m-d') : '') }}" class="block w-full @error('inspection_date') border-danger @enderror" />
                            @error('inspection_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="inspection_type">Inspection Type <span class="text-danger">*</span></x-base.form-label>
                            <select id="inspection_type" name="inspection_type"
                                class="form-select block w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">Select Type</option>
                                <option value="DOT Roadside" {{ old('inspection_type', $inspection->inspection_type) == 'DOT Roadside' ? 'selected' : '' }}>DOT Roadside</option>
                                <option value="State Police" {{ old('inspection_type', $inspection->inspection_type) == 'State Police' ? 'selected' : '' }}>State Police</option>
                                <option value="Annual DOT" {{ old('inspection_type', $inspection->inspection_type) == 'Annual DOT' ? 'selected' : '' }}>Annual DOT</option>
                                <option value="Pre-trip" {{ old('inspection_type', $inspection->inspection_type) == 'Pre-trip' ? 'selected' : '' }}>Pre-trip</option>
                                <option value="Post-trip" {{ old('inspection_type', $inspection->inspection_type) == 'Post-trip' ? 'selected' : '' }}>Post-trip</option>
                                <option value="Border Crossing" {{ old('inspection_type', $inspection->inspection_type) == 'Border Crossing' ? 'selected' : '' }}>Border Crossing</option>
                                <option value="Weigh Station" {{ old('inspection_type', $inspection->inspection_type) == 'Weigh Station' ? 'selected' : '' }}>Weigh Station</option>
                            </select>
                            @error('inspection_type')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <x-base.form-label for="inspection_level">Inspection Level</x-base.form-label>
                            <select id="inspection_level" name="inspection_level"
                                class="form-select block w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">Select Level</option>
                                <option value="Level I" {{ old('inspection_level', $inspection->inspection_level) == 'Level I' ? 'selected' : '' }}>Level I - Full Inspection</option>
                                <option value="Level II" {{ old('inspection_level', $inspection->inspection_level) == 'Level II' ? 'selected' : '' }}>Level II - Walk-Around</option>
                                <option value="Level III" {{ old('inspection_level', $inspection->inspection_level) == 'Level III' ? 'selected' : '' }}>Level III - Driver Only</option>
                                <option value="Level IV" {{ old('inspection_level', $inspection->inspection_level) == 'Level IV' ? 'selected' : '' }}>Level IV - Special Inspection</option>
                                <option value="Level V" {{ old('inspection_level', $inspection->inspection_level) == 'Level V' ? 'selected' : '' }}>Level V - Vehicle Only</option>
                                <option value="Level VI" {{ old('inspection_level', $inspection->inspection_level) == 'Level VI' ? 'selected' : '' }}>Level VI - Hazmat Inspection</option>
                            </select>
                            @error('inspection_level')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="status">Status <span class="text-danger">*</span></x-base.form-label>
                            <select id="status" name="status"
                                class="form-select block w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">Select Status</option>
                                <option value="Pass" {{ old('status', $inspection->status) == 'Pass' ? 'selected' : '' }}>Pass</option>
                                <option value="Fail" {{ old('status', $inspection->status) == 'Fail' ? 'selected' : '' }}>Fail</option>
                                <option value="Conditional Pass" {{ old('status', $inspection->status) == 'Conditional Pass' ? 'selected' : '' }}>Conditional Pass</option>
                                <option value="Out of Service" {{ old('status', $inspection->status) == 'Out of Service' ? 'selected' : '' }}>Out of Service</option>
                                <option value="Pending" {{ old('status', $inspection->status) == 'Pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                            @error('status')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <x-base.form-label for="inspector_name">Inspector Name <span class="text-danger">*</span></x-base.form-label>
                            <x-base.form-input id="inspector_name" name="inspector_name" type="text" 
                                value="{{ old('inspector_name', $inspection->inspector_name) }}" class="block w-full" />
                            @error('inspector_name')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="inspector_number">Inspector Number/Badge</x-base.form-label>
                            <x-base.form-input id="inspector_number" name="inspector_number" type="text" 
                                value="{{ old('inspector_number', $inspection->inspector_number) }}" class="block w-full" />
                            @error('inspector_number')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="location">Inspection Location</x-base.form-label>
                            <x-base.form-input id="location" name="location" type="text" 
                                value="{{ old('location', $inspection->location) }}" class="block w-full" />
                            @error('location')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <x-base.form-label for="is_vehicle_safe_to_operate">Vehicle Safe to Operate?</x-base.form-label>
                            <div class="mt-2">
                                <label class="form-check mr-2 inline-block">
                                    <input id="vehicle_safe_yes" name="is_vehicle_safe_to_operate" type="radio" class="form-check-input" value="1" {{ old('is_vehicle_safe_to_operate', $inspection->is_vehicle_safe_to_operate) == '1' ? 'checked' : '' }}>
                                    <span class="form-check-label">Yes</span>
                                </label>
                                <label class="form-check mr-2 inline-block">
                                    <input id="vehicle_safe_no" name="is_vehicle_safe_to_operate" type="radio" class="form-check-input" value="0" {{ old('is_vehicle_safe_to_operate', $inspection->is_vehicle_safe_to_operate) == '0' ? 'checked' : '' }}>
                                    <span class="form-check-label">No</span>
                                </label>
                            </div>
                            @error('is_vehicle_safe_to_operate')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Comments/Notes -->
                    <div class="mt-4">
                        <x-base.form-label for="notes">Comments/Notes</x-base.form-label>
                        <x-base.form-textarea id="notes" name="notes" 
                            rows="4" class="block w-full">{{ old('notes', $inspection->notes) }}</x-base.form-textarea>
                        @error('notes')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Sección de Documentos -->
                    <div class="mt-4">
                        <div class="mb-5">
                            <h3 class="text-lg font-medium">Inspection Documents</h3>
                            
                            @livewire('components.file-uploader', [
                                'modelName' => 'inspection_files',
                                'modelIndex' => 0,
                                'label' => 'Upload Inspection Documents',
                                'existingFiles' => $documents
                            ])
                        </div>
                        <input type="hidden" name="inspection_files" id="inspection_files_input">
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="flex justify-end mt-5">
                        <x-base.button as="a" href="{{ route('carrier.drivers.inspections.index') }}" variant="outline-secondary" class="mr-2">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
                            Update Inspection Record
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables para almacenar los valores antiguos (si existen por errores de validación)
            const oldDriverId = '{{ old("user_driver_detail_id", $inspection->user_driver_detail_id) }}';
            const oldVehicleId = '{{ old("vehicle_id", $inspection->vehicle_id) }}';
            
            // Manejar cambio de conductor para filtrar vehículos
            document.getElementById('user_driver_detail_id').addEventListener('change', function() {
                const driverId = this.value;
                
                // Limpiar el select de vehículos usando JavaScript nativo
                const vehicleSelect = document.getElementById('vehicle_id');
                vehicleSelect.innerHTML = '<option value="">Select Vehicle (Optional)</option>';
                
                if (driverId) {
                    // Hacer una petición AJAX para obtener los vehículos relacionados con este conductor
                    fetch(`{{ url('/carrier/carrier-driver-inspections/driver') }}/${driverId}/vehicles`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.length > 0) {
                                // Hay vehículos, agregarlos al select
                                data.forEach(function(vehicle) {
                                    const option = document.createElement('option');
                                    option.value = vehicle.id;
                                    option.textContent = `${vehicle.company_unit_number || 'N/A'} - ${vehicle.year} ${vehicle.make} ${vehicle.model}`;
                                    
                                    // Si este vehículo es el que estaba seleccionado previamente, seleccionarlo
                                    if (vehicle.id.toString() === oldVehicleId) {
                                        option.selected = true;
                                    }
                                    
                                    vehicleSelect.appendChild(option);
                                });
                            } else {
                                // No hay vehículos para este conductor
                                const option = document.createElement('option');
                                option.value = '';
                                option.disabled = true;
                                option.textContent = 'No vehicles found for this driver';
                                vehicleSelect.appendChild(option);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading vehicles:', error);
                            const option = document.createElement('option');
                            option.value = '';
                            option.disabled = true;
                            option.textContent = 'Error loading vehicles';
                            vehicleSelect.appendChild(option);
                        });
                }
            });
            
            // Inicializar conductor si hay valor antiguo (para errores de validación)
            if (oldDriverId) {
                const driverSelect = document.getElementById('user_driver_detail_id');
                driverSelect.value = oldDriverId;
                driverSelect.dispatchEvent(new Event('change'));
            }
            
            // Almacenar archivos subidos del componente Livewire
            const inspectionFilesInput = document.getElementById('inspection_files_input');
            let inspectionFiles = [];
            
            // Inicializar con los archivos existentes desde MediaLibrary
            @if(isset($documents) && count($documents) > 0)
                inspectionFiles = @json($documents);
                inspectionFilesInput.value = JSON.stringify(inspectionFiles);
                console.log('Archivos existentes cargados:', inspectionFiles.length);
            @endif
            
            // Escuchar eventos emitidos por el componente Livewire
            document.addEventListener('livewire:initialized', () => {
                // Este evento se dispara cuando se sube un nuevo archivo
                Livewire.on('fileUploaded', (data) => {
                    const fileData = data[0];
                    
                    if (fileData.modelName === 'inspection_files') {
                        // Agregar el archivo al array con la estructura correcta para MediaLibrary
                        inspectionFiles.push({
                            name: fileData.originalName,
                            original_name: fileData.originalName,
                            mime_type: fileData.mimeType,
                            size: fileData.size,
                            is_temp: true,
                            tempPath: fileData.tempPath,
                            path: fileData.tempPath,
                            // URL formateada para vista previa
                            url: '/storage/' + fileData.tempPath,
                            id: fileData.previewData.id
                        });
                        
                        // Actualizar el input hidden con los datos JSON
                        inspectionFilesInput.value = JSON.stringify(inspectionFiles);
                        console.log('Archivo agregado:', fileData.originalName);
                        console.log('Total archivos:', inspectionFiles.length);
                    }
                });
                
                // Este evento se dispara cuando se elimina un archivo
                Livewire.on('fileRemoved', (eventData) => {
                    console.log('Archivo eliminado:', eventData);
                    // Extraer los datos del evento
                    const data = eventData[0]; // Los datos vienen como primer elemento del array
                    
                    if (data.modelName === 'inspection_files') {
                        const fileId = data.fileId;
                        
                        // Si es un archivo permanente (no temporal), eliminarlo de la base de datos
                        if (!data.isTemp) {
                            // Llamar al endpoint para eliminar el documento
                            fetch('{{ route("carrier.drivers.inspections.document.delete.ajax") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    document_id: fileId
                                })
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    console.log('Documento eliminado con éxito de la base de datos');
                                } else {
                                    console.error('Error al eliminar documento:', result.error);
                                }
                            })
                            .catch(error => {
                                console.error('Error en la solicitud AJAX:', error);
                            });
                        }
                        
                        // Eliminar el archivo del array de archivos
                        inspectionFiles = inspectionFiles.filter(file => {
                            // Para archivos temporales, verificar por la ruta y el id
                            if (data.isTemp && file.is_temp) {
                                return file.id !== fileId;
                            }
                            // Para archivos permanentes, verificar por id
                            return file.id !== fileId;
                        });
                        
                        // Actualizar el input hidden con los datos JSON
                        inspectionFilesInput.value = JSON.stringify(inspectionFiles);
                        console.log('Total archivos restantes:', inspectionFiles.length);
                    }
                });
            });
        });
    </script>
@endpush

@pushOnce('scripts')
    @vite('resources/js/app.js')
    @vite('resources/js/pages/notification.js')
    @vite('resources/js/components/base/tom-select.js')
@endPushOnce
