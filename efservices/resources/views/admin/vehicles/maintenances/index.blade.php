@extends('../themes/' . $activeTheme)
@section('title', 'Vehicle Maintenance Records')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
        ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('admin.vehicles.show', $vehicle->id)],
        ['label' => 'Maintenance', 'active' => true],
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
                            <x-base.lucide class="w-8 h-8 text-primary" icon="FileText" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">Maintenance Records</h1>
                            <p class="text-slate-600"> Maintenance Records: {{ $vehicle->make }} {{ $vehicle->model }}
                                ({{ $vehicle->year }})</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        <x-base.button as="a" href="{{ route('admin.vehicles.show', $vehicle->id) }}"
                            class="w-full sm:w-auto" variant="outline-secondary">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                            Back to Vehicle
                        </x-base.button>
                        <form action="{{ route('admin.vehicles.maintenances.generate-report', $vehicle->id) }}" method="POST" class="inline w-full sm:w-auto">
                            @csrf
                            <x-base.button type="submit" class="w-full sm:w-auto" variant="outline-success">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="FileText" />
                                Generate Report
                            </x-base.button>
                        </form>
                        <x-base.button as="a" href="{{ route('admin.vehicles.maintenances.create', $vehicle->id) }}"
                            class="w-full sm:w-auto" variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="PlusCircle" />
                            Add Maintenance Record
                        </x-base.button>
                    </div>
                </div>
            </div>

            <div class="mt-7">
                <div class="box box--stacked">
                    <div class="box-header bg-slate-50 p-5 border-b border-slate-200/60">
                        <div class="flex flex-col gap-y-3 md:flex-row md:items-center">
                            <div class="box-title font-medium">Maintenance History</div>
                        </div>
                    </div>
                    <div class="box-body p-5">
                        @if ($maintenances->isEmpty())
                            {{-- Empty state --}}
                            <div class="text-center py-10">
                                <x-base.lucide class="h-12 w-12 mx-auto text-slate-300" icon="Wrench" />
                                <div class="mt-3 text-slate-500">No maintenance records found</div>
                                <div class="mt-1 text-sm text-slate-400">
                                    Start tracking maintenance by adding your first record
                                </div>
                                <div class="mt-5">
                                    <x-base.button as="a"
                                        href="{{ route('admin.vehicles.maintenances.create', $vehicle->id) }}"
                                        variant="primary">
                                        <x-base.lucide class="mr-2 h-4 w-4" icon="Plus" />
                                        Add First Maintenance Record
                                    </x-base.button>
                                </div>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Service Date</th>
                                            <th scope="col" class="px-6 py-3">Service Tasks</th>
                                            <th scope="col" class="px-6 py-3">Vendor/Mechanic</th>
                                            <th scope="col" class="px-6 py-3">Cost</th>
                                            <th scope="col" class="px-6 py-3">Odometer</th>
                                            <th scope="col" class="px-6 py-3">Next Service</th>
                                            <th scope="col" class="px-6 py-3">Status</th>
                                            <th scope="col" class="px-6 py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($maintenances as $maintenance)
                                            @php
                                                // Determine status CSS class
                                                $statusClass = '';
                                                if ($maintenance->status) {
                                                    $statusClass = 'completed';
                                                } elseif (
                                                    $maintenance->next_service_date &&
                                                    \Carbon\Carbon::parse($maintenance->next_service_date)->isPast()
                                                ) {
                                                    $statusClass = 'overdue';
                                                } else {
                                                    $statusClass = 'pending';
                                                }
                                            @endphp
                                            <tr class="bg-white border-b hover:bg-gray-50 {{ $statusClass }}">
                                                <td class="px-6 py-4">
                                                    {{ \Carbon\Carbon::parse($maintenance->service_date)->format('m/d/Y') }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="font-medium">
                                                        {{ Str::limit($maintenance->service_tasks, 50) }}</div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    {{ $maintenance->vendor_mechanic }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    ${{ number_format($maintenance->cost, 2) }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    {{ number_format($maintenance->odometer) }} mi
                                                </td>
                                                <td class="px-6 py-4">
                                                    @if ($maintenance->next_service_date)
                                                        <div
                                                            class="{{ $statusClass === 'overdue' ? 'text-danger' : ($statusClass === 'pending' ? 'text-warning' : 'text-slate-500') }}">
                                                            {{ \Carbon\Carbon::parse($maintenance->next_service_date)->format('m/d/Y') }}
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400">N/A</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center whitespace-nowrap">
                                                        @if ($statusClass === 'completed')
                                                            <div class="w-2 h-2 rounded-full mr-2 bg-success"></div>
                                                            <span class="text-success">Completed</span>
                                                        @elseif ($statusClass === 'overdue')
                                                            <div class="w-2 h-2 rounded-full mr-2 bg-danger"></div>
                                                            <span class="text-danger">Overdue</span>
                                                        @else
                                                            <div class="w-2 h-2 rounded-full mr-2 bg-warning"></div>
                                                            <span class="text-warning">Pending</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <div class="flex justify-center space-x-1 gap-2">
                                                        <a href="{{ route('admin.vehicles.maintenances.show', [$vehicle->id, $maintenance->id]) }}"
                                                            class="btn btn-primary btn-sm" title="View Details">
                                                            <x-base.lucide class="h-4 w-4" icon="Eye" />
                                                        </a>
                                                        <a href="{{ route('admin.vehicles.maintenances.edit', [$vehicle->id, $maintenance->id]) }}"
                                                            class="btn btn-warning btn-sm" title="Edit">
                                                            <x-base.lucide class="h-4 w-4" icon="Edit" />
                                                        </a>
                                                        <form
                                                            action="{{ route('admin.vehicles.maintenances.toggle-status', [$vehicle->id, $maintenance->id]) }}"
                                                            method="POST" class="inline">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit"
                                                                class="btn {{ $maintenance->status ? 'btn-secondary' : 'btn-success' }} btn-sm"
                                                                title="{{ $maintenance->status ? 'Mark as Pending' : 'Mark as Completed' }}">
                                                                <x-base.lucide class="h-4 w-4"
                                                                    icon="{{ $maintenance->status ? 'Clock' : 'CheckCircle' }}" />
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination controls --}}
                            @if ($maintenances->hasPages())
                                <div class="mt-5">
                                    {{ $maintenances->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Emergency Repairs Section --}}
            <div class="mt-7">
                <div class="box box--stacked">
                    <div class="box-header bg-slate-50 p-5 border-b border-slate-200/60">
                        <div class="flex flex-col gap-y-3 md:flex-row md:items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-danger/10 rounded-lg">
                                    <x-base.lucide class="h-5 w-5 text-danger" icon="AlertTriangle" />
                                </div>
                                <div class="box-title font-medium">Emergency Repairs History</div>
                            </div>
                            <x-base.button as="a" href="{{ route('admin.admin-vehicles.vehicle-emergency-repairs.create', $vehicle->id) }}"
                                class="w-full sm:w-auto" variant="outline-danger" size="sm">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="Plus" />
                                Add Emergency Repair
                            </x-base.button>
                        </div>
                    </div>
                    <div class="box-body p-5">
                        @if ($emergencyRepairs->isEmpty())
                            {{-- Empty state --}}
                            <div class="text-center py-10">
                                <x-base.lucide class="h-12 w-12 mx-auto text-slate-300" icon="AlertTriangle" />
                                <div class="mt-3 text-slate-500">No emergency repairs found</div>
                                <div class="mt-1 text-sm text-slate-400">
                                    Emergency repairs will appear here when added
                                </div>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Repair Name</th>
                                            <th scope="col" class="px-6 py-3">Repair Date</th>
                                            <th scope="col" class="px-6 py-3">Cost</th>
                                            <th scope="col" class="px-6 py-3">Odometer</th>
                                            <th scope="col" class="px-6 py-3">Status</th>
                                            <th scope="col" class="px-6 py-3">Description</th>
                                            <th scope="col" class="px-6 py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($emergencyRepairs as $repair)
                                            @php
                                                $repairStatusClass = match($repair->status) {
                                                    'completed' => 'repair-completed',
                                                    'in_progress' => 'repair-in-progress',
                                                    default => 'repair-pending'
                                                };
                                            @endphp
                                            <tr class="bg-white border-b hover:bg-gray-50 {{ $repairStatusClass }}">
                                                <td class="px-6 py-4">
                                                    <div class="font-medium">{{ $repair->repair_name }}</div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    {{ $repair->repair_date ? $repair->repair_date->format('m/d/Y') : 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    ${{ number_format($repair->cost, 2) }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    {{ $repair->odometer ? number_format($repair->odometer) . ' mi' : 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center whitespace-nowrap">
                                                        @if ($repair->status === 'completed')
                                                            <div class="w-2 h-2 rounded-full mr-2 bg-success"></div>
                                                            <span class="text-success">Completed</span>
                                                        @elseif ($repair->status === 'in_progress')
                                                            <div class="w-2 h-2 rounded-full mr-2 bg-primary"></div>
                                                            <span class="text-primary">In Progress</span>
                                                        @else
                                                            <div class="w-2 h-2 rounded-full mr-2 bg-warning"></div>
                                                            <span class="text-warning">Pending</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="max-w-xs truncate" title="{{ $repair->description }}">
                                                        {{ Str::limit($repair->description, 40) ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <div class="flex justify-center space-x-1 gap-2">
                                                        <a href="{{ route('admin.admin-vehicles.vehicle-emergency-repairs.show', [$vehicle->id, $repair->id]) }}"
                                                            class="btn btn-primary btn-sm" title="View Details">
                                                            <x-base.lucide class="h-4 w-4" icon="Eye" />
                                                        </a>
                                                        <a href="{{ route('admin.admin-vehicles.vehicle-emergency-repairs.edit', [$vehicle->id, $repair->id]) }}"
                                                            class="btn btn-warning btn-sm" title="Edit">
                                                            <x-base.lucide class="h-4 w-4" icon="Edit" />
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Visual indicators for maintenance status */
        tr.overdue {
            background-color: rgba(239, 68, 68, 0.05);
        }

        tr.pending {
            background-color: rgba(245, 158, 11, 0.05);
        }

        tr.completed {
            background-color: rgba(34, 197, 94, 0.05);
        }

        tr.overdue:hover {
            background-color: rgba(239, 68, 68, 0.1);
        }

        tr.pending:hover {
            background-color: rgba(245, 158, 11, 0.1);
        }

        tr.completed:hover {
            background-color: rgba(34, 197, 94, 0.1);
        }

        /* Visual indicators for emergency repair status */
        tr.repair-pending {
            background-color: rgba(245, 158, 11, 0.05);
        }

        tr.repair-in-progress {
            background-color: rgba(59, 130, 246, 0.05);
        }

        tr.repair-completed {
            background-color: rgba(34, 197, 94, 0.05);
        }

        tr.repair-pending:hover {
            background-color: rgba(245, 158, 11, 0.1);
        }

        tr.repair-in-progress:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }

        tr.repair-completed:hover {
            background-color: rgba(34, 197, 94, 0.1);
        }
    </style>
@endsection
