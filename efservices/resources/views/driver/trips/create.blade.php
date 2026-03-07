@extends('../themes/' . $activeTheme)
@section('title', 'Create New Trip')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'My Trips', 'url' => route('driver.trips.index')],
        ['label' => 'Create Trip', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-success/10 rounded-xl border border-success/20">
                <x-base.lucide class="w-8 h-8 text-success" icon="Plus" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Create New Trip</h1>
                <p class="text-slate-600">Schedule your own trip or start a quick trip immediately</p>
            </div>
        </div>
        <x-base.button as="a" href="{{ route('driver.trips.index') }}" variant="secondary" class="gap-2">
            <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
            Back to Trips
        </x-base.button>
    </div>
</div>

<!-- HOS Status Summary -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Available Hours Today -->
    <div class="box box--stacked p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-primary/10 rounded-lg">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Clock" />
            </div>
            <h3 class="font-semibold text-slate-800">Available Today</h3>
        </div>
        <div class="text-3xl font-bold text-primary mb-1">
            {{ number_format($fmcsaStatus['driving_remaining_hours'] ?? 12, 1) }}h
        </div>
        <p class="text-sm text-slate-500">Driving hours remaining</p>
    </div>

    <!-- Weekly Cycle Status -->
    <div class="box box--stacked p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-{{ $weeklyCycleStatus['status_color'] === 'green' ? 'success' : ($weeklyCycleStatus['status_color'] === 'yellow' ? 'warning' : 'danger') }}/10 rounded-lg">
                <x-base.lucide class="w-5 h-5 text-{{ $weeklyCycleStatus['status_color'] === 'green' ? 'success' : ($weeklyCycleStatus['status_color'] === 'yellow' ? 'warning' : 'danger') }}" icon="BarChart3" />
            </div>
            <h3 class="font-semibold text-slate-800">Weekly Cycle</h3>
        </div>
        <div class="text-3xl font-bold text-slate-800 mb-1">
            {{ number_format($weeklyCycleStatus['hours_remaining'], 1) }}h
        </div>
        <p class="text-sm text-slate-500">of {{ $weeklyCycleStatus['hours_limit'] }}h remaining ({{ $weeklyCycleStatus['cycle_type_name'] }})</p>
        <div class="mt-3">
            <div class="w-full bg-slate-200 rounded-full h-2">
                <div class="bg-{{ $weeklyCycleStatus['status_color'] === 'green' ? 'success' : ($weeklyCycleStatus['status_color'] === 'yellow' ? 'warning' : 'danger') }} h-2 rounded-full" style="width: {{ min($weeklyCycleStatus['percentage_used'], 100) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Carrier Info -->
    <div class="box box--stacked p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-slate-100 rounded-lg">
                <x-base.lucide class="w-5 h-5 text-slate-600" icon="Building2" />
            </div>
            <h3 class="font-semibold text-slate-800">Carrier</h3>
        </div>
        <div class="text-xl font-bold text-slate-800 mb-1">
            {{ $carrier->name ?? 'N/A' }}
        </div>
        <p class="text-sm text-slate-500">Your assigned carrier</p>
    </div>
</div>

<!-- FMCSA Warnings -->
@if($weeklyCycleStatus['is_over_limit'])
    <div class="box box--stacked mb-6 border-l-4 border-danger">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-danger/10 flex items-center justify-center">
                        <x-base.lucide class="w-5 h-5 text-danger" icon="AlertTriangle" />
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-danger mb-1">HOS Limit Exceeded</h3>
                    <p class="text-slate-600">You have exceeded your weekly hour limit. You must take a 34-hour rest period before creating new trips.</p>
                </div>
            </div>
        </div>
    </div>
@elseif($weeklyCycleStatus['is_approaching_limit'])
    <div class="box box--stacked mb-6 border-l-4 border-warning">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-warning/10 flex items-center justify-center">
                        <x-base.lucide class="w-5 h-5 text-warning" icon="AlertCircle" />
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-warning mb-1">Approaching Weekly Limit</h3>
                    <p class="text-slate-600">You are at {{ number_format($weeklyCycleStatus['percentage_used'], 0) }}% of your weekly hours. Plan your trips carefully.</p>
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
                    <div class="w-10 h-10 rounded-full bg-danger/10 flex items-center justify-center">
                        <x-base.lucide class="w-5 h-5 text-danger" icon="XCircle" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-danger mb-2">Please correct the following errors:</h3>
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <x-base.lucide class="w-4 h-4 text-danger mt-0.5 flex-shrink-0" icon="Minus" />
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Trip Creation Tabs -->
<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 lg:col-span-8">
        <!-- Tab Navigation -->
        <div class="box box--stacked">
            <div class="border-b border-slate-200">
                <nav class="flex -mb-px" aria-label="Tabs">
                    <button type="button"
                            id="quickTripTab"
                            onclick="switchTab('quick')"
                            class="tab-btn active w-1/2 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-primary text-primary bg-primary/5">
                        <div class="flex items-center justify-center gap-2">
                            <x-base.lucide class="w-5 h-5" icon="Zap" />
                            <span>Quick Trip</span>
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-warning text-white">Fast</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Start immediately with minimal info</p>
                    </button>
                    <button type="button"
                            id="fullTripTab"
                            onclick="switchTab('full')"
                            class="tab-btn w-1/2 py-4 px-6 text-center border-b-2 font-medium text-sm transition-colors border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300">
                        <div class="flex items-center justify-center gap-2">
                            <x-base.lucide class="w-5 h-5" icon="FileText" />
                            <span>Full Trip</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Complete trip with all details</p>
                    </button>
                </nav>
            </div>

            <!-- Quick Trip Form -->
            <div id="quickTripContent" class="tab-content p-6">
                <form action="{{ route('driver.trips.quick-store') }}" method="POST" id="quickTripForm">
                    @csrf

                    <!-- Quick Trip Alert -->
                    <div class="mb-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
                        <div class="flex items-start gap-3">
                            <x-base.lucide class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" icon="AlertCircle" />
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-1">Quick Trip Mode</h4>
                                <p class="text-sm text-slate-600">
                                    Create a trip with just a vehicle selection. Your carrier will need to complete the origin/destination information later.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Selection (Required) -->
                    <div class="mb-6">
                        <x-base.form-label for="quick_vehicle_id" class="flex items-center gap-2">
                            Vehicle <span class="text-danger">*</span>
                            <span class="text-xs text-slate-500">(Required)</span>
                        </x-base.form-label>
                        <select name="vehicle_id" id="quick_vehicle_id" class="w-full form-select rounded-lg border-slate-200 text-lg py-3" required>
                            <option value="">Select your vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->make }} {{ $vehicle->model }}
                                    @if($vehicle->company_unit_number) ({{ $vehicle->company_unit_number }}) @endif
                                    - {{ $vehicle->license_plate ?? 'No plate' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Optional Fields (Collapsed by default) -->
                    <div class="mb-6">
                        <button type="button" onclick="toggleOptionalFields()" class="flex items-center gap-2 text-sm text-primary hover:text-primary/80">
                            <x-base.lucide class="w-4 h-4 transition-transform" id="optionalFieldsIcon" icon="ChevronDown" />
                            <span id="optionalFieldsText">Show optional fields</span>
                        </button>

                        <div id="optionalFieldsContainer" class="hidden mt-4 space-y-4 p-4 bg-slate-50 rounded-lg">
                            <p class="text-xs text-slate-500 mb-4">These fields are optional but help your carrier manage the trip better.</p>

                            <!-- Origin Address (Optional) -->
                            <div>
                                <x-base.form-label for="quick_origin_address">Origin Address (Optional)</x-base.form-label>
                                <x-base.form-input
                                    type="text"
                                    name="origin_address"
                                    id="quick_origin_address"
                                    placeholder="Where are you starting from?"
                                    value="{{ old('origin_address') }}" />
                            </div>

                            <!-- Destination Address (Optional) -->
                            <div>
                                <x-base.form-label for="quick_destination_address">Destination Address (Optional)</x-base.form-label>
                                <x-base.form-input
                                    type="text"
                                    name="destination_address"
                                    id="quick_destination_address"
                                    placeholder="Where are you going?"
                                    value="{{ old('destination_address') }}" />
                            </div>

                            <!-- Notes (Optional) -->
                            <div>
                                <x-base.form-label for="quick_notes">Notes (Optional)</x-base.form-label>
                                <x-base.form-textarea
                                    id="quick_notes"
                                    name="notes"
                                    rows="2"
                                    placeholder="Any notes for your carrier...">{{ old('notes') }}</x-base.form-textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <x-base.button as="a" href="{{ route('driver.trips.index') }}" variant="secondary" class="gap-2">
                            <x-base.lucide class="w-4 h-4" icon="X" />
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="warning" class="flex-1 gap-2 text-lg py-3" :disabled="$weeklyCycleStatus['is_over_limit']">
                            <x-base.lucide class="w-5 h-5" icon="Zap" />
                            Create Quick Trip
                        </x-base.button>
                    </div>
                </form>
            </div>

            <!-- Full Trip Form -->
            <div id="fullTripContent" class="tab-content hidden p-6">
                <form action="{{ route('driver.trips.store') }}" method="POST" id="fullTripForm">
                    @csrf

                    <!-- Trip Details -->
                    <div class="space-y-4 mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="MapPin" />
                            <h2 class="text-lg font-semibold text-slate-800">Trip Details</h2>
                        </div>

                        <!-- Vehicle Selection -->
                        <div>
                            <x-base.form-label for="vehicle_id">Vehicle *</x-base.form-label>
                            <select name="vehicle_id" id="vehicle_id" class="w-full form-select rounded-lg border-slate-200" required>
                                <option value="">Select a vehicle</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->make }} {{ $vehicle->model }}
                                        @if($vehicle->company_unit_number) ({{ $vehicle->company_unit_number }}) @endif
                                        - {{ $vehicle->license_plate ?? 'No plate' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Select the vehicle you will use for this trip</p>
                        </div>

                        <!-- Origin Address -->
                        <div>
                            <x-base.form-label for="origin_address">Origin Address *</x-base.form-label>
                            <x-base.form-input
                                type="text"
                                name="origin_address"
                                id="origin_address"
                                placeholder="Enter pickup location..."
                                value="{{ old('origin_address') }}"
                                required />
                            <p class="text-xs text-slate-500 mt-1">Where you'll pick up or start from</p>
                        </div>

                        <!-- Destination Address -->
                        <div>
                            <x-base.form-label for="destination_address">Destination Address *</x-base.form-label>
                            <x-base.form-input
                                type="text"
                                name="destination_address"
                                id="destination_address"
                                placeholder="Enter delivery location..."
                                value="{{ old('destination_address') }}"
                                required />
                            <p class="text-xs text-slate-500 mt-1">Where you'll deliver or end the trip</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Scheduled Start Date -->
                            <div>
                                <x-base.form-label for="scheduled_start_date">Planned Start Date & Time *</x-base.form-label>
                                <x-base.form-input
                                    type="datetime-local"
                                    name="scheduled_start_date"
                                    id="scheduled_start_date"
                                    value="{{ old('scheduled_start_date', now()->format('Y-m-d\TH:i')) }}"
                                    min="{{ now()->format('Y-m-d\TH:i') }}"
                                    required />
                                <p class="text-xs text-slate-500 mt-1">When you plan to start</p>
                            </div>

                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="space-y-4 mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                            <h2 class="text-lg font-semibold text-slate-800">Additional Information</h2>
                            <span class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded">Optional</span>
                        </div>

                        <!-- Description -->
                        <div>
                            <x-base.form-label for="description">Trip Description</x-base.form-label>
                            <x-base.form-textarea
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Brief description of the trip, load information, etc...">{{ old('description') }}</x-base.form-textarea>
                        </div>

                        <!-- Notes -->
                        <div>
                            <x-base.form-label for="notes">Notes</x-base.form-label>
                            <x-base.form-textarea
                                id="notes"
                                name="notes"
                                rows="2"
                                placeholder="Any additional notes or instructions...">{{ old('notes') }}</x-base.form-textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <x-base.button as="a" href="{{ route('driver.trips.index') }}" variant="secondary" class="gap-2">
                            <x-base.lucide class="w-4 h-4" icon="X" />
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="success" class="flex-1 gap-2" :disabled="$weeklyCycleStatus['is_over_limit']">
                            <x-base.lucide class="w-4 h-4" icon="Plus" />
                            Create Trip
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-span-12 lg:col-span-4">
        <!-- Quick Trip Info -->
        <div id="quickTripInfo" class="box box--stacked flex flex-col p-6 bg-warning/5 border-l-4 border-warning">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-warning" icon="Zap" />
                </div>
                <h2 class="text-lg font-semibold text-slate-800">Quick Trip</h2>
            </div>

            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-6 h-6 rounded-full bg-warning/20 flex items-center justify-center text-xs font-bold text-warning">1</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800 text-sm mb-1">Select your vehicle</div>
                        <p class="text-xs text-slate-500">Just pick the vehicle you're driving</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-6 h-6 rounded-full bg-warning/20 flex items-center justify-center text-xs font-bold text-warning">2</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800 text-sm mb-1">Start immediately</div>
                        <p class="text-xs text-slate-500">Your trip is ready to start right away</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-6 h-6 rounded-full bg-warning/20 flex items-center justify-center text-xs font-bold text-warning">3</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800 text-sm mb-1">Carrier completes info</div>
                        <p class="text-xs text-slate-500">Your carrier will add the destination details later</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 p-3 bg-white rounded-lg border border-warning/20">
                <p class="text-xs text-slate-600">
                    <strong>Best for:</strong> Urgent trips when you need to start driving immediately and don't have all the trip details yet.
                </p>
            </div>
        </div>

        <!-- Full Trip Info -->
        <div id="fullTripInfo" class="hidden box box--stacked flex flex-col p-6 bg-primary/5 border-l-4 border-primary">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                </div>
                <h2 class="text-lg font-semibold text-slate-800">How it works</h2>
            </div>

            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs font-bold text-primary">1</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800 text-sm mb-1">Create your trip</div>
                        <p class="text-xs text-slate-500">Fill in the origin, destination, and schedule</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs font-bold text-primary">2</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800 text-sm mb-1">Trip is ready to start</div>
                        <p class="text-xs text-slate-500">Your trip will be created in "Accepted" status</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs font-bold text-primary">3</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800 text-sm mb-1">Start when you're ready</div>
                        <p class="text-xs text-slate-500">You can start the trip at your convenience</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-6 h-6 rounded-full bg-success/20 flex items-center justify-center text-xs font-bold text-success">4</div>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800 text-sm mb-1">Complete pre-trip inspection</div>
                        <p class="text-xs text-slate-500">Before starting, you'll complete the DVIR inspection</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- HOS Tips -->
        <div class="box box--stacked flex flex-col p-6 mt-6">
            <div class="flex items-center gap-3 mb-4">
                <x-base.lucide class="w-5 h-5 text-warning" icon="Lightbulb" />
                <h3 class="font-semibold text-slate-800">HOS Tips</h3>
            </div>
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" icon="Check" />
                    Max 12 hours driving per day
                </li>
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" icon="Check" />
                    Max 14 hours on-duty per day
                </li>
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" icon="Check" />
                    8 hours off-duty resets daily limits
                </li>
                <li class="flex items-start gap-2">
                    <x-base.lucide class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" icon="Check" />
                    34 hours off-duty resets weekly limits
                </li>
            </ul>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        const quickTab = document.getElementById('quickTripTab');
        const fullTab = document.getElementById('fullTripTab');
        const quickContent = document.getElementById('quickTripContent');
        const fullContent = document.getElementById('fullTripContent');
        const quickInfo = document.getElementById('quickTripInfo');
        const fullInfo = document.getElementById('fullTripInfo');

        if (tab === 'quick') {
            // Activate quick tab
            quickTab.classList.add('border-primary', 'text-primary', 'bg-primary/5');
            quickTab.classList.remove('border-transparent', 'text-slate-500');
            fullTab.classList.remove('border-primary', 'text-primary', 'bg-primary/5');
            fullTab.classList.add('border-transparent', 'text-slate-500');

            // Show quick content
            quickContent.classList.remove('hidden');
            fullContent.classList.add('hidden');

            // Show quick info
            quickInfo.classList.remove('hidden');
            fullInfo.classList.add('hidden');
        } else {
            // Activate full tab
            fullTab.classList.add('border-primary', 'text-primary', 'bg-primary/5');
            fullTab.classList.remove('border-transparent', 'text-slate-500');
            quickTab.classList.remove('border-primary', 'text-primary', 'bg-primary/5');
            quickTab.classList.add('border-transparent', 'text-slate-500');

            // Show full content
            fullContent.classList.remove('hidden');
            quickContent.classList.add('hidden');

            // Show full info
            fullInfo.classList.remove('hidden');
            quickInfo.classList.add('hidden');
        }
    }

    function toggleOptionalFields() {
        const container = document.getElementById('optionalFieldsContainer');
        const icon = document.getElementById('optionalFieldsIcon');
        const text = document.getElementById('optionalFieldsText');

        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
            text.textContent = 'Hide optional fields';
        } else {
            container.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
            text.textContent = 'Show optional fields';
        }
    }
</script>
@endpush
