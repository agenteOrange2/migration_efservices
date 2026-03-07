@extends('../themes/' . $activeTheme)
@section('title', 'Membership')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Membership', 'active' => true],
    ];
@endphp
@section('subcontent')
    <x-base.notificationtoast.notification-toast :notification="session('notification')" />
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <!-- Professional Header -->
            <div class="box box--stacked p-8 mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-8 h-8 text-primary" icon="Users" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">Membership Management</h1>
                            <p class="text-slate-600">Manage and track employment verification requests</p>
                        </div>
                    </div>
                    <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                        <x-base.button as="a" href="{{ route('admin.membership.create') }}" variant="primary">
                            <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="PenLine" />
                            Add New Membership
                        </x-base.button>
                    </div>
                </div>
            </div>
            <div class="box box--stacked flex flex-col mt-5">
                <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center">
                    <div class="relative">
                        <livewire:search-bar placeholder="Search Membership..." />
                    </div>
                    <div class="flex flex-col gap-x-3 gap-y-2 sm:ml-auto sm:flex-row">
                        <livewire:menu-export :exportExcel="true" :exportPdf="true" wire:ignore />
                        <livewire:filter-popover :filter-options="[
                            'status' => [
                                'type' => 'select',
                                'label' => 'Status',
                                'options' => [
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                ],
                            ],
                        ]" />
                    </div>
                </div>
                <livewire:generic-table class="p-0" model="App\Models\Membership" :columns="['name', 'description', 'status', 'created_at']" :searchableFields="['name', 'description', 'status', 'created_at']"
                    showRoute="admin.membership.show"
                    editRoute="admin.membership.edit" exportExcelRoute="admin.membership.export.excel"
                    exportPdfRoute="admin.membership.export.pdf" :customFilters="[
                        'status' => [
                            'type' => 'select',
                            'label' => 'Status',
                            'options' => [
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ],
                        ],
                    ]" />
            </div>
        </div>
    </div>
@endsection
