@extends('../themes/' . $activeTheme)
@section('title', 'Edit Accident Record')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Accidents', 'url' => route('carrier.drivers.accidents.index')],
    ['label' => 'Edit', 'active' => true],
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
            Edit Accident Record
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
            <form id="accidentForm" action="{{ route('carrier.drivers.accidents.update', $accident) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

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
                                <option value="{{ $driver->id }}" {{ (old('user_driver_detail_id', $accident->user_driver_detail_id) == $driver->id) ? 'selected' : '' }}>
                                    {{ implode(' ', array_filter([$driver->user->name ?? '', $driver->middle_name, $driver->last_name])) }}
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
                            <x-base.litepicker id="accident_date" name="accident_date" value="{{ old('accident_date', $accident->accident_date ? \Carbon\Carbon::parse($accident->accident_date)->format('m/d/Y') : '') }}" placeholder="MM/DD/YYYY" required />
                            @error('accident_date')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Nature of Accident -->
                        <div class="lg:col-span-2">
                            <x-base.form-label for="nature_of_accident" class="form-label required">Nature of Accident</x-base.form-label>
                            <x-base.form-textarea id="nature_of_accident" name="nature_of_accident" class="form-control @error('nature_of_accident') is-invalid @enderror" rows="3" placeholder="Describe the nature of the accident" required>{{ old('nature_of_accident', $accident->nature_of_accident) }}</x-base.form-textarea>
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
                                <input id="had_injuries" name="had_injuries" type="checkbox" value="1" {{ old('had_injuries', $accident->had_injuries) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="had_injuries" class="form-check-label ml-2">
                                    Were there any injuries?
                                </label>
                            </div>
                            <div id="injuries_count_section" class="{{ old('had_injuries', $accident->had_injuries) ? '' : 'hidden' }}">
                                <x-base.form-label for="number_of_injuries" class="form-label">Number of Injuries</x-base.form-label>
                                <x-base.form-input type="number" id="number_of_injuries" name="number_of_injuries" class="form-control @error('number_of_injuries') is-invalid @enderror" value="{{ old('number_of_injuries', $accident->number_of_injuries ?? 0) }}" min="0" />
                                @error('number_of_injuries')
                                <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Fatalities -->
                        <div>
                            <x-base.form-label class="form-label">Fatalities</x-base.form-label>
                            <div class="flex items-center mb-3">
                                <input id="had_fatalities" name="had_fatalities" type="checkbox" value="1" {{ old('had_fatalities', $accident->had_fatalities) ? 'checked' : '' }}
                                class="form-checkbox h-4 w-4 text-primary border-gray-300 rounded" />
                                <label for="had_fatalities" class="form-check-label ml-2">
                                    Were there any fatalities?
                                </label>
                            </div>
                            <div id="fatalities_count_section" class="{{ old('had_fatalities', $accident->had_fatalities) ? '' : 'hidden' }}">
                                <x-base.form-label for="number_of_fatalities" class="form-label">Number of Fatalities</x-base.form-label>
                                <x-base.form-input type="number" id="number_of_fatalities" name="number_of_fatalities" class="form-control @error('number_of_fatalities') is-invalid @enderror" value="{{ old('number_of_fatalities', $accident->number_of_fatalities ?? 0) }}" min="0" />
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
                            <x-base.form-textarea id="comments" name="comments" class="form-control @error('comments') is-invalid @enderror" rows="4" placeholder="Enter any additional comments or details about the accident">{{ old('comments', $accident->comments) }}</x-base.form-textarea>
                            @error('comments')
                            <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 4: Existing Documents -->
                @if(($oldDocuments && $oldDocuments->count() > 0) || ($mediaDocuments && $mediaDocuments->count() > 0))
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Existing Documents</h4>
                    
                    <!-- Media Library Documents -->
                    @if($mediaDocuments && $mediaDocuments->count() > 0)
                    <div class="mb-6">
                        <h5 class="text-md font-medium mb-3 text-gray-700">Current Documents</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($mediaDocuments as $media)
                            <div class="border border-gray-200 rounded-lg p-4 bg-white hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        @if(str_starts_with($media->mime_type, 'image/'))
                                            <img src="{{ $media->getUrl() }}" alt="{{ $media->file_name }}" class="w-full h-32 object-cover rounded mb-2">
                                        @else
                                            <div class="w-full h-32 bg-gray-100 rounded mb-2 flex items-center justify-center">
                                                <x-base.lucide class="w-12 h-12 text-gray-400" icon="file-text" />
                                            </div>
                                        @endif
                                        <p class="text-sm font-medium text-gray-900 truncate" title="{{ $media->file_name }}">{{ $media->file_name }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($media->size / 1024, 2) }} KB</p>
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-3">
                                    <x-base.button type="button" variant="outline-primary" size="sm" class="flex-1" onclick="previewDocument('media_{{ $media->id }}')">
                                        <x-base.lucide class="w-3 h-3 mr-1" icon="eye" />
                                        Preview
                                    </x-base.button>
                                    <x-base.button type="button" variant="outline-danger" size="sm" onclick="deleteMediaDocument({{ $media->id }})">
                                        <x-base.lucide class="w-3 h-3" icon="trash-2" />
                                    </x-base.button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Old System Documents -->
                    @if($oldDocuments && $oldDocuments->count() > 0)
                    <div class="mb-6">
                        <h5 class="text-md font-medium mb-3 text-gray-700">Legacy Documents</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($oldDocuments as $doc)
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <div class="w-full h-32 bg-gray-200 rounded mb-2 flex items-center justify-center">
                                            <x-base.lucide class="w-12 h-12 text-gray-500" icon="file" />
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 truncate" title="{{ $doc->original_name }}">{{ $doc->original_name }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($doc->size / 1024, 2) }} KB</p>
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-3">
                                    <x-base.button type="button" variant="outline-primary" size="sm" class="flex-1" onclick="previewDocument('doc_{{ $doc->id }}')">
                                        <x-base.lucide class="w-3 h-3 mr-1" icon="eye" />
                                        Preview
                                    </x-base.button>
                                    <x-base.button type="button" variant="outline-danger" size="sm" onclick="deleteOldDocument({{ $doc->id }})">
                                        <x-base.lucide class="w-3 h-3" icon="trash-2" />
                                    </x-base.button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Section 5: Add New Documents -->
                <div class="mb-8">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 border-b pb-2">Add New Documents</h4>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <x-base.form-label class="form-label">Upload Additional Documents</x-base.form-label>
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
                        Update Accident Record
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<x-base.dialog id="previewModal" size="xl">
    <x-base.dialog.panel>
        <x-base.dialog.title>
            <h2 class="mr-auto text-base font-medium">Document Preview</h2>
        </x-base.dialog.title>
        <x-base.dialog.description class="p-0">
            <div id="preview-content" class="w-full" style="min-height: 500px;">
                <div class="flex items-center justify-center h-96">
                    <div class="text-center">
                        <x-base.lucide class="w-12 h-12 mx-auto text-gray-400 mb-3" icon="loader" />
                        <p class="text-gray-500">Loading preview...</p>
                    </div>
                </div>
            </div>
        </x-base.dialog.description>
        <x-base.dialog.footer class="text-right">
            <x-base.button type="button" variant="outline-secondary" data-tw-dismiss="modal">
                Close
            </x-base.button>
        </x-base.dialog.footer>
    </x-base.dialog.panel>
</x-base.dialog>
@endsection

@push('scripts')
<script src="{{ asset('js/carrier-accidents.js') }}"></script>
<script src="{{ asset('js/carrier-accidents-documents.js') }}"></script>
@endpush
