<x-guest-layout>
    <div class="bg-[#050505] text-white">

        <!-- ========== HEADER ========== -->
        <header id="main-header"
            class="fixed w-full z-50 px-6 md:px-8 py-5 transition-all duration-300 border-b border-white/5 bg-black/20 backdrop-blur-md">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('respaldo/img/logo_efservices_logo.png') }}" class="w-8 h-8 object-contain"
                        alt="Logo EF">
                    <span class="text-xl font-extrabold tracking-tighter uppercase">EFCTS</span>
                </div>
                <nav class="hidden lg:flex gap-10 text-[10px] font-bold uppercase tracking-[0.2em]">
                    <a href="#features" class="nav-link-new" data-i18n="nav_features">Features</a>
                    <a href="#pricing" class="nav-link-new" data-i18n="nav_pricing">Pricing</a>
                    <a href="#testimonials" class="nav-link-new" data-i18n="nav_testimonials">Testimonials</a>
                    <a href="#contact" class="nav-link-new" data-i18n="nav_contact">Contact</a>
                </nav>
                <div class="hidden lg:flex items-center gap-6">
                    <button onclick="toggleLang()" id="lang-toggle"
                        class="text-[10px] font-bold uppercase tracking-widest border border-white/20 px-3 py-1.5 hover:border-white/50 transition rounded">ES</button>
                    @auth
                        @if (auth()->user()->hasRole('superadmin'))
                            <a href="{{ route('admin.dashboard') }}"
                                class="text-[10px] font-bold opacity-60 hover:opacity-100 transition uppercase tracking-widest">Dashboard</a>
                        @elseif(auth()->user()->hasRole('user_carrier'))
                            <a href="{{ route('carrier.dashboard') }}"
                                class="text-[10px] font-bold opacity-60 hover:opacity-100 transition uppercase tracking-widest">Dashboard</a>
                        @elseif(auth()->user()->hasRole('user_driver'))
                            <a href="{{ route('driver.dashboard') }}"
                                class="text-[10px] font-bold opacity-60 hover:opacity-100 transition uppercase tracking-widest">Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}"
                            class="text-[10px] font-bold opacity-60 hover:opacity-100 transition uppercase tracking-widest">Login</a>
                    @endauth
                    <a href="#contact"
                        class="btn-brand text-white px-6 py-2.5 text-[10px] font-bold uppercase tracking-widest"
                        data-i18n="nav_contact_btn">
                        Contact
                    </a>
                </div>
                <!-- Mobile Menu Button -->
                <div class="lg:hidden">
                    <button id="menu-toggle" class="w-8 h-8 flex flex-col justify-center items-center gap-1.5">
                        <span class="w-6 h-0.5 bg-white"></span>
                        <span class="w-5 h-0.5 bg-white"></span>
                        <span class="w-4 h-0.5 bg-white"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Mobile Menu -->
        <div id="mobile-menu"
            class="mobile-menu-panel lg:hidden fixed top-0 right-0 w-4/5 h-full bg-[#0a0a0a] z-[60] p-8">
            <div class="flex justify-end mb-10">
                <button id="close-menu" class="text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mb-6">
                <button onclick="toggleLang()" id="lang-toggle-mobile"
                    class="text-sm font-bold uppercase tracking-widest border border-white/20 px-4 py-2 hover:border-white/50 transition rounded text-white/70 hover:text-white">ES</button>
            </div>
            <nav class="space-y-6">
                <a href="#features"
                    class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition"
                    data-i18n="nav_features">Features</a>
                <a href="#pricing"
                    class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition"
                    data-i18n="nav_pricing">Pricing</a>
                <a href="#testimonials"
                    class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition"
                    data-i18n="nav_testimonials">Testimonials</a>
                <a href="#contact"
                    class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition"
                    data-i18n="nav_contact">Contact</a>
                <div class="border-t border-white/10 pt-6 mt-6 space-y-4">
                    @auth
                        @if (auth()->user()->hasRole('superadmin'))
                            <a href="{{ route('admin.dashboard') }}"
                                class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition">Dashboard</a>
                        @elseif(auth()->user()->hasRole('user_carrier'))
                            <a href="{{ route('carrier.dashboard') }}"
                                class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition">Dashboard</a>
                        @elseif(auth()->user()->hasRole('user_driver'))
                            <a href="{{ route('driver.dashboard') }}"
                                class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition">Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}"
                            class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition"
                            data-i18n="nav_login">Login</a>
                        <a href="{{ route('carrier.register') }}"
                            class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition"
                            data-i18n="nav_register_carrier">Register
                            as Carrier</a>
                        <a href="{{ route('driver.register') }}"
                            class="block text-sm font-bold uppercase tracking-widest text-white/70 hover:text-white transition"
                            data-i18n="nav_register_driver">Register
                            as Driver</a>
                    @endauth
                </div>
            </nav>
            <div class="mt-10">
                <a href="#contact"
                    class="block text-center btn-brand text-white py-3 px-5 text-xs font-bold uppercase tracking-widest"
                    data-i18n="nav_contact_btn">Contact</a>
            </div>
            <div class="mt-auto pt-10 text-[9px] text-gray-600 uppercase tracking-widest">
                <p>(432) 853-5493</p>
                <p class="mt-1">801 Magnolia St Kermit, TX 79745</p>
            </div>
        </div>


        <!-- ========== HERO (Video) ========== -->
        <section class="relative h-screen flex items-center justify-center overflow-hidden">
            <video class="absolute w-full h-full object-cover" autoplay muted loop playsinline>
                <source src="https://efcts.com/img/video/video_efcts.mp4" type="video/mp4">
            </video>
            <div class="absolute inset-0 video-overlay"></div>

            <div class="relative z-10 text-center px-6 mt-20">
                <h1 class="text-5xl md:text-8xl font-extrabold mb-8 leading-[1] tracking-tighter uppercase"
                    data-i18n-html="hero_title">
                    Your Trusted Partner in <br><span class="text-brand">Trucking Compliance</span>
                </h1>
                <p class="text-lg md:text-xl text-gray-200 mb-10 max-w-2xl mx-auto font-medium drop-shadow-lg"
                    data-i18n="hero_subtitle">
                    Comprehensive compliance, tax, and business services for the trucking industry.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                    <a href="#contact"
                        class="btn-brand px-10 py-4 text-xs font-bold uppercase tracking-[0.2em] shadow-2xl"
                        data-i18n-html="hero_cta">
                        Contact Us &darr;
                    </a>
                    <a href="tel:4328535493"
                        class="text-sm font-bold border-b border-white/30 pb-1 hover:border-white transition-all uppercase tracking-widest">
                        (432) 853-5493
                    </a>
                </div>
            </div>
        </section>

        <!-- ========== STATS BAR ========== -->
        <section class="w-full py-20 md:py-24 bg-white text-black">
            <div class="container px-4 md:px-6 mx-auto">
                <div class="flex flex-col items-center justify-center space-y-4 text-center mb-16">
                    <span
                        class="inline-flex items-center rounded-md bg-success px-3 py-1.5 text-sm font-medium text-white">
                        <i data-lucide="check-circle" class="h-4 w-4 mr-2"></i>
                        <span data-i18n="stats_badge">Platform in Action</span>
                    </span>
                    <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold tracking-tight text-primary max-w-3xl"
                        data-i18n="stats_title">
                        Already Helping Companies Stay Compliant
                    </h2>
                    <p class="max-w-[800px] text-xl text-gray-600" data-i18n="stats_subtitle">
                        Our platform is actively managing fleets, drivers, and compliance for transport companies across
                        the country.
                    </p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-16">
                    <div class="rounded-2xl p-8 text-center border border-primary hover:shadow-xl transition-all">
                        <div class="text-5xl font-bold text-primary mb-2">50+</div>
                        <div class="text-gray-700 font-medium" data-i18n="stats_carriers">Active Carriers</div>
                        <div class="text-sm text-gray-600 mt-2" data-i18n="stats_carriers_sub">Managing their fleets
                        </div>
                    </div>
                    <div class="rounded-2xl p-8 text-center border border-success hover:shadow-xl transition-all">
                        <div class="text-5xl font-bold text-success mb-2">200+</div>
                        <div class="text-gray-700 font-medium" data-i18n="stats_drivers">Registered Drivers</div>
                        <div class="text-sm text-gray-600 mt-2" data-i18n="stats_drivers_sub">Using the platform daily
                        </div>
                    </div>
                    <div class="rounded-2xl p-8 text-center border border-warning hover:shadow-xl transition-all">
                        <div class="text-5xl font-bold text-warning mb-2">1,500+</div>
                        <div class="text-gray-700 font-medium" data-i18n="stats_docs">Documents Managed</div>
                        <div class="text-sm text-gray-600 mt-2" data-i18n="stats_docs_sub">Securely stored and tracked
                        </div>
                    </div>
                    <div class="rounded-2xl p-8 text-center border border-success hover:shadow-xl transition-all">
                        <div class="text-5xl font-bold text-success mb-2">99%</div>
                        <div class="text-gray-700 font-medium" data-i18n="stats_compliance">Compliance Rate</div>
                        <div class="text-sm text-gray-600 mt-2" data-i18n="stats_compliance_sub">Audit-ready companies
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== TABS IMAGES ========== -->
        <section class="py-24 bg-[#050505]">
            <div class="max-w-7xl mx-auto px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-extrabold uppercase tracking-tighter mb-4" data-i18n="tabs_title">Explore the Platform</h2>
                    <p class="text-gray-500 max-w-2xl mx-auto" data-i18n="tabs_subtitle">Manage every detail of your fleet with an interface designed for efficiency.</p>
                </div>

                <div class="flex flex-col items-center">
                    <div class="flex gap-8 mb-12 border-b border-white/10 pb-4">
                        <button onclick="openTab(event, 'tab1')"
                            class="tab-link active-tab text-[10px] font-bold uppercase tracking-widest opacity-50 hover:opacity-100 transition-all" data-i18n="tabs_tab1">General</button>
                        <button onclick="openTab(event, 'tab2')"
                            class="tab-link text-[10px] font-bold uppercase tracking-widest opacity-50 hover:opacity-100 transition-all" data-i18n="tabs_tab2">Drivers</button>
                        <button onclick="openTab(event, 'tab3')"
                            class="tab-link text-[10px] font-bold uppercase tracking-widest opacity-50 hover:opacity-100 transition-all" data-i18n="tabs_tab3">Drugs & Testing</button>
                    </div>

                    <div id="tab1" class="tab-content block w-full">
                        <img src="{{ asset('/img/tabs/1_general.webp') }}"
                            class="w-full rounded-sm shadow-2xl  hover:grayscale-0 transition-all duration-700"
                            alt="General Dashboard">
                    </div>
                    <div id="tab2" class="tab-content hidden w-full">
                        <img src="{{ asset('/img/tabs/2_drivers.webp') }}"
                            class="w-full rounded-sm shadow-2xl  hover:grayscale-0 transition-all duration-700"
                            alt="Drivers Management">
                    </div>
                    <div id="tab3" class="tab-content hidden w-full">
                        <img src="{{ asset('/img/tabs/3_vehicle_drugs.webp') }}"
                            class="w-full rounded-sm shadow-2xl  hover:grayscale-0 transition-all duration-700"
                            alt="Drugs & Testing">
                    </div>
                </div>
            </div>
        </section>
        <!-- ========== CTA: Register ========== -->
        <section class="py-24 bg-brand text-center text-white">
            <div class="max-w-3xl mx-auto px-8 fade-up">
                <h3 class="text-3xl md:text-4xl font-extrabold uppercase tracking-tighter mb-4" data-i18n="cta_title">
                    Ready to Join Our
                    Growing Community?</h3>
                <p class="text-lg text-blue-100 mb-10 max-w-2xl mx-auto" data-i18n="cta_subtitle">
                    Start managing your fleet with confidence. Get started today and see why carriers trust EFCTS.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="{{ route('carrier.register') }}"
                        class="inline-flex items-center justify-center bg-white text-[#08459f] px-8 py-4 text-xs font-bold uppercase tracking-widest hover:bg-blue-50 transition-all shadow-lg">
                        <i data-lucide="building" class="h-4 w-4 mr-2"></i>
                        <span data-i18n="cta_register_carrier">Register as Carrier</span>
                    </a>
                    <a href="{{ route('driver.register') }}"
                        class="inline-flex items-center justify-center border-2 border-white text-white px-8 py-4 text-xs font-bold uppercase tracking-widest hover:bg-white/10 transition-all">
                        <i data-lucide="user" class="h-4 w-4 mr-2"></i>
                        <span data-i18n="cta_register_driver">Register as Driver</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- ========== FEATURES ========== -->
        <section id="features" class="py-32 px-8 bg-[#050505]">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-20 fade-up">
                    <h2 class="text-4xl md:text-5xl font-extrabold uppercase tracking-tighter mb-6"
                        data-i18n-html="features_title">
                        Stay Audit-Ready, <span class="text-brand">Stay Profitable</span>
                    </h2>
                    <p class="text-gray-500 max-w-2xl mx-auto" data-i18n="features_subtitle">
                        Our specialized platform helps carriers manage regulatory compliance and streamline driver
                        operations in line with US regulations.
                    </p>
                </div>

                <!-- Feature cards grid -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 fade-up">
                    <!-- Driver Management -->
                    <div class="border border-white/10 p-8 hover:border-brand/50 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-lg bg-brand/10 flex items-center justify-center mb-6">
                            <i data-lucide="user" class="h-6 w-6 text-brand"></i>
                        </div>
                        <h3 class="font-bold uppercase text-[11px] tracking-widest mb-4"
                            data-i18n="feat_driver_title">Driver Management</h3>
                        <p class="text-sm text-gray-500 mb-6" data-i18n="feat_driver_desc">Complete driver lifecycle
                            management with automated
                            document verification.</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-brand rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_driver_1">Automated background checks</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-brand rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_driver_2">DOT compliance management</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-brand rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_driver_3">Qualification file maintenance</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-brand rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_driver_4">License expiration tracking</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-brand rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_driver_5">Digital application forms</span>
                            </div>
                        </div>
                    </div>

                    <!-- Compliance & Reporting -->
                    <div class="border border-white/10 p-8 hover:border-brand/50 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-lg bg-green-900/20 flex items-center justify-center mb-6">
                            <i data-lucide="shield-check" class="h-6 w-6 text-green-400"></i>
                        </div>
                        <h3 class="font-bold uppercase text-[11px] tracking-widest mb-4"
                            data-i18n="feat_compliance_title">Compliance & Reporting</h3>
                        <p class="text-sm text-gray-500 mb-6" data-i18n="feat_compliance_desc">Stay prepared for DOT
                            audits with comprehensive
                            compliance reports.</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-green-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_compliance_1">Real-time compliance monitoring</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-green-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_compliance_2">Automated audit reports</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-green-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_compliance_3">Regulatory updates</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-green-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_compliance_4">DOT-compliant document retention</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-green-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_compliance_5">FMCSA safety rating tools</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hours of Service -->
                    <div class="border border-white/10 p-8 hover:border-brand/50 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-lg bg-amber-900/20 flex items-center justify-center mb-6">
                            <i data-lucide="clock" class="h-6 w-6 text-amber-400"></i>
                        </div>
                        <h3 class="font-bold uppercase text-[11px] tracking-widest mb-4" data-i18n="feat_hos_title">
                            Hours of Service</h3>
                        <p class="text-sm text-gray-500 mb-6" data-i18n="feat_hos_desc">Monitor driver hours and
                            breaks to ensure FMCSA
                            compliance.</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-amber-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_hos_1">ELD/HOS compliance monitoring</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-amber-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_hos_2">Break and rest period tracking</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-amber-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_hos_3">Violation risk alerts</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-amber-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_hos_4">Driver duty status logs</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-amber-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_hos_5">Automated RODS reporting</span>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Management -->
                    <div class="border border-white/10 p-8 hover:border-brand/50 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-lg bg-orange-900/20 flex items-center justify-center mb-6">
                            <i data-lucide="truck" class="h-6 w-6 text-orange-400"></i>
                        </div>
                        <h3 class="font-bold uppercase text-[11px] tracking-widest mb-4"
                            data-i18n="feat_vehicle_title">Vehicle Management</h3>
                        <p class="text-sm text-gray-500 mb-6" data-i18n="feat_vehicle_desc">Comprehensive fleet
                            maintenance and inspection tracking.
                        </p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-orange-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_vehicle_1">Preventive maintenance scheduling</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-orange-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_vehicle_2">Vehicle inspection reports</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-orange-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_vehicle_3">DVIR system integration</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-orange-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_vehicle_4">Registration and permit tracking</span>
                            </div>
                        </div>
                    </div>

                    {{-- <!-- Route Management -->
                    <div class="border border-white/10 p-8 hover:border-brand/50 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-lg bg-cyan-900/20 flex items-center justify-center mb-6">
                            <i data-lucide="map" class="h-6 w-6 text-cyan-400"></i>
                        </div>
                        <h3 class="font-bold uppercase text-[11px] tracking-widest mb-4" data-i18n="feat_route_title">Route Management</h3>
                        <p class="text-sm text-gray-500 mb-6" data-i18n="feat_route_desc">Track and optimize driver routes for operational
                            efficiency.</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-cyan-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_route_1">Route assignment and tracking</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-cyan-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_route_2">Rest location management</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-cyan-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_route_3">Performance metrics</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-cyan-400 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_route_4">Driver behavior monitoring</span>
                            </div>
                        </div>
                    </div> --}}

                    <!-- Smart Alerts -->
                    <div class="border border-white/10 p-8 hover:border-brand/50 transition-all duration-300 group">
                        <div class="h-12 w-12 rounded-lg bg-blue-900/20 flex items-center justify-center mb-6">
                            <i data-lucide="bell" class="h-6 w-6 text-blue-400"></i>
                        </div>
                        <h3 class="font-bold uppercase text-[11px] tracking-widest mb-4"
                            data-i18n="feat_alerts_title">Smart Alerts</h3>
                        <p class="text-sm text-gray-500 mb-6" data-i18n="feat_alerts_desc">Never miss an important
                            date with proactive
                            notifications.</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-blue-600 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_alerts_1">Expiration date reminders</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-blue-600 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_alerts_2">Compliance deadline alerts</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-blue-600 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_alerts_3">Document renewal notices</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400">
                                <span class="w-1 h-1 bg-blue-600 rounded-full flex-shrink-0"></span>
                                <span data-i18n="feat_alerts_4">Custom notification rules</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== PRICING ========== -->
        <section id="pricing" class="py-32 px-8 bg-white text-black">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16 fade-up">
                    <h2 class="text-4xl md:text-5xl font-extrabold uppercase tracking-tighter mb-4 text-[#050505]"
                        data-i18n-html="pricing_title">
                        Plans Tailored to <span class="text-brand">Your Needs</span>
                    </h2>
                    <p class="text-gray-500 max-w-xl mx-auto" data-i18n="pricing_subtitle">
                        Choose the plan that best fits your fleet size and specific requirements.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8 fade-up">
                    <!-- Beginner -->
                    <div class="border border-gray-200 p-8 hover:border-brand/40 transition-all group">
                        <h3 class="font-bold uppercase text-[11px] tracking-widest text-gray-400 mb-2"
                            data-i18n="pricing_beginner">Beginner</h3>
                        <p class="text-sm text-gray-500 mb-4" data-i18n="pricing_small_fleets">For small fleets</p>
                        <div class="flex items-baseline mb-6">
                            <span class="text-5xl font-extrabold text-[#050505]">$400</span>
                            <span class="ml-1 text-gray-400 text-sm">/month</span>
                        </div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_1_user">1 platform user access</span></li>
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_5_drivers">5 drivers management</span></li>
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_5_vehicles">5 vehicles in the system</span></li>
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_compliance_report">Compliance reporting</span></li>
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_email_support">Email support</span></li>
                        </ul>
                        <button onclick="openPlanModal('Beginner', 400)"
                            class="w-full block text-center border-2 border-[#050505] text-[#050505] py-3 text-xs font-bold uppercase tracking-widest hover:bg-[#050505] hover:text-white transition-all"
                            data-i18n="pricing_get_started">
                            Get Started
                        </button>
                    </div>

                    <!-- Intermediate (Popular) -->
                    <div class="border-2 border-brand p-8 relative">
                        <div
                            class="absolute top-0 left-0 right-0 bg-brand text-white text-center py-1.5 text-[9px] font-bold uppercase tracking-widest">
                            <span data-i18n="pricing_most_popular">Most Popular</span>
                        </div>
                        <div class="pt-4">
                            <h3 class="font-bold uppercase text-[11px] tracking-widest text-gray-400 mb-2"
                                data-i18n="pricing_intermediate">Intermediate
                            </h3>
                            <p class="text-sm text-gray-500 mb-4" data-i18n="pricing_medium_fleets">For medium fleets
                            </p>
                            <div class="flex items-baseline mb-6">
                                <span class="text-5xl font-extrabold text-brand">$600</span>
                                <span class="ml-1 text-gray-400 text-sm">/month</span>
                            </div>
                            <ul class="space-y-3 mb-8">
                                <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                        class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                        data-i18n="pricing_2_users">2 platform user access</span></li>
                                <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                        class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                        data-i18n="pricing_10_drivers">10 drivers management</span></li>
                                <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                        class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                        data-i18n="pricing_10_vehicles">10 vehicles in the system</span></li>
                                <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                        class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                        data-i18n="pricing_advanced_compliance">Advanced compliance tools</span></li>
                                <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                        class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                        data-i18n="pricing_priority_support">Priority email & phone support</span></li>
                            </ul>
                            <button onclick="openPlanModal('Intermediate', 600)"
                                class="w-full block text-center btn-brand text-white py-3 text-xs font-bold uppercase tracking-widest"
                                data-i18n="pricing_get_started">
                                Get Started
                            </button>
                        </div>
                    </div>

                    <!-- Pro -->
                    <div class="border border-gray-200 p-8 hover:border-brand/40 transition-all group">
                        <h3 class="font-bold uppercase text-[11px] tracking-widest text-gray-400 mb-2"
                            data-i18n="pricing_pro">Pro</h3>
                        <p class="text-sm text-gray-500 mb-4" data-i18n="pricing_growing_fleets">For growing fleets
                        </p>
                        <div class="flex items-baseline mb-6">
                            <span class="text-5xl font-extrabold text-[#050505]">$800</span>
                            <span class="ml-1 text-gray-400 text-sm">/month</span>
                        </div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_3_users">3 platform user access</span></li>
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_15_drivers">15 drivers management</span></li>
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_15_vehicles">15 vehicles in the system</span></li>
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_advanced_docs">Advanced document management</span></li>
                            <li class="flex items-center gap-3 text-sm text-gray-600"><span
                                    class="w-1.5 h-1.5 bg-brand rounded-full flex-shrink-0"></span> <span
                                    data-i18n="pricing_247_support">24/7 priority support</span></li>
                        </ul>
                        <button onclick="openPlanModal('Pro', 800)"
                            class="w-full block text-center border-2 border-[#050505] text-[#050505] py-3 text-xs font-bold uppercase tracking-widest hover:bg-[#050505] hover:text-white transition-all"
                            data-i18n="pricing_get_started">
                            Get Started
                        </button>
                    </div>
                </div>

                <div class="mt-16 border border-gray-200 p-8 flex flex-col md:flex-row gap-8 items-center fade-up">
                    <div class="md:w-2/3">
                        <h3 class="text-xl font-extrabold uppercase tracking-tighter text-[#050505] mb-2"
                            data-i18n="pricing_custom_title">Need a custom
                            solution?</h3>
                        <p class="text-sm text-gray-500" data-i18n="pricing_custom_desc">Contact us to discuss your
                            specific needs and create a
                            tailored plan for your company.</p>
                    </div>
                    <div class="md:w-1/3 flex justify-center">
                        <button onclick="openPlanModal('Custom', 0)"
                            class="btn-brand text-white py-3 px-8 text-xs font-bold uppercase tracking-widest"
                            data-i18n="pricing_contact_sales">Contact
                            Sales</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== TESTIMONIALS ========== -->
        <section id="testimonials" class="py-32 px-8 bg-[#050505]">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16 fade-up">
                    <h2 class="text-4xl md:text-5xl font-extrabold uppercase tracking-tighter mb-4"
                        data-i18n-html="testimonials_title">
                        What Our <span class="text-brand">Clients Say</span>
                    </h2>
                    <p class="text-gray-500 max-w-xl mx-auto" data-i18n="testimonials_subtitle">
                        Transportation companies across the country trust EFCTS to optimize their operations.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8 fade-up">
                    <div class="border border-white/10 p-8 hover:border-brand/30 transition-all">
                        <p class="text-gray-400 italic text-sm mb-8 leading-relaxed" data-i18n="testimonial_1">
                            "Since implementing EFCTS, we have reduced our operating costs by 20% and significantly
                            improved our fleet efficiency. The technical support is exceptional."
                        </p>
                        <div class="border-t border-white/10 pt-6">
                            <h4 class="font-bold text-sm uppercase tracking-widest">Carlos Rodriguez</h4>
                            <p class="text-[10px] text-gray-600 uppercase tracking-widest mt-1"
                                data-i18n="testimonial_1_role">Operations Director,
                                Transportes XYZ</p>
                        </div>
                    </div>
                    <div class="border border-white/10 p-8 hover:border-brand/30 transition-all">
                        <p class="text-gray-400 italic text-sm mb-8 leading-relaxed" data-i18n="testimonial_2">
                            "The platform is intuitive and easy to use. The support team is excellent and always
                            available to help us with any questions. It has transformed our operation."
                        </p>
                        <div class="border-t border-white/10 pt-6">
                            <h4 class="font-bold text-sm uppercase tracking-widest">Maria Gonzalez</h4>
                            <p class="text-[10px] text-gray-600 uppercase tracking-widest mt-1"
                                data-i18n="testimonial_2_role">CEO, Fast Logistics</p>
                        </div>
                    </div>
                    <div class="border border-white/10 p-8 hover:border-brand/30 transition-all">
                        <p class="text-gray-400 italic text-sm mb-8 leading-relaxed" data-i18n="testimonial_3">
                            "Route optimization has allowed us to save fuel and time. Our customers are more satisfied
                            with more accurate delivery times and real-time visibility."
                        </p>
                        <div class="border-t border-white/10 pt-6">
                            <h4 class="font-bold text-sm uppercase tracking-widest">Javier Lopez</h4>
                            <p class="text-[10px] text-gray-600 uppercase tracking-widest mt-1"
                                data-i18n="testimonial_3_role">Fleet Manager, National
                                Transport</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========== CONTACT ========== -->
        <section id="contact" class="py-32 px-8 bg-white text-black">
            <div class="max-w-4xl mx-auto fade-up">
                <h2 class="text-5xl font-extrabold uppercase tracking-tighter mb-6 text-center text-[#050505]"
                    data-i18n="contact_title">Contact
                    Us</h2>
                <p class="text-center text-gray-500 mb-16 max-w-xl mx-auto" data-i18n="contact_subtitle">
                    Contact us today for a personalized demo and discover how EFCTS can transform your transportation
                    operations.
                </p>
                <div id="contact-success"
                    class="hidden mb-8 p-6 bg-green-50 border border-green-200 text-green-800 text-center text-sm font-medium rounded">
                    <span data-i18n="contact_success">Thank you! We will contact you shortly.</span>
                </div>
                <form id="contact-form" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest"
                            data-i18n="contact_fullname">Full Name
                            *</label>
                        <input type="text" name="full_name" required
                            class="p-4 outline-none text-sm bg-gray-50 border border-gray-200 text-gray-900 focus:border-[#08459f]"
                            placeholder="Your full name" data-i18n-placeholder="contact_ph_name">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest"
                            data-i18n="contact_company">Company</label>
                        <input type="text" name="company"
                            class="p-4 outline-none text-sm bg-gray-50 border border-gray-200 text-gray-900 focus:border-[#08459f]"
                            placeholder="Your company name" data-i18n-placeholder="contact_ph_company">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest"
                            data-i18n="contact_email">Email *</label>
                        <input type="email" name="email" required
                            class="p-4 outline-none text-sm bg-gray-50 border border-gray-200 text-gray-900 focus:border-[#08459f]"
                            placeholder="email@company.com">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest"
                            data-i18n="contact_phone">Phone</label>
                        <input type="tel" name="phone"
                            class="p-4 outline-none text-sm bg-gray-50 border border-gray-200 text-gray-900 focus:border-[#08459f]"
                            placeholder="(000) 000-0000">
                    </div>
                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest"
                            data-i18n="contact_message">Message</label>
                        <textarea name="message"
                            class="p-4 outline-none text-sm h-32 bg-gray-50 border border-gray-200 text-gray-900 focus:border-[#08459f]"
                            placeholder="How can we help you?" data-i18n-placeholder="contact_ph_message"></textarea>
                    </div>
                    <div id="contact-error"
                        class="hidden md:col-span-2 p-4 bg-red-50 border border-red-200 text-red-700 text-sm text-center rounded">
                    </div>
                    <button type="submit" id="contact-btn"
                        class="md:col-span-2 btn-brand text-white py-5 font-bold uppercase text-xs tracking-[0.3em]"
                        data-i18n="contact_send">
                        Send Request
                    </button>
                </form>

                <div class="mt-16 grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
                    <a href="tel:+14328535493" class="group">
                        <div class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2"
                            data-i18n="contact_label_phone">Phone</div>
                        <div class="text-sm font-bold text-[#050505] group-hover:text-brand transition">(432) 853-5493
                        </div>
                    </a>
                    <a href="mailto:support@efcts.com" class="group">
                        <div class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2"
                            data-i18n="contact_label_email">Email</div>
                        <div class="text-sm font-bold text-[#050505] group-hover:text-brand transition">
                            support@efcts.com</div>
                    </a>
                    <a href="https://wa.me/14328535493" target="_blank" class="group">
                        <div class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2">WhatsApp</div>
                        <div class="text-sm font-bold text-[#050505] group-hover:text-brand transition"
                            data-i18n="contact_send_message">Send a message
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- ========== PLAN REQUEST MODAL ========== -->
        <div id="plan-modal"
            class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="bg-white text-black w-full max-w-lg mx-4 p-8 relative max-h-[90vh] overflow-y-auto"
                onclick="event.stopPropagation()">
                <button onclick="closePlanModal()"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                <h3 class="text-xl font-extrabold uppercase tracking-tighter text-[#050505] mb-1"
                    data-i18n="modal_title">Request Plan</h3>
                <p id="plan-modal-subtitle" class="text-sm text-gray-500 mb-6"></p>

                <div id="plan-success"
                    class="hidden mb-6 p-4 bg-green-50 border border-green-200 text-green-800 text-sm text-center font-medium rounded">
                    <span data-i18n="modal_success">Thank you! We will contact you shortly about this plan.</span>
                </div>

                <form id="plan-form" class="space-y-4">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="plan_name" id="plan-name-input">
                    <input type="hidden" name="plan_price" id="plan-price-input">
                    <div>
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest"
                            data-i18n="modal_fullname">Full Name
                            *</label>
                        <input type="text" name="full_name" required
                            class="w-full p-3 mt-1 outline-none text-sm bg-gray-50 border border-gray-200 text-gray-900 focus:border-[#08459f]"
                            placeholder="Your full name" data-i18n-placeholder="modal_ph_name">
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest"
                            data-i18n="modal_email">Email *</label>
                        <input type="email" name="email" required
                            class="w-full p-3 mt-1 outline-none text-sm bg-gray-50 border border-gray-200 text-gray-900 focus:border-[#08459f]"
                            placeholder="email@company.com">
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest"
                            data-i18n="modal_company">Company</label>
                        <input type="text" name="company"
                            class="w-full p-3 mt-1 outline-none text-sm bg-gray-50 border border-gray-200 text-gray-900 focus:border-[#08459f]"
                            placeholder="Your company name" data-i18n-placeholder="modal_ph_company">
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest"
                            data-i18n="modal_phone">Phone</label>
                        <input type="tel" name="phone"
                            class="w-full p-3 mt-1 outline-none text-sm bg-gray-50 border border-gray-200 text-gray-900 focus:border-[#08459f]"
                            placeholder="(000) 000-0000">
                    </div>
                    <div id="plan-error"
                        class="hidden p-3 bg-red-50 border border-red-200 text-red-700 text-sm text-center rounded">
                    </div>
                    <button type="submit" id="plan-btn"
                        class="w-full btn-brand text-white py-4 font-bold uppercase text-xs tracking-[0.3em]"
                        data-i18n="modal_submit">
                        Submit Request
                    </button>
                </form>
            </div>
        </div>

        <!-- ========== FOOTER ========== -->
        <footer class="py-12 bg-black text-center">
            <p class="text-[9px] text-gray-300 font-bold tracking-[0.4em] uppercase">
                EFCTS LLC | 801 Magnolia St Kermit, TX 79745 | (432) 853-5493
            </p>
        </footer>

        <!-- ========== SUBMISSION & TRANSLATION SCRIPTS ========== -->
        <script>
            // ==================== TRANSLATIONS ====================
            let currentLang = localStorage.getItem('efcts_lang') || 'en';
            const enOriginals = {};

            const translations = {
                es: {
                    // Nav
                    nav_features: 'Caracteristicas',
                    nav_pricing: 'Precios',
                    nav_testimonials: 'Testimonios',
                    nav_contact: 'Contacto',
                    nav_contact_btn: 'Contacto',
                    nav_login: 'Iniciar Sesion',
                    nav_register_carrier: 'Registrarse como Carrier',
                    nav_register_driver: 'Registrarse como Conductor',
                    // Hero
                    hero_title: 'Tu Socio de Confianza en <br><span class="text-brand">Cumplimiento de Transporte</span>',
                    hero_subtitle: 'Servicios integrales de cumplimiento, impuestos y negocios para la industria del transporte.',
                    hero_cta: 'Contactanos &darr;',
                    // Tabs
                    tabs_title: 'Explora la Plataforma',
                    tabs_subtitle: 'Gestiona cada detalle de tu flota con una interfaz diseñada para la eficiencia.',
                    tabs_tab1: 'General',
                    tabs_tab2: 'Conductores',
                    tabs_tab3: 'Drogas y Pruebas',
                    // Stats
                    stats_badge: 'Plataforma en Accion',
                    stats_title: 'Ya Ayudando a Empresas a Mantenerse en Cumplimiento',
                    stats_subtitle: 'Nuestra plataforma gestiona activamente flotas, conductores y cumplimiento para empresas de transporte en todo el pais.',
                    stats_carriers: 'Carriers Activos',
                    stats_carriers_sub: 'Gestionando sus flotas',
                    stats_drivers: 'Conductores Registrados',
                    stats_drivers_sub: 'Usando la plataforma diariamente',
                    stats_docs: 'Documentos Gestionados',
                    stats_docs_sub: 'Almacenados y rastreados de forma segura',
                    stats_compliance: 'Tasa de Cumplimiento',
                    stats_compliance_sub: 'Empresas listas para auditoria',
                    // CTA
                    cta_title: '\u00bfListo para Unirte a Nuestra Comunidad en Crecimiento?',
                    cta_subtitle: 'Comienza a gestionar tu flota con confianza. Empieza hoy y descubre por que los carriers confian en EFCTS.',
                    cta_register_carrier: 'Registrarse como Carrier',
                    cta_register_driver: 'Registrarse como Conductor',
                    // Features
                    features_title: 'Mantente Listo para Auditoria, <span class="text-brand">Mantente Rentable</span>',
                    features_subtitle: 'Nuestra plataforma especializada ayuda a los carriers a gestionar el cumplimiento regulatorio y optimizar las operaciones de conductores segun las regulaciones de EE.UU.',
                    feat_driver_title: 'Gestion de Conductores',
                    feat_driver_desc: 'Gestion completa del ciclo de vida del conductor con verificacion automatizada de documentos.',
                    feat_driver_1: 'Verificacion automatizada de antecedentes',
                    feat_driver_2: 'Gestion de cumplimiento DOT',
                    feat_driver_3: 'Mantenimiento de archivos de calificacion',
                    feat_driver_4: 'Seguimiento de vencimiento de licencias',
                    feat_driver_5: 'Formularios de solicitud digital',
                    feat_compliance_title: 'Cumplimiento y Reportes',
                    feat_compliance_desc: 'Mantente preparado para auditorias DOT con reportes de cumplimiento completos.',
                    feat_compliance_1: 'Monitoreo de cumplimiento en tiempo real',
                    feat_compliance_2: 'Reportes de auditoria automatizados',
                    feat_compliance_3: 'Actualizaciones regulatorias',
                    feat_compliance_4: 'Retencion de documentos conforme a DOT',
                    feat_compliance_5: 'Herramientas de calificacion de seguridad FMCSA',
                    feat_hos_title: 'Horas de Servicio',
                    feat_hos_desc: 'Monitorea las horas y descansos de los conductores para asegurar el cumplimiento FMCSA.',
                    feat_hos_1: 'Monitoreo de cumplimiento ELD/HOS',
                    feat_hos_2: 'Seguimiento de periodos de descanso',
                    feat_hos_3: 'Alertas de riesgo de violacion',
                    feat_hos_4: 'Registros de estado de servicio del conductor',
                    feat_hos_5: 'Reportes RODS automatizados',
                    feat_vehicle_title: 'Gestion de Vehiculos',
                    feat_vehicle_desc: 'Mantenimiento integral de flota y seguimiento de inspecciones.',
                    feat_vehicle_1: 'Programacion de mantenimiento preventivo',
                    feat_vehicle_2: 'Reportes de inspeccion de vehiculos',
                    feat_vehicle_3: 'Integracion con sistema DVIR',
                    feat_vehicle_4: 'Seguimiento de registro y permisos',
                    feat_route_title: 'Gestion de Rutas',
                    feat_route_desc: 'Rastrea y optimiza las rutas de los conductores para eficiencia operativa.',
                    feat_route_1: 'Asignacion y seguimiento de rutas',
                    feat_route_2: 'Gestion de ubicaciones de descanso',
                    feat_route_3: 'Metricas de rendimiento',
                    feat_route_4: 'Monitoreo de comportamiento del conductor',
                    feat_alerts_title: 'Alertas Inteligentes',
                    feat_alerts_desc: 'Nunca pierdas una fecha importante con notificaciones proactivas.',
                    feat_alerts_1: 'Recordatorios de fechas de vencimiento',
                    feat_alerts_2: 'Alertas de plazos de cumplimiento',
                    feat_alerts_3: 'Avisos de renovacion de documentos',
                    feat_alerts_4: 'Reglas de notificacion personalizadas',
                    // Pricing
                    pricing_title: 'Planes Adaptados a <span class="text-brand">Tus Necesidades</span>',
                    pricing_subtitle: 'Elige el plan que mejor se adapte al tamano de tu flota y requisitos especificos.',
                    pricing_beginner: 'Principiante',
                    pricing_small_fleets: 'Para flotas pequenas',
                    pricing_get_started: 'Comenzar',
                    pricing_most_popular: 'Mas Popular',
                    pricing_intermediate: 'Intermedio',
                    pricing_medium_fleets: 'Para flotas medianas',
                    pricing_pro: 'Pro',
                    pricing_growing_fleets: 'Para flotas en crecimiento',
                    pricing_1_user: '1 usuario de plataforma',
                    pricing_5_drivers: '5 conductores',
                    pricing_5_vehicles: '5 vehiculos en el sistema',
                    pricing_compliance_report: 'Reportes de cumplimiento',
                    pricing_email_support: 'Soporte por correo',
                    pricing_2_users: '2 usuarios de plataforma',
                    pricing_10_drivers: '10 conductores',
                    pricing_10_vehicles: '10 vehiculos en el sistema',
                    pricing_advanced_compliance: 'Herramientas avanzadas de cumplimiento',
                    pricing_priority_support: 'Soporte prioritario por correo y telefono',
                    pricing_3_users: '3 usuarios de plataforma',
                    pricing_15_drivers: '15 conductores',
                    pricing_15_vehicles: '15 vehiculos en el sistema',
                    pricing_advanced_docs: 'Gestion avanzada de documentos',
                    pricing_247_support: 'Soporte prioritario 24/7',
                    pricing_custom_title: '\u00bfNecesitas una solucion personalizada?',
                    pricing_custom_desc: 'Contactanos para discutir tus necesidades especificas y crear un plan a medida para tu empresa.',
                    pricing_contact_sales: 'Contactar Ventas',
                    // Testimonials
                    testimonials_title: 'Lo Que Dicen Nuestros <span class="text-brand">Clientes</span>',
                    testimonials_subtitle: 'Empresas de transporte en todo el pais confian en EFCTS para optimizar sus operaciones.',
                    testimonial_1: '"Desde que implementamos EFCTS, hemos reducido nuestros costos operativos en un 20% y mejorado significativamente la eficiencia de nuestra flota. El soporte tecnico es excepcional."',
                    testimonial_1_role: 'Director de Operaciones, Transportes XYZ',
                    testimonial_2: '"La plataforma es intuitiva y facil de usar. El equipo de soporte es excelente y siempre esta disponible para ayudarnos con cualquier consulta. Ha transformado nuestra operacion."',
                    testimonial_2_role: 'CEO, Fast Logistics',
                    testimonial_3: '"La optimizacion de rutas nos ha permitido ahorrar combustible y tiempo. Nuestros clientes estan mas satisfechos con tiempos de entrega mas precisos y visibilidad en tiempo real."',
                    testimonial_3_role: 'Gerente de Flota, National Transport',
                    // Contact
                    contact_title: 'Contactanos',
                    contact_subtitle: 'Contactanos hoy para una demostracion personalizada y descubre como EFCTS puede transformar tus operaciones de transporte.',
                    contact_success: '\u00a1Gracias! Nos pondremos en contacto contigo pronto.',
                    contact_fullname: 'Nombre Completo *',
                    contact_company: 'Empresa',
                    contact_email: 'Correo Electronico *',
                    contact_phone: 'Telefono',
                    contact_message: 'Mensaje',
                    contact_send: 'Enviar Solicitud',
                    contact_ph_name: 'Tu nombre completo',
                    contact_ph_company: 'Nombre de tu empresa',
                    contact_ph_message: '\u00bfComo podemos ayudarte?',
                    contact_label_phone: 'Telefono',
                    contact_label_email: 'Correo',
                    contact_send_message: 'Enviar un mensaje',
                    // Modal
                    modal_title: 'Solicitar Plan',
                    modal_success: '\u00a1Gracias! Nos pondremos en contacto contigo pronto sobre este plan.',
                    modal_fullname: 'Nombre Completo *',
                    modal_email: 'Correo Electronico *',
                    modal_company: 'Empresa',
                    modal_phone: 'Telefono',
                    modal_submit: 'Enviar Solicitud',
                    modal_ph_name: 'Tu nombre completo',
                    modal_ph_company: 'Nombre de tu empresa',
                    // JS dynamic
                    js_sending: 'Enviando...',
                    js_send_request: 'Enviar Solicitud',
                    js_submit_request: 'Enviar Solicitud',
                    js_custom_subtitle: 'Cuentanos sobre tus necesidades y crearemos un plan personalizado.',
                    js_plan_suffix: '/mes',
                    js_error: 'Ocurrio un error. Por favor intenta de nuevo.',
                    js_network_error: 'Error de red. Por favor intenta de nuevo.',
                }
            };

            // Store English originals on first load
            function storeOriginals() {
                document.querySelectorAll('[data-i18n]').forEach(el => {
                    enOriginals[el.getAttribute('data-i18n')] = el.textContent.trim();
                });
                document.querySelectorAll('[data-i18n-html]').forEach(el => {
                    enOriginals[el.getAttribute('data-i18n-html')] = el.innerHTML.trim();
                });
                document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                    enOriginals[el.getAttribute('data-i18n-placeholder')] = el.getAttribute('placeholder');
                });
            }

            function applyLanguage(lang) {
                currentLang = lang;
                localStorage.setItem('efcts_lang', lang);

                if (lang === 'es') {
                    document.querySelectorAll('[data-i18n]').forEach(el => {
                        const key = el.getAttribute('data-i18n');
                        if (translations.es[key]) el.textContent = translations.es[key];
                    });
                    document.querySelectorAll('[data-i18n-html]').forEach(el => {
                        const key = el.getAttribute('data-i18n-html');
                        if (translations.es[key]) el.innerHTML = translations.es[key];
                    });
                    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                        const key = el.getAttribute('data-i18n-placeholder');
                        if (translations.es[key]) el.setAttribute('placeholder', translations.es[key]);
                    });
                } else {
                    document.querySelectorAll('[data-i18n]').forEach(el => {
                        const key = el.getAttribute('data-i18n');
                        if (enOriginals[key]) el.textContent = enOriginals[key];
                    });
                    document.querySelectorAll('[data-i18n-html]').forEach(el => {
                        const key = el.getAttribute('data-i18n-html');
                        if (enOriginals[key]) el.innerHTML = enOriginals[key];
                    });
                    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                        const key = el.getAttribute('data-i18n-placeholder');
                        if (enOriginals[key]) el.setAttribute('placeholder', enOriginals[key]);
                    });
                }

                // Update toggle buttons
                const label = lang === 'es' ? 'EN' : 'ES';
                const desktop = document.getElementById('lang-toggle');
                const mobile = document.getElementById('lang-toggle-mobile');
                if (desktop) desktop.textContent = label;
                if (mobile) mobile.textContent = label;
            }

            function toggleLang() {
                applyLanguage(currentLang === 'en' ? 'es' : 'en');
            }

            // Helper to get translated dynamic string
            function t(key, fallback) {
                if (currentLang === 'es' && translations.es[key]) return translations.es[key];
                return fallback;
            }

            // Init on load
            document.addEventListener('DOMContentLoaded', function() {
                storeOriginals();
                if (currentLang === 'es') applyLanguage('es');
            });

            // ==================== CONTACT FORM AJAX ====================
            document.getElementById('contact-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = document.getElementById('contact-btn');
                const errorEl = document.getElementById('contact-error');
                const successEl = document.getElementById('contact-success');
                const form = this;

                btn.disabled = true;
                btn.textContent = t('js_sending', 'Sending...');
                errorEl.classList.add('hidden');

                try {
                    const formData = new FormData(form);
                    const res = await fetch('{{ route('submit.contact') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': formData.get('_token'),
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        form.classList.add('hidden');
                        successEl.classList.remove('hidden');
                    } else {
                        let msg = data.message || t('js_error', 'An error occurred. Please try again.');
                        if (data.errors) {
                            msg = Object.values(data.errors).flat().join('<br>');
                        }
                        errorEl.innerHTML = msg;
                        errorEl.classList.remove('hidden');
                    }
                } catch (err) {
                    errorEl.textContent = t('js_network_error', 'Network error. Please try again.');
                    errorEl.classList.remove('hidden');
                }

                btn.disabled = false;
                btn.textContent = t('js_send_request', 'Send Request');
            });

            // ==================== PLAN MODAL ====================
            function openPlanModal(planName, planPrice) {
                const modal = document.getElementById('plan-modal');
                document.getElementById('plan-name-input').value = planName;
                document.getElementById('plan-price-input').value = planPrice;

                let subtitle;
                if (planName === 'Custom') {
                    subtitle = t('js_custom_subtitle', 'Tell us about your needs and we\'ll create a custom plan.');
                } else {
                    const suffix = t('js_plan_suffix', '/month');
                    subtitle = planName + ' Plan \u2014 $' + planPrice + suffix;
                }
                document.getElementById('plan-modal-subtitle').textContent = subtitle;

                document.getElementById('plan-form').classList.remove('hidden');
                document.getElementById('plan-success').classList.add('hidden');
                document.getElementById('plan-error').classList.add('hidden');
                document.getElementById('plan-form').reset();
                document.getElementById('plan-name-input').value = planName;
                document.getElementById('plan-price-input').value = planPrice;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closePlanModal() {
                const modal = document.getElementById('plan-modal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.getElementById('plan-modal').addEventListener('click', function(e) {
                if (e.target === this) closePlanModal();
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closePlanModal();
            });

            // ==================== PLAN FORM AJAX ====================
            document.getElementById('plan-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = document.getElementById('plan-btn');
                const errorEl = document.getElementById('plan-error');
                const successEl = document.getElementById('plan-success');
                const form = this;

                btn.disabled = true;
                btn.textContent = t('js_sending', 'Sending...');
                errorEl.classList.add('hidden');

                try {
                    const formData = new FormData(form);
                    const res = await fetch('{{ route('submit.plan-request') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': formData.get('_token'),
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        form.classList.add('hidden');
                        successEl.classList.remove('hidden');
                    } else {
                        let msg = data.message || t('js_error', 'An error occurred. Please try again.');
                        if (data.errors) {
                            msg = Object.values(data.errors).flat().join('<br>');
                        }
                        errorEl.innerHTML = msg;
                        errorEl.classList.remove('hidden');
                    }
                } catch (err) {
                    errorEl.textContent = t('js_network_error', 'Network error. Please try again.');
                    errorEl.classList.remove('hidden');
                }

                btn.disabled = false;
                btn.textContent = t('js_submit_request', 'Submit Request');
            });
        </script>

    </div>
</x-guest-layout>
