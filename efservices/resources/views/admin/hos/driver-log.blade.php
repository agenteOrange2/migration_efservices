@extends('../themes/' . $activeTheme)
@section('title', 'Driver HOS Log')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Hours of Service', 'url' => route('admin.hos.dashboard')],
        ['label' => $driver->carrier->name ?? 'Carrier', 'url' => route('admin.hos.carrier.detail', $driver->carrier_id)],
        ['label' => $driver->full_name, 'active' => true],
    ];
@endphp
@section('subcontent')
    <div>
        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="User" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ $driver->full_name }}</h1>
                        <p class="text-slate-600">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
                                {{ $driver->carrier->name ?? 'N/A' }}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('admin.hos.carrier.detail', $driver->carrier_id) }}" class="w-full sm:w-auto"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="ArrowLeft" />
                        Back to Carrier
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="box box--stacked mb-5">
            <div class="box-body p-5">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">From Date</label>
                        <x-base.litepicker id="filter_start_date" name="start_date" class="w-full" value="{{ $startDate }}" placeholder="Select Date" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">To Date</label>
                        <x-base.litepicker id="filter_end_date" name="end_date" class="w-full" value="{{ $endDate }}" placeholder="Select Date" />
                    </div>
                    <div class="flex items-end gap-2 md:col-span-2">
                        <x-base.button variant="primary" type="submit" class="flex items-center">
                            <x-base.lucide class="w-4 h-4 mr-1" icon="Search" />
                            Filter
                        </x-base.button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daily Summaries -->
        <div class="box box--stacked mb-5">
            <div class="box-header flex items-center justify-between p-5 border-b border-slate-200/60">
                <h2 class="text-lg font-semibold text-slate-800">Daily Summaries</h2>
            </div>
            <div class="box-body p-5">
                @if($dailyLogs->isEmpty())
                    <div class="text-center py-10">
                        <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="Calendar" />
                        <p class="text-slate-500">No data for selected period</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-slate-500 border-b border-slate-200/60">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Date</th>
                                    <th class="px-4 py-3 font-medium text-center">Driving</th>
                                    <th class="px-4 py-3 font-medium text-center">On Duty</th>
                                    <th class="px-4 py-3 font-medium text-center">Off Duty</th>
                                    <th class="px-4 py-3 font-medium text-center">Violations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyLogs as $log)
                                    <tr class="border-b border-slate-200/60 hover:bg-slate-50 @if($log->has_violations) bg-danger/5 @endif">
                                        <td class="px-4 py-4">
                                            <div class="font-medium text-slate-800">{{ $log->date->format('M j, Y') }}</div>
                                            <div class="text-xs text-slate-500">{{ $log->date->format('l') }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                                {{ $log->formatted_driving_time }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                                {{ $log->formatted_on_duty_time }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="text-slate-600">{{ $log->formatted_off_duty_time }}</span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if($log->has_violations)
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-danger/10 text-danger">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="AlertTriangle" />
                                                    Yes
                                                </span>
                                            @else
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="CheckCircle" />
                                                    No
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Detailed Entries -->
        <div class="box box--stacked">
            <div class="box-header flex items-center justify-between p-5 border-b border-slate-200/60">
                <h2 class="text-lg font-semibold text-slate-800">Detailed Entries</h2>
                @if($entries->isNotEmpty())
                <div class="flex items-center gap-2">
                    <span class="text-sm text-slate-500" id="selected-count">0 selected</span>
                    <x-base.button type="button" variant="danger" size="sm" class="gap-1" onclick="bulkDeleteEntries()" id="bulk-delete-btn" disabled>
                        <x-base.lucide class="w-4 h-4" icon="Trash2" />
                        Delete Selected
                    </x-base.button>
                </div>
                @endif
            </div>
            <div class="box-body p-5">
                @if($entries->isEmpty())
                    <div class="text-center py-10">
                        <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="Clock" />
                        <p class="text-slate-500">No entries for selected period</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-slate-500 border-b border-slate-200/60">
                                <tr>
                                    <th class="px-4 py-3 font-medium">
                                        <input type="checkbox" id="select-all" class="form-checkbox rounded border-slate-300" onclick="toggleSelectAll()">
                                    </th>
                                    <th class="px-4 py-3 font-medium">Date</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium text-center">Start</th>
                                    <th class="px-4 py-3 font-medium text-center">End</th>
                                    <th class="px-4 py-3 font-medium text-center">Duration</th>
                                    <th class="px-4 py-3 font-medium">Location</th>
                                    <th class="px-4 py-3 font-medium text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                    <tr class="border-b border-slate-200/60 hover:bg-slate-50">
                                        <td class="px-4 py-4">
                                            <input type="checkbox" class="entry-checkbox form-checkbox rounded border-slate-300" value="{{ $entry->id }}" onchange="updateSelectedCount()">
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="text-slate-800">{{ $entry->date->format('M j') }}</span>
                                        </td>
                                        <td class="px-4 py-4">
                                            @if($entry->status === 'on_duty_driving')
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Car" />
                                                    {{ $entry->status_name }}
                                                </span>
                                            @elseif($entry->status === 'on_duty_not_driving')
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Briefcase" />
                                                    {{ $entry->status_name }}
                                                </span>
                                            @else
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                                    <x-base.lucide class="w-3 h-3 inline mr-1" icon="Moon" />
                                                    {{ $entry->status_name }}
                                                </span>
                                            @endif
                                            @if($entry->is_manual_entry)
                                                <span class="ml-1 px-2 py-0.5 text-xs rounded bg-orange-100 text-orange-600">Manual</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center font-medium text-slate-800">
                                            {{ $entry->start_time->format('H:i') }}
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            {{ $entry->end_time ? $entry->end_time->format('H:i') : '-' }}
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="font-medium text-slate-800">{{ $entry->formatted_duration }}</span>
                                        </td>
                                        <td class="px-4 py-4">
                                            @if($entry->latitude && $entry->longitude)
                                                <a href="https://www.google.com/maps?q={{ $entry->latitude }},{{ $entry->longitude }}" 
                                                   target="_blank"
                                                   class="flex items-center gap-2 text-primary hover:text-primary/80 text-xs max-w-xs truncate">
                                                    <x-base.lucide class="w-3 h-3 flex-shrink-0" icon="MapPin" />
                                                    {{ $entry->latitude }}, {{ $entry->longitude }}
                                                    <x-base.lucide class="w-3 h-3 flex-shrink-0" icon="ExternalLink" />
                                                </a>
                                            @elseif($entry->formatted_address)
                                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($entry->formatted_address) }}" 
                                                   target="_blank"
                                                   class="flex items-center gap-2 text-primary hover:text-primary/80 text-xs max-w-xs truncate">
                                                    <x-base.lucide class="w-3 h-3 flex-shrink-0" icon="MapPin" />
                                                    {{ $entry->formatted_address }}
                                                    <x-base.lucide class="w-3 h-3 flex-shrink-0" icon="ExternalLink" />
                                                </a>
                                            @else
                                                <div class="flex items-center gap-2 text-slate-400 text-xs">
                                                    <x-base.lucide class="w-3 h-3 flex-shrink-0" icon="MapPin" />
                                                    Location unavailable
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" 
                                                    class="p-1.5 text-primary hover:bg-primary/10 rounded-lg transition-colors" 
                                                    title="Edit entry"
                                                    onclick="openEditModal({{ json_encode([
                                                        'id' => $entry->id,
                                                        'status' => $entry->status,
                                                        'start_time' => $entry->start_time->format('Y-m-d\TH:i'),
                                                        'end_time' => $entry->end_time ? $entry->end_time->format('Y-m-d\TH:i') : '',
                                                        'formatted_address' => $entry->formatted_address ?? ''
                                                    ]) }})">
                                                    <x-base.lucide class="w-4 h-4" icon="Pencil" />
                                                </button>
                                                <form action="{{ route('admin.hos.entry.delete', $entry->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this entry?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 text-danger hover:bg-danger/10 rounded-lg transition-colors" title="Delete entry">
                                                        <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bulk Delete Form (hidden) -->
        <form id="bulk-delete-form" action="{{ route('admin.hos.entries.bulk-delete') }}" method="POST" class="hidden">
            @csrf
            <input type="hidden" name="driver_id" value="{{ $driver->id }}">
            <div id="entry-ids-container"></div>
        </form>

        <!-- Edit Entry Modal -->
        <x-base.dialog id="edit-entry-modal" size="md">
            <x-base.dialog.panel>
                <div class="p-5">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <x-base.lucide class="w-6 h-6 text-primary" icon="Pencil" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">Edit HOS Entry</h3>
                            <p class="text-sm text-slate-500">Modify the entry details</p>
                        </div>
                    </div>

                    <form id="edit-entry-form" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="space-y-4">
                            <div>
                                <x-base.form-label for="edit_status">Status</x-base.form-label>
                                <x-base.form-select id="edit_status" name="status" required>
                                    <option value="on_duty_driving">On Duty - Driving</option>
                                    <option value="on_duty_not_driving">On Duty - Not Driving</option>
                                    <option value="off_duty">Off Duty</option>
                                </x-base.form-select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-base.form-label for="edit_start_date">Start Date</x-base.form-label>
                                    <x-base.form-input type="text" id="edit_start_date" class="datepicker" placeholder="M/D/Y" required />
                                </div>
                                <div>
                                    <x-base.form-label for="edit_start_time_input">Start Time</x-base.form-label>
                                    <x-base.form-input type="time" id="edit_start_time_input" required />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-base.form-label for="edit_end_date">End Date</x-base.form-label>
                                    <x-base.form-input type="text" id="edit_end_date" class="datepicker" placeholder="M/D/Y" />
                                </div>
                                <div>
                                    <x-base.form-label for="edit_end_time_input">End Time</x-base.form-label>
                                    <x-base.form-input type="time" id="edit_end_time_input" />
                                </div>
                            </div>
                            <input type="hidden" id="edit_start_time" name="start_time" />
                            <input type="hidden" id="edit_end_time" name="end_time" />

                            <div>
                                <x-base.form-label for="edit_formatted_address">Location</x-base.form-label>
                                <x-base.form-input type="text" id="edit_formatted_address" name="formatted_address" placeholder="e.g., Houston, TX" />
                            </div>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary" class="flex-1">
                                Cancel
                            </x-base.button>
                            <x-base.button type="submit" variant="primary" class="flex-1 gap-2">
                                <x-base.lucide class="w-4 h-4" icon="Save" />
                                Save Changes
                            </x-base.button>
                        </div>
                    </form>
                </div>
            </x-base.dialog.panel>
        </x-base.dialog>

        <!-- Generate Documents Section -->
        <div class="box box--stacked my-5">
            <div class="box-header flex items-center justify-between p-5 border-b border-slate-200/60">
                <h2 class="text-lg font-semibold text-slate-800">Generate Documents</h2>
            </div>
            <div class="box-body p-5">
                <!-- Success/Error Alert -->
                <div id="generate-alert" class="hidden mb-4 px-4 py-3 rounded-lg text-sm font-medium"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Daily Log -->
                    <div class="border border-slate-200 rounded-lg p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-success/10 rounded-lg flex items-center justify-center">
                                <x-base.lucide class="w-5 h-5 text-success" icon="Calendar" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800">Daily Log PDF</h3>
                                <p class="text-xs text-slate-500">Generate HOS log for a specific date</p>
                            </div>
                        </div>
                        <form id="daily-log-form">
                            <input type="hidden" name="driver_id" value="{{ $driver->id }}">
                            <div class="mb-3">
                                <x-base.form-label for="daily_date">Select Date</x-base.form-label>
                                <x-base.litepicker id="daily_date" name="date" class="w-full" value="{{ $endDate ?? now()->format('Y-m-d') }}" placeholder="Select Date" />
                            </div>
                            <x-base.button type="submit" variant="success" class="w-full gap-2 text-white" id="daily-log-btn">
                                <x-base.lucide class="w-4 h-4" icon="FileText" />
                                <span>Generate Daily Log</span>
                            </x-base.button>
                        </form>
                    </div>

                    <!-- Monthly Summary -->
                    <div class="border border-slate-200 rounded-lg p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-info/10 rounded-lg flex items-center justify-center">
                                <x-base.lucide class="w-5 h-5 text-info" icon="BarChart" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800">Monthly Summary PDF</h3>
                                <p class="text-xs text-slate-500">Generate summary for a month</p>
                            </div>
                        </div>
                        <form id="monthly-summary-form">
                            <input type="hidden" name="driver_id" value="{{ $driver->id }}">
                            <div class="grid grid-cols-2 gap-3 mb-3">
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
                            <x-base.button type="submit" variant="primary" class="w-full gap-2" id="monthly-summary-btn">
                                <x-base.lucide class="w-4 h-4" icon="FileText" />
                                <span>Generate Monthly Summary</span>
                            </x-base.button>
                        </form>
                    </div>

                    <!-- FMCSA Monthly -->
                    <div class="border border-slate-200 rounded-lg p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-warning/10 rounded-lg flex items-center justify-center">
                                <x-base.lucide class="w-5 h-5 text-warning" icon="ClipboardList" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800">FMCSA Monthly PDF</h3>
                                <p class="text-xs text-slate-500">Intermittent driver format</p>
                            </div>
                        </div>
                        <form id="fmcsa-monthly-form">
                            <input type="hidden" name="driver_id" value="{{ $driver->id }}">
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <x-base.form-label for="fmcsa_month">Month</x-base.form-label>
                                    <x-base.form-select id="fmcsa_month" name="month" required>
                                        @for($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                            </option>
                                        @endfor
                                    </x-base.form-select>
                                </div>
                                <div>
                                    <x-base.form-label for="fmcsa_year">Year</x-base.form-label>
                                    <x-base.form-select id="fmcsa_year" name="year" required>
                                        @for($y = now()->year; $y >= 2020; $y--)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </x-base.form-select>
                                </div>
                            </div>
                            <x-base.button type="submit" variant="warning" class="w-full gap-2 text-white" id="fmcsa-monthly-btn">
                                <x-base.lucide class="w-4 h-4" icon="FileText" />
                                <span>Generate FMCSA Monthly</span>
                            </x-base.button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generated Documents Table -->
        <div class="box box--stacked">
            <div class="box-header flex items-center justify-between p-5 border-b border-slate-200/60">
                <h2 class="text-lg font-semibold text-slate-800">
                    <x-base.lucide class="w-5 h-5 inline mr-1" icon="FolderOpen" />
                    Generated Documents
                </h2>
                <span class="text-sm text-slate-500" id="doc-count">{{ $hosDocuments->count() }} document(s)</span>
            </div>
            <div class="box-body p-5">
                <div id="documents-table-container">
                    @if($hosDocuments->isEmpty())
                        <div class="text-center py-10" id="no-documents-msg">
                            <x-base.lucide class="w-16 h-16 mx-auto text-slate-300 mb-4" icon="FileX" />
                            <p class="text-slate-500">No documents generated yet</p>
                            <p class="text-xs text-slate-400 mt-1">Use the forms above to generate Daily Log or Monthly Summary PDFs</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left" id="documents-table">
                                <thead class="text-slate-500 border-b border-slate-200/60">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">Document</th>
                                        <th class="px-4 py-3 font-medium">Type</th>
                                        <th class="px-4 py-3 font-medium text-center">Size</th>
                                        <th class="px-4 py-3 font-medium text-center">Generated</th>
                                        <th class="px-4 py-3 font-medium text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hosDocuments as $doc)
                                        <tr class="border-b border-slate-200/60 hover:bg-slate-50">
                                            <td class="px-4 py-4">
                                                <div class="flex items-center gap-3">
                                                    @php
                                                        $isFmcsa = $doc->getCustomProperty('document_type') === 'fmcsa_monthly';
                                                        $iconBg = $doc->collection_name === 'daily_logs' ? 'bg-success/10' : ($isFmcsa ? 'bg-warning/10' : 'bg-info/10');
                                                        $iconColor = $doc->collection_name === 'daily_logs' ? 'text-success' : ($isFmcsa ? 'text-warning' : 'text-info');
                                                    @endphp
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $iconBg }}">
                                                        <x-base.lucide class="w-4 h-4 {{ $iconColor }}" icon="FileText" />
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-slate-800">{{ $doc->file_name }}</div>
                                                        <div class="text-xs text-slate-500">{{ $doc->name }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                @if($doc->collection_name === 'daily_logs')
                                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">Daily Log</span>
                                                @elseif($doc->getCustomProperty('document_type') === 'fmcsa_monthly')
                                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning">FMCSA Monthly</span>
                                                @else
                                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-info/10 text-info">Monthly Summary</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-center text-slate-600">
                                                {{ number_format($doc->size / 1024, 1) }} KB
                                            </td>
                                            <td class="px-4 py-4 text-center text-slate-600">
                                                {{ $doc->created_at->format('m/d/Y H:i') }}
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <a href="{{ route('admin.hos.documents.preview', $doc->id) }}" target="_blank"
                                                       class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Preview">
                                                        <x-base.lucide class="w-4 h-4" icon="Eye" />
                                                    </a>
                                                    <a href="{{ route('admin.hos.documents.download', $doc->id) }}" 
                                                       class="p-1.5 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="Download">
                                                        <x-base.lucide class="w-4 h-4" icon="Download" />
                                                    </a>
                                                    <form action="{{ route('admin.hos.documents.destroy', $doc->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this document?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1.5 text-danger hover:bg-danger/10 rounded-lg transition-colors" title="Delete">
                                                            <x-base.lucide class="w-4 h-4" icon="Trash2" />
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleSelectAll() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.entry-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.entry-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selected-count').textContent = count + ' selected';
            document.getElementById('bulk-delete-btn').disabled = count === 0;
        }

        function bulkDeleteEntries() {
            const checkboxes = document.querySelectorAll('.entry-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one entry to delete.');
                return;
            }

            if (!confirm('Are you sure you want to delete ' + checkboxes.length + ' entries? This action cannot be undone.')) {
                return;
            }

            const container = document.getElementById('entry-ids-container');
            container.innerHTML = '';
            
            checkboxes.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'entry_ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });

            document.getElementById('bulk-delete-form').submit();
        }

        let startDatePicker, endDatePicker;

        // Initialize Litepicker on page load
        document.addEventListener('DOMContentLoaded', function() {
            startDatePicker = new Litepicker({
                element: document.getElementById('edit_start_date'),
                format: 'M/D/YYYY',
                singleMode: true,
                autoApply: true,
                dropdowns: {
                    minYear: 2020,
                    maxYear: null,
                    months: true,
                    years: true
                }
            });

            endDatePicker = new Litepicker({
                element: document.getElementById('edit_end_date'),
                format: 'M/D/YYYY',
                singleMode: true,
                autoApply: true,
                dropdowns: {
                    minYear: 2020,
                    maxYear: null,
                    months: true,
                    years: true
                }
            });

            // Before form submit, combine date and time into hidden fields
            document.getElementById('edit-entry-form').addEventListener('submit', function(e) {
                const startDate = document.getElementById('edit_start_date').value;
                const startTime = document.getElementById('edit_start_time_input').value;
                const endDate = document.getElementById('edit_end_date').value;
                const endTime = document.getElementById('edit_end_time_input').value;

                if (startDate && startTime) {
                    const startDateTime = convertToISO(startDate, startTime);
                    document.getElementById('edit_start_time').value = startDateTime;
                }

                if (endDate && endTime) {
                    const endDateTime = convertToISO(endDate, endTime);
                    document.getElementById('edit_end_time').value = endDateTime;
                } else {
                    document.getElementById('edit_end_time').value = '';
                }
            });
        });

        function convertToISO(dateStr, timeStr) {
            // Parse M/D/YYYY format
            const parts = dateStr.split('/');
            const month = parts[0].padStart(2, '0');
            const day = parts[1].padStart(2, '0');
            const year = parts[2];
            return `${year}-${month}-${day}T${timeStr}`;
        }

        function parseDateTime(isoString) {
            if (!isoString) return { date: '', time: '' };
            const dt = new Date(isoString);
            const month = dt.getMonth() + 1;
            const day = dt.getDate();
            const year = dt.getFullYear();
            const hours = String(dt.getHours()).padStart(2, '0');
            const minutes = String(dt.getMinutes()).padStart(2, '0');
            return {
                date: `${month}/${day}/${year}`,
                time: `${hours}:${minutes}`
            };
        }

        // ===== AJAX Document Generation =====
        function showAlert(message, type = 'success') {
            const alert = document.getElementById('generate-alert');
            alert.className = type === 'success' 
                ? 'mb-4 px-4 py-3 rounded-lg text-sm font-medium bg-success/10 text-success border border-success/20'
                : 'mb-4 px-4 py-3 rounded-lg text-sm font-medium bg-danger/10 text-danger border border-danger/20';
            alert.textContent = message;
            alert.classList.remove('hidden');
            setTimeout(() => alert.classList.add('hidden'), 5000);
        }

        function refreshDocumentsTable(documents) {
            const container = document.getElementById('documents-table-container');
            const docCount = document.getElementById('doc-count');
            docCount.textContent = documents.length + ' document(s)';

            if (documents.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-slate-300 mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="9.5" y1="12.5" x2="14.5" y2="17.5"/><line x1="14.5" y1="12.5" x2="9.5" y2="17.5"/></svg>
                        <p class="text-slate-500">No documents generated yet</p>
                        <p class="text-xs text-slate-400 mt-1">Use the forms above to generate Daily Log or Monthly Summary PDFs</p>
                    </div>`;
                return;
            }

            let html = `<div class="overflow-x-auto"><table class="w-full text-sm text-left" id="documents-table">
                <thead class="text-slate-500 border-b border-slate-200/60">
                    <tr>
                        <th class="px-4 py-3 font-medium">Document</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium text-center">Size</th>
                        <th class="px-4 py-3 font-medium text-center">Generated</th>
                        <th class="px-4 py-3 font-medium text-center">Actions</th>
                    </tr>
                </thead><tbody>`;

            documents.forEach(doc => {
                const isDaily = doc.collection_name === 'daily_logs';
                const isFmcsa = doc.document_type === 'fmcsa_monthly';
                const colorClass = isDaily ? 'success' : (isFmcsa ? 'warning' : 'info');
                const typeLabel = isDaily ? 'Daily Log' : (isFmcsa ? 'FMCSA Monthly' : 'Monthly Summary');
                const sizeKB = (doc.size / 1024).toFixed(1);

                html += `<tr class="border-b border-slate-200/60 hover:bg-slate-50">
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-${colorClass}/10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-${colorClass}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800">${doc.file_name}</div>
                                <div class="text-xs text-slate-500">${doc.name}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-${colorClass}/10 text-${colorClass}">${typeLabel}</span>
                    </td>
                    <td class="px-4 py-4 text-center text-slate-600">${sizeKB} KB</td>
                    <td class="px-4 py-4 text-center text-slate-600">${doc.created_at}</td>
                    <td class="px-4 py-4 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="${doc.preview_url}" target="_blank" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors inline-block" title="Preview">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="${doc.download_url}" class="p-1.5 text-primary hover:bg-primary/10 rounded-lg transition-colors inline-block" title="Download">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        function setButtonLoading(btn, loading) {
            const span = btn.querySelector('span');
            if (loading) {
                btn.disabled = true;
                btn.classList.add('opacity-75');
                if (span) span.textContent = 'Generating...';
            } else {
                btn.disabled = false;
                btn.classList.remove('opacity-75');
            }
        }

        // Daily Log AJAX
        document.getElementById('daily-log-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('daily-log-btn');
            const span = btn.querySelector('span');
            const originalText = span.textContent;
            setButtonLoading(btn, true);

            const formData = new FormData(this);

            fetch('{{ route("admin.hos.documents.daily-log") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    refreshDocumentsTable(data.documents);
                } else {
                    showAlert(data.message || 'Failed to generate daily log.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while generating the daily log.', 'error');
            })
            .finally(() => {
                setButtonLoading(btn, false);
                span.textContent = originalText;
            });
        });

        // Monthly Summary AJAX
        document.getElementById('monthly-summary-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('monthly-summary-btn');
            const span = btn.querySelector('span');
            const originalText = span.textContent;
            setButtonLoading(btn, true);

            const formData = new FormData(this);

            fetch('{{ route("admin.hos.documents.monthly-summary") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    refreshDocumentsTable(data.documents);
                } else {
                    showAlert(data.message || 'Failed to generate monthly summary.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while generating the monthly summary.', 'error');
            })
            .finally(() => {
                setButtonLoading(btn, false);
                span.textContent = originalText;
            });
        });

        // FMCSA Monthly AJAX
        document.getElementById('fmcsa-monthly-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('fmcsa-monthly-btn');
            const span = btn.querySelector('span');
            const originalText = span.textContent;
            setButtonLoading(btn, true);

            const formData = new FormData(this);

            fetch('{{ route("admin.hos.documents.document-monthly") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    refreshDocumentsTable(data.documents);
                } else {
                    showAlert(data.message || 'Failed to generate FMCSA Monthly.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while generating the FMCSA Monthly.', 'error');
            })
            .finally(() => {
                setButtonLoading(btn, false);
                span.textContent = originalText;
            });
        });

        function openEditModal(entry) {
            // Set form action URL
            const form = document.getElementById('edit-entry-form');
            form.action = '{{ url("admin/hos/entry") }}/' + entry.id;

            // Populate form fields
            document.getElementById('edit_status').value = entry.status;

            // Parse start time
            const start = parseDateTime(entry.start_time);
            document.getElementById('edit_start_date').value = start.date;
            document.getElementById('edit_start_time_input').value = start.time;
            if (startDatePicker) startDatePicker.setDate(start.date);

            // Parse end time
            const end = parseDateTime(entry.end_time);
            document.getElementById('edit_end_date').value = end.date;
            document.getElementById('edit_end_time_input').value = end.time;
            if (endDatePicker) endDatePicker.setDate(end.date || null);

            document.getElementById('edit_formatted_address').value = entry.formatted_address || '';

            // Open modal
            const modal = tailwind.Modal.getOrCreateInstance(document.getElementById('edit-entry-modal'));
            modal.show();
        }
    </script>
    @endpush
@endsection
