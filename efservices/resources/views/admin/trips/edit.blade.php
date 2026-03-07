@extends('../themes/' . $activeTheme)
@section('title', 'Edit Trip - ' . $trip->trip_number)
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Trips', 'url' => route('admin.trips.index')],
        ['label' => 'Edit ' . $trip->trip_number, 'active' => true],
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
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Edit Trip {{ $trip->trip_number }}</h1>
                    <p class="text-slate-600">Update trip details</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('admin.trips.show', $trip) }}" variant="outline-secondary">
                    <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                    Back to Trip
                </x-base.button>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible show flex items-center mb-6" role="alert">
            <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.trips.update', $trip) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-12 gap-6">
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
                                @foreach($carriers as $carrier)
                                    <option value="{{ $carrier->id }}" {{ $trip->carrier_id == $carrier->id ? 'selected' : '' }}>
                                        {{ $carrier->name }}
                                    </option>
                                @endforeach
                            </x-base.form-select>
                        </div>
                        
                        <div>
                            <x-base.form-label for="driver_id">Driver *</x-base.form-label>
                            <x-base.form-select id="driver_id" name="driver_id" required>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ $trip->user_driver_detail_id == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->user->name ?? 'Unknown' }}
                                    </option>
                                @endforeach
                            </x-base.form-select>
                        </div>
                        
                        <div>
                            <x-base.form-label for="vehicle_id">Vehicle *</x-base.form-label>
                            <x-base.form-select id="vehicle_id" name="vehicle_id" required>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ $trip->vehicle_id == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->company_unit_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                    </option>
                                @endforeach
                            </x-base.form-select>
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
                            <x-base.form-textarea id="origin_address" name="origin_address" rows="2" required>{{ old('origin_address', $trip->origin_address) }}</x-base.form-textarea>
                        </div>
                        
                        <div>
                            <x-base.form-label for="destination_address">Destination Address *</x-base.form-label>
                            <x-base.form-textarea id="destination_address" name="destination_address" rows="2" required>{{ old('destination_address', $trip->destination_address) }}</x-base.form-textarea>
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
                                value="{{ old('scheduled_start_date', $trip->scheduled_start_date?->format('m/d/Y')) }}" 
                                placeholder="Select date" required />
                            @error('scheduled_start_date')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Scheduled Start Time -->
                        <div>
                            <x-base.form-label for="scheduled_start_time">Start Time *</x-base.form-label>
                            <x-base.form-input type="time" id="scheduled_start_time" name="scheduled_start_time" 
                                value="{{ old('scheduled_start_time', $trip->scheduled_start_date?->format('H:i') ?? '08:00') }}" required />
                            @error('scheduled_start_time')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Est. Duration -->
                        <div>
                            <x-base.form-label for="estimated_duration_minutes">Est. Duration (minutes)</x-base.form-label>
                            <x-base.form-input type="number" id="estimated_duration_minutes" name="estimated_duration_minutes" 
                                value="{{ old('estimated_duration_minutes', $trip->estimated_duration_minutes) }}" min="1" />
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
                                value="{{ old('scheduled_end_date', $trip->scheduled_end_date?->format('m/d/Y')) }}" 
                                placeholder="Select date (optional)" />
                            @error('scheduled_end_date')
                                <div class="text-danger text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Scheduled End Time (Optional) -->
                        <div>
                            <x-base.form-label for="scheduled_end_time">End Time</x-base.form-label>
                            <x-base.form-input type="time" id="scheduled_end_time" name="scheduled_end_time" 
                                value="{{ old('scheduled_end_time', $trip->scheduled_end_date?->format('H:i')) }}" />
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-base.form-label for="description">Description</x-base.form-label>
                            <x-base.form-textarea id="description" name="description" rows="3">{{ old('description', $trip->description) }}</x-base.form-textarea>
                        </div>
                        
                        <div>
                            <x-base.form-label for="notes">Notes</x-base.form-label>
                            <x-base.form-textarea id="notes" name="notes" rows="3">{{ old('notes', $trip->notes) }}</x-base.form-textarea>
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
                            <x-base.lucide class="mr-2 h-4 w-4" icon="Save" />
                            Update Trip
                        </x-base.button>
                        
                        <x-base.button type="button" as="a" href="{{ route('admin.trips.show', $trip) }}" 
                            class="w-full" variant="outline-secondary">
                            Cancel
                        </x-base.button>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-slate-200">
                        <div class="text-sm text-slate-500">
                            <div class="flex justify-between mb-2">
                                <span>Status:</span>
                                <span class="font-medium">{{ $trip->status_name }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span>Created:</span>
                                <span class="font-medium">{{ $trip->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
