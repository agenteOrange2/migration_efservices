@extends('../themes/' . $activeTheme)
@section('title', 'Repairs Management')
@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Repairs Management', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Header -->
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-danger/10 rounded-xl border border-danger/20">
                            <x-base.lucide class="w-8 h-8 text-danger" icon="AlertTriangle" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Repairs Management</h1>
                            <p class="text-slate-600">Manage repairs for your vehicle</p>
                        </div>
                    </div>
                    @if($vehicle)
                    <x-base.button as="a" href="{{ route('driver.emergency-repairs.create') }}" variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="Plus" />
                        New Repair
                    </x-base.button>
                    @endif
                </div>
            </div>

            @if(!$vehicle)
            <!-- No Vehicle Assigned -->
            <div class="box box--stacked p-8 text-center">
                <x-base.lucide class="w-16 h-16 mx-auto text-slate-400 mb-4" icon="Truck" />
                <h3 class="text-lg font-semibold text-slate-700 mb-2">No Vehicle Assigned</h3>
                <p class="text-slate-500">You need a vehicle assignment to manage repairs.</p>
            </div>
            @else
            <!-- Vehicle Info -->
            <div class="box box--stacked p-5 mb-5">
                <div class="flex items-center gap-3">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="Truck" />
                    <div>
                        <h3 class="font-semibold text-slate-800">
                            @if($vehicle->company_unit_number)
                                {{ $vehicle->company_unit_number }} - 
                            @endif
                            {{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}
                        </h3>
                        <p class="text-sm text-slate-500">VIN: {{ $vehicle->vin ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Total Repairs</p>
                            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['total'] }}</p>
                            <p class="text-xs text-slate-500 mt-1">${{ number_format($stats['total_cost'], 2) }}</p>
                        </div>
                        <div class="p-3 bg-slate-100 rounded-lg">
                            <x-base.lucide class="w-6 h-6 text-slate-600" icon="Wrench" />
                        </div>
                    </div>
                </div>

                <!-- Pending -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Pending</p>
                            <p class="text-2xl font-bold text-warning mt-1">{{ $stats['pending'] }}</p>
                        </div>
                        <div class="p-3 bg-warning/10 rounded-lg">
                            <x-base.lucide class="w-6 h-6 text-warning" icon="Clock" />
                        </div>
                    </div>
                </div>

                <!-- In Progress -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">In Progress</p>
                            <p class="text-2xl font-bold text-primary mt-1">{{ $stats['in_progress'] }}</p>
                        </div>
                        <div class="p-3 bg-primary/10 rounded-lg">
                            <x-base.lucide class="w-6 h-6 text-primary" icon="Settings" />
                        </div>
                    </div>
                </div>

                <!-- Completed -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Completed</p>
                            <p class="text-2xl font-bold text-success mt-1">{{ $stats['completed'] }}</p>
                        </div>
                        <div class="p-3 bg-success/10 rounded-lg">
                            <x-base.lucide class="w-6 h-6 text-success" icon="CheckCircle" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="box box--stacked p-5 mb-5">
                <form method="GET" action="{{ route('driver.emergency-repairs.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-base.form-label for="status">Status</x-base.form-label>
                        <select id="status" name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3">
                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>

                    <div>
                        <x-base.form-label for="search">Search</x-base.form-label>
                        <x-base.form-input id="search" name="search" type="text" class="w-full" 
                            placeholder="Search repairs..." value="{{ request('search') }}" />
                    </div>

                    <div class="flex items-end gap-2">
                        <x-base.button type="submit" variant="primary" class="w-full">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Search" />
                            Filter
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('driver.emergency-repairs.index') }}" variant="outline-secondary">
                            Clear
                        </x-base.button>
                    </div>
                </form>
            </div>

            <!-- Repairs List -->
            <div class="box box--stacked">
                @if($emergencyRepairs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-200/60 dark:border-darkmode-400">
                                <th class="px-5 py-3 font-medium text-slate-600">Repair Name</th>
                                <th class="px-5 py-3 font-medium text-slate-600">Date</th>
                                <th class="px-5 py-3 font-medium text-slate-600">Cost</th>
                                <th class="px-5 py-3 font-medium text-slate-600">Status</th>
                                <th class="px-5 py-3 font-medium text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($emergencyRepairs as $repair)
                            <tr class="border-b border-slate-200/60 dark:border-darkmode-400 hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ $repair->repair_name }}</div>
                                    @if($repair->description)
                                    <div class="text-xs text-slate-500 mt-1">{{ Str::limit($repair->description, 50) }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ \Carbon\Carbon::parse($repair->repair_date)->format('M d, Y') }}
                                </td>
                                <td class="px-5 py-4 text-slate-600 font-medium">
                                    ${{ number_format($repair->cost, 2) }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($repair->status == 'completed')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                            Completed
                                        </span>
                                    @elseif($repair->status == 'in_progress')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                            In Progress
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex gap-2">
                                        <x-base.button as="a" href="{{ route('driver.emergency-repairs.show', $repair->id) }}" 
                                            variant="outline-secondary" size="sm">
                                            <x-base.lucide class="w-4 h-4" icon="Eye" />
                                        </x-base.button>
                                        <x-base.button as="a" href="{{ route('driver.emergency-repairs.edit', $repair->id) }}" 
                                            variant="outline-primary" size="sm">
                                            <x-base.lucide class="w-4 h-4" icon="Edit" />
                                        </x-base.button>
                                        <form action="{{ route('driver.emergency-repairs.destroy', $repair->id) }}" method="POST" 
                                            onsubmit="return confirm('Are you sure you want to delete this repair?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <x-base.button type="submit" variant="outline-danger" size="sm">
                                                <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                            </x-base.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-5">
                    {{ $emergencyRepairs->links() }}
                </div>
                @else
                <div class="p-8 text-center">
                    <x-base.lucide class="w-12 h-12 mx-auto text-slate-400 mb-3" icon="Wrench" />
                    <p class="text-slate-600">No repairs found.</p>
                    <x-base.button as="a" href="{{ route('driver.emergency-repairs.create') }}" variant="primary" class="mt-4">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="Plus" />
                        Create First Repair
                    </x-base.button>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>
@endsection

