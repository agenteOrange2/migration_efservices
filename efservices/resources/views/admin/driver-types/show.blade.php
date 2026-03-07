@extends('../themes/' . $activeTheme)

@section('subhead')
    <title>Driver Details - {{ $driver->user->name ?? 'N/A' }}</title>
@endsection

@section('subcontent')
    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <div class="col-span-12">
            <!-- Header Section -->
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Driver Details
                </div>
                <div class="flex gap-x-2 md:ml-auto">
                    <x-base.button as="a" href="{{ route('admin.driver-types.index') }}" variant="outline-secondary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                        Back to List
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.driver-types.assign-vehicle', $driver) }}" variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="Truck" />
                        Assign Vehicle
                    </x-base.button>
                </div>
            </div>

            <!-- Driver Information Card -->
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col p-5">
                    <div class="flex flex-col gap-5 md:flex-row">
                        <!-- Profile Photo -->
                        <div class="flex-shrink-0">
                            <div class="h-32 w-32 overflow-hidden rounded-lg border-2 border-slate-200">
                                <div class="h-full w-full bg-slate-200 flex items-center justify-center">
                                    <x-base.lucide class="w-16 h-16 text-slate-500" icon="User" />
                                </div>
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

                                    <!-- License Number -->
                                    <div>
                                        <div class="text-xs text-slate-500">License Number</div>
                                        <div class="mt-1 font-medium">
                                            {{ $driver->license_number ?? 'N/A' }}
                                        </div>
                                    </div>

                                    <!-- License Expiration -->
                                    <div>
                                        <div class="text-xs text-slate-500">License Expiration</div>
                                        <div class="mt-1 font-medium">
                                            {{ $driver->license_expiration ? \Carbon\Carbon::parse($driver->license_expiration)->format('M d, Y') : 'N/A' }}
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
                                            @if($driver->application_completed)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-success/10 text-success">
                                                    Completed
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-warning/10 text-warning">
                                                    Pending
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Assignments -->
            <div class="mt-5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex items-center border-b border-slate-200/60 p-5">
                        <h3 class="text-lg font-medium">Vehicle Assignment History</h3>
                    </div>
                    <div class="p-5">
                    @if($driver->vehicleAssignments && $driver->vehicleAssignments->count() > 0)
                        <div class="overflow-x-auto">
                            <x-base.table>
                                <x-base.table.thead>
                                    <x-base.table.tr>
                                        <x-base.table.th>Vehicle</x-base.table.th>
                                        <x-base.table.th>Assignment Date</x-base.table.th>
                                        <x-base.table.th>Status</x-base.table.th>
                                        <x-base.table.th>Notes</x-base.table.th>
                                    </x-base.table.tr>
                                </x-base.table.thead>
                                <x-base.table.tbody>
                                    @foreach($driver->vehicleAssignments as $assignment)
                                        <x-base.table.tr>
                                            <x-base.table.td>
                                                <div class="font-medium">{{ $assignment->vehicle->vehicle_number ?? 'N/A' }}</div>
                                                <div class="text-slate-500 text-sm">{{ $assignment->vehicle->make }} {{ $assignment->vehicle->model }}</div>
                                            </x-base.table.td>
                                            <x-base.table.td>{{ \Carbon\Carbon::parse($assignment->assignment_date)->format('M d, Y') }}</x-base.table.td>
                                            <x-base.table.td>
                                                @if($assignment->status === 'active')
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                                @else
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($assignment->status) }}</span>
                                                @endif
                                            </x-base.table.td>
                                            <x-base.table.td>{{ $assignment->notes ?? 'No notes' }}</x-base.table.td>
                                        </x-base.table.tr>
                                    @endforeach
                                </x-base.table.tbody>
                            </x-base.table>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <x-base.lucide class="h-16 w-16 text-slate-300 mb-4" icon="Truck" />
                            <div class="text-lg font-medium text-slate-500">No Vehicle Assignments</div>
                            <div class="mt-1 text-sm text-slate-400">
                                This driver has not been assigned to any vehicles yet.
                            </div>
                            <div class="mt-4">
                                <x-base.button as="a" href="{{ route('admin.driver-types.assign-vehicle', $driver) }}" variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Truck" />
                                    Assign Vehicle
                                </x-base.button>
                            </div>
                        </div>
                    @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex items-center border-b border-slate-200/60 p-5">
                        <h3 class="text-lg font-medium">Actions</h3>
                    </div>
                    <div class="p-5">
                        <div class="flex flex-wrap gap-3">
                            <x-base.button as="a" href="{{ route('admin.driver-types.assign-vehicle', $driver) }}" variant="primary">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="Truck" />
                                Assign Vehicle
                            </x-base.button>
                            <x-base.button as="a" href="{{ route('admin.driver-types.contact', $driver) }}" variant="outline-secondary">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="Mail" />
                                Contact Driver
                            </x-base.button>
                        </div>
                    </div>
                </div>
           
        </div>
    </div>
@endsection