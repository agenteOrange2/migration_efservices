@extends('../themes/' . $activeTheme)
@section('title', 'Edit Vehicle Assignment')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Driver Types', 'url' => route('admin.driver-types.index')],
        ['label' => 'Driver Details', 'url' => route('admin.driver-types.show', $driver->id)],
        ['label' => 'Edit Assignment', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Professional Header -->
            <div class="box box--stacked p-8 mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-8 h-8 text-primary" icon="FileCheck" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">Edit Vehicle Assignment</h1>
                            <p class="text-slate-600">Edit vehicle assignment for {{ $driver->user->name ?? 'N/A' }} {{ $driver->last_name ?? '' }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        <x-base.button as="a" href="{{ route('admin.driver-types.show', $driver->id) }}"
                            variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                            Back to Driver
                        </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="alert-success alert mt-3">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert-danger alert mt-3">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Driver Information Card -->
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col p-5">
                    <div class="flex flex-col gap-5 md:flex-row">
                        <!-- Profile Photo -->
                        <div class="flex-shrink-0">
                            <div class="h-32 w-32 overflow-hidden rounded-lg border-2 border-slate-200">
                                <img src="{{ $driver->profile_photo_url }}" alt="{{ $driver->full_name }}"
                                    class="h-full w-full object-cover">
                            </div>
                        </div>

                        <!-- Driver Info -->
                        <div class="flex-1">
                            <div class="flex flex-col gap-y-3">
                                <div>
                                    <h2 class="text-2xl font-medium">
                                        {{ $driver->user->name ?? 'N/A' }} {{ $driver->last_name ?? '' }}
                                    </h2>
                                    <div class="mt-1 text-slate-500">
                                        {{ $driver->user->email ?? 'N/A' }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    <!-- Phone -->
                                    <div>
                                        <div class="text-xs text-slate-500">Phone</div>
                                        <div class="mt-1 font-medium">
                                            {{ $driver->phone ?? 'N/A' }}
                                        </div>
                                    </div>

                                    <!-- Date of Birth -->
                                    <div>
                                        <div class="text-xs text-slate-500">Date of Birth</div>
                                        <div class="mt-1 font-medium">
                                            {{ $driver->date_of_birth ? $driver->date_of_birth->format('M d, Y') : 'N/A' }}
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <div class="text-xs text-slate-500">Status</div>
                                        <div class="mt-1">
                                            <span @class([
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                'bg-success/10 text-success' => $driver->status == 1,
                                                'bg-slate-100 text-slate-500' => $driver->status == 0,
                                                'bg-warning/10 text-warning' => $driver->status == 2,
                                            ])>
                                                {{ $driver->status_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Assignment Info -->
            <div class="mt-5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex items-center border-b border-slate-200/60 p-5">
                        <h3 class="text-lg font-medium">Current Assignment</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                            <!-- Vehicle Info -->
                            <div>
                                <div class="text-xs text-slate-500">Vehicle</div>
                                <div class="mt-1 font-medium">
                                    {{ $currentAssignment->vehicle->company_unit_number ?: 'N/A' }}
                                </div>
                                <div class="mt-0.5 text-sm text-slate-500">
                                    {{ $currentAssignment->vehicle->make }} {{ $currentAssignment->vehicle->model }}
                                    ({{ $currentAssignment->vehicle->year }})
                                </div>
                            </div>

                            <!-- Driver Type -->
                            <div>
                                <div class="text-xs text-slate-500">Driver Type</div>
                                <div class="mt-1 font-medium">
                                    {{ ucwords(str_replace('_', ' ', $currentAssignment->driver_type)) }}
                                </div>
                            </div>

                            <!-- Start Date -->
                            <div>
                                <div class="text-xs text-slate-500">Start Date</div>
                                <div class="mt-1 font-medium">
                                    {{ \Carbon\Carbon::parse($currentAssignment->start_date)->format('M d, Y') }}
                                </div>
                            </div>

                            <!-- Notes -->
                            @if ($currentAssignment->notes)
                                <div class="md:col-span-2 lg:col-span-3">
                                    <div class="text-xs text-slate-500">Notes</div>
                                    <div class="mt-1 text-sm">
                                        {{ $currentAssignment->notes }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Assignment Form -->
            <div class="mt-5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex items-center border-b border-slate-200/60 p-5">
                        <h3 class="text-lg font-medium">New Assignment Details</h3>
                    </div>

                    <form action="{{ route('admin.driver-types.update-assignment', $driver->id) }}" method="POST"
                        class="p-5" x-data="{ driverType: '' }" x-init="driverType = '{{ old('driver_type', $currentAssignment->driver_type) }}'">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <!-- Vehicle Selection -->
                            <div class="md:col-span-2">
                                <x-base.form-label for="vehicle_id">
                                    Vehicle <span class="text-danger">*</span>
                                </x-base.form-label>
                                <x-base.tom-select id="vehicle_id" name="vehicle_id" class="w-full" required>
                                    <option value="">Select a vehicle...</option>
                                    @foreach ($availableVehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}"
                                            {{ old('vehicle_id', $currentAssignment->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                            {{ $vehicle->company_unit_number ?: 'Unit #' . $vehicle->id }} -
                                            {{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})
                                            @if ($vehicle->vin)
                                                - VIN: {{ substr($vehicle->vin, -6) }}
                                            @endif
                                            @if ($vehicle->id == $currentAssignment->vehicle_id)
                                                (Current)
                                            @endif
                                        </option>
                                    @endforeach
                                </x-base.tom-select>
                                <div class="mt-2 text-sm text-slate-500">
                                    <x-base.lucide class="inline h-4 w-4" icon="Info" />
                                    Changing the vehicle will end the current assignment and create a new one.
                                </div>
                            </div>

                            <!-- Driver Type -->
                            <div>
                                <x-base.form-label for="driver_type">
                                    Driver Type <span class="text-danger">*</span>
                                </x-base.form-label>
                                <x-base.form-select id="driver_type" name="driver_type" class="w-full" x-model="driverType"
                                    required>
                                    <option value="">Select driver type...</option>
                                    <option value="company_driver"
                                        {{ old('driver_type', $currentAssignment->driver_type) == 'company_driver' ? 'selected' : '' }}>
                                        Company Driver
                                    </option>
                                    <option value="owner_operator"
                                        {{ old('driver_type', $currentAssignment->driver_type) == 'owner_operator' ? 'selected' : '' }}>
                                        Owner Operator
                                    </option>
                                    <option value="third_party"
                                        {{ old('driver_type', $currentAssignment->driver_type) == 'third_party' ? 'selected' : '' }}>
                                        Third Party
                                    </option>
                                </x-base.form-select>
                            </div>

                            <!-- Start Date -->
                            <div>
                                <x-base.form-label for="start_date">
                                    Start Date <span class="text-danger">*</span>
                                </x-base.form-label>
                                <x-base.litepicker id="start_date" name="start_date" class="w-full"
                                    value="{{ old('start_date', now()->format('Y-m-d')) }}" required />
                            </div>

                            <!-- Third Party Information Section -->
                            <div x-show="driverType === 'third_party'" class="md:col-span-2" style="display: none;">
                                <div class="border-t border-slate-200 pt-5 mt-3">
                                    <h4 class="text-base font-medium mb-4">Third Party Information</h4>

                                    @php
                                        $thirdPartyData = $currentAssignment->thirdPartyDetail ?? null;
                                    @endphp

                                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                                        <!-- Company Name -->
                                        <div>
                                            <x-base.form-label for="third_party_name">
                                                Company Name <span class="text-danger">*</span>
                                            </x-base.form-label>
                                            <x-base.form-input id="third_party_name" name="third_party_name"
                                                type="text" placeholder="Enter company name"
                                                value="{{ old('third_party_name', $thirdPartyData->third_party_name ?? '') }}"
                                                x-bind:required="driverType === 'third_party'" />
                                        </div>

                                        <!-- DBA -->
                                        <div>
                                            <x-base.form-label for="third_party_dba">
                                                DBA (Doing Business As)
                                            </x-base.form-label>
                                            <x-base.form-input id="third_party_dba" name="third_party_dba" type="text"
                                                placeholder="Enter DBA name"
                                                value="{{ old('third_party_dba', $thirdPartyData->third_party_dba ?? '') }}" />
                                        </div>

                                        <!-- Company Address -->
                                        <div>
                                            <x-base.form-label for="third_party_address">
                                                Company Address <span class="text-danger">*</span>
                                            </x-base.form-label>
                                            <x-base.form-input id="third_party_address" name="third_party_address"
                                                type="text" placeholder="Enter complete address"
                                                value="{{ old('third_party_address', $thirdPartyData->third_party_address ?? '') }}"
                                                x-bind:required="driverType === 'third_party'" />
                                        </div>

                                        <!-- Phone Number -->
                                        <div>
                                            <x-base.form-label for="third_party_phone">
                                                Phone Number <span class="text-danger">*</span>
                                            </x-base.form-label>
                                            <x-base.form-input id="third_party_phone" name="third_party_phone"
                                                type="text" placeholder="(555) 123-4567"
                                                value="{{ old('third_party_phone', $thirdPartyData->third_party_phone ?? '') }}"
                                                x-bind:required="driverType === 'third_party'" />
                                        </div>

                                        <!-- Email Address -->
                                        <div>
                                            <x-base.form-label for="third_party_email">
                                                Email Address <span class="text-danger">*</span>
                                            </x-base.form-label>
                                            <x-base.form-input id="third_party_email" name="third_party_email"
                                                type="email" placeholder="company@email.com"
                                                value="{{ old('third_party_email', $thirdPartyData->third_party_email ?? '') }}"
                                                x-bind:required="driverType === 'third_party'" />
                                        </div>

                                        <!-- FEIN / Tax ID -->
                                        <div>
                                            <x-base.form-label for="third_party_fein">
                                                FEIN / Tax ID
                                            </x-base.form-label>
                                            <x-base.form-input id="third_party_fein" name="third_party_fein"
                                                type="text" placeholder="XX-XXXXXXX"
                                                value="{{ old('third_party_fein', $thirdPartyData->third_party_fein ?? '') }}" />
                                        </div>

                                        <!-- Contact Person -->
                                        <div class="md:col-span-3">
                                            <x-base.form-label for="third_party_contact">
                                                Contact Person
                                            </x-base.form-label>
                                            <x-base.form-input id="third_party_contact" name="third_party_contact"
                                                type="text" placeholder="Primary contact name"
                                                value="{{ old('third_party_contact', $thirdPartyData->third_party_contact ?? '') }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-2">
                                <x-base.form-label for="notes">
                                    Notes
                                </x-base.form-label>
                                <x-base.form-textarea id="notes" name="notes" rows="3"
                                    placeholder="Enter any additional notes about this assignment...">{{ old('notes', $currentAssignment->notes) }}</x-base.form-textarea>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-6 flex justify-end gap-2">
                            <x-base.button type="button" variant="outline-secondary"
                                onclick="window.location='{{ route('admin.driver-types.show', $driver->id) }}'">
                                Cancel
                            </x-base.button>
                            <x-base.button type="submit" variant="primary">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="Check" />
                                Update Assignment
                            </x-base.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
