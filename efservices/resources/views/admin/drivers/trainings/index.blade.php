@extends('../themes/' . $activeTheme)
@section('title', 'Trainings')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('admin.dashboard')],
['label' => 'Trainings', 'active' => true],
];
@endphp

@section('subcontent')

<!-- Professional Header -->
<div class="box box--stacked p-4 sm:p-6 lg:p-8 mb-6 lg:mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 lg:gap-6">
        <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-3 lg:gap-4">
            <div class="p-2 sm:p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-6 h-6 sm:w-8 sm:h-8 text-primary" icon="BookOpen" />
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-800 mb-1 sm:mb-2">Driver Trainings Management</h1>
                <p class="text-sm sm:text-base text-slate-600">Manage and assign training materials to your drivers</p>
            </div>
        </div>
        <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-2 w-full justify-end">
            <x-base.button as="a" href="{{ route('admin.training-dashboard.index') }}" variant="outline-secondary" class="w-full sm:w-auto">
                <x-base.lucide class="w-5 h-5 mr-2" icon="BarChart3" />
                Dashboard
            </x-base.button>

            <x-base.button as="a" href="{{ route('admin.trainings.create') }}" variant="primary" class="w-full sm:w-auto">
                <x-base.lucide class="w-5 h-5 mr-2" icon="plus" />
                Create Training
            </x-base.button>

            <x-base.button as="a" href="{{ route('admin.training-assignments.index') }}" class="w-full sm:w-auto" variant="outline-primary">
                <x-base.lucide class="w-5 h-5 mr-2" icon="clipboard-list" />
                View Assignments
            </x-base.button>
        </div>
    </div>
</div>

