@extends('../themes/' . $activeTheme)
@section('title', 'Create New Drug Test')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Testing Drugs Management', 'url' => route('admin.driver-testings.index')],
        ['label' => 'Create New Test', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div>
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
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="PlusCircle" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Create New Drug Test</h1>
                        <p class="text-slate-600">Create a new drug test for a driver</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('admin.driver-testings.index') }}"
                        class="w-full sm:w-auto" variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="PlusCircle" />
                        Back to List
                    </x-base.button>
                </div>
            </div>
        </div>
        <!-- Formulario y Selección de Carrier/Driver -->
        <div class="grid grid-cols-12 gap-6 mt-5">
            <!-- Panel de Selección de Carrier y Driver -->
            <div class="col-span-12 xl:col-span-4">
                <div class="box box--stacked p-5">
                    <div class="flex items-center border-b border-slate-200/60 pb-5 mb-5">
                        <div class="font-medium truncate text-base mr-5">Select Carrier & Driver</div>
                    </div>

                    <!-- Selección de Carrier -->
                    <div class="mb-5">
                        <label for="carrier_id" class="form-label">Carrier <span class="text-danger">*</span></label>
                        <x-base.tom-select id="carrier_id" name="carrier_id"
                            class="w-full @error('carrier_id') border-danger @enderror" data-placeholder="-- Select a carrier --" required>
                            <option value="">-- Select a carrier --</option>
                            @foreach ($carriers as $carrier)
                                <option value="{{ $carrier->id }}" {{ old('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                    {{ $carrier->name }} (USDOT: {{ $carrier->usdot ?: 'N/A' }})
                                </option>
                            @endforeach
                        </x-base.tom-select>
                        @error('carrier_id')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Selección de Driver -->
                    <div class="mb-5">
                        <label for="user_driver_detail_id" class="form-label">Driver <span
                                class="text-danger">*</span></label>
                        <x-base.tom-select id="user_driver_detail_id" name="user_driver_detail_id"
                            class="w-full @error('user_driver_detail_id') border-danger @enderror" data-placeholder="-- Select a driver --" disabled required>
                            <option value="">-- Select a driver --</option>
                        </x-base.tom-select>
                        @error('user_driver_detail_id')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                        <!-- Loading indicator is now handled by the JavaScript class -->
                        <div id="driver-loading" class="mt-2 hidden">
                            <div class="flex items-center">
                                <div class="w-4 h-4 animate-spin mr-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
                                    </svg>
                                </div>
                                <span class="text-xs text-slate-500">Loading drivers...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Details Card -->
                    <div id="driver-detail-card" class="card border shadow-sm mt-4 hidden p-3">
                        <div class="card-header">
                            <h3 class="font-medium text-base">Driver Details</h3>
                        </div>
                        <div class="card-body ">
                            <div class="mb-2">
                                <span class="text-gray-500">Full Name</span><br>
                                <span id="driver-name" class="font-medium">-</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">Email</span><br>
                                <span id="driver-email" class="font-medium">-</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">Phone</span><br>
                                <span id="driver-phone" class="font-medium">-</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">License Information</span><br>
                                <span id="driver-license" class="font-medium">-</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">License Class</span><br>
                                <span id="driver-license-class" class="font-medium">-</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">License Expiration</span><br>
                                <span id="driver-license-expiration" class="font-medium">-</span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel del Formulario Principal -->
            <div class="col-span-12 xl:col-span-8">
                <div class="box box--stacked p-3">
                    <div class="box-header box-header--transparent">
                        <div class="box-title mb-5">Drug & Alcohol Test Details</div>
                    </div>
                    <div class="box-body">
                        <form action="{{ route('admin.driver-testings.store') }}" method="POST" id="create-test-form"
                            enctype="multipart/form-data">
                            @csrf
                            
                            <!-- Display validation errors with enhanced styling -->
                            @if ($errors->any())
                                <div class="alert alert-danger flex items-start mb-5 p-4 border border-danger rounded-md bg-danger/10">
                                    <x-base.lucide class="w-6 h-6 mr-3 text-danger flex-shrink-0 mt-0.5" icon="alert-circle" />
                                    <div class="flex-1">
                                        <div class="font-medium text-danger mb-2">Please correct the following errors:</div>
                                        <ul class="list-disc list-inside text-sm text-danger space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Campos ocultos para datos seleccionados -->
                            <!-- No usar old() aquí - el JavaScript sincronizará los valores desde los selects -->
                            <input type="hidden" name="carrier_id" id="carrier_id_hidden" value="">
                            <input type="hidden" name="user_driver_detail_id" id="user_driver_detail_id_hidden" value="">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Tipo de Prueba -->
                                <div>
                                    <label for="test_type" class="form-label">Test Type</label>
                                    <select name="test_type" id="test_type"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8"
                                        required>
                                        <option value="">-- Select test type --</option>
                                        @foreach ($testTypes as $key => $testType)
                                            <option value="{{ $key }}" {{ old('test_type') == $key ? 'selected' : '' }}>
                                                {{ $testType }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('test_type')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Administered By -->
                                <div x-data="{ showOtherField: '{{ old('administered_by') }}' === 'other' }">
                                    <label for="administered_by_select" class="form-label">Administered By <span
                                            class="text-danger">*</span></label>
                                    <select id="administered_by_select"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8"
                                        required x-on:change="showOtherField = $event.target.value === 'other'">
                                        <option value="">-- Select administrator --</option>
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getAdministrators() as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ old('administered_by') == $value && $value != 'other' ? 'selected' : '' }}>
                                                {{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <!-- Campo oculto que almacenará el valor real a enviar -->
                                    <input type="hidden" id="administered_by" name="administered_by"
                                        value="{{ old('administered_by') }}">
                                    <div id="administered_by_other_container" class="mt-2" x-show="showOtherField">
                                        <input type="text" id="administered_by_other"
                                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8"
                                            placeholder="Please specify"
                                            value="{{ old('administered_by_other', old('administered_by') != 'other' ? old('administered_by') : '') }}"
                                            x-bind:required="showOtherField">
                                    </div>
                                    @error('administered_by')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const selectElement = document.getElementById('administered_by_select');
                                        const hiddenInput = document.getElementById('administered_by');
                                        const otherInput = document.getElementById('administered_by_other');
                                        const formElement = document.getElementById('create-test-form');

                                        // Inicializar el select con el valor correcto
                                        if (hiddenInput.value && hiddenInput.value !== 'other') {
                                            // Verificar si el valor está en las opciones predefinidas
                                            let found = false;
                                            for (let i = 0; i < selectElement.options.length; i++) {
                                                if (selectElement.options[i].value === hiddenInput.value) {
                                                    selectElement.selectedIndex = i;
                                                    found = true;
                                                    break;
                                                }
                                            }

                                            // Si no está en las opciones, es un valor personalizado
                                            if (!found) {
                                                selectElement.value = 'other';
                                                otherInput.value = hiddenInput.value;
                                            }
                                        }

                                        // Manejar cambios en el select
                                        selectElement.addEventListener('change', function() {
                                            if (this.value === 'other') {
                                                // Si se selecciona 'other', el valor real será lo que se escriba en el campo de texto
                                                hiddenInput.value = otherInput.value || 'other';
                                            } else {
                                                // Si se selecciona otra opción, usar ese valor
                                                hiddenInput.value = this.value;
                                            }
                                        });

                                        // Manejar cambios en el campo de texto 'other'
                                        otherInput.addEventListener('input', function() {
                                            if (selectElement.value === 'other') {
                                                hiddenInput.value = this.value || 'other';
                                            }
                                        });

                                        // Validar el formulario antes de enviar
                                        formElement.addEventListener('submit', function(e) {
                                            // Verificar que se haya seleccionado un carrier
                                            const carrierId = document.getElementById('carrier_id').value;
                                            if (!carrierId) {
                                                e.preventDefault();
                                                alert('Please select a carrier');
                                                return false;
                                            }

                                            // Verificar que se haya seleccionado un driver
                                            const driverId = document.getElementById('user_driver_detail_id').value;
                                            if (!driverId) {
                                                e.preventDefault();
                                                alert('Please select a driver');
                                                return false;
                                            }

                                            // Si se seleccionó 'other', verificar que se haya ingresado un valor en el campo de texto
                                            if (selectElement.value === 'other') {
                                                const otherValue = otherInput.value.trim();
                                                if (!otherValue) {
                                                    e.preventDefault();
                                                    alert('Please specify who administered the test');
                                                    return false;
                                                }
                                                // Asegurarse de que el valor del campo oculto sea el texto ingresado
                                                hiddenInput.value = otherValue;
                                            }
                                        });
                                    });
                                </script>

                                <!-- Fecha del Test -->
                                <div>
                                    <label for="test_date" class="form-label">Test Date <span
                                            class="text-danger">*</span></label>
                                    <x-base.litepicker id="test_date" name="test_date" class="w-full"
                                        value="{{ old('test_date', date('m/d/Y')) }}" required />

                                    @error('test_date')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Ubicación -->
                                <div>
                                    <label for="location" class="form-label">Location <span
                                            class="text-danger">*</span></label>
                                    <select id="location" name="location"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('location') is-invalid @enderror"
                                        required>
                                        <option value="">-- Select location --</option>
                                        @foreach ($locations as $location)
                                            <option value="{{ $location }}"
                                                {{ old('location') == $location ? 'selected' : '' }}>
                                                {{ $location }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('location')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Requester Name -->
                                <div>
                                    <label for="requester_name" class="form-label">Requester Name</label>
                                    <input type="text" id="requester_name" name="requester_name"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('requester_name') is-invalid @enderror"
                                        value="{{ old('requester_name') }}">
                                    @error('requester_name')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- MRO -->
                                <div>
                                    <label for="mro" class="form-label">MRO</label>
                                    <input type="text" id="mro" name="mro"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('mro') is-invalid @enderror"
                                        value="{{ old('mro') }}">
                                    @error('mro')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!-- Scheduled Time -->
                                <div>
                                    <label for="scheduled_time" class="form-label">Scheduled Time</label>
                                    <input type="datetime-local" id="scheduled_time" name="scheduled_time"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('scheduled_time') is-invalid @enderror"
                                        value="{{ old('scheduled_time') }}">
                                    @error('scheduled_time')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Solicita la prueba -->
                                <div>
                                    <label for="requester_name" class="form-label">Test Requested By</label>
                                    <input type="text" id="requester_name" name="requester_name"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('requester_name') is-invalid @enderror"
                                        value="{{ old('requester_name') }}"
                                        placeholder="Name of person requesting the test">
                                    @error('requester_name')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Test Result -->
                                <div>
                                    <label for="test_result" class="form-label">Test Result</label>
                                    <select id="test_result" name="test_result"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('test_result') is-invalid @enderror">
                                        <option value="">-- Select result --</option>
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getTestResults() as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('test_result') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('test_result')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="status" class="form-label">Status</label>
                                    <select id="status" name="status"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('status') is-invalid @enderror">
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getStatuses() as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('status', 'active') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Next Test Due -->
                                <div>
                                    <label for="next_test_due" class="form-label">Next Test Due Date</label>
                                    <x-base.litepicker id="next_test_due" name="next_test_due" class="w-full"
                                        value="{{ old('next_test_due') }}" />
                                    @error('next_test_due')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Bill To -->
                                <div>
                                    <label for="bill_to" class="form-label">Bill To <span
                                            class="text-danger">*</span></label>
                                    <select id="bill_to" name="bill_to"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('bill_to') is-invalid @enderror"
                                        required>
                                        <option value="">-- Select billing option --</option>
                                        @foreach ($billOptions as $option)
                                            <option value="{{ $option }}"
                                                {{ old('bill_to') == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bill_to')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Tipo de Test Checkboxes -->
                            <div class="mt-6">
                                <div class="font-medium mb-2">Test Details <span class="text-danger">*</span></div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_random_test" name="is_random_test" value="1"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            {{ old('is_random_test') ? 'checked' : '' }}>
                                        <label for="is_random_test" class="cursor-pointer">Random Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_post_accident_test" name="is_post_accident_test"
                                            value="1"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            {{ old('is_post_accident_test') ? 'checked' : '' }}>
                                        <label for="is_post_accident_test" class="cursor-pointer">Post-Accident
                                            Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_reasonable_suspicion_test"
                                            name="is_reasonable_suspicion_test" value="1"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            {{ old('is_reasonable_suspicion_test') ? 'checked' : '' }}>
                                        <label for="is_reasonable_suspicion_test" class="cursor-pointer">Reasonable
                                            Suspicion Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_pre_employment_test" name="is_pre_employment_test"
                                            value="1"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            {{ old('is_pre_employment_test') ? 'checked' : '' }}>
                                        <label for="is_pre_employment_test" class="cursor-pointer">Pre-Employment
                                            Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_follow_up_test" name="is_follow_up_test"
                                            value="1"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            {{ old('is_follow_up_test') ? 'checked' : '' }}>
                                        <label for="is_follow_up_test" class="cursor-pointer">Follow-Up Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_return_to_duty_test" name="is_return_to_duty_test"
                                            value="1"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            {{ old('is_return_to_duty_test') ? 'checked' : '' }}>
                                        <label for="is_return_to_duty_test" class="cursor-pointer">Return-To-Duty
                                            Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_other_reason_test" name="is_other_reason_test"
                                            value="1"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            {{ old('is_other_reason_test') ? 'checked' : '' }}>
                                        <label for="is_other_reason_test" class="cursor-pointer">Other Reason</label>
                                    </div>
                                </div>
                                @error('test_details')
                                    <div class="text-danger mt-2 text-sm">{{ $message }}</div>
                                @enderror
                                <!-- Campo de descripción para Other Reason -->
                                <div id="other_reason_container" class="w-full" style="display: none;">
                                    <input type="text" id="other_reason_description" name="other_reason_description"
                                        class="w-full mt-3 text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('other_reason_description') is-invalid border-danger @enderror"
                                        placeholder="Specify other reason" value="{{ old('other_reason_description') }}">
                                    @error('other_reason_description')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Notas -->
                            <div class="mt-6">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" rows="3"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Document Upload with Livewire component -->
                            <div class="mt-6">
                                <div class="font-medium mb-2">Upload Files</div>
                                <livewire:components.file-uploader model-name="driver_testing_files" :model-index="0"
                                    :auto-upload="true"
                                    class="border-2 border-dashed border-gray-300 rounded-lg p-6 cursor-pointer" />
                                <!-- Campo oculto para almacenar los archivos subidos -->
                                <input type="hidden" name="driver_testing_files" id="driver_testing_files_input">
                                @error('driver_testing_files')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-6 flex justify-end gap-2">
                                <a href="{{ route('admin.driver-testings.index') }}">
                                    <x-base.button type="button" variant="outline-secondary" class="flex items-center">
                                        <x-base.lucide icon="x" class="w-4 h-4 mr-2" />
                                        Cancel
                                    </x-base.button>
                                </a>
                                <x-base.button type="submit" variant="primary" class="flex items-center" id="submit-button">
                                    <x-base.lucide icon="save" class="w-4 h-4 mr-2" />                                    
                                    Create Drug Test
                                </x-base.button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/driver-testing-form.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the driver testing form with enhanced error handling
            const driverTestingForm = new DriverTestingForm({
                isEditMode: false,
                currentDriverId: '{{ old('user_driver_detail_id') }}'
            });

            console.log('Driver Testing Form initialized for create mode');
        });
    </script>
@endpush
