@extends('../themes/' . $activeTheme)

@section('title', 'Maintenance Reports')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
    ['label' => 'Maintenance', 'url' => route('admin.maintenance.index')],
    ['label' => 'Reports', 'active' => true],
];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <!-- Professional Header -->
        <div class="box box--stacked p-6 mb-6">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="BarChart3" />
                    </div>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-slate-800 mb-2">Maintenance Reports</h1>
                        <p class="text-slate-600">Analyze maintenance costs, trends and upcoming services</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <form action="{{ route('admin.maintenance.export-pdf') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="period" value="{{ $period ?? 'monthly' }}">
                        <input type="hidden" name="vehicle_id" value="{{ $vehicleId ?? '' }}">
                        <input type="hidden" name="start_date" value="{{ $startDate ?? \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                        <input type="hidden" name="end_date" value="{{ $endDate ?? \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d') }}">
                        <x-base.button type="submit" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="FileText" />
                            Export PDF
                        </x-base.button>
                    </form>
                    <x-base.button as="a" href="{{ route('admin.maintenance.index') }}" variant="outline-secondary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                        Back
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked mb-6">
            <div class="box-header p-5 border-b border-slate-200/60">
                <h3 class="text-lg font-medium flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Filter" />
                    Report Filters
                </h3>
            </div>
            <div class="box-body p-5">
                <form method="GET" action="{{ route('admin.maintenance.reports') }}" id="filter-form">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-base.form-label>Period</x-base.form-label>
                            <select name="period" id="period-filter"
                                class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="all" {{ ($period ?? 'all') == 'all' ? 'selected' : '' }}>All Periods</option>
                                <option value="daily" {{ $period == 'daily' ? 'selected' : '' }}>Today</option>
                                <option value="weekly" {{ $period == 'weekly' ? 'selected' : '' }}>This Week</option>
                                <option value="monthly" {{ $period == 'monthly' ? 'selected' : '' }}>This Month</option>
                                <option value="yearly" {{ $period == 'yearly' ? 'selected' : '' }}>This Year</option>
                                <option value="custom" {{ $period == 'custom' ? 'selected' : '' }}>Custom Period</option>
                            </select>
                        </div>
                        <div>
                            <x-base.form-label>Vehicle</x-base.form-label>
                            <select name="vehicle_id" id="vehicle-filter"
                                class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">All Vehicles</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ $vehicleId == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }}) - {{ $vehicle->license_plate }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-base.form-label>Status</x-base.form-label>
                            <select name="status" id="status-filter"
                                class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                <option value="">All Status</option>
                                <option value="1" {{ $status == '1' ? 'selected' : '' }}>Completed</option>
                                <option value="0" {{ $status == '0' ? 'selected' : '' }}>Pending</option>
                                <option value="2" {{ $status == '2' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <x-base.button type="submit" variant="primary" class="flex-1">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="Search" />
                                Filter
                            </x-base.button>
                            <x-base.button as="a" href="{{ route('admin.maintenance.reports') }}" variant="outline-secondary">
                                <x-base.lucide class="w-4 h-4" icon="RefreshCw" />
                            </x-base.button>
                        </div>
                    </div>
                    
                    <!-- Custom Date Range -->
                    <div id="custom-date-range" class="mt-4 {{ $period != 'custom' ? 'hidden' : '' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-base.form-label>Start Date</x-base.form-label>
                                <x-base.litepicker id="start_date" name="start_date" class="w-full" 
                                    value="{{ $startDate ?? '' }}" />
                            </div>
                            <div>
                                <x-base.form-label>End Date</x-base.form-label>
                                <x-base.litepicker id="end_date" name="end_date" class="w-full" 
                                    value="{{ $endDate ?? '' }}" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="box box--stacked p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="Wrench" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ $totalMaintenances }}</div>
                        <div class="text-xs text-slate-500">Total Maintenances</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-success/10 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-success" icon="Truck" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-success">{{ $vehiclesServiced }}</div>
                        <div class="text-xs text-slate-500">Vehicles Serviced</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-warning/10 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-warning" icon="DollarSign" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-warning">${{ number_format($totalCost, 2) }}</div>
                        <div class="text-xs text-slate-500">Total Cost</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-info/10 rounded-lg">
                        <x-base.lucide class="w-5 h-5 text-info" icon="Calculator" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-info">${{ number_format($avgCostPerVehicle ?? 0, 2) }}</div>
                        <div class="text-xs text-slate-500">Avg Cost/Vehicle</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Cost Chart -->
            <div class="box box--stacked">
                <div class="box-header p-5 border-b border-slate-200/60">
                    <h3 class="text-lg font-medium flex items-center gap-2">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="TrendingUp" />
                        Maintenance Costs by Month
                    </h3>
                </div>
                <div class="box-body p-5">
                    <div style="height: 280px;">
                        <canvas id="maintenanceCostChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Service Type Distribution -->
            <div class="box box--stacked">
                <div class="box-header p-5 border-b border-slate-200/60">
                    <h3 class="text-lg font-medium flex items-center gap-2">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="PieChart" />
                        Service Type Distribution
                    </h3>
                </div>
                <div class="box-body p-5">
                    @if(count($serviceTypeDistribution) > 0)
                        <div class="space-y-3">
                            @foreach($serviceTypeDistribution as $type => $data)
                                <div class="flex items-center gap-3">
                                    <div class="w-32 text-sm text-slate-600 truncate">{{ ucfirst($type) }}</div>
                                    <div class="flex-1 h-4 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-primary rounded-full" style="width: {{ $data['percentage'] }}%"></div>
                                    </div>
                                    <div class="w-16 text-right">
                                        <span class="text-sm font-medium text-slate-800">{{ $data['count'] }}</span>
                                        <span class="text-xs text-slate-500">({{ number_format($data['percentage'], 1) }}%)</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-200/60 flex items-center justify-between">
                            <span class="font-medium text-slate-600">Total</span>
                            <span class="font-bold text-slate-800">{{ array_sum(array_column($serviceTypeDistribution, 'count')) }} services</span>
                        </div>
                    @else
                        <div class="text-center py-8 text-slate-400">
                            <x-base.lucide class="w-12 h-12 mx-auto mb-2 text-slate-300" icon="PieChart" />
                            <p>No service data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Upcoming Maintenances -->
        <div class="box box--stacked mb-6">
            <div class="box-header p-5 border-b border-slate-200/60">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium flex items-center gap-2">
                        <x-base.lucide class="w-5 h-5 text-warning" icon="Calendar" />
                        Upcoming Scheduled Maintenances
                    </h3>
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-warning/10 text-warning">
                        {{ $upcomingMaintenances->count() }} scheduled
                    </span>
                </div>
            </div>
            <div class="box-body p-5">
                @if($upcomingMaintenances->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-200/60">
                                    <th class="py-3 px-4 font-medium text-slate-500">Vehicle</th>
                                    <th class="py-3 px-4 font-medium text-slate-500">Service Type</th>
                                    <th class="py-3 px-4 font-medium text-slate-500 text-center">Scheduled Date</th>
                                    <th class="py-3 px-4 font-medium text-slate-500 text-center">Days Remaining</th>
                                    <th class="py-3 px-4 font-medium text-slate-500 text-center">Status</th>
                                    <th class="py-3 px-4 font-medium text-slate-500 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcomingMaintenances as $maintenance)
                                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-slate-800">
                                                {{ $maintenance->vehicle->make }} {{ $maintenance->vehicle->model }}
                                            </div>
                                            <div class="text-xs text-slate-500">{{ $maintenance->vehicle->year }} - {{ $maintenance->vehicle->license_plate }}</div>
                                        </td>
                                        <td class="py-3 px-4 text-slate-600">{{ $maintenance->service_tasks }}</td>
                                        <td class="py-3 px-4 text-center">
                                            {{ $maintenance->next_service_date ? \Carbon\Carbon::parse($maintenance->next_service_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            @if($maintenance->next_service_date)
                                                @php
                                                    $daysRemaining = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($maintenance->next_service_date)->startOfDay(), false);
                                                @endphp
                                                @if($daysRemaining < 0)
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                                                        Overdue ({{ abs($daysRemaining) }} days)
                                                    </span>
                                                @elseif($daysRemaining <= 7)
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                                        {{ $daysRemaining }} days
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                                        {{ $daysRemaining }} days
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            @if($maintenance->status)
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">Completed</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-slate-200 text-slate-600">Pending</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <x-base.button as="a" href="{{ route('admin.maintenance.show', $maintenance->id) }}" variant="outline-primary" size="sm">
                                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                </x-base.button>
                                                <x-base.button as="a" href="{{ route('admin.maintenance.edit', $maintenance->id) }}" variant="outline-secondary" size="sm">
                                                    <x-base.lucide class="w-4 h-4" icon="Edit" />
                                                </x-base.button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="p-4 bg-slate-100 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <x-base.lucide class="w-8 h-8 text-slate-400" icon="Calendar" />
                        </div>
                        <h4 class="text-lg font-medium text-slate-600 mb-2">No Upcoming Maintenances</h4>
                        <p class="text-slate-500">No maintenance services are scheduled for the near future.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Detailed Maintenance List -->
        <div class="box box--stacked">
            <div class="box-header p-5 border-b border-slate-200/60">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium flex items-center gap-2">
                        <x-base.lucide class="w-5 h-5 text-primary" icon="List" />
                        Detailed Maintenance List
                    </h3>
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-primary/10 text-primary">
                        {{ $maintenances->total() }} records
                    </span>
                </div>
            </div>
            <div class="box-body p-5">
                @if($maintenances->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-200/60">
                                    <th class="py-3 px-4 font-medium text-slate-500 text-center">Date</th>
                                    <th class="py-3 px-4 font-medium text-slate-500">Vehicle</th>
                                    <th class="py-3 px-4 font-medium text-slate-500">Service Type</th>
                                    <th class="py-3 px-4 font-medium text-slate-500">Description</th>
                                    <th class="py-3 px-4 font-medium text-slate-500 text-center">Cost</th>
                                    <th class="py-3 px-4 font-medium text-slate-500 text-center">Status</th>
                                    <th class="py-3 px-4 font-medium text-slate-500 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($maintenances as $maintenance)
                                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                                        <td class="py-3 px-4 text-center text-sm text-slate-600">
                                            {{ $maintenance->service_date ? \Carbon\Carbon::parse($maintenance->service_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-slate-800">
                                                {{ $maintenance->vehicle->make }} {{ $maintenance->vehicle->model }}
                                            </div>
                                            <div class="text-xs text-slate-500">{{ $maintenance->vehicle->year }} - {{ $maintenance->vehicle->license_plate }}</div>
                                        </td>
                                        <td class="py-3 px-4 text-slate-600">{{ $maintenance->service_tasks }}</td>
                                        <td class="py-3 px-4 max-w-xs">
                                            <span class="text-sm text-slate-600" title="{{ $maintenance->notes }}">
                                                {{ Str::limit($maintenance->notes ?? 'No description', 40) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="font-medium text-success">${{ number_format($maintenance->cost, 2) }}</span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            @if($maintenance->status)
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">Completed</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-slate-200 text-slate-600">Pending</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <x-base.button as="a" href="{{ route('admin.maintenance.show', $maintenance->id) }}" variant="outline-primary" size="sm">
                                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                </x-base.button>
                                                <x-base.button as="a" href="{{ route('admin.maintenance.edit', $maintenance->id) }}" variant="outline-secondary" size="sm">
                                                    <x-base.lucide class="w-4 h-4" icon="Edit" />
                                                </x-base.button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($maintenances->hasPages())
                        <div class="mt-5 pt-5 border-t border-slate-200/60">
                            {{ $maintenances->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-12">
                        <div class="p-4 bg-slate-100 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                            <x-base.lucide class="w-8 h-8 text-slate-400" icon="Search" />
                        </div>
                        <h4 class="text-lg font-medium text-slate-600 mb-2">No Maintenances Found</h4>
                        <p class="text-slate-500">No maintenance records match the applied filters.</p>
                        <x-base.button as="a" href="{{ route('admin.maintenance.reports') }}" variant="outline-primary" class="mt-4">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="RefreshCw" />
                            Reset Filters
                        </x-base.button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize TomSelect for filters
        if (typeof TomSelect !== 'undefined') {
            if (document.getElementById('vehicle-filter')) {
                new TomSelect('#vehicle-filter', {
                    placeholder: 'Select vehicle',
                    allowEmptyOption: true
                });
            }
            if (document.getElementById('status-filter')) {
                new TomSelect('#status-filter', {
                    placeholder: 'Select status',
                    allowEmptyOption: true
                });
            }
        }

        // Period filter toggle for custom dates
        const periodSelect = document.getElementById('period-filter');
        const customDateRange = document.getElementById('custom-date-range');
        
        if (periodSelect && customDateRange) {
            periodSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                }
            });
        }

        // Maintenance Cost Chart
        const costsCtx = document.getElementById('maintenanceCostChart');
        if (costsCtx) {
            const costData = @json($costByMonth ?? []);
            const costLabels = Object.keys(costData);
            const costValues = Object.values(costData);
            
            new Chart(costsCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: costLabels.length > 0 ? costLabels : ['No data'],
                    datasets: [{
                        label: 'Maintenance Cost ($)',
                        data: costValues.length > 0 ? costValues : [0],
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
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
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
