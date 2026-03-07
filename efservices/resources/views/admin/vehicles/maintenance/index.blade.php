@extends('../themes/' . $activeTheme)
@section('title', 'Vehicle Maintenance Records')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Vehicles', 'url' => route('admin.vehicles.index')],
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
                            <x-base.lucide class="w-8 h-8 text-primary" icon="Wrench" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">Maintenance Records</h1>
                            <p class="text-slate-600">Manage and track maintenance records</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        <x-base.button as="a" href="{{ route('admin.maintenance.reports') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="BarChart2" />
                        reports
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.maintenance.calendar') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Calendar" />
                        Calendar
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.maintenance.create') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="PlusCircle" />
                        New Maintenance
                    </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="p-3 bg-danger/10 rounded-xl mr-4">
                            <x-base.lucide class="w-6 h-6 text-danger" icon="AlertTriangle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-danger">{{ $overdueCount }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">Overdue</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="p-3 bg-warning/10 rounded-xl mr-4">
                            <x-base.lucide class="w-6 h-6 text-warning" icon="Clock" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-warning">{{ $upcomingCount }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">Upcoming (15 days)</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="p-3 bg-primary/10 rounded-xl mr-4">
                            <x-base.lucide class="w-6 h-6 text-primary" icon="Wrench" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-primary">{{ $pendingCount }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">Pending</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="p-3 bg-success/10 rounded-xl mr-4">
                            <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-success">{{ $completedCount }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">Completed</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Maintenances -->
            @if($overdueMaintenances->count() > 0)
            <div class="box box--stacked mb-6">
                <div class="box-header bg-danger/5 p-5 border-b border-danger/20">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-danger/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-danger" icon="AlertTriangle" />
                        </div>
                        <div class="box-title font-medium text-danger">Overdue Maintenances</div>
                        <span class="ml-2 bg-danger text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $overdueCount }}</span>
                    </div>
                </div>
                <div class="box-body p-5">
                    @foreach ($overdueMaintenances as $overdue)
                        <div class="flex items-center py-3 {{ !$loop->last ? 'border-b border-slate-200' : '' }}">
                            <div class="flex-1">
                                <div class="font-medium">{{ $overdue->service_tasks }}</div>
                                <div class="text-sm text-slate-500">
                                    {{ $overdue->vehicle->make ?? '' }} {{ $overdue->vehicle->model ?? '' }}
                                    {{ $overdue->vehicle->company_unit_number ? '(' . $overdue->vehicle->company_unit_number . ')' : '' }}
                                </div>
                                <div class="text-xs text-danger mt-1">
                                    Due: {{ $overdue->next_service_date->format('m/d/Y') }}
                                    ({{ floor($overdue->next_service_date->diffInDays(now())) }} days overdue)
                                </div>
                            </div>
                            <a href="{{ route('admin.maintenance-system.show', $overdue->id) }}"
                                class="btn btn-sm btn-outline-primary ml-3">
                                <x-base.lucide class="w-4 h-4 mr-1" icon="Eye" /> View
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Upcoming Maintenances -->
            <div class="box box--stacked mb-6">
                <div class="box-header bg-slate-50 p-5 border-b border-slate-200/60">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-warning/10 rounded-lg">
                                <x-base.lucide class="w-5 h-5 text-warning" icon="Clock" />
                            </div>
                            <div class="box-title font-medium">Upcoming Maintenance</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-slate-500">{{ Carbon\Carbon::now()->translatedFormat('F Y') }} &mdash; {{ $totalScheduled }} scheduled</span>
                            <a href="{{ route('admin.maintenance.calendar') }}"
                                class="btn btn-sm btn-outline-secondary">
                                <x-base.lucide class="w-4 h-4 mr-1" icon="Calendar" /> Calendar
                            </a>
                        </div>
                    </div>
                </div>
                <div class="box-body p-5">
                    @forelse ($upcomingMaintenances as $maintenance)
                        <div class="flex items-center py-3 {{ !$loop->last ? 'border-b border-slate-200' : '' }}">
                            <div class="flex-1">
                                <div class="font-medium">{{ $maintenance->service_tasks }}</div>
                                <div class="text-sm text-slate-500">
                                    {{ $maintenance->vehicle->make ?? '' }} {{ $maintenance->vehicle->model ?? '' }}
                                    {{ $maintenance->vehicle->company_unit_number ? '(' . $maintenance->vehicle->company_unit_number . ')' : '' }}
                                </div>
                                <div class="text-xs text-warning mt-1">
                                    Due: {{ $maintenance->next_service_date->format('m/d/Y') }}
                                    (in {{ floor(now()->diffInDays($maintenance->next_service_date)) }} days)
                                </div>
                            </div>
                            <a href="{{ route('admin.maintenance-system.show', $maintenance->id) }}"
                                class="btn btn-sm btn-outline-primary ml-3">
                                <x-base.lucide class="w-4 h-4 mr-1" icon="Eye" /> View
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-500">
                            <x-base.lucide class="h-10 w-10 mx-auto text-slate-300" icon="CheckCircle" />
                            <div class="mt-2">No upcoming maintenances scheduled.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- All Maintenance Records (Livewire) -->
            <div class="box box--stacked">
                <div class="box-header bg-slate-50 p-5 border-b border-slate-200/60">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="List" />
                        </div>
                        <div class="box-title font-medium">All Maintenance Records</div>
                    </div>
                </div>
                <livewire:admin.vehicle.maintenance-list />
            </div>


        </div>
    </div>
@endsection
