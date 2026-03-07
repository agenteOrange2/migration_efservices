@extends('../themes/' . $activeTheme)
@section('title', 'Hours of Service')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('driver.dashboard')],
        ['label' => 'Hours of Service', 'active' => true],
    ];
@endphp
@section('subcontent')
    <div>
        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="Clock" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Hours of Service</h1>
                        <p class="text-slate-600">Track your driving and duty hours</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('driver.hos.documents.index') }}" class="w-full sm:w-auto"
                        variant="outline-primary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="FileText" />
                        Documents
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('driver.hos.history') }}" class="w-full sm:w-auto"
                        variant="outline-secondary">
                        <x-base.lucide class="mr-2 h-4 w-4" icon="History" />
                        View History
                    </x-base.button>
                </div>
            </div>
        </div>

        @livewire('driver.hos.dashboard')
    </div>

    <!-- HOS Notifications -->
    @includeIf('components.hos-notification')
@endsection
