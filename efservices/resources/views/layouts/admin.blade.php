<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'EFCTS') }} h2</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        

        <link rel="mask-icon" href="{{asset('build/img/favicon_efservices.png')}}" color="#000000">
        <link rel="alternate icon" class="js-site-favicon" type="image/png" href="{{asset('build/img/favicon_efservices.png')}}">
        <link rel="icon" class="js-site-favicon" type="image/svg+xml" href="{{asset('build/img/favicon_efservices.png')}}" data-bse-href="{{asset('build/img/favicon_efservices.png')}}">
        
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Pikaday for date picker -->
        <script src="https://cdn.jsdelivr.net/npm/pikaday/pikaday.js"></script>

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

            <!-- Flash Messages -->
            @if(session('success') || session('error') || session('warning') || session('info'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="mb-3 flex items-center gap-3 p-4 rounded-lg bg-green-100 border border-green-400 text-green-700">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="flex-1 text-sm font-medium">{{ session('success') }}</span>
                    <button @click="show = false" class="text-green-700 hover:text-green-900">&times;</button>
                </div>
                @endif
                @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="mb-3 flex items-center gap-3 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    <span class="flex-1 text-sm font-medium">{{ session('error') }}</span>
                    <button @click="show = false" class="text-red-700 hover:text-red-900">&times;</button>
                </div>
                @endif
                @if(session('warning'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="mb-3 flex items-center gap-3 p-4 rounded-lg bg-yellow-100 border border-yellow-400 text-yellow-700">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    <span class="flex-1 text-sm font-medium">{{ session('warning') }}</span>
                    <button @click="show = false" class="text-yellow-700 hover:text-yellow-900">&times;</button>
                </div>
                @endif
                @if(session('info'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition class="mb-3 flex items-center gap-3 p-4 rounded-lg bg-blue-100 border border-blue-400 text-blue-700">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    <span class="flex-1 text-sm font-medium">{{ session('info') }}</span>
                    <button @click="show = false" class="text-blue-700 hover:text-blue-900">&times;</button>
                </div>
                @endif
            </div>
            @endif

            <!-- Page Content -->
            <main>
                @yield('content')
                
            </main>
        </div>

        @stack('modals')
        @stack('scripts')
        

        <livewire:scripts />
        
        <script>
            // Definir __WS_TOKEN__ para evitar errores con el hot reload de Vite
            window.__WS_TOKEN__ = window.__WS_TOKEN__ || null;
        </script>

    </body>
</html>
