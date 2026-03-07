@extends('../themes/' . $activeTheme)
@section('title', 'Edit Drug Test')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Testing Drugs Management', 'url' => route('carrier.drivers.testings.index')],
        ['label' => 'Edit Test', 'active' => true],
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
                Edit Drug Test #{{ $testing->id }}
            </h2>
            <div class="w-full sm:w-auto flex mt-4 sm:mt-0 gap-2">
                <a href="{{ route('carrier.drivers.testings.show', $testing->id) }}">
                    <x-base.button variant="outline-primary" class="flex items-center">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="eye" />
                        View Details
                    </x-base.button>
                </a>
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
                                <option value="{{ $driver->id }}"
                                    {{ old('user_driver_detail_id', $testing->user_driver_detail_id) == $driver->id ? 'selected' : '' }}
                                    data-driver-name="{{ $driver->user->name ?? 'N/A' }}"
                                    data-driver-email="{{ $driver->user->email ?? 'N/A' }}"
                                    data-driver-phone="{{ $driver->user->phone ?? 'N/A' }}"
                                    data-driver-license="{{ $driver->licenses->first()?->license_number ?? 'N/A' }}"
                                    data-driver-license-class="{{ $driver->licenses->first()?->license_class ?? 'N/A' }}"
                                    data-driver-license-expiration="{{ $driver->licenses->first()?->expiration_date?->format('m/d/Y') ?? 'N/A' }}">
                                    {{ $driver->user->name ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_driver_detail_id')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Driver Details Card -->
                    <div id="driver-detail-card" class="card border shadow-sm mt-4 p-3">
                        <div class="card-header">
                            <h3 class="font-medium text-base">Driver Details</h3>
                        </div>
                        <div class="card-body ">
                            <div class="mb-2">
                                <span class="text-gray-500">Full Name</span><br>
                                <span id="driver-name" class="font-medium">{{ $testing->userDriverDetail->user->name ?? 'N/A' }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">Email</span><br>
                                <span id="driver-email" class="font-medium">{{ $testing->userDriverDetail->user->email ?? 'N/A' }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">Phone</span><br>
                                <span id="driver-phone" class="font-medium">{{ $testing->userDriverDetail->user->phone ?? 'N/A' }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">License Information</span><br>
                                <span id="driver-license" class="font-medium">{{ $testing->userDriverDetail->licenses->first()?->license_number ?? 'N/A' }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">License Class</span><br>
                                <span id="driver-license-class" class="font-medium">{{ $testing->userDriverDetail->licenses->first()?->license_class ?? 'N/A' }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-gray-500">License Expiration</span><br>
                                <span id="driver-license-expiration" class="font-medium">{{ $testing->userDriverDetail->licenses->first()?->expiration_date?->format('m/d/Y') ?? 'N/A' }}</span>
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
                        <form action="{{ route('carrier.drivers.testings.update', $testing->id) }}" method="POST"
                            id="edit-test-form" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            
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
                            
                            <!-- Campo oculto para datos seleccionados -->
                            <input type="hidden" name="user_driver_detail_id" id="user_driver_detail_id_hidden"
                                value="{{ old('user_driver_detail_id', $testing->user_driver_detail_id) }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <!-- Tipo de Test -->
                                <div>
                                    <label for="test_type" class="form-label">Test Type <span
                                            class="text-danger">*</span></label>
                                    <select id="test_type" name="test_type"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('test_type') is-invalid @enderror"
                                        required>
                                        <option value="">Select Test Type</option>
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getTestTypes() as $key => $type)
                                            <option value="{{ $key }}"
                                                {{ old('test_type', $testing->test_type) == $key ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('test_type')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Administrado por -->
                                <div x-data="{ showOtherField: {{ old('administered_by', $testing->administered_by) == 'other' || !in_array($testing->administered_by, array_merge([''], array_keys(\App\Models\Admin\Driver\DriverTesting::getAdministrators()))) ? 'true' : 'false' }} }">
                                    <label for="administered_by_select" class="form-label">Administered By <span
                                            class="text-danger">*</span></label>
                                    @php
                                        $administrators = \App\Models\Admin\Driver\DriverTesting::getAdministrators();
                                        $adminKeys = array_keys($administrators);
                                        $currentValue = old('administered_by', $testing->administered_by);
                                        $isCustomValue = !in_array($currentValue, array_merge([''], $adminKeys));
                                    @endphp
                                    <select id="administered_by_select"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8"
                                        required x-on:change="showOtherField = $event.target.value === 'other'">
                                        <option value="">-- Select administrator --</option>
                                        @foreach ($administrators as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ ($currentValue == $value && !$isCustomValue) || ($value == 'other' && $isCustomValue) ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <!-- Campo oculto que almacenará el valor real a enviar -->
                                    <input type="hidden" id="administered_by" name="administered_by"
                                        value="{{ $currentValue }}">
                                    <div id="administered_by_other_container" class="mt-2" x-show="showOtherField">
                                        <input type="text" id="administered_by_other"
                                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8"
                                            placeholder="Please specify"
                                            value="{{ $isCustomValue ? $currentValue : old('administered_by_other') }}"
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

                                        // Manejar cambios en el select
                                        selectElement.addEventListener('change', function() {
                                            if (this.value === 'other') {
                                                hiddenInput.value = otherInput.value || 'other';
                                            } else {
                                                hiddenInput.value = this.value;
                                            }
                                        });

                                        // Manejar cambios en el campo de texto 'other'
                                        otherInput.addEventListener('input', function() {
                                            if (selectElement.value === 'other') {
                                                hiddenInput.value = this.value || 'other';
                                            }
                                        });
                                    });
                                </script>

                                <!-- Fecha del Test -->
                                <div>
                                    <label for="test_date" class="form-label">Test Date <span
                                            class="text-danger">*</span></label>
                                    <x-base.litepicker id="test_date" name="test_date" class="w-full"
                                        value="{{ old('test_date', $testing->test_date->format('m/d/Y')) }}" />
                                    @error('test_date')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Ubicación del Test -->
                                <div>
                                    <label for="location" class="form-label">Location <span
                                            class="text-danger">*</span></label>
                                    <select id="location" name="location"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('location') is-invalid @enderror"
                                        required>
                                        <option value="">Select Location</option>
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getLocations() as $location)
                                            <option value="{{ $location }}"
                                                {{ old('location', $testing->location) == $location ? 'selected' : '' }}>
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
                                        value="{{ old('requester_name', $testing->requester_name) }}">
                                    @error('requester_name')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- MRO -->
                                <div>
                                    <label for="mro" class="form-label">MRO</label>
                                    <input type="text" id="mro" name="mro"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('mro') is-invalid @enderror"
                                        value="{{ old('mro', $testing->mro) }}">
                                    @error('mro')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Fecha programada -->
                                <div>
                                    <label for="scheduled_time" class="form-label">Scheduled Time</label>
                                    <input type="datetime-local" id="scheduled_time" name="scheduled_time"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('scheduled_time') is-invalid @enderror"
                                        value="{{ old('scheduled_time', $testing->scheduled_time ? $testing->scheduled_time->format('Y-m-d\TH:i') : '') }}">
                                    @error('scheduled_time')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Resultado del Test -->
                                <div>
                                    <label for="test_result" class="form-label">Test Result</label>
                                    <select id="test_result" name="test_result"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('test_result') is-invalid @enderror">
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getTestResults() as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('test_result', $testing->test_result) == $key ? 'selected' : '' }}>
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
                                                {{ old('status', $testing->status) == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Siguiente fecha de prueba -->
                                <div>
                                    <label for="next_test_due" class="form-label">Next Test Due</label>
                                    <x-base.litepicker id="next_test_due" name="next_test_due" class="w-full"
                                        value="{{ old('next_test_due', $testing->next_test_due ? $testing->next_test_due->format('m/d/Y') : '') }}"
                                        placeholder="Select Date" />
                                    @error('next_test_due')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Facturación a -->
                                <div>
                                    <label for="bill_to" class="form-label">Bill To</label>
                                    <select id="bill_to" name="bill_to"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('bill_to') is-invalid @enderror">
                                        <option value="">Select Billing Option</option>
                                        @foreach (\App\Models\Admin\Driver\DriverTesting::getBillOptions() as $option)
                                            <option value="{{ $option }}"
                                                {{ old('bill_to', $testing->bill_to) == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bill_to')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="form-label">Test Details <span class="text-danger">*</span></label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                    <div class="flex items-center">
                                        <input id="is_random_test" name="is_random_test" type="checkbox"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            value="1"
                                            {{ old('is_random_test', $testing->is_random_test) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_random_test">Random Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="is_post_accident_test" name="is_post_accident_test" type="checkbox"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            value="1"
                                            {{ old('is_post_accident_test', $testing->is_post_accident_test) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_post_accident_test">Post Accident
                                            Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="is_reasonable_suspicion_test" name="is_reasonable_suspicion_test"
                                            type="checkbox"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            value="1"
                                            {{ old('is_reasonable_suspicion_test', $testing->is_reasonable_suspicion_test) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_reasonable_suspicion_test">Reasonable
                                            Suspicion Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="is_pre_employment_test" name="is_pre_employment_test" type="checkbox"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            value="1"
                                            {{ old('is_pre_employment_test', $testing->is_pre_employment_test ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_pre_employment_test">Pre-Employment
                                            Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="is_follow_up_test" name="is_follow_up_test" type="checkbox"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            value="1"
                                            {{ old('is_follow_up_test', $testing->is_follow_up_test ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_follow_up_test">Follow-Up Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="is_return_to_duty_test" name="is_return_to_duty_test" type="checkbox"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            value="1"
                                            {{ old('is_return_to_duty_test', $testing->is_return_to_duty_test ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_return_to_duty_test">Return-To-Duty
                                            Test</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="is_other_reason_test" name="is_other_reason_test" type="checkbox"
                                            class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded mr-2"
                                            value="1"
                                            {{ old('is_other_reason_test', $testing->is_other_reason_test ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_other_reason_test">Other
                                            Reason</label>
                                    </div>
                                </div>
                                @error('test_details')
                                    <div class="text-danger mt-2 text-sm">{{ $message }}</div>
                                @enderror
                                <!-- Campo de descripción para Other Reason -->
                                <div id="other_reason_container" class="mt-3" style="display: none;">
                                    <input type="text" id="other_reason_description" name="other_reason_description"
                                        class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('other_reason_description') is-invalid border-danger @enderror"
                                        placeholder="Specify other reason"
                                        value="{{ old('other_reason_description', $testing->other_reason_description) }}">
                                    @error('other_reason_description')
                                        <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-6">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea id="notes" name="notes" rows="4"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8 @error('notes') is-invalid @enderror"
                                    placeholder="Add any additional notes here">{{ old('notes', $testing->notes) }}</textarea>
                                @error('notes')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Adjuntar Archivos -->
                            <div class="mt-6">
                                <label class="form-label">Attach Files (Optional)</label>
                                <p class="text-sm text-slate-500 mb-3">Upload any supporting documents such as test results
                                    or reports.</p>

                                @php
                                    $existingFilesArray = [];
                                    foreach ($testing->getMedia('document_attachments') as $document) {
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
                                                'url' => $document->getUrl(),
                                                'is_temp' => false,
                                                'media_id' => $document->id,
                                            ];
                                        } catch (\Exception $e) {
                                            \Illuminate\Support\Facades\Log::error(
                                                'Error al procesar documento para vista',
                                                [
                                                    'document_id' => $document->id ?? 'unknown',
                                                    'error' => $e->getMessage(),
                                                ],
                                            );
                                        }
                                    }
                                @endphp

                                <livewire:components.file-uploader model-name="driver_testing_files" :model-index="0"
                                    :label="'Upload Files'" :existing-files="$existingFilesArray" />
                                <!-- Campo oculto para almacenar los archivos subidos -->
                                <input type="hidden" name="driver_testing_files" id="driver_testing_files_input">
                                @error('driver_testing_files')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-6 flex justify-end gap-2">
                                <a href="{{ route('carrier.drivers.testings.show', $testing->id) }}">
                                    <x-base.button type="button" variant="outline-secondary" class="flex items-center">
                                        <x-base.lucide icon="x" class="w-4 h-4 mr-2" />
                                        Cancel
                                    </x-base.button>
                                </a>
                                <x-base.button type="submit" variant="primary" class="flex items-center" id="submit-button">
                                    <x-base.lucide icon="save" class="w-4 h-4 mr-2" />
                                    Update Drug Test
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
            // Initialize the carrier driver testing form in edit mode
            const form = new CarrierDriverTestingForm({
                isEditMode: true,
                currentDriverId: {{ $testing->user_driver_detail_id }}
            });
        });
    </script>
@endpush
