@extends('../themes/' . $activeTheme)
@section('title', 'Create Trip')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Trips', 'url' => route('carrier.trips.index')],
        ['label' => 'Create Trip', 'active' => true],
    ];
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

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="PlusCircle" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">Create New Trip</h1>
                <p class="text-slate-600">Fill in the details below to create a new trip</p>
            </div>
        </div>
        <x-base.button as="a" href="{{ route('carrier.trips.index') }}" variant="secondary" class="gap-2">
            <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
            Back to Trips
        </x-base.button>
    </div>
</div>

<form action="{{ route('carrier.trips.store') }}" method="POST">
    @csrf
    
    <div class="grid grid-cols-12 gap-6">
        <!-- Main Content -->
        <div class="col-span-12 lg:col-span-8 space-y-6">
            <!-- Route Information -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="MapPin" />
                    <h2 class="text-lg font-semibold text-slate-800">Route Information</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-base.form-label for="origin_address" required>Origin Address</x-base.form-label>
                        <x-base.form-textarea id="origin_address" name="origin_address" rows="3" required
                            placeholder="Enter pickup address">{{ old('origin_address') }}</x-base.form-textarea>
                        @error('origin_address')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-base.form-label for="destination_address" required>Destination Address</x-base.form-label>
                        <x-base.form-textarea id="destination_address" name="destination_address" rows="3" required
                            placeholder="Enter delivery address">{{ old('destination_address') }}</x-base.form-textarea>
                        @error('destination_address')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Calendar" />
                    <h2 class="text-lg font-semibold text-slate-800">Schedule</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-base.form-label for="scheduled_start_date" required>Scheduled Start</x-base.form-label>
                        <x-base.form-input type="datetime-local" id="scheduled_start_date" name="scheduled_start_date" 
                            value="{{ old('scheduled_start_date') }}" required />
                        @error('scheduled_start_date')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-base.form-label for="estimated_duration_minutes">Estimated Duration (minutes)</x-base.form-label>
                        <x-base.form-input type="number" id="estimated_duration_minutes" name="estimated_duration_minutes" 
                            value="{{ old('estimated_duration_minutes') }}" min="1" placeholder="e.g. 480" />
                        @error('estimated_duration_minutes')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Load Information -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Package" />
                    <h2 class="text-lg font-semibold text-slate-800">Load Information <span class="text-sm font-normal text-slate-500">(Optional)</span></h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-base.form-label for="load_type">Load Type</x-base.form-label>
                        <x-base.form-input type="text" id="load_type" name="load_type" 
                            value="{{ old('load_type') }}" placeholder="e.g. General Freight" />
                    </div>
                    <div>
                        <x-base.form-label for="load_weight">Load Weight (lbs)</x-base.form-label>
                        <x-base.form-input type="number" id="load_weight" name="load_weight" 
                            value="{{ old('load_weight') }}" min="0" step="0.01" placeholder="e.g. 5000" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-6 mt-6">
                    <div>
                        <x-base.form-label for="description">Description</x-base.form-label>
                        <x-base.form-textarea id="description" name="description" rows="3"
                            placeholder="Trip description...">{{ old('description') }}</x-base.form-textarea>
                    </div>
                    <div>
                        <x-base.form-label for="notes">Internal Notes</x-base.form-label>
                        <x-base.form-textarea id="notes" name="notes" rows="3"
                            placeholder="Internal notes for this trip...">{{ old('notes') }}</x-base.form-textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <!-- Assignment -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="UserCheck" />
                    <h2 class="text-lg font-semibold text-slate-800">Assignment</h2>
                </div>
                <div class="space-y-6">
                    <div>
                        <x-base.form-label for="driver_id" required>Driver</x-base.form-label>
                        <x-base.tom-select id="driver_id" name="driver_id" class="w-full" required>
                            <option value="">Select Driver</option>
                            @foreach($drivers as $driver)
                                @php
                                    $hoursPercent = ($driver['hours_remaining'] / 11) * 100;
                                    $availabilityClass = $hoursPercent >= 75 ? 'text-success' : ($hoursPercent >= 25 ? 'text-warning' : 'text-danger');
                                @endphp
                                <option value="{{ $driver['id'] }}" 
                                        {{ old('driver_id') == $driver['id'] ? 'selected' : '' }}
                                        {{ !$driver['can_drive'] ? 'disabled' : '' }}
                                        data-availability="{{ $availabilityClass }}">
                                    {{ $driver['name'] }} 
                                    ({{ $driver['hours_remaining'] }}h remaining)
                                    @if(!$driver['can_drive']) - UNAVAILABLE @endif
                                </option>
                            @endforeach
                        </x-base.tom-select>
                        @error('driver_id')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <x-base.form-label for="vehicle_id" required>Vehicle</x-base.form-label>
                        <x-base.tom-select id="vehicle_id" name="vehicle_id" class="w-full" required>
                            <option value="">Select Vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->unit_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </x-base.tom-select>
                        @error('vehicle_id')
                            <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Driver Availability Legend -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                    <h2 class="text-lg font-semibold text-slate-800">Driver Availability</h2>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-success"></div>
                        <span class="text-sm text-slate-600">Available (75%+ hours remaining)</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-warning"></div>
                        <span class="text-sm text-slate-600">Limited (25-75% hours remaining)</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-danger"></div>
                        <span class="text-sm text-slate-600">Unavailable (exceeded limits)</span>
                    </div>
                </div>
            </div>

            <!-- Submit Actions -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex flex-col gap-3">
                    <x-base.button type="submit" variant="primary" class="w-full gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Plus" />
                        Create Trip
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.trips.index') }}" variant="secondary" class="w-full gap-2">
                        <x-base.lucide class="w-4 h-4" icon="X" />
                        Cancel
                    </x-base.button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
