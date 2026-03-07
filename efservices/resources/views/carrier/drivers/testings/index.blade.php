@extends('../themes/' . $activeTheme)
@section('title', 'Driver Testing Management')
@php
    use App\Helpers\FormatHelper;
    
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver Testing Management', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div>
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert alert-success flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger flex items-center mb-5">
                <x-base.lucide class="w-6 h-6 mr-2" icon="x-circle" />
                {{ session('error') }}
            </div>
        @endif

        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-center mt-8">
            <h2 class="text-lg font-medium mr-auto">
                Driver Testing Management
            </h2>
            <div class="w-full sm:w-auto flex mt-4 sm:mt-0">
                <a href="{{ route('carrier.drivers.testings.create') }}">
                    <x-base.button variant="primary" class="flex items-center">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                        Add New Test
                    </x-base.button>
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked mt-5">
            <div class="box-body p-5">
                <form action="{{ route('carrier.drivers.testings.index') }}" method="GET"
                    class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <!-- General Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <x-base.lucide
                                class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500"
                                icon="Search" />
                            <x-base.form-input class="rounded-[0.5rem] pl-9" name="search_term"
                                value="{{ request('search_term') }}" type="text" placeholder="Search tests..." />
                        </div>
                    </div>

                    <!-- Filter by Driver -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Driver</label>
                        <select name="driver_filter"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Drivers</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ request('driver_filter') == $driver->id ? 'selected' : '' }}>
                                    {{ FormatHelper::formatDriverName($driver) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter by Test Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Test Type</label>
                        <select name="test_type" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Test Types</option>
                            @foreach (\App\Models\Admin\Driver\DriverTesting::getTestTypes() as $typeKey => $typeValue)
                                <option value="{{ $typeKey }}" {{ request('test_type') == $typeKey ? 'selected' : '' }}>
                                    {{ $typeValue }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter by Date From -->
                    <div class="mt-3">
                        <x-base.form-label for="date_from">From Date</x-base.form-label>
                        <x-base.litepicker id="date_from" name="date_from" class="w-full"
                            value="{{ request('date_from') }}" placeholder="Select Date" />
                    </div>

                    <!-- Filter by Date To -->
                    <div class="mt-3">
                        <x-base.form-label for="date_to">To Date</x-base.form-label>
                        <x-base.litepicker id="date_to" name="date_to" class="w-full"
                            value="{{ request('date_to') }}" placeholder="Select Date" />
                    </div>

                    <!-- Filter by Test Result -->
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Result</label>
                        <select name="test_result" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                            <option value="">All Results</option>
                            @foreach (\App\Models\Admin\Driver\DriverTesting::getTestResults() as $resultKey => $resultValue)
                                <option value="{{ $resultKey }}" {{ request('test_result') == $resultKey ? 'selected' : '' }}>
                                    {{ $resultValue }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="md:col-span-3 flex justify-start space-x-2">
                        <x-base.button type="submit" variant="primary" class="flex items-center">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="filter" />
                            Apply Filters
                        </x-base.button>
                        <a href="{{ route('carrier.drivers.testings.index') }}"
                            class="btn btn-outline-secondary flex items-center">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="x" />
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="box box--stacked mt-5">
            <div class="box-body p-5">
                <div class="overflow-x-auto">
                    <x-base.table class="border-separate border-spacing-y-[10px]">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.th class="whitespace-nowrap">Test Date</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Driver</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Test Type</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Status</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Test Result</x-base.table.th>
                                <x-base.table.th class="whitespace-nowrap">Actions</x-base.table.th>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @forelse ($testings as $test)
                                <x-base.table.tr>
                                    <x-base.table.td>
                                        {{ FormatHelper::formatDate($test->test_date) }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        @if ($test->userDriverDetail)
                                            {{ FormatHelper::formatDriverName($test->userDriverDetail) }}
                                        @else
                                            <span class="text-slate-400 italic">N/A</span>
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        {{ $test->test_type ?? 'Not specified' }}
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        @php
                                            $statuses = \App\Models\Admin\Driver\DriverTesting::getStatuses();
                                            $statusDisplay = $statuses[$test->status] ?? ucfirst($test->status);
                                            $statusClass = in_array($test->status, ['pending', 'Schedule', 'In Progress']) ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs {{ $statusClass }}">
                                            {{ $statusDisplay }}
                                        </span>
                                    </x-base.table.td>
                                    <x-base.table.td>
                                        @if ($test->test_result)
                                            <span
                                                class="px-2 py-1 rounded-full text-xs font-medium {{ $test->test_result == 'Positive' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $test->test_result }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 italic">Pending</span>
                                        @endif
                                    </x-base.table.td>

                                    <x-base.table.td class="flex">
                                        <div class="flex items-center">
                                            <a href="{{ route('carrier.drivers.testings.download_pdf', $test->id) }}"
                                                class="btn-sm btn-danger p-1 mr-2 flex" title="Download PDF"
                                                target="_blank">
                                                <x-base.lucide class="w-4 h-4" icon="file-text" />
                                            </a>
                                        </div>
                                        <x-base.menu class="h-5">
                                            <x-base.menu.button class="h-5 w-5 text-slate-500">
                                                <x-base.lucide class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70"
                                                    icon="MoreVertical" />
                                            </x-base.menu.button>
                                            <x-base.menu.items class="w-40">
                                                <!-- View Details -->
                                                <a href="{{ route('carrier.drivers.testings.show', $test->id) }}"
                                                    class="btn-sm btn-danger mr-2 flex gap-2 items-center text-primary p-3">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Eye" />
                                                    View Details
                                                </a>
                                                <a href="{{ route('carrier.drivers.testings.download_pdf', $test->id) }}"
                                                    class="btn-sm btn-danger mr-2 flex gap-2 items-center text-primary p-3"
                                                    target="_blank">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="FileText" />
                                                    Download PDF
                                                </a>

                                                <!-- Edit -->
                                                <a href="{{ route('carrier.drivers.testings.edit', $test->id) }}"
                                                    class="btn-sm btn-danger mr-2 flex gap-2 items-center text-primary p-3">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Edit" />
                                                    Edit Test
                                                </a>

                                                <!-- Delete -->
                                                <button type="button" data-tw-toggle="modal"
                                                    data-tw-target="#delete-confirmation-modal"
                                                    class="btn-sm btn-danger mr-2 flex gap-2 items-center text-danger p-3 delete-testing"
                                                    data-testing-id="{{ $test->id }}"
                                                    data-driver-name="{{ $test->userDriverDetail->user->name ?? 'N/A' }} {{ $test->userDriverDetail->user->last_name ?? '' }}"
                                                    data-test-type="{{ $test->test_type }}">
                                                    <x-base.lucide class="mr-2 h-4 w-4" icon="Trash" />
                                                    Delete
                                                </button>
                                            </x-base.menu.items>
                                        </x-base.menu>

                                    </x-base.table.td>
                                </x-base.table.tr>
                            @empty
                                <x-base.table.tr>
                                    <x-base.table.td colspan="6" class="text-center">
                                        <div class="flex flex-col items-center justify-center py-16">
                                            <x-base.lucide class="h-8 w-8 text-slate-400" icon="Vial" />
                                            <p class="mt-2 text-slate-500">No tests found</p>
                                        </div>
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @endforelse
                        </x-base.table.tbody>
                    </x-base.table>
                </div>

                <!-- Pagination -->
                <div class="mt-5">
                    {{ $testings->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <x-base.dialog id="delete-confirmation-modal" size="md">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-danger" icon="x-circle" />
                <div class="mt-5 text-2xl">Are you sure?</div>
                <div class="mt-2 text-slate-500">
                    Do you really want to delete this test record?
                    <br>
                    This process cannot be undone.
                </div>
            </div>
            <form id="delete_testing_form" method="POST" action="" class="px-5 pb-8 text-center">
                @csrf
                @method('DELETE')
                <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="mr-1 w-24">
                    Cancel
                </x-base.button>
                <x-base.button type="submit" variant="danger" class="w-24">
                    Delete
                </x-base.button>
            </form>
        </x-base.dialog.panel>
    </x-base.dialog>

@endsection

@push('scripts')
    <script src="{{ asset('js/carrier-driver-testing-delete.js') }}"></script>
@endpush
