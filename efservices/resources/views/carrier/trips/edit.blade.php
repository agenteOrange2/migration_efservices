@extends('../themes/' . $activeTheme)
@section('title', 'Edit Trip - ' . $trip->trip_number)

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Trips', 'url' => route('carrier.trips.index')],
        ['label' => 'Trip Details', 'url' => route('carrier.trips.show', $trip)],
        ['label' => 'Edit Trip', 'active' => true],
    ];

    // Check if this is a quick trip that's in progress/completed (limited editing)
    $isLimitedEdit = $trip->isQuickTrip() && ($trip->isInProgress() || $trip->isCompleted() || $trip->isPaused());
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Flash Messages -->
@if(session('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
        {{ session('success') }}
    </div>
@endif

<!-- Quick Trip Alert -->
@if($trip->isQuickTrip() && $trip->needsCompletion())
    <div class="box box--stacked mb-6 border-l-4 border-warning">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-warning/10 flex items-center justify-center">
                        <x-base.lucide class="w-5 h-5 text-warning" icon="AlertTriangle" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-warning mb-1">Quick Trip - Information Required</h3>
                    <p class="text-slate-600 mb-3">This trip was created as a Quick Trip by the driver. Please complete the missing information:</p>
                    @php $missingFields = $trip->getMissingFields(); @endphp
                    @if(!empty($missingFields))
                        <ul class="space-y-1">
                            @foreach($missingFields as $field => $label)
                                <li class="flex items-center gap-2 text-sm text-slate-700">
                                    <x-base.lucide class="w-4 h-4 text-warning" icon="AlertCircle" />
                                    {{ $label }} is required
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@elseif($trip->isQuickTrip() && !$trip->needsCompletion())
    <div class="box box--stacked mb-6 border-l-4 border-success">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center">
                        <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-success mb-1">Quick Trip - Information Complete</h3>
                    <p class="text-slate-600">All required information has been provided for this quick trip.</p>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Limited Edit Notice for In-Progress/Completed Trips -->
@if($isLimitedEdit)
    <div class="box box--stacked mb-6 border-l-4 border-info">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full bg-info/10 flex items-center justify-center">
                        <x-base.lucide class="w-5 h-5 text-info" icon="Info" />
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-info mb-1">Limited Editing Mode</h3>
                    <p class="text-slate-600">
                        This trip is {{ $trip->status_name }}. You can only update the route information and details.
                        Driver and vehicle assignment cannot be changed.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Edit" />
            </div>
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold text-slate-800">Edit Trip: {{ $trip->trip_number }}</h1>
                    @if($trip->isQuickTrip())
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-{{ $trip->quick_trip_status_color }}/10 text-{{ $trip->quick_trip_status_color }}">
                            <x-base.lucide class="w-3 h-3 inline mr-1" icon="Zap" />
                            Quick Trip {{ $trip->requires_completion ? '- Needs Info' : '- Complete' }}
                        </span>
                    @endif
                </div>
                <p class="text-slate-600">
                    @if($trip->isQuickTrip() && $trip->needsCompletion())
                        Complete the trip information for this quick trip
                    @else
                        Update trip information and details
                    @endif
                </p>
            </div>
        </div>
        <x-base.button as="a" href="{{ route('carrier.trips.show', $trip) }}" variant="secondary" class="gap-2">
            <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
            Back to Trip
        </x-base.button>
    </div>
</div>

<form action="{{ route('carrier.trips.update', $trip) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-12 gap-6">
        <!-- Main Content -->
        <div class="col-span-12 lg:col-span-8 space-y-6">
            <!-- Route Information -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="MapPin" />
                    <h2 class="text-lg font-semibold text-slate-800">Route Information</h2>
                    @if($trip->isQuickTrip() && $trip->needsCompletion())
                        <span class="text-xs text-warning bg-warning/10 px-2 py-1 rounded">Required for Quick Trip</span>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-base.form-label for="origin_address" class="flex items-center gap-2">
                            Origin Address
                            <span class="text-danger">*</span>
                            @if($trip->isQuickTrip() && empty($trip->origin_address))
                                <span class="text-xs text-warning">(Missing)</span>
                            @endif
                        </x-base.form-label>
                        <x-base.form-textarea id="origin_address" name="origin_address" rows="3" required
                            placeholder="Enter pickup address"
                            class="{{ $trip->isQuickTrip() && empty($trip->origin_address) ? 'border-warning focus:border-warning' : '' }}">{{ old('origin_address', $trip->origin_address) }}</x-base.form-textarea>
                        @error('origin_address')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-base.form-label for="destination_address" class="flex items-center gap-2">
                            Destination Address
                            <span class="text-danger">*</span>
                            @if($trip->isQuickTrip() && empty($trip->destination_address))
                                <span class="text-xs text-warning">(Missing)</span>
                            @endif
                        </x-base.form-label>
                        <x-base.form-textarea id="destination_address" name="destination_address" rows="3" required
                            placeholder="Enter delivery address"
                            class="{{ $trip->isQuickTrip() && empty($trip->destination_address) ? 'border-warning focus:border-warning' : '' }}">{{ old('destination_address', $trip->destination_address) }}</x-base.form-textarea>
                        @error('destination_address')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Schedule (Only for non-limited edit) -->
            @if(!$isLimitedEdit)
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Calendar" />
                        <h2 class="text-lg font-semibold text-slate-800">Schedule</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <x-base.form-label for="scheduled_start_date">Scheduled Start <span class="text-danger">*</span></x-base.form-label>
                            <x-base.form-input type="datetime-local" id="scheduled_start_date" name="scheduled_start_date"
                                    value="{{ old('scheduled_start_date', $trip->scheduled_start_date?->format('Y-m-d\TH:i')) }}" required />
                            @error('scheduled_start_date')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <x-base.form-label for="scheduled_end_date">Scheduled End</x-base.form-label>
                                <x-base.form-input type="datetime-local" id="scheduled_end_date" name="scheduled_end_date"
                                    value="{{ old('scheduled_end_date', $trip->scheduled_end_date?->format('Y-m-d\TH:i')) }}" />
                            @error('scheduled_end_date')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <x-base.form-label for="estimated_duration_minutes">Estimated Duration</x-base.form-label>
                            <select name="estimated_duration_minutes" id="estimated_duration_minutes" class="w-full form-select rounded-lg border-slate-200">
                                <option value="">Select duration</option>
                                <option value="30" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '30' ? 'selected' : '' }}>30 minutes</option>
                                <option value="60" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '60' ? 'selected' : '' }}>1 hour</option>
                                <option value="120" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '120' ? 'selected' : '' }}>2 hours</option>
                                <option value="180" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '180' ? 'selected' : '' }}>3 hours</option>
                                <option value="240" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '240' ? 'selected' : '' }}>4 hours</option>
                                <option value="300" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '300' ? 'selected' : '' }}>5 hours</option>
                                <option value="360" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '360' ? 'selected' : '' }}>6 hours</option>
                                <option value="480" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '480' ? 'selected' : '' }}>8 hours</option>
                                <option value="600" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '600' ? 'selected' : '' }}>10 hours</option>
                                <option value="720" {{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) == '720' ? 'selected' : '' }}>12 hours</option>
                            </select>
                            @error('estimated_duration_minutes')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            @endif

            <!-- Load Information -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Package" />
                    <h2 class="text-lg font-semibold text-slate-800">Load Information</h2>
                    <span class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded">Optional</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-base.form-label for="load_type">Load Type</x-base.form-label>
                        <x-base.form-input type="text" id="load_type" name="load_type"
                            value="{{ old('load_type', $trip->load_type) }}"
                            placeholder="e.g., General Freight, Refrigerated, Hazmat" />
                        @error('load_type')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-base.form-label for="load_weight">Load Weight (lbs)</x-base.form-label>
                        <x-base.form-input type="number" id="load_weight" name="load_weight"
                            value="{{ old('load_weight', $trip->load_weight) }}"
                            min="0" step="0.01" placeholder="e.g., 45000" />
                        @error('load_weight')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
                    <h2 class="text-lg font-semibold text-slate-800">Additional Information</h2>
                </div>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <x-base.form-label for="description">Description</x-base.form-label>
                        <x-base.form-textarea id="description" name="description" rows="3"
                            placeholder="Trip description...">{{ old('description', $trip->description) }}</x-base.form-textarea>
                    </div>
                    <div>
                        <x-base.form-label for="notes">Internal Notes</x-base.form-label>
                        <x-base.form-textarea id="notes" name="notes" rows="3"
                            placeholder="Internal notes for this trip...">{{ old('notes', $trip->notes) }}</x-base.form-textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <!-- Trip Status Info -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-4">
                    <x-base.lucide class="w-5 h-5 text-slate-600" icon="Info" />
                    <h2 class="text-lg font-semibold text-slate-800">Trip Status</h2>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Status:</span>
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-{{ $trip->status_color }}/10 text-{{ $trip->status_color }}">
                            {{ $trip->status_name }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Trip Number:</span>
                        <span class="text-sm font-medium text-slate-800">{{ $trip->trip_number }}</span>
                    </div>
                    @if($trip->isQuickTrip())
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Type:</span>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                <x-base.lucide class="w-3 h-3 inline mr-1" icon="Zap" />
                                Quick Trip
                            </span>
                        </div>
                    @endif
                    @if($trip->created_at)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Created:</span>
                            <span class="text-sm text-slate-800">{{ $trip->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Assignment (Only for non-limited edit) -->
            @if(!$isLimitedEdit)
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="UserCheck" />
                        <h2 class="text-lg font-semibold text-slate-800">Assignment</h2>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <x-base.form-label for="driver_id">Driver <span class="text-danger">*</span></x-base.form-label>
                            <select id="driver_id" name="driver_id" class="w-full form-select rounded-lg border-slate-200" required>
                                <option value="">Select Driver</option>
                                @foreach($drivers ?? [] as $driver)
                                    <option value="{{ $driver->id }}" {{ old('driver_id', $trip->user_driver_detail_id) == $driver->id ? 'selected' : '' }}>
                                        {{ implode(' ', array_filter([$driver->user->name ?? 'Unknown', $driver->middle_name ?? '', $driver->last_name ?? ''])) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('driver_id')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <x-base.form-label for="vehicle_id">Vehicle <span class="text-danger">*</span></x-base.form-label>
                            <select id="vehicle_id" name="vehicle_id" class="w-full form-select rounded-lg border-slate-200" required>
                                <option value="">Select Vehicle</option>
                                @foreach($vehicles ?? [] as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $trip->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->company_unit_number ?? $vehicle->unit_number ?? 'N/A' }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')
                                <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            @else
                <!-- Current Assignment (Read-only) -->
                <div class="box box--stacked flex flex-col p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <x-base.lucide class="w-5 h-5 text-slate-600" icon="UserCheck" />
                        <h2 class="text-lg font-semibold text-slate-800">Assignment</h2>
                        <span class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded">Read-only</span>
                    </div>
                    <div class="space-y-4">
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <div class="text-xs text-slate-500 mb-1">Driver</div>
                            <div class="font-medium text-slate-800">
                                {{ $trip->driver->user->name ?? 'Unknown' }}
                                {{ $trip->driver->middle_name ?? '' }}
                                {{ $trip->driver->last_name ?? '' }}
                            </div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <div class="text-xs text-slate-500 mb-1">Vehicle</div>
                            <div class="font-medium text-slate-800">
                                {{ $trip->vehicle->company_unit_number ?? $trip->vehicle->unit_number ?? 'N/A' }}
                                - {{ $trip->vehicle->make ?? '' }} {{ $trip->vehicle->model ?? '' }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Submit Actions -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex flex-col gap-3">
                    <x-base.button type="submit" variant="{{ $trip->isQuickTrip() && $trip->needsCompletion() ? 'warning' : 'primary' }}" class="w-full gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Check" />
                        @if($trip->isQuickTrip() && $trip->needsCompletion())
                            Complete Trip Information
                        @else
                            Update Trip
                        @endif
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.trips.show', $trip) }}" variant="secondary" class="w-full gap-2">
                        <x-base.lucide class="w-4 h-4" icon="X" />
                        Cancel
                    </x-base.button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
