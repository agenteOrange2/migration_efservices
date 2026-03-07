@extends('../themes/' . $activeTheme)
@section('title', 'Trainings Report')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'Trainings Report', 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Header -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 12v5c3 3 9 3 12 0v-5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Trainings Report</h1>
                    <p class="text-slate-600 mt-1">View and manage driver training assignments</p>
                </div>
            </div>
            <x-base.button as="a" href="{{ route('admin.reports.index') }}" variant="outline-secondary" class="gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back to Reports
            </x-base.button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6 mb-6">
        <!-- Total Assignments -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 12v5c3 3 9 3 12 0v-5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="primary" class="text-xs">Total</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Total Assignments</div>
            <div class="text-3xl font-bold text-slate-800">{{ number_format($totalCount) }}</div>
        </div>

        <!-- Completed -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-success/10 rounded-lg">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 4L12 14.01l-3-3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="success" class="text-xs">Completed</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Completed</div>
            <div class="text-3xl font-bold text-success">{{ number_format($completedCount) }}</div>
        </div>

        <!-- In Progress -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-info/10 rounded-lg">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="info" class="text-xs">In Progress</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">In Progress</div>
            <div class="text-3xl font-bold text-info">{{ number_format($inProgressCount) }}</div>
        </div>

        <!-- Assigned -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-warning/10 rounded-lg">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="warning" class="text-xs">Assigned</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Assigned</div>
            <div class="text-3xl font-bold text-warning">{{ number_format($assignedCount) }}</div>
        </div>

        <!-- Overdue -->
        <div class="box box--stacked p-6 transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2 bg-danger/10 rounded-lg">
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 9v4M12 17h.01" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <x-base.badge variant="danger" class="text-xs">Overdue</x-base.badge>
            </div>
            <div class="text-sm font-medium text-slate-500 mb-1">Overdue</div>
            <div class="text-3xl font-bold text-danger">{{ number_format($overdueCount) }}</div>
        </div>
    </div>

    <!-- Completion Rate -->
    <div class="box box--stacked p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 3v18h18M7 16l4-8 4 8M7 12h10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="text-lg font-semibold text-slate-800">Completion Rate</h3>
            </div>
            <div class="text-2xl font-bold text-primary">{{ $completionRate }}%</div>
        </div>
        <div class="w-full bg-slate-200 rounded-full h-3">
            <div class="bg-gradient-to-r from-primary to-primary h-3 rounded-full transition-all duration-300" style="width: {{ $completionRate }}%"></div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="box box--stacked">
        <!-- Filters Bar -->
        <div class="p-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                <!-- Search -->
                <div class="flex-1 w-full lg:w-auto">
                    <form action="{{ route('admin.reports.trainings') }}" method="GET" id="search-form">
                        @foreach(request()->except(['search', 'page']) as $key => $value)
                            @if($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                        @endforeach
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21 21l-4.35-4.35" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <x-base.form-input 
                                class="pl-10 w-full lg:w-80" 
                                type="text" 
                                name="search" 
                                value="{{ $search }}" 
                                placeholder="Search trainings..."
                                onchange="this.form.submit()" />
                        </div>
                    </form>
                </div>

                <!-- Filter Buttons -->
                <div class="flex gap-3">
                    @php
                        $activeFiltersCount = collect([$carrierFilter, $statusFilter, $dateFrom, $dateTo])->filter()->count();
                    @endphp

                    <x-base.popover class="inline-block">
                        <x-base.popover.button as="x-base.button" variant="outline-secondary" class="gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6h18M7 12h10M5 18h14" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Filters
                            @if($activeFiltersCount > 0)
                                <span class="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-medium text-white">
                                    {{ $activeFiltersCount }}
                                </span>
                            @endif
                        </x-base.popover.button>
                        <x-base.popover.panel>
                            <div class="p-4 w-80">
                                <form method="GET" action="{{ route('admin.reports.trainings') }}">
                                    @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                                    
                                    <div class="mb-4">
                                        <x-base.form-label>Carrier</x-base.form-label>
                                        <x-base.form-select name="carrier" class="mt-2">
                                            <option value="">All Carriers</option>
                                            @foreach($carriers as $carrier)
                                                <option value="{{ $carrier->id }}" {{ $carrierFilter == $carrier->id ? 'selected' : '' }}>
                                                    {{ $carrier->name }}
                                                </option>
                                            @endforeach
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Status</x-base.form-label>
                                        <x-base.form-select name="status" class="mt-2">
                                            <option value="">All Status</option>
                                            <option value="assigned" {{ $statusFilter == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                            <option value="in_progress" {{ $statusFilter == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                            <option value="completed" {{ $statusFilter == 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="overdue" {{ $statusFilter == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                        </x-base.form-select>
                                    </div>

                                    <div class="mb-4">
                                        <x-base.form-label>Date Range (Assigned)</x-base.form-label>
                                        <div class="grid grid-cols-2 gap-2 mt-2">
                                            <div>
                                                <x-base.form-label class="text-xs">From</x-base.form-label>
                                                <x-base.form-input type="date" name="date_from" value="{{ $dateFrom }}" />
                                            </div>
                                            <div>
                                                <x-base.form-label class="text-xs">To</x-base.form-label>
                                                <x-base.form-input type="date" name="date_to" value="{{ $dateTo }}" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <x-base.button class="flex-1" variant="outline-secondary" as="a" href="{{ route('admin.reports.trainings') }}">
                                            Clear
                                        </x-base.button>
                                        <x-base.button class="flex-1" variant="primary" type="submit">
                                            Apply
                                        </x-base.button>
                                    </div>
                                </form>
                            </div>
                        </x-base.popover.panel>
                    </x-base.popover>

                    <x-base.button id="export-pdf-btn" variant="outline-secondary" class="gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Export PDF
                    </x-base.button>
                </div>
            </div>
        </div>

        @if($assignments->count() > 0)
            <!-- Table -->
            <div class="overflow-x-auto">
                <x-base.table>
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th>Driver</x-base.table.th>
                            <x-base.table.th>Carrier</x-base.table.th>
                            <x-base.table.th>Training</x-base.table.th>
                            <x-base.table.th>Assigned Date</x-base.table.th>
                            <x-base.table.th>Due Date</x-base.table.th>
                            <x-base.table.th>Completed Date</x-base.table.th>
                            <x-base.table.th>Status</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach($assignments as $assignment)
                            <x-base.table.tr>
                                <x-base.table.td>
                                    <div class="text-sm font-medium text-slate-900">
                                        {{ $assignment->driver->full_name ?? 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if($assignment->driver && $assignment->driver->carrier)
                                        <x-base.badge variant="primary" class="text-xs">{{ $assignment->driver->carrier->name }}</x-base.badge>
                                    @else
                                        <span class="text-sm text-slate-400">N/A</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900 max-w-xs truncate">
                                        {{ $assignment->training->title ?? 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        {{ $assignment->assigned_date ? $assignment->assigned_date->format('M d, Y') : 'N/A' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if($assignment->due_date)
                                        <div class="text-sm {{ $assignment->isOverdue() ? 'text-danger font-medium' : 'text-slate-900' }}">
                                            {{ $assignment->due_date->format('M d, Y') }}
                                        </div>
                                    @else
                                        <span class="text-sm text-slate-400">N/A</span>
                                    @endif
                                </x-base.table.td>
                                <x-base.table.td>
                                    <div class="text-sm text-slate-900">
                                        {{ $assignment->completed_date ? $assignment->completed_date->format('M d, Y') : '-' }}
                                    </div>
                                </x-base.table.td>
                                <x-base.table.td>
                                    @if($assignment->status == 'completed')
                                        <x-base.badge variant="success" class="text-xs">Completed</x-base.badge>
                                    @elseif($assignment->status == 'in_progress')
                                        <x-base.badge variant="info" class="text-xs">In Progress</x-base.badge>
                                    @elseif($assignment->isOverdue())
                                        <x-base.badge variant="danger" class="text-xs">Overdue</x-base.badge>
                                    @else
                                        <x-base.badge variant="warning" class="text-xs">Assigned</x-base.badge>
                                    @endif
                                </x-base.table.td>
                            </x-base.table.tr>
                        @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>

            <!-- Pagination -->
            @if($assignments->hasPages())
                <div class="p-6 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-slate-600">
                            Showing <span class="font-medium">{{ $assignments->firstItem() }}</span> to 
                            <span class="font-medium">{{ $assignments->lastItem() }}</span> of 
                            <span class="font-medium">{{ $assignments->total() }}</span> results
                        </div>
                        <div>
                            {{ $assignments->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M6 12v5c3 3 9 3 12 0v-5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No training assignments found</h3>
                <p class="text-slate-600 mb-6">No training records match the applied filters.</p>
                <x-base.button as="a" href="{{ route('admin.reports.trainings') }}" variant="primary">
                    Clear Filters
                </x-base.button>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('export-pdf-btn').addEventListener('click', function() {
                    const params = new URLSearchParams(window.location.search);
                    params.delete('page');
                    let url = '{{ route("admin.reports.trainings.pdf") }}';
                    const queryString = params.toString();
                    window.location.href = queryString ? `${url}?${queryString}` : url;
                });
            });
        </script>
    @endpush
@endsection
