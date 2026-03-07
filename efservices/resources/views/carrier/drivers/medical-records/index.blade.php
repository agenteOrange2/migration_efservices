@extends('../themes/' . $activeTheme)
@section('title', 'Medical Records')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('carrier.dashboard')],
['label' => 'Medical Records', 'active' => true],
];
@endphp

@section('subcontent')
<div class="container-fluid">
    <!-- Alerts -->
    <div class="pb-4">
        <!-- Flash Messages -->
        @if(session('success'))
        <x-base.alert variant="success" dismissible class="flex items-center gap-3">
            <x-base.lucide class="w-8 h-8 text-white" icon="check-circle " />
            <span class="text-white">
                {{ session('success') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide
                    class="h-4 w-4 text-white"
                    icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif

        @if(session('error'))
        <x-base.alert variant="danger" dismissible>
            <span class="text-white">
                {{ session('error') }}
            </span>
            <x-base.alert.dismiss-button class="btn-close">
                <x-base.lucide
                    class="h-4 w-4 text-white"
                    icon="X" />
            </x-base.alert.dismiss-button>
        </x-base.alert>
        @endif
    </div>
    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="Heart" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Medical Records Management</h1>
                    <p class="text-slate-600">Manage your driver medical records</p>
                </div>
            </div>
            <div class="flex flex-col sm:justify-end sm:flex-row gap-3 w-full md:w-[300px]">
                <x-base.button as="a" href="{{ route('carrier.medical-records.create') }}" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                    Add Medical Record
                </x-base.button>
            </div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row justify-between items-center my-5 gap-3 px-3 sm:px-0">
        <!-- Expiration Alerts -->
        @if($expiringCount > 0)
        <x-base.alert variant="outline-warning" class="mt-5 bg-white w-full md:w-[350px]">
            <div class="flex items-center">
                <x-base.lucide class="w-5 h-5 mr-2 flex-shrink-0" icon="alert-triangle" />
                <div>
                    <strong>{{ $expiringCount }}</strong> medical records are expiring within 30 days.
                </div>
            </div>
        </x-base.alert>
        @endif

        @if($expiredCount > 0)
        <x-base.alert variant="danger" class="mt-5 w-full md:w-[350px]">
            <div class="flex items-center">
                <x-base.lucide class="w-5 h-5 mr-2 flex-shrink-0" icon="x-circle" />
                <div>
                    <strong>{{ $expiredCount }}</strong> medical records have expired.
                </div>
            </div>
        </x-base.alert>
        @endif
    </div>

    <!-- Interactive Filter Cards -->
    <div class="box box--stacked flex flex-col p-5">
        <div class="grid grid-cols-4 gap-5">
            <!-- Total Records Card -->
            <a href="{{ route('carrier.medical-records.index', ['tab' => 'all'] + request()->except('tab', 'page')) }}"
                class="box col-span-4 rounded-[0.6rem] border border-dashed relative
                      {{ ($currentTab ?? 'all') == 'all' ? 'border-primary/80 bg-primary/5' : 'border-slate-300/80' }}
                      p-5 shadow-sm md:col-span-2 xl:col-span-1
                      hover:border-primary/60 hover:bg-primary/5
                      transition-all duration-150 ease-in-out cursor-pointer">
                <div class="text-base {{ ($currentTab ?? 'all') == 'all' ? 'text-primary' : 'text-slate-500' }}">
                    Total Records
                </div>
                <div class="mt-1.5 text-2xl font-medium">{{ $totalCount }}</div>
                <div class="absolute inset-y-0 right-0 mr-5 flex flex-col justify-center">
                    <div class="flex items-center rounded-full border border-success/10 bg-success/10
                                py-[2px] pl-[7px] pr-1 text-xs font-medium text-success">
                        <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5] mr-1" icon="file-text" />
                        All
                    </div>
                </div>
            </a>

            <!-- Active Records Card -->
            <a href="{{ route('carrier.medical-records.index', ['tab' => 'active'] + request()->except('tab', 'page')) }}"
                class="box col-span-4 rounded-[0.6rem] border border-dashed relative
                      {{ ($currentTab ?? 'all') == 'active' ? 'border-primary/80 bg-primary/5' : 'border-slate-300/80' }}
                      p-5 shadow-sm md:col-span-2 xl:col-span-1
                      hover:border-primary/60 hover:bg-primary/5
                      transition-all duration-150 ease-in-out cursor-pointer">
                <div class="text-base {{ ($currentTab ?? 'all') == 'active' ? 'text-primary' : 'text-slate-500' }}">
                    Active Records
                </div>
                <div class="mt-1.5 text-2xl font-medium">{{ $activeCount ?? 0 }}</div>
                <div class="absolute inset-y-0 right-0 mr-5 flex flex-col justify-center">
                    <div class="flex items-center rounded-full border border-success/10 bg-success/10
                                py-[2px] pl-[7px] pr-1 text-xs font-medium text-success">
                        <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5] mr-1" icon="check-circle" />
                        Active
                    </div>
                </div>
            </a>

            <!-- Expiring Soon Card -->
            <a href="{{ route('carrier.medical-records.index', ['tab' => 'expiring'] + request()->except('tab', 'page')) }}"
                class="box col-span-4 rounded-[0.6rem] border border-dashed relative
                      {{ ($currentTab ?? 'all') == 'expiring' ? 'border-primary/80 bg-primary/5' : 'border-slate-300/80' }}
                      p-5 shadow-sm md:col-span-2 xl:col-span-1
                      hover:border-primary/60 hover:bg-primary/5
                      transition-all duration-150 ease-in-out cursor-pointer">
                <div class="text-base {{ ($currentTab ?? 'all') == 'expiring' ? 'text-primary' : 'text-slate-500' }}">
                    Expiring Soon
                </div>
                <div class="mt-1.5 text-2xl font-medium">{{ $expiringCount }}</div>
                <div class="absolute inset-y-0 right-0 mr-5 flex flex-col justify-center">
                    <div class="flex items-center rounded-full border border-warning/10 bg-warning/10
                                py-[2px] pl-[7px] pr-1 text-xs font-medium text-warning">
                        <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5] mr-1" icon="clock" />
                        Expiring
                    </div>
                </div>
            </a>

            <!-- Expired Card -->
            <a href="{{ route('carrier.medical-records.index', ['tab' => 'expired'] + request()->except('tab', 'page')) }}"
                class="box col-span-4 rounded-[0.6rem] border border-dashed relative
                      {{ ($currentTab ?? 'all') == 'expired' ? 'border-primary/80 bg-primary/5' : 'border-slate-300/80' }}
                      p-5 shadow-sm md:col-span-2 xl:col-span-1
                      hover:border-primary/60 hover:bg-primary/5
                      transition-all duration-150 ease-in-out cursor-pointer">
                <div class="text-base {{ ($currentTab ?? 'all') == 'expired' ? 'text-primary' : 'text-slate-500' }}">
                    Expired
                </div>
                <div class="mt-1.5 text-2xl font-medium">{{ $expiredCount }}</div>
                <div class="absolute inset-y-0 right-0 mr-5 flex flex-col justify-center">
                    <div class="flex items-center rounded-full border border-danger/10 bg-danger/10
                                py-[2px] pl-[7px] pr-1 text-xs font-medium text-danger">
                        <x-base.lucide class="ml-px h-4 w-4 stroke-[1.5] mr-1" icon="alert-circle" />
                        Expired
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <h3 class="box-title">Filters</h3>
        </div>
        <div class="box-body p-5">
            <form action="{{ route('carrier.medical-records.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-base.form-label for="search_term">Search</x-base.form-label>
                    <x-base.form-input type="text" name="search_term" id="search_term" value="{{ request('search_term') }}" placeholder="Driver name, examiner name, registry number..." />
                </div>
                <div>
                    <x-base.form-label for="driver_filter">Driver</x-base.form-label>
                    <select id="driver_filter" name="driver_filter" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Drivers</option>
                        @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ request('driver_filter') == $driver->id ? 'selected' : '' }}>
                            {{ implode(' ', array_filter([$driver->user->name, $driver->middle_name, $driver->last_name])) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <x-base.form-label for="date_from">Date (from)</x-base.form-label>
                            <x-base.litepicker name="date_from" value="{{ request('date_from') }}" placeholder="Select a date" />
                        </div>
                        <div>
                            <x-base.form-label for="date_to">Date (to)</x-base.form-label>
                            <x-base.litepicker name="date_to" value="{{ request('date_to') }}" placeholder="Select a date" />
                        </div>
                    </div>
                </div>
                <div class="flex items-end">
                    <x-base.button type="submit" variant="primary" class="w-full">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="filter" />
                        Apply Filters
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>

    <!-- Medical Records Table -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <h3 class="box-title">Medical Records ({{ $medicalRecords->total() }})</h3>
        </div>
        <div class="box-body p-0">
            @if($medicalRecords->count() > 0)
            <div class="overflow-x-auto">
                <x-base.table class="table-auto">
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.medical-records.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('sort') == 'created_at' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}"
                                    class="flex items-center text-slate-500 hover:text-slate-700">
                                    Created Date
                                    @if(request('sort') == 'created_at')
                                    <x-base.lucide class="w-4 h-4 ml-1" icon="{{ request('direction') == 'asc' ? 'chevron-up' : 'chevron-down' }}" />
                                    @else
                                    <x-base.lucide class="w-4 h-4 ml-1 text-slate-400" icon="chevrons-up-down" />
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">Driver</x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.medical-records.index', array_merge(request()->query(), ['sort' => 'medical_card_expiration_date', 'direction' => request('sort') == 'medical_card_expiration_date' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}"
                                    class="flex items-center text-slate-500 hover:text-slate-700">
                                    Expiration Date
                                    @if(request('sort') == 'medical_card_expiration_date')
                                    <x-base.lucide class="w-4 h-4 ml-1" icon="{{ request('direction') == 'asc' ? 'chevron-up' : 'chevron-down' }}" />
                                    @else
                                    <x-base.lucide class="w-4 h-4 ml-1 text-slate-400" icon="chevrons-up-down" />
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('carrier.medical-records.index', array_merge(request()->query(), ['sort' => 'medical_examiner_name', 'direction' => request('sort') == 'medical_examiner_name' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}"
                                    class="flex items-center text-slate-500 hover:text-slate-700">
                                    Medical Examiner
                                    @if(request('sort') == 'medical_examiner_name')
                                    <x-base.lucide class="w-4 h-4 ml-1" icon="{{ request('direction') == 'asc' ? 'chevron-up' : 'chevron-down' }}" />
                                    @else
                                    <x-base.lucide class="w-4 h-4 ml-1 text-slate-400" icon="chevrons-up-down" />
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">Status</x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">Documents</x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">Actions</x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @foreach($medicalRecords as $record)
                        @php
                        $expirationDate = \Carbon\Carbon::parse($record->medical_card_expiration_date);
                        $now = \Carbon\Carbon::now();
                        $daysUntilExpiration = $now->diffInDays($expirationDate, false);

                        if ($daysUntilExpiration < 0) {
                            $statusClass='bg-red-100 text-red-800' ;
                            $statusText='Expired' ;
                            } elseif ($daysUntilExpiration <=30) {
                            $statusClass='bg-yellow-100 text-yellow-800' ;
                            $statusText='Expiring Soon' ;
                            } else {
                            $statusClass='bg-green-100 text-green-800' ;
                            $statusText='Active' ;
                            }
                            @endphp
                            <x-base.table.tr>
                            <x-base.table.td class="whitespace-nowrap">
                                {{ $record->created_at->format('M d, Y') }}
                            </x-base.table.td>
                            <x-base.table.td class="whitespace-nowrap">
                                @if($record->userDriverDetail && $record->userDriverDetail->user)
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-slate-300 flex items-center justify-center">
                                            <span class="text-xs font-medium text-slate-700">
                                                {{ strtoupper(substr($record->userDriverDetail->user->name, 0, 2)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-slate-900">
                                            {{ implode(' ', array_filter([$record->userDriverDetail->user->name, $record->userDriverDetail->middle_name, $record->userDriverDetail->last_name])) }}
                                        </div>
                                        <div class="text-sm text-slate-500">
                                            {{ $record->userDriverDetail->user->email }}
                                        </div>
                                    </div>
                                </div>
                                @else
                                <span class="text-slate-400">No driver assigned</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="whitespace-nowrap">
                                @if($record->medical_card_expiration_date)
                                {{ $expirationDate->format('M d, Y') }}
                                @else
                                <span class="text-slate-400">N/A</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="whitespace-nowrap">
                                @if($record->medical_examiner_name)
                                <div class="text-sm font-medium text-slate-900">
                                    {{ $record->medical_examiner_name }}
                                </div>
                                @if($record->medical_examiner_registry_number)
                                <div class="text-sm text-slate-500">
                                    {{ $record->medical_examiner_registry_number }}
                                </div>
                                @endif
                                @else
                                <span class="text-slate-400">N/A</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="whitespace-nowrap">
                                @if($record->medical_card_expiration_date)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $statusText }}
                                </span>
                                @else
                                <span class="text-slate-400">N/A</span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="text-center">
                                @if($record->documents_count > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $record->documents_count }} {{ $record->documents_count == 1 ? 'document' : 'documents' }}
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    0 documents
                                </span>
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="whitespace-nowrap">
                                <x-base.menu>
                                    <x-base.menu.button as="x-base.button" variant="outline-secondary" size="sm">
                                        <x-base.lucide class="w-4 h-4" icon="more-horizontal" />
                                    </x-base.menu.button>
                                    <x-base.menu.items class="w-48">
                                        <x-base.menu.item as="a" href="{{ route('carrier.medical-records.show', $record) }}">
                                            <x-base.lucide class="w-4 h-4 mr-2" icon="eye" />
                                            View Details
                                        </x-base.menu.item>
                                        <x-base.menu.item as="a" href="{{ route('carrier.medical-records.edit', $record) }}">
                                            <x-base.lucide class="w-4 h-4 mr-2" icon="edit" />
                                            Edit
                                        </x-base.menu.item>
                                        @if($record->documents_count > 0)
                                        <x-base.menu.item as="a" href="{{ route('carrier.medical-records.docs.show', $record->id) }}">
                                            <x-base.lucide class="w-4 h-4 mr-2" icon="file-text" />
                                            View Documents ({{ $record->documents_count }})
                                        </x-base.menu.item>
                                        @endif
                                        <x-base.menu.divider />
                                        <x-base.menu.item>
                                            <form action="{{ route('carrier.medical-records.destroy', $record) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this medical record?')" class="w-full">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex items-center w-full text-red-600 hover:text-red-700">
                                                    <x-base.lucide class="w-4 h-4 mr-2" icon="trash-2" />
                                                    Delete
                                                </button>
                                            </form>
                                        </x-base.menu.item>
                                    </x-base.menu.items>
                                </x-base.menu>
                            </x-base.table.td>
                            </x-base.table.tr>
                            @endforeach
                    </x-base.table.tbody>
                </x-base.table>
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-16">
                <x-base.lucide class="w-16 h-16 text-slate-300 mb-4" icon="file-x" />
                <h3 class="text-lg font-medium text-slate-500 mb-2">No medical records found</h3>
                <p class="text-slate-400 mb-6 text-center max-w-md">
                    No medical records match your current filters. Try adjusting your search criteria or create a new medical record.
                </p>
                <x-base.button as="a" href="{{ route('carrier.medical-records.create') }}" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                    Add First Medical Record
                </x-base.button>
            </div>
            @endif
        </div>
        <!-- Pagination -->
        @if($medicalRecords->hasPages())
        <div class="w-full">
            {{ $medicalRecords->links('custom.pagination') }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Auto-submit form when driver filter changes
    document.getElementById('driver_filter').addEventListener('change', function() {
        this.form.submit();
    });
</script>
@endpush
