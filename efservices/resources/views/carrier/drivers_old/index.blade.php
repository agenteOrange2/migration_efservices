@extends('../themes/' . $activeTheme)
@section('title', 'Driver Management')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Driver Management', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="container-fluid">
        <!-- Mensajes Flash -->
        @if (session()->has('success'))
            <div class="alert alert-success-soft show flex items-center mb-5" role="alert">
                <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
                <div class="ml-1">{{ session('success') }}</div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger-soft show flex items-center mb-5" role="alert">
                <x-base.lucide class="w-6 h-6 mr-2" icon="alert-circle" />
                <div class="ml-1">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Cabecera -->
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center mt-8">
            <div class="text-base font-medium group-[.mode--light]:text-white">
                Driver Management
            </div>
            <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                <a href="{{ route('carrier.drivers.create') }}" class="btn btn-primary w-full sm:w-auto">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="plus" />
                    Add Driver
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="box box--stacked mt-5">
            <div class="box-body p-5">
                <form action="{{ route('carrier.drivers.index') }}" method="GET">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Search Input -->
                        <div class="sm:col-span-2">
                            <x-base.form-label for="search_term">Search by Name or Email</x-base.form-label>
                            <div class="relative mt-2">
                                <x-base.lucide
                                    class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500"
                                    icon="search" />
                                <x-base.form-input 
                                    id="search_term"
                                    class="pl-9" 
                                    name="search_term"
                                    value="{{ request('search_term') }}" 
                                    type="text" 
                                    placeholder="Search by name or email..." />
                            </div>
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <x-base.form-label for="status_filter">Status</x-base.form-label>
                            <x-base.form-select id="status_filter" name="status_filter" class="mt-2">
                                <option value="">All Status</option>
                                <option value="1" {{ request('status_filter') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ request('status_filter') == '2' ? 'selected' : '' }}>Pending</option>
                                <option value="0" {{ request('status_filter') == '0' ? 'selected' : '' }}>Inactive</option>
                            </x-base.form-select>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex items-end gap-2">
                            <x-base.button type="submit" variant="primary" class="w-full sm:w-auto">
                                <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="filter" />
                                Filter
                            </x-base.button>
                            @if(request()->hasAny(['search_term', 'status_filter']))
                                <x-base.button 
                                    type="button" 
                                    variant="outline-secondary" 
                                    class="w-full sm:w-auto"
                                    onclick="window.location.href='{{ route('carrier.drivers.index') }}'">
                                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="x" />
                                    Clear
                                </x-base.button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="box box--stacked mt-5">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="border-b-2 border-slate-200/60 bg-slate-50 px-5 py-4 font-medium text-slate-700 dark:border-darkmode-300 dark:bg-darkmode-800">
                                Driver
                            </th>
                            <th class="border-b-2 border-slate-200/60 bg-slate-50 px-5 py-4 font-medium text-slate-700 dark:border-darkmode-300 dark:bg-darkmode-800">
                                Contact
                            </th>
                            <th class="border-b-2 border-slate-200/60 bg-slate-50 px-5 py-4 font-medium text-slate-700 dark:border-darkmode-300 dark:bg-darkmode-800">
                                License Info
                            </th>
                            <th class="border-b-2 border-slate-200/60 bg-slate-50 px-5 py-4 font-medium text-slate-700 dark:border-darkmode-300 dark:bg-darkmode-800">
                                Status
                            </th>
                            <th class="border-b-2 border-slate-200/60 bg-slate-50 px-5 py-4 font-medium text-center text-slate-700 dark:border-darkmode-300 dark:bg-darkmode-800">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drivers as $driver)
                            <tr class="[&_td]:last:border-b-0">
                                <!-- Driver Info -->
                                <td class="border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                                    <div class="flex items-center">
                                        <div class="image-fit zoom-in h-10 w-10 flex-none">
                                            <img 
                                                class="rounded-full shadow-sm" 
                                                src="{{ $driver->getFirstMediaUrl('profile_photo_driver') ?: asset('default_profile.png') }}"
                                                alt="{{ $driver->user->name }}"
                                                onerror="this.src='{{ asset('default_profile.png') }}'"
                                            >
                                        </div>
                                        <div class="ml-3.5">
                                            <div class="font-medium text-slate-700 dark:text-slate-300">
                                                {{ $driver->user->name }} {{ $driver->last_name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Contact Info -->
                                <td class="border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                                    <div class="text-slate-600 dark:text-slate-400">
                                        <div class="flex items-center text-sm">
                                            <x-base.lucide class="mr-1.5 h-3.5 w-3.5 stroke-[1.3]" icon="mail" />
                                            {{ $driver->user->email }}
                                        </div>
                                        @if($driver->phone)
                                            <div class="mt-1 flex items-center text-sm">
                                                <x-base.lucide class="mr-1.5 h-3.5 w-3.5 stroke-[1.3]" icon="phone" />
                                                {{ $driver->phone }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                
                                <!-- License Info -->
                                <td class="border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                                    <div class="text-slate-600 dark:text-slate-400">
                                        @if($driver->license_number)
                                            <div class="font-medium text-slate-700 dark:text-slate-300">
                                                {{ $driver->license_number }}
                                            </div>
                                            <div class="mt-0.5 text-xs text-slate-500">
                                                {{ $driver->license_state }}
                                            </div>
                                            @if($driver->license_expiration)
                                                <div class="mt-1 text-xs text-slate-500">
                                                    Exp: {{ $driver->license_expiration->format('M d, Y') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-xs text-slate-400">Not provided</span>
                                        @endif
                                    </div>
                                </td>
                                
                                <!-- Status Badge -->
                                <td class="border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                                    @if($driver->status === 1)
                                        <div class="flex items-center text-success">
                                            <div class="mr-1.5 h-2 w-2 rounded-full border border-success/50 bg-success/80"></div>
                                            <span class="text-sm font-medium">Active</span>
                                        </div>
                                    @elseif($driver->status === 2)
                                        <div class="flex items-center text-warning">
                                            <div class="mr-1.5 h-2 w-2 rounded-full border border-warning/50 bg-warning/80"></div>
                                            <span class="text-sm font-medium">Pending</span>
                                        </div>
                                    @else
                                        <div class="flex items-center text-danger">
                                            <div class="mr-1.5 h-2 w-2 rounded-full border border-danger/50 bg-danger/80"></div>
                                            <span class="text-sm font-medium">Inactive</span>
                                        </div>
                                    @endif
                                </td>
                                
                                <!-- Actions -->
                                <td class="border-b border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                                    <div class="flex items-center justify-center gap-2">
                                        <a 
                                            href="{{ route('carrier.drivers.show', $driver->id) }}" 
                                            class="flex h-8 w-8 items-center justify-center rounded-md border border-primary/20 text-primary transition-all hover:bg-primary/10"
                                            title="View Details">
                                            <x-base.lucide class="h-4 w-4 stroke-[1.3]" icon="eye" />
                                        </a>
                                        <a 
                                            href="{{ route('carrier.drivers.edit', $driver->id) }}" 
                                            class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 text-slate-600 transition-all hover:bg-slate-100"
                                            title="Edit Driver">
                                            <x-base.lucide class="h-4 w-4 stroke-[1.3]" icon="pencil" />
                                        </a>
                                        <button 
                                            data-tw-toggle="modal" 
                                            data-tw-target="#delete-driver-modal" 
                                            class="delete-driver flex h-8 w-8 items-center justify-center rounded-md border border-danger/20 text-danger transition-all hover:bg-danger/10"
                                            data-driver-id="{{ $driver->id }}"
                                            data-driver-name="{{ $driver->user->name }} {{ $driver->last_name }}"
                                            title="Delete Driver">
                                            <x-base.lucide class="h-4 w-4 stroke-[1.3]" icon="trash-2" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border-b border-slate-200/60 px-5 py-16 text-center dark:border-darkmode-300">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-base.lucide class="h-16 w-16 text-slate-300" icon="users" />
                                        <div class="mt-5 text-lg font-medium text-slate-600">No drivers found</div>
                                        <div class="mt-2 text-sm text-slate-500">
                                            @if(request()->hasAny(['search_term', 'status_filter']))
                                                Try adjusting your filters or search terms
                                            @else
                                                Get started by adding your first driver
                                            @endif
                                        </div>
                                        @if(!request()->hasAny(['search_term', 'status_filter']))
                                            <a href="{{ route('carrier.drivers.create') }}" class="btn btn-primary mt-5">
                                                <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="plus" />
                                                Add First Driver
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            @if($drivers->hasPages())
                <div class="border-t border-slate-200/60 px-5 py-4 dark:border-darkmode-300">
                    {{ $drivers->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Eliminar Conductor -->
    <x-base.dialog id="delete-driver-modal" size="md">
        <x-base.dialog.panel>
            <div class="p-5 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-4 border-danger/20 bg-danger/10">
                    <x-base.lucide class="h-8 w-8 text-danger" icon="trash-2" />
                </div>
                <div class="mt-5 text-xl font-medium text-slate-700">Delete Driver</div>
                <div class="mt-2 text-slate-500">
                    Are you sure you want to delete <strong id="driver-name-display" class="text-slate-700"></strong>?
                </div>
                <div class="mt-3 rounded-md bg-warning/10 px-4 py-3 text-left">
                    <div class="flex items-start">
                        <x-base.lucide class="mr-2 mt-0.5 h-5 w-5 flex-shrink-0 text-warning" icon="alert-triangle" />
                        <div class="text-sm text-slate-600">
                            <strong class="font-medium">Warning:</strong> This action will permanently delete:
                            <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                                <li>Driver profile and personal information</li>
                                <li>All uploaded documents and photos</li>
                                <li>License and certification records</li>
                            </ul>
                            <div class="mt-2 font-medium text-danger">This action cannot be undone.</div>
                        </div>
                    </div>
                </div>
            </div>
            <form id="delete_driver_form" action="" method="POST" class="flex gap-3 px-5 pb-8">
                @csrf
                @method('DELETE')
                <x-base.button 
                    data-tw-dismiss="modal" 
                    type="button" 
                    variant="outline-secondary" 
                    class="w-full">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="x" />
                    Cancel
                </x-base.button>
                <x-base.button 
                    type="submit" 
                    variant="danger" 
                    class="w-full">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="trash-2" />
                    Delete Driver
                </x-base.button>
            </form>
        </x-base.dialog.panel>
    </x-base.dialog>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Configuración del modal de eliminación
                const deleteButtons = document.querySelectorAll('.delete-driver');
                const deleteForm = document.getElementById('delete_driver_form');
                const driverNameDisplay = document.getElementById('driver-name-display');

                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const driverId = this.getAttribute('data-driver-id');
                        const driverName = this.getAttribute('data-driver-name');
                        
                        // Actualizar el formulario con la URL correcta
                        deleteForm.action = `/carrier/drivers/${driverId}`;
                        
                        // Actualizar el nombre del conductor en el modal
                        if (driverNameDisplay && driverName) {
                            driverNameDisplay.textContent = driverName;
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
