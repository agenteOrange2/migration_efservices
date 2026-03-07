@extends('../themes/' . $activeTheme)
@section('title', 'Driver Testing History')
@php
    use App\Helpers\FormatHelper;
    
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver Testing', 'url' => route('carrier.drivers.testings.index')],
        ['label' => 'Driver History', 'active' => true],
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
    </div>

    <!-- Driver Information Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="User" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">
                        {{ FormatHelper::formatDriverName($driver) }}
                    </h1>
                    <p class="text-slate-600">Testing History</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('carrier.drivers.testings.index') }}" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                    Back to Tests
                </x-base.button>
                <x-base.button as="a" href="{{ route('carrier.drivers.testings.create') }}" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                    Add Test
                </x-base.button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <h3 class="box-title">Filter Tests</h3>
        </div>
        <div class="box-body p-5">
            <form action="{{ route('carrier.drivers.testings.driver_history', $driver) }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-base.form-label for="search_term">Search</x-base.form-label>
                    <x-base.form-input type="text" name="search_term" id="search_term" value="{{ request('search_term') }}" placeholder="Test type, notes..." />
                </div>
                <div>
                    <x-base.form-label for="test_type">Test Type</x-base.form-label>
                    <select id="test_type" name="test_type" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Types</option>
                        @foreach($testTypes as $type)
                            <option value="{{ $type }}" {{ request('test_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-base.form-label for="test_result">Test Result</x-base.form-label>
                    <select id="test_result" name="test_result" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Results</option>
                        @foreach($testResults as $result)
                            <option value="{{ $result }}" {{ request('test_result') === $result ? 'selected' : '' }}>{{ $result }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end md:col-span-3">
                    <x-base.button type="submit" variant="primary" class="w-full">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="filter" />
                        Apply Filters
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>

    <!-- Testing Records List -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h3 class="box-title">Testing Records ({{ $testings->total() ?? 0 }})</h3>
            </div>
        </div>

        @if($testings->count() > 0)
        <div class="box-body p-0">
            <div class="overflow-x-auto">
                <x-base.table class="border-separate border-spacing-y-[10px]">
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.drivers.testings.driver_history', array_merge(['driver' => $driver->id], request()->except(['sort_field', 'sort_direction']), ['sort_field' => 'test_date', 'sort_direction' => request('sort_field') == 'test_date' && request('sort_direction') == 'asc' ? 'desc' : 'asc'])) }}" class="flex items-center">
                                    Test Date
                                    @if (request('sort_field') == 'test_date')
                                        @if (request('sort_direction') == 'asc')
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                        @else
                                            <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                        @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Test Type
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Test Result
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Status
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Actions
                            </x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @forelse ($testings as $testing)
                        <x-base.table.tr>
                            <x-base.table.td class="px-6 py-4">
                                {{ FormatHelper::formatDate($testing->test_date) }}
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                <div class="max-w-xs truncate" title="{{ $testing->test_type }}">
                                    {{ $testing->test_type ?? 'Not specified' }}
                                </div>
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @if($testing->test_result)
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $testing->test_result == 'Positive' ? 'bg-red-100 text-red-800' : ($testing->test_result == 'Negative' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ $testing->test_result }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">Pending</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @php
                                    $statuses = \App\Models\Admin\Driver\DriverTesting::getStatuses();
                                    $statusDisplay = $statuses[$testing->status] ?? ucfirst($testing->status ?? 'N/A');
                                    $statusClass = in_array($testing->status, ['pending', 'Schedule', 'In Progress']) ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs {{ $statusClass }}">
                                    {{ $statusDisplay }}
                                </span>
                            </x-base.table.td>
                            <x-base.table.td>
                                <x-base.menu class="h-5">
                                    <x-base.menu.button class="h-5 w-5 text-slate-500">
                                        <x-base.lucide class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70" icon="MoreVertical" />
                                    </x-base.menu.button>

                                    <x-base.menu.items class="w-48">
                                        <div class="flex flex-col gap-3">
                                            <a href="{{ route('carrier.drivers.testings.show', $testing->id) }}" class="flex mr-1 text-primary" title="View Details">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="eye" />
                                                View Details
                                            </a>
                                            <a href="{{ route('carrier.drivers.testings.download_pdf', $testing->id) }}" class="flex mr-1 text-primary" title="Download PDF" target="_blank">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="file-text" />
                                                Download PDF
                                            </a>
                                            <a href="{{ route('carrier.drivers.testings.edit', $testing->id) }}" class="btn btn-sm btn-primary flex">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="edit" />
                                                Edit
                                            </a>
                                            <button type="button" onclick="confirmDeleteTesting({{ $testing->id }})" class="btn btn-sm text-red-600 flex">
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
                            <x-base.table.td colspan="5" class="text-center">
                                <div class="flex flex-col items-center justify-center py-16">
                                    <x-base.lucide class="h-8 w-8 text-slate-400" icon="Vial" />
                                    No testing records found
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @endforelse
                    </x-base.table.tbody>
                </x-base.table>
            </div>
        </div>
        <!-- Pagination -->
        <div class="box-footer py-5 px-8">
            {{ $testings->appends(request()->query())->links('custom.pagination') }}
        </div>
        @else
        <div class="box-body p-10 text-center">
            <div class="flex flex-col items-center justify-center py-8">
                <x-base.lucide class="w-16 h-16 text-slate-300" icon="Vial" />
                <div class="mt-5 text-slate-500">
                    No testing records found for this driver.
                </div>
                <x-base.button as="a" href="{{ route('carrier.drivers.testings.create') }}" class="mt-5">
                    <x-base.lucide class="w-4 h-4 mr-1" icon="plus" />
                    Add Test
                </x-base.button>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Delete Testing Confirmation Modal -->
<x-base.dialog id="deleteTestingModal">
    <x-base.dialog.panel>
        <div class="p-5 text-center">
            <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="alert-triangle" />
            <div class="mt-5 text-3xl">Delete Testing Record?</div>
            <div class="mt-2 text-slate-500">
                Are you sure you want to delete this testing record? <br>
                This will permanently delete the test and all associated documents. <br>
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
            <form id="deleteTestingForm" method="POST" style="display: inline;">
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
<script src="{{ asset('js/carrier-driver-testing-delete.js') }}"></script>
@endsection
