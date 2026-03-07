@extends('../themes/' . $activeTheme)
@section('title', 'All Carriers Registered')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Carriers', 'active' => true],
    ];
@endphp

@section('subcontent')
    <x-base.notificationtoast.notification-toast :notification="session('notification')" />
    @if (isset($notification))
        <div class="alert alert-{{ $notification['type'] }} alert-dismissible fade show" role="alert">
            <strong>{{ $notification['message'] }}</strong>
            @if (isset($notification['details']))
                <p class="mb-0">{{ $notification['details'] }}</p>
            @endif
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">

            <!-- Professional Header -->
            <div class="box box--stacked p-8 mb-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <x-base.lucide class="w-8 h-8 text-primary" icon="users" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2">All Carriers Registered</h1>
                            <p class="text-slate-600">Manage all carriers registered</p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:justify-end sm:flex-row gap-3 w-full md:w-[300px]">
                        <x-base.button as="a" href="{{ route('admin.carrier.create') }}" variant="primary">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="plus" />
                            Add New Carrier
                        </x-base.button>
                    </div>
                </div>
            </div>
            <!-- End Professional Header -->

            <!-- Reemplaza el contenido de la tabla con el componente Livewire -->
            <div class="box box--stacked flex flex-col mt-5">
                <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center">
                    <div class="relative">
                        <livewire:search-bar placeholder="Search users..." />
                    </div>

                    <div class="flex flex-col gap-x-3 gap-y-2 sm:ml-auto sm:flex-row">
                        <livewire:menu-export :exportExcel="true" :exportPdf="true" wire:ignore />
                        <livewire:filter-popover :filter-options="[
                            'status' => [
                                'type' => 'select',
                                'label' => 'Status',
                                'options' => [
                                    '0' => 'Inactive',
                                    '1' => 'Active',
                                    '2' => 'Pending',
                                    '3' => 'Pending Validation',
                                ],
                            ],
                        ]" />
                    </div>
                </div>
                {{-- <livewire:carrier-manager /> --}}


                <livewire:generic-table class="p-0" model="App\Models\Carrier" :columns="['name', 'address', 'ein_number', 'dot_number', 'status', 'created_at']" :searchableFields="['name', 'address', 'ein_number', 'dot_number']"
                    editRoute="admin.carrier.edit" showSlugRoute="admin.carrier.show"
                    exportExcelRoute="admin.carrier.export.excel" exportPdfRoute="admin.carrier.export.pdf"
                    :customFilters="[
                        'status' => [
                            'type' => 'select',
                            'label' => 'Status',
                            'options' => [
                                '0' => 'Inactive',
                                '1' => 'Active',
                                '2' => 'Pending',
                                '3' => 'Pending Validation',
                            ],
                        ],
                    ]" />
            </div>
        </div>
    </div>
@endsection
