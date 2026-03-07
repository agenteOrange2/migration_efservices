@extends('../themes/' . $activeTheme)
@section('title', 'Driver Test History')

@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('admin.dashboard')],
    ['label' => 'Testing Drugs Management', 'url' => route('admin.driver-testings.index')],
    ['label' => 'Driver Test History', 'active' => true],
];
@endphp

@section('subcontent')
    <!-- Breadcrumbs -->
    <div class="mb-6">
        <x-base.breadcrumb :links="$breadcrumbLinks" />
    </div>

    <!-- Header -->
    <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="History" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Test History</h1>
                        <p class="text-slate-600">
                            Driver: {{ $userDriverDetail->user->name ?? 'N/A' }}
                            @if ($userDriverDetail->carrier)
                                - {{ $userDriverDetail->carrier->name }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <x-base.button as="a" href="{{ route('admin.driver-testings.index') }}" variant="secondary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                        Back to Tests
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked p-6 mb-6">
            <form method="GET" action="{{ route('admin.driver-testings.driver-history', $userDriverDetail->id) }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Test Result Filter -->
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Test Result</label>
                        <select name="test_result" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Results</option>
                            @foreach ($testResults as $key => $value)
                                <option value="{{ $key }}" {{ request('test_result') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Status</label>
                        <select name="status" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Statuses</option>
                            @foreach ($statuses as $key => $value)
                                <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Test Type Filter -->
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Test Type</label>
                        <select name="test_type" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Types</option>
                            @foreach ($testTypes as $key => $value)
                                <option value="{{ $key }}" {{ request('test_type') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Date From</label>
                        <x-base.litepicker id="date_from" name="date_from" class="w-full" value="{{ request('date_from') }}" placeholder="Select date" />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Date To</label>
                        <x-base.litepicker id="date_to" name="date_to" class="w-full" value="{{ request('date_to') }}" placeholder="Select date" />                        
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <x-base.button type="submit" variant="primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="Filter" />
                        Apply Filters
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('admin.driver-testings.driver-history', $userDriverDetail->id) }}" variant="secondary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="X" />
                        Clear
                    </x-base.button>
                </div>
            </form>
        </div>

        <!-- Tests Table -->
        <div class="box box--stacked p-6">
            @if ($tests->count() > 0)
                <div class="overflow-x-auto">
                    <x-base.table class="w-full">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.th>Test Date</x-base.table.th>
                                <x-base.table.th>Test Type</x-base.table.th>
                                <x-base.table.th>Location</x-base.table.th>
                                <x-base.table.th>Result</x-base.table.th>
                                <x-base.table.th>Status</x-base.table.th>
                                <x-base.table.th>Actions</x-base.table.th>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @foreach ($tests as $test)
                                <x-base.table.tr>
                                    <x-base.table.td>
                                        {{ $test->test_date ? $test->test_date->format('m/d/Y') : 'N/A' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        {{ $test->test_type ?: 'N/A' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        {{ $test->location ?: 'N/A' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        @if (in_array($test->test_result, ['passed', 'negative']))
                                            <x-base.badge variant="success" class="gap-1.5">
                                                <span class="w-1.5 h-1.5 bg-success rounded-full"></span>
                                                {{ ucfirst($test->test_result) }}
                                            </x-base.badge>
                                        @elseif (in_array($test->test_result, ['failed', 'positive']))
                                            <x-base.badge variant="danger" class="gap-1.5">
                                                <span class="w-1.5 h-1.5 bg-danger rounded-full"></span>
                                                {{ ucfirst($test->test_result) }}
                                            </x-base.badge>
                                        @else
                                            <x-base.badge variant="warning" class="gap-1.5">
                                                <span class="w-1.5 h-1.5 bg-warning rounded-full"></span>
                                                {{ ucfirst($test->test_result) }}
                                            </x-base.badge>
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        @if ($test->status == 'approved')
                                            <x-base.badge variant="success">Approved</x-base.badge>
                                        @elseif ($test->status == 'rejected')
                                            <x-base.badge variant="danger">Rejected</x-base.badge>
                                        @elseif ($test->status == 'pending')
                                            <x-base.badge variant="warning">Pending</x-base.badge>
                                        @else
                                            <x-base.badge variant="secondary">{{ ucfirst($test->status) }}</x-base.badge>
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        <div class="flex gap-2">
                                            <x-base.button 
                                                as="a" 
                                                href="{{ route('admin.driver-testings.show', $test->id) }}"
                                                variant="primary" 
                                                size="sm"
                                                class="gap-1">
                                                <x-base.lucide class="w-3 h-3" icon="Eye" />
                                                View
                                            </x-base.button>
                                        </div>
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @endforeach
                        </x-base.table.tbody>
                    </x-base.table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $tests->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <x-base.lucide class="w-16 h-16 mx-auto text-slate-400 mb-4" icon="FileQuestion" />
                    <p class="text-lg font-medium text-slate-700 mb-2">No Tests Found</p>
                    <p class="text-sm text-slate-500">No test records found for this driver with the selected filters.</p>
                </div>
            @endif
        </div>
@endsection
