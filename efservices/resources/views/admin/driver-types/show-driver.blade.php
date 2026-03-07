@extends('../themes/' . $activeTheme)

@section('title', 'Driver Details')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Driver Types', 'url' => route('admin.driver-types.index')],
        ['label' => 'Driver Details', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <div class="col-span-12">
            <!-- Professional Header -->
            <div class="box box--stacked p-8 mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-8 h-8 text-primary" icon="User" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">Driver Details</h1>
                            <p class="text-slate-600">Driver details for {{ $driver->user->name ?? 'N/A' }} {{ $driver->last_name ?? '' }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        <x-base.button as="a" href="{{ route('admin.driver-types.index') }}" variant="outline-primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                            Back to List
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.driver-types.assign-vehicle', $driver) }}"
                            variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Truck" />
                            Assign Vehicle
                        </x-base.button>
                    </div>
                </div>
            </div>

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
                                        {{ $driver->user->name ?? 'N/A' }}
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
                                            {{ $driver->date_of_birth ? \Carbon\Carbon::parse($driver->date_of_birth)->format('M d, Y') : 'N/A' }}
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <div class="text-xs text-slate-500">Status</div>
                                        <div class="mt-1">
                                            @if ($driver->status == 1)
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-success/10 text-success">
                                                    Active
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-danger/10 text-danger">
                                                    Inactive
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Carrier -->
                                    <div>
                                        <div class="text-xs text-slate-500">Carrier</div>
                                        <div class="mt-1 font-medium">
                                            {{ $driver->carrier->name ?? 'N/A' }}
                                        </div>
                                    </div>

                                    <!-- Registration Date -->
                                    <div>
                                        <div class="text-xs text-slate-500">Registration Date</div>
                                        <div class="mt-1 font-medium">
                                            {{ $driver->created_at->format('M d, Y') }}
                                        </div>
                                    </div>

                                    <!-- Application Status -->
                                    <div>
                                        <div class="text-xs text-slate-500">Application Status</div>
                                        <div class="mt-1">
                                            @if ($driver->application_completed)
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-success/10 text-success">
                                                    Completed
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-warning/10 text-warning">
                                                    Pending
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($driver->hire_date)
                                        <!-- Hire Date -->
                                        <div>
                                            <div class="text-xs text-slate-500">Hire Date</div>
                                            <div class="mt-1 font-medium">
                                                {{ $driver->hire_date->format('M d, Y') }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Vehicle Assignment -->
            @php
                $activeAssignment = $driver->vehicleAssignments->where('status', 'active')->first();
            @endphp

            <div class="mt-5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex items-center border-b border-slate-200/60 p-5">
                        <h3 class="text-lg font-medium">Current Vehicle Assignment</h3>
                        <div class="ml-auto flex gap-2">
                            @if ($activeAssignment)
                                <x-base.button as="a"
                                    href="{{ route('admin.driver-types.edit-assignment', $driver) }}"
                                    variant="outline-warning" size="sm">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Edit" />
                                    Edit Assignment
                                </x-base.button>
                                <x-base.button type="button" variant="outline-danger" size="sm" data-tw-toggle="modal"
                                    data-tw-target="#cancelAssignmentModal"
                                    onclick="confirmCancelAssignment({{ $driver->id }}, '{{ $driver->user->name ?? 'N/A' }}', '{{ $activeAssignment->vehicle->company_unit_number ?? 'N/A' }}')">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="XCircle" />
                                    Cancel Assignment
                                </x-base.button>
                            @endif
                        </div>
                    </div>
                    <div class="p-5">
                        @if ($activeAssignment && $activeAssignment->vehicle)
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                                <!-- Vehicle Info -->
                                <div>
                                    <div class="text-xs text-slate-500">Vehicle</div>
                                    <div class="mt-1 font-medium">
                                        {{ $activeAssignment->vehicle->company_unit_number ?: 'N/A' }}
                                    </div>
                                    <div class="mt-0.5 text-sm text-slate-500">
                                        {{ $activeAssignment->vehicle->make }} {{ $activeAssignment->vehicle->model }}
                                        ({{ $activeAssignment->vehicle->year }})
                                    </div>
                                </div>

                                <!-- Start Date -->
                                <div>
                                    <div class="text-xs text-slate-500">Start Date</div>
                                    <div class="mt-1 font-medium">
                                        {{ $activeAssignment->start_date ? \Carbon\Carbon::parse($activeAssignment->start_date)->format('M d, Y') : 'N/A' }}
                                    </div>
                                </div>

                                <!-- VIN -->
                                <div>
                                    <div class="text-xs text-slate-500">VIN</div>
                                    <div class="mt-1 font-medium">
                                        {{ $activeAssignment->vehicle->vin ?? 'N/A' }}
                                    </div>
                                </div>

                                <!-- Notes -->
                                @if ($activeAssignment->notes)
                                    <div class="md:col-span-2 lg:col-span-3">
                                        <div class="text-xs text-slate-500">Notes</div>
                                        <div class="mt-1 text-sm">
                                            {{ $activeAssignment->notes }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <x-base.lucide class="h-16 w-16 text-slate-300 mb-4" icon="Truck" />
                                <div class="text-lg font-medium text-slate-500">No Active Vehicle Assignment</div>
                                <div class="mt-1 text-sm text-slate-400">
                                    This driver is currently not assigned to any vehicle.
                                </div>
                                <div class="mt-4">
                                    <x-base.button as="a"
                                        href="{{ route('admin.driver-types.assign-vehicle', $driver) }}"
                                        variant="primary">
                                        <x-base.lucide class="w-4 h-4 mr-2" icon="Truck" />
                                        Assign Vehicle
                                    </x-base.button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Vehicle Assignment History -->
            <div class="mt-5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex items-center border-b border-slate-200/60 p-5">
                        <h3 class="text-lg font-medium">Vehicle Assignment History</h3>
                        <div class="ml-auto">
                            <x-base.button as="a"
                                href="{{ route('admin.driver-types.assignment-history', $driver) }}"
                                variant="outline-secondary" size="sm">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="History" />
                                View Full History
                            </x-base.button>
                        </div>
                    </div>
                    <div class="p-5">
                        @if ($driver->vehicleAssignments && $driver->vehicleAssignments->count() > 0)
                            <div class="overflow-x-auto">
                                <x-base.table class="border-separate border-spacing-y-[10px]">
                                    <x-base.table.thead>
                                        <x-base.table.tr>
                                            <x-base.table.th class="whitespace-nowrap">Vehicle</x-base.table.th>
                                            <x-base.table.th class="whitespace-nowrap">Assignment Period</x-base.table.th>
                                            <x-base.table.th class="whitespace-nowrap">Status</x-base.table.th>
                                            <x-base.table.th class="whitespace-nowrap">Duration</x-base.table.th>
                                            <x-base.table.th class="whitespace-nowrap">Notes</x-base.table.th>
                                        </x-base.table.tr>
                                    </x-base.table.thead>
                                    <x-base.table.tbody>
                                        @foreach ($driver->vehicleAssignments->sortByDesc('start_date')->take(5) as $assignment)
                                            <x-base.table.tr>
                                                <x-base.table.td
                                                    class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                                    @if ($assignment->vehicle)
                                                        <div class="flex items-center">
                                                            <div
                                                                class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center mr-3">
                                                                <x-base.lucide class="w-5 h-5 text-slate-500"
                                                                    icon="truck" />
                                                            </div>
                                                            <div>
                                                                <div class="font-medium">Unit
                                                                    {{ $assignment->vehicle->company_unit_number ?? 'N/A' }}
                                                                </div>
                                                                <div class="text-slate-500 text-sm">
                                                                    {{ $assignment->vehicle->make }}
                                                                    {{ $assignment->vehicle->model }}</div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center">
                                                            <div
                                                                class="w-10 h-10 bg-red-200 rounded-full flex items-center justify-center mr-3">
                                                                <x-base.lucide class="w-5 h-5 text-red-500"
                                                                    icon="alert-circle" />
                                                            </div>
                                                            <div>
                                                                <div class="font-medium text-red-600">Vehicle N/A</div>
                                                                <div class="text-red-500 text-sm">Vehicle information not
                                                                    available</div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </x-base.table.td>
                                                <x-base.table.td
                                                    class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                                    <div class="font-medium">
                                                        {{ $assignment->start_date ? \Carbon\Carbon::parse($assignment->start_date)->format('M d, Y') : 'N/A' }}
                                                    </div>
                                                    <div class="text-slate-500 text-sm">
                                                        to
                                                        {{ $assignment->end_date ? \Carbon\Carbon::parse($assignment->end_date)->format('M d, Y') : 'Present' }}
                                                    </div>
                                                </x-base.table.td>
                                                <x-base.table.td
                                                    class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                                    @if ($assignment->status === 'active')
                                                        <span
                                                            class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                                    @elseif($assignment->status === 'terminated')
                                                        <span
                                                            class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Terminated</span>
                                                    @else
                                                        <span
                                                            class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($assignment->status) }}</span>
                                                    @endif
                                                </x-base.table.td>
                                                <x-base.table.td
                                                    class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                                    @if ($assignment->start_date)
                                                        @php
                                                            $startDate = \Carbon\Carbon::parse($assignment->start_date);
                                                            $endDate = $assignment->end_date
                                                                ? \Carbon\Carbon::parse($assignment->end_date)
                                                                : \Carbon\Carbon::now();
                                                            $duration = $startDate->diffInDays($endDate);
                                                        @endphp
                                                        <div class="font-medium">
                                                            @if ($duration == 0)
                                                                Less than a day
                                                            @elseif($duration < 30)
                                                                {{ $duration }} {{ $duration == 1 ? 'day' : 'days' }}
                                                            @elseif($duration < 365)
                                                                {{ floor($duration / 30) }}
                                                                {{ floor($duration / 30) == 1 ? 'month' : 'months' }}
                                                            @else
                                                                {{ floor($duration / 365) }}
                                                                {{ floor($duration / 365) == 1 ? 'year' : 'years' }}
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400">N/A</span>
                                                    @endif
                                                </x-base.table.td>
                                                <x-base.table.td
                                                    class="px-6 py-4 first:rounded-l-md last:rounded-r-md bg-white border-b-0 dark:bg-darkmode-600 shadow-[20px_3px_20px_#0000000b]">
                                                    <div class="max-w-xs truncate"
                                                        title="{{ $assignment->notes ?? 'No notes' }}">
                                                        {{ $assignment->notes ?? 'No notes' }}
                                                    </div>
                                                </x-base.table.td>
                                            </x-base.table.tr>
                                        @endforeach
                                    </x-base.table.tbody>
                                </x-base.table>
                            </div>
                            @if ($driver->vehicleAssignments->count() > 5)
                                <div class="mt-4 text-center">
                                    <x-base.button as="a"
                                        href="{{ route('admin.driver-types.assignment-history', $driver) }}"
                                        variant="outline-primary">
                                        <x-base.lucide class="w-4 h-4 mr-2" icon="eye" />
                                        View All {{ $driver->vehicleAssignments->count() }} Assignments
                                    </x-base.button>
                                </div>
                            @endif
                        @else
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <x-base.lucide class="h-16 w-16 text-slate-300 mb-4" icon="History" />
                                <div class="text-lg font-medium text-slate-500">No Vehicle Assignment History</div>
                                <div class="mt-1 text-sm text-slate-400">
                                    This driver has not been assigned to any vehicles yet.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Emergency Contact Information -->
            @if ($driver->emergency_contact_name || $driver->emergency_contact_phone)
                <div class="mt-5">
                    <div class="box box--stacked flex flex-col">
                        <div class="flex items-center border-b border-slate-200/60 p-5">
                            <h3 class="text-lg font-medium">Emergency Contact</h3>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <div class="text-xs text-slate-500">Name</div>
                                    <div class="mt-1 font-medium">{{ $driver->emergency_contact_name ?? 'N/A' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">Phone</div>
                                    <div class="mt-1 font-medium">{{ $driver->emergency_contact_phone ?? 'N/A' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">Relationship</div>
                                    <div class="mt-1 font-medium">{{ $driver->emergency_contact_relationship ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Notes -->
            @if ($driver->notes)
                <div class="mt-5">
                    <div class="box box--stacked flex flex-col">
                        <div class="flex items-center border-b border-slate-200/60 p-5">
                            <h3 class="text-lg font-medium">Notes</h3>
                        </div>
                        <div class="p-5">
                            <div class="text-slate-700">{{ $driver->notes }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="mt-5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex items-center border-b border-slate-200/60 p-5">
                        <h3 class="text-lg font-medium">Actions</h3>
                    </div>
                    <div class="p-5">
                        <div class="flex flex-wrap gap-3">
                            @if ($activeAssignment && $activeAssignment->vehicle)
                                <x-base.button as="a"
                                    href="{{ route('admin.driver-types.edit-assignment', $driver) }}"
                                    variant="outline-warning">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Edit" />
                                    Edit Vehicle Assignment
                                </x-base.button>
                                <x-base.button type="button" variant="danger" data-tw-toggle="modal"
                                    data-tw-target="#cancelAssignmentModal"
                                    onclick="confirmCancelAssignment({{ $driver->id }}, '{{ $driver->user->name ?? 'N/A' }}', '{{ $activeAssignment->vehicle->company_unit_number ?? 'N/A' }}')">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="XCircle" />
                                    Cancel Assignment
                                </x-base.button>
                            @else
                                <x-base.button as="a"
                                    href="{{ route('admin.driver-types.assign-vehicle', $driver) }}" variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Truck" />
                                    Assign Vehicle
                                </x-base.button>
                            @endif
                            <x-base.button as="a" href="{{ route('admin.driver-types.contact', $driver) }}"
                                variant="outline-secondary">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="Mail" />
                                Contact Driver
                            </x-base.button>
                            <x-base.button as="a"
                                href="{{ route('admin.driver-types.assignment-history', $driver) }}"
                                variant="outline-primary">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="History" />
                                Assignment History
                            </x-base.button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Assignment Confirmation Modal -->
    <x-base.dialog id="cancelAssignmentModal" size="md">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-warning" icon="alert-triangle" />
                <div class="mt-5 text-3xl">Are you sure?</div>
                <div class="mt-2 text-slate-500">
                    You are about to cancel the vehicle assignment for <strong id="driverNameModal"></strong>.
                    <br>
                    Current vehicle: <strong id="vehicleUnitModal"></strong>
                    <br><br>
                    This action will terminate the current assignment and make both the driver and vehicle available for new
                    assignments.
                </div>
            </div>
            <div class="px-5 pb-8 text-center">
                <x-base.button class="mr-1 w-24" data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Cancel
                </x-base.button>
                <x-base.button id="confirmCancelBtn" class="w-24" type="button" variant="danger">
                    Yes, Cancel
                </x-base.button>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>
@endsection

@push('scripts')
    <script>
        function confirmCancelAssignment(driverId, driverName, vehicleUnit) {
            // Update modal content
            document.getElementById('driverNameModal').textContent = driverName;
            document.getElementById('vehicleUnitModal').textContent = 'Unit ' + vehicleUnit;

            // Set up the confirm button action
            document.getElementById('confirmCancelBtn').onclick = function() {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/driver-types/${driverId}/cancel-assignment`;

                // Add CSRF token
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                // Add method override for DELETE
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);

                // Add termination_date (current date)
                const terminationDate = document.createElement('input');
                terminationDate.type = 'hidden';
                terminationDate.name = 'termination_date';
                terminationDate.value = new Date().toISOString().split('T')[0];
                form.appendChild(terminationDate);

                // Add termination_reason
                const terminationReason = document.createElement('input');
                terminationReason.type = 'hidden';
                terminationReason.name = 'termination_reason';
                terminationReason.value = 'Assignment cancelled by administrator';
                form.appendChild(terminationReason);

                // Add notes
                const notes = document.createElement('input');
                notes.type = 'hidden';
                notes.name = 'notes';
                notes.value = 'Assignment cancelled via admin panel';
                form.appendChild(notes);

                document.body.appendChild(form);
                form.submit();
            };
        }
    </script>
@endpush
