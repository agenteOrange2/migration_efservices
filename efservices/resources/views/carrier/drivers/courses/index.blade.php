@extends('../themes/' . $activeTheme)
@section('title', 'Driver Courses Management')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver Courses Management', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="py-5">
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="alert alert-success flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
                {{ session('error') }}
            </div>
        @endif

        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="GraduationCap" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Driver Courses Management</h1>
                        <p class="text-slate-600">Manage the courses and certifications of your drivers</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('carrier.courses.create') }}" class="w-full sm:w-auto"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="PlusCircle" />
                        Add New Course
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked p-5 mb-5">
            <form action="{{ route('carrier.courses.index') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <x-base.form-label for="search_term">Search</x-base.form-label>
                        <x-base.form-input id="search_term" name="search_term" type="text"
                            placeholder="Search by organization, experience, city, or state" value="{{ request('search_term') }}" />
                    </div>
                    <div>
                        <x-base.form-label for="driver_filter">Driver</x-base.form-label>
                        <select id="driver_filter" name="driver_filter"
                            class="tom-select w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Drivers</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}"
                                    {{ request('driver_filter') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->user->name }} {{ $driver->user->last_name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-base.form-label for="status_filter">Status</x-base.form-label>
                        <select id="status_filter" name="status_filter"
                            class="tom-select w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Statuses</option>
                            <option value="Active" {{ request('status_filter') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status_filter') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <x-base.form-label for="date_from">Date From</x-base.form-label>
                        <x-base.litepicker id="date_from" name="date_from" class="w-full"
                            value="{{ request('date_from') }}" placeholder="MM/DD/YYYY" />
                    </div>
                    <div>
                        <x-base.form-label for="date_to">Date To</x-base.form-label>    
                        <x-base.litepicker id="date_to" name="date_to" class="w-full" 
                            value="{{ request('date_to') }}" placeholder="MM/DD/YYYY" />
                    </div>
                    <div class="flex items-end gap-2">
                        <x-base.button type="submit" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="filter" />
                            Filter
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('carrier.courses.index') }}" variant="outline-secondary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="refresh-cw" />
                            Clear
                        </x-base.button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Courses Table -->
        <div class="box box--stacked">
            <div class="box-body p-5">
                <div class="overflow-x-auto">
                    <x-base.table class="border-separate border-spacing-y-[10px]">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.th class="whitespace-nowrap">Driver</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Organization</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">City</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">State</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Date of Certification</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Experience</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Expiration Date</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Status</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Actions</x-base.table.th>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @forelse ($courses as $course)
                                <x-base.table.tr>
                                    <x-base.table.td>
                                        {{ $course->driverDetail->user->name }}
                                        {{ $course->driverDetail->user->last_name ?? '' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        {{ $course->organization_name }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        {{ $course->city ?? 'N/A' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        {{ $course->state ?? 'N/A' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        {{ $course->certification_date ? $course->certification_date->format('m/d/Y') : 'N/A' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        {{ $course->experience ?? 'N/A' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        {{ $course->expiration_date ? $course->expiration_date->format('m/d/Y') : 'N/A' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        <span class="px-2 py-1 rounded text-xs font-medium {{ $course->status == 'Active' ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $course->status == 'Active' ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('carrier.courses.documents', $course->id) }}"
                                                class="flex items-center justify-center w-8 h-8 rounded-md bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
                                                title="Ver Documentos">
                                                <x-base.lucide class="w-4 h-4" icon="file-text" />
                                            </a>
                                            <x-base.menu class="h-5">
                                                <x-base.menu.button class="h-5 w-5 text-slate-500">
                                                    <x-base.lucide class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70"
                                                        icon="MoreVertical" />
                                                </x-base.menu.button>
                                                <x-base.menu.items class="w-40">
                                                    <x-base.menu.item>
                                                        <x-base.button as="a" href="{{ route('carrier.courses.edit', $course->id) }}"
                                                            class="flex items-center gap-2">
                                                            <x-base.lucide class="w-4 h-4" icon="edit" />
                                                            Edit
                                                        </x-base.button>
                                                    </x-base.menu.item>
                                                    <x-base.menu.item>
                                                        <x-base.button type="button" data-tw-toggle="modal"
                                                            data-tw-target="#delete-course-modal-{{ $course->id }}"
                                                            class="flex items-center gap-2 text-danger w-full">
                                                            <x-base.lucide class="w-4 h-4" icon="trash" />
                                                            Delete
                                                        </x-base.button>
                                                    </x-base.menu.item>
                                                </x-base.menu.items>
                                            </x-base.menu>
                                        </div>

                                        <!-- Delete Confirmation Modal -->
                                        <x-base.dialog id="delete-course-modal-{{ $course->id }}" size="md">
                                            <x-base.dialog.panel>
                                                <div class="p-5 text-center">
                                                    <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger"
                                                        icon="x-circle" />
                                                    <div class="mt-5 text-2xl">Are you sure?</div>
                                                    <div class="mt-2 text-slate-500">
                                                        Are you sure you want to delete this course record?
                                                        <br>
                                                        This process cannot be undone.
                                                    </div>
                                                </div>
                                                <form action="{{ route('carrier.courses.destroy', $course->id) }}"
                                                    method="POST" class="px-5 pb-8 text-center">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-base.button data-tw-dismiss="modal" type="button"
                                                        variant="outline-secondary" class="mr-1 w-24">
                                                        Cancel
                                                    </x-base.button>
                                                    <x-base.button type="submit" variant="danger" class="w-24">
                                                        Delete
                                                    </x-base.button>
                                                </form>
                                            </x-base.dialog.panel>
                                        </x-base.dialog>
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @empty
                                <x-base.table.tr>
                                    <x-base.table.td colspan="9" class="text-center">
                                        <div class="flex flex-col items-center justify-center py-16">
                                            <x-base.lucide class="h-16 w-16 text-slate-300" icon="alert-triangle" />
                                            <p class="mt-4 text-slate-500">No courses found</p>
                                            <x-base.button as="a" href="{{ route('carrier.courses.create') }}"
                                                class="btn btn-primary mt-4">
                                                <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                                                Add First Course
                                            </x-base.button>
                                        </div>
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @endforelse
                        </x-base.table.tbody>
                    </x-base.table>
                </div>

                <!-- Pagination -->
                @if ($courses->hasPages())
                    <div class="mt-5">
                        {{ $courses->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@pushOnce('scripts')
    @vite('resources/js/components/base/tom-select.js')
@endPushOnce
