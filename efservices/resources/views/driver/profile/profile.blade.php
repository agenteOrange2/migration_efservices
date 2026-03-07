@extends('../themes/' . $activeTheme)
@section('title', 'My Profile - EF Services')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'url' => route('driver.dashboard')],
        ['label' => 'My Profile', 'active' => true],
    ];
@endphp

@section('subcontent')

<!-- Breadcrumbs -->
<div class="mb-6">
    <x-base.breadcrumb :links="$breadcrumbLinks" />
</div>

<!-- Header Section with Profile Information -->
<div class="box box--stacked p-6 sm:p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-center lg:items-start justify-between gap-6">
        <div class="flex flex-col lg:flex-row items-center gap-4">
            <div class="p-3">
                @if ($driver->getFirstMediaUrl('profile_photo_driver'))
                    <img
                        class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg"
                        src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}"
                        alt="{{ $driver->user->name ?? 'Driver' }}"
                    />
                @else
                    <div class="w-24 h-24 bg-gradient-to-br from-primary/20 to-primary/10 rounded-full flex items-center justify-center border-4 border-white shadow-lg">
                        <x-base.lucide class="w-12 h-12 text-primary" icon="User" />
                    </div>
                @endif
            </div>
            <div class="text-center lg:text-left">
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 mb-2">
                    {{ $driver->user->name ?? 'Unknown' }} {{ $driver->middle_name }} {{ $driver->last_name }}
                </h1>
                <div class="flex items-center justify-center lg:justify-start gap-2 mb-3">
                    <x-base.lucide class="w-4 h-4 text-slate-400" icon="Mail" />
                    <span class="text-slate-500">{{ $driver->user->email ?? 'No email' }}</span>
                </div>
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3">
                    @php $effectiveStatus = $driver->getEffectiveStatus(); @endphp
                    @switch($effectiveStatus)
                        @case('active')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-success/10 text-success">
                                <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                                Active Driver
                            </span>
                            @break
                        @case('pending_review')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-warning/10 text-warning">
                                <span class="w-2 h-2 rounded-full bg-warning"></span>
                                Pending Review
                            </span>
                            @break
                        @case('draft')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-slate-200/80 text-slate-600">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                Draft
                            </span>
                            @break
                        @case('rejected')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-danger/10 text-danger">
                                <span class="w-2 h-2 rounded-full bg-danger"></span>
                                Rejected
                            </span>
                            @break
                        @default
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-600">
                                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                                Inactive
                            </span>
                    @endswitch
                    <span class="text-slate-400">•</span>
                    <span class="text-sm text-slate-500">{{ $driver->carrier->name ?? 'No carrier' }}</span>
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-base.button as="a" href="{{ route('driver.profile.edit') }}" variant="primary" class="gap-2">
                <x-base.lucide class="w-4 h-4" icon="Edit" />
                Edit Profile
            </x-base.button>
            @if($stats['total_documents'] > 0)
                <x-base.button as="a" href="{{ route('driver.profile.download-documents') }}" variant="outline-primary" class="gap-2">
                    <x-base.lucide class="w-4 h-4" icon="Download" />
                    Download Documents
                </x-base.button>
            @endif
        </div>
    </div>
</div>

