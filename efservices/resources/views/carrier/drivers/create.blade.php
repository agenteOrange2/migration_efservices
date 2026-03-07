@extends('../themes/' . $activeTheme)
@section('title', 'Add New Driver')

@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('carrier.dashboard')],
        ['label' => 'Drivers', 'url' => route('carrier.drivers.index')],
        ['label' => 'Add New Driver', 'active' => true],
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

        <!-- Cabecera -->
        <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center mb-6">
            <div class="text-2xl font-bold group-[.mode--light]:text-white">
                Add New Driver
            </div>
            <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                <a href="{{ route('carrier.drivers.index') }}" class="btn btn-outline-secondary w-full sm:w-auto">
                    <x-base.lucide class="mr-2 h-4 w-4 stroke-[1.3]" icon="arrow-left" />
                    Back to Drivers List
                </a>
            </div>
        </div>

        <!-- Componente Livewire -->
        <div class="bg-white rounded-lg shadow-md">
            <livewire:admin.driver.driver-registration-manager :carrier="$carrier" />
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
