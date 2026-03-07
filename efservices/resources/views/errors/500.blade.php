<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>500 - Error del Servidor | {{ config('app.name', 'EFServices') }}</title>

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
                <h1 class="text-6xl sm:text-7xl font-bold text-gray-900 mb-2">500</h1>
                <div class="w-20 h-1 bg-red-500 mx-auto"></div>
            </div>

            <!-- Error Message -->
            <h2 class="text-2xl sm:text-3xl font-semibold text-gray-900 mb-3">
                Error del Servidor
            </h2>
            
            <p class="text-gray-600 text-base sm:text-lg mb-8 leading-relaxed">
                Algo salió mal en nuestro servidor. Estamos trabajando para solucionarlo. Por favor, intenta de nuevo más tarde.
            </p>

            <!-- Icon -->
            <div class="mb-8 flex justify-center">
                <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
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
                
                <button onclick="location.reload()" 
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors font-medium shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Intentar de Nuevo
                </button>
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
