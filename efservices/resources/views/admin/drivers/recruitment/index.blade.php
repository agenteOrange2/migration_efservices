@extends('../themes/' . $activeTheme)
@section('title', 'Driver Recruitment')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Driver Recruitment', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="UserCheck" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Driver Recruitment Management</h1>
                        <p class="text-slate-600">Manage and track driver recruitment requests</p>
                    </div>
                </div>
            </div>
        </div>
            
            <!-- Componente Livewire -->
            @livewire('admin.driver.recruitment.driver-recruitment-list')
        </div>
    </div>
@endsection