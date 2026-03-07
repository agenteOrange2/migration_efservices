@extends('../themes/' . $activeTheme)
@section('title', 'Add Accident Record')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Accidents', 'url' => route('carrier.drivers.accidents.index')],
    ['label' => 'Add', 'active' => true],
];
@endphp

@section('subcontent')
<div>
    <!-- Flash Messages -->
    @if (session('success'))
    <x-base.alert variant="success" dismissible class="flex items-center gap-3 mb-5">
        <x-base.lucide class="w-8 h-8 text-white" icon="check-circle" />
        <span class="text-white">
            {{ session('success') }}
        </span>
        <x-base.alert.dismiss-button class="btn-close">
            <x-base.lucide class="h-4 w-4 text-white" icon="X" />
        </x-base.alert.dismiss-button>
    </x-base.alert>
    @endif

    @if (session('error'))
    <x-base.alert variant="danger" dismissible class="mb-5">
        <span class="text-white">
            {{ session('error') }}
        </span>
        <x-base.alert.dismiss-button class="btn-close">
            <x-base.lucide class="h-4 w-4 text-white" icon="X" />
        </x-base.alert.dismiss-button>
    </x-base.alert>
    @endif

    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center justify-between mt-8">
        <h2 class="text-lg font-medium">
            Add New Accident Record
        </h2>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <x-base.button as="a" href="{{ route('carrier.drivers.accidents.index') }}" variant="outline-secondary">
                <x-base.lucide class="w-4 h-4 mr-1" icon="arrow-left" />
                Back to Accidents
            </x-base.button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="box box--stacked mt-5">
        <div class="box-body p-5">
            <form id="accidentForm" action="{{ route('carrier.drivers.accidents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Section 1: Basic Information -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Basic Information</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Driver -->
                        <div class="lg:col-span-2">
                            <x-base.form-label for="user_driver_detail_id" class="form-label required">Driver</x-base.form-label>
                            <x-base.form-select id="user_driver_detail_id" name="user_driver_detail_id" class="form-select @error('user_driver_detail_id') is-invalid @enderror" required>
                                <option value="">Select Driver</option>
                                @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('user_driver_detail_id') == $driver->id ? 'selected' : '' }}>
                                    {{ implode(' ', array_filter([$driver->user->name, $driver->middle_name, $driver->last_name])) }}
                                </option>
                                @endforeach
                            </x-base.form-select>
                            @error('user_driver_detail_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Accident Date -->
                        <div>
                            <x-base.form-label for="accident_date" class="form-label required">Accident Date</x-base.form-label>
                            <x-base.litepicker id="accident_date" name="accident_date" value="{{ old('accident_date') }}" placeholder="MM/DD/YYYY" required />
                            @error('accident_date')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Nature of Accident -->
                        <div class="lg:col-span-2">
                            <x-base.form-label for="nature_of_accident" class="form-label required">Nature of Accident</x-base.form-label>
                            <x-base.form-textarea id="nature_of_accident" name="nature_of_accident" class="form-control @error('nature_of_accident') is-invalid @enderror" rows="3" placeholder="Describe the nature of the accident" required>{{ old('nature_of_accident') }}</x-base.form-textarea>
                            @error('nature_of_accident')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 2: Injuries and Fatalities -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Injuries and Fatalities</h4>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Injuries -->
                        <div>
                            <x-base.form-label class="form-label">Injuries</x-base.form-label>
                            <div class="flex items-center mb-3">
                                <input id="had_injuries" name="had_injuries" type="checkbox" value="1" {{ old('had_injuries') ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="had_injuries" class="form-check-label ml-2">
                                    Were there any injuries?
                                </label>
                            </div>
                            <div id="injuries_count_section" class="hidden">
                                <x-base.form-label for="number_of_injuries" class="form-label">Number of Injuries</x-base.form-label>
                                <x-base.form-input type="number" id="number_of_injuries" name="number_of_injuries" class="form-control @error('number_of_injuries') is-invalid @enderror" value="{{ old('number_of_injuries', 0) }}" min="0" />
                                @error('number_of_injuries')
                                <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Fatalities -->
                        <div>
                            <x-base.form-label class="form-label">Fatalities</x-base.form-label>
                            <div class="flex items-center mb-3">
                                <input id="had_fatalities" name="had_fatalities" type="checkbox" value="1" {{ old('had_fatalities') ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="had_fatalities" class="form-check-label ml-2">
                                    Were there any fatalities?
                                </label>
                            </div>
                            <div id="fatalities_count_section" class="hidden">
                                <x-base.form-label for="number_of_fatalities" class="form-label">Number of Fatalities</x-base.form-label>
                                <x-base.form-input type="number" id="number_of_fatalities" name="number_of_fatalities" class="form-control @error('number_of_fatalities') is-invalid @enderror" value="{{ old('number_of_fatalities', 0) }}" min="0" />
                                @error('number_of_fatalities')
                                <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Additional Comments -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Additional Information</h4>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <x-base.form-label for="comments" class="form-label">Comments</x-base.form-label>
                            <x-base.form-textarea id="comments" name="comments" class="form-control @error('comments') is-invalid @enderror" rows="4" placeholder="Enter any additional comments or details about the accident">{{ old('comments') }}</x-base.form-textarea>
                            @error('comments')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 4: Accident Documents -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Accident Documents</h4>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <x-base.form-label class="form-label">Upload Documents</x-base.form-label>
                            <div id="file-upload-area" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary transition-colors cursor-pointer">
                                <x-base.lucide class="w-12 h-12 mx-auto text-gray-400 mb-3" icon="upload-cloud" />
                                <p class="text-sm text-gray-600 mb-2">Drag and drop files here or click to browse</p>
                                <p class="text-xs text-gray-500">Supported formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB per file)</p>
                                <input type="file" id="accident_files" name="accident_files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden" />
                                <x-base.button type="button" variant="outline-primary" class="mt-3" onclick="document.getElementById('accident_files').click()">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="file-plus" />
                                    Select Files
                                </x-base.button>
                            </div>
                            <div id="file-list" class="mt-4 space-y-2"></div>
                            @error('accident_files')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                            @error('accident_files.*')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="flex justify-end mt-8 space-x-4">
                    <x-base.button type="button" variant="outline-secondary" as="a" href="{{ route('carrier.drivers.accidents.index') }}">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="save" />
                        Save Accident Record
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/carrier-accidents.js') }}"></script>
@endpush
