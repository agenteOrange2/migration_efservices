@extends('../themes/' . $activeTheme)
@section('title', 'Vehicle Maintenance')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Maintenance', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Maintenance Records                
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button as="a" href="{{ route('carrier.maintenance.reports') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="BarChart2" />
                        reports
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.maintenance.calendar') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Calendar" />
                        View Calendar
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.maintenance.create') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="PlusCircle" />
                        Create Maintenance
                    </x-base.button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="w-12 h-12 flex items-center justify-center rounded-full bg-danger/10 mr-4">
                            <x-base.lucide class="w-6 h-6 text-danger" icon="AlertTriangle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-danger">{{ $overdueCount }}</div>
                            <div class="text-slate-500 text-sm">Overdue</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="w-12 h-12 flex items-center justify-center rounded-full bg-warning/10 mr-4">
                            <x-base.lucide class="w-6 h-6 text-warning" icon="Clock" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-warning">{{ $upcomingCount }}</div>
                            <div class="text-slate-500 text-sm">Upcoming</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="w-12 h-12 flex items-center justify-center rounded-full bg-primary/10 mr-4">
                            <x-base.lucide class="w-6 h-6 text-primary" icon="Wrench" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-primary">{{ $pendingCount }}</div>
                            <div class="text-slate-500 text-sm">Pending</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="flex items-center">
                        <div class="w-12 h-12 flex items-center justify-center rounded-full bg-success/10 mr-4">
                            <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-success">{{ $completedCount }}</div>
                            <div class="text-slate-500 text-sm">Completed</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Maintenance Section -->
            @if($overdueMaintenance->count() > 0)
                <div class="intro-y box p-5 mt-5 border-l-4 border-danger">
                    <div class="flex items-center">
                        <x-base.lucide class="w-5 h-5 text-danger mr-2" icon="AlertTriangle" />
                        <h2 class="text-lg font-medium truncate text-danger">Overdue Maintenance ({{ $overdueCount }})</h2>
                    </div>
                    <div class="mt-4">
                        @foreach ($overdueMaintenance as $maintenance)
                            <div class="intro-y">
                                <div class="flex items-center py-3 border-b border-slate-200 dark:border-darkmode-400">
                                    <div>
                                        <div class="text-danger font-medium">
                                            {{ Carbon\Carbon::parse($maintenance->next_service_date)->format('m/d/Y') }}
                                            <span class="text-xs ml-1">({{ floor(Carbon\Carbon::parse($maintenance->next_service_date)->diffInDays(now())) }} days overdue)</span>
                                        </div>
                                        <div class="mt-1">{{ $maintenance->service_tasks }} - {{ $maintenance->vehicle->unit ?? '' }} {{ $maintenance->vehicle->make }} {{ $maintenance->vehicle->model }}</div>
                                        <div class="text-xs text-slate-500">{{ $maintenance->vendor_mechanic }}</div>
                                    </div>
                                    <div class="flex items-center ml-auto gap-2">
                                        <a href="{{ route('carrier.maintenance.show', $maintenance->id) }}" class="btn btn-sm btn-outline-primary">
                                            <x-base.lucide class="w-4 h-4 mr-1" icon="Eye" /> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Upcoming Maintenance Section -->
            <div class="intro-y box p-5 mt-5">
                <div class="flex items-center">
                    <h2 class="text-lg font-medium truncate mr-5">Upcoming Maintenance</h2>
                </div>
                <div class="mt-5">
                    <div class="flex flex-col sm:flex-row sm:items-center">
                        <div class="mr-auto">
                            <div class="flex items-center">
                                <div class="text-base font-bold">{{ Carbon\Carbon::now()->translatedFormat('F Y') }}</div>
                            </div>
                            <div class="text-slate-500 mt-1">{{ $currentMonthCount }} scheduled maintenance</div>
                        </div>
                        <div class="flex">
                            <a href="{{ route('carrier.maintenance.calendar') }}"
                                class="btn btn-outline-secondary w-32 mt-5 sm:mt-0 sm:ml-1">
                                View Calendar
                            </a>
                        </div>
                    </div>
                    <div class="mt-5">
                        @forelse ($upcomingMaintenance as $maintenance)
                            <div class="intro-y">
                                <div class="flex items-center py-4 border-b border-slate-200 dark:border-darkmode-400">
                                    <div>
                                        <div class="text-slate-500 font-medium">{{ Carbon\Carbon::parse($maintenance->next_service_date)->format('d M') }}</div>
                                        <div class="mt-1">{{ $maintenance->service_tasks }} - {{ $maintenance->vehicle->unit ?? '' }} {{ $maintenance->vehicle->make }} {{ $maintenance->vehicle->model }}</div>
                                        <div class="text-xs text-slate-500">{{ $maintenance->vendor_mechanic }}</div>
                                    </div>
                                    <div class="flex items-center ml-auto">
                                        <div class="flex items-center justify-center {{ Carbon\Carbon::parse($maintenance->next_service_date)->isPast() ? 'bg-danger/20 text-danger' : 'bg-warning/20 text-warning' }} rounded-full p-2">
                                            <x-base.lucide class="w-4 h-4" icon="{{ Carbon\Carbon::parse($maintenance->next_service_date)->isPast() ? 'AlertTriangle' : 'Clock' }}" />
                                        </div>
                                        <a href="{{ route('carrier.maintenance.show', $maintenance->id) }}" class="flex items-center ml-3 text-primary">
                                            <x-base.lucide class="w-4 h-4 mr-1" icon="Eye" /> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-slate-500">
                                No upcoming maintenance scheduled.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
