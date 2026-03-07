@extends('../themes/' . $activeTheme)
@section('title', 'Forgive Violation')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'HOS', 'url' => route('admin.hos.dashboard')],
        ['label' => 'Violations', 'url' => route('admin.hos.violations')],
        ['label' => 'Details', 'url' => route('admin.hos.violations.show', $violation)],
        ['label' => 'Forgive', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Flash Messages -->
@if(session('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger mb-5">
        <div class="flex items-center mb-2">
            <x-base.lucide class="w-6 h-6 mr-2" icon="AlertCircle" />
            <span class="font-semibold">Please correct the following errors:</span>
        </div>
        <ul class="list-disc list-inside ml-8">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 rounded-xl bg-warning/10 border border-warning/20">
                <x-base.lucide class="w-8 h-8 text-warning" icon="ShieldOff" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 mb-1">Forgive Violation</h1>
                <p class="text-slate-500">
                    {{ $violation->violation_type_name ?? ucfirst(str_replace('_', ' ', $violation->violation_type ?? 'Violation')) }}
                </p>
            </div>
        </div>
        <a href="{{ route('admin.hos.violations.show', $violation) }}">
            <x-base.button variant="outline-secondary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
                Back to Details
            </x-base.button>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Warning Alert -->
        <div class="box box--stacked p-6 bg-warning/5 border-warning/20">
            <div class="flex items-start gap-4">
                <div class="p-2 rounded-lg bg-warning/10">
                    <x-base.lucide class="w-6 h-6 text-warning" icon="AlertTriangle" />
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 mb-2">Important Notice</h3>
                    <p class="text-sm text-slate-600 mb-2">
                        Forgiving this violation will:
                    </p>
                    <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
                        <li>Remove any active penalties associated with this violation</li>
                        <li>Allow the driver to resume driving immediately</li>
                        <li>If you adjust the trip end time, the HOS entries will be recalculated</li>
                        <li>This action will be recorded for audit purposes</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Forgiveness Form -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-primary" icon="FileEdit" />
                <h2 class="text-lg font-semibold text-slate-800">Forgiveness Details</h2>
            </div>

            <form action="{{ route('admin.hos.violations.forgive', $violation) }}" method="POST">
                @csrf

                <!-- Adjusted End Time (for forgot_to_close_trip violations) -->
                @if($violation->trip && in_array($violation->violation_type, ['forgot_to_close_trip', 'driving_limit_exceeded', 'duty_period_exceeded']))
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Adjusted Trip End Time
                        <span class="text-slate-400 font-normal">(Optional)</span>
                    </label>
                    <input
                        type="datetime-local"
                        name="adjusted_end_time"
                        value="{{ old('adjusted_end_time') }}"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                        max="{{ now()->format('Y-m-d\TH:i') }}"
                    >
                    <p class="mt-2 text-sm text-slate-500">
                        @if($violation->trip->actual_start_time)
                            Trip started: {{ $violation->trip->actual_start_time->format('M d, Y h:i A') }}
                        @endif
                        @if($violation->trip->actual_end_time ?? $violation->trip->auto_stopped_at)
                            <br>Original end time: {{ ($violation->trip->actual_end_time ?? $violation->trip->auto_stopped_at)->format('M d, Y h:i A') }}
                        @endif
                    </p>
                    @error('adjusted_end_time')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <!-- Justification -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Justification <span class="text-danger">*</span>
                    </label>
                    <textarea
                        name="forgiveness_reason"
                        rows="5"
                        class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                        placeholder="Please provide a detailed reason for forgiving this violation (minimum 10 characters)..."
                        required
                    >{{ old('forgiveness_reason') }}</textarea>
                    <p class="mt-2 text-sm text-slate-500">
                        Explain why this violation should be forgiven. This will be recorded for compliance and audit purposes.
                    </p>
                    @error('forgiveness_reason')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirmation -->
                <div class="mb-6 p-4 bg-slate-50 rounded-lg">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="confirm_forgiveness"
                            value="1"
                            class="mt-1 w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary"
                            {{ old('confirm_forgiveness') ? 'checked' : '' }}
                            required
                        >
                        <span class="text-sm text-slate-700">
                            I confirm that I want to forgive this violation and understand that this action will:
                            <ul class="mt-2 list-disc list-inside text-slate-600">
                                <li>Remove the driver's current driving restriction</li>
                                <li>Clear any mandatory rest period penalties</li>
                                <li>Be permanently recorded with my user ID and timestamp</li>
                            </ul>
                        </span>
                    </label>
                    @error('confirm_forgiveness')
                        <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center gap-4">
                    <x-base.button type="submit" variant="warning" class="gap-2">
                        <x-base.lucide class="w-4 h-4" icon="ShieldOff" />
                        Forgive Violation
                    </x-base.button>
                    <a href="{{ route('admin.hos.violations.show', $violation) }}">
                        <x-base.button type="button" variant="outline-secondary">
                            Cancel
                        </x-base.button>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Violation Summary -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-danger" icon="AlertOctagon" />
                <h2 class="text-lg font-semibold text-slate-800">Violation Summary</h2>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Type</label>
                    <p class="mt-1 text-slate-800 font-medium">
                        {{ $violation->violation_type_name ?? ucfirst(str_replace('_', ' ', $violation->violation_type ?? 'N/A')) }}
                    </p>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Severity</label>
                    <div class="mt-1">
                        @if($violation->violation_severity === 'critical')
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                                Critical
                            </span>
                        @elseif($violation->violation_severity === 'moderate')
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                Moderate
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">
                                Minor
                            </span>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Date</label>
                    <p class="mt-1 text-slate-800">
                        {{ $violation->violation_date?->format('M d, Y') ?? 'N/A' }}
                    </p>
                </div>
                @if($violation->penalty_type)
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Current Penalty</label>
                    <p class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                            {{ ucfirst(str_replace('_', ' ', $violation->penalty_type)) }}
                        </span>
                    </p>
                </div>
                @endif
                @if($violation->penalty_end)
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Penalty Ends</label>
                    <p class="mt-1 text-slate-800">
                        {{ $violation->penalty_end->format('M d, Y h:i A') }}
                    </p>
                </div>
                @endif
            </div>
        </div>

        <!-- Driver Information -->
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-primary" icon="User" />
                <h2 class="text-lg font-semibold text-slate-800">Driver</h2>
            </div>

            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                    <x-base.lucide class="w-6 h-6 text-primary" icon="User" />
                </div>
                <div>
                    <p class="font-semibold text-slate-800">
                        {{ implode(' ', array_filter([$violation->driver->user->name ?? 'N/A', $violation->driver->middle_name ?? '', $violation->driver->last_name ?? ''])) }}
                    </p>
                    <p class="text-sm text-slate-500">{{ $violation->driver->user->email ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Related Trip -->
        @if($violation->trip)
        <div class="box box--stacked p-6">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200/60">
                <x-base.lucide class="w-5 h-5 text-info" icon="MapPin" />
                <h2 class="text-lg font-semibold text-slate-800">Related Trip</h2>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Trip Number</label>
                    <p class="mt-1 text-slate-800 font-medium">{{ $violation->trip->trip_number }}</p>
                </div>
                @if($violation->trip->actual_start_time)
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Started</label>
                    <p class="mt-1 text-slate-800">{{ $violation->trip->actual_start_time->format('M d, Y h:i A') }}</p>
                </div>
                @endif
                @if($violation->trip->actual_end_time ?? $violation->trip->auto_stopped_at)
                <div>
                    <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Ended (Auto-stopped)</label>
                    <p class="mt-1 text-slate-800">
                        {{ ($violation->trip->actual_end_time ?? $violation->trip->auto_stopped_at)->format('M d, Y h:i A') }}
                    </p>
                </div>
                @endif
                <a href="{{ route('admin.trips.show', $violation->trip) }}" class="block mt-4">
                    <x-base.button variant="outline-secondary" size="sm" class="w-full gap-2">
                        <x-base.lucide class="w-4 h-4" icon="ExternalLink" />
                        View Trip Details
                    </x-base.button>
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
