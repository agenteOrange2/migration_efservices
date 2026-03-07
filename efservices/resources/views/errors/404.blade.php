<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>404 - Página No Encontrada | {{ config('app.name', 'EFServices') }}</title>

    <!-- Favicon -->
    <link rel="mask-icon" href="{{asset('build/img/favicon_efservices.png')}}" color="#000000">
    <link rel="alternate icon" class="js-site-favicon" type="image/png" href="{{asset('build/img/favicon_efservices.png')}}">
    <link rel="icon" class="js-site-favicon" type="image/svg+xml" href="{{asset('build/img/favicon_efservices.png')}}" data-bse-href="{{asset('build/img/favicon_efservices.png')}}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center">
            <!-- Logo -->
            <div class="mb-8 flex justify-center">
                <img src="{{ asset('build/img/logo_efservices_logo.png') }}" alt="EF Services" class="h-12">
            </div>

            <!-- Error Code -->
            <div class="mb-6">
                <h1 class="text-6xl sm:text-7xl font-bold text-gray-900 mb-2">404</h1>
                <div class="w-20 h-1 bg-primary mx-auto"></div>
            </div>

            <!-- Error Message -->
            <h2 class="text-2xl sm:text-3xl font-semibold text-gray-900 mb-3">
                Página No Encontrada
            </h2>
            
            <p class="text-gray-600 text-base sm:text-lg mb-8 leading-relaxed">
                Lo sentimos, la página que buscas no existe o ha sido movida a otra ubicación.
            </p>

            <!-- Icon -->
            <div class="mb-8 flex justify-center">
                <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ url()->previous() }}" 
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-colors font-medium shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </a>
                
                <a href="/" 
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-medium shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Ir al Inicio
                </a>
            </div>

            <!-- Helpful Links -->
            <div class="mt-10 pt-6 border-t border-gray-200">
                <p class="text-gray-500 text-sm mb-4">Enlaces útiles:</p>
                <div class="flex flex-wrap gap-3 justify-center text-sm">
                    @auth
                        @php
                            $user = auth()->user();
                            $isAdmin = $user && method_exists($user, 'hasRole') && $user->hasRole('superadmin');
                            $isCarrier = $user && method_exists($user, 'hasRole') && $user->hasRole('user_carrier');
                            $isDriver = $user && method_exists($user, 'hasRole') && $user->hasRole('user_driver');
                        @endphp
                        @if($isAdmin)
                            <a href="/admin/dashboard" class="text-primary hover:text-primary/80 hover:underline transition-colors">
                                Panel de Administración
                            </a>
                        @elseif($isCarrier)
                            <a href="/carrier/dashboard" class="text-primary hover:text-primary/80 hover:underline transition-colors">
                                Portal de Transportista
                            </a>
                        @elseif($isDriver)
                            <a href="{{ route('driver.dashboard') }}" class="text-primary hover:text-primary/80 hover:underline transition-colors">
                                Portal de Conductor
                            </a>
                        @endif
                    @else
                        <a href="/login" class="text-primary hover:text-primary/80 hover:underline transition-colors">
                            Iniciar Sesión
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} EF Services. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
