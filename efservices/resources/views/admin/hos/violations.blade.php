@extends('../themes/' . $activeTheme)
@section('title', 'HOS Violations')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Hours of Service', 'url' => route('admin.hos.dashboard')],
        ['label' => 'Violations', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div>
        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-danger/10 rounded-xl border border-danger/20">
                        <x-base.lucide class="w-8 h-8 text-danger" icon="AlertTriangle" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">HOS Violations</h1>
                        <p class="text-slate-600">Monitor and track HOS violations across all carriers</p>
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-danger/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-danger" icon="AlertCircle" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Total Violations</div>
                        <div class="text-2xl font-bold text-danger">{{ $totalViolations }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-warning/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-warning" icon="Car" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Driving Limit Exceeded</div>
                        <div class="text-2xl font-bold text-warning">{{ $drivingViolations }}</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-info/10 rounded-xl">
                        <x-base.lucide class="w-6 h-6 text-info" icon="Briefcase" />
                    </div>
                    <div>
                        <div class="text-slate-500 text-sm">Duty Limit Exceeded</div>
                        <div class="text-2xl font-bold text-info">{{ $dutyViolations }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked mb-5">
            <div class="box-body p-5">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">From Date</label>
                        <x-base.litepicker id="start_date" name="start_date" value="{{ $startDate }}" class="w-full" placeholder="Select Date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">To Date</label>
                        <x-base.litepicker id="end_date" name="end_date" value="{{ $endDate }}" class="w-full" placeholder="Select Date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Carrier</label>
                        <select name="carrier_id" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3">
                            <option value="">All Carriers</option>
                            @foreach($carriers as $carrier)
                                <option value="{{ $carrier->id }}" @if($selectedCarrierId == $carrier->id) selected @endif>
                                    {{ $carrier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <x-base.button variant="primary" type="submit" class="flex items-center">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="Search" />
                            Filter
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.hos.violations') }}" variant="outline-secondary">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="X" />
                            Clear
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Violations Table -->
        <div class="box box--stacked">            
            <div class="box-body p-5">
                @if($violations->isEmpty())
                    <div class="text-center py-16">
                        <x-base.lucide class="w-20 h-20 mx-auto text-success/50 mb-4" icon="CheckCircle" />
                        <h3 class="text-xl font-semibold text-slate-800 mb-2">No Violations Found</h3>
                        <p class="text-slate-500">Great news! No HOS violations in the selected period.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-slate-500 border-b border-slate-200/60">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Date</th>
                                    <th class="px-4 py-3 font-medium">Carrier</th>
                                    <th class="px-4 py-3 font-medium">Driver</th>
                                    <th class="px-4 py-3 font-medium text-center">Violation Type</th>
                                    <th class="px-4 py-3 font-medium text-center">Exceeded By</th>
                                    <th class="px-4 py-3 font-medium text-center">Status</th>
                                    <th class="px-4 py-3 font-medium text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($violations as $violation)
                                    <tr class="border-b border-slate-200/60 hover:bg-slate-50">
                                        <td class="px-4 py-4">
                                            <div class="font-medium text-slate-800">{{ $violation->violation_date->format('M j, Y') }}</div>
                                            <div class="text-xs text-slate-500">{{ $violation->violation_date->format('l') }}</div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                                {{ $violation->carrier->name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                                                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="User" />
                                                </div>
                                                <div>
                                                    <div class="font-medium text-slate-800">{{ $violation->driver->full_name ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if($violation->violation_type === 'driving_limit_exceeded')
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Car" />
                                                    Driving Limit
                                                </span>
                                            @else
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-info/10 text-info">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Briefcase" />
                                                    Duty Limit
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="font-semibold text-danger">{{ $violation->formatted_hours_exceeded ?? 'N/A' }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if($violation->acknowledged)
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                                    Acknowledged
                                                </span>
                                            @else
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('admin.hos.violations.show', $violation) }}" 
                                                    class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                                    title="View Details">
                                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                </a>
                                                <a href="{{ route('admin.hos.driver.log', $violation->user_driver_detail_id) }}" 
                                                    class="inline-flex items-center justify-center w-8 h-8 text-info hover:bg-info/10 rounded-lg transition-colors"
                                                    title="View Driver Log">
                                                    <x-base.lucide class="w-4 h-4" icon="Clock" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-5">
                        {{ $violations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
