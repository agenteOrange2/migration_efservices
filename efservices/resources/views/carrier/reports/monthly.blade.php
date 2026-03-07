@extends('../themes/' . $activeTheme)
@section('title', 'Monthly Summary Report')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Reports', 'url' => route('carrier.reports.index')],
        ['label' => 'Monthly Summary', 'active' => true],
    ];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Monthly Summary Report
            </div>
            <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                <x-base.button
                    as="a"
                    href="{{ route('carrier.reports.monthly.export-pdf', request()->query()) }}"
                    variant="primary"
                    class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                >
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Download" />
                    Export to PDF
                </x-base.button>
            </div>
        </div>

        <div class="mt-3.5">
            <!-- Filter Panel -->
            <div class="box box--stacked p-5 mb-5">
                <form action="{{ route('carrier.reports.monthly') }}" method="GET" class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 sm:col-span-6 lg:col-span-4">
                        <x-base.form-label for="date_from">From Date</x-base.form-label>
                        <x-base.form-input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="{{ $filters['date_from'] ?? '' }}"
                        />
                    </div>
                    
                    <div class="col-span-12 sm:col-span-6 lg:col-span-4">
                        <x-base.form-label for="date_to">To Date</x-base.form-label>
                        <x-base.form-input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="{{ $filters['date_to'] ?? '' }}"
                        />
                    </div>
                    
                    <div class="col-span-12 lg:col-span-4 flex items-end gap-2">
                        <x-base.button type="submit" variant="primary" class="w-full sm:w-auto">
                            <x-base.lucide class="mr-2 h-4 w-4" icon="Filter" />
                            Apply Filters
                        </x-base.button>
                        <x-base.button
                            as="a"
                            href="{{ route('carrier.reports.monthly') }}"
                            variant="outline-secondary"
                            class="w-full sm:w-auto"
                        >
                            <x-base.lucide class="mr-2 h-4 w-4" icon="X" />
                            Clear
                        </x-base.button>
                    </div>
                </form>
            </div>

            <!-- Summary Statistics -->
            @php
                $totalDrivers = collect($monthlyData)->sum('drivers');
                $totalVehicles = collect($monthlyData)->sum('vehicles');
                $totalAccidents = collect($monthlyData)->sum('accidents');
                $totalMaintenanceCost = collect($monthlyData)->sum('maintenance.total_cost');
                $totalRepairCost = collect($monthlyData)->sum('repairs.total_cost');
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5 mb-5">
                <!-- New Drivers Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                            <x-base.lucide class="h-6 w-6 text-primary" icon="Users" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">New Drivers</div>
                            <div class="mt-1 text-2xl font-medium">{{ $totalDrivers }}</div>
                        </div>
                    </div>
                </div>

                <!-- New Vehicles Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success/10">
                            <x-base.lucide class="h-6 w-6 text-success" icon="Truck" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">New Vehicles</div>
                            <div class="mt-1 text-2xl font-medium">{{ $totalVehicles }}</div>
                        </div>
                    </div>
                </div>

                <!-- Accidents Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-danger/10">
                            <x-base.lucide class="h-6 w-6 text-danger" icon="AlertTriangle" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Accidents</div>
                            <div class="mt-1 text-2xl font-medium">{{ $totalAccidents }}</div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Cost Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning/10">
                            <x-base.lucide class="h-6 w-6 text-warning" icon="Wrench" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Maintenance</div>
                            <div class="mt-1 text-xl font-medium">${{ number_format($totalMaintenanceCost, 0) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Repair Cost Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-info/10">
                            <x-base.lucide class="h-6 w-6 text-info" icon="Settings" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Repairs</div>
                            <div class="mt-1 text-xl font-medium">${{ number_format($totalRepairCost, 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Data Table -->
            <div class="box box--stacked mb-5">
                <div class="flex flex-col border-b border-slate-200/60 p-5 dark:border-darkmode-400 sm:flex-row">
                    <h2 class="mr-auto text-base font-medium">
                        Monthly Breakdown
                    </h2>
                    <div class="mt-3 text-sm text-slate-500 sm:mt-0">
                        {{ \Carbon\Carbon::parse($startDate)->format('F Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('F Y') }}
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <x-base.table class="border-b border-slate-200/60">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.td class="whitespace-nowrap bg-slate-50 py-4 font-medium text-slate-500">
                                    Month
                                </x-base.table.td>
                                <x-base.table.td class="whitespace-nowrap bg-slate-50 text-center py-4 font-medium text-slate-500">
                                    Drivers
                                </x-base.table.td>
                                <x-base.table.td class="whitespace-nowrap bg-slate-50 text-center py-4 font-medium text-slate-500">
                                    Vehicles
                                </x-base.table.td>
                                <x-base.table.td class="whitespace-nowrap bg-slate-50 text-center py-4 font-medium text-slate-500">
                                    Accidents
                                </x-base.table.td>
                                <x-base.table.td class="whitespace-nowrap bg-slate-50 text-center py-4 font-medium text-slate-500">
                                    Maintenance
                                </x-base.table.td>
                                <x-base.table.td class="whitespace-nowrap bg-slate-50 text-center py-4 font-medium text-slate-500">
                                    Maint. Cost
                                </x-base.table.td>
                                <x-base.table.td class="whitespace-nowrap bg-slate-50 text-center py-4 font-medium text-slate-500">
                                    Repairs
                                </x-base.table.td>
                                <x-base.table.td class="whitespace-nowrap bg-slate-50 text-center py-4 font-medium text-slate-500">
                                    Repair Cost
                                </x-base.table.td>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @forelse($monthlyData as $data)
                                <x-base.table.tr>
                                    <x-base.table.td class="border-b dark:border-darkmode-400">
                                        <div class="font-medium">{{ $data['month_name'] }}</div>
                                    </x-base.table.td>
                                    <x-base.table.td class="border-b dark:border-darkmode-400 text-center">
                                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                            {{ $data['drivers'] }}
                                        </span>
                                    </x-base.table.td>
                                    <x-base.table.td class="border-b dark:border-darkmode-400 text-center">
                                        <span class="rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">
                                            {{ $data['vehicles'] }}
                                        </span>
                                    </x-base.table.td>
                                    <x-base.table.td class="border-b dark:border-darkmode-400 text-center">
                                        @if($data['accidents'] > 0)
                                            <span class="rounded-full bg-danger/10 px-3 py-1 text-xs font-medium text-danger">
                                                {{ $data['accidents'] }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">0</span>
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td class="border-b dark:border-darkmode-400 text-center">
                                        <span class="rounded-full bg-warning/10 px-3 py-1 text-xs font-medium text-warning">
                                            {{ $data['maintenance']['count'] }}
                                        </span>
                                    </x-base.table.td>
                                    <x-base.table.td class="border-b dark:border-darkmode-400 text-center">
                                        <span class="text-slate-600">${{ number_format($data['maintenance']['total_cost'], 2) }}</span>
                                    </x-base.table.td>
                                    <x-base.table.td class="border-b dark:border-darkmode-400 text-center">
                                        <span class="rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info">
                                            {{ $data['repairs']['count'] }}
                                        </span>
                                    </x-base.table.td>
                                    <x-base.table.td class="border-b dark:border-darkmode-400 text-center">
                                        <span class="text-slate-600">${{ number_format($data['repairs']['total_cost'], 2) }}</span>
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @empty
                                <x-base.table.tr>
                                    <x-base.table.td colspan="8" class="py-10 text-center text-slate-500">
                                        <div class="flex flex-col items-center">
                                            <x-base.lucide class="mb-3 h-16 w-16 text-slate-400" icon="Inbox" />
                                            <p class="text-lg font-medium">No data available</p>
                                            <p class="mt-1 text-sm">Try adjusting your date range filters</p>
                                        </div>
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @endforelse
                        </x-base.table.tbody>
                    </x-base.table>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-12 gap-5">
                <!-- Drivers & Vehicles Chart -->
                <div class="col-span-12 lg:col-span-6">
                    <div class="box box--stacked p-5">
                        <div class="mb-5 flex items-center border-b border-slate-200/60 pb-5 dark:border-darkmode-400">
                            <h2 class="mr-auto text-base font-medium">Drivers & Vehicles Trend</h2>
                        </div>
                        <div class="h-[300px]">
                            <canvas id="driversVehiclesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Accidents Chart -->
                <div class="col-span-12 lg:col-span-6">
                    <div class="box box--stacked p-5">
                        <div class="mb-5 flex items-center border-b border-slate-200/60 pb-5 dark:border-darkmode-400">
                            <h2 class="mr-auto text-base font-medium">Accidents Trend</h2>
                        </div>
                        <div class="h-[300px]">
                            <canvas id="accidentsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Maintenance & Repairs Cost Chart -->
                <div class="col-span-12">
                    <div class="box box--stacked p-5">
                        <div class="mb-5 flex items-center border-b border-slate-200/60 pb-5 dark:border-darkmode-400">
                            <h2 class="mr-auto text-base font-medium">Maintenance & Repairs Cost Trend</h2>
                        </div>
                        <div class="h-[200px]">
                            <canvas id="costsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@pushOnce('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlyData = @json($monthlyData);
    
    // Prepare data for charts
    const labels = monthlyData.map(item => item.month_name);
    const driversData = monthlyData.map(item => item.drivers);
    const vehiclesData = monthlyData.map(item => item.vehicles);
    const accidentsData = monthlyData.map(item => item.accidents);
    const maintenanceCosts = monthlyData.map(item => item.maintenance.total_cost);
    const repairCosts = monthlyData.map(item => item.repairs.total_cost);
    
    // Drivers & Vehicles Chart
    const driversVehiclesCtx = document.getElementById('driversVehiclesChart');
    if (driversVehiclesCtx) {
        new Chart(driversVehiclesCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Drivers',
                        data: driversData,
                        borderColor: 'rgba(59, 130, 246, 0.8)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Vehicles',
                        data: vehiclesData,
                        borderColor: 'rgba(34, 197, 94, 0.8)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(34, 197, 94, 1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    
    // Accidents Chart
    const accidentsCtx = document.getElementById('accidentsChart');
    if (accidentsCtx) {
        new Chart(accidentsCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Accidents',
                    data: accidentsData,
                    backgroundColor: 'rgba(239, 68, 68, 0.6)',
                    borderColor: 'rgba(239, 68, 68, 0.8)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Accidents: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    
    // Maintenance & Repairs Cost Chart
    const costsCtx = document.getElementById('costsChart');
    if (costsCtx) {
        new Chart(costsCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Maintenance Cost',
                        data: maintenanceCosts,
                        backgroundColor: 'rgba(251, 191, 36, 0.6)',
                        borderColor: 'rgba(251, 191, 36, 0.8)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Repair Cost',
                        data: repairCosts,
                        backgroundColor: 'rgba(14, 165, 233, 0.6)',
                        borderColor: 'rgba(14, 165, 233, 0.8)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString('en-US');
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endPushOnce
