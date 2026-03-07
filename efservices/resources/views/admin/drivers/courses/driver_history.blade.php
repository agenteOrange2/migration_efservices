@extends('../themes/' . $activeTheme)
@section('title', 'Driver Course History')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Courses', 'url' => route('admin.courses.index')],
        ['label' => 'Driver Course History', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="alert alert-success flex items-center mb-5">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger flex items-center mb-5">
                    <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
                    {{ session('error') }}
                </div>
            @endif

            <!-- Professional Header -->
            <div class="box box--stacked p-4 sm:p-6 lg:p-8 mb-6 lg:mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 lg:gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-3 lg:gap-4">
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0">
                            @if ($driver->getFirstMediaUrl('profile_photo_driver'))
                                <img src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}" 
                                    alt="{{ $driver->user->name ?? 'Driver' }}"
                                    class="w-full h-full object-cover">
                            @else
                                <x-base.lucide class="w-8 h-8 text-slate-400" icon="User" />
                            @endif
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-800 mb-1 sm:mb-2">
                                {{ $driver->user->name ?? '' }} {{ $driver->middle_name ?? '' }} {{ $driver->last_name ?? '' }}
                            </h1>
                            <p class="text-sm sm:text-base text-slate-600">
                                Course History
                                @if($driver->carrier)
                                    <span class="px-2 py-1 ml-2 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                        {{ $driver->carrier->name }}
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-2 w-full justify-end">
                        <x-base.button as="a" href="{{ route('admin.drivers.show', $driver->id) }}"
                            variant="outline-primary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="User" />
                            Driver Profile
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.courses.index') }}"
                            variant="outline-secondary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                            Back to Courses
                        </x-base.button>
                        <x-base.button as="a" href="{{ route('admin.courses.create', ['driver_id' => $driver->id]) }}"
                            variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="Plus" />
                            Add Course
                        </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="GraduationCap" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-800">{{ $courses->total() }}</div>
                            <div class="text-xs text-slate-500">Total Courses</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-success/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-success">{{ $courses->where('status', 'active')->count() }}</div>
                            <div class="text-xs text-slate-500">Active</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-danger/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-danger" icon="XCircle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-danger">{{ $courses->where('status', 'inactive')->count() }}</div>
                            <div class="text-xs text-slate-500">Inactive</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-warning/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-warning" icon="AlertTriangle" />
                        </div>
                        <div>
                            @php
                                $expiringSoon = $courses->filter(function($c) {
                                    return $c->expiration_date && now()->diffInDays($c->expiration_date, false) <= 30 && now()->diffInDays($c->expiration_date, false) >= 0;
                                })->count();
                            @endphp
                            <div class="text-2xl font-bold text-warning">{{ $expiringSoon }}</div>
                            <div class="text-xs text-slate-500">Expiring Soon</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="box box--stacked">
                <div class="box-body p-5">
                    <form action="{{ route('admin.drivers.course-history', $driver->id) }}" method="GET" id="filter-form">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <x-base.form-label>Search</x-base.form-label>
                                <div class="relative">
                                    <x-base.lucide class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500" icon="Search" />
                                    <x-base.form-input class="pl-9" name="search_term" type="text" 
                                        placeholder="Search organization..." value="{{ request('search_term') }}" />
                                </div>
                            </div>

                            <div>
                                <x-base.form-label>Status</x-base.form-label>
                                <select name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            <div>
                                <x-base.form-label>Sort By</x-base.form-label>
                                <select name="sort_field" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="certification_date" {{ request('sort_field', 'certification_date') == 'certification_date' ? 'selected' : '' }}>Certification Date</option>
                                    <option value="expiration_date" {{ request('sort_field') == 'expiration_date' ? 'selected' : '' }}>Expiration Date</option>
                                    <option value="organization_name" {{ request('sort_field') == 'organization_name' ? 'selected' : '' }}>Organization</option>
                                </select>
                            </div>

                            <div class="flex items-end gap-2">
                                <x-base.button type="submit" variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Filter" />
                                    Filter
                                </x-base.button>
                                <x-base.button as="a" href="{{ route('admin.drivers.course-history', $driver->id) }}"
                                    variant="outline-secondary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="RefreshCw" />
                                    Reset
                                </x-base.button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Courses Table -->
            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    <div class="overflow-auto xl:overflow-visible">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 w-12">
                                        #
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Organization
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Location
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Certification Date
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Expiration Date
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Status
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Documents
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500 w-32">
                                        Actions
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @forelse ($courses as $course)
                                    <x-base.table.tr class="hover:bg-slate-50 transition-colors">
                                        <x-base.table.td class="border-dashed py-4">
                                            {{ $loop->iteration + ($courses->currentPage() - 1) * $courses->perPage() }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="font-medium text-slate-800">{{ $course->organization_name }}</div>
                                            @if($course->experience)
                                                <div class="text-xs text-slate-500">{{ Str::limit($course->experience, 40) }}</div>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4 text-slate-600">
                                            @if($course->city || $course->state)
                                                {{ $course->city }}{{ $course->city && $course->state ? ', ' : '' }}{{ $course->state }}
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4 text-slate-600">
                                            {{ $course->certification_date ? $course->certification_date->format('M d, Y') : 'N/A' }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if($course->expiration_date)
                                                @php
                                                    $daysLeft = now()->diffInDays($course->expiration_date, false);
                                                @endphp
                                                <span class="text-sm {{ $daysLeft < 0 ? 'text-danger font-medium' : ($daysLeft <= 30 ? 'text-warning font-medium' : 'text-slate-600') }}">
                                                    {{ $course->expiration_date->format('M d, Y') }}
                                                </span>
                                                @if ($daysLeft < 0)
                                                    <div class="text-xs text-danger">Expired</div>
                                                @elseif ($daysLeft <= 30)
                                                    <div class="text-xs text-warning">{{ $daysLeft }} days left</div>
                                                @endif
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if($course->status === 'active')
                                                <span class="inline-flex items-center rounded-full bg-success/10 px-2.5 py-1 text-xs font-medium text-success">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="CheckCircle" />
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-danger/10 px-2.5 py-1 text-xs font-medium text-danger">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="XCircle" />
                                                    Inactive
                                                </span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if ($course->hasMedia('course_certificates'))
                                                <a href="{{ route('admin.courses.documents', $course) }}" 
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary hover:bg-primary/20">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="FileText" />
                                                    {{ $course->getMedia('course_certificates')->count() }} {{ Str::plural('Doc', $course->getMedia('course_certificates')->count()) }}
                                                </a>
                                            @else
                                                <span class="text-slate-400 text-xs">No documents</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="{{ route('admin.courses.documents', $course) }}" 
                                                    class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                                    title="View Documents">
                                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
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
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @empty
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="8" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <x-base.lucide class="w-16 h-16 text-slate-300 mb-4" icon="GraduationCap" />
                                                <h3 class="text-lg font-medium text-slate-600 mb-2">No courses found</h3>
                                                <p class="text-slate-400 text-center max-w-sm mb-4">
                                                    This driver doesn't have any course records yet.
                                                </p>
                                                <x-base.button as="a" href="{{ route('admin.courses.create', ['driver_id' => $driver->id]) }}"
                                                    variant="primary">
                                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Plus" />
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
                        <div class="border-t border-slate-200/60 pt-5 mt-5">
                            {{ $courses->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
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
@endsection

@push('scripts')
<script>
    function confirmDelete(id) {
        const form = document.getElementById('deleteForm');
        form.action = `{{ url('admin/courses') }}/${id}`;
        const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteModal'));
        modal.show();
    }
</script>
@endpush
