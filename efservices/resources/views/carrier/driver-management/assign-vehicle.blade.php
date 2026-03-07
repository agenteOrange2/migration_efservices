@extends('../themes/' . $activeTheme)

@section('title', 'Assign Vehicle')
@php
$breadcrumbLinks = [
['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
['label' => 'Driver & Vehicle Management', 'url' => route('carrier.driver-vehicle-management.index')],
['label' => 'Driver Details', 'url' => route('carrier.driver-vehicle-management.show', $driver->id)],
['label' => 'Assign Vehicle', 'active' => true],
];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-y-10 gap-x-6">
    <div class="col-span-12">
        <!-- Header Section -->
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Assign Vehicle to Driver
            </div>
            <div class="flex gap-x-2 md:ml-auto">
                <x-base.button as="a" href="{{ route('carrier.driver-vehicle-management.show', $driver->id) }}" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                    Back to Driver
                </x-base.button>
            </div>
        </div>

        <!-- Driver Information Summary -->
        <div class="mt-3.5">
            <div class="box box--stacked flex flex-col p-5">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-slate-200 rounded-full flex items-center justify-center">
                        <x-base.lucide class="w-6 h-6 text-slate-500" icon="user" />
                    </div>
                    <div>
                        <div class="font-medium text-lg">{{ $driver->user->name ?? 'N/A' }}</div>
                        <div class="text-slate-500">{{ $driver->user->email ?? 'N/A' }} | Carrier: {{ $driver->carrier->name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Assignment Form -->
        <div class="box box--stacked mt-5">
            <div class="box-header p-5">
                <h3 class="box-title">Vehicle Assignment Details</h3>
            </div>
            <div class="box-body p-5">
                @if(session('error'))
                <div class="alert alert-danger mb-4">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="alert-circle" />
                    {{ session('error') }}
                </div>
                @endif

                <form action="{{ route('carrier.driver-vehicle-management.store-vehicle-assignment', $driver->id) }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 gap-6">
                        <!-- Vehicle Selection -->
                        <div class="space-y-4">
                            <div>
                                <x-base.form-label for="vehicle_id">Select Vehicle *</x-base.form-label>
                                <x-base.form-select id="vehicle_id" name="vehicle_id" required>
                                    <option value="">Choose a vehicle...</option>
                                    @foreach($availableVehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                        Unit {{ $vehicle->company_unit_number ?? 'N/A' }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                        @if($vehicle->carrier)
                                        ({{ $vehicle->carrier->name }})
                                        @endif
                                    </option>
                                    @endforeach
                                </x-base.form-select>
                                @error('vehicle_id')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <x-base.form-label for="assignment_date">Assignment Date *</x-base.form-label>
                                <x-base.litepicker
                                    name="assignment_date"
                                    value="{{ old('assignment_date', date('Y-m-d')) }}"
                                    placeholder="Select date" />
                                @error('assignment_date')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="space-y-4">
                            <div>
                                <x-base.form-label for="notes">Assignment Notes</x-base.form-label>
                                <x-base.form-textarea
                                    id="notes"
                                    name="notes"
                                    rows="4"
                                    placeholder="Enter any notes about this vehicle assignment...">{{ old('notes') }}</x-base.form-textarea>
                                @error('notes')
                                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-slate-500 text-sm mt-1">Maximum 500 characters</div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 mt-6 pt-6 border-t">
                        <x-base.button as="a" href="{{ route('carrier.driver-vehicle-management.show', $driver->id) }}" variant="outline-secondary">
                            Cancel
                        </x-base.button>
                        <x-base.button type="submit" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="truck" />
                            Assign Vehicle
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Available Vehicles List -->
        @if($availableVehicles->count() > 0)
        <div class="box box--stacked mt-5">
            <div class="box-header p-5">
                <h3 class="box-title">Available Vehicles ({{ $availableVehicles->count() }})</h3>
            </div>
            <div class="box-body p-0">
                <div class="overflow-x-auto">
                    <x-base.table class="border-separate border-spacing-y-[10px]">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.th class="whitespace-nowrap">Make & Model</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Vehicle Number</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Type</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Carrier</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Status</x-base.table.th>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @foreach($availableVehicles as $vehicle)
                            <x-base.table.tr>
                                <x-base.table.td class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                    <div class="font-medium">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                                    <div class="text-slate-500 text-xs">Year: {{ $vehicle->year ?? 'N/A' }}</div>
                                </x-base.table.td>
                                <x-base.table.td class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                    <div class="font-medium">Unit {{ $vehicle->company_unit_number ?? 'N/A' }}</div>
                                    <div class="text-slate-500 text-xs">ID: {{ $vehicle->id }}</div>
                                </x-base.table.td>
                                <x-base.table.td class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                    {{ $vehicle->vehicleType->name ?? 'N/A' }}
                                </x-base.table.td>
                                <x-base.table.td class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                    {{ $vehicle->carrier->name ?? 'N/A' }}
                                </x-base.table.td>
                                <x-base.table.td class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Available
                                    </span>
                                </x-base.table.td>
                            </x-base.table.tr>
                            @endforeach
                        </x-base.table.tbody>
                    </x-base.table>
                </div>
            </div>
        </div>
        @else
        <div class="box box--stacked mt-5">
            <div class="box-body p-10 text-center">
                <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto" icon="truck" />
                <div class="text-xl font-medium text-slate-500 mt-3">No Available Vehicles</div>
                <div class="text-slate-400 mt-2">All vehicles are currently assigned to other drivers.</div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
