@extends('../themes/' . $activeTheme)
@section('title', 'Add Course Record')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Driver Courses Management', 'url' => route('admin.courses.index')],
        ['label' => 'Add Course Record', 'active' => true],
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

        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="PlusCircle" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Add New Course Record</h1>
                        <p class="text-slate-600">Add a new course record for a driver</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('admin.courses.index') }}" class="w-full sm:w-auto"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="PlusCircle" />
                        Back to Courses
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Formulario de Creación con Livewire -->
        <div class="box box--stacked mt-5">
            <div class="box-body p-5">
                <form action="{{ route('admin.courses.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-base.form-label for="carrier_id">Carrier</x-base.form-label>
                            <select id="carrier_id" name="carrier_id"
                                class="tom-select w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">Select Carrier</option>
                                @foreach ($carriers as $carrier)
                                    <option value="{{ $carrier->id }}"
                                        {{ old('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                        {{ $carrier->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('carrier_id')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Driver Selection -->
                        <div>
                            <x-base.form-label for="user_driver_detail_id">Driver</x-base.form-label>
                            <select id="user_driver_detail_id" name="user_driver_detail_id"
                                class="tom-select w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">Select Driver</option>
                                @if (isset($drivers))
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}"
                                            {{ old('user_driver_detail_id') == $driver->id ? 'selected' : '' }}>
                                            {{ trim(($driver->user->name ?? '') . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? '')) }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('user_driver_detail_id')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div x-data="{ showOtherField: false }">
                            <x-base.form-label for="organization_name">Organization Name</x-base.form-label>
                            <select id="organization_name_select" name="organization_name"
                                class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8"
                                x-on:change="showOtherField = ($event.target.value === 'Other')">
                                <option value="">Select Organization</option>
                                <option value="H2S" {{ old('organization_name') == 'H2S' ? 'selected' : '' }}>H2S
                                </option>
                                <option value="PEC" {{ old('organization_name') == 'PEC' ? 'selected' : '' }}>PEC
                                </option>
                                <option value="SANDTRAX" {{ old('organization_name') == 'SANDTRAX' ? 'selected' : '' }}>
                                    SANDTRAX</option>
                                <option value="OSHA10" {{ old('organization_name') == 'OSHA10' ? 'selected' : '' }}>OSHA10
                                </option>
                                <option value="OSHA30" {{ old('organization_name') == 'OSHA30' ? 'selected' : '' }}>OSHA30
                                </option>
                                <option value="Other"
                                    {{ old('organization_name') != 'H2S' && old('organization_name') != 'PEC' && old('organization_name') != 'SANDTRAX' && old('organization_name') != 'OSHA10' && old('organization_name') != 'OSHA30' && old('organization_name') ? 'selected' : '' }}>
                                    Other</option>
                            </select>

                            <!-- Campo para "Other" que se muestra condicionalmente -->
                            <div x-show="showOtherField" class="mt-2">
                                <x-base.form-input id="organization_name_other" name="organization_name_other"
                                    type="text" value="{{ old('organization_name_other') }}" class="block w-full"
                                    placeholder="Specify organization name" />
                            </div>

                            @error('organization_name')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <x-base.form-label for="city">City</x-base.form-label>
                            <x-base.form-input id="city" name="city" type="text" value="{{ old('city') }}"
                                class="block w-full" />
                            @error('city')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="state">State</x-base.form-label>
                            <select id="state" name="state"
                                class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">Select State</option>
                                @foreach (\App\Helpers\Constants::usStates() as $code => $name)
                                    <option value="{{ $code }}" {{ old('state') == $code ? 'selected' : '' }}>
                                        {{ $name }}</option>
                                @endforeach
                            </select>
                            @error('state')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="experience">Experience</x-base.form-label>
                            <x-base.form-input id="experience" name="experience" type="text"
                                value="{{ old('experience') }}" class="block w-full" />
                            @error('experience')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <x-base.form-label for="certification_date">Certification Date</x-base.form-label>
                            <x-base.litepicker id="certification_date" name="certification_date"
                                value="{{ old('certification_date') }}" data-format="MM-DD-YYYY" class="block w-full"
                                placeholder="MM-DD-YYYY" />
                            @error('certification_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="expiration_date">Expiration Date</x-base.form-label>
                            <x-base.litepicker id="expiration_date" name="expiration_date"
                                value="{{ old('expiration_date') }}" data-format="MM-DD-YYYY" class="block w-full"
                                placeholder="MM-DD-YYYY" />
                            @error('expiration_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-base.form-label for="status">Status</x-base.form-label>
                            <select id="status" name="status"
                                class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                            @error('status')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-base.form-label>Course Certificate</x-base.form-label>
                        <div class="border border-dashed rounded-md p-4 mt-2">
                            <livewire:components.file-uploader model-name="course_certificate" :model-index="0"
                                :auto-upload="true"
                                class="border-2 border-dashed border-gray-300 rounded-lg p-6 cursor-pointer" />
                            <!-- Campo oculto para almacenar los archivos subidos - valor inicial vacío pero no null -->
                            <input type="hidden" name="certificate_files" id="certificate_files_input" value="">
                        </div>
                        @error('certificate_files')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-base.button as="a" href="{{ route('admin.courses.index') }}" class="mr-2"
                            variant="outline-secondary">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
                            Save Course
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const carrierSelect = document.getElementById('carrier_id');
                const driverSelect = document.getElementById('user_driver_detail_id');
                const oldCarrierId = '{{ old('carrier_id') }}';
                const oldDriverId = '{{ old('user_driver_detail_id') }}';

                // Cargar drivers cuando se selecciona un carrier
                carrierSelect.addEventListener('change', function() {
                    const carrierId = this.value;

                    // Limpiar el select de conductores
                    driverSelect.innerHTML = '<option value="">Select Driver</option>';

                    if (carrierId) {
                        // Hacer una petición AJAX para obtener los conductores activos de esta transportista
                        fetch(`/api/active-drivers-by-carrier/${carrierId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data && data.length > 0) {
                                    // Hay conductores activos, agregarlos al select
                                    data.forEach(function(driver) {
                                        const option = document.createElement('option');
                                        option.value = driver.id;
                                        option.textContent = driver.full_name;

                                        // Seleccionar driver si coincide con el valor antiguo
                                        if (oldDriverId && oldDriverId == driver.id) {
                                            option.selected = true;
                                        }

                                        driverSelect.appendChild(option);
                                    });
                                } else {
                                    // No hay conductores activos para este carrier
                                    const option = document.createElement('option');
                                    option.value = '';
                                    option.disabled = true;
                                    option.textContent = 'No active drivers found for this carrier';
                                    driverSelect.appendChild(option);
                                }

                                // Disparar un evento change para que se actualice la UI
                                driverSelect.dispatchEvent(new Event('change'));
                            })
                            .catch(error => {
                                console.error('Error loading drivers:', error);
                                const option = document.createElement('option');
                                option.value = '';
                                option.disabled = true;
                                option.textContent = 'Error loading drivers';
                                driverSelect.appendChild(option);
                                driverSelect.dispatchEvent(new Event('change'));
                            });
                    }
                });

                // Inicializar selectores si hay valores antiguos (para errores de validación)
                if (oldCarrierId) {
                    // Seleccionar carrier
                    carrierSelect.value = oldCarrierId;

                    // Disparar manualmente el evento change para cargar los drivers
                    carrierSelect.dispatchEvent(new Event('change'));
                }

                // Inicializar el array para almacenar los archivos
                let uploadedFiles = [];
                // IMPORTANTE: Asegurarnos que el campo oculto esté accesible en toda la función
                const certificateFilesInput = document.getElementById('certificate_files_input');
                console.log('Campo oculto encontrado:', certificateFilesInput ? 'Sí' : 'No');

                // Escuchar eventos del componente Livewire
                window.addEventListener('livewire:initialized', () => {
                    console.log('Livewire inicializado, preparando escucha de eventos');

                    // Escuchar el evento fileUploaded del componente Livewire
                    Livewire.on('fileUploaded', (eventData) => {
                        console.log('Archivo subido evento recibido:', eventData);
                        // Extraer los datos del evento
                        const data = eventData[0]; // Los datos vienen como primer elemento del array

                        if (data.modelName === 'course_certificate') {
                            console.log('Archivo subido para course_certificate');
                            // Añadir el archivo al array de archivos
                            uploadedFiles.push({
                                name: data.originalName,
                                original_name: data.originalName,
                                mime_type: data.mimeType,
                                size: data.size,
                                path: data.tempPath,
                                tempPath: data.tempPath,
                                is_temp: true
                            });

                            // Asegurarnos que el campo oculto sigue existiendo
                            const hiddenInput = document.getElementById('certificate_files_input');
                            if (hiddenInput) {
                                hiddenInput.value = JSON.stringify(uploadedFiles);
                                console.log('Campo actualizado con:', hiddenInput.value);
                            } else {
                                console.error('Campo oculto no encontrado en el DOM');
                            }
                        }
                    });

                    // Escuchar el evento fileRemoved del componente Livewire
                    Livewire.on('fileRemoved', (eventData) => {
                        console.log('Archivo eliminado:', eventData);
                        // Extraer los datos del evento
                        const data = eventData[0]; // Los datos vienen como primer elemento del array

                        if (data.modelName === 'course_certificate') {
                            // Eliminar el archivo del array
                            const fileId = data.fileId;
                            uploadedFiles = uploadedFiles.filter((file, index) => {
                                // Para archivos temporales, el ID contiene un timestamp
                                if (fileId.startsWith('temp_') && index === uploadedFiles
                                    .length - 1) {
                                    // Eliminar el último archivo añadido si es temporal
                                    return false;
                                }
                                return true;
                            });

                            // Actualizar el campo oculto con el nuevo array
                            certificateFilesInput.value = JSON.stringify(uploadedFiles);
                            console.log('Archivos actualizados después de eliminar:',
                                certificateFilesInput.value);
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
@endsection
