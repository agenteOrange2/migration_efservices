@extends('../themes/' . $activeTheme)
@section('title', 'Inactive Drivers Archive')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Drivers', 'url' => route('carrier.drivers.index')],
        ['label' => 'Inactive Drivers', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="py-5">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-3">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-slate-100">
                    <x-base.lucide class="h-6 w-6 text-slate-600" icon="Archive" />
                </div>
                <div>
                    <h2 class="text-2xl font-medium">Inactive Drivers Archive</h2>
                    <div class="mt-1 text-slate-500">
                        View historical records of drivers who have been terminated
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="box box--stacked mb-5 p-5 bg-blue-50 border-blue-200">
            <div class="flex items-start gap-3">
                <x-base.lucide class="h-5 w-5 text-blue-600 mt-0.5" icon="Info" />
                <div class="flex-1">
                    <h3 class="font-medium text-blue-900">About Inactive Driver Archives</h3>
                    <p class="text-sm text-blue-700 mt-1">
                        This section contains complete historical records of drivers who were previously employed by your carrier. 
                        All data and documents are preserved as they existed at the time of inactivation for compliance and auditing purposes.
                    </p>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Livewire Component for Inactive Drivers List -->
        <livewire:carrier.inactive-drivers-list />
    </div>
@endsection
