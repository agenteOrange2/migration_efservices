<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>EFCTS | Trucking Compliance Reimagined</title>

    <!-- Favicon -->
    <link rel="mask-icon" href="{{ asset('build/img/favicon_efservices.png') }}" color="#000000">
    <link rel="alternate icon" class="js-site-favicon" type="image/png"
        href="{{ asset('build/img/favicon_efservices.png') }}">
    <link rel="icon" class="js-site-favicon" type="image/svg+xml"
        href="{{ asset('build/img/favicon_efservices.png') }}"
        data-bse-href="{{ asset('build/img/favicon_efservices.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
    @stack('styles')

    <style>
        :root {
            --brand-blue: #08459f;
        }

        body {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
        }

        .video-overlay {
            background: linear-gradient(to bottom, rgba(5, 5, 5, 0.4) 0%, rgba(5, 5, 5, 0.2) 50%, rgba(5, 5, 5, 0.8) 100%);
        }

        .nav-link-new {
            position: relative;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .nav-link-new:hover {
            opacity: 1;
            color: var(--brand-blue);
        }

        .btn-brand {
            background-color: var(--brand-blue);
            transition: all 0.3s ease;
        }

        .btn-brand:hover {
            background-color: #06367a;
            transform: translateY(-2px);
        }

        .text-brand {
            color: var(--brand-blue);
        }

        .bg-brand {
            background-color: var(--brand-blue);
        }

        .border-brand {
            border-color: var(--brand-blue);
        }

        /* Mobile menu */
        .mobile-menu-panel {
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .mobile-menu-panel.active {
            transform: translateX(0);
        }

        /* Pricing toggle */
        .pricing-toggle .active-tab {
            background-color: var(--brand-blue);
            color: #fff;
        }

        /* Scroll animations */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .active-tab {
            color: #08459f;
            opacity: 1 !important;
            border-bottom: 2px solid #08459f;
            padding-bottom: 16px;
        }
    </style>
</head>

<body class="antialiased">
    @hasSection('content')
        @yield('content')
    @else
        {{ $slot ?? '' }}
    @endif

    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Mobile menu
            const menuToggle = document.getElementById('menu-toggle');
            const closeMenu = document.getElementById('close-menu');
            const mobileMenu = document.getElementById('mobile-menu');

            if (menuToggle && mobileMenu) {
                menuToggle.addEventListener('click', () => mobileMenu.classList.add('active'));
            }
            if (closeMenu && mobileMenu) {
                closeMenu.addEventListener('click', () => mobileMenu.classList.remove('active'));
            }

            // Close mobile menu on link click
            if (mobileMenu) {
                mobileMenu.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => mobileMenu.classList.remove('active'));
                });
            }

            // Header scroll effect
            const header = document.getElementById('main-header');
            if (header) {
                window.addEventListener('scroll', () => {
                    if (window.scrollY > 50) {
                        header.classList.add('bg-black/80', 'backdrop-blur-lg', 'border-white/10');
                        header.classList.remove('bg-black/20', 'backdrop-blur-md', 'border-white/5');
                    } else {
                        header.classList.remove('bg-black/80', 'backdrop-blur-lg', 'border-white/10');
                        header.classList.add('bg-black/20', 'backdrop-blur-md', 'border-white/5');
                    }
                });
            }

            // Scroll fade-up animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.1
            });

            document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
        });

        function openTab(evt, tabName) {
                var i, tabcontent, tablinks;
                tabcontent = document.getElementsByClassName("tab-content");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("tab-link");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].classList.remove("active-tab");
                }
                document.getElementById(tabName).style.display = "block";
                evt.currentTarget.classList.add("active-tab");
            }
    </script>
</body>

</html>
