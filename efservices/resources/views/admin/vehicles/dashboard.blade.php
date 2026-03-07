@extends('../themes/' . $activeTheme)
@section('title', 'Vehicles Dashboard')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Vehicles Dashboard', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Professional Header -->
            <div class="box box--stacked p-4 sm:p-6 lg:p-8 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 lg:gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-3 lg:gap-4">
                        <div class="p-2 sm:p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-6 h-6 sm:w-8 sm:h-8 text-primary" icon="Truck" />
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-800 mb-1 sm:mb-2">Vehicles Dashboard</h1>
                            <p class="text-sm sm:text-base text-slate-600">Overview of your fleet management</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="box box--stacked mb-5">
                <div class="box-body p-5">
                    <form action="{{ route('admin.vehicles.dashboard') }}" method="GET" id="filter-form">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <x-base.form-label>Carrier</x-base.form-label>
                                <select name="carrier_id" id="carrier-filter"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Carriers</option>
                                    @foreach ($carriers as $carrier)
                                        <option value="{{ $carrier->id }}" {{ $carrierId == $carrier->id ? 'selected' : '' }}>
                                            {{ $carrier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-base.form-label>Start Date</x-base.form-label>
                                <x-base.litepicker id="start_date" name="start_date" class="w-full" 
                                    value="{{ $startDate->format('Y-m-d') }}" />
                            </div>
                            <div>
                                <x-base.form-label>End Date</x-base.form-label>
                                <x-base.litepicker id="end_date" name="end_date" class="w-full" 
                                    value="{{ $endDate->format('Y-m-d') }}" />
                            </div>
                            <div class="flex items-end gap-2">
                                <x-base.button type="submit" variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Filter" />
                                    Filter
                                </x-base.button>
                                <x-base.button as="a" href="{{ route('admin.vehicles.dashboard') }}" variant="outline-secondary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="RefreshCw" />
                                    Reset
                                </x-base.button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Access Buttons -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-5">
                <x-base.button as="a" href="{{ route('admin.vehicles.index') }}" variant="primary" class="flex items-center justify-center py-4">
                    <x-base.lucide class="w-5 h-5 mr-2" icon="Truck" />
                    Vehicles
                </x-base.button>
                <x-base.button as="a" href="{{ route('admin.driver-types.index') }}" variant="outline-primary" class="flex items-center justify-center py-4">
                    <x-base.lucide class="w-5 h-5 mr-2" icon="Users" />
                    Driver Types
                </x-base.button>
                <x-base.button as="a" href="{{ route('admin.vehicles-documents.index') }}" variant="outline-primary" class="flex items-center justify-center py-4">
                    <x-base.lucide class="w-5 h-5 mr-2" icon="FileText" />
                    Documents
                </x-base.button>
                <x-base.button as="a" href="{{ route('admin.maintenance.index') }}" variant="outline-primary" class="flex items-center justify-center py-4">
                    <x-base.lucide class="w-5 h-5 mr-2" icon="Wrench" />
                    Maintenance
                </x-base.button>
                <x-base.button as="a" href="{{ route('admin.vehicles.emergency-repairs.index') }}" variant="outline-danger" class="flex items-center justify-center py-4">
                    <x-base.lucide class="w-5 h-5 mr-2" icon="AlertTriangle" />
                    Emergency Repairs
                </x-base.button>
            </div>

            <!-- Vehicle Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="Truck" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-800">{{ $stats['total_vehicles'] }}</div>
                            <div class="text-xs text-slate-500">Total Vehicles</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-success/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-success">{{ $stats['active_vehicles'] }}</div>
                            <div class="text-xs text-slate-500">Active</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-danger/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-danger" icon="XCircle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-danger">{{ $stats['out_of_service'] }}</div>
                            <div class="text-xs text-slate-500">Out of Service</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-warning/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-warning" icon="AlertTriangle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-warning">{{ $stats['suspended'] }}</div>
                            <div class="text-xs text-slate-500">Suspended</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance & Emergency Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                <!-- Maintenance Stats -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium">Maintenance Overview</h3>
                            <a href="{{ route('admin.maintenance.index') }}" class="text-primary text-sm hover:underline">View All</a>
                        </div>
                    </div>
                    <div class="box-body p-5">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center p-3 bg-slate-50 rounded-lg">
                                <div class="text-2xl font-bold text-slate-800">{{ $maintenanceStats['total'] }}</div>
                                <div class="text-xs text-slate-500">Total</div>
                            </div>
                            <div class="text-center p-3 bg-success/10 rounded-lg">
                                <div class="text-2xl font-bold text-success">{{ $maintenanceStats['completed'] }}</div>
                                <div class="text-xs text-slate-500">Completed</div>
                            </div>
                            <div class="text-center p-3 bg-warning/10 rounded-lg">
                                <div class="text-2xl font-bold text-warning">{{ $maintenanceStats['pending'] }}</div>
                                <div class="text-xs text-slate-500">Pending</div>
                            </div>
                            <div class="text-center p-3 bg-danger/10 rounded-lg">
                                <div class="text-2xl font-bold text-danger">{{ $maintenanceStats['overdue'] }}</div>
                                <div class="text-xs text-slate-500">Overdue</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Repairs Stats -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium">Emergency Repairs</h3>
                            <a href="{{ route('admin.vehicles.emergency-repairs.index') }}" class="text-primary text-sm hover:underline">View All</a>
                        </div>
                    </div>
                    <div class="box-body p-5">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div class="text-center p-3 bg-slate-50 rounded-lg">
                                <div class="text-2xl font-bold text-slate-800">{{ $emergencyStats['total'] }}</div>
                                <div class="text-xs text-slate-500">Total</div>
                            </div>
                            <div class="text-center p-3 bg-success/10 rounded-lg">
                                <div class="text-2xl font-bold text-success">{{ $emergencyStats['completed'] }}</div>
                                <div class="text-xs text-slate-500">Completed</div>
                            </div>
                            <div class="text-center p-3 bg-danger/10 rounded-lg">
                                <div class="text-2xl font-bold text-danger">${{ number_format($emergencyStats['total_cost'], 2) }}</div>
                                <div class="text-xs text-slate-500">Total Cost</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents & Expiring Items -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
                <!-- Documents Stats -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium">Documents</h3>
                            <a href="{{ route('admin.vehicles-documents.index') }}" class="text-primary text-sm hover:underline">View All</a>
                        </div>
                    </div>
                    <div class="box-body p-5">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                <span class="text-slate-600">Total Documents</span>
                                <span class="font-bold text-slate-800">{{ $documentStats['total'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-warning/10 rounded-lg">
                                <span class="text-warning">Expiring Soon</span>
                                <span class="font-bold text-warning">{{ $documentStats['expiring_soon'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-danger/10 rounded-lg">
                                <span class="text-danger">Expired</span>
                                <span class="font-bold text-danger">{{ $documentStats['expired'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expiring Registrations -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <h3 class="text-lg font-medium">Expiring Soon (30 days)</h3>
                    </div>
                    <div class="box-body p-5">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-warning/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-5 h-5 text-warning" icon="CreditCard" />
                                    <span class="text-slate-600">Registrations</span>
                                </div>
                                <span class="font-bold text-warning">{{ $expiringRegistrations }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-warning/10 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <x-base.lucide class="w-5 h-5 text-warning" icon="ClipboardCheck" />
                                    <span class="text-slate-600">Annual Inspections</span>
                                </div>
                                <span class="font-bold text-warning">{{ $expiringInspections }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicles by Driver Type -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <h3 class="text-lg font-medium">By Driver Type</h3>
                    </div>
                    <div class="box-body p-5">
                        <div class="space-y-3">
                            @forelse($vehiclesByDriverType as $item)
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                    <span class="text-slate-600">{{ ucwords(str_replace('_', ' ', $item->driver_type ?? 'Unassigned')) }}</span>
                                    <span class="font-bold text-slate-800">{{ $item->count }}</span>
                                </div>
                            @empty
                                <div class="text-center text-slate-400 py-4">No data available</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicles by Type & Maintenance Trend -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                <!-- Vehicles by Type -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <h3 class="text-lg font-medium">Vehicles by Type</h3>
                    </div>
                    <div class="box-body p-5">
                        <div class="space-y-2">
                            @forelse($vehiclesByType as $item)
                                @php
                                    $percentage = $stats['total_vehicles'] > 0 ? ($item->count / $stats['total_vehicles']) * 100 : 0;
                                @endphp
                                <div class="flex items-center gap-3">
                                    <div class="w-24 text-sm text-slate-600 truncate">{{ $item->type ?? 'Unknown' }}</div>
                                    <div class="flex-1 h-4 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-primary rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <div class="w-12 text-right font-medium text-slate-800">{{ $item->count }}</div>
                                </div>
                            @empty
                                <div class="text-center text-slate-400 py-4">No data available</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Maintenance Trend -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <h3 class="text-lg font-medium">Maintenance Trend (Last 6 Months)</h3>
                    </div>
                    <div class="box-body p-5">
                        @php
                            $maxCount = collect($maintenanceTrend)->max('count') ?: 1;
                        @endphp
                        <div class="flex items-end justify-between gap-3" style="height: 180px;">
                            @foreach($maintenanceTrend as $item)
                                @php
                                    $height = $maxCount > 0 ? ($item['count'] / $maxCount) * 100 : 0;
                                    $heightPx = max(($height / 100) * 140, 8);
                                @endphp
                                <div class="flex-1 flex flex-col items-center justify-end h-full">
                                    <div class="text-xs font-medium text-slate-600 mb-1">{{ $item['count'] }}</div>
                                    <div class="w-full bg-gradient-to-t from-primary to-primary/70 rounded-t transition-all" 
                                        style="height: {{ $heightPx }}px;"></div>
                                    <div class="text-xs text-slate-500 mt-2 text-center whitespace-nowrap">{{ $item['month'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Recent Maintenance -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium">Recent Maintenance</h3>
                            <a href="{{ route('admin.maintenance.index') }}" class="text-primary text-sm hover:underline">View All</a>
                        </div>
                    </div>
                    <div class="box-body p-5">
                        @forelse($recentMaintenance as $maintenance)
                            <div class="flex items-center gap-3 py-3 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                                <div class="p-2 rounded-lg 
                                    @if($maintenance->status === 'completed') bg-success/10
                                    @elseif($maintenance->status === 'pending') bg-warning/10
                                    @else bg-slate-100 @endif">
                                    <x-base.lucide class="w-4 h-4 
                                        @if($maintenance->status === 'completed') text-success
                                        @elseif($maintenance->status === 'pending') text-warning
                                        @else text-slate-500 @endif" icon="Wrench" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-slate-800 truncate">{{ $maintenance->service_type ?? 'Maintenance' }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $maintenance->vehicle->company_unit_number ?? 'N/A' }} - {{ $maintenance->vehicle->make ?? '' }} {{ $maintenance->vehicle->model ?? '' }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        @if($maintenance->status === 'completed') bg-success/10 text-success
                                        @elseif($maintenance->status === 'pending') bg-warning/10 text-warning
                                        @else bg-slate-100 text-slate-600 @endif">
                                        {{ ucfirst($maintenance->status ?? 'N/A') }}
                                    </span>
                                    <div class="text-xs text-slate-400 mt-1">{{ $maintenance->created_at?->format('M d') }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-slate-400 py-8">
                                <x-base.lucide class="w-12 h-12 mx-auto mb-2 text-slate-300" icon="Wrench" />
                                <p>No recent maintenance records</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Emergency Repairs -->
                <div class="box box--stacked">
                    <div class="box-header p-5 border-b border-slate-200/60">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium">Recent Emergency Repairs</h3>
                            <a href="{{ route('admin.vehicles.emergency-repairs.index') }}" class="text-primary text-sm hover:underline">View All</a>
                        </div>
                    </div>
                    <div class="box-body p-5">
                        @forelse($recentEmergencyRepairs as $repair)
                            <div class="flex items-center gap-3 py-3 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                                <div class="p-2 rounded-lg bg-danger/10">
                                    <x-base.lucide class="w-4 h-4 text-danger" icon="AlertTriangle" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-slate-800 truncate">{{ $repair->repair_name ?? ($repair->description ?? 'Emergency Repair') }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $repair->vehicle->company_unit_number ?? 'N/A' }} - {{ $repair->vehicle->carrier->name ?? '' }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium text-danger">${{ number_format($repair->cost ?? 0, 2) }}</div>
                                    <div class="text-xs text-slate-400">{{ $repair->created_at?->format('M d') }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-slate-400 py-8">
                                <x-base.lucide class="w-12 h-12 mx-auto mb-2 text-slate-300" icon="AlertTriangle" />
                                <p>No recent emergency repairs</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize TomSelect for carrier filter
        if (typeof TomSelect !== 'undefined' && document.getElementById('carrier-filter')) {
            new TomSelect('#carrier-filter', {
                placeholder: 'Select carrier',
                allowEmptyOption: true
            });
        }
    });
</script>
@endpush
