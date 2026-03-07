@extends('../themes/' . $activeTheme)
@section('title', 'HOS Cycle Settings')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'HOS', 'url' => route('driver.hos.dashboard')],
        ['label' => 'Cycle Settings', 'active' => true],
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
            <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                <x-base.lucide class="w-8 h-8 text-primary" icon="Settings" />
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2">HOS Cycle Settings</h1>
                <p class="text-slate-600">View and manage your weekly hours cycle</p>
            </div>
        </div>
        <x-base.button as="a" href="{{ route('driver.hos.dashboard') }}" variant="secondary" class="gap-2">
            <x-base.lucide class="w-4 h-4" icon="ArrowLeft" />
            Back to HOS
        </x-base.button>
    </div>
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

<div class="grid grid-cols-12 gap-6">
    <!-- Current Cycle Status -->
    <div class="col-span-12 lg:col-span-8">
        <div class="box box--stacked flex flex-col p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="Clock" />
                <h2 class="text-lg font-semibold text-slate-800">Current Cycle Status</h2>
            </div>

            <!-- Cycle Type Badge -->
            <div class="flex items-center gap-4 mb-6">
                <div class="text-center p-6 bg-primary/5 rounded-xl border border-primary/20 flex-1">
                    <div class="text-sm text-slate-500 mb-2">Your Current Cycle</div>
                    <div class="text-3xl font-bold text-primary mb-1">
                        {{ $currentCycleType === '70_8' ? '70' : '60' }} Hours
                    </div>
                    <div class="text-sm text-slate-600">
                        {{ $currentCycleType === '70_8' ? '8 Days Rolling Period' : '7 Days Rolling Period' }}
                    </div>
                </div>
            </div>

            <!-- Usage Progress -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-slate-600">Weekly Hours Used</span>
                    <span class="text-sm font-semibold text-slate-800">
                        {{ number_format($cycleStatus['hours_used'], 1) }} / {{ $cycleStatus['hours_limit'] }} hours
                    </span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-3">
                    <div class="bg-{{ $cycleStatus['status_color'] === 'green' ? 'success' : ($cycleStatus['status_color'] === 'yellow' ? 'warning' : 'danger') }} h-3 rounded-full transition-all duration-500" 
                         style="width: {{ min($cycleStatus['percentage_used'], 100) }}%"></div>
                </div>
                <div class="flex justify-between mt-2">
                    <span class="text-xs text-slate-500">{{ number_format($cycleStatus['percentage_used'], 1) }}% used</span>
                    <span class="text-xs text-slate-500">{{ number_format($cycleStatus['hours_remaining'], 1) }} hours remaining</span>
                </div>
            </div>

            <!-- Cycle Details -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100">
                    <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">Cycle Type</div>
                    <div class="font-semibold text-slate-800">{{ $cycleStatus['cycle_type_name'] }}</div>
                </div>
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100">
                    <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">Hours Remaining</div>
                    <div class="font-semibold text-{{ $cycleStatus['status_color'] === 'green' ? 'success' : ($cycleStatus['status_color'] === 'yellow' ? 'warning' : 'danger') }}">
                        {{ number_format($cycleStatus['hours_remaining'], 1) }} hours
                    </div>
                </div>
                <div class="p-4 bg-slate-50/50 rounded-lg border border-slate-100">
                    <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">Status</div>
                    <div class="font-semibold text-{{ $cycleStatus['status_color'] === 'green' ? 'success' : ($cycleStatus['status_color'] === 'yellow' ? 'warning' : 'danger') }}">
                        @if($cycleStatus['is_over_limit'])
                            Over Limit
                        @elseif($cycleStatus['is_approaching_limit'])
                            Approaching Limit
                        @else
                            OK
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Cycle Change -->
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-6">
                <x-base.lucide class="w-5 h-5 text-primary" icon="RefreshCw" />
                <h2 class="text-lg font-semibold text-slate-800">Request Cycle Change</h2>
            </div>

            @if($hasPendingRequest)
                <!-- Pending Request Notice -->
                <div class="p-6 bg-warning/5 rounded-xl border border-warning/20 mb-4">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-warning/10 flex items-center justify-center">
                                <x-base.lucide class="w-5 h-5 text-warning" icon="Clock" />
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-slate-800 mb-1">Pending Request</h3>
                            <p class="text-sm text-slate-600 mb-3">
                                You have requested to change to 
                                <strong>{{ $pendingRequestTo === '70_8' ? '70 hours / 8 days' : '60 hours / 7 days' }}</strong> cycle.
                                Submitted on {{ $pendingRequestAt->format('M d, Y \a\t h:i A') }}.
                            </p>
                            <p class="text-xs text-slate-500 mb-4">Waiting for carrier approval...</p>
                            
                            <form action="{{ route('driver.hos.cycle.cancel') }}" method="POST">
                                @csrf
                                <x-base.button type="submit" variant="outline-danger" size="sm" class="gap-2"
                                    onclick="return confirm('Are you sure you want to cancel this request?');">
                                    <x-base.lucide class="w-4 h-4" icon="X" />
                                    Cancel Request
                                </x-base.button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <!-- Request Form -->
                <div class="p-6 bg-slate-50/50 rounded-xl border border-slate-100">
                    <p class="text-sm text-slate-600 mb-6">
                        You can request to change your weekly cycle type. Your carrier will need to approve the change before it takes effect.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <!-- 60/7 Option -->
                        <label class="relative cursor-pointer {{ $currentCycleType === '60_7' ? 'opacity-50 cursor-not-allowed' : '' }}">
                            <input type="radio" name="new_cycle_type" value="60_7" form="cycleChangeForm"
                                   class="peer sr-only" {{ $currentCycleType === '60_7' ? 'disabled' : '' }}>
                            <div class="p-4 border-2 border-slate-200 rounded-xl transition-all peer-checked:border-primary peer-checked:bg-primary/5">
                                <div class="text-xl font-bold text-slate-800 mb-1">60 Hours / 7 Days</div>
                                <p class="text-sm text-slate-500">Standard cycle for most operations</p>
                                @if($currentCycleType === '60_7')
                                    <span class="text-xs text-primary font-medium">(Current)</span>
                                @endif
                            </div>
                        </label>

                        <!-- 70/8 Option -->
                        <label class="relative cursor-pointer {{ $currentCycleType === '70_8' ? 'opacity-50 cursor-not-allowed' : '' }}">
                            <input type="radio" name="new_cycle_type" value="70_8" form="cycleChangeForm"
                                   class="peer sr-only" {{ $currentCycleType === '70_8' ? 'disabled' : '' }}>
                            <div class="p-4 border-2 border-slate-200 rounded-xl transition-all peer-checked:border-primary peer-checked:bg-primary/5">
                                <div class="text-xl font-bold text-slate-800 mb-1">70 Hours / 8 Days</div>
                                <p class="text-sm text-slate-500">Extended cycle for Texas intrastate</p>
                                @if($currentCycleType === '70_8')
                                    <span class="text-xs text-primary font-medium">(Current)</span>
                                @endif
                            </div>
                        </label>
                    </div>

                    <form action="{{ route('driver.hos.cycle.request') }}" method="POST" id="cycleChangeForm">
                        @csrf
                        <x-base.button type="submit" variant="primary" class="gap-2">
                            <x-base.lucide class="w-4 h-4" icon="Send" />
                            Submit Change Request
                        </x-base.button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-span-12 lg:col-span-4">
        <!-- Cycle Explanation -->
        <div class="box box--stacked flex flex-col p-6 bg-primary/5 border-l-4 border-primary mb-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <x-base.lucide class="w-5 h-5 text-primary" icon="Info" />
                </div>
                <h3 class="font-semibold text-slate-800">Understanding Cycles</h3>
            </div>

            <div class="space-y-4 text-sm">
                <div>
                    <div class="font-medium text-slate-800 mb-1">60/7 Cycle</div>
                    <p class="text-slate-600">Maximum 60 hours of on-duty time in any 7 consecutive days.</p>
                </div>
                <div>
                    <div class="font-medium text-slate-800 mb-1">70/8 Cycle</div>
                    <p class="text-slate-600">Maximum 70 hours of on-duty time in any 8 consecutive days. Typically used for Texas intrastate operations.</p>
                </div>
            </div>
        </div>

        <!-- Reset Info -->
        <div class="box box--stacked flex flex-col p-6">
            <div class="flex items-center gap-3 mb-4">
                <x-base.lucide class="w-5 h-5 text-warning" icon="RefreshCcw" />
                <h3 class="font-semibold text-slate-800">Reset Your Cycle</h3>
            </div>
            <p class="text-sm text-slate-600 mb-4">
                To reset your weekly cycle and regain full hours, you must take:
            </p>
            <ul class="space-y-2 text-sm text-slate-600">
                <li class="flex items-center gap-2">
                    <x-base.lucide class="w-4 h-4 text-success" icon="Check" />
                    <strong>34 consecutive hours</strong> off-duty
                </li>
            </ul>
            <p class="text-xs text-slate-500 mt-4">
                After completing a 34-hour reset, your weekly hours will reset to 0.
            </p>
        </div>
    </div>
</div>

@endsection
