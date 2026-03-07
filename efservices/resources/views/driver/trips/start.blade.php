@extends('../themes/' . $activeTheme)
@section('title', 'Start Trip')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'My Trips', 'url' => route('driver.trips.index')],
        ['label' => 'Trip Details', 'url' => route('driver.trips.show', $trip)],
        ['label' => 'Start Trip', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

@if(!$validation['valid'])
    <!-- Error Alert -->
    <div class="box box--stacked mb-6 border-l-4 border-danger">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-danger/10 flex items-center justify-center">
                        <x-base.lucide class="w-6 h-6 text-danger" icon="AlertTriangle" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-danger mb-2">Cannot Start Trip</h3>
                    <p class="text-slate-600 mb-4">The following FMCSA requirements are not met:</p>
                    <ul class="space-y-2">
                        @foreach($validation['errors'] as $error)
                            <li class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-danger mt-0.5 flex-shrink-0" icon="XCircle" />
                                <div>
                                    <span class="font-medium text-slate-800">{{ $error['message'] }}</span>
                                    @if(isset($error['fmcsa_reference']))
                                        <div class="text-xs text-slate-500 mt-0.5">Reference: {{ $error['fmcsa_reference'] }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="px-6 pb-6">
            <x-base.button as="a" href="{{ route('driver.trips.show', $trip) }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Trip
            </x-base.button>
        </div>
    </div>
@else
    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="Play" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Start Trip</h1>
                    <p class="text-slate-600">Trip: {{ $trip->trip_number ?: 'Trip #' . $trip->id }}</p>
                </div>
            </div>
            <x-base.button as="a" href="{{ route('driver.trips.show', $trip) }}" variant="secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Trip
            </x-base.button>
        </div>
    </div>

    <!-- Warnings -->
    @if(!empty($validation['warnings']))
        <div class="box box--stacked mb-6 border-l-4 border-warning">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-warning/10 flex items-center justify-center">
                            <x-base.lucide class="w-6 h-6 text-warning" icon="AlertCircle" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-warning mb-3">Warnings</h3>
                        <ul class="space-y-2">
                            @foreach($validation['warnings'] as $warning)
                                <li class="flex items-start gap-2">
                                    <x-base.lucide class="w-4 h-4 text-warning mt-0.5 flex-shrink-0" icon="Info" />
                                    <span class="text-slate-700">{{ $warning['message'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
            <form action="{{ route('driver.trips.start', $trip) }}" method="POST" id="preTripInspectionForm">
                @csrf

                <!-- Pre-Trip Inspection Header -->
                <div class="box box--stacked flex flex-col p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="FileCheck" />
                        <h2 class="text-lg font-semibold text-slate-800">Pre-Trip Vehicle Inspection Report</h2>
                    </div>

                    <p class="text-slate-600 mb-4">
                        Federal Motor Carrier Safety Regulations (49 CFR 396.11) require this inspection.
                        <strong>All items must be checked</strong> before starting your trip.
                    </p>

                    <!-- Trailer Toggle -->
                    <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="has_trailer" value="1" id="has_trailer"
                                   class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary"
                                   {{ old('has_trailer') ? 'checked' : '' }}>
                            <span class="text-sm font-semibold text-slate-800">This trip includes a trailer</span>
                        </label>
                    </div>
                </div>

                <!-- Tractor/Truck Inspection Checklist -->
                <div class="box box--stacked flex flex-col p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Truck" />
                        <h3 class="text-md font-semibold text-slate-800">Tractor/Truck Inspection</h3>
                        <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded">All items required</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($tractorColumns as $colNum => $items)
                            <div class="space-y-2">
                                @foreach($items as $key)
                                    <label class="flex items-center gap-3 p-3 bg-slate-50/50 rounded-lg border border-slate-200 hover:bg-slate-100/50 transition-colors cursor-pointer">
                                        <input type="checkbox"
                                               name="tractor[]"
                                               value="{{ $key }}"
                                               id="tractor_{{ $key }}"
                                               class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary tractor-checkbox"
                                               {{ in_array($key, old('tractor', [])) ? 'checked' : '' }}>
                                        <span class="text-sm text-slate-800">{{ $tractorItems[$key] }}</span>
                                    </label>
                                    @if($key === 'other_tractor')
                                        <div id="other_tractor_container" class="ml-7 hidden">
                                            <input type="text"
                                                   name="other_tractor"
                                                   id="other_tractor_input"
                                                   value="{{ old('other_tractor') }}"
                                                   placeholder="Please specify..."
                                                   class="w-full form-control rounded-lg border-slate-200 text-sm">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <!-- Select All Button -->
                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <button type="button" id="selectAllTractor" class="text-sm text-primary hover:text-primary/80 font-medium">
                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="CheckSquare" />
                            Select All Tractor Items
                        </button>
                    </div>
                </div>

                <!-- Trailer Inspection Checklist (Conditional) -->
                <div id="trailer-section" class="box box--stacked flex flex-col p-6 mb-6 hidden">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Container" />
                        <h3 class="text-md font-semibold text-slate-800">Trailer Inspection</h3>
                        <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded">All items required</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($trailerColumns as $colNum => $items)
                            <div class="space-y-2">
                                @foreach($items as $key)
                                    <label class="flex items-center gap-3 p-3 bg-slate-50/50 rounded-lg border border-slate-200 hover:bg-slate-100/50 transition-colors cursor-pointer">
                                        <input type="checkbox"
                                               name="trailer[]"
                                               value="{{ $key }}"
                                               id="trailer_{{ $key }}"
                                               class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary trailer-checkbox"
                                               {{ in_array($key, old('trailer', [])) ? 'checked' : '' }}>
                                        <span class="text-sm text-slate-800">{{ $trailerItems[$key] }}</span>
                                    </label>
                                    @if($key === 'other_trailer')
                                        <div id="other_trailer_container" class="ml-7 hidden">
                                            <input type="text"
                                                   name="other_trailer"
                                                   id="other_trailer_input"
                                                   value="{{ old('other_trailer') }}"
                                                   placeholder="Please specify..."
                                                   class="w-full form-control rounded-lg border-slate-200 text-sm">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <!-- Select All Button -->
                    <div class="mt-4 pt-4 border-t border-slate-200">
                        <button type="button" id="selectAllTrailer" class="text-sm text-primary hover:text-primary/80 font-medium">
                            <x-base.lucide class="w-4 h-4 inline mr-1" icon="CheckSquare" />
                            Select All Trailer Items
                        </button>
                    </div>
                </div>

                <!-- Condition & Remarks -->
                <div class="box box--stacked flex flex-col p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="ClipboardCheck" />
                        <h3 class="text-md font-semibold text-slate-800">Condition Certification & Remarks</h3>
                    </div>

                    <div class="space-y-4">
                        <!-- Remarks/Notes -->
                        <div>
                            <x-base.form-label for="remarks">Remarks / Defects Found (optional)</x-base.form-label>
                            <x-base.form-textarea
                                id="remarks"
                                name="remarks"
                                rows="3"
                                placeholder="Describe any defects, issues, or notes about the vehicle condition...">{{ old('remarks') }}</x-base.form-textarea>
                            <p class="text-xs text-slate-500 mt-1">If defects are found, describe them here.</p>
                        </div>

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
                        </div>

                        <!-- Defects Corrected -->
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="defects_corrected" value="1" id="defects_corrected"
                                       class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary"
                                       {{ old('defects_corrected') ? 'checked' : '' }}>
                                <span class="text-sm font-semibold text-slate-800">
                                    Above Defects Corrected
                                </span>
                            </label>
                            <div id="defects_corrected_notes_container" class="mt-3 ml-8 {{ old('defects_corrected') ? '' : 'hidden' }}">
                                <x-base.form-textarea
                                    id="defects_corrected_notes"
                                    name="defects_corrected_notes"
                                    rows="2"
                                    placeholder="Describe the corrections made...">{{ old('defects_corrected_notes') }}</x-base.form-textarea>
                            </div>
                        </div>

                        <!-- Defects Not Need Correction -->
                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="defects_not_need_correction" value="1" id="defects_not_need_correction"
                                       class="w-5 h-5 text-primary border-slate-300 rounded focus:ring-primary"
                                       {{ old('defects_not_need_correction') ? 'checked' : '' }}>
                                <span class="text-sm font-semibold text-slate-800">
                                    Above Defects Need NOT Be Corrected For Safe Operation Of Vehicle
                                </span>
                            </label>
                            <div id="defects_not_need_correction_notes_container" class="mt-3 ml-8 {{ old('defects_not_need_correction') ? '' : 'hidden' }}">
                                <x-base.form-textarea
                                    id="defects_not_need_correction_notes"
                                    name="defects_not_need_correction_notes"
                                    rows="2"
                                    placeholder="Explain why defects don't affect safe operation...">{{ old('defects_not_need_correction_notes') }}</x-base.form-textarea>
                            </div>
                        </div>
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
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">From</label>
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                                <p class="text-sm font-semibold text-slate-800">{{ $trip->origin_address }}</p>
                            </div>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">To</label>
                            <div class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5" icon="MapPin" />
                                <p class="text-sm font-semibold text-slate-800">{{ $trip->destination_address }}</p>
                            </div>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Vehicle</label>
                            <div class="flex items-center gap-2">
                                <x-base.lucide class="w-4 h-4 text-slate-400" icon="Truck" />
                                <p class="text-sm font-semibold text-slate-800">{{ $trip->vehicle->company_unit_number ?? ($trip->vehicle->make . ' ' . $trip->vehicle->model ?? 'N/A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <x-base.button as="a" href="{{ route('driver.trips.show', $trip) }}" variant="secondary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="X" />
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="primary" class="flex-1 gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Play" />
                        Start Trip
                    </x-base.button>
                </div>
            </form>
        </div>

        <!-- Sidebar - FMCSA Status -->
        <div class="col-span-12 lg:col-span-4">
            <div class="box box--stacked flex flex-col p-6 bg-success/5 border-l-4 border-success">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-success/10 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                    </div>
                    <h2 class="text-lg font-semibold text-slate-800">FMCSA Status: OK</h2>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <x-base.lucide class="w-5 h-5 text-success flex-shrink-0 mt-0.5" icon="Check" />
                        <div>
                            <div class="font-medium text-slate-800 text-sm">10-hour reset completed</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <x-base.lucide class="w-5 h-5 text-success flex-shrink-0 mt-0.5" icon="Check" />
                        <div>
                            <div class="font-medium text-slate-800 text-sm">
                                Weekly hours available: {{ round($validation['weekly_status']['hours_remaining'], 1) }}h
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <x-base.lucide class="w-5 h-5 text-success flex-shrink-0 mt-0.5" icon="Check" />
                        <div>
                            <div class="font-medium text-slate-800 text-sm">No active penalties</div>
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
                            <span id="tractorProgress" class="font-medium text-slate-800">0/{{ count($tractorItems) }}</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div id="tractorProgressBar" class="bg-primary h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                    <div id="trailerProgressContainer" class="hidden">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600">Trailer Items</span>
                            <span id="trailerProgress" class="font-medium text-slate-800">0/{{ count($trailerItems) }}</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div id="trailerProgressBar" class="bg-primary h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hasTrailerCheckbox = document.getElementById('has_trailer');
    const trailerSection = document.getElementById('trailer-section');
    const trailerProgressContainer = document.getElementById('trailerProgressContainer');

    const tractorCheckboxes = document.querySelectorAll('.tractor-checkbox');
    const trailerCheckboxes = document.querySelectorAll('.trailer-checkbox');

    // "Other" items are OPTIONAL - don't count them in required totals
    const tractorTotal = {{ count($tractorItems) }} - 1; // Exclude "other_tractor"
    const trailerTotal = {{ count($trailerItems) }} - 1; // Exclude "other_trailer"

    // Function to count checked items EXCLUDING "Other"
    function getCheckedCount(type) {
        const checkboxes = document.querySelectorAll('.' + type + '-checkbox:checked');
        let count = 0;
        checkboxes.forEach(cb => {
            if (cb.value !== 'other_' + type) {
                count++;
            }
        });
        return count;
    }

    // Toggle trailer section
    function toggleTrailerSection() {
        if (hasTrailerCheckbox.checked) {
            trailerSection.classList.remove('hidden');
            trailerProgressContainer.classList.remove('hidden');
        } else {
            trailerSection.classList.add('hidden');
            trailerProgressContainer.classList.add('hidden');
            // Uncheck all trailer checkboxes
            trailerCheckboxes.forEach(cb => cb.checked = false);
            updateProgress();
        }
    }

    hasTrailerCheckbox.addEventListener('change', toggleTrailerSection);

    // Initialize on page load (for old values)
    if (hasTrailerCheckbox.checked) {
        toggleTrailerSection();
    }

    // Update progress bars (excluding "Other" from count)
    function updateProgress() {
        const tractorChecked = getCheckedCount('tractor');
        const trailerChecked = getCheckedCount('trailer');

        document.getElementById('tractorProgress').textContent = `${tractorChecked}/${tractorTotal}`;
        document.getElementById('tractorProgressBar').style.width = `${(tractorChecked / tractorTotal) * 100}%`;

        document.getElementById('trailerProgress').textContent = `${trailerChecked}/${trailerTotal}`;
        document.getElementById('trailerProgressBar').style.width = `${(trailerChecked / trailerTotal) * 100}%`;
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

    document.getElementById('selectAllTrailer').addEventListener('click', function() {
        trailerCheckboxes.forEach(cb => {
            // Don't select "Other" checkbox
            if (cb.value !== 'other_trailer') {
                cb.checked = true;
            }
        });
        updateProgress();
    });

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
        // Initialize
        if (otherTractorCheckbox.checked) {
            toggleOtherInput('tractor');
        }
    }

    const otherTrailerCheckbox = document.getElementById('trailer_other_trailer');
    if (otherTrailerCheckbox) {
        otherTrailerCheckbox.addEventListener('change', function() {
            toggleOtherInput('trailer');
        });
        // Initialize
        if (otherTrailerCheckbox.checked) {
            toggleOtherInput('trailer');
        }
    }

    // Initialize progress on page load
    updateProgress();

    // Toggle defects correction notes visibility
    const defectsCorrectedCheckbox = document.getElementById('defects_corrected');
    const defectsCorrectedNotesContainer = document.getElementById('defects_corrected_notes_container');

    if (defectsCorrectedCheckbox && defectsCorrectedNotesContainer) {
        defectsCorrectedCheckbox.addEventListener('change', function() {
            if (this.checked) {
                defectsCorrectedNotesContainer.classList.remove('hidden');
            } else {
                defectsCorrectedNotesContainer.classList.add('hidden');
            }
        });
    }

    // Toggle defects not need correction notes visibility
    const defectsNotNeedCorrectionCheckbox = document.getElementById('defects_not_need_correction');
    const defectsNotNeedCorrectionNotesContainer = document.getElementById('defects_not_need_correction_notes_container');

    if (defectsNotNeedCorrectionCheckbox && defectsNotNeedCorrectionNotesContainer) {
        defectsNotNeedCorrectionCheckbox.addEventListener('change', function() {
            if (this.checked) {
                defectsNotNeedCorrectionNotesContainer.classList.remove('hidden');
            } else {
                defectsNotNeedCorrectionNotesContainer.classList.add('hidden');
            }
        });
    }
});
</script>
@endpush
