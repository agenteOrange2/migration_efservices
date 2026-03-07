@extends('../themes/' . $activeTheme)
@section('title', 'FMCSA HOS Configuration')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('carrier.dashboard')],
        ['label' => 'HOS', 'url' => route('carrier.hos.dashboard')],
        ['label' => 'FMCSA Configuration', 'active' => true],
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

<!-- Professional Header -->
<div class="box box--stacked p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Settings" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">FMCSA HOS Configuration</h1>
                <p class="text-slate-600">Configure Hours of Service settings for your carrier</p>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('carrier.hos.fmcsa.configuration.update') }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="grid grid-cols-12 gap-6">
        <!-- Left Column -->
        <div class="col-span-12 lg:col-span-6 space-y-6">
            <!-- FMCSA Mode -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Shield" />
                    <h2 class="text-lg font-semibold text-slate-800">FMCSA Mode</h2>
                </div>
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label for="fmcsa_texas_mode" class="text-sm font-medium text-slate-700 cursor-pointer">
                                Enable FMCSA Texas Intrastate Mode
                            </label>
                            <p class="text-xs text-slate-500 mt-1">Enforces 37 TAC §4.11 & §4.12 regulations</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="fmcsa_texas_mode" 
                                   name="fmcsa_texas_mode" value="1"
                                   {{ old('fmcsa_texas_mode', $config->fmcsa_texas_mode ?? false) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Cycle -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Calendar" />
                    <h2 class="text-lg font-semibold text-slate-800">Weekly Cycle</h2>
                </div>
                <div class="space-y-3">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="cycle_type" 
                                   id="cycle_60_7" value="60_7"
                                   {{ old('cycle_type', $config->cycle_type ?? '70_8') == '60_7' ? 'checked' : '' }}>
                            <label class="form-check-label text-sm font-medium text-slate-700 cursor-pointer" for="cycle_60_7">
                                60 hours / 7 days
                            </label>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="cycle_type" 
                                   id="cycle_70_8" value="70_8"
                                   {{ old('cycle_type', $config->cycle_type ?? '70_8') == '70_8' ? 'checked' : '' }}>
                            <label class="form-check-label text-sm font-medium text-slate-700 cursor-pointer" for="cycle_70_8">
                                70 hours / 8 days
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-span-12 lg:col-span-6 space-y-6">
            <!-- Reset Options -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="RotateCcw" />
                    <h2 class="text-lg font-semibold text-slate-800">Reset Options</h2>
                </div>
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label for="allow_24_hour_reset" class="text-sm font-medium text-slate-700 cursor-pointer">
                                Allow 24-Hour Reset
                            </label>
                            <p class="text-xs text-slate-500 mt-1">For construction/oilfield operations only</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="allow_24_hour_reset" 
                                   name="allow_24_hour_reset" value="1"
                                   {{ old('allow_24_hour_reset', $config->allow_24_hour_reset ?? false) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Break Requirements -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Coffee" />
                    <h2 class="text-lg font-semibold text-slate-800">Break Requirements</h2>
                </div>
                <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label for="require_30_min_break" class="text-sm font-medium text-slate-700 cursor-pointer">
                                Require 30-Minute Break
                            </label>
                            <p class="text-xs text-slate-500 mt-1">After 8 hours of continuous driving</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="require_30_min_break" 
                                   name="require_30_min_break" value="1"
                                   {{ old('require_30_min_break', $config->require_30_min_break ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ghost Log Detection -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Eye" />
                    <h2 class="text-lg font-semibold text-slate-800">Ghost Log Detection</h2>
                </div>
                <div class="space-y-4">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <label for="ghost_log_detection_enabled" class="text-sm font-medium text-slate-700 cursor-pointer">
                                    Enable Ghost Log Detection
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ghost_log_detection_enabled" 
                                       name="ghost_log_detection_enabled" value="1"
                                       {{ old('ghost_log_detection_enabled', $config->ghost_log_detection_enabled ?? true) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                    <div>
                        <x-base.form-label for="ghost_log_threshold_minutes">Detection Threshold (minutes)</x-base.form-label>
                        <x-base.form-input type="number" id="ghost_log_threshold_minutes" name="ghost_log_threshold_minutes" 
                            min="10" max="120" value="{{ old('ghost_log_threshold_minutes', $config->ghost_log_threshold_minutes ?? 30) }}" />
                    </div>
                </div>
            </div>

            <!-- GPS Tracking -->
            <div class="box box--stacked flex flex-col p-6">
                <div class="flex items-center gap-3 mb-6">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="MapPin" />
                    <h2 class="text-lg font-semibold text-slate-800">GPS Tracking</h2>
                </div>
                <div>
                    <x-base.form-label for="gps_tracking_interval_minutes">Tracking Interval (minutes)</x-base.form-label>
                    <x-base.form-input type="number" id="gps_tracking_interval_minutes" name="gps_tracking_interval_minutes" 
                        min="1" max="30" value="{{ old('gps_tracking_interval_minutes', $config->gps_tracking_interval_minutes ?? 5) }}" />
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="mt-8 flex justify-end">
        <x-base.button type="submit" variant="primary" class="gap-2">
            <x-base.lucide class="w-4 h-4" icon="Save" />
            Save Configuration
        </x-base.button>
    </div>
</form>

@endsection
