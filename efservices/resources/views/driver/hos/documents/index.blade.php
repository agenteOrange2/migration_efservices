@extends('../themes/' . $activeTheme)
@section('title', 'HOS Documents')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'HOS Dashboard', 'url' => route('driver.hos.dashboard')],
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
                <p class="text-slate-600">View and download your Hours of Service documents</p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
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
    </div>
</div>

<!-- Filters -->
<div class="box box--stacked p-6 mb-6">
    <form method="GET" action="{{ route('driver.hos.documents.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <x-base.form-label for="type">Document Type</x-base.form-label>
            <x-base.form-select id="type" name="type">
                <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Documents</option>
                <option value="trip_reports" {{ $type === 'trip_reports' ? 'selected' : '' }}>Trip Reports</option>
                <option value="daily_logs" {{ $type === 'daily_logs' ? 'selected' : '' }}>Daily Logs</option>
                <option value="monthly_summaries" {{ $type === 'monthly_summaries' ? 'selected' : '' }}>Monthly Summaries</option>
            </x-base.form-select>
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
                <x-base.lucide class="w-4 h-4" icon="Filter" />
                Apply Filters
            </x-base.button>
        </div>
    </form>
</div>

<!-- Documents List -->
<div class="box box--stacked p-6">
    @if($documents->isEmpty())
        <div class="text-center py-12">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="FileText" />
            <h3 class="text-lg font-semibold text-slate-700 mb-2">No Documents Found</h3>
            <p class="text-slate-500 mb-4">You don't have any HOS documents yet.</p>
            <x-base.button 
                type="button" 
                variant="primary"
                data-tw-toggle="modal"
                data-tw-target="#generate-modal">
                Generate Your First Document
            </x-base.button>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="text-left py-3 px-4 font-semibold text-slate-700">Document Type</th>
                        <th class="text-left py-3 px-4 font-semibold text-slate-700">Date</th>
                        <th class="text-left py-3 px-4 font-semibold text-slate-700">File Name</th>
                        <th class="text-left py-3 px-4 font-semibold text-slate-700">Size</th>
                        <th class="text-left py-3 px-4 font-semibold text-slate-700">Status</th>
                        <th class="text-right py-3 px-4 font-semibold text-slate-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    @if($document->collection_name === 'trip_reports')
                                        <x-base.lucide class="w-4 h-4 text-primary" icon="Truck" />
                                        <span class="font-medium">Trip Report</span>
                                    @elseif($document->collection_name === 'daily_logs')
                                        <x-base.lucide class="w-4 h-4 text-success" icon="Calendar" />
                                        <span class="font-medium">Daily Log</span>
                                    @else
                                        <x-base.lucide class="w-4 h-4 text-info" icon="BarChart" />
                                        <span class="font-medium">Monthly Summary</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ \Carbon\Carbon::parse($document->getCustomProperty('document_date') ?? $document->created_at)->format('M d, Y') }}
                            </td>
                            <td class="py-3 px-4 text-slate-600 text-sm">
                                {{ $document->file_name }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ number_format($document->size / 1024, 2) }} KB
                            </td>
                            <td class="py-3 px-4">
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
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <x-base.button 
                                        as="a" 
                                        href="{{ $document->getUrl() }}" 
                                        target="_blank"
                                        variant="outline-primary" 
                                        size="sm"
                                        class="gap-1">
                                        <x-base.lucide class="w-3 h-3" icon="Eye" />
                                        View
                                    </x-base.button>
                                    <x-base.button 
                                        as="a" 
                                        href="{{ route('driver.hos.documents.download', $document->id) }}"
                                        variant="primary" 
                                        size="sm"
                                        class="gap-1">
                                        <x-base.lucide class="w-3 h-3" icon="Download" />
                                        Download
                                    </x-base.button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Generate Document Modal -->
<x-base.dialog id="generate-modal" size="md">
    <x-base.dialog.panel>
        <div class="p-5">
            <div class="text-center mb-5">
                <x-base.lucide class="mx-auto mt-3 h-16 w-16 text-primary" icon="FileText" />
                <div class="mt-5 text-2xl font-semibold text-slate-800">Generate Document</div>
                <div class="mt-2 text-slate-500">
                    Select the type of document you want to generate
                </div>
            </div>

            <!-- Daily Log Form -->
            <div class="mb-6 p-4 border border-slate-200 rounded-lg">
                <h3 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-success" icon="Calendar" />
                    Daily Log
                </h3>
                <form action="{{ route('driver.hos.documents.daily-log') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <x-base.form-label for="daily_date">Select Date</x-base.form-label>
                        <x-base.litepicker id="daily_date" name="date" class="w-full" value="{{ now()->format('Y-m-d') }}" placeholder="Select Date" />
                    </div>
                    <x-base.button type="submit" variant="success" class="w-full">
                        Generate Daily Log
                    </x-base.button>
                </form>
            </div>

            <!-- Monthly Summary Form -->
            <div class="p-4 border border-slate-200 rounded-lg">
                <h3 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                    <x-base.lucide class="w-5 h-5 text-info" icon="BarChart" />
                    Monthly Summary
                </h3>
                <form action="{{ route('driver.hos.documents.monthly-summary') }}" method="POST">
                    @csrf
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
                    <x-base.button type="submit" variant="info" class="w-full">
                        Generate Monthly Summary
                    </x-base.button>
                </form>
            </div>

            <div class="mt-5 text-center">
                <x-base.button data-tw-dismiss="modal" type="button" variant="outline-secondary">
                    Cancel
                </x-base.button>
            </div>
        </div>
    </x-base.dialog.panel>
</x-base.dialog>

@endsection
