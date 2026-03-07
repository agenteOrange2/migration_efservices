@extends('../themes/' . $activeTheme)
@section('title', 'Edit Accident Record')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Driver Accidents Management', 'url' => route('admin.accidents.index')],
        ['label' => 'Edit Accident Record', 'active' => true],
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
                        <x-base.lucide class="w-8 h-8 text-primary" icon="Edit" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Edit Accident Record</h1>
                        <p class="text-slate-600">Edit accident record: {{ $accident->accident_date }}</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('admin.accidents.documents.show', $accident->id) }}"
                        variant="outline-primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="file-text" />
                        View Documents
                    </x-base.button>

                    <x-base.button as="a" href="{{ route('admin.accidents.index') }}" class="w-full sm:w-auto"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                        Back to Accidents
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Formulario de Edición -->
        <div class="box box--stacked mt-5 p-3">
            <div class="box-body">
                <form action="{{ route('admin.accidents.update', $accident->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Carrier Selection -->
                        <div>
                            <x-base.form-label for="carrier_id">Carrier</x-base.form-label>
                            <select id="carrier_id"
                                class="tom-select w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8"
                                disabled>
                                <option value="{{ $accident->userDriverDetail->carrier_id }}">
                                    {{ $accident->userDriverDetail->carrier->name }}
                                </option>
                            </select>
                            <input type="hidden" name="carrier_id" value="{{ $accident->userDriverDetail->carrier_id }}">
                        </div>

                        <!-- Driver Selection -->
                        <div>
                            <x-base.form-label for="user_driver_detail_id">Driver</x-base.form-label>
                            <select id="user_driver_detail_id" name="user_driver_detail_id"
                                class="tom-select w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8"
                                required>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}"
                                        {{ $driver->id == $accident->user_driver_detail_id ? 'selected' : '' }}>
                                        {{ implode(' ', array_filter([$driver->user->name, $driver->middle_name, $driver->last_name])) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_driver_detail_id')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Registration Date (Read-only) -->
                        <div>
                            <x-base.form-label>Registration Date</x-base.form-label>
                            <x-base.form-input type="text" class="w-full"
                                value="{{ $accident->created_at->format('m-d-Y') }}" readonly />
                        </div>

                        <!-- Accident Date -->
                        <div>
                            <x-base.form-label for="accident_date">Accident Date</x-base.form-label>
                            <x-base.litepicker id="accident_date" name="accident_date" class="w-full"
                                value="{{ $accident->accident_date }}" required />
                            @error('accident_date')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>


                        <!-- Nature of Accident -->
                        <div>
                            <x-base.form-label for="nature_of_accident">Nature of Accident</x-base.form-label>
                            <x-base.form-input id="nature_of_accident" name="nature_of_accident" type="text"
                                class="w-full" value="{{ $accident->nature_of_accident }}" required />
                            @error('nature_of_accident')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <!-- Had Injuries -->
                        <div>
                            <div class="flex items-center">
                                <input type="checkbox" id="had_injuries" name="had_injuries"
                                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2" value="1"
                                    {{ $accident->had_injuries ? 'checked' : '' }}>
                                <label for="had_injuries" class="ml-2 form-label">Had Injuries?</label>
                            </div>

                            <div id="injuries_container" class="mt-3 {{ $accident->had_injuries ? '' : 'hidden' }}">
                                <label for="number_of_injuries" class="form-label">Number of Injuries</label>
                                <x-base.form-input id="number_of_injuries" name="number_of_injuries" type="number"
                                    class="w-full" min="0" value="{{ $accident->number_of_injuries }}" />
                                @error('number_of_injuries')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Had Fatalities -->
                        <div>
                            <div class="flex items-center">
                                <input type="checkbox" id="had_fatalities" name="had_fatalities"
                                    class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2" value="1"
                                    {{ $accident->had_fatalities ? 'checked' : '' }}>
                                <label for="had_fatalities" class="ml-2 form-label">Had Fatalities?</label>
                            </div>

                            <div id="fatalities_container" class="mt-3 {{ $accident->had_fatalities ? '' : 'hidden' }}">
                                <label for="number_of_fatalities" class="form-label">Number of Fatalities</label>
                                <x-base.form-input id="number_of_fatalities" name="number_of_fatalities" type="number"
                                    class="w-full" min="0" value="{{ $accident->number_of_fatalities }}" />
                                @error('number_of_fatalities')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Comments -->
                    <div class="mt-6">
                        <x-base.form-label for="comments">Comments</x-base.form-label>
                        <x-base.form-textarea id="comments" name="comments" class="w-full"
                            rows="4">{{ $accident->comments }}</x-base.form-textarea>

                        @error('comments')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Documentos con FileUploader de Livewire -->
                    <div class="mt-8 border-t pt-6" id="documents">
                        <h3 class="text-lg font-medium mb-4">Documents</h3>

                        <div class="mt-4">
                            @php
                                $existingFilesArray = [];
                                foreach ($documents as $document) {
                                    // Verificar que document sea un objeto con las propiedades necesarias
                                    if (is_object($document)) {
                                        try {
                                            $existingFilesArray[] = [
                                                'id' => $document->id,
                                                'name' => $document->file_name ?? 'Unknown',
                                                'file_name' => $document->file_name ?? 'Unknown',
                                                'mime_type' => $document->mime_type ?? 'application/octet-stream',
                                                'size' => $document->size ?? 0,
                                                'created_at' => $document->created_at
                                                    ? $document->created_at->format('Y-m-d H:i:s')
                                                    : now()->format('Y-m-d H:i:s'),
                                                'url' => method_exists($document, 'getUrl')
                                                    ? $document->getUrl()
                                                    : route('admin.accidents.document.preview', $document->id),
                                                'is_temp' => false,
                                            ];
                                        } catch (\Exception $e) {
                                            // Si hay error al acceder a alguna propiedad, lo ignoramos
                                            \Illuminate\Support\Facades\Log::error(
                                                'Error al procesar documento para vista',
                                                [
                                                    'document_id' => $document->id ?? 'unknown',
                                                    'error' => $e->getMessage(),
                                                ],
                                            );
                                        }
                                    }
                                }
                            @endphp

                            <livewire:components.file-uploader model-name="accident_files" :model-index="0"
                                :label="'Upload Documents'" :existing-files="$existingFilesArray" />
                            <!-- Campo oculto para almacenar los archivos subidos -->
                            <input type="hidden" name="accident_files" id="accident_files_input">
                        </div>
                    </div>

                    <!-- Archivos de Media Library -->
                    <div class="mt-8 border-t pt-6" id="media_files">
                        <h3 class="text-lg font-medium mb-4">Images and Media Files</h3>

                        @if ($mediaFiles && count($mediaFiles) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @foreach ($mediaFiles as $media)
                                    <div class="border rounded-md p-2 relative group">
                                        @php
                                            $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
                                            $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']);
                                        @endphp

                                        @if ($isImage)
                                            <img src="{{ $media->getUrl() }}" alt="{{ $media->file_name }}"
                                                class="w-full h-auto rounded">
                                        @else
                                            <div class="flex items-center justify-center bg-gray-100 rounded h-32">
                                                <x-base.lucide class="w-12 h-12 text-gray-500" icon="file" />
                                                <span class="ml-2">{{ $extension }}</span>
                                            </div>
                                        @endif

                                        <div class="mt-2">
                                            <p class="text-sm truncate">{{ $media->file_name }}</p>
                                            <p class="text-xs text-gray-500">{{ number_format($media->size / 1024, 2) }}
                                                KB</p>
                                        </div>

                                        <div
                                            class="absolute flex top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="{{ $media->getUrl() }}" target="_blank"
                                                class="bg-primary text-white p-1 rounded mr-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="lucide lucide-eye-icon lucide-eye w-4 h-4">
                                                    <path
                                                        d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </a>
                                            <button type="button"
                                                onclick="deleteMedia({{ $media->id }}, '{{ $media->file_name }}')"
                                                class="bg-red-500 text-white p-1 rounded">
                                                <x-base.lucide class="w-4 h-4" icon="trash" />
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-gray-500">No media files found.</div>
                        @endif
                    </div>

                    <!-- Ya no necesitamos este componente porque lo agregamos arriba -->
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end mt-5">
                <x-base.button as="a" href="{{ route('admin.accidents.index') }}" variant="outline-secondary"
                    class="mr-2">
                    Cancel
                </x-base.button>
                <x-base.button type="submit" variant="primary">
                    Update Accident Record
                </x-base.button>
            </div>
            </form>


        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Inicializar el array para almacenar los archivos
            let uploadedFiles = [];
            const accidentFilesInput = document.getElementById('accident_files_input');

            // Manejar cambio de carrier para filtrar conductores (aunque esté deshabilitado, por consistencia)
            document.getElementById('carrier_id').addEventListener('change', function() {
                const carrierId = this.value;

                // Limpiar el select de conductores usando JavaScript nativo
                const driverSelect = document.getElementById('user_driver_detail_id');
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

            // Escuchar eventos del componente Livewire
            window.addEventListener('livewire:initialized', () => {
                // Escuchar el evento fileUploaded del componente Livewire
                Livewire.on('fileUploaded', (eventData) => {
                    console.log('Archivo subido:', eventData);
                    // Extraer los datos del evento
                    const data = eventData[0]; // Los datos vienen como primer elemento del array

                    if (data.modelName === 'accident_files') {
                        // Añadir el archivo al array de archivos
                        uploadedFiles.push({
                            path: data.tempPath,
                            original_name: data.originalName,
                            mime_type: data.mimeType,
                            size: data.size
                        });

                        // Actualizar el campo oculto con el nuevo array
                        accidentFilesInput.value = JSON.stringify(uploadedFiles);
                        console.log('Archivos actualizados:', accidentFilesInput.value);
                    }
                });

                // Escuchar el evento fileRemoved del componente Livewire
                Livewire.on('fileRemoved', (eventData) => {
                    console.log('Archivo eliminado:', eventData);
                    // Extraer los datos del evento
                    const data = eventData[0]; // Los datos vienen como primer elemento del array

                    if (data.modelName === 'accident_files') {
                        const fileId = data.fileId;

                        // Si es un archivo permanente (no temporal), eliminarlo de la base de datos
                        if (!data.isTemp) {
                            // Llamar al endpoint para eliminar el documento
                            fetch('{{ route('admin.accidents.documents.ajax-destroy') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({
                                        document_id: fileId
                                    })
                                })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.success) {
                                        console.log(
                                            'Documento eliminado con éxito de la base de datos'
                                        );
                                    } else {
                                        console.error('Error al eliminar documento:', result
                                            .message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error en la solicitud AJAX:', error);
                                });
                        }

                        // Eliminar el archivo del array de archivos temporales
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
                        accidentFilesInput.value = JSON.stringify(uploadedFiles);
                        console.log('Archivos actualizados después de eliminar:', accidentFilesInput
                            .value);
                    }
                });
            });

            // Mostrar/ocultar campos de lesiones y fatalidades
            const hadInjuriesCheckbox = document.getElementById('had_injuries');
            const injuriesContainer = document.getElementById('injuries_container');
            const hadFatalitiesCheckbox = document.getElementById('had_fatalities');
            const fatalitiesContainer = document.getElementById('fatalities_container');

            hadInjuriesCheckbox.addEventListener('change', function() {
                injuriesContainer.classList.toggle('hidden', !this.checked);
                if (!this.checked) {
                    document.getElementById('number_of_injuries').value = '';
                }
            });

            hadFatalitiesCheckbox.addEventListener('change', function() {
                fatalitiesContainer.classList.toggle('hidden', !this.checked);
                if (!this.checked) {
                    document.getElementById('number_of_fatalities').value = '';
                }
            });

            // Función para eliminar archivos de Media Library
            window.deleteMedia = function(mediaId, fileName) {
                if (confirm('¿Estás seguro de que quieres eliminar el archivo ' + fileName + '?')) {
                    fetch('{{ route('admin.accidents.ajax-destroy-media', '') }}/' + mediaId, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                // Mostrar mensaje de éxito
                                alert('Archivo eliminado correctamente');
                                // Recargar la página para reflejar los cambios
                                location.reload();
                            } else {
                                alert('Error al eliminar archivo: ' + (result.message ||
                                    'Error desconocido'));
                            }
                        })
                        .catch(error => {
                            console.error('Error en la solicitud AJAX:', error);
                            alert('Error al eliminar archivo: ' + error.message);
                        });
                }
            };
        });
    </script>
@endpush

@pushOnce('scripts')
    @vite('resources/js/app.js') {{-- Este debe ir primero --}}
    @vite('resources/js/pages/notification.js')
    @vite('resources/js/components/base/tom-select.js')
@endPushOnce
