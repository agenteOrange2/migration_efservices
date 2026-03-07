@extends('../themes/' . $activeTheme)
@section('title', 'Reports Dashboard')

@php
$breadcrumbLinks = [
    ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
    ['label' => 'Reports', 'active' => true],
];
@endphp

@section('subcontent')
<div class="grid grid-cols-12 gap-x-6 gap-y-10">
    <div class="col-span-12">
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Reports Dashboard
            </div>
        </div>
        
        <div class="mt-3.5">
            <!-- Welcome Message -->
            <div class="box box--stacked p-5 mb-5">
                <h2 class="text-xl font-medium mb-2">Welcome to Your Reports Dashboard</h2>
                <p class="text-slate-600">
                    View comprehensive reports about your operations including drivers, vehicles, accidents, maintenance, and more.
                </p>
            </div>

            <!-- Summary Statistics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-5">
                
                <!-- Drivers Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                            <x-base.lucide class="h-6 w-6 text-primary" icon="Users" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Drivers</div>
                            <div class="mt-1 flex items-baseline">
                                <div class="text-2xl font-medium">{{ $stats['drivers']['total'] ?? 0 }}</div>
                                <div class="ml-2 text-xs text-slate-500">total</div>
                            </div>
                            <div class="mt-1 text-xs">
                                <span class="text-success">{{ $stats['drivers']['active'] ?? 0 }} active</span>
                                <span class="text-slate-400 mx-1">•</span>
                                <span class="text-slate-500">{{ $stats['drivers']['recent'] ?? 0 }} new</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('carrier.reports.drivers') }}" class="text-primary text-sm hover:underline flex items-center">
                            View Driver Report
                            <x-base.lucide class="ml-1 h-3 w-3" icon="ArrowRight" />
                        </a>
                    </div>
                </div>

                <!-- Vehicles Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success/10">
                            <x-base.lucide class="h-6 w-6 text-success" icon="Truck" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Vehicles</div>
                            <div class="mt-1 flex items-baseline">
                                <div class="text-2xl font-medium">{{ $stats['vehicles']['total'] ?? 0 }}</div>
                                <div class="ml-2 text-xs text-slate-500">total</div>
                            </div>
                            <div class="mt-1 text-xs">
                                <span class="text-success">{{ $stats['vehicles']['active'] ?? 0 }} active</span>
                                <span class="text-slate-400 mx-1">•</span>
                                <span class="text-slate-500">{{ $stats['vehicles']['recent'] ?? 0 }} new</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('carrier.reports.vehicles') }}" class="text-primary text-sm hover:underline flex items-center">
                            View Vehicle Report
                            <x-base.lucide class="ml-1 h-3 w-3" icon="ArrowRight" />
                        </a>
                    </div>
                </div>

                <!-- Accidents Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning/10">
                            <x-base.lucide class="h-6 w-6 text-warning" icon="AlertTriangle" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Accidents</div>
                            <div class="mt-1 flex items-baseline">
                                <div class="text-2xl font-medium">{{ $stats['accidents']['total'] ?? 0 }}</div>
                                <div class="ml-2 text-xs text-slate-500">total</div>
                            </div>
                            <div class="mt-1 text-xs">
                                <span class="text-warning">{{ $stats['accidents']['recent'] ?? 0 }} recent</span>
                                <span class="text-slate-400 mx-1">•</span>
                                <span class="text-slate-500">last 30 days</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('carrier.reports.accidents') }}" class="text-primary text-sm hover:underline flex items-center">
                            View Accident Report
                            <x-base.lucide class="ml-1 h-3 w-3" icon="ArrowRight" />
                        </a>
                    </div>
                </div>

                <!-- Medical Records Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-info/10">
                            <x-base.lucide class="h-6 w-6 text-info" icon="FileText" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Medical Records</div>
                            <div class="mt-1 flex items-baseline">
                                <div class="text-2xl font-medium">{{ $stats['medical_records']['total'] ?? 0 }}</div>
                                <div class="ml-2 text-xs text-slate-500">total</div>
                            </div>
                            <div class="mt-1 text-xs">
                                @if(($stats['medical_records']['expiring_soon'] ?? 0) > 0)
                                    <span class="text-warning">{{ $stats['medical_records']['expiring_soon'] }} expiring soon</span>
                                @elseif(($stats['medical_records']['expired'] ?? 0) > 0)
                                    <span class="text-danger">{{ $stats['medical_records']['expired'] }} expired</span>
                                @else
                                    <span class="text-success">All current</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('carrier.reports.medical-records') }}" class="text-primary text-sm hover:underline flex items-center">
                            View Medical Records Report
                            <x-base.lucide class="ml-1 h-3 w-3" icon="ArrowRight" />
                        </a>
                    </div>
                </div>

                <!-- Licenses Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-info/10">
                            <x-base.lucide class="h-6 w-6 text-info" icon="CreditCard" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Licenses</div>
                            <div class="mt-1 flex items-baseline">
                                <div class="text-2xl font-medium">{{ $stats['licenses']['total'] ?? 0 }}</div>
                                <div class="ml-2 text-xs text-slate-500">total</div>
                            </div>
                            <div class="mt-1 text-xs">
                                @if(($stats['licenses']['expiring_soon'] ?? 0) > 0)
                                    <span class="text-danger">{{ $stats['licenses']['expiring_soon'] }} expiring soon</span>
                                @else
                                    <span class="text-success">All current</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('carrier.reports.licenses') }}" class="text-primary text-sm hover:underline flex items-center">
                            View License Report
                            <x-base.lucide class="ml-1 h-3 w-3" icon="ArrowRight" />
                        </a>
                    </div>
                </div>

                <!-- Maintenance Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-pending/10">
                            <x-base.lucide class="h-6 w-6 text-pending" icon="Settings" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Maintenance</div>
                            <div class="mt-1 flex items-baseline">
                                <div class="text-2xl font-medium">{{ $stats['maintenance']['total'] ?? 0 }}</div>
                                <div class="ml-2 text-xs text-slate-500">records</div>
                            </div>
                            <div class="mt-1 text-xs">
                                <span class="text-slate-500">{{ $stats['maintenance']['recent'] ?? 0 }} recent</span>
                                <span class="text-slate-400 mx-1">•</span>
                                <span class="text-slate-500">${{ number_format($stats['maintenance']['total_cost'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('carrier.reports.maintenance') }}" class="text-primary text-sm hover:underline flex items-center">
                            View Maintenance Report
                            <x-base.lucide class="ml-1 h-3 w-3" icon="ArrowRight" />
                        </a>
                    </div>
                </div>

                <!-- Repairs Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-danger/10">
                            <x-base.lucide class="h-6 w-6 text-danger" icon="wrench" />
                        </div>
                        <div class="ml-4 flex-1">
                            <div class="text-xs text-slate-500 uppercase">Repairs</div>
                            <div class="mt-1 flex items-baseline">
                                <div class="text-2xl font-medium">{{ $stats['repairs']['total'] ?? 0 }}</div>
                                <div class="ml-2 text-xs text-slate-500">records</div>
                            </div>
                            <div class="mt-1 text-xs">
                                <span class="text-slate-500">{{ $stats['repairs']['recent'] ?? 0 }} recent</span>
                                <span class="text-slate-400 mx-1">•</span>
                                <span class="text-slate-500">${{ number_format($stats['repairs']['total_cost'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('carrier.reports.repairs') }}" class="text-primary text-sm hover:underline flex items-center">
                            View Repair Report
                            <x-base.lucide class="ml-1 h-3 w-3" icon="ArrowRight" />
                        </a>
                    </div>
                </div>

            </div>

            <!-- Quick Links Section -->
            <div class="box box--stacked p-5">
                <h3 class="text-lg font-medium mb-4">Available Reports</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    
                    <a href="{{ route('carrier.reports.drivers') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-primary mr-3" icon="Users" />
                        <div>
                            <div class="font-medium">Driver Reports</div>
                            <div class="text-xs text-slate-500">View driver status, licenses, and compliance</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.vehicles') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-success mr-3" icon="Truck" />
                        <div>
                            <div class="font-medium">Vehicle Reports</div>
                            <div class="text-xs text-slate-500">Track fleet status and registrations</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.accidents') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-warning mr-3" icon="AlertTriangle" />
                        <div>
                            <div class="font-medium">Accident Reports</div>
                            <div class="text-xs text-slate-500">Review safety incidents and trends</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.medical-records') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-info mr-3" icon="FileText" />
                        <div>
                            <div class="font-medium">Medical Records Reports</div>
                            <div class="text-xs text-slate-500">Track driver medical certifications and expirations</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.licenses') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-info mr-3" icon="CreditCard" />
                        <div>
                            <div class="font-medium">License Reports</div>
                            <div class="text-xs text-slate-500">Monitor driver license status and expirations</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.maintenance') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-pending mr-3" icon="Wrench" />
                        <div>
                            <div class="font-medium">Maintenance Reports</div>
                            <div class="text-xs text-slate-500">Track maintenance schedules and costs</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.repairs') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-danger mr-3" icon="settings" />
                        <div>
                            <div class="font-medium">Repair Reports</div>
                            <div class="text-xs text-slate-500">Review repair history and expenses</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.monthly') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-primary mr-3" icon="Calendar" />
                        <div>
                            <div class="font-medium">Monthly Summary</div>
                            <div class="text-xs text-slate-500">View monthly trends and aggregated data</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.trips') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-blue-500 mr-3" icon="MapPin" />
                        <div>
                            <div class="font-medium">Trip Reports</div>
                            <div class="text-xs text-slate-500">View all trips and routes</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.hos') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-cyan-500 mr-3" icon="Clock" />
                        <div>
                            <div class="font-medium">HOS Reports</div>
                            <div class="text-xs text-slate-500">Hours of Service compliance</div>
                        </div>
                    </a>

                    <a href="{{ route('carrier.reports.violations') }}" class="flex items-center p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                        <x-base.lucide class="h-5 w-5 text-danger mr-3" icon="AlertOctagon" />
                        <div>
                            <div class="font-medium">Violations Reports</div>
                            <div class="text-xs text-slate-500">HOS violations tracking</div>
                        </div>
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
