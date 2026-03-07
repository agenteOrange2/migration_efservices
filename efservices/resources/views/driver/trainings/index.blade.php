@extends('../themes/' . $activeTheme)
@section('title', 'My Training')

@php
    $breadcrumbLinks = [
        ['label' => 'Dashboard', 'active' => true],
    ];
@endphp

@section('subcontent')


@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">

    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-primary/70 text-white shadow-lg">
                            <x-base.lucide class="w-6 h-6" icon="GraduationCap" />
                        </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">My Trainings</h1>
                    <p class="text-slate-600">View and manage your assigned training courses</p>
                </div>
            </div>
            <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                <x-base.button  as="a" href="{{ route('driver.dashboard') }}" variant="primary" class="gap-2">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="ArrowLeft" />
                    Back to Dashboard
                </x-base.button>
            </div>
        </div>
    </div>
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="box p-4 mb-6 bg-success/10 border border-success/20 text-success rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-5 h-5 flex-shrink-0" icon="CheckCircle2" />
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="box p-4 mb-6 bg-danger/10 border border-danger/20 text-danger rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-5 h-5 flex-shrink-0" icon="AlertCircle" />
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if(session('info'))
                <div class="box p-4 mb-6 bg-info/10 border border-info/20 text-info rounded-lg">
                    <div class="flex items-center gap-3">
                        <x-base.lucide class="w-5 h-5 flex-shrink-0" icon="Info" />
                        <p class="font-medium">{{ session('info') }}</p>
                    </div>
                </div>
            @endif

            {{-- Livewire Trainings List Component --}}
            <livewire:driver.trainings-list />
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Listen for Livewire events and show toast notifications
        document.addEventListener('livewire:init', () => {
            Livewire.on('success', (event) => {
                // You can add custom toast notification here if you have one
                console.log('Success:', event.message);
            });

            Livewire.on('error', (event) => {
                // You can add custom toast notification here if you have one
                console.error('Error:', event.message);
            });
        });
    </script>
@endpush

