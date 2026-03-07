<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'EFServices') }} h1</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <link rel="mask-icon" href="{{asset('build/img/favicon_efservices.png')}}" color="#000000">
        <link rel="alternate icon" class="js-site-favicon" type="image/png" href="{{asset('build/img/favicon_efservices.png')}}">
        <link rel="icon" class="js-site-favicon" type="image/svg+xml" href="{{asset('build/img/favicon_efservices.png')}}" data-bse-href="{{asset('build/img/favicon_efservices.png')}}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <x-banner />

        <div class="min-h-screen bg-gray-100">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}

            </main>
        </div>

        @stack('modals')

        @livewireScripts
        @stack('scripts')
    </body>
</html>
