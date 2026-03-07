@extends('../themes/' . $activeTheme)
@section('title', 'Vehicle Maintenance - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'Maintenance', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Header -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Vehicle Maintenance</h1>
            <p class="text-slate-500 mt-1">View and manage maintenance tasks for your assigned vehicle</p>
        </div>
        @if($vehicle)
        <x-base.button as="a" href="{{ route('driver.maintenance.create') }}" variant="primary">
            <x-base.lucide class="w-4 h-4 mr-2" icon="Plus" />
            New Maintenance
        </x-base.button>
        @endif
    </div>
</div>

@if(!$vehicle)
<!-- No Vehicle Assigned -->
<div class="box box--stacked p-8 text-center">
    <div class="mb-4">
        <x-base.lucide class="w-16 h-16 text-slate-400 mx-auto" icon="TruckOff" />
    </div>
    <h3 class="text-lg font-semibold text-slate-700 mb-2">No Vehicle Assigned</h3>
    <p class="text-slate-500 mb-4">You don't have a vehicle assigned to you yet.</p>
    <a href="{{ route('driver.dashboard') }}" class="inline-flex items-center gap-2 text-primary hover:underline">
        <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
        Back to Dashboard
    </a>
</div>
@else

<!-- Vehicle Info Card -->
<div class="box box--stacked p-5 mb-6">
    <div class="flex items-center gap-4">
        <div class="p-3 bg-primary/10 rounded-lg">
            <x-base.lucide class="w-8 h-8 text-primary" icon="Truck" />
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-slate-800">{{ $vehicle->make }} {{ $vehicle->model }}</h3>
            <p class="text-sm text-slate-500">{{ $vehicle->year }} • Unit: {{ $vehicle->company_unit_number ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('driver.vehicles.show', $vehicle->id) }}" class="text-primary hover:underline text-sm">
            View Details →
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <!-- Total -->
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-slate-100 rounded-lg">
                <x-base.lucide class="w-5 h-5 text-slate-600" icon="ListChecks" />
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800">{{ $stats['total'] }}</p>
                <p class="text-xs text-slate-500">Total</p>
            </div>
        </div>
    </div>

    <!-- Pending -->
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-warning/10 rounded-lg">
                <x-base.lucide class="w-5 h-5 text-warning" icon="Clock" />
            </div>
            <div>
                <p class="text-2xl font-bold text-warning">{{ $stats['pending'] }}</p>
                <p class="text-xs text-slate-500">Pending</p>
            </div>
        </div>
    </div>

    <!-- Overdue -->
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-danger/10 rounded-lg">
                <x-base.lucide class="w-5 h-5 text-danger" icon="AlertCircle" />
            </div>
            <div>
                <p class="text-2xl font-bold text-danger">{{ $stats['overdue'] }}</p>
                <p class="text-xs text-slate-500">Overdue</p>
            </div>
        </div>
    </div>

    <!-- Upcoming -->
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-info/10 rounded-lg">
                <x-base.lucide class="w-5 h-5 text-info" icon="Calendar" />
            </div>
            <div>
                <p class="text-2xl font-bold text-info">{{ $stats['upcoming'] }}</p>
                <p class="text-xs text-slate-500">Due Soon</p>
            </div>
        </div>
    </div>

    <!-- Completed -->
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-success/10 rounded-lg">
                <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
            </div>
            <div>
                <p class="text-2xl font-bold text-success">{{ $stats['completed'] }}</p>
                <p class="text-xs text-slate-500">Completed</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="box box--stacked p-5 mb-6">
    <form method="GET" action="{{ route('driver.maintenance.index') }}" class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="text-sm font-medium text-slate-700 mb-2 block">Filter by Status</label>
            <select name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                <option value="">All Maintenance</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                <option value="upcoming" {{ request('status') == 'upcoming' ? 'selected' : '' }}>Due Soon (30 days)</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <x-base.button type="submit" variant="primary">
                <x-base.lucide class="w-4 h-4 mr-2" icon="Filter" />
                Apply Filter
            </x-base.button>
            @if(request()->has('status'))
            <a href="{{ route('driver.maintenance.index') }}" class="btn btn-secondary">
                Clear
            </a>
            @endif
        </div>
    </form>
</div>

<!-- Maintenance List -->
<div class="box box--stacked">
    <div class="p-5 border-b border-slate-200/80">
        <h3 class="text-lg font-semibold text-slate-800">Maintenance Records</h3>
    </div>

    @if($maintenances->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200/80">
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Service Type</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Service Date</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Next Service</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200/80">
                @foreach($maintenances as $maintenance)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <x-base.lucide class="w-4 h-4 text-slate-400" icon="Wrench" />
                            <span class="font-medium text-slate-800">{{ $maintenance->service_tasks }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-slate-600">
                        {{ $maintenance->service_date ? $maintenance->service_date->format('M d, Y') : 'N/A' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-slate-600">
                        @if($maintenance->next_service_date)
                            {{ $maintenance->next_service_date->format('M d, Y') }}
                            @if($maintenance->next_service_date->isPast() && !$maintenance->status)
                                <span class="ml-2 text-xs text-danger">(Overdue)</span>
                            @elseif($maintenance->next_service_date->diffInDays(now()) <= 30 && !$maintenance->status)
                                <span class="ml-2 text-xs text-warning">(Due Soon)</span>
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if($maintenance->status)
                            <x-base.badge variant="success">Completed</x-base.badge>
                        @elseif($maintenance->next_service_date && $maintenance->next_service_date->isPast())
                            <x-base.badge variant="danger">Overdue</x-base.badge>
                        @else
                            <x-base.badge variant="warning">Pending</x-base.badge>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('driver.maintenance.show', $maintenance->id) }}" 
                               class="p-1.5 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="View">
                                <x-base.lucide class="w-4 h-4" icon="Eye" />
                            </a>
                            <a href="{{ route('driver.maintenance.edit', $maintenance->id) }}" 
                               class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                <x-base.lucide class="w-4 h-4" icon="Pencil" />
                            </a>
                            <button type="button" 
                                    class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors delete-maintenance-btn"
                                    data-id="{{ $maintenance->id }}"
                                    data-name="{{ $maintenance->service_tasks }}"
                                    title="Delete">
                                <x-base.lucide class="w-4 h-4" icon="Trash2" />
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="p-5 border-t border-slate-200/80">
        {{ $maintenances->links() }}
    </div>
    @else
    <div class="p-8 text-center">
        <x-base.lucide class="w-12 h-12 text-slate-300 mx-auto mb-3" icon="ClipboardList" />
        <p class="text-slate-500">No maintenance records found</p>
        @if(request()->has('status'))
        <a href="{{ route('driver.maintenance.index') }}" class="text-primary hover:underline text-sm mt-2 inline-block">
            Clear filters to see all records
        </a>
        @endif
    </div>
    @endif
</div>

@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-maintenance-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                if (confirm('¿Estás seguro de que deseas eliminar el mantenimiento "' + name + '"?')) {
                    const deleteForm = document.createElement('form');
                    deleteForm.method = 'POST';
                    deleteForm.action = '/driver/maintenance/' + id;
                    deleteForm.style.display = 'none';

                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = '{{ csrf_token() }}';
                    deleteForm.appendChild(csrfInput);

                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    deleteForm.appendChild(methodInput);

                    document.body.appendChild(deleteForm);
                    deleteForm.submit();
                }
            });
        });
    });
</script>
@endpush

@endsection

