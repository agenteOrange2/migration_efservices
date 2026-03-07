<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>403 - Acceso Denegado | {{ config('app.name', 'EFServices') }}</title>

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
                <h1 class="text-6xl sm:text-7xl font-bold text-gray-900 mb-2">403</h1>
                <div class="w-20 h-1 bg-amber-500 mx-auto"></div>
            </div>

            <!-- Error Message -->
            <h2 class="text-2xl sm:text-3xl font-semibold text-gray-900 mb-3">
                Acceso Denegado
            </h2>
            
            <p class="text-gray-600 text-base sm:text-lg mb-8 leading-relaxed">
                No tienes permisos para acceder a esta página. Si crees que esto es un error, contacta con tu administrador.
            </p>

            <!-- Icon -->
            <div class="mb-8 flex justify-center">
                <div class="w-24 h-24 bg-amber-50 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
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
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors font-medium shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Ir al Inicio
                </a>
            </div>

            <!-- Footer -->
            <div class="mt-12 pt-6 border-t border-gray-200">
                <p class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} EF Services. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
