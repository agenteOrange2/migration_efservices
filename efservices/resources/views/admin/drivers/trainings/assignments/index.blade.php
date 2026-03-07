@extends('../themes/' . $activeTheme)
@section('title', 'Training Assignments')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Trainings', 'url' => route('admin.trainings.index')],
        ['label' => 'Assignments', 'active' => true],
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
                        <div class="p-2 sm:p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-6 h-6 sm:w-8 sm:h-8 text-primary" icon="ClipboardList" />
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-800 mb-1 sm:mb-2">Training Assignments</h1>
                            <p class="text-sm sm:text-base text-slate-600">Manage assignments of trainings to drivers</p>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-2 w-full justify-end">
                        <x-base.button as="a" href="{{ route('admin.trainings.index') }}"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="outline-primary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="ArrowLeft" />
                        Back to Trainings
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.select-training') }}" variant="primary">
                        <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="UserPlus" />
                        New Assignment
                    </x-base.button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-5">
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-primary" icon="Users" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-800">{{ $assignments->total() ?? 0 }}</div>
                            <div class="text-xs text-slate-500">Total</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-success/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-success">{{ $stats['completed'] ?? 0 }}</div>
                            <div class="text-xs text-slate-500">Completed</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-warning/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-warning" icon="Clock" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-warning">{{ $stats['in_progress'] ?? 0 }}</div>
                            <div class="text-xs text-slate-500">In Progress</div>
                        </div>
                    </div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-danger/10 rounded-lg">
                            <x-base.lucide class="w-5 h-5 text-danger" icon="AlertTriangle" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-danger">{{ $stats['overdue'] ?? 0 }}</div>
                            <div class="text-xs text-slate-500">Overdue</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="box box--stacked mt-5">
                <div class="box-body p-5">
                    <form action="{{ route('admin.training-assignments.index') }}" method="GET" id="filter-form">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <x-base.form-label>Training</x-base.form-label>
                                <select name="training_id" id="training-filter"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Trainings</option>
                                    @foreach ($trainings as $training)
                                        <option value="{{ $training->id }}"
                                            {{ request('training_id') == $training->id ? 'selected' : '' }}>
                                            {{ $training->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-base.form-label>Carrier</x-base.form-label>
                                <select name="carrier_id" id="carrier-filter"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Carriers</option>
                                    @foreach ($carriers as $carrier)
                                        <option value="{{ $carrier->id }}"
                                            {{ request('carrier_id') == $carrier->id ? 'selected' : '' }}>
                                            {{ $carrier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-base.form-label>Status</x-base.form-label>
                                <select name="status"
                                    class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                                    <option value="">All Status</option>
                                    <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>
                                        Assigned</option>
                                    <option value="in_progress"
                                        {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>
                                        Completed</option>
                                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>
                                        Overdue</option>
                                </select>
                            </div>

                            <div class="flex items-end gap-3">
                                <x-base.button type="submit" variant="primary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="Filter" />
                                    Filter
                                </x-base.button>
                                <x-base.button as="a" href="{{ route('admin.training-assignments.index') }}"
                                    variant="outline-secondary">
                                    <x-base.lucide class="w-4 h-4 mr-2" icon="RefreshCw" />
                                    Reset
                                </x-base.button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="box box--stacked mt-6">
                <div class="box-body p-5">
                    <div class="overflow-auto xl:overflow-visible">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500 w-12">
                                        #
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Driver
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Carrier
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Training
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Due Date
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500">
                                        Status
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500 w-32">
                                        Actions
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @forelse ($assignments as $assignment)
                                    <x-base.table.tr class="hover:bg-slate-50 transition-colors">
                                        <x-base.table.td class="border-dashed py-4">
                                            {{ $loop->iteration + ($assignments->currentPage() - 1) * $assignments->perPage() }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0">
                                                    @if ($assignment->driver && $assignment->driver->getFirstMediaUrl('profile_photo_driver'))
                                                        <img src="{{ $assignment->driver->getFirstMediaUrl('profile_photo_driver') }}"
                                                            alt="Driver Photo" class="w-full h-full object-cover">
                                                    @else
                                                        <x-base.lucide class="w-5 h-5 text-slate-400" icon="User" />
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="font-medium text-slate-800">
                                                        @if ($assignment->driver && $assignment->driver->user)
                                                            {{ $assignment->driver->user->name ?? '' }}
                                                            {{ $assignment->driver->middle_name ?? '' }}
                                                            {{ $assignment->driver->last_name ?? ($assignment->driver->user->last_name ?? '') }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-slate-500">
                                                        {{ $assignment->driver->user->email ?? '' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if ($assignment->driver && $assignment->driver->carrier)
                                                <span
                                                    class="px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                                    {{ $assignment->driver->carrier->name }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if ($assignment->training)
                                                <a href="{{ route('admin.trainings.show', $assignment->training->id) }}"
                                                    class="text-primary hover:underline font-medium">
                                                    {{ $assignment->training->title }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if ($assignment->due_date)
                                                @php
                                                    $dueDate = \Carbon\Carbon::parse($assignment->due_date);
                                                    $isOverdue =
                                                        $dueDate->isPast() && $assignment->status !== 'completed';
                                                @endphp
                                                <span
                                                    class="text-sm {{ $isOverdue ? 'text-danger font-medium' : 'text-slate-600' }}">
                                                    {{ $dueDate->format('M d, Y') }}
                                                </span>
                                                @if ($isOverdue)
                                                    <div class="text-xs text-danger">Overdue</div>
                                                @endif
                                            @else
                                                <span class="text-slate-400">No due date</span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            @if ($assignment->status === 'completed')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-success/10 px-2.5 py-1 text-xs font-medium text-success">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="CheckCircle" />
                                                    Completed
                                                </span>
                                            @elseif($assignment->status === 'in_progress')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-info/10 px-2.5 py-1 text-xs font-medium text-info">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="Clock" />
                                                    In Progress
                                                </span>
                                            @elseif($assignment->status === 'overdue')
                                                <span
                                                    class="inline-flex items-center rounded-full bg-danger/10 px-2.5 py-1 text-xs font-medium text-danger">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="AlertTriangle" />
                                                    Overdue
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-full bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="Circle" />
                                                    Assigned
                                                </span>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" onclick="showDetails('{{ $assignment->id }}')"
                                                    class="inline-flex items-center justify-center w-8 h-8 text-primary hover:bg-primary/10 rounded-lg transition-colors"
                                                    title="View Details">
                                                    <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                </button>

                                                @if ($assignment->status !== 'completed')
                                                    <button type="button"
                                                        onclick="markComplete('{{ $assignment->id }}')"
                                                        class="inline-flex items-center justify-center w-8 h-8 text-success hover:bg-success/10 rounded-lg transition-colors"
                                                        title="Mark as Completed">
                                                        <x-base.lucide class="w-4 h-4" icon="CheckCircle" />
                                                    </button>
                                                @else
                                                    <form
                                                        action="{{ url('admin/training-assignments/' . $assignment->id . '/mark-complete') }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="revert" value="1">
                                                        <button type="submit"
                                                            class="inline-flex items-center justify-center w-8 h-8 text-warning hover:bg-warning/10 rounded-lg transition-colors"
                                                            title="Revert Status">
                                                            <x-base.lucide class="w-4 h-4" icon="RotateCcw" />
                                                        </button>
                                                    </form>
                                                @endif

                                                <button type="button" onclick="confirmDelete('{{ $assignment->id }}')"
                                                    class="inline-flex items-center justify-center w-8 h-8 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                                                    title="Delete">
                                                    <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                                </button>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @empty
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="7" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <x-base.lucide class="w-16 h-16 text-slate-300 mb-4"
                                                    icon="ClipboardList" />
                                                <h3 class="text-lg font-medium text-slate-600 mb-2">No assignments found
                                                </h3>
                                                <p class="text-slate-400 text-center max-w-sm mb-4">
                                                    Start assigning trainings to drivers.
                                                </p>
                                                <x-base.button as="a" href="{{ route('admin.select-training') }}"
                                                    variant="primary">
                                                    <x-base.lucide class="w-4 h-4 mr-2" icon="UserPlus" />
                                                    New Assignment
                                                </x-base.button>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforelse
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>

                    <!-- Pagination -->
                    @if ($assignments->hasPages())
                        <div class="border-t border-slate-200/60 pt-5 mt-5">
                            {{ $assignments->appends(request()->except('page'))->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <x-base.dialog id="detailsModal">
        <x-base.dialog.panel>
            <div class="p-5">
                <div class="flex items-center border-b border-slate-200/60 pb-4 mb-4">
                    <x-base.lucide class="w-6 h-6 text-primary mr-2" icon="Info" />
                    <h3 class="text-lg font-medium">Assignment Details</h3>
                </div>
                <div id="assignmentDetails">
                    <div class="flex justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    </div>
                </div>
            </div>
            <div class="px-5 pb-5 text-right">
                <x-base.button type="button" variant="outline-secondary" data-tw-dismiss="modal">
                    Close
                </x-base.button>
            </div>
        </x-base.dialog.panel>
    </x-base.dialog>

    <!-- Complete Modal -->
    <x-base.dialog id="completeModal">
        <x-base.dialog.panel>
            <form id="completeForm" action="" method="POST">
                @csrf
                <div class="p-5">
                    <div class="flex items-center border-b border-slate-200/60 pb-4 mb-4">
                        <x-base.lucide class="w-6 h-6 text-success mr-2" icon="CheckCircle" />
                        <h3 class="text-lg font-medium">Mark as Completed</h3>
                    </div>
                    <div>
                        <x-base.form-label>Completion Notes (Optional)</x-base.form-label>
                        <x-base.form-textarea name="completion_notes" rows="4"
                            placeholder="Add any notes about the completion..."></x-base.form-textarea>
                    </div>
                </div>
                <div class="px-5 pb-5 flex justify-end gap-3">
                    <x-base.button type="button" variant="outline-secondary" data-tw-dismiss="modal">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="success">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="CheckCircle" />
                        Mark as Completed
                    </x-base.button>
                </div>
            </form>
        </x-base.dialog.panel>
    </x-base.dialog>

    <!-- Delete Modal -->
    <x-base.dialog id="deleteModal">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="w-16 h-16 mx-auto mt-3 text-danger" icon="XCircle" />
                <div class="mt-5 text-3xl">Are you sure?</div>
                <div class="mt-2 text-slate-500">
                    Do you really want to delete this assignment? <br>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize TomSelect
            if (typeof TomSelect !== 'undefined') {
                if (document.getElementById('training-filter')) {
                    new TomSelect('#training-filter', {
                        placeholder: 'Select training',
                        allowEmptyOption: true
                    });
                }
                if (document.getElementById('carrier-filter')) {
                    new TomSelect('#carrier-filter', {
                        placeholder: 'Select carrier',
                        allowEmptyOption: true
                    });
                }
            }
        });

        function showDetails(id) {
            const detailsContainer = document.getElementById('assignmentDetails');
            const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#detailsModal'));
            modal.show();

            detailsContainer.innerHTML =
                '<div class="flex justify-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>';

            fetch(`{{ url('admin/training-assignments') }}/${id}`)
                .then(response => response.json())
                .then(data => {
                    detailsContainer.innerHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase mb-1">Driver</div>
                            <div class="font-medium">${data.driver?.user?.name || 'N/A'}</div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase mb-1">Carrier</div>
                            <div class="font-medium">${data.driver?.carrier?.name || 'N/A'}</div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase mb-1">Training</div>
                            <div class="font-medium">${data.training?.title || 'N/A'}</div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase mb-1">Status</div>
                            <div class="font-medium">${data.status_label || data.status || 'N/A'}</div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase mb-1">Assigned Date</div>
                            <div class="font-medium">${data.created_at_formatted || 'N/A'}</div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase mb-1">Due Date</div>
                            <div class="font-medium">${data.due_date_formatted || 'Not set'}</div>
                        </div>
                        <div class="col-span-2 p-3 bg-slate-50 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase mb-1">Notes</div>
                            <div class="font-medium">${data.notes || 'No notes'}</div>
                        </div>
                    </div>
                `;
                })
                .catch(error => {
                    detailsContainer.innerHTML =
                    `<div class="text-danger text-center py-4">Error loading details</div>`;
                });
        }

        function markComplete(id) {
            const form = document.getElementById('completeForm');
            form.action = `{{ url('admin/training-assignments') }}/${id}/mark-complete`;
            const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#completeModal'));
            modal.show();
        }

        function confirmDelete(id) {
            const form = document.getElementById('deleteForm');
            form.action = `{{ url('admin/training-assignments') }}/${id}`;
            const modal = tailwind.Modal.getOrCreateInstance(document.querySelector('#deleteModal'));
            modal.show();
        }
    </script>
@endpush
