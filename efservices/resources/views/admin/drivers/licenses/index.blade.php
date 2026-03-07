@extends('../themes/' . $activeTheme)
@section('title', 'Driver Licenses')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('admin.dashboard')],
['label' => 'Driver Licenses', 'active' => true],
];
@endphp

@section('subcontent')
<div>
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
                    <x-base.lucide class="w-8 h-8 text-primary" icon="UserCheck" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Driver Licenses Management</h1>
                    <p class="text-slate-600">Manage and track driver licenses</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button as="a" href="{{ route('admin.licenses.create') }}" class="w-full sm:w-auto" variant="primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                    Add New License
                </x-base.button>
                <x-base.button as="a" href="{{ route('admin.licenses.docs.all') }}" class="w-full sm:w-auto" variant="outline-primary">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="file-text" />
                    View All Documents
                </x-base.button>
            </div>
        </div>
    </div>
    <!-- Filtros y búsqueda -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <h3 class="box-title">Filter Licenses</h3>
        </div>
        <div class="box-body p-5">
            <form action="{{ route('admin.licenses.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-base.form-label for="search_term">Search</x-base.form-label>
                    <x-base.form-input type="text" name="search_term" id="search_term" value="{{ request('search_term') }}" placeholder="License number, state..." />
                </div>
                <div>
                    <x-base.form-label for="carrier_filter">Carrier</x-base.form-label>
                    <select id="carrier_filter" name="carrier_filter" class="w-full text-sm border-slate-200 shadow-sm rounded-md py-2 px-3 pr-8">
                        <option value="">All Carriers</option>
                        @foreach ($carriers as $carrier)
                        <option value="{{ $carrier->id }}" {{ request('carrier_filter') == $carrier->id ? 'selected' : '' }}>
                            {{ $carrier->name }}
                        </option>
                        @endforeach
                    </select>
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
                            <x-base.form-label for="date_to">Expiration Date (to)</x-base.form-label>
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

    <!-- Lista de licencias -->
    <div class="box box--stacked mt-5 p-3">
        <div class="box-header">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <h3 class="box-title">Licenses ({{ $licenses->total() ?? 0 }})</h3>
            </div>
        </div>

        @if($licenses->count() > 0)
        <div class="box-body p-0">
            <div class="overflow-x-auto">
                <x-base.table class="border-separate border-spacing-y-[10px]">
                    <x-base.table.thead>
                        <x-base.table.tr>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('admin.licenses.index', ['sort_field' => 'created_at', 'sort_direction' => request('sort_field') == 'created_at' && request('sort_direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center">
                                    Created At
                                    @if (request('sort_field') == 'created_at')
                                    @if (request('sort_direction') == 'asc')
                                    <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                    @else
                                    <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                    @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Driver
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Carrier
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('admin.licenses.index', ['sort_field' => 'license_number', 'sort_direction' => request('sort_field') == 'license_number' && request('sort_direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center">
                                    License Number
                                    @if (request('sort_field') == 'license_number')
                                    @if (request('sort_direction') == 'asc')
                                    <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                    @else
                                    <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                    @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                <a href="{{ route('admin.licenses.index', ['sort_field' => 'expiration_date', 'sort_direction' => request('sort_field') == 'expiration_date' && request('sort_direction') == 'asc' ? 'desc' : 'asc']) }}" class="flex items-center">
                                    Expiration Date
                                    @if (request('sort_field') == 'expiration_date')
                                    @if (request('sort_direction') == 'asc')
                                    <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-up" />
                                    @else
                                    <x-base.lucide class="w-4 h-4 ml-2" icon="chevron-down" />
                                    @endif
                                    @endif
                                </a>
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Documents
                            </x-base.table.th>
                            <x-base.table.th class="whitespace-nowrap">
                                Actions
                            </x-base.table.th>
                        </x-base.table.tr>
                    </x-base.table.thead>
                    <x-base.table.tbody>
                        @forelse ($licenses as $license)
                        <x-base.table.tr>
                            <x-base.table.td class="px-6 py-4">{{ $license->created_at->format('m/d/Y') }}</x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                @if($license->driverDetail)
                                {{ implode(' ', array_filter([$license->driverDetail->user->name ?? '', $license->driverDetail->middle_name, $license->driverDetail->last_name])) ?: '---' }}
                                @else
                                ---
                                @endif
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">
                                {{ $license->driverDetail->carrier->name ?? '---' }}
                            </x-base.table.td>
                            <x-base.table.td class="px-6 py-4">{{ $license->license_number }}</x-base.table.td>
                            <x-base.table.td class="px-6 py-4">{{ $license->expiration_date ? \Carbon\Carbon::parse($license->expiration_date)->format('m/d/Y') : '---' }}</x-base.table.td>
                            <x-base.table.td class="px-0 py-4">
                                @php
                                $docsCount = $documentCounts[$license->id] ?? 0;
                                @endphp
                                <a href="{{ route('admin.licenses.docs.show', $license->id) }}" class="flex items-center">
                                    <span class="bg-primary/20 text-primary rounded px-2 py-1 text-xs flex gap-2 items-center">
                                        <x-base.lucide class="w-3 h-3 inline-block" icon="file-text" />
                                        {{ $docsCount }} {{ Str::plural('Document', $docsCount) }}
                                    </span>
                                </a>
                            </x-base.table.td>
                            <x-base.table.td>
                                <x-base.menu class="h-5">
                                    <x-base.menu.button class="h-5 w-5 text-slate-500">
                                        <x-base.lucide class="h-5 w-5 fill-slate-400/70 stroke-slate-400/70"
                                            icon="MoreVertical" />
                                    </x-base.menu.button>

                                    <x-base.menu.items class="w-40">
                                        <div class="flex  flex-col gap-3">
                                            <a href="{{ route('admin.licenses.show', $license->id) }}"
                                                class="flex mr-1 text-primary" title="View Documents">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="file-text" />
                                                Documents
                                                <span class="ml-1">
                                                    ({{ \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', \App\Models\Admin\Driver\DriverLicense::class)->where('model_id', $license->id)->whereIn('collection_name', ['license_front', 'license_back'])->count() }})
                                                </span>
                                            </a>
                                            <a href="{{ route('admin.licenses.edit', $license->id) }}" class="btn btn-sm btn-primary flex">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="edit" />
                                                Edit
                                            </a>
                                            <a href="{{ route('admin.licenses.contact', $license->id) }}" class="btn btn-sm flex">
                                                <x-base.lucide class="w-4 h-4 mr-3" icon="mail" />
                                                Contact Driver
                                            </a>
                                            <form action="{{ route('admin.licenses.destroy', $license->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this license record?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm text-red-600  flex">
                                                    <x-base.lucide class="w-4 h-4 mr-3" icon="trash-2" />
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </x-base.menu.items>
                                </x-base.menu>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @empty
                        <x-base.table.tr>
                            <x-base.table.td colspan="7" class="text-center">
                                <div class="flex flex-col items-center justify-center py-16">
                                    <x-base.lucide class="h-8 w-8 text-slate-400" icon="Users" />
                                    No Licenses found
                                </div>
                            </x-base.table.td>
                        </x-base.table.tr>
                        @endforelse
                    </x-base.table.tbody>
                </x-base.table>
            </div>
        </div>
        <!-- Paginación -->
        <div class="box-footer py-5 px-8">
            {{ $licenses->links('custom.pagination') }}
        </div>
        @else
        <div class="box-body p-10 text-center">
            <div class="flex flex-col items-center justify-center py-8">
                <x-base.lucide class="w-16 h-16 text-slate-300" icon="file-text" />
                <div class="mt-5 text-slate-500">
                    No license records found.
                </div>
                <x-base.button as="a" href="{{ route('admin.licenses.create') }}" class="mt-5">
                    <x-base.lucide class="w-4 h-4 mr-1" icon="plus" />
                    Add License
                </x-base.button>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection