@extends('../themes/' . $activeTheme)
@section('title', 'Import Results')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Bulk Import', 'url' => route('admin.imports.index')],
        ['label' => 'Results', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Success/Error Header -->
@php
    $totalProcessed = $result['imported_count'] + ($result['updated_count'] ?? 0);
@endphp
@if($result['success'] && $totalProcessed > 0)
<div class="box box--stacked p-8 mb-8 bg-success/5 border-success/20">
    <div class="flex items-center gap-4">
        <div class="p-3 rounded-xl bg-success/10">
            <x-base.lucide class="w-8 h-8 text-success" icon="CheckCircle" />
        </div>
        <div>
            <h1 class="text-2xl font-bold text-success mb-1">Import Completed Successfully</h1>
            <p class="text-slate-600">
                @if($result['imported_count'] > 0)
                    {{ $result['imported_count'] }} {{ $typeName }} record(s) imported
                @endif
                @if(($result['updated_count'] ?? 0) > 0)
                    @if($result['imported_count'] > 0) and @endif
                    {{ $result['updated_count'] }} record(s) updated
                @endif
                @if($carrierName && $carrierName !== 'N/A')
                    to {{ $carrierName }}
                @endif.
            </p>
        </div>
    </div>
</div>
@else
<div class="box box--stacked p-8 mb-8 bg-warning/5 border-warning/20">
    <div class="flex items-center gap-4">
        <div class="p-3 rounded-xl bg-warning/10">
            <x-base.lucide class="w-8 h-8 text-warning" icon="AlertTriangle" />
        </div>
        <div>
            <h1 class="text-2xl font-bold text-warning mb-1">Import Completed with Issues</h1>
            <p class="text-slate-600">
                No records were imported. Please review the errors below.
            </p>
        </div>
    </div>
</div>
@endif

<!-- Summary Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-success/10">
                <x-base.lucide class="w-5 h-5 text-success" icon="Plus" />
            </div>
            <div>
                <p class="text-xs text-slate-500">Created</p>
                <p class="text-xl font-bold text-success">{{ $result['imported_count'] }}</p>
            </div>
        </div>
    </div>
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-info/10">
                <x-base.lucide class="w-5 h-5 text-info" icon="RefreshCw" />
            </div>
            <div>
                <p class="text-xs text-slate-500">Updated</p>
                <p class="text-xl font-bold text-info">{{ $result['updated_count'] ?? 0 }}</p>
            </div>
        </div>
    </div>
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-warning/10">
                <x-base.lucide class="w-5 h-5 text-warning" icon="SkipForward" />
            </div>
            <div>
                <p class="text-xs text-slate-500">Skipped</p>
                <p class="text-xl font-bold text-warning">{{ $result['skipped_count'] }}</p>
            </div>
        </div>
    </div>
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-danger/10">
                <x-base.lucide class="w-5 h-5 text-danger" icon="XCircle" />
            </div>
            <div>
                <p class="text-xs text-slate-500">Failed</p>
                <p class="text-xl font-bold text-danger">{{ $result['failed_count'] }}</p>
            </div>
        </div>
    </div>
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-slate-100">
                <x-base.lucide class="w-5 h-5 text-slate-600" icon="Calculator" />
            </div>
            <div>
                <p class="text-xs text-slate-500">Total</p>
                <p class="text-xl font-bold text-slate-800">
                    {{ $result['imported_count'] + ($result['updated_count'] ?? 0) + $result['skipped_count'] + $result['failed_count'] }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Skipped Rows -->
@if(count($result['skipped_rows']) > 0)
<div class="box box--stacked mb-6">
    <div class="p-4 border-b border-slate-200/60 bg-warning/5">
        <div class="flex items-center gap-2">
            <x-base.lucide class="w-5 h-5 text-warning" icon="SkipForward" />
            <h2 class="font-semibold text-slate-800">Skipped Rows ({{ count($result['skipped_rows']) }})</h2>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 border-b">Row</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 border-b">Reason</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 border-b">Data Preview</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($result['skipped_rows'], 0, 50) as $row)
                    <tr class="hover:bg-slate-50 border-b border-slate-100">
                        <td class="px-4 py-3 font-mono text-xs">{{ $row['row'] }}</td>
                        <td class="px-4 py-3">
                            <span class="text-warning">{{ $row['reason'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs font-mono max-w-md truncate">
                            {{ json_encode(array_slice($row['data'] ?? [], 0, 5)) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(count($result['skipped_rows']) > 50)
        <div class="p-4 text-center text-sm text-slate-500 border-t border-slate-200/60">
            Showing first 50 of {{ count($result['skipped_rows']) }} skipped rows.
        </div>
    @endif
</div>
@endif

<!-- Validation Failures -->
@if(count($result['failures']) > 0)
<div class="box box--stacked mb-6">
    <div class="p-4 border-b border-slate-200/60 bg-danger/5">
        <div class="flex items-center gap-2">
            <x-base.lucide class="w-5 h-5 text-danger" icon="XCircle" />
            <h2 class="font-semibold text-slate-800">Validation Failures ({{ count($result['failures']) }})</h2>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 border-b">Row</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 border-b">Field</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 border-b">Errors</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($result['failures'], 0, 50) as $failure)
                    <tr class="hover:bg-slate-50 border-b border-slate-100">
                        <td class="px-4 py-3 font-mono text-xs">{{ $failure['row'] ?? 'N/A' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs bg-slate-100 rounded">{{ $failure['attribute'] ?? 'N/A' }}</span>
                        </td>
                        <td class="px-4 py-3 text-danger">
                            @if(is_array($failure['errors'] ?? null))
                                {{ implode(', ', $failure['errors']) }}
                            @else
                                {{ $failure['errors'] ?? 'Unknown error' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(count($result['failures']) > 50)
        <div class="p-4 text-center text-sm text-slate-500 border-t border-slate-200/60">
            Showing first 50 of {{ count($result['failures']) }} failures.
        </div>
    @endif
</div>
@endif

<!-- Action Buttons -->
<div class="box box--stacked p-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-sm text-slate-600">
            <p>Import Type: <span class="font-medium">{{ $typeName }}</span></p>
            @if($carrierName && $carrierName !== 'N/A')
                <p>Carrier: <span class="font-medium">{{ $carrierName }}</span></p>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <a href="{{ route('admin.imports.index') }}">
                <x-base.button variant="primary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="Upload" />
                    Import More Data
                </x-base.button>
            </a>

            @if($type === 'vehicles')
                <a href="{{ route('admin.admin-vehicles.index') }}">
                    <x-base.button variant="outline-primary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                        View Vehicles
                    </x-base.button>
                </a>
            @endif
        </div>
    </div>
</div>

@endsection
