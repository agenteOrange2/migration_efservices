@extends('../themes/' . $activeTheme)
@section('title', 'Carrier HOS Details')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Hours of Service', 'url' => route('admin.hos.dashboard')],
        ['label' => $carrier->name, 'active' => true],
    ];
@endphp
@section('subcontent')
    <div>
        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="Building2" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $carrier->name }}</h1>
                        <p class="text-slate-600">Hours of Service details and driver monitoring</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('admin.hos.dashboard') }}" class="w-full sm:w-auto"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                        Back to Dashboard
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Configuration Card -->
        <div class="box box--stacked p-5 mb-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-info/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-info" icon="Settings" />
                    </div>
                    <div>
                        <div class="text-sm text-slate-500">HOS Configuration</div>
                        <div class="font-semibold text-slate-800">
                            {{ $config->max_driving_hours }}h driving / {{ $config->max_duty_hours }}h duty
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-warning/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-warning" icon="Bell" />
                    </div>
                    <div>
                        <div class="text-sm text-slate-500">Warning Threshold</div>
                        <div class="font-semibold text-slate-800">{{ $config->warning_threshold_minutes }} minutes before limit</div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-success/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-success" icon="Users" />
                    </div>
                    <div>
                        <div class="text-sm text-slate-500">Active Drivers</div>
                        <div class="font-semibold text-slate-800">{{ $driverSummaries->count() }} drivers</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drivers Table -->
        <div class="box box--stacked">
            <div class="box-header flex items-center justify-between p-5 border-b border-slate-200/60">
                <h2 class="text-lg font-semibold text-slate-800">Active Drivers</h2>
            </div>
            <div class="box-body p-5">
                @if($driverSummaries->isEmpty())
                    <div class="text-center py-16">
                        <x-base.lucide class="w-20 h-20 mx-auto text-slate-300 mb-4" icon="Users" />
                        <h3 class="text-xl font-semibold text-slate-800 mb-2">No Active Drivers</h3>
                        <p class="text-slate-500">This carrier has no active drivers at the moment.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-slate-500 border-b border-slate-200/60">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Driver</th>
                                    <th class="px-4 py-3 font-medium text-center">Current Status</th>
                                    <th class="px-4 py-3 font-medium text-center">Driving Today</th>
                                    <th class="px-4 py-3 font-medium text-center">On Duty Today</th>
                                    <th class="px-4 py-3 font-medium text-center">Remaining</th>
                                    <th class="px-4 py-3 font-medium text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($driverSummaries as $summary)
                                    <tr class="border-b border-slate-200/60 hover:bg-slate-50 @if($summary['remaining']['is_driving_exceeded'] || $summary['remaining']['is_duty_exceeded']) bg-danger/5 @endif">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0">
                                                    <x-base.lucide class="w-5 h-5 text-slate-400" icon="User" />
                                                </div>
                                                <div>
                                                    <div class="font-medium text-slate-800">{{ $summary['driver']->full_name }}</div>
                                                    <div class="text-xs text-slate-500">{{ $summary['driver']->phone ?? 'No phone' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if(str_contains($summary['current_status'], 'Driving'))
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Car" />
                                                    {{ $summary['current_status'] }}
                                                </span>
                                            @elseif(str_contains($summary['current_status'], 'On Duty'))
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Briefcase" />
                                                    {{ $summary['current_status'] }}
                                                </span>
                                            @else
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Moon" />
                                                    {{ $summary['current_status'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="font-semibold text-slate-800">{{ $summary['totals']['driving_formatted'] }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="font-semibold text-slate-800">{{ $summary['totals']['on_duty_formatted'] }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if($summary['remaining']['remaining_driving_minutes'] < 60)
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-danger/10 text-danger">
                                                    {{ $summary['remaining']['remaining_driving_formatted'] }}
                                                </span>
                                            @elseif($summary['remaining']['remaining_driving_minutes'] < 120)
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                                    {{ $summary['remaining']['remaining_driving_formatted'] }}
                                                </span>
                                            @else
                                                <span class="text-slate-600">{{ $summary['remaining']['remaining_driving_formatted'] }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <a href="{{ route('admin.hos.driver.log', $summary['driver']->id) }}" 
                                                class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                                title="View Driver Log">
                                                <x-base.lucide class="w-4 h-4" icon="Eye" />
                                            </a>
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
@endsection
