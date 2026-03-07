@extends('../themes/' . $activeTheme)

@section('title', 'Carrier Documents Overview')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Carriers', 'url' => route('admin.carrier.index')],
        ['label' => 'Carriers Documents', 'active' => true],
    ];
@endphp

@section('subcontent')
    <!-- Professional Header -->
    <div class="box box--stacked p-8 mb-8">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                    <x-base.lucide class="w-8 h-8 text-primary" icon="file-text" />
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Carriers Document Review</h1>
                    <p class="text-slate-600">Review and manage carrier documents. Use the filters to find specific carriers
                        and check their document status.</p>
                </div>
            </div>
            <div class="flex flex-col text-center sm:justify-end sm:flex-row gap-3 w-full md:w-[400px]">
                <x-base.button as="a" href="{{ route('admin.carrier.index') }}"
                    class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                    variant="primary">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="PenLine" />
                    Back to Carriers
                </x-base.button>
            </div>
        </div>
    </div>
    <livewire:document.document-table />
@endsection
