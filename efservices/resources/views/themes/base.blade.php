<!DOCTYPE html>
<html class="opacity-0" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!-- BEGIN: Head -->

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="EFCTS - Fleet Management System">
    <meta name="author" content="EFCTS">    

    <link rel="mask-icon" href="{{asset('build/img/favicon_efservices.png')}}" color="#000000">
    <link rel="alternate icon" class="js-site-favicon" type="image/png" href="{{asset('build/img/favicon_efservices.png')}}">
    <link rel="icon" class="js-site-favicon" type="image/svg+xml" href="{{asset('build/img/favicon_efservices.png')}}">

    <title>@yield('title') | EFCTS</title>

    <!-- jQuery MUST load first - before any other scripts (using jsdelivr which is allowed by CSP) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script>
        // Ensure jQuery is available globally
        if (typeof jQuery !== 'undefined') {
            window.$ = window.jQuery = jQuery;
        }
    </script>

    @stack('head')

    <!-- BEGIN: CSS Assets-->
    @stack('styles')
    <!-- END: CSS Assets-->

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>
<!-- END: Head -->

<body>
    
    <!-- Flash Messages -->
    @if(session('success') || session('error') || session('warning') || session('info'))
    <div style="position:fixed;top:1rem;right:1rem;z-index:9999;max-width:28rem;width:100%;" id="flash-messages">
        @if(session('success'))
        <div class="flex items-center gap-3 p-4 mb-3 rounded-lg bg-green-100 border border-green-400 text-green-700 shadow-lg" id="flash-success">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="flex-1 text-sm font-medium">{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900 text-lg font-bold">&times;</button>
        </div>
        @endif
        @if(session('error'))
        <div class="flex items-center gap-3 p-4 mb-3 rounded-lg bg-red-100 border border-red-400 text-red-700 shadow-lg" id="flash-error">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
            <span class="flex-1 text-sm font-medium">{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900 text-lg font-bold">&times;</button>
        </div>
        @endif
        @if(session('warning'))
        <div class="flex items-center gap-3 p-4 mb-3 rounded-lg bg-yellow-100 border border-yellow-400 text-yellow-700 shadow-lg" id="flash-warning">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <span class="flex-1 text-sm font-medium">{{ session('warning') }}</span>
            <button onclick="this.parentElement.remove()" class="text-yellow-700 hover:text-yellow-900 text-lg font-bold">&times;</button>
        </div>
        @endif
        @if(session('info'))
        <div class="flex items-center gap-3 p-4 mb-3 rounded-lg bg-blue-100 border border-blue-400 text-blue-700 shadow-lg" id="flash-info">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
            <span class="flex-1 text-sm font-medium">{{ session('info') }}</span>
            <button onclick="this.parentElement.remove()" class="text-blue-700 hover:text-blue-900 text-lg font-bold">&times;</button>
        </div>
        @endif
    </div>
    <script>
        setTimeout(function() {
            var el = document.getElementById('flash-messages');
            if (el) el.style.display = 'none';
        }, 5000);
    </script>
    @endif

    @yield('content')

    <!-- BEGIN: Vendor JS Assets-->
    @vite('resources/js/vendors/dom.js')
    @vite('resources/js/vendors/tailwind-merge.js')
    @stack('vendors')
    <!-- END: Vendor JS Assets-->
    
    <!-- BEGIN: Pages, layouts, components JS Assets-->
    @vite('resources/js/components/base/theme-color.js')
    @stack('scripts')
    <!-- END: Pages, layouts, components JS Assets-->

    @livewireScripts    

    <script>
        // Definir __WS_TOKEN__ para evitar errores con el hot reload de Vite
        window.__WS_TOKEN__ = window.__WS_TOKEN__ || null;
    </script>
</body>

</html>
