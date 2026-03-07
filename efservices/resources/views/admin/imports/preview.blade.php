@extends('../themes/' . $activeTheme)
@section('title', 'Preview Import')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Bulk Import', 'url' => route('admin.imports.index')],
        ['label' => 'Preview', 'active' => true],
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
            <div class="p-3 rounded-xl bg-info/10 border border-info/20">
                <x-base.lucide class="w-8 h-8 text-info" icon="Eye" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 mb-1">Preview Import: {{ $typeName }}</h1>
                <p class="text-slate-500">
                    Review your data before importing
                    @if($carrierName && $carrierName !== 'N/A')
                        to {{ $carrierName }}
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('admin.imports.index') }}">
            <x-base.button variant="outline-secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back
            </x-base.button>
        </a>
    </div>
</div>

<!-- Summary Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-slate-100">
                <x-base.lucide class="w-5 h-5 text-slate-600" icon="FileText" />
            </div>
            <div>
                <p class="text-xs text-slate-500">Total Rows</p>
                <p class="text-xl font-bold text-slate-800">{{ $preview['total'] }}</p>
            </div>
        </div>
    </div>
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-success/10">
                <x-base.lucide class="w-5 h-5 text-success" icon="CheckCircle" />
            </div>
            <div>
                <p class="text-xs text-slate-500">Valid</p>
                <p class="text-xl font-bold text-success">{{ $preview['valid'] }}</p>
            </div>
        </div>
    </div>
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-warning/10">
                <x-base.lucide class="w-5 h-5 text-warning" icon="Copy" />
            </div>
            <div>
                <p class="text-xs text-slate-500">Duplicates</p>
                <p class="text-xl font-bold text-warning">{{ $preview['duplicates'] }}</p>
            </div>
        </div>
    </div>
    <div class="box box--stacked p-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-danger/10">
                <x-base.lucide class="w-5 h-5 text-danger" icon="AlertCircle" />
            </div>
            <div>
                <p class="text-xs text-slate-500">Errors</p>
                <p class="text-xl font-bold text-danger">{{ $preview['errors'] }}</p>
            </div>
        </div>
    </div>
</div>

@if($preview['preview_limited'] ?? false)
<div class="alert alert-warning flex items-center mb-5">
    <x-base.lucide class="w-6 h-6 mr-2" icon="Info" />
    Showing first 100 rows of {{ $preview['total'] }} total rows. All rows will be processed during import.
</div>
@endif

<!-- Preview Table -->
<div class="box box--stacked mb-6">
    <div class="p-4 border-b border-slate-200/60">
        <h2 class="font-semibold text-slate-800">Data Preview</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50">
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 border-b">Row</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 border-b">Status</th>
                    @if(!empty($preview['headers']))
                        @foreach($preview['headers'] as $header)
                            <th class="px-4 py-3 text-left font-semibold text-slate-700 border-b whitespace-nowrap">
                                {{ ucwords(str_replace('_', ' ', $header)) }}
                            </th>
                        @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($preview['rows'] as $row)
                    <tr class="hover:bg-slate-50 border-b border-slate-100
                        @if($row['status'] === 'error') bg-danger/5
                        @elseif($row['status'] === 'duplicate') bg-warning/5
                        @endif">
                        <td class="px-4 py-3 font-mono text-xs">{{ $row['row'] }}</td>
                        <td class="px-4 py-3">
                            @if($row['status'] === 'valid')
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                    <x-base.lucide class="w-3 h-3" icon="Check" />
                                    Valid
                                </span>
                            @elseif($row['status'] === 'duplicate')
                                <div>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                        <x-base.lucide class="w-3 h-3" icon="Copy" />
                                        Duplicate
                                    </span>
                                    <div class="text-xs text-warning mt-1">{{ $row['message'] }}</div>
                                </div>
                            @else
                                <div>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                                        <x-base.lucide class="w-3 h-3" icon="X" />
                                        Error
                                    </span>
                                    <div class="text-xs text-danger mt-1">{{ $row['message'] }}</div>
                                </div>
                            @endif
                        </td>
                        @foreach($preview['headers'] as $header)
                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate" title="{{ $row['data'][$header] ?? '' }}">
                                {{ \Illuminate\Support\Str::limit($row['data'][$header] ?? '-', 30) }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($preview['headers'] ?? []) + 2 }}" class="px-4 py-8 text-center text-slate-500">
                            No data found in the file.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Action Buttons -->
<div class="box box--stacked p-6">
    <form action="{{ route('admin.imports.execute') }}" method="POST" id="import-form">
        @csrf
        <input type="hidden" name="import_type" value="{{ $type }}">
        <input type="hidden" name="carrier_id" value="{{ $carrierId }}">
        <input type="hidden" name="temp_path" value="{{ $tempPath }}">

        @if($preview['duplicates'] > 0)
        <div class="mb-6 p-4 bg-warning/5 border border-warning/20 rounded-lg">
            <h3 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-warning" icon="Copy" />
                Duplicate Handling ({{ $preview['duplicates'] }} duplicates found)
            </h3>
            <div class="flex flex-col sm:flex-row gap-4">
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-slate-50 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                    <input type="radio" name="duplicate_action" value="skip" checked class="form-radio text-primary">
                    <div>
                        <span class="font-medium text-slate-800">Skip Duplicates</span>
                        <p class="text-xs text-slate-500">Only import new records, ignore duplicates</p>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-slate-50 transition-colors has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                    <input type="radio" name="duplicate_action" value="update" class="form-radio text-primary">
                    <div>
                        <span class="font-medium text-slate-800">Update Duplicates</span>
                        <p class="text-xs text-slate-500">Replace existing records with new data</p>
                    </div>
                </label>
            </div>
        </div>
        @else
        <input type="hidden" name="duplicate_action" value="skip">
        @endif

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-slate-600">
                @if($preview['valid'] > 0)
                    <p class="flex items-center gap-2">
                        <x-base.lucide class="w-4 h-4 text-success" icon="CheckCircle" />
                        <span id="valid-count">{{ $preview['valid'] }}</span> row(s) will be imported.
                    </p>
                @endif
                @if($preview['duplicates'] > 0)
                    <p class="flex items-center gap-2 mt-1" id="duplicate-message">
                        <x-base.lucide class="w-4 h-4 text-warning" icon="Copy" />
                        <span id="duplicate-action-text">{{ $preview['duplicates'] }} duplicate(s) will be skipped.</span>
                    </p>
                @endif
                @if($preview['errors'] > 0)
                    <p class="flex items-center gap-2 mt-1">
                        <x-base.lucide class="w-4 h-4 text-danger" icon="AlertCircle" />
                        {{ $preview['errors'] }} row(s) with errors will be skipped.
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('admin.imports.index') }}">
                    <x-base.button type="button" variant="outline-secondary" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="X" />
                        Cancel
                    </x-base.button>
                </a>

                @if($preview['valid'] > 0 || $preview['duplicates'] > 0)
                    <x-base.button type="submit" variant="primary" class="gap-2" id="import-btn">
                        <x-base.lucide class="w-4 h-4" icon="Upload" />
                        <span id="import-btn-text">Import {{ $preview['valid'] }} Row(s)</span>
                    </x-base.button>
                @else
                    <x-base.button type="button" variant="secondary" disabled class="gap-2 opacity-50 cursor-not-allowed">
                        <x-base.lucide class="w-4 h-4" icon="Upload" />
                        No Valid Rows to Import
                    </x-base.button>
                @endif
            </div>
        </div>
    </form>
</div>


@endsection
