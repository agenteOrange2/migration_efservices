@extends('../themes/' . $activeTheme)
@section('title', 'Create New Drug Test')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Testing Drugs Management', 'url' => route('carrier.drivers.testings.index')],
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

        <!-- Cabecera -->
        <div class="flex flex-col sm:flex-row items-center mt-8">
            <h2 class="text-lg font-medium mr-auto">
                Create New Drug Test
            </h2>
            <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
                <a href="{{ route('carrier.drivers.testings.index') }}">
                    <x-base.button variant="outline-secondary" class="flex items-center">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                        Back to List
                    </x-base.button>
                </a>
            </div>
        </div>

        <!-- Formulario y Selección de Driver -->
        <div class="grid grid-cols-12 gap-6 mt-5">
            <!-- Panel de Selección de Driver -->
            <div class="col-span-12 xl:col-span-4">
                <div class="box box--stacked p-5">
                    <div class="flex items-center border-b border-slate-200/60 pb-5 mb-5">
                        <div class="font-medium truncate text-base mr-5">Select Driver</div>
                    </div>

                    <!-- Selección de Driver -->
                    <div class="mb-5">
                        <label for="user_driver_detail_id" class="form-label">Driver <span
                                class="text-danger">*</span></label>
                        <select id="user_driver_detail_id" name="user_driver_detail_id"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('user_driver_detail_id') is-invalid border-danger @enderror" required>
                            <option value="">-- Select a driver --</option>
                            @foreach ($drivers as $driver)
                                @php
                                    $firstLicense = $driver->licenses->first();
                                    $name = $driver->user->name ?? '';
                                    $middleName = $driver->middle_name ?? '';
                                    $lastName = $driver->last_name ?? '';
                                    $fullName = trim($name . ' ' . $middleName . ' ' . $lastName);
                                    $email = $driver->user->email ?? 'N/A';
                                    $phone = $driver->phone ?? 'N/A';
                                @endphp
                                <option value="{{ $driver->id }}" 
                                    data-name="{{ $fullName }}"
                                    data-email="{{ $email }}"
                                    data-phone="{{ $phone }}"
                                    data-license="{{ $firstLicense->license_number ?? 'N/A' }}"
                                    data-license-class="{{ $firstLicense->license_class ?? 'N/A' }}"
                                    data-license-expiration="{{ $firstLicense && $firstLicense->expiration_date ? \App\Helpers\FormatHelper::formatDate($firstLicense->expiration_date) : 'N/A' }}"
                                    {{ old('user_driver_detail_id') == $driver->id ? 'selected' : '' }}>
                                    {{ $fullName ?: 'Driver #' . $driver->id }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_driver_detail_id')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
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
                        <form action="{{ route('carrier.drivers.testings.store') }}" method="POST" id="create-test-form"
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
                            
                            <!-- Campo oculto para driver seleccionado -->
                            <input type="hidden" name="user_driver_detail_id" id="user_driver_detail_id_hidden" value="{{ old('user_driver_detail_id') }}">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Tipo de Prueba -->
                                <div>
                                    <label for="test_type" class="form-label">Test Type <span class="text-danger">*</span></label>
                                    <select name="test_type" id="test_type"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('test_type') is-invalid border-danger @enderror"
                                        required>
                                        <option value="">-- Select test type --</option>
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getTestTypes() as $key => $testType)
                                            <option value="{{ $key }}" {{ old('test_type') == $key ? 'selected' : '' }}>
                                                {{ $testType }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('test_type')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Administered By -->
                                <div x-data="{ showOtherField: '{{ old('administered_by') }}' === 'other' }">
                                    <label for="administered_by_select" class="form-label">Administered By <span
                                            class="text-danger">*</span></label>
                                    <select id="administered_by_select"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('administered_by') is-invalid border-danger @enderror"
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
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('location') is-invalid border-danger @enderror"
                                        required>
                                        <option value="">-- Select location --</option>
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getLocations() as $key => $location)
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
                                    <label for="requester_name" class="form-label">Requester Name <span class="text-danger">*</span></label>
                                    <input type="text" id="requester_name" name="requester_name"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('requester_name') is-invalid border-danger @enderror"
                                        value="{{ old('requester_name') }}" required>
                                    @error('requester_name')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- MRO -->
                                <div>
                                    <label for="mro" class="form-label">MRO <span class="text-danger">*</span></label>
                                    <input type="text" id="mro" name="mro"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('mro') is-invalid border-danger @enderror"
                                        value="{{ old('mro') }}" required>
                                    @error('mro')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Scheduled Time -->
                                <div>
                                    <label for="scheduled_time" class="form-label">Scheduled Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" id="scheduled_time" name="scheduled_time"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('scheduled_time') is-invalid border-danger @enderror"
                                        value="{{ old('scheduled_time') }}" required>
                                    @error('scheduled_time')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Test Result -->
                                <div>
                                    <label for="test_result" class="form-label">Test Result</label>
                                    <select id="test_result" name="test_result"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('test_result') is-invalid border-danger @enderror">
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
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('status') is-invalid border-danger @enderror">
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getStatuses() as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('status', 'Schedule') == $key ? 'selected' : '' }}>
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
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('bill_to') is-invalid border-danger @enderror"
                                        required>
                                        <option value="">-- Select billing option --</option>
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getBillOptions() as $key => $option)
                                            <option value="{{ $key }}"
                                                {{ old('bill_to') == $key ? 'selected' : '' }}>
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
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('notes') is-invalid border-danger @enderror">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- File Upload -->
                            <div class="mt-6">
                                <label for="attachments" class="form-label">Upload Attachments</label>
                                <input type="file" name="attachments[]" id="attachments" multiple
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <div class="text-xs text-slate-500 mt-1">
                                    Allowed file types: PDF, JPG, PNG, DOC, DOCX (Max 10MB per file)
                                </div>
                                @error('attachments')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                                @error('attachments.*')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-6 flex justify-end gap-2">
                                <a href="{{ route('carrier.drivers.testings.index') }}">
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
    <script src="{{ asset('js/carrier-driver-testing-form.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the carrier driver testing form
            const form = new CarrierDriverTestingForm({
                isEditMode: false
            });
            
            // Administered By handler
            const selectElement = document.getElementById('administered_by_select');
            const hiddenInput = document.getElementById('administered_by');
            const otherInput = document.getElementById('administered_by_other');

            // Initialize the select with the correct value
            if (hiddenInput.value && hiddenInput.value !== 'other') {
                let found = false;
                for (let i = 0; i < selectElement.options.length; i++) {
                    if (selectElement.options[i].value === hiddenInput.value) {
                        selectElement.selectedIndex = i;
                        found = true;
                        break;
                    }
                }

                if (!found) {
                    selectElement.value = 'other';
                    otherInput.value = hiddenInput.value;
                }
            }

            selectElement.addEventListener('change', function() {
                if (this.value === 'other') {
                    hiddenInput.value = otherInput.value || 'other';
                } else {
                    hiddenInput.value = this.value;
                }
            });

            otherInput.addEventListener('input', function() {
                if (selectElement.value === 'other') {
                    hiddenInput.value = this.value || 'other';
                }
            });
        });
    </script>
@endpush
