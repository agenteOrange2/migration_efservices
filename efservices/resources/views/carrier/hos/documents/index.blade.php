@extends('../themes/' . $activeTheme)
@section('title', 'HOS Documents')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'HOS Dashboard', 'url' => route('carrier.hos.dashboard')],
        ['label' => 'Documents', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Professional Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Flash Messages -->
@if(session('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="CheckCircle" />
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
        {{ session('error') }}
    </div>
@endif

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="FileText" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">HOS Documents</h1>
                <p class="text-slate-600">Manage and download Hours of Service documents for all drivers</p>
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
                Document Monthly
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
    <div class="flex items-center gap-3 mb-6">
        <x-base.lucide class="w-5 h-5 text-primary" icon="Filter" />
        <h2 class="text-lg font-semibold text-slate-800">Filters</h2>
    </div>
    <form method="GET" action="{{ route('carrier.hos.documents.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-6">
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
            </x-base.tom-select>
        </div>
        <div>
            <x-base.form-label for="start_date">Start Date</x-base.form-label>
            <x-base.litepicker id="start_date" name="start_date" value="{{ $startDate }}" placeholder="Select Date" />
        </div>
        <div>
            <x-base.form-label for="end_date">End Date</x-base.form-label>
            <x-base.litepicker id="end_date" name="end_date" value="{{ $endDate }}" placeholder="Select Date" />
        </div>
        <div class="flex items-end">
            <x-base.button type="submit" variant="primary" class="w-full gap-2">
                <x-base.lucide class="w-4 h-4" icon="Search" />
                Apply Filters
            </x-base.button>
        </div>
    </form>
</div>

<!-- Documents List -->
<div class="box box--stacked p-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <x-base.lucide class="w-5 h-5 text-primary" icon="FileText" />
            <h2 class="text-lg font-semibold text-slate-800">Documents</h2>
            @if(isset($documents) && method_exists($documents, 'total') && $documents->total() > 0)
                <x-base.badge variant="secondary" class="ml-2">
                    {{ $documents->total() }} {{ Str::plural('document', $documents->total()) }}
                </x-base.badge>
            @endif
        </div>
    </div>

    @if($documents->isEmpty())
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
                data-tw-target="#generate-modal">
                <x-base.lucide class="w-4 h-4" icon="Plus" />
                Generate Document
            </x-base.button>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table table-hover w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left py-4 px-4">
                            <input type="checkbox" id="select-all" class="form-checkbox w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary" onclick="toggleSelectAll(this)">
                        </th>
                        <th class="text-left py-4 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Driver</th>
                        <th class="text-left py-4 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Document Type</th>
                        <th class="text-left py-4 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Date</th>
                        <th class="text-left py-4 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Size</th>
                        <th class="text-left py-4 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Status</th>
                        <th class="text-right py-4 px-4 font-semibold text-slate-700 uppercase text-xs tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                        @php
                            $driver = \App\Models\UserDriverDetail::find($document->model_id);
                        @endphp
                        <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-4">
                                <input type="checkbox" class="form-checkbox document-checkbox w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary" value="{{ $document->id }}">
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-slate-700 font-medium">
                                    {{ $driver ? implode(' ', array_filter([$driver->user->name ?? 'N/A', $driver->middle_name ?? '', $driver->last_name ?? ''])) : 'N/A' }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    @if($document->collection_name === 'trip_reports')
                                        <x-base.lucide class="w-4 h-4 text-primary" icon="Truck" />
                                        <span class="font-medium text-slate-700">Trip Report</span>
                                    @elseif($document->collection_name === 'daily_logs')
                                        <x-base.lucide class="w-4 h-4 text-success" icon="Calendar" />
                                        <span class="font-medium text-slate-700">Daily Log</span>
                                    @else
                                        <x-base.lucide class="w-4 h-4 text-info" icon="BarChart" />
                                        <span class="font-medium text-slate-700">Monthly Summary</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-slate-600">
                                    {{ \Carbon\Carbon::parse($document->getCustomProperty('document_date') ?? $document->created_at)->format('M d, Y') }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="text-slate-600">{{ number_format($document->size / 1024, 2) }} KB</span>
                            </td>
                            <td class="py-4 px-4">
                                @if($document->getCustomProperty('signed_at'))
                                    <x-base.badge variant="success" class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="CheckCircle" />
                                        Signed
                                    </x-base.badge>
                                @else
                                    <x-base.badge variant="secondary" class="gap-1.5">
                                        <x-base.lucide class="w-3 h-3" icon="FileText" />
                                        Unsigned
                                    </x-base.badge>
                                @endif
                            </td>
                            <td class="py-4 px-4">
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
                                        href="{{ route('carrier.hos.documents.download', $document->id) }}"
                                        variant="primary" 
                                        size="sm"
                                        class="gap-1.5">
                                        <x-base.lucide class="w-3.5 h-3.5" icon="Download" />
                                        Download
                                    </x-base.button>
                                    <form action="{{ route('carrier.hos.documents.destroy', $document->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this document?');">
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
        
        @if(method_exists($documents, 'hasPages') && $documents->hasPages())
            <div class="mt-6">
                {{ $documents->links('custom.pagination') }}
            </div>
        @endif
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

            <form action="{{ route('carrier.hos.documents.daily-log') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <x-base.form-label for="daily_driver_id">Select Driver</x-base.form-label>
                    <x-base.tom-select id="daily_driver_id" name="driver_id" class="w-full" required>
                        <option value="">Choose a driver...</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">
                                {{ implode(' ', array_filter([$driver->user->name ?? 'Driver #' . $driver->id, $driver->middle_name ?? '', $driver->last_name ?? ''])) }}
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

            <form action="{{ route('carrier.hos.documents.monthly-summary') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <x-base.form-label for="monthly_driver_id">Select Driver</x-base.form-label>
                    <x-base.tom-select id="monthly_driver_id" name="driver_id" class="w-full" required>
                        <option value="">Choose a driver...</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">
                                {{ implode(' ', array_filter([$driver->user->name ?? 'Driver #' . $driver->id, $driver->middle_name ?? '', $driver->last_name ?? ''])) }}
                            </option>
                        @endforeach
                    </x-base.tom-select>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div>
                        <x-base.form-label for="month">Month</x-base.form-label>
                        <x-base.tom-select id="month" name="month" class="w-full" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </x-base.tom-select>
                    </div>
                    <div>
                        <x-base.form-label for="year">Year</x-base.form-label>
                        <x-base.tom-select id="year" name="year" class="w-full" required>
                            @for($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </x-base.tom-select>
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

<!-- Document Monthly Modal -->
<x-base.dialog id="document-monthly-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5">
            <div class="text-center mb-5">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-amber-500" icon="FileText" />
                <div class="mt-5 text-2xl font-semibold text-slate-800">Document Monthly</div>
                <div class="mt-2 text-slate-500">
                    FMCSA format for drivers operating within 100/150 air-mile radius
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm text-amber-800">
                <strong>Includes:</strong> Date, Start Time, End Time, Total Hours, Driving Hours, Truck Number, Headquarters
            </div>

            <form action="{{ route('carrier.hos.documents.document-monthly') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <x-base.form-label for="doc_monthly_driver_id">Select Driver</x-base.form-label>
                    <x-base.tom-select id="doc_monthly_driver_id" name="driver_id" class="w-full" required>
                        <option value="">Choose a driver...</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">
                                {{ implode(' ', array_filter([$driver->user->name ?? 'Driver #' . $driver->id, $driver->middle_name ?? '', $driver->last_name ?? ''])) }}
                            </option>
                        @endforeach
                    </x-base.tom-select>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div>
                        <x-base.form-label for="doc_month">Month</x-base.form-label>
                        <x-base.tom-select id="doc_month" name="month" class="w-full" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </x-base.tom-select>
                    </div>
                    <div>
                        <x-base.form-label for="doc_year">Year</x-base.form-label>
                        <x-base.tom-select id="doc_year" name="year" class="w-full" required>
                            @for($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </x-base.tom-select>
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
    form.action = '{{ route("carrier.hos.documents.bulk-download") }}';
    
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
</script>

@endsection
