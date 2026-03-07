@extends('../themes/' . $activeTheme)

@section('title', 'Training Assignments')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Trainings', 'url' => route('admin.trainings.index')],
        ['label' => 'Assignments', 'active' => true],
    ];
@endphp

@section('subcontent')
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-info to-info/70 text-white shadow-lg">
                    <x-base.lucide class="w-6 h-6" icon="ClipboardList" />
                </div>
                <div>
                    <div>Training Assignments</div>
                    <div class="text-sm font-normal text-slate-500 mt-1">Manage driver training assignments and track progress</div>
                </div>
            </h2>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2">
            <x-base.button as="a" href="{{ route('admin.training-dashboard.index') }}" variant="outline-secondary" class="w-full sm:w-auto">
                <x-base.lucide class="w-5 h-5 mr-2" icon="BarChart3" />
                Dashboard
            </x-base.button>

            <x-base.button as="a" href="{{ route('admin.select-training') }}" variant="primary" class="w-full sm:w-auto">
                <x-base.lucide class="w-5 h-5 mr-2" icon="UserPlus" />
                New Assignment
            </x-base.button>

            <x-base.button as="a" href="{{ route('admin.trainings.index') }}" variant="outline-primary" class="w-full sm:w-auto">
                <x-base.lucide class="w-5 h-5 mr-2" icon="BookOpen" />
                View Trainings
            </x-base.button>
        </div>
    </div>

    {{-- Livewire Component --}}
    <livewire:admin.assignments-list />
@endsection

