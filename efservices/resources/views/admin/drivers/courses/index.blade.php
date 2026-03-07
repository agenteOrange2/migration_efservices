@extends('../themes/' . $activeTheme)
@section('title', 'Driver Courses Management')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Driver Courses Management', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div>
        <!-- Mensajes Flash -->
        @if (session()->has('success'))
            <div class="alert alert-success flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                {{ session('success') }}
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
                    <x-base.button as="a" href="{{ route('admin.courses.all-documents') }}" class="w-full sm:w-auto"
                        variant="outline-primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="FileText" />
                        All Documents
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.courses.create') }}" class="w-full sm:w-auto"
                        variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="PlusCircle" />
                        Add New Course
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="box box--stacked mt-5">
            <div class="box-body p-5">
                <form action="{{ route('admin.courses.index') }}" method="GET" id="filter-form"
                    class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <x-base.lucide
                                class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500"
                                icon="Search" />
                            <x-base.form-input class="rounded-[0.5rem] pl-9 sm:w-64" name="search_term"
                                value="{{ request('search_term') }}" type="text" placeholder="Search courses..." />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Carrier</label>
                        <select name="carrier_filter" id="carrier_filter"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Carriers</option>
                            @foreach ($carriers as $carrier)
                                <option value="{{ $carrier->id }}"
                                    {{ request('carrier_filter') == $carrier->id ? 'selected' : '' }}>
                                    {{ $carrier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Driver</label>
                        <select name="driver_filter" id="driver_filter"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Drivers</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}"
                                    {{ request('driver_filter') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->user->name ?? '' }} {{ $driver->middle_name ?? '' }} {{ $driver->last_name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>                    
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <x-base.litepicker id="date_from" name="date_from" class="w-full" value="{{ request('date_from') }}"
                            placeholder="Select Date" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <x-base.litepicker id="date_to" name="date_to" class="w-full" value="{{ request('date_to') }}"
                            placeholder="Select Date" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-span-1 md:col-span-3 flex items-end space-x-2">
                        <x-base.button variant="primary" type="submit" class="flex items-center">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="search" />
                            Filter
                        </x-base.button>
                        <button type="button" id="clear-filters"
                            class="py-2 px-3 bg-gray-200 text-gray-700 rounded-md text-sm flex items-center">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="x" />
                            Clear Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="box box--stacked mt-5">
            <div class="box-body p-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3 cursor-pointer" data-sort-field="user_driver_detail_id">
                                    Driver
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Carrier
                                </th>
                                <th scope="col" class="px-6 py-3 cursor-pointer" data-sort-field="organization_name">
                                    Organization
                                </th>
                                <th scope="col" class="px-6 py-3 cursor-pointer" data-sort-field="certification_date">
                                    Certification Date
                                </th>
                                <th scope="col" class="px-6 py-3 cursor-pointer" data-sort-field="expiration_date">
                                    Expiration Date
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Documents
                                </th>
                                <th scope="col" class="px-6 py-3 text-center">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($courses as $course)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            @if ($course->driverDetail && $course->driverDetail->getFirstMediaUrl('profile_photo_driver'))
                                                <img src="{{ $course->driverDetail->getFirstMediaUrl('profile_photo_driver') }}"
                                                    alt="Driver Photo" class="w-full h-full object-cover">
                                            @else
                                                <x-base.lucide class="w-5 h-5 text-slate-400" icon="User" />
                                            @endif
                                        </div>
                                        <div>
                                            @if($course->driverDetail && $course->driverDetail->user)
                                                <div class="font-medium text-slate-800">
                                                    {{ $course->driverDetail->user->name ?? '' }}
                                                    {{ $course->driverDetail->middle_name ?? '' }}
                                                    {{ $course->driverDetail->last_name ?? '' }}
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    {{ $course->driverDetail->user->email ?? '' }}
                                                </div>
                                            @else
                                                <span class="text-red-500">No driver assigned</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($course->driverDetail && $course->driverDetail->carrier)
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                            {{ $course->driverDetail->carrier->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    {{ $course->organization_name }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $course->certification_date ? $course->certification_date->format('m/d/Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $course->expiration_date ? $course->expiration_date->format('m/d/Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($course->status == 'active')
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600">
                                            Active
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                            {{ ucfirst($course->status ?? 'inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($course->hasMedia('course_certificates'))
                                        <a href="{{ route('admin.courses.documents', $course) }}" class="bg-primary/20 text-primary rounded px-2 py-1 text-xs">
                                            <i class="fas fa-file-alt mr-1"></i>{{ $course->getMedia('course_certificates')->count() }} {{ Str::plural('Document', $course->getMedia('course_certificates')->count()) }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">No documents</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center gap-1">
                                        @if($course->driverDetail)
                                            <a href="{{ route('admin.drivers.course-history', $course->driverDetail->id) }}" 
                                                class="inline-flex items-center justify-center w-8 h-8 text-info hover:bg-info/10 rounded-lg transition-colors"
                                                title="Driver Course History">
                                                <x-base.lucide class="w-4 h-4" icon="History" />
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.courses.documents', $course) }}" 
                                            class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                            title="View Documents">
                                            <x-base.lucide class="w-4 h-4" icon="FileText" />
                                        </a>
                                        <a href="{{ route('admin.courses.edit', $course) }}" 
                                            class="inline-flex items-center justify-center w-8 h-8 text-warning hover:bg-warning/10 rounded-lg transition-colors"
                                            title="Edit Course">
                                            <x-base.lucide class="w-4 h-4" icon="Edit" />
                                        </a>
                                        <button type="button" onclick="confirmDelete({{ $course->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                            title="Delete Course">
                                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        No courses found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-5">
                    {{ $courses->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <x-base.dialog id="deleteModal">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="w-16 h-16 mx-auto mt-3 text-danger" icon="XCircle" />
                <div class="mt-5 text-3xl">Are you sure?</div>
                <div class="mt-2 text-slate-500">
                    Do you really want to delete this course? <br>
                    This process cannot be undone.
                </div>
            </div>
            <div class="px-5 pb-8 text-center">
                <x-base.button class="w-24 mr-1" data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Cancel
                </x-base.button>
                <form id="deleteForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <x-base.button class="w-24" type="submit" variant="danger">
                        Delete
                    </x-base.button>
                </form>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize TomSelect for filters
                if (typeof TomSelect !== 'undefined') {
                    if (document.getElementById('carrier_filter')) {
                        new TomSelect('#carrier_filter', {
                            placeholder: 'Select carrier',
                            allowEmptyOption: true
                        });
                    }
                    if (document.getElementById('driver_filter')) {
                        new TomSelect('#driver_filter', {
                            placeholder: 'Select driver',
                            allowEmptyOption: true
                        });
                    }
                }

                // Manejar el botón de limpiar filtros
                document.getElementById('clear-filters').addEventListener('click', function() {
                    window.location.href = '{{ route('admin.courses.index') }}';
                });
            });

            function confirmDelete(id) {
                const form = document.getElementById('deleteForm');
                form.action = `{{ url('admin/courses') }}/${id}`;
                const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteModal'));
                modal.show();
            }
        </script>
    @endpush
@endsection
