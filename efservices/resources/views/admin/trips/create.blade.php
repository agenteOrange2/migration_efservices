@extends('../themes/' . $activeTheme)
@section('title', 'Create New Trip')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Trips', 'url' => route('admin.trips.index')],
        ['label' => 'Create New Trip', 'active' => true],
    ];
@endphp
@section('subcontent')

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="Truck" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Create New Trip</h1>
                    <p class="text-slate-600">Schedule a new trip for any carrier</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('admin.trips.index') }}" variant="outline-secondary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back to Trips
                </x-base.button>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible show flex items-center mb-6" role="alert">
            <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
            {{ session('error') }}
            <button type="button" class="btn-close" data-tw-dismiss="alert" aria-label="Close">
                <x-base.lucide class="w-4 h-4" icon="X" />
            </button>
        </div>
    @endif

    <form action="{{ route('admin.trips.store') }}" method="POST" id="tripForm">
        @csrf
        
        <div class="grid grid-cols-12 gap-6">
            <!-- Main Form -->
            <div class="col-span-12 xl:col-span-8">
                <!-- Carrier & Assignment -->
                <div class="box box--stacked p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="Building2" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Carrier & Assignment</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <x-base.form-label for="carrier_id">Carrier *</x-base.form-label>
                            <x-base.form-select id="carrier_id" name="carrier_id" required>
                                <option value="">Select Carrier</option>
                                @foreach($carriers as $carrier)
                                    <option value="{{ $carrier->id }}" {{ old('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                        {{ $carrier->name }}
                                    </option>
                                @endforeach
                            </x-base.form-select>
                            @error('carrier_id')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="driver_id">Driver *</x-base.form-label>
                            <x-base.form-select id="driver_id" name="driver_id" required disabled>
                                <option value="">Select Carrier First</option>
                            </x-base.form-select>
                            <div id="driver_hours" class="text-xs mt-1 hidden"></div>
                            @error('driver_id')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="vehicle_id">Vehicle *</x-base.form-label>
                            <x-base.form-select id="vehicle_id" name="vehicle_id" required disabled>
                                <option value="">Select Carrier First</option>
                            </x-base.form-select>
                            @error('vehicle_id')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Route Information -->
                <div class="box box--stacked p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="MapPin" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Route Information</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-base.form-label for="origin_address">Origin Address *</x-base.form-label>
                            <x-base.form-textarea id="origin_address" name="origin_address" rows="2" required
                                placeholder="Enter pickup address">{{ old('origin_address') }}</x-base.form-textarea>
                            @error('origin_address')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div>
                            <x-base.form-label for="destination_address">Destination Address *</x-base.form-label>
                            <x-base.form-textarea id="destination_address" name="destination_address" rows="2" required
                                placeholder="Enter delivery address">{{ old('destination_address') }}</x-base.form-textarea>
                            @error('destination_address')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="box box--stacked p-6 mb-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="Calendar" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Schedule</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Scheduled Start Date -->
                        <div>
                            <x-base.form-label for="scheduled_start_date">Scheduled Start *</x-base.form-label>
                            <x-base.litepicker id="scheduled_start_date" name="scheduled_start_date" 
                                value="{{ old('scheduled_start_date') }}" placeholder="Select date" required />
                            @error('scheduled_start_date')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Scheduled Start Time -->
                        <div>
                            <x-base.form-label for="scheduled_start_time">Start Time *</x-base.form-label>
                            <x-base.form-input type="time" id="scheduled_start_time" name="scheduled_start_time" 
                                value="{{ old('scheduled_start_time', '08:00') }}" required />
                            @error('scheduled_start_time')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Est. Duration -->
                        <div>
                            <x-base.form-label for="estimated_duration_minutes">Est. Duration (minutes)</x-base.form-label>
                            <x-base.form-input type="number" id="estimated_duration_minutes" name="estimated_duration_minutes" 
                                value="{{ old('estimated_duration_minutes') }}" min="1" placeholder="e.g. 120" />
                            @error('estimated_duration_minutes')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                        <!-- Scheduled End Date (Optional) -->
                        <div>
                            <x-base.form-label for="scheduled_end_date">Scheduled End</x-base.form-label>
                            <x-base.litepicker id="scheduled_end_date" name="scheduled_end_date" 
                                value="{{ old('scheduled_end_date') }}" placeholder="Select date (optional)" />
                            @error('scheduled_end_date')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Scheduled End Time (Optional) -->
                        <div>
                            <x-base.form-label for="scheduled_end_time">End Time</x-base.form-label>
                            <x-base.form-input type="time" id="scheduled_end_time" name="scheduled_end_time" 
                                value="{{ old('scheduled_end_time') }}" />
                            @error('scheduled_end_time')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Additional Details -->
                <div class="box box--stacked p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-slate-600" icon="FileText" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Additional Details</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-base.form-label for="description">Description</x-base.form-label>
                            <x-base.form-textarea id="description" name="description" rows="3"
                                placeholder="Trip description...">{{ old('description') }}</x-base.form-textarea>
                        </div>
                        
                        <div>
                            <x-base.form-label for="notes">Notes</x-base.form-label>
                            <x-base.form-textarea id="notes" name="notes" rows="3"
                                placeholder="Additional notes...">{{ old('notes') }}</x-base.form-textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-span-12 xl:col-span-4">
                <div class="box box--stacked p-6 sticky top-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="CheckCircle" />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">Actions</h2>
                    </div>
                    
                    <div class="space-y-4">
                        <x-base.button type="submit" class="w-full" variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="Plus" />
                            Create Trip
                        </x-base.button>
                        
                        <x-base.button type="button" as="a" href="{{ route('admin.trips.index') }}" 
                            class="w-full" variant="outline-secondary">
                            Cancel
                        </x-base.button>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <h3 class="text-sm font-semibold text-slate-600 mb-3">Quick Tips</h3>
                        <ul class="text-sm text-slate-500 space-y-2">
                            <li class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 mt-0.5 text-slate-400" icon="Info" />
                                Select a carrier first to load drivers and vehicles
                            </li>
                            <li class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 mt-0.5 text-slate-400" icon="Info" />
                                Drivers with low remaining hours will be highlighted
                            </li>
                            <li class="flex items-start gap-2">
                                <x-base.lucide class="w-4 h-4 mt-0.5 text-slate-400" icon="Info" />
                                Trip will be created with "Pending" status
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const carrierSelect = document.getElementById('carrier_id');
    const driverSelect = document.getElementById('driver_id');
    const vehicleSelect = document.getElementById('vehicle_id');
    const driverHoursDiv = document.getElementById('driver_hours');
    
    let driversData = [];

    carrierSelect.addEventListener('change', function() {
        const carrierId = this.value;
        
        if (!carrierId) {
            driverSelect.innerHTML = '<option value="">Select Carrier First</option>';
            driverSelect.disabled = true;
            vehicleSelect.innerHTML = '<option value="">Select Carrier First</option>';
            vehicleSelect.disabled = true;
            driverHoursDiv.classList.add('hidden');
            return;
        }

        // Fetch carrier data
        fetch(`{{ route('admin.trips.carrier.data') }}?carrier_id=${carrierId}`)
            .then(response => response.json())
            .then(data => {
                driversData = data.drivers;
                
                // Populate drivers
                driverSelect.innerHTML = '<option value="">Select Driver</option>';
                data.drivers.forEach(driver => {
                    const option = document.createElement('option');
                    option.value = driver.id;
                    option.textContent = `${driver.name} (${driver.hours_remaining}h remaining)`;
                    if (!driver.can_drive) {
                        option.disabled = true;
                        option.textContent += ' - Over limit';
                    }
                    driverSelect.appendChild(option);
                });
                driverSelect.disabled = false;

                // Populate vehicles
                vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>';
                data.vehicles.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.id;
                    option.textContent = `${vehicle.company_unit_number} - ${vehicle.make} ${vehicle.model} (${vehicle.year})`;
                    vehicleSelect.appendChild(option);
                });
                vehicleSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error fetching carrier data:', error);
            });
    });

    driverSelect.addEventListener('change', function() {
        const driverId = parseInt(this.value);
        const driver = driversData.find(d => d.id === driverId);
        
        if (driver) {
            driverHoursDiv.classList.remove('hidden');
            driverHoursDiv.textContent = `Hours remaining: ${driver.hours_remaining}h`;
            driverHoursDiv.className = `text-xs mt-1 text-${driver.status_color}`;
        } else {
            driverHoursDiv.classList.add('hidden');
        }
    });
});
</script>
@endpush
