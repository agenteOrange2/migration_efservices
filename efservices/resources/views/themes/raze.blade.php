@extends('../themes/base')

@section('head')
    @yield('subhead')
@endsection

@section('content')
    <div @class([
        'raze',
        "before:content-[''] before:bg-gradient-to-b before:from-slate-100 before:to-slate-50 before:h-screen before:w-full before:fixed before:top-0",
    ])>
        <div @class([
            '[&.loading-page--before-hide]:h-screen [&.loading-page--before-hide]:relative loading-page loading-page--before-hide',
            "[&.loading-page--before-hide]:before:block [&.loading-page--hide]:before:opacity-0 before:content-[''] before:transition-opacity before:duration-300 before:hidden before:inset-0 before:h-screen before:w-screen before:fixed before:bg-gradient-to-b before:from-theme-1 before:to-theme-2 before:z-[60]",
            "[&.loading-page--before-hide]:after:block [&.loading-page--hide]:after:opacity-0 after:content-[''] after:transition-opacity after:duration-300 after:hidden after:h-16 after:w-16 after:animate-pulse after:fixed after:opacity-50 after:inset-0 after:m-auto after:bg-loading-puff after:bg-cover after:z-[61]",
        ])>
            <div @class([
                'xl:ml-0 shadow-xl transition-[margin] duration-300 xl:shadow-none fixed top-0 left-0 z-50 side-menu group',
                "after:content-[''] after:fixed after:inset-0 after:bg-black/80 after:xl:hidden",
                'side-menu--collapsed',
                '[&.side-menu--mobile-menu-open]:ml-0 [&.side-menu--mobile-menu-open]:after:block',
                '-ml-[275px] after:hidden',
            ])>
                <div @class([
                    'close-mobile-menu fixed ml-[275px] w-10 h-10 items-center justify-center xl:hidden z-50',
                    '[&.close-mobile-menu--mobile-menu-open]:flex',
                    'hidden',
                ])>
                    <a class="ml-5 mt-5" href="">
                        <x-base.lucide class="h-8 w-8 text-white" icon="X" />
                    </a>
                </div>
                <div @class([
                    'side-menu__content bg-gradient-to-b from-theme-1 to-theme-2 z-20 relative w-[275px] duration-300 transition-[width] xl:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0] group-[.side-menu--collapsed]:xl:w-[91px] group-[.side-menu--collapsed.side-menu--on-hover]:xl:shadow-[6px_0_12px_-4px_#0000000f] group-[.side-menu--collapsed.side-menu--on-hover]:xl:w-[275px] overflow-hidden h-screen flex flex-col',
                    "after:content-[''] after:absolute after:inset-0 after:-mr-4 after:bg-texture-white after:bg-contain after:bg-fixed after:bg-[center_-20rem] after:bg-no-repeat",
                ])>
                    @php
                        $currentUser = auth()->user();
                        $isCarrier = $currentUser && $currentUser->hasRole('user_carrier');
                        $isDriver = $currentUser && $currentUser->hasRole('user_driver');
                        $isAdmin = $currentUser && $currentUser->hasRole('superadmin');
                        
                        // Obtener datos del carrier si es usuario carrier
                        $carrierData = null;
                        $carrierLogo = null;
                        $carrierName = 'EFCT';
                        
                        if ($isCarrier && $currentUser->carrierDetails && $currentUser->carrierDetails->carrier) {
                            $carrierData = $currentUser->carrierDetails->carrier;
                            $carrierLogo = $carrierData->getFirstMediaUrl('logo_carrier');
                            $carrierName = $carrierData->name ?? 'EFCT';
                        }
                        
                        // URL del dashboard según el rol
                        if ($isCarrier && Route::has('carrier.dashboard')) {
                            $dashboardUrl = route('carrier.dashboard');
                        } elseif ($isDriver && Route::has('driver.dashboard')) {
                            $dashboardUrl = route('driver.dashboard');
                        } elseif (Route::has('admin.dashboard')) {
                            $dashboardUrl = route('admin.dashboard');
                        } else {
                            $dashboardUrl = '/';
                        }
                    @endphp
                    <div
                        class="relative z-10 hidden h-[65px] w-[275px] flex-none items-center overflow-hidden px-5 duration-300 xl:flex group-[.side-menu--collapsed.side-menu--on-hover]:xl:w-[275px] group-[.side-menu--collapsed]:xl:w-[91px]">
                        <a class="flex items-center transition-[margin] duration-300 group-[.side-menu--collapsed.side-menu--on-hover]:xl:ml-0 group-[.side-menu--collapsed]:xl:ml-2"
                            href="{{ $dashboardUrl }}">
                            <div
                                class="flex h-[34px] w-[34px] items-center justify-center rounded-lg bg-white/[0.08] transition-transform ease-in-out group-[.side-menu--collapsed.side-menu--on-hover]:xl:-rotate-180 overflow-hidden">
                                @if($isCarrier && $carrierLogo)
                                    <img src="{{ $carrierLogo }}" class="w-full h-full object-cover" alt="{{ $carrierName }}">
                                @else
                                    <img src="{{ asset('build/img/logo_efservices_logo.png') }}" class="w-[80px]" alt="EFCT">
                                @endif
                            </div>
                            <div
                                class="ml-3.5 font-medium text-white transition-opacity group-[.side-menu--collapsed.side-menu--on-hover]:xl:opacity-100 group-[.side-menu--collapsed]:xl:opacity-0">
                                <div class="text-sm font-bold truncate max-w-[150px]">
                                    @if($isCarrier && $carrierData)
                                        {{ Str::limit($carrierName, 18) }}
                                    @else
                                        EFCTS
                                    @endif
                                </div>
                                @if($isCarrier && $carrierData)
                                    <div class="text-[10px] text-white/60 truncate max-w-[150px]">
                                        DOT: {{ $carrierData->dot_number ?? 'N/A' }}
                                    </div>
                                @endif
                            </div>
                        </a>
                        <a class="toggle-compact-menu ml-auto hidden h-[20px] w-[20px] items-center justify-center rounded-full border border-white/40 text-white transition-[opacity,transform] hover:bg-white/5 group-[.side-menu--collapsed]:xl:rotate-180 group-[.side-menu--collapsed.side-menu--on-hover]:xl:opacity-100 group-[.side-menu--collapsed]:xl:opacity-0 3xl:flex"
                            href="">
                            <x-base.lucide class="h-3.5 w-3.5 stroke-[1.3]" icon="ArrowLeft" />
                        </a>
                    </div>
                    <div @class([
                        'scrollable-ref w-full h-full z-20 px-5 overflow-y-auto overflow-x-hidden pb-3 [-webkit-mask-image:-webkit-linear-gradient(top,rgba(0,0,0,0),black_30px)] [&:-webkit-scrollbar]:w-0 [&:-webkit-scrollbar]:bg-transparent',
                        '[&_.simplebar-content]:p-0 [&_.simplebar-track.simplebar-vertical]:w-[10px] [&_.simplebar-track.simplebar-vertical]:mr-0.5 [&_.simplebar-track.simplebar-vertical_.simplebar-scrollbar]:before:bg-slate-400/30',
                    ])>
                        <ul class="scrollable">
                            <!-- BEGIN: First Child -->
                            @foreach ($sideMenu as $menuKey => $menu)
                                @if (is_string($menu))
                                    <li class="side-menu__divider">
                                        {{ $menu }}
                                    </li>
                                @else
                                    <li>
                                        <a href="{{ isset($menu['route_name']) && Route::has($menu['route_name']) ? route($menu['route_name'], $menu['params']) : 'javascript:;' }}"
                                            @class([
                                                'side-menu__link',
                                                $firstLevelActiveIndex == $menuKey ? 'side-menu__link--active' : '',
                                                $firstLevelActiveIndex == $menuKey && isset($menu['sub_menu'])
                                                    ? 'side-menu__link--active-dropdown'
                                                    : '',
                                            ])>
                                            <x-base.lucide class="side-menu__link__icon" :icon="$menu['icon']" />
                                            <div class="side-menu__link__title">{{ $menu['title'] }}</div>
                                            @if (isset($menu['badge']))
                                                <div class="side-menu__link__badge">
                                                    {{ $menu['badge'] }}
                                                </div>
                                            @endif
                                            @if (isset($menu['sub_menu']))
                                                <x-base.lucide class="side-menu__link__chevron" icon="ChevronDown" />
                                            @endif
                                        </a>
                                        <!-- BEGIN: Second Child -->
                                        @if (isset($menu['sub_menu']))
                                            <ul class="{{ $firstLevelActiveIndex == $menuKey ? 'block' : 'hidden' }}">
                                                @foreach ($menu['sub_menu'] as $subMenuKey => $subMenu)
                                                    <li>
                                                        <a href="{{ isset($subMenu['route_name']) && Route::has($subMenu['route_name']) ? route($subMenu['route_name'], $subMenu['params']) : 'javascript:;' }}"
                                                            @class([
                                                                'side-menu__link',
                                                                $firstLevelActiveIndex == $menuKey && $secondLevelActiveIndex == $subMenuKey
                                                                    ? 'side-menu__link--active'
                                                                    : '',
                                                                $secondLevelActiveIndex == $subMenuKey && isset($subMenu['sub_menu'])
                                                                    ? 'side-menu__link--active-dropdown'
                                                                    : '',
                                                            ])>
                                                            <x-base.lucide class="side-menu__link__icon"
                                                                :icon="$subMenu['icon']" />
                                                            <div class="side-menu__link__title">
                                                                {{ $subMenu['title'] }}
                                                            </div>
                                                            @if (isset($subMenu['badge']))
                                                                <div class="side-menu__link__badge">
                                                                    {{ $subMenu['badge'] }}
                                                                </div>
                                                            @endif
                                                            @if (isset($subMenu['sub_menu']))
                                                                <x-base.lucide class="side-menu__link__chevron"
                                                                    icon="ChevronDown" />
                                                            @endif
                                                        </a>
                                                        <!-- BEGIN: Third Child -->
                                                        @if (isset($subMenu['sub_menu']))
                                                            <ul
                                                                class="{{ $secondLevelActiveIndex == $subMenuKey ? 'block' : 'hidden' }}">
                                                                >
                                                                @foreach ($subMenu['sub_menu'] as $lastSubMenuKey => $lastSubMenu)
                                                                    <li>
                                                                        <a href="{{ isset($lastSubMenu['route_name']) && Route::has($lastSubMenu['route_name']) ? route($lastSubMenu['route_name'], $lastSubMenu['params']) : 'javascript:;' }}"
                                                                            @class([
                                                                                'side-menu__link',
                                                                                $firstLevelActiveIndex == $menuKey &&
                                                                                $secondLevelActiveIndex == $subMenuKey &&
                                                                                $thirdLevelActiveIndex == $lastSubMenuKey
                                                                                    ? 'side-menu__link--active'
                                                                                    : '',
                                                                                $thirdLevelActiveIndex == $lastSubMenuKey && isset($lastSubMenu['sub_menu'])
                                                                                    ? 'side-menu__link--active-dropdown'
                                                                                    : '',
                                                                            ])>
                                                                            <x-base.lucide class="side-menu__link__icon"
                                                                                :icon="$lastSubMenu['icon']" />
                                                                            <div class="side-menu__link__title">
                                                                                {{ $lastSubMenu['title'] }}
                                                                            </div>
                                                                            @if (isset($lastSubMenu['badge']))
                                                                                <div class="side-menu__link__badge">
                                                                                    {{ $lastSubMenu['title'] }}
                                                                                </div>
                                                                            @endif
                                                                        </a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                        <!-- END: Third Child -->
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        <!-- END: Second Child -->
                                    </li>
                                @endif
                            @endforeach
                            <!-- END: First Child -->
                        </ul>
                    </div>
                </div>
                <div @class([
                    'fixed h-[65px] transition-[margin] duration-100 xl:ml-[275px] group-[.side-menu--collapsed]:xl:ml-[90px] mt-3.5 inset-x-0 top-0',
                    "before:content-[''] before:mx-5 before:absolute before:top-0 before:inset-x-0 before:-mt-[15px] before:h-[20px] before:backdrop-blur",
                ])>
                    <div
                        class="box absolute inset-x-0 mx-5 h-full before:absolute before:inset-x-4 before:top-0 before:z-[-1] before:mx-auto before:mt-3 before:h-full before:rounded-lg before:border before:border-slate-200 before:bg-slate-50 before:shadow-sm before:content-[''] before:dark:border-darkmode-500/60 before:dark:bg-darkmode-600/70">
                        <div class="flex h-full w-full items-center px-5">
                            <div class="flex items-center gap-1 xl:hidden">
                                <a class="open-mobile-menu rounded-full p-2 hover:bg-slate-100" href="">
                                    <x-base.lucide class="h-[18px] w-[18px]" icon="AlignJustify" />
                                </a>
                                <a class="rounded-full p-2 hover:bg-slate-100" data-tw-toggle="modal"
                                    data-tw-target="#quick-search" href="" href="javascript:;">
                                    <x-base.lucide class="h-[18px] w-[18px]" icon="Search" />
                                </a>
                            </div>
                            <!-- BEGIN: Breadcrumb -->
                            {{-- <x-base.breadcrumb class="hidden flex-1 xl:block">
                                <x-base.breadcrumb.link :index="0">App</x-base.breadcrumb.link>
                                <x-base.breadcrumb.link :index="1">Dashboards</x-base.breadcrumb.link>
                                <x-base.breadcrumb.link :index="2" :active="true">
                                    EFCTS
                                </x-base.breadcrumb.link>
                            </x-base.breadcrumb> --}}

                            <x-base.breadcrumb class="hidden flex-1 xl:block" :links="$breadcrumbLinks ?? []" />


                            <!-- END: Breadcrumb -->
                            <!-- BEGIN: Search -->
                            <div class="relative hidden flex-1 justify-center xl:flex" data-tw-toggle="modal"
                                data-tw-target="#quick-search">
                                <div
                                    class="flex w-[350px] cursor-pointer items-center rounded-[0.5rem] border bg-slate-50 px-3.5 py-2 text-slate-400 transition-colors hover:bg-slate-100">
                                    <x-base.lucide class="h-[18px] w-[18px]" icon="Search" />
                                    <div class="ml-2.5 mr-auto">Quick search...</div>
                                    <div>⌘K</div>
                                </div>
                            </div>
                            <x-quick-search />
                            <!-- END: Search -->
                            <!-- BEGIN: Notification & User Menu -->
                            <div class="flex flex-1 items-center">
                                <div class="ml-auto flex items-center gap-1">
                                    <a class="rounded-full p-2 hover:bg-slate-100" data-tw-toggle="modal"
                                        data-tw-target="#activities-panel" href="javascript:;">
                                        <x-base.lucide class="h-[18px] w-[18px]" icon="LayoutGrid" />
                                    </a>
                                    <a class="request-full-screen rounded-full p-2 hover:bg-slate-100" href="javascript:;">
                                        <x-base.lucide class="h-[18px] w-[18px]" icon="Expand" />
                                    </a>
                                    <a class="rounded-full p-2 hover:bg-slate-100 relative" data-tw-toggle="modal" data-tw-target="#notifications-panel" href="javascript:;">
                                        <x-base.lucide class="h-[18px] w-[18px]" icon="Bell" />
                                        @livewire('notification.notification-counter')
                                    </a>
                                </div>
                                <x-base.menu class="ml-5">
                                    <x-base.menu.button
                                        class="image-fit h-[36px] w-[36px] overflow-hidden rounded-full border-[3px] border-slate-200/70">
                                        @if(auth()->user()->profile_photo_url && auth()->user()->profile_photo_url !== asset('build/default_profile.png'))
                                            <img src="{{ auth()->user()->profile_photo_url }}"
                                                alt="{{ auth()->user()->name }}"
                                                class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-slate-200">
                                                <x-base.lucide class="h-5 w-5 text-slate-500" icon="User" />
                                            </div>
                                        @endif
                                    </x-base.menu.button>
                                    <x-base.menu.items class="mt-1 w-56">
                                        {{-- User Info Header --}}
                                        <div class="px-3 py-2 border-b border-slate-200/60">
                                            <div class="font-medium text-slate-800 truncate">{{ auth()->user()->name }}</div>
                                            <div class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</div>
                                            @if($isCarrier && $carrierData)
                                                <div class="mt-1 inline-flex items-center px-2 py-0.5 bg-primary/10 text-primary text-xs rounded-full">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="Building2" />
                                                    {{ Str::limit($carrierName, 15) }}
                                                </div>
                                            @elseif($isDriver)
                                                <div class="mt-1 inline-flex items-center px-2 py-0.5 bg-info/10 text-info text-xs rounded-full">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="Truck" />
                                                    Driver
                                                </div>
                                            @elseif($isAdmin)
                                                <div class="mt-1 inline-flex items-center px-2 py-0.5 bg-success/10 text-success text-xs rounded-full">
                                                    <x-base.lucide class="w-3 h-3 mr-1" icon="Shield" />
                                                    Administrator
                                                </div>
                                            @endif
                                        </div>
                                        
                                        {{-- Menu Items Based on Role --}}
                                        @if($isAdmin)
                                            {{-- Admin Menu --}}
                                            <x-base.menu.item href="{{ route('admin.settings') }}">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="User" />
                                                Profile Info
                                            </x-base.menu.item>
                                            <x-base.menu.item href="{{ route('admin.settings', ['page' => 'email-settings']) }}">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="Mail" />
                                                Email Settings
                                            </x-base.menu.item>
                                            <x-base.menu.item href="{{ route('admin.settings', ['page' => 'security']) }}">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="Lock" />
                                                Security
                                            </x-base.menu.item>
                                            <x-base.menu.divider />
                                            @if(Route::has('admin.roles.index'))
                                            <x-base.menu.item href="{{ route('admin.roles.index') }}">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="ShieldCheck" />
                                                Roles & Permissions
                                            </x-base.menu.item>
                                            @endif
                                        @elseif($isCarrier)
                                            {{-- Carrier Menu --}}
                                            <x-base.menu.item href="{{ route('carrier.profile') }}">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="Building2" />
                                                Company Profile
                                            </x-base.menu.item>
                                            <x-base.menu.item href="{{ route('carrier.profile.edit') }}">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="Settings" />
                                                Edit Profile
                                            </x-base.menu.item>
                                            @if($carrierData)
                                            <x-base.menu.item href="{{ route('carrier.documents.index', $carrierData->slug) }}">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="FileText" />
                                                Documents
                                            </x-base.menu.item>
                                            @endif
                                            <x-base.menu.divider />
                                            <x-base.menu.item href="{{ route('carrier.reports.index') }}">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="BarChart3" />
                                                Reports
                                            </x-base.menu.item>
                        @elseif($isDriver)
                            {{-- Driver Menu --}}
                            <x-base.menu.item href="{{ route('driver.profile') }}">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="User" />
                                My Profile
                            </x-base.menu.item>
                            <x-base.menu.item href="{{ route('driver.profile.edit') }}">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="Settings" />
                                Edit Profile
                            </x-base.menu.item>
                            <x-base.menu.divider />
                            <x-base.menu.item href="{{ route('driver.documents.index') }}">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="FileText" />
                                My Documents
                            </x-base.menu.item>
                            <x-base.menu.item href="{{ route('driver.vehicles.index') }}">
                                <x-base.lucide class="mr-2 h-4 w-4" icon="Truck" />
                                My Vehicle
                            </x-base.menu.item>
                                        @else
                                            {{-- Default Menu for other roles --}}
                                            <x-base.menu.item href="javascript:;">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="User" />
                                                Profile
                                            </x-base.menu.item>
                                        @endif
                                        
                                        <x-base.menu.divider />
                                        <form method="POST" action="{{ route('logout') }}" x-data>
                                            @csrf
                                            <x-base.menu.item href="{{ route('logout') }}" @click.prevent="$root.submit();" class="text-danger">
                                                <x-base.lucide class="mr-2 h-4 w-4" icon="Power" />
                                                Logout
                                            </x-base.menu.item>
                                        </form>
                                    </x-base.menu.items>
                                </x-base.menu>
                            </div>
                            <x-activities-panel />
                            <x-base.dialog id="notifications-panel" size="lg">
                                <x-base.dialog.panel>
                                    @livewire('notification.notifications-panel')
                                </x-base.dialog.panel>
                            </x-base.dialog>
                            <x-switch-account />
                            <!-- END: Notification & User Menu -->
                        </div>
                    </div>
                </div>
            </div>
            <div @class([
                'content transition-[margin,width] duration-100 px-0 md:px-5 pt-[56px] pb-16 relative z-20',
                'content--compact',
                'xl:ml-[275px]',
                '[&.content--compact]:xl:ml-[91px]',
            ])>
                <div class="container mt-[65px]">
                    @yield('subcontent')
                </div>
            </div>
        </div>
    </div>
@endsection



@pushOnce('styles')
    @vite('resources/css/vendors/simplebar.css')
    @vite('resources/css/themes/raze.css')
@endPushOnce

@pushOnce('vendors')
    @vite('resources/js/vendors/simplebar.js')
@endPushOnce

@pushOnce('scripts')
    @vite('resources/js/themes/raze.js')
@endPushOnce