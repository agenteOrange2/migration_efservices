@extends('../themes/' . $activeTheme)
@section('title', 'HOS Documents')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'HOS Dashboard', 'url' => route('admin.hos.dashboard')],
        ['label' => 'Documents', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Flash Messages -->
@if(session('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
        {{ session('error') }}
    </div>
@endif

<!-- Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="FileText" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">HOS Documents</h1>
                <p class="text-slate-600">Manage and download Hours of Service documents across all carriers</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <x-base.button 
                type="button" 
                variant="success" 
                class="gap-2"
                data-tw-toggle="modal"
                data-tw-target="#daily-log-modal">
                <x-base.lucide class="w-4 h-4" icon="Calendar" />
                Daily Log
            </x-base.button>
            <x-base.button 
                type="button" 
                variant="info" 
                class="gap-2"
                data-tw-toggle="modal"
                data-tw-target="#monthly-summary-modal">
                <x-base.lucide class="w-4 h-4" icon="BarChart" />
                Monthly Summary
            </x-base.button>
            <x-base.button 
                type="button" 
                variant="warning" 
                class="gap-2"
                data-tw-toggle="modal"
                data-tw-target="#document-monthly-modal">
                <x-base.lucide class="w-4 h-4" icon="FileText" />
                FMCSA Monthly
            </x-base.button>
            @if($documents->isNotEmpty())
                <x-base.button 
                    type="button" 
                    variant="primary" 
                    class="gap-2"
                    onclick="bulkDownload()">
                    <x-base.lucide class="w-4 h-4" icon="Download" />
                    Bulk Download
                </x-base.button>
            @endif
        </div>
    </div>
</div>


<!-- Filters -->
<div class="box box--stacked p-6 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <x-base.lucide class="w-5 h-5 text-primary" icon="Filter" />
        <h3 class="text-base font-semibold text-slate-800">Filter Documents</h3>
    </div>
    <form method="GET" action="{{ route('admin.hos.documents.index') }}" id="filter-form">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <x-base.form-label for="carrier_id">Carrier</x-base.form-label>
                <x-base.tom-select id="carrier_id" name="carrier_id" class="w-full">
                    <option value="">All Carriers</option>
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}" {{ $carrierId == $carrier->id ? 'selected' : '' }}>
                            {{ $carrier->name }}
                        </option>
                    @endforeach
                </x-base.tom-select>
            </div>
            <div>
                <x-base.form-label for="driver_id">Driver</x-base.form-label>
                <x-base.tom-select id="driver_id" name="driver_id" class="w-full">
                    <option value="">All Drivers</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ $driverId == $driver->id ? 'selected' : '' }}>
                            {{ implode(' ', array_filter([$driver->user->name ?? 'Driver #' . $driver->id, $driver->middle_name ?? '', $driver->last_name ?? ''])) }}
                        </option>
                    @endforeach
                </x-base.tom-select>
            </div>
            <div>
                <x-base.form-label for="type">Document Type</x-base.form-label>
                <x-base.tom-select id="type" name="type" class="w-full">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Documents</option>
                    <option value="trip_reports" {{ $type === 'trip_reports' ? 'selected' : '' }}>Trip Reports</option>
                    <option value="daily_logs" {{ $type === 'daily_logs' ? 'selected' : '' }}>Daily Logs</option>
                    <option value="monthly_summaries" {{ $type === 'monthly_summaries' ? 'selected' : '' }}>Monthly Summaries</option>
                    <option value="fmcsa_monthly" {{ $type === 'fmcsa_monthly' ? 'selected' : '' }}>FMCSA Monthly</option>
                </x-base.tom-select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <x-base.form-label for="start_date">From Date</x-base.form-label>
                <x-base.litepicker id="start_date" name="start_date" value="{{ $startDate }}" placeholder="Select start date" />
            </div>
            <div>
                <x-base.form-label for="end_date">To Date</x-base.form-label>
                <x-base.litepicker id="end_date" name="end_date" value="{{ $endDate }}" placeholder="Select end date" />
            </div>
            <div class="flex items-end gap-2">
                <x-base.button type="submit" variant="primary" class="flex-1 gap-2">
                    <x-base.lucide class="w-4 h-4" icon="Search" />
                    Search
                </x-base.button>
                <a href="{{ route('admin.hos.documents.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors h-[38px]">
                    <x-base.lucide class="w-4 h-4" icon="X" />
                    Clear
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Documents List -->
<div class="box box--stacked p-6">
    @if(empty($documentsByCarrier))
        <div class="text-center py-16">
            <div class="p-4 bg-slate-100 rounded-full w-20 h-20 mx-auto mb-4 flex items-center justify-center">
                <x-base.lucide class="w-10 h-10 text-slate-400" icon="FileText" />
            </div>
            <h3 class="text-lg font-semibold text-slate-800 mb-2">No Documents Found</h3>
            <p class="text-slate-500 mb-6">No HOS documents match your filters. Try adjusting your search criteria or generate a new document.</p>
            <x-base.button 
                type="button" 
                variant="primary"
                class="gap-2"
                data-tw-toggle="modal"
                data-tw-target="#daily-log-modal">
                <x-base.lucide class="w-4 h-4" icon="Plus" />
                Generate Document
            </x-base.button>
        </div>
    @else
        <!-- Bulk Actions Bar -->
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <input type="checkbox" id="select-all" class="form-checkbox w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary" onclick="toggleSelectAll(this)">
                <label for="select-all" class="text-sm font-medium text-slate-700">Select All</label>
                <span class="text-sm text-slate-500" id="selected-count">0 selected</span>
            </div>
            <div class="flex gap-2">
                <x-base.button
                    type="button"
                    variant="primary"
                    size="sm"
                    class="gap-2"
                    onclick="bulkDownload()"
                    id="bulk-download-btn"
                    style="display: none;">
                    <x-base.lucide class="w-4 h-4" icon="Download" />
                    Download Selected
                </x-base.button>
                <x-base.button
                    type="button"
                    variant="danger"
                    size="sm"
                    class="gap-2"
                    onclick="bulkDelete()"
                    id="bulk-delete-btn"
                    style="display: none;">
                    <x-base.lucide class="w-4 h-4" icon="Trash2" />
                    Delete Selected
                </x-base.button>
            </div>
        </div>

        <!-- Hierarchical Document List -->
        <div class="space-y-6">
            @foreach($documentsByCarrier as $carrierIdKey => $carrierGroup)
                @php
                    $carrierData = $carrierGroup['carrier'];
                    $carrierDrivers = $carrierGroup['drivers'];
                    $totalDocs = collect($carrierDrivers)->sum(fn($d) => $d['documents']->count());
                @endphp
                
                <!-- Carrier Section -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <!-- Carrier Header -->
                    <div class="bg-gradient-to-r from-primary/5 to-primary/10 border-b border-primary/20">
                        <button 
                            type="button"
                            class="w-full px-6 py-4 flex items-center justify-between hover:bg-primary/5 transition-colors"
                            onclick="toggleCarrierSection('carrier-{{ $carrierIdKey }}')">
                            <div class="flex items-center gap-4">
                                <div class="p-2 bg-white rounded-lg shadow-sm">
                                    <x-base.lucide class="w-5 h-5 text-primary" icon="Building2" />
                                </div>
                                <div class="text-left">
                                    <h3 class="text-lg font-bold text-slate-800">{{ $carrierData->name }}</h3>
                                    <p class="text-sm text-slate-600">
                                        {{ count($carrierDrivers) }} {{ Str::plural('driver', count($carrierDrivers)) }} • 
                                        {{ $totalDocs }} {{ Str::plural('document', $totalDocs) }}
                                    </p>
                                </div>
                            </div>
                            <x-base.lucide class="w-5 h-5 text-slate-400 transition-transform carrier-chevron" icon="ChevronDown" />
                        </button>
                    </div>

                    <!-- Carrier Content (Collapsible) -->
                    <div id="carrier-{{ $carrierIdKey }}" class="carrier-content">
                        @foreach($carrierDrivers as $driverIdKey => $driverGroup)
                            @php
                                $driverData = $driverGroup['driver'];
                                $driverDocuments = $driverGroup['documents'];
                            @endphp

                            <!-- Driver Section -->
                            <div class="border-b border-slate-100 last:border-b-0">
                                <!-- Driver Header -->
                                <div class="bg-slate-50/50 px-6 py-3 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-1.5 bg-white rounded-lg border border-slate-200">
                                            <x-base.lucide class="w-4 h-4 text-slate-600" icon="User" />
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.drivers.show', $driverData->id) }}" 
                                               class="font-semibold text-slate-800 hover:text-primary transition-colors">
                                                {{ implode(' ', array_filter([$driverData->user->name ?? 'Driver #' . $driverData->id, $driverData->middle_name ?? '', $driverData->last_name ?? ''])) }}
                                            </a>
                                            <p class="text-xs text-slate-500">
                                                {{ $driverDocuments->count() }} {{ Str::plural('document', $driverDocuments->count()) }}
                                            </p>
                                        </div>
                                    </div>
                                    <x-base.badge variant="soft-secondary" class="text-xs">
                                        Driver ID: {{ $driverData->id }}
                                    </x-base.badge>
                                </div>

                                <!-- Documents Table -->
                                <div class="bg-white">
                                    <table class="w-full">
                                        <thead class="bg-slate-50/30">
                                            <tr class="border-b border-slate-100">
                                                <th class="text-left py-3 px-6 w-12">
                                                    <span class="sr-only">Select</span>
                                                </th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-600 uppercase text-xs tracking-wide">Type</th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-600 uppercase text-xs tracking-wide">Date</th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-600 uppercase text-xs tracking-wide">Size</th>
                                                <th class="text-left py-3 px-4 font-semibold text-slate-600 uppercase text-xs tracking-wide">Status</th>
                                                <th class="text-right py-3 px-6 font-semibold text-slate-600 uppercase text-xs tracking-wide">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($driverDocuments as $document)
                                                <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                                    <td class="py-3 px-6">
                                                        <input type="checkbox" class="form-checkbox document-checkbox w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary" value="{{ $document->id }}" onchange="updateSelectedCount()">
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        <div class="flex items-center gap-2">
                                                            @if($document->collection_name === 'trip_reports')
                                                                <x-base.lucide class="w-4 h-4 text-primary" icon="Truck" />
                                                                <span class="font-medium text-sm">Trip Report</span>
                                                            @elseif($document->collection_name === 'daily_logs')
                                                                <x-base.lucide class="w-4 h-4 text-success" icon="Calendar" />
                                                                <span class="font-medium text-sm">Daily Log</span>
                                                            @elseif($document->getCustomProperty('document_type') === 'fmcsa_monthly')
                                                                <x-base.lucide class="w-4 h-4 text-amber-500" icon="FileText" />
                                                                <span class="font-medium text-sm">FMCSA Monthly</span>
                                                            @else
                                                                <x-base.lucide class="w-4 h-4 text-info" icon="BarChart" />
                                                                <span class="font-medium text-sm">Monthly Summary</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        <div class="flex items-center gap-2">
                                                            <x-base.lucide class="w-3.5 h-3.5 text-slate-400" icon="Calendar" />
                                                            <span class="text-sm text-slate-600">
                                                                {{ \Carbon\Carbon::parse($document->getCustomProperty('document_date') ?? $document->created_at)->format('M d, Y') }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        <div class="flex items-center gap-2">
                                                            <x-base.lucide class="w-3.5 h-3.5 text-slate-400" icon="HardDrive" />
                                                            <span class="text-sm text-slate-600">{{ number_format($document->size / 1024, 2) }} KB</span>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        @if($document->getCustomProperty('signed_at'))
                                                            <x-base.badge variant="success" class="gap-1.5 text-xs">
                                                                <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                                                                Signed
                                                            </x-base.badge>
                                                        @else
                                                            <x-base.badge variant="secondary" class="gap-1.5 text-xs">
                                                                <x-base.lucide class="w-3 h-3" icon="FileText" />
                                                                Unsigned
                                                            </x-base.badge>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 px-6">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <x-base.button 
                                                                as="a" 
                                                                href="{{ $document->getUrl() }}" 
                                                                target="_blank"
                                                                variant="outline-primary" 
                                                                size="sm"
                                                                class="gap-1.5">
                                                                <x-base.lucide class="w-3.5 h-3.5" icon="Eye" />
                                                                View
                                                            </x-base.button>
                                                            <x-base.button 
                                                                as="a" 
                                                                href="{{ route('admin.hos.documents.download', $document->id) }}"
                                                                variant="primary" 
                                                                size="sm"
                                                                class="gap-1.5">
                                                                <x-base.lucide class="w-3.5 h-3.5" icon="Download" />
                                                                Download
                                                            </x-base.button>
                                                            <form action="{{ route('admin.hos.documents.destroy', $document->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <x-base.button 
                                                                    type="submit"
                                                                    variant="outline-danger" 
                                                                    size="sm"
                                                                    class="gap-1.5">
                                                                    <x-base.lucide class="w-3.5 h-3.5" icon="Trash2" />
                                                                    Delete
                                                                </x-base.button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Daily Log Modal -->
<x-base.dialog id="daily-log-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5">
            <div class="text-center mb-5">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-success" icon="Calendar" />
                <div class="mt-5 text-2xl font-semibold text-slate-800">Generate Daily Log</div>
                <div class="mt-2 text-slate-500">
                    Generate a daily HOS log for a specific driver and date
                </div>
            </div>

            <form action="{{ route('admin.hos.documents.daily-log') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <x-base.form-label for="daily_driver_id">Select Driver</x-base.form-label>
                    <x-base.tom-select id="daily_driver_id" name="driver_id" class="w-full" data-placeholder="Choose a driver...">
                        <option value="">Choose a driver...</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">
                                {{ implode(' ', array_filter([$driver->user->name ?? 'Driver #' . $driver->id, $driver->middle_name ?? '', $driver->last_name ?? ''])) }} 
                                ({{ $driver->carrier->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </x-base.tom-select>
                </div>
                <div class="mb-5">
                    <x-base.form-label for="daily_date">Select Date</x-base.form-label>
                    <x-base.litepicker id="daily_date" name="date" class="w-full" value="{{ now()->format('Y-m-d') }}" placeholder="Select Date" />
                </div>
                <div class="flex gap-3">
                    <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="flex-1">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="success" class="flex-1 gap-2">
                        <x-base.lucide class="w-4 h-4" icon="FileText" />
                        Generate
                    </x-base.button>
                </div>
            </form>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>

<!-- Monthly Summary Modal -->
<x-base.dialog id="monthly-summary-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5">
            <div class="text-center mb-5">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-info" icon="BarChart" />
                <div class="mt-5 text-2xl font-semibold text-slate-800">Generate Monthly Summary</div>
                <div class="mt-2 text-slate-500">
                    Generate a monthly HOS summary report for a driver
                </div>
            </div>

            <form action="{{ route('admin.hos.documents.monthly-summary') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <x-base.form-label for="monthly_driver_id">Select Driver</x-base.form-label>
                    <x-base.tom-select id="monthly_driver_id" name="driver_id" class="w-full" data-placeholder="Choose a driver...">
                        <option value="">Choose a driver...</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">
                                {{ implode(' ', array_filter([$driver->user->name ?? 'Driver #' . $driver->id, $driver->middle_name ?? '', $driver->last_name ?? ''])) }} 
                                ({{ $driver->carrier->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </x-base.tom-select>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div>
                        <x-base.form-label for="month">Month</x-base.form-label>
                        <x-base.form-select id="month" name="month" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </x-base.form-select>
                    </div>
                    <div>
                        <x-base.form-label for="year">Year</x-base.form-label>
                        <x-base.form-select id="year" name="year" required>
                            @for($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </x-base.form-select>
                    </div>
                </div>
                <div class="flex gap-3">
                    <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="flex-1">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="info" class="flex-1 gap-2">
                        <x-base.lucide class="w-4 h-4" icon="FileText" />
                        Generate
                    </x-base.button>
                </div>
            </form>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>

<!-- FMCSA Monthly Modal -->
<x-base.dialog id="document-monthly-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5">
            <div class="text-center mb-5">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-amber-500" icon="FileText" />
                <div class="mt-5 text-2xl font-semibold text-slate-800">FMCSA Monthly Document</div>
                <div class="mt-2 text-slate-500">
                    FMCSA format for drivers operating within 100/150 air-mile radius
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm text-amber-800">
                <strong>Includes:</strong> Date, Start Time, End Time, Total Hours, Driving Hours, Truck Number, Headquarters
            </div>

            <form action="{{ route('admin.hos.documents.document-monthly') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <x-base.form-label for="doc_monthly_driver_id">Select Driver</x-base.form-label>
                    <x-base.tom-select id="doc_monthly_driver_id" name="driver_id" class="w-full" data-placeholder="Choose a driver...">
                        <option value="">Choose a driver...</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">
                                {{ implode(' ', array_filter([$driver->user->name ?? 'Driver #' . $driver->id, $driver->middle_name ?? '', $driver->last_name ?? ''])) }} 
                                ({{ $driver->carrier->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </x-base.tom-select>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div>
                        <x-base.form-label for="doc_month">Month</x-base.form-label>
                        <x-base.form-select id="doc_month" name="month" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </x-base.form-select>
                    </div>
                    <div>
                        <x-base.form-label for="doc_year">Year</x-base.form-label>
                        <x-base.form-select id="doc_year" name="year" required>
                            @for($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </x-base.form-select>
                    </div>
                </div>
                <div class="flex gap-3">
                    <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="flex-1">
                        Cancel
                    </x-base.button>
                    <x-base.button type="submit" variant="warning" class="flex-1 gap-2">
                        <x-base.lucide class="w-4 h-4" icon="FileText" />
                        Generate
                    </x-base.button>
                </div>
            </form>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>


<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.document-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.document-checkbox:checked');
    const count = checkboxes.length;
    const countElement = document.getElementById('selected-count');
    const bulkDownloadBtn = document.getElementById('bulk-download-btn');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const selectAllCheckbox = document.getElementById('select-all');

    if (countElement) {
        countElement.textContent = count + ' selected';
    }

    if (bulkDownloadBtn) {
        bulkDownloadBtn.style.display = count > 0 ? 'flex' : 'none';
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.style.display = count > 0 ? 'flex' : 'none';
    }

    // Update select-all checkbox state
    const allCheckboxes = document.querySelectorAll('.document-checkbox');
    if (selectAllCheckbox && allCheckboxes.length > 0) {
        selectAllCheckbox.checked = count === allCheckboxes.length;
        selectAllCheckbox.indeterminate = count > 0 && count < allCheckboxes.length;
    }
}

function toggleCarrierSection(carrierId) {
    const section = document.getElementById(carrierId);
    const button = section.previousElementSibling.querySelector('.carrier-chevron');
    
    if (section.style.display === 'none') {
        section.style.display = 'block';
        button.style.transform = 'rotate(0deg)';
    } else {
        section.style.display = 'none';
        button.style.transform = 'rotate(-90deg)';
    }
}

function bulkDownload() {
    const checkboxes = document.querySelectorAll('.document-checkbox:checked');
    const documentIds = Array.from(checkboxes).map(cb => cb.value);

    if (documentIds.length === 0) {
        alert('Please select at least one document to download.');
        return;
    }

    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.hos.documents.bulk-download") }}';

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);

    documentIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'document_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function bulkDelete() {
    const checkboxes = document.querySelectorAll('.document-checkbox:checked');
    const documentIds = Array.from(checkboxes).map(cb => cb.value);

    if (documentIds.length === 0) {
        alert('Please select at least one document to delete.');
        return;
    }

    const confirmed = confirm(`Are you sure you want to delete ${documentIds.length} document(s)? This action cannot be undone.`);

    if (!confirmed) {
        return;
    }

    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.hos.documents.bulk-destroy") }}';

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);

    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'DELETE';
    form.appendChild(methodField);

    documentIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'document_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

@endsection
