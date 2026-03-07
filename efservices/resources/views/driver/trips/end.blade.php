@extends('../themes/' . $activeTheme)
@section('title', 'End Trip - ' . $trip->trip_number)

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'My Trips', 'url' => route('driver.trips.index')],
        ['label' => 'Trip Details', 'url' => route('driver.trips.show', $trip)],
        ['label' => 'End Trip', 'active' => true],
    ];
@endphp

@section('subcontent')

    <!-- Professional Breadcrumbs -->
    <div class="mb-6">
        <x-base.breadcrumb :links="$breadcrumbLinks" />
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="box box--stacked mb-6 border-l-4 border-success">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-success/10 flex items-center justify-center">
                            <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-success mb-2">Success</h3>
                        <p class="text-slate-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="box box--stacked mb-6 border-l-4 border-danger">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-danger/10 flex items-center justify-center">
                            <x-base.lucide class="w-6 h-6 text-danger" icon="AlertCircle" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-danger mb-2">Error</h3>
                        <p class="text-slate-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-warning/10 rounded-xl border border-warning/20">
                    <x-base.lucide class="w-8 h-8 text-warning" icon="Square" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">End Trip</h1>
                    <p class="text-slate-600">Trip: {{ $trip->trip_number ?: 'Trip #' . $trip->id }}</p>
                </div>
            </div>
            <x-base.button as="a" href="{{ route('driver.trips.show', $trip) }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Trip
            </x-base.button>
        </div>
    </div>

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="box box--stacked mb-6 border-l-4 border-danger">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-danger/10 flex items-center justify-center">
                            <x-base.lucide class="w-6 h-6 text-danger" icon="AlertTriangle" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-danger mb-3">Please correct the following errors:</h3>
                        <ul class="space-y-2">
                            @foreach ($errors->all() as $error)
                                <li class="flex items-start gap-2">
                                    <x-base.lucide class="w-4 h-4 text-danger mt-0.5 flex-shrink-0" icon="XCircle" />
                                    <span class="text-slate-700">{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-6">
        <!-- Main Form -->
        <div class="col-span-12 lg:col-span-8">
            <form action="{{ route('driver.trips.end', $trip) }}" method="POST" id="postTripInspectionForm">
                @csrf

                <!-- Post-Trip Inspection Header -->
                <div class="box box--stacked flex flex-col p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="FileCheck" />
                        <h2 class="text-lg font-semibold text-slate-800">Post-Trip Vehicle Inspection Report</h2>
                    </div>

                    <p class="text-slate-600 mb-4">
                        Federal Motor Carrier Safety Regulations (49 CFR 396.11) require this inspection.
                        <strong>All items must be checked</strong> after completing your trip.
                    </p>

                    @if ($trip->has_trailer)
                        <div class="p-4 bg-info/5 rounded-lg border border-info/20">
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-5 h-5 text-info" icon="Container" />
                                <span class="text-sm font-semibold text-slate-800">This trip includes a trailer - trailer
                                    inspection is required</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Tractor/Truck Inspection Checklist -->
                <div class="box box--stacked flex flex-col p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Truck" />
                        <h3 class="text-md font-semibold text-slate-800">Tractor/Truck Inspection</h3>
                        <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded">All items required</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach ($tractorColumns as $colNum => $items)
                            <div class="space-y-2">
                                @foreach ($items as $key)
                                    <label
                                        class="flex items-center gap-3 p-3 bg-slate-50/50 rounded-lg border border-slate-200 hover:bg-slate-100/50 transition-colors cursor-pointer">
                                        <input type="checkbox" name="tractor[]" value="{{ $key }}"
                                            id="tractor_{{ $key }}"
                                            class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary tractor-checkbox"
                                            {{ in_array($key, old('tractor', [])) ? 'checked' : '' }}>
                                        <span class="text-sm text-slate-800">{{ $tractorItems[$key] }}</span>
                                    </label>
                                    @if ($key === 'other_tractor')
                                        <div id="other_tractor_container" class="ml-7 hidden">
                                            <input type="text" name="other_tractor" id="other_tractor_input"
                                                value="{{ old('other_tractor') }}" placeholder="Please specify..."
                                                class="w-full form-control rounded-lg border-slate-200 text-sm">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <!-- Select All Button -->
                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <button type="button" id="selectAllTractor"
                            class="text-sm text-primary hover:text-primary/80 font-medium">
                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="CheckSquare" />
                            Select All Tractor Items
                        </button>
                    </div>
                </div>

                <!-- Trailer Inspection Checklist (Conditional - based on trip has_trailer) -->
                @if ($trip->has_trailer)
                    <div id="trailer-section" class="box box--stacked flex flex-col p-6 mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="Container" />
                            <h3 class="text-md font-semibold text-slate-800">Trailer Inspection</h3>
                            <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded">All items required</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach ($trailerColumns as $colNum => $items)
                                <div class="space-y-2">
                                    @foreach ($items as $key)
                                        <label
                                            class="flex items-center gap-3 p-3 bg-slate-50/50 rounded-lg border border-slate-200 hover:bg-slate-100/50 transition-colors cursor-pointer">
                                            <input type="checkbox" name="trailer[]" value="{{ $key }}"
                                                id="trailer_{{ $key }}"
                                                class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary trailer-checkbox"
                                                {{ in_array($key, old('trailer', [])) ? 'checked' : '' }}>
                                            <span class="text-sm text-slate-800">{{ $trailerItems[$key] }}</span>
                                        </label>
                                        @if ($key === 'other_trailer')
                                            <div id="other_trailer_container" class="ml-7 hidden">
                                                <input type="text" name="other_trailer" id="other_trailer_input"
                                                    value="{{ old('other_trailer') }}" placeholder="Please specify..."
                                                    class="w-full form-control rounded-lg border-slate-200 text-sm">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        <!-- Select All Button -->
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <button type="button" id="selectAllTrailer"
                                class="text-sm text-primary hover:text-primary/80 font-medium">
                                <x-base.lucide class="w-4 h-4 inline mr-1" icon="CheckSquare" />
                                Select All Trailer Items
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Condition & Remarks -->
                <div class="box box--stacked flex flex-col p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="ClipboardCheck" />
                        <h3 class="text-md font-semibold text-slate-800">Condition Certification & Remarks</h3>
                    </div>

                    <div class="space-y-4">
                        <!-- Condition Satisfactory - REQUIRED -->
                        <div class="p-4 bg-success/5 rounded-lg border border-success/20">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="condition_satisfactory" value="1" required
                                    class="w-5 h-5 text-success border-slate-300 rounded focus:ring-success"
                                    {{ old('condition_satisfactory') ? 'checked' : '' }}>
                                <span class="text-sm font-semibold text-slate-800">
                                    Condition of the above vehicle is satisfactory <span class="text-danger">*</span>
                                </span>
                            </label>
                            <p class="text-xs text-slate-500 mt-2 ml-8">Required to complete the post-trip inspection
                                report.</p>
                        </div>

                        <!-- Defects Corrected -->
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="defects_corrected" value="1"
                                    id="post_defects_corrected"
                                    class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary"
                                    {{ old('defects_corrected') ? 'checked' : '' }}>
                                <span class="text-sm font-medium text-slate-700">
                                    Above Defects Corrected
                                </span>
                            </label>
                            <div id="post_defects_corrected_notes_container"
                                class="mt-3 ml-8 {{ old('defects_corrected') ? '' : 'hidden' }}">
                                <x-base.form-textarea id="post_defects_corrected_notes" name="defects_corrected_notes"
                                    rows="2"
                                    placeholder="Describe the corrections made...">{{ old('defects_corrected_notes') }}</x-base.form-textarea>
                            </div>
                        </div>

                        <!-- Defects Not Need Correction -->
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="defects_not_need_correction" value="1"
                                    id="post_defects_not_need_correction"
                                    class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary"
                                    {{ old('defects_not_need_correction') ? 'checked' : '' }}>
                                <span class="text-sm font-medium text-slate-700">
                                    Above Defects Need NOT Be Corrected For Safe Operation Of Vehicle
                                </span>
                            </label>
                            <div id="post_defects_not_need_correction_notes_container"
                                class="mt-3 ml-8 {{ old('defects_not_need_correction') ? '' : 'hidden' }}">
                                <x-base.form-textarea id="post_defects_not_need_correction_notes"
                                    name="defects_not_need_correction_notes" rows="2"
                                    placeholder="Explain why correction is not needed for safe operation...">{{ old('defects_not_need_correction_notes') }}</x-base.form-textarea>
                            </div>
                        </div>

                        <!-- Remarks/Notes -->
                        <div>
                            <x-base.form-label for="remarks">Remarks / Defects Found (optional)</x-base.form-label>
                            <x-base.form-textarea id="remarks" name="remarks" rows="3"
                                placeholder="Describe any defects, issues, or notes about the vehicle condition...">{{ old('remarks') }}</x-base.form-textarea>
                            <p class="text-xs text-slate-500 mt-1">If defects are found, describe them here so maintenance
                                can be scheduled.</p>
                        </div>
                    </div>
                </div>

                <!-- Trip Notes -->
                <div class="box box--stacked flex flex-col p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                        <h2 class="text-lg font-semibold text-slate-800">Trip Notes</h2>
                    </div>
                    <div>
                        <x-base.form-label for="notes">Add any notes about this trip (optional)</x-base.form-label>
                        <x-base.form-textarea id="notes" name="notes" rows="4"
                            placeholder="Any issues, delays, or comments about the trip...">{{ old('notes') }}</x-base.form-textarea>
                    </div>
                </div>

                <!-- Trip Summary -->
                <div class="box box--stacked flex flex-col p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                        <h2 class="text-lg font-semibold text-slate-800">Trip Summary</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label
                                class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Started</label>
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400" icon="Calendar" />
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $trip->actual_start_time ? $trip->actual_start_time->format('M d, Y H:i') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label
                                class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Duration</label>
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400" icon="Clock" />
                                <p class="text-sm font-semibold text-slate-800">
                                    @if ($trip->actual_start_time)
                                        {{ $trip->actual_start_time->diffForHumans(now(), true) }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label
                                class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">From</label>
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                                <p class="text-sm font-semibold text-slate-800">{{ $trip->origin_address ?: 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">To</label>
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                                <p class="text-sm font-semibold text-slate-800">{{ $trip->destination_address ?: 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Form Actions -->
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <x-base.button as="a" href="{{ route('driver.trips.show', $trip) }}" variant="secondary"
                        class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="X" />
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="warning" class="flex-1 gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Square" />
                        End Trip
                    </x-base.button>
                </div>
            </form>
        </div>

        <!-- Sidebar - What happens next? -->
        <div class="col-span-12 lg:col-span-4">
            <div class="box box--stacked flex flex-col p-6 bg-primary/5 border-l-4 border-primary">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                    </div>
                    <h2 class="text-lg font-semibold text-slate-800">What happens next?</h2>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="w-6 h-6 rounded-full bg-success/10 flex items-center justify-center">
                                <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-800 text-sm mb-1">Trip will be marked as completed</div>
                            <p class="text-xs text-slate-500">The trip status will automatically update to "Completed"</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="w-6 h-6 rounded-full bg-success/10 flex items-center justify-center">
                                <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-800 text-sm mb-1">Your HOS status will change to "Off Duty"
                            </div>
                            <p class="text-xs text-slate-500">Hours of Service tracking will automatically stop</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="w-6 h-6 rounded-full bg-success/10 flex items-center justify-center">
                                <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-800 text-sm mb-1">Daily HOS report will be generated</div>
                            <p class="text-xs text-slate-500">A summary of your driving hours will be created</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="w-6 h-6 rounded-full bg-success/10 flex items-center justify-center">
                                <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                            </div>
                        </div>
                        <div>
                            <div class="font-medium text-slate-800 text-sm mb-1">GPS tracking will stop</div>
                            <p class="text-xs text-slate-500">Location tracking will be automatically disabled</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inspection Progress -->
            <div class="box box--stacked flex flex-col p-6 mt-6">
                <div class="flex items-center gap-3 mb-4">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="BarChart2" />
                    <h3 class="text-md font-semibold text-slate-800">Inspection Progress</h3>
                </div>

                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600">Tractor Items</span>
                            <span id="tractorProgress"
                                class="font-medium text-slate-800">0/{{ count($tractorItems) }}</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div id="tractorProgressBar" class="bg-primary h-2 rounded-full transition-all duration-300"
                                style="width: 0%"></div>
                        </div>
                    </div>
                    @if ($trip->has_trailer)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-600">Trailer Items</span>
                                <span id="trailerProgress"
                                    class="font-medium text-slate-800">0/{{ count($trailerItems) }}</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-2">
                                <div id="trailerProgressBar"
                                    class="bg-primary h-2 rounded-full transition-all duration-300" style="width: 0%">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Trip Documents Section -->
            <div class="box box--stacked flex flex-col p-6 mt-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Paperclip" />
                        <h2 class="text-lg font-semibold text-slate-800">Trip Documents</h2>
                    </div>
                    <span class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded">Optional</span>
                </div>

                <p class="text-slate-600 mb-4 text-sm">
                    Upload any documents related to this trip (BOL, POD, receipts, photos, etc.).
                    You can also upload documents after completing the trip.
                </p>

                <!-- Existing Documents -->
                @php
                    $tripDocuments = $trip->getTripDocuments();
                @endphp
                @if ($tripDocuments->count() > 0)
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-slate-700 mb-3">Uploaded Documents
                            ({{ $tripDocuments->count() }})</h3>
                        <div class="space-y-2">
                            @foreach ($tripDocuments as $document)
                                <div
                                    class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div
                                            class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center
                                                    {{ str_starts_with($document->mime_type, 'image/') ? 'bg-success/10' : 'bg-danger/10' }}">
                                            <x-base.lucide
                                                class="w-5 h-5 {{ str_starts_with($document->mime_type, 'image/') ? 'text-success' : 'text-danger' }}"
                                                icon="{{ str_starts_with($document->mime_type, 'image/') ? 'Image' : 'FileText' }}" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-slate-800 text-sm truncate">
                                                {{ $document->file_name }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $document->getCustomProperty('document_type_name', 'Document') }}
                                                - {{ number_format($document->size / 1024, 1) }} KB
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('driver.trips.documents.preview', [$trip, $document->id]) }}"
                                            target="_blank"
                                            class="w-8 h-8 bg-slate-200 hover:bg-slate-300 rounded-lg flex items-center justify-center transition-colors">
                                            <x-base.lucide class="w-4 h-4 text-slate-600" icon="Eye" />
                                        </a>
                                        @if ($trip->canDeleteDocuments())
                                            <form
                                                action="{{ route('driver.trips.documents.delete', [$trip, $document->id]) }}"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="w-8 h-8 bg-danger/10 hover:bg-danger/20 rounded-lg flex items-center justify-center transition-colors">
                                                    <x-base.lucide class="w-4 h-4 text-danger" icon="Trash2" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Upload Button -->
                <x-base.button type="button" variant="outline-primary" class="gap-2" data-tw-toggle="modal"
                    data-tw-target="#end-upload-documents-modal">
                    <x-base.lucide class="w-4 h-4" icon="Upload" />
                    {{ $tripDocuments->count() > 0 ? 'Add More Documents' : 'Upload Documents' }}
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Upload Documents Modal -->
    <x-base.dialog id="end-upload-documents-modal" size="lg">
        <x-base.dialog.panel>
            <form action="{{ route('driver.trips.documents.upload', $trip) }}" method="POST"
                enctype="multipart/form-data" id="endUploadDocumentsForm">
                @csrf
                <x-base.dialog.title class="border-b border-slate-200 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="Upload" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">Upload Trip Documents</h3>
                            <p class="text-sm text-slate-500">Add BOL, POD, receipts, photos and more</p>
                        </div>
                    </div>
                </x-base.dialog.title>

                <x-base.dialog.description class="py-4">
                    <div id="endModalDocumentInputs" class="max-h-[400px] overflow-y-auto space-y-4 pr-2">
                        <!-- Document Input Template -->
                        <div class="end-modal-document-group p-4 bg-slate-50/50 rounded-lg border border-slate-200"
                            data-index="0">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                <div>
                                    <x-base.form-label>Document Type *</x-base.form-label>
                                    <select name="document_types[]" class="w-full form-select rounded-lg border-slate-200"
                                        required>
                                        @foreach (\App\Models\Trip::DOCUMENT_TYPES as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-base.form-label>File *</x-base.form-label>
                                    <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif"
                                        required
                                        class="w-full form-control rounded-lg border-slate-200 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                </div>
                            </div>
                            <div>
                                <x-base.form-label>Notes (optional)</x-base.form-label>
                                <input type="text" name="document_notes[]" placeholder="Brief description..."
                                    class="w-full form-control rounded-lg border-slate-200">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <button type="button" id="addEndModalDocumentBtn"
                            class="flex items-center gap-2 text-sm text-primary hover:text-primary/80 font-medium">
                            <x-base.lucide class="w-4 h-4" icon="Plus" />
                            Add Another Document
                        </button>
                        <p class="text-xs text-slate-400 mt-2">Max 10 documents per upload. Supported: PDF, JPG, PNG, WebP,
                            GIF (max 10MB each)</p>
                    </div>
                </x-base.dialog.description>

                <x-base.dialog.footer class="border-t border-slate-200 pt-4">
                    <div class="flex gap-3 justify-end w-full">
                        <x-base.button type="button" variant="outline-secondary" data-tw-dismiss="modal">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary" class="gap-2">
                            <x-base.lucide class="w-4 h-4" icon="Upload" />
                            Upload Documents
                        </x-base.button>
                    </div>
                </x-base.dialog.footer>
            </form>
        </x-base.dialog.panel>
    </x-base.dialog>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {            
            const tractorCheckboxes = document.querySelectorAll('.tractor-checkbox');
            const trailerCheckboxes = document.querySelectorAll('.trailer-checkbox');

            // "Other" items are OPTIONAL - don't count them in required totals
            const tractorTotal = {{ count($tractorItems) }} - 1; // Exclude "other_tractor"
            const trailerTotal = {{ count($trailerItems) }} - 1; // Exclude "other_trailer"
            const hasTrailer = {{ $trip->has_trailer ? 'true' : 'false' }};


            // Check for configuration mismatch
            if (tractorCheckboxes.length !== tractorTotal) {
                console.error('MISMATCH: Expected ' + tractorTotal + ' tractor checkboxes but found ' +
                    tractorCheckboxes.length);
                alert('Configuration Error: Expected ' + tractorTotal + ' tractor checkboxes but found ' +
                    tractorCheckboxes.length + '. Please contact support.');
            }
            if (hasTrailer && trailerCheckboxes.length !== trailerTotal) {
                console.error('MISMATCH: Expected ' + trailerTotal + ' trailer checkboxes but found ' +
                    trailerCheckboxes.length);
                alert('Configuration Error: Expected ' + trailerTotal + ' trailer checkboxes but found ' +
                    trailerCheckboxes.length + '. Please contact support.');
            }

            // Function to get missing checkboxes for a section
            function getMissingCheckboxes(checkboxes, total) {
                const checked = document.querySelectorAll(checkboxes + ':checked').length;
                return total - checked;
            }

            // Function to scroll to first section with missing items
            function scrollToFirstIncompleteSection(hasMissingTractor, hasMissingTrailer) {
                if (hasMissingTractor) {
                    // Scroll to tractor section
                    const tractorSection = document.querySelector('.tractor-checkbox').closest('.box');
                    if (tractorSection) {
                        tractorSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                } else if (hasMissingTrailer) {
                    // Scroll to trailer section
                    const trailerSection = document.getElementById('trailer-section');
                    if (trailerSection) {
                        trailerSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            }

            // Function to validate "Other" fields
            function validateOtherFields() {
                const errors = [];

                // Check if "other_tractor" checkbox is marked
                const otherTractorCheckbox = document.getElementById('tractor_other_tractor');
                if (otherTractorCheckbox && otherTractorCheckbox.checked) {
                    const otherTractorInput = document.getElementById('other_tractor_input');
                    if (otherTractorInput && otherTractorInput.value.trim() === '') {
                        errors.push({
                            field: 'other_tractor',
                            message: 'Please specify details for "Other" in the Tractor/Truck section.'
                        });
                    }
                }

                // Check if "other_trailer" checkbox is marked (only if has_trailer)
                if (hasTrailer) {
                    const otherTrailerCheckbox = document.getElementById('trailer_other_trailer');
                    if (otherTrailerCheckbox && otherTrailerCheckbox.checked) {
                        const otherTrailerInput = document.getElementById('other_trailer_input');
                        if (otherTrailerInput && otherTrailerInput.value.trim() === '') {
                            errors.push({
                                field: 'other_trailer',
                                message: 'Please specify details for "Other" in the Trailer section.'
                            });
                        }
                    }
                }

                return {
                    valid: errors.length === 0,
                    errors: errors
                };
            }

            // Form validation function - intercepts submit event
            function validateInspectionForm(event) {

                const tractorChecked = document.querySelectorAll('.tractor-checkbox:checked').length;
                const trailerChecked = hasTrailer ? document.querySelectorAll('.trailer-checkbox:checked').length :
                    0;

                let isValid = true;
                let errorMessage = 'Please complete the inspection checklist:\n\n';
                let hasMissingTractor = false;
                let hasMissingTrailer = false;

                // Validate tractor checkboxes
                const missingTractor = getMissingCheckboxes('.tractor-checkbox', tractorTotal);
                if (missingTractor > 0) {
                    isValid = false;
                    hasMissingTractor = true;
                    errorMessage +=
                        `• Tractor/Truck: ${missingTractor} item${missingTractor > 1 ? 's' : ''} remaining (${tractorChecked}/${tractorTotal} checked)\n`;
                }

                // Validate trailer checkboxes (only if has_trailer)
                if (hasTrailer) {
                    const missingTrailer = getMissingCheckboxes('.trailer-checkbox', trailerTotal);
                    if (missingTrailer > 0) {
                        isValid = false;
                        hasMissingTrailer = true;
                        errorMessage +=
                            `• Trailer: ${missingTrailer} item${missingTrailer > 1 ? 's' : ''} remaining (${trailerChecked}/${trailerTotal} checked)\n`;
                    }
                }

                // Validate "Other" fields
                const otherFieldsValidation = validateOtherFields();
                if (!otherFieldsValidation.valid) {
                    isValid = false;
                    otherFieldsValidation.errors.forEach(error => {
                        errorMessage += `• ${error.message}\n`;
                    });
                }

                // If validation fails, prevent form submission and show error
                if (!isValid) {
                    event.preventDefault();
                    alert(errorMessage);

                    // Focus on the first "Other" field that needs attention, or scroll to incomplete section
                    if (!otherFieldsValidation.valid && otherFieldsValidation.errors.length > 0) {
                        const firstErrorField = otherFieldsValidation.errors[0].field;
                        const inputField = document.getElementById(`${firstErrorField}_input`);
                        if (inputField) {
                            inputField.focus();
                            inputField.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    } else {
                        // Scroll to the first section with missing items
                        scrollToFirstIncompleteSection(hasMissingTractor, hasMissingTrailer);
                    }

                    return false;
                }

                return true;
            }

            // Attach validation to form submit event
            const form = document.getElementById('postTripInspectionForm');

            if (form) {
                form.addEventListener('submit', function(event) {   
                    return validateInspectionForm(event);
                });

                // Also listen for click on submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.addEventListener('click', function(e) {
                        return validateInspectionForm(e);
                    });
                }
            }

            // Update progress bars
            function updateProgress() {
                const tractorChecked = document.querySelectorAll('.tractor-checkbox:checked').length;

                document.getElementById('tractorProgress').textContent = `${tractorChecked}/${tractorTotal}`;
                document.getElementById('tractorProgressBar').style.width =
                    `${(tractorChecked / tractorTotal) * 100}%`;

                if (hasTrailer) {
                    const trailerChecked = document.querySelectorAll('.trailer-checkbox:checked').length;
                    document.getElementById('trailerProgress').textContent = `${trailerChecked}/${trailerTotal}`;
                    document.getElementById('trailerProgressBar').style.width =
                        `${(trailerChecked / trailerTotal) * 100}%`;
                }
            }

            // Listen for checkbox changes
            tractorCheckboxes.forEach(cb => cb.addEventListener('change', updateProgress));
            trailerCheckboxes.forEach(cb => cb.addEventListener('change', updateProgress));

            // Select All buttons - exclude "Other" checkbox
            document.getElementById('selectAllTractor').addEventListener('click', function() {
                tractorCheckboxes.forEach(cb => {
                    // Don't select "Other" checkbox
                    if (cb.value !== 'other_tractor') {
                        cb.checked = true;
                    }
                });
                updateProgress();
            });

            const selectAllTrailerBtn = document.getElementById('selectAllTrailer');
            if (selectAllTrailerBtn) {
                selectAllTrailerBtn.addEventListener('click', function() {
                    trailerCheckboxes.forEach(cb => {
                        // Don't select "Other" checkbox
                        if (cb.value !== 'other_trailer') {
                            cb.checked = true;
                        }
                    });
                    updateProgress();
                });
            }

            // Toggle "Other" input visibility
            function toggleOtherInput(type) {
                const otherCheckbox = document.getElementById(`${type}_other_${type}`);
                const otherContainer = document.getElementById(`other_${type}_container`);

                if (otherCheckbox && otherContainer) {
                    if (otherCheckbox.checked) {
                        otherContainer.classList.remove('hidden');
                    } else {
                        otherContainer.classList.add('hidden');
                    }
                }
            }

            // Listen for "Other" checkbox changes
            const otherTractorCheckbox = document.getElementById('tractor_other_tractor');
            if (otherTractorCheckbox) {
                otherTractorCheckbox.addEventListener('change', function() {
                    toggleOtherInput('tractor');
                });
                if (otherTractorCheckbox.checked) {
                    toggleOtherInput('tractor');
                }
            }

            const otherTrailerCheckbox = document.getElementById('trailer_other_trailer');
            if (otherTrailerCheckbox) {
                otherTrailerCheckbox.addEventListener('change', function() {
                    toggleOtherInput('trailer');
                });
                if (otherTrailerCheckbox.checked) {
                    toggleOtherInput('trailer');
                }
            }

            // Initialize progress on page load
            updateProgress();

            // Modal document upload functionality
            const addEndModalDocumentBtn = document.getElementById('addEndModalDocumentBtn');
            const endModalDocumentInputs = document.getElementById('endModalDocumentInputs');
            let endModalDocumentIndex = 1;
            const maxEndModalDocuments = 10;

            if (addEndModalDocumentBtn && endModalDocumentInputs) {
                addEndModalDocumentBtn.addEventListener('click', function() {
                    const currentCount = endModalDocumentInputs.querySelectorAll(
                        '.end-modal-document-group').length;

                    if (currentCount >= maxEndModalDocuments) {
                        alert('Maximum ' + maxEndModalDocuments + ' documents allowed per upload.');
                        return;
                    }

                    const template = `
                <div class="end-modal-document-group p-4 bg-slate-50/50 rounded-lg border border-slate-200 relative" data-index="${endModalDocumentIndex}">
                    <button type="button" class="remove-end-modal-doc-btn absolute top-2 right-2 w-6 h-6 bg-danger/10 hover:bg-danger/20 rounded-full flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4 text-danger" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3 pr-8">
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-1.5 block">Document Type *</label>
                            <select name="document_types[]" class="w-full form-select rounded-lg border-slate-200" required>
                                @foreach (\App\Models\Trip::DOCUMENT_TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-1.5 block">File *</label>
                            <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif" required
                                class="w-full form-control rounded-lg border-slate-200 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                        </div>
                    </div>
                    <div class="pr-8">
                        <label class="text-sm font-medium text-slate-700 mb-1.5 block">Notes (optional)</label>
                        <input type="text" name="document_notes[]" placeholder="Brief description..."
                            class="w-full form-control rounded-lg border-slate-200">
                    </div>
                </div>
            `;

                    endModalDocumentInputs.insertAdjacentHTML('beforeend', template);
                    endModalDocumentIndex++;
                });

                // Event delegation for remove buttons in modal
                endModalDocumentInputs.addEventListener('click', function(e) {
                    const removeBtn = e.target.closest('.remove-end-modal-doc-btn');
                    if (removeBtn) {
                        const group = removeBtn.closest('.end-modal-document-group');
                        if (group && endModalDocumentInputs.querySelectorAll('.end-modal-document-group')
                            .length > 1) {
                            group.remove();
                        }
                    }
                });
            }
        });
    </script>
@endpush
