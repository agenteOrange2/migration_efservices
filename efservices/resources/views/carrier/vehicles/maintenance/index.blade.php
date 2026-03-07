@extends('../themes/' . $activeTheme)
@section('title', 'Vehicle Maintenance Records')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Vehicles', 'url' => route('carrier.vehicles.index')],
        ['label' => $vehicle->make . ' ' . $vehicle->model, 'url' => route('carrier.vehicles.show', $vehicle->id)],
        ['label' => 'Maintenance', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium">
                    Maintenance Records: {{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('carrier.vehicles.show', $vehicle->id) }}"
                        class="w-full sm:w-auto" variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                        Back to Vehicle
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.vehicles.maintenance.create', $vehicle->id) }}"
                        class="w-full sm:w-auto" variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="PlusCircle" />
                        Add Maintenance Record
                    </x-base.button>
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
                                    <x-base.button as="a" href="{{ route('carrier.vehicles.maintenance.create', $vehicle->id) }}"
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
                                                // Determine status CSS class based on requirements 9.1, 9.2, 9.3
                                                $statusClass = '';
                                                if ($maintenance->status) {
                                                    $statusClass = 'completed';
                                                } elseif ($maintenance->next_service_date && \Carbon\Carbon::parse($maintenance->next_service_date)->isPast()) {
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
                                                    <div class="font-medium">{{ Str::limit($maintenance->service_tasks, 50) }}</div>
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
                                                        <div class="{{ $statusClass === 'overdue' ? 'text-danger' : ($statusClass === 'pending' ? 'text-warning' : 'text-slate-500') }}">
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
                                                        <a href="{{ route('carrier.maintenance.show', $maintenance->id) }}"
                                                            class="btn btn-primary btn-sm" title="View Details">
                                                            <x-base.lucide class="h-4 w-4" icon="Eye" />
                                                        </a>
                                                        <a href="{{ route('carrier.maintenance.edit', $maintenance->id) }}"
                                                            class="btn btn-warning btn-sm" title="Edit">
                                                            <x-base.lucide class="h-4 w-4" icon="Edit" />
                                                        </a>
                                                        <form action="{{ route('carrier.maintenance.toggle-status', $maintenance->id) }}"
                                                            method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn {{ $maintenance->status ? 'btn-secondary' : 'btn-success' }} btn-sm"
                                                                title="{{ $maintenance->status ? 'Mark as Pending' : 'Mark as Completed' }}">
                                                                <x-base.lucide class="h-4 w-4" icon="{{ $maintenance->status ? 'Clock' : 'CheckCircle' }}" />
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
        </div>
    </div>

    <style>
        /* Visual indicators for maintenance status - Requirements 9.1, 9.2, 9.3 */
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
    </style>
@endsection
