@extends('../themes/' . $activeTheme)
@section('title', 'Edit Driver')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Drivers', 'url' => route('carrier.drivers.index')],
        ['label' => 'Edit Driver', 'active' => true],
    ];
@endphp

@section('subcontent')
    <div class="container mx-auto py-6">
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

        <!-- Professional Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex flex-col w-full md:flex-row text-center md:text-left items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <x-base.lucide class="w-8 h-8 text-primary" icon="User" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Edit Driver</h1>
                        <p class="text-slate-600">Edit driver information</p>
                    </div>
                </div>
                <div class="flex flex-col justify-center md:flex-row md:justify-end gap-3 w-full">
                    <x-base.button as="a" href="{{ route('carrier.drivers.show', $driver) }}" class="w-full sm:w-auto"
                        variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="eye" />
                        View Driver
                    </x-base.button>
                    <x-base.button as="a" href="{{ route('carrier.drivers.index') }}" class="w-full sm:w-auto"
                        variant="outline-primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
                        Back to Drivers List
                    </x-base.button>
                </div>
            </div>
        </div>

        <!-- Componente Livewire -->
        <div class="box box--stacked flex flex-col p-6 h-fit">
            <livewire:carrier.driver.driver-registration-manager :carrier="$carrier" :driver="$driver" :isIndependent="false" />
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Incluir IMask para las máscaras -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script defer src="https://unpkg.com/@alpinejs/validate@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/imask"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara para el teléfono
            const phoneInputs = document.querySelectorAll('input[name="phone"]');
            phoneInputs.forEach(input => {
                if (input) {
                    IMask(input, {
                        mask: '(000) 000-0000'
                    });
                }
            });

            // Máscara para la licencia (ajustar formato según necesidad)
            const licInputs = document.querySelectorAll('input[name="license_number"]');
            licInputs.forEach(input => {
                if (input) {
                    IMask(input, {
                        mask: 'AA-000000'
                    });
                }
            });
        });
    </script>
@endpush

@pushOnce('scripts')
    @vite('resources/js/app.js')
    @vite('resources/js/pages/notification.js')
@endPushOnce
