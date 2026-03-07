@extends('../themes/' . $activeTheme)
@section('title', 'Training Schools')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Training Schools', 'active' => true],
];
@endphp

@section('subcontent')
<div>
    <!-- Alerts -->
    <div class="pb-4">
        <!-- Flash Messages -->
        @if(session('success'))
        <x-base.alert variant="success" dismissible class="flex items-center gap-3">
            <x-base.lucide class="w-8 h-8 text-white" icon="check-circle" />
            <span class="text-white">
                {{ session('success') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif

        @if(session('error'))
        <x-base.alert variant="danger" dismissible>
            <span class="text-white">
                {{ session('error') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif

        @if(session('warning'))
        <x-base.alert variant="warning" dismissible>
            <span class="text-white">
                {{ session('warning') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide class="h-4 w-4 text-white" icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif
    </div>

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="GraduationCap" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Training Schools Management</h1>
                    <p class="text-slate-600">Manage driver training school records and certificates</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('carrier.training-schools.docs.all') }}" class="w-full sm:w-auto" variant="outline-primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="file-text" />
                    View All Documents
                </x-base.button>
                <x-base.button as="a" href="{{ route('carrier.training-schools.create') }}" class="w-full sm:w-auto" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                    Add Training School
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <h3 class="box-title">Filter Training Schools</h3>
        </div>
        <div class="box-body p-5">
            <form action="{{ route('carrier.training-schools.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-base.form-label for="search_term">Search</x-base.form-label>
                    <x-base.form-input type="text" name="search_term" id="search_term" value="{{ request('search_term') }}" placeholder="School name, city, state..." />
                </div>
                <div>
                    <x-base.form-label for="driver_filter">Driver</x-base.form-label>
                    <select id="driver_filter" name="driver_filter" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Drivers</option>
                        @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ request('driver_filter') == $driver->id ? 'selected' : '' }}>
                            {{ implode(' ', array_filter([$driver->user->name ?? '', $driver->middle_name, $driver->last_name])) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <x-base.form-label for="date_from">Date From (MM/DD/YYYY)</x-base.form-label>
                            <x-base.litepicker name="date_from" value="{{ request('date_from') }}" placeholder="MM/DD/YYYY" />
                        </div>
                        <div>
                            <x-base.form-label for="date_to">Date To (MM/DD/YYYY)</x-base.form-label>
                            <x-base.litepicker name="date_to" value="{{ request('date_to') }}" placeholder="MM/DD/YYYY" />
                        </div>
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <x-base.button type="submit" variant="primary" class="w-full">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="filter" />
                        Apply Filters
                    </x-base.button>
                    @if(request()->hasAny(['search_term', 'driver_filter', 'date_from', 'date_to']))
                    <x-base.button as="a" href="{{ route('carrier.training-schools.index') }}" variant="outline-secondary" class="w-full">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="x" />
                        Clear
                    </x-base.button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de escuelas de entrenamiento -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h3 class="box-title">Training Schools ({{ $trainingSchools->total() ?? 0 }})</h3>
            </div>
        </div>

        @if($trainingSchools->count() > 0)
        <div class="box-body p-0">
            <div class="overflow-x-auto">
                <x-base.table class="border-separate border-spacing-y-[10px]">
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.training-schools.index', array_merge(request()->except(['sort_field', 'sort_direction']), ['sort_field' => 'created_at', 'sort_direction' => request('sort_field') == 'created_at' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                    Registration Date
                                    @if (request('sort_field') == 'created_at')
                                        @if (request('sort_direction') == 'asc')
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @else
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Driver
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.training-schools.index', array_merge(request()->except(['sort_field', 'sort_direction']), ['sort_field' => 'school_name', 'sort_direction' => request('sort_field') == 'school_name' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                    School Name
                                    @if (request('sort_field') == 'school_name')
                                        @if (request('sort_direction') == 'asc')
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @else
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Location
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.training-schools.index', array_merge(request()->except(['sort_field', 'sort_direction']), ['sort_field' => 'date_end', 'sort_direction' => request('sort_field') == 'date_end' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                    Completion Date
                                    @if (request('sort_field') == 'date_end')
                                        @if (request('sort_direction') == 'asc')
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @else
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Documents
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Actions
                            </x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @forelse ($trainingSchools as $school)
                        <x-base.table.tr>
                            <x-base.table.td class="px-6 py-4">
                                {{ $school->created_at->format('m/d/Y') }}
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @if($school->userDriverDetail)
                                    {{ implode(' ', array_filter([$school->userDriverDetail->user->name ?? '', $school->userDriverDetail->middle_name, $school->userDriverDetail->last_name])) ?: '---' }}
                                @else
                                    ---
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <div class="max-w-xs truncate" title="{{ $school->school_name }}">
                                    {{ $school->school_name }}
                                </div>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                {{ $school->city }}, {{ $school->state }}
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                {{ \Carbon\Carbon::parse($school->date_end)->format('M d, Y') }}
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @php
                                    $documentCount = $school->getMedia('school_certificates')->count();
                                @endphp
                                @if($documentCount > 0)
                                    <span class="bg-primary/20 text-primary rounded px-2 py-1 text-xs font-medium">
                                        {{ $documentCount }} {{ $documentCount == 1 ? 'document' : 'documents' }}
                                    </span>
                                @else
                                    <span class="text-slate-400">No documents</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td>
                                <x-base.menu class="h-5">
                                    <x-base.menu.button class="h-5 w-5 text-slate-500">
                                        <x-base.lucide class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70" icon="MoreVertical" />
                                    </x-base.menu.button>

                                    <x-base.menu.items class="w-48">
                                        <div class="flex flex-col gap-3">                                            
                                            <a href="{{ route('carrier.training-schools.show', $school->id) }}" class="flex mr-1 text-primary" title="View Documents">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="file-text" />
                                                View Documents
                                            </a>
                                            <a href="{{ route('carrier.training-schools.edit', $school->id) }}" class="btn btn-sm btn-primary flex">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="edit" />
                                                Edit
                                            </a>
                                            <button type="button" onclick="confirmDeleteSchool({{ $school->id }})" class="btn btn-sm text-red-600 flex">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="trash-2" />
                                                Delete
                                            </button>
                                        </div>
                                    </x-base.menu.items>
                                </x-base.menu>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @empty
                        <x-base.table.tr>
                            <x-base.table.td colspan="7" class="text-center">
                                <div class="flex flex-col items-center justify-center py-16">
                                    <x-base.lucide class="h-8 w-8 text-slate-400" icon="GraduationCap" />
                                    No training schools found
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @endforelse
                    </x-base.table.tbody>
                </x-base.table>
            </div>
        </div>
        <!-- Paginación -->
        <div class="box-footer py-5 px-8">
            {{ $trainingSchools->appends(request()->except('page'))->links('custom.pagination') }}
        </div>
        @else
        <div class="box-body p-10 text-center">
            <div class="flex flex-col items-center justify-center py-8">
                <x-base.lucide class="w-16 h-16 text-slate-300" icon="GraduationCap" />
                <div class="mt-5 text-slate-500">
                    No training school records found.
                </div>
                <x-base.button as="a" href="{{ route('carrier.training-schools.create') }}" class="mt-5">
                    <x-base.lucide class="w-4 h-4 mr-1" icon="plus" />
                    Add Training School
                </x-base.button>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Delete Training School Confirmation Modal -->
<x-base.dialog id="deleteSchoolModal">
    <x-base.dialog.panel>
        <div class="p-5 text-center">
            <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="alert-triangle" />
            <div class="mt-5 text-3xl">Delete Training School?</div>
            <div class="mt-2 text-slate-500">
                Are you sure you want to delete this training school record? <br>
                This will permanently delete the school and all associated documents. <br>
                <strong>This action cannot be undone.</strong>
            </div>
        </div>
        <div class="px-5 pb-8 text-center">
            <x-base.button
                class="mr-1 w-24"
                data-tw-dismiss="modal"
                type="button"
                variant="outline-secondary"
            >
                Cancel
            </x-base.button>
            <form id="deleteSchoolForm" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <x-base.button class="w-24" type="submit" variant="danger">
                    Delete
                </x-base.button>
            </form>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>
@endsection

@section('script')
<script>
function confirmDeleteSchool(schoolId) {
    const modal = tailwind.Modal.getOrCreateInstance(document.querySelector("#deleteSchoolModal"));
    const form = document.getElementById('deleteSchoolForm');
    form.action = `/carrier/training-schools/${schoolId}`;
    modal.show();
}
</script>
@endsection
