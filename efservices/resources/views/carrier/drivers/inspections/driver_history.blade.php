@extends('../themes/' . $activeTheme)
@section('title', 'Driver Inspection History')
@php
$breadcrumbLinks = [
    ['label' => 'App', 'url' => route('carrier.dashboard')],
    ['label' => 'Driver Inspections', 'url' => route('carrier.drivers.inspections.index')],
    ['label' => 'Driver Inspection History', 'active' => true],
];
@endphp
@section('subcontent')
<div>
    <!-- Mensajes Flash -->
    @if(session()->has('success'))
    <div class="alert alert-success flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="check-circle" />
        {{ session('success') }}
    </div>
    @endif

    @if(session()->has('error'))
    <div class="alert alert-danger flex items-center mb-5">
        <x-base.lucide class="w-6 h-6 mr-2" icon="alert-triangle" />
        {{ session('error') }}
    </div>
    @endif

    <!-- Cabecera -->
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Inspection History for {{ $driver->user->name }} {{ $driver->last_name }}
        </h2>
        <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
            <x-base.button as="a" href="{{ route('carrier.drivers.inspections.index') }}"
                class="w-full sm:w-auto" variant="primary">
                <x-base.lucide class="w-4 h-4 mr-2" icon="list" />
                All Inspections
            </x-base.button>
        </div>
    </div>

    <!-- Info del Conductor -->
    <div class="box box--stacked p-5 mt-5">
        <div class="flex flex-col md:flex-row items-center">
            <div class="w-24 h-24 md:w-16 md:h-16 rounded-full overflow-hidden mr-5 mb-4 md:mb-0">
                @if ($driver->getFirstMediaUrl('profile_photo_driver'))
                <img src="{{ $driver->getFirstMediaUrl('profile_photo_driver') }}" alt="{{ $driver->user->name }}"
                    class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-500">
                    <x-base.lucide class="h-8 w-8" icon="user" />
                </div>
                @endif
            </div>
            <div class="text-center md:text-left md:mr-auto">
                <div class="text-lg font-medium">{{ $driver->user->name }} {{ $driver->last_name }}</div>
                <div class="text-gray-500">{{ $driver->phone }}</div>
            </div>
            <div class="mt-4 md:mt-0">
                <div class="flex items-center">
                    <div class="text-gray-500 mr-2">Total Inspections:</div>
                    <div class="text-lg font-medium">{{ $inspections->total() }}</div>
                </div>
                @if ($inspections->count() > 0)
                <div class="flex items-center mt-1">
                    <div class="text-gray-500 mr-2">Last Inspection:</div>
                    <div class="text-blue-600">
                        {{ $inspections->first()->inspection_date->format('M d, Y') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabla de Inspecciones -->
    <div class="box box--stacked mt-5">
        <div class="box-body p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead>
                        <tr>
                            <th scope="col" class="px-6 py-3">Inspection Date</th>
                            <th scope="col" class="px-6 py-3">Type</th>
                            <th scope="col" class="px-6 py-3">Level</th>
                            <th scope="col" class="px-6 py-3">Inspector</th>
                            <th scope="col" class="px-6 py-3">Vehicle</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inspections as $inspection)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4">{{ $inspection->inspection_date->format('m/d/Y') }}</td>
                            <td class="px-6 py-4">{{ $inspection->inspection_type }}</td>
                            <td class="px-6 py-4">{{ $inspection->inspection_level ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                {{ $inspection->inspector_name }}
                                @if($inspection->inspector_number)
                                <br><span class="text-xs text-gray-500">{{ $inspection->inspector_number }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($inspection->vehicle)
                                {{ $inspection->vehicle->company_unit_number ?? 'N/A' }} -
                                {{ $inspection->vehicle->year }}
                                {{ $inspection->vehicle->make }}
                                {{ $inspection->vehicle->model }}
                                @else
                                N/A
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($inspection->status == 'Pass')
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-success/20 text-success">
                                    Pass
                                </span>
                                @elseif ($inspection->status == 'Fail')
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-danger/20 text-danger">
                                    Fail
                                </span>
                                @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-warning/20 text-warning">
                                    {{ $inspection->status }}
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center">
                                    <a href="{{ route('carrier.drivers.inspections.edit', $inspection) }}"
                                        class="btn btn-primary mr-2 p-1" title="Edit">
                                        <x-base.lucide class="w-4 h-4" icon="edit" />
                                    </a>
                                    <form action="{{ route('carrier.drivers.inspections.destroy', $inspection) }}" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this inspection?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger p-1" title="Delete">
                                            <x-base.lucide class="w-4 h-4" icon="trash" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="bg-white border-b">
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No inspection records found for this driver.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Paginación -->
            <div class="mt-5 px-5 pb-5">
                {{ $inspections->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