<!-- Tabbed Content Section -->
<div class="box box--stacked flex flex-col p-4 sm:p-6">
    <!-- Tab Navigation -->
    <div class="border-b border-slate-200 -mx-4 sm:-mx-6 px-4 sm:px-6">
        <nav class="flex space-x-1 overflow-x-auto scrollbar-hide pb-px" aria-label="Tabs" role="tablist">
            <button class="tab-button active flex items-center gap-2 px-3 sm:px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 border-primary text-primary" 
                    data-target="#tab-content-general" role="tab" aria-selected="true">
                <x-base.lucide class="w-4 h-4" icon="User" />
                <span class="hidden sm:inline">General</span>
            </button>
            <button class="tab-button flex items-center gap-2 px-3 sm:px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-slate-500 hover:text-slate-700" 
                    data-target="#tab-content-licenses" role="tab" aria-selected="false">
                <x-base.lucide class="w-4 h-4" icon="CreditCard" />
                <span class="hidden sm:inline">Licenses</span>
            </button>
            <button class="tab-button flex items-center gap-2 px-3 sm:px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-slate-500 hover:text-slate-700" 
                    data-target="#tab-content-medical" role="tab" aria-selected="false">
                <x-base.lucide class="w-4 h-4" icon="Heart" />
                <span class="hidden sm:inline">Medical</span>
            </button>
            <button class="tab-button flex items-center gap-2 px-3 sm:px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-slate-500 hover:text-slate-700" 
                    data-target="#tab-content-vehicles" role="tab" aria-selected="false">
                <x-base.lucide class="w-4 h-4" icon="Truck" />
                <span class="hidden sm:inline">Vehicles</span>
            </button>
            <button class="tab-button flex items-center gap-2 px-3 sm:px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-slate-500 hover:text-slate-700" 
                    data-target="#tab-content-trainings" role="tab" aria-selected="false">
                <x-base.lucide class="w-4 h-4" icon="GraduationCap" />
                <span class="hidden sm:inline">Trainings</span>
            </button>
            <button class="tab-button flex items-center gap-2 px-3 sm:px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-slate-500 hover:text-slate-700" 
                    data-target="#tab-content-testing" role="tab" aria-selected="false">
                <x-base.lucide class="w-4 h-4" icon="ClipboardCheck" />
                <span class="hidden sm:inline">Testing</span>
            </button>
            <button class="tab-button flex items-center gap-2 px-3 sm:px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-slate-500 hover:text-slate-700" 
                    data-target="#tab-content-inspections" role="tab" aria-selected="false">
                <x-base.lucide class="w-4 h-4" icon="Search" />
                <span class="hidden sm:inline">Inspections</span>
            </button>
            <button class="tab-button flex items-center gap-2 px-3 sm:px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 border-transparent text-slate-500 hover:text-slate-700" 
                    data-target="#tab-content-documents" role="tab" aria-selected="false">
                <x-base.lucide class="w-4 h-4" icon="FileText" />
                <span class="hidden sm:inline">Documents</span>
            </button>
        </nav>
    </div>

    <!-- Tab Content Panels -->
    <div class="mt-6">
        <div id="tab-content-general" class="tab-pane" role="tabpanel">
            @include('driver.profile.tabs.general', ['driver' => $driver])
        </div>
        <div id="tab-content-licenses" class="tab-pane hidden" role="tabpanel">
            @include('driver.profile.tabs.licenses', ['driver' => $driver])
        </div>
        <div id="tab-content-medical" class="tab-pane hidden" role="tabpanel">
            @include('driver.profile.tabs.medical', ['driver' => $driver])
        </div>
        <div id="tab-content-vehicles" class="tab-pane hidden" role="tabpanel">
            @include('driver.profile.tabs.vehicles', ['driver' => $driver])
        </div>
        <div id="tab-content-trainings" class="tab-pane hidden" role="tabpanel">
            @include('driver.profile.tabs.trainings', ['driver' => $driver])
        </div>
        <div id="tab-content-testing" class="tab-pane hidden" role="tabpanel">
            @include('driver.profile.tabs.testing', ['driver' => $driver])
        </div>
        <div id="tab-content-inspections" class="tab-pane hidden" role="tabpanel">
            @include('driver.profile.tabs.inspections', ['driver' => $driver])
        </div>
        <div id="tab-content-documents" class="tab-pane hidden" role="tabpanel">
            @include('driver.profile.tabs.documents', ['driver' => $driver, 'stats' => $stats])
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanes = document.querySelectorAll('.tab-pane');

    function switchTab(button) {
        const targetId = button.getAttribute('data-target');
        
        tabButtons.forEach(btn => {
            btn.classList.remove('active', 'border-primary', 'text-primary');
            btn.classList.add('border-transparent', 'text-slate-500');
            btn.setAttribute('aria-selected', 'false');
        });
        
        button.classList.add('active', 'border-primary', 'text-primary');
        button.classList.remove('border-transparent', 'text-slate-500');
        button.setAttribute('aria-selected', 'true');
        
        tabPanes.forEach(pane => pane.classList.add('hidden'));
        
        const targetPane = document.querySelector(targetId);
        if (targetPane) targetPane.classList.remove('hidden');
        
        const tabName = targetId.replace('#tab-content-', '');
        history.pushState(null, null, `#${tabName}`);
    }

    tabButtons.forEach((button, index) => {
        button.addEventListener('click', () => switchTab(button));
        button.addEventListener('keydown', function(e) {
            let target = null;
            if (e.key === 'ArrowRight') target = tabButtons[index + 1] || tabButtons[0];
            else if (e.key === 'ArrowLeft') target = tabButtons[index - 1] || tabButtons[tabButtons.length - 1];
            if (target) { e.preventDefault(); target.focus(); switchTab(target); }
        });
    });

    function initFromHash() {
        const hash = window.location.hash.replace('#', '');
        const target = hash ? `#tab-content-${hash}` : '#tab-content-general';
        const btn = document.querySelector(`[data-target="${target}"]`) || document.querySelector('[data-target="#tab-content-general"]');
        if (btn) switchTab(btn);
    }

    initFromHash();
    window.addEventListener('popstate', initFromHash);
});
</script>
@endpush