<div class="container mx-auto px-2 sm:px-2 lg:px-2 py-8">
    <!-- Instrucciones de asignación -->
    <div class="box box--stacked mt-5 p-3 bg-blue-50">
        <h3 class="box-title text-primary">Instructions for assigning trainings</h3>
        <div class="p-3">
            <p class="text-primary"><strong>To assign a training:</strong> Click on the "View details" button of any active training and then use the "Assign to drivers" button on the details page.</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="box box--stacked mt-5 p-3">        
        <div class="box-content">
            <form action="{{ route('admin.trainings.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <x-base.form-label for="search">Search</x-base.form-label>
                    <x-base.form-input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Title or description" />
                </div>

                <div>
                    <x-base.form-label for="status">Status</x-base.form-label>
                    <x-base.form-select name="status" id="status">
                        <option value="">All</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </x-base.form-select>
                </div>

                <div>
                    <x-base.form-label for="type">Content Type</x-base.form-label>
                    <x-base.form-select name="type" id="type">
                        <option value="">All</option>
                        <option value="file" {{ request('type') === 'file' ? 'selected' : '' }}>File</option>
                        <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Video</option>
                        <option value="url" {{ request('type') === 'url' ? 'selected' : '' }}>URL</option>
                    </x-base.form-select>
                </div>

                <div class="flex items-end">
                    <x-base.button type="submit" class="mr-2">
                        <x-base.lucide class="w-5 h-5 mr-2" icon="search" />
                        Filter
                    </x-base.button>

                    <x-base.button type="button" variant="outline" onclick="window.location.href='{{ route('admin.trainings.index') }}'">
                        <x-base.lucide class="w-5 h-5 mr-2" icon="x" />
                        Clear
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>



    <!-- Trainings List -->
    <div class="box box--stacked">
        <div class="box-body p-4 sm:p-5">
            @forelse ($trainings as $training)
            <!-- Mobile Card Layout (visible on small screens) -->
            <div class="block lg:hidden mb-4 p-4 border border-slate-200 rounded-lg bg-white hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-slate-800 text-base mb-1 truncate">{{ $training->title }}</h3>
                        <p class="text-sm text-slate-600 line-clamp-2 mb-2">{{ $training->description }}</p>
                    </div>
                    <div class="flex items-center gap-2 ml-3">
                        <x-base.button as="a" href="{{ route('admin.trainings.show', $training->id) }}"
                            class="flex items-center justify-center w-8 h-8 rounded-md bg-info/10 text-info hover:bg-info/20 transition-colors"
                            title="View Details">
                            <x-base.lucide class="w-4 h-4" icon="eye" />
                        </x-base.button>
                        <x-base.menu class="h-5">
                            <x-base.menu.button class="h-5 w-5 text-slate-500">
                                <x-base.lucide class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70"
                                    icon="MoreVertical" />
                            </x-base.menu.button>
                            <x-base.menu.items class="w-40">
                                <x-base.menu.item>
                                    <x-base.button as="a" href="{{ route('admin.trainings.edit', $training->id) }}"
                                        class="flex items-center gap-2">
                                        <x-base.lucide class="w-4 h-4" icon="edit" />
                                        Edit
                                    </x-base.button>
                                </x-base.menu.item>
                                <x-base.menu.item>
                                    <x-base.button type="button" data-tw-toggle="modal"
                                        data-tw-target="#delete-training-modal-{{ $training->id }}"
                                        class="flex items-center gap-2 text-danger w-full">
                                        <x-base.lucide class="w-4 h-4" icon="trash" />
                                        Delete
                                    </x-base.button>
                                </x-base.menu.item>
                            </x-base.menu.items>
                        </x-base.menu>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <!-- Content Type -->
                    @if ($training->content_type == 'file')
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                        <x-base.lucide class="w-3 h-3" icon="file-text" />
                        File
                    </span>
                    @elseif ($training->content_type == 'video')
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-purple-100 text-purple-800">
                        <x-base.lucide class="w-3 h-3" icon="video" />
                        Video
                    </span>
                    @elseif ($training->content_type == 'url')
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-green-100 text-green-800">
                        <x-base.lucide class="w-3 h-3" icon="link" />
                        URL
                    </span>
                    @endif

                    <!-- Status -->
                    <span class="px-2 py-1 rounded-full font-medium {{ $training->status == 'active' ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-600' }}">
                        {{ ucfirst($training->status) }}
                    </span>

                    <!-- Assignments -->
                    <span class="px-2 py-1 rounded-full bg-primary/10 text-primary font-medium">
                        {{ $training->driverAssignments->count() }} assigned
                    </span>

                    <!-- Date -->
                    <span class="text-slate-500 ml-auto">
                        {{ $training->created_at->format('m/d/Y') }}
                    </span>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <x-base.dialog id="delete-training-modal-{{ $training->id }}" size="md">
                <x-base.dialog.panel>
                    <div class="p-5 text-center">
                        <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger"
                            icon="x-circle" />
                        <div class="mt-5 text-xl sm:text-2xl">Are you sure?</div>
                        <div class="mt-2 text-slate-500 text-sm sm:text-base">
                            Are you sure you want to delete this training?
                            <br>
                            @if ($training->driverAssignments->count() > 0)
                            <strong class="text-danger">This training has {{ $training->driverAssignments->count() }} assignment(s) and cannot be deleted.</strong>
                            @else
                            This process cannot be undone.
                            @endif
                        </div>
                    </div>
                    <form action="{{ route('admin.trainings.destroy', $training->id) }}"
                        method="POST" class="px-5 pb-8 text-center">
                        @csrf
                        @method('DELETE')
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-1 justify-center">
                            <x-base.button data-tw-dismiss="modal" type="button"
                                variant="outline-secondary" class="w-full sm:w-24">
                                Cancel
                            </x-base.button>
                            @if ($training->driverAssignments->count() == 0)
                            <x-base.button type="submit" variant="danger" class="w-full sm:w-24">
                                Delete
                            </x-base.button>
                            @endif
                        </div>
                    </form>
                </x-base.dialog.panel>
            </x-base.dialog>
            @empty
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-12 sm:py-16">
                <x-base.lucide class="h-12 w-12 sm:h-16 sm:w-16 text-slate-300" icon="book-open" />
                <p class="mt-4 text-slate-500 text-center">No trainings found</p>
                <x-base.button as="a" href="{{ route('admin.trainings.create') }}"
                    class="btn btn-primary mt-4 w-full sm:w-auto">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                    Add First Training
                </x-base.button>
            </div>
            @endforelse

            <!-- Desktop Table Layout (hidden on small screens) -->
            <div class="hidden lg:block">
                <div class="overflow-x-auto">
                    <x-base.table class="border-separate border-spacing-y-[10px]">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.th class="whitespace-nowrap">
                                    <a href="{{ route('admin.trainings.index', array_merge(request()->except(['sort_by']), ['sort_by' => request('sort_by') == 'title_asc' ? 'title_desc' : 'title_asc'])) }}"
                                        class="flex items-center">
                                        Title
                                        @if (request('sort_by') == 'title_asc')
                                        <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @elseif (request('sort_by') == 'title_desc')
                                        <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    </a>
                                </x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Description</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Content Type</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Status</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">
                                    <a href="{{ route('admin.trainings.index', array_merge(request()->except(['sort_by']), ['sort_by' => request('sort_by') == 'created_at_asc' ? 'created_at_desc' : 'created_at_asc'])) }}"
                                        class="flex items-center">
                                        Created Date
                                        @if (request('sort_by') == 'created_at_asc')
                                        <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @elseif (request('sort_by') == 'created_at_desc' || !request('sort_by'))
                                        <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    </a>
                                </x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Assignments</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Actions</x-base.table.th>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @foreach ($trainings as $training)
                            <x-base.table.tr>
                                <x-base.table.td>
                                    <div class="font-medium text-slate-800">{{ $training->title }}</div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="max-w-xs truncate text-slate-600" title="{{ $training->description }}">
                                        {{ $training->description }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if ($training->content_type == 'file')
                                    <span class="flex items-center gap-1 text-primary">
                                        <x-base.lucide class="w-4 h-4" icon="file-text" />
                                        File
                                    </span>
                                    @elseif ($training->content_type == 'video')
                                    <span class="flex items-center gap-1 text-info">
                                        <x-base.lucide class="w-4 h-4" icon="video" />
                                        Video
                                    </span>
                                    @elseif ($training->content_type == 'url')
                                    <span class="flex items-center gap-1 text-warning">
                                        <x-base.lucide class="w-4 h-4" icon="link" />
                                        URL
                                    </span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    <span class="px-2 py-1 rounded text-xs font-medium {{ $training->status == 'active' ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-600' }}">
                                        {{ ucfirst($training->status) }}
                                    </span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    {{ $training->created_at->format('m/d/Y') }}
                                </x-base.table.td>
                                <x-base.table.td>
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-primary/10 text-primary">
                                        {{ $training->driverAssignments->count() }} assigned
                                    </span>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.trainings.show', $training->id) }}"
                                            class="flex items-center justify-center w-8 h-8 rounded-md bg-info/10 text-info hover:bg-info/20 transition-colors"
                                            title="View Details">
                                            <x-base.lucide class="w-4 h-4" icon="eye" />
                                        </a>
                                        <x-base.menu class="h-5">
                                            <x-base.menu.button class="h-5 w-5 text-slate-500">
                                                <x-base.lucide class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70"
                                                    icon="MoreVertical" />
                                            </x-base.menu.button>
                                            <x-base.menu.items class="w-40">
                                                <x-base.menu.item>
                                                    <x-base.button as="a" href="{{ route('admin.trainings.edit', $training->id) }}"
                                                        class="flex items-center gap-2">
                                                        <x-base.lucide class="w-4 h-4" icon="edit" />
                                                        Edit
                                                    </x-base.button>
                                                </x-base.menu.item>
                                                <x-base.menu.item>
                                                    <x-base.button type="button" data-tw-toggle="modal"
                                                        data-tw-target="#delete-training-modal-{{ $training->id }}"
                                                        class="flex items-center gap-2 text-danger w-full">
                                                        <x-base.lucide class="w-4 h-4" icon="trash" />
                                                        Delete
                                                    </x-base.button>
                                                </x-base.menu.item>
                                            </x-base.menu.items>
                                        </x-base.menu>
                                    </div>
                                </x-base.table.td>
                            </x-base.table.tr>
                            @endforeach
                        </x-base.table.tbody>
                    </x-base.table>
                </div>
            </div>

            <!-- Pagination -->
            @if ($trainings->hasPages())
            <div class="mt-4 sm:mt-5">
                {{ $trainings->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection