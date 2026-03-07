{{-- resources/views/auth/user_driver/register.blade.php --}}
<x-driver-layout>
    <div class="min-h-screen bg-gray-100 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-9xl mx-auto">
            @if ($carrier)
                <!-- Header con Logo y Información de la Empresa -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border border-gray-200">
                    <!-- Header principal -->
                    <div class="px-6 py-8 sm:px-8 sm:py-10">
                        <div class="flex flex-col lg:flex-row items-start gap-8">
                            <!-- Logo de la empresa -->
                            <div class="flex-shrink-0">
                                <div
                                    class="h-28 w-28 sm:h-32 sm:w-32 rounded-lg bg-white p-3 border-2 border-gray-200 shadow-sm">
                                    @if ($carrier->hasMedia('logo_carrier'))
                                        <img src="{{ $carrier->getFirstMediaUrl('logo_carrier') }}"
                                            alt="{{ $carrier->name }}" class="w-full h-full object-contain">
                                    @else
                                        <div class="w-full h-full bg-gray-100 rounded flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-14 w-14 sm:h-16 sm:w-16 text-gray-400" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Información principal de la empresa -->
                            <div class="flex-1 w-full">
                                <div class="mb-6">
                                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">
                                        {{ $carrier->name }}
                                    </h1>
                                    <p class="text-gray-600 text-lg mb-4">
                                        Driver Registration Application
                                    </p>

                                    <!-- Badges de información principal -->
                                    <div class="flex flex-wrap items-center gap-3 mb-6">
                                        @if ($carrier->dot_number)
                                            <div class="inline-flex items-center gap-2 bg-[#03045E] border border-[#03045E] rounded-md px-4 py-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="text-white font-semibold text-sm">DOT:
                                                    {{ $carrier->dot_number }}</span>
                                            </div>
                                        @endif
                                        @if ($carrier->mc_number)
                                            <div
                                                class="inline-flex items-center gap-2 bg-[#03045E] border border-[#03045E] rounded-md px-4 py-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <span class="text-white font-semibold text-sm">MC:
                                                    {{ $carrier->mc_number }}</span>
                                            </div>
                                        @endif
                                        @if ($carrier->state)
                                            <div
                                                class="inline-flex items-center gap-2 bg-[#03045E] border border-[#03045E] rounded-md px-4 py-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span
                                                    class="text-white font-semibold text-sm">{{ $carrier->state }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Información adicional en grid -->
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-gray-200">
                                    @if ($carrier->address)
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                                                Address</p>
                                            <p class="text-sm text-gray-900 font-medium">{{ $carrier->address }}</p>
                                            <p class="text-sm text-gray-600">
                                                {{ $carrier->state ?? '' }}{{ $carrier->state && $carrier->zipcode ? ' ' : '' }}{{ $carrier->zipcode ?? '' }}
                                            </p>
                                        </div>
                                    @endif

                                    @if ($carrier->years_in_business)
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                                                Years in Business</p>
                                            <p class="text-sm text-gray-900 font-semibold">
                                                {{ $carrier->years_in_business }}
                                                {{ $carrier->years_in_business == 1 ? 'year' : 'years' }}</p>
                                        </div>
                                    @endif

                                    @if ($carrier->fleet_size)
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                                                Fleet Size</p>
                                            <p class="text-sm text-gray-900 font-semibold">{{ $carrier->fleet_size }}
                                                {{ $carrier->fleet_size == 1 ? 'vehicle' : 'vehicles' }}</p>
                                        </div>
                                    @endif

                                    @if ($carrier->business_type)
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                                                Business Type</p>
                                            <p class="text-sm text-gray-900 font-semibold">
                                                {{ ucfirst(str_replace('_', ' ', $carrier->business_type)) }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Header para registro independiente -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border border-gray-200">
                    <div class="px-6 py-8 sm:px-8 sm:py-10">
                        <div class="text-center">
                            <div
                                class="inline-flex items-center justify-center h-20 w-20 sm:h-24 sm:w-24 rounded-lg bg-gray-100 border-2 border-gray-200 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 sm:h-14 sm:w-14 text-gray-600"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">
                                Independent Driver Registration
                            </h1>
                            <p class="text-gray-600 text-lg">
                                Complete your application to get started
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Contenedor del formulario -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                    <livewire:driver.driver-registration-manager :carrier="$carrier ?? null" :token="$token ?? null" />
                </div>
            </div>

            <!-- Footer informativo -->
            <div class="mt-8 bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Secure & Encrypted</p>
                                <p class="text-xs text-gray-500">Your information is protected with industry-standard encryption</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>SSL Protected</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>GDPR Compliant</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-3 bg-white">
                    <p class="text-xs text-center text-gray-500">
                        By continuing, you agree to our Terms of Service and Privacy Policy. All data is processed securely and in accordance with applicable regulations.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
</x-driver-layout>
