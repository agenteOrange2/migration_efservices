@extends('../themes/' . $activeTheme)
@section('title', 'Trainings Management')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('admin.dashboard')],
['label' => 'Trainings', 'active' => true],
];
@endphp

@section('subcontent')

{{-- Professional Header --}}
<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-primary/70 text-white shadow-lg">
                <x-base.lucide class="w-6 h-6" icon="BookOpen" />
            </div>
            <div>
                <div>Driver Trainings Management</div>
                <div class="text-sm font-normal text-slate-500 mt-1">Manage and assign training materials to your drivers</div>
            </div>
        </h2>
    </div>
    
    <div class="flex flex-col sm:flex-row gap-2">
        <x-base.button as="a" href="{{ route('admin.training-dashboard.index') }}" variant="outline-secondary" class="w-full sm:w-auto">
            <x-base.lucide class="w-5 h-5 mr-2" icon="BarChart3" />
            Dashboard
        </x-base.button>

        <x-base.button as="a" href="{{ route('admin.trainings.create') }}" variant="primary" class="w-full sm:w-auto">
            <x-base.lucide class="w-5 h-5 mr-2" icon="plus" />
            Create Training
        </x-base.button>

        <x-base.button as="a" href="{{ route('admin.training-assignments.index') }}" class="w-full sm:w-auto" variant="outline-primary">
            <x-base.lucide class="w-5 h-5 mr-2" icon="clipboard-list" />
            View Assignments
        </x-base.button>
    </div>
</div>

{{-- Livewire Component --}}
<livewire:admin.trainings-list />

@endsection

