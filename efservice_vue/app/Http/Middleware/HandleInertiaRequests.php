<?php

namespace App\Http\Middleware;

use App\Main\SideMenu;
use App\Main\CarrierSideMenu;
use App\Main\DriverSideMenu;
use App\Services\BrandingService;
use App\Services\NotificationCenterService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $notificationCenter = app(NotificationCenterService::class);
        $branding = app(BrandingService::class)->getSharedData();

        return [
            ...parent::share($request),
            'name' => $branding['appName'] ?? config('app.name'),
            'branding' => $branding,
            'auth' => [
                'user' => $user ? $this->getUserData($user) : null,
            ],
            'ziggy' => fn () => [
                ...(new \Tighten\Ziggy\Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sideMenu' => fn () => $user ? $this->getSideMenu($user) : [],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info'    => fn () => $request->session()->get('info'),
            ],
            'notifications' => fn () => $user
                ? $notificationCenter->getDropdownNotificationsForUser($user, 10)
                : [],
            'unreadNotificationsCount' => fn () => $user
                ? $notificationCenter->getUnreadCount($user)
                : 0,
        ];
    }

    protected function getUserData($user): array
    {
        $user->loadMissing('roles:id,name', 'permissions:id,name', 'carrierDetails.carrier:id,name');

        $carrier = $user->carrierDetails?->carrier;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->profile_photo_url,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $user->permissions->pluck('name')->toArray(),
            'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'carrier_name' => $carrier?->name,
            'carrier_logo_url' => $carrier?->logo_url,
        ];
    }

    protected function getSideMenu($user): array
    {
        if ($user->hasRole('superadmin')) {
            return $this->filterAvailableRoutes(SideMenu::menu());
        }

        if ($user->hasRole('user_carrier')) {
            return $this->filterAvailableRoutes(CarrierSideMenu::menu());
        }

        if ($user->hasRole('user_driver')) {
            return $this->filterAvailableRoutes(DriverSideMenu::menu());
        }

        return [];
    }

    protected function filterAvailableRoutes(array $menuItems): array
    {
        $filtered = [];

        foreach ($menuItems as $item) {
            if (is_string($item)) {
                $filtered[] = $item;
                continue;
            }

            $routeName = $item['route_name'] ?? '#';

            $hasValidRoute = $routeName === '#' || $this->routeExists($routeName);

            if (isset($item['sub_menu'])) {
                $validSubItems = [];
                foreach ($item['sub_menu'] as $subItem) {
                    $subRoute = $subItem['route_name'] ?? '#';
                    if ($subRoute === '#' || $this->routeExists($subRoute)) {
                        $validSubItems[] = $subItem;
                    }
                }

                if (!empty($validSubItems)) {
                    $item['sub_menu'] = $validSubItems;
                    $filtered[] = $item;
                } elseif ($hasValidRoute) {
                    unset($item['sub_menu']);
                    $filtered[] = $item;
                }
            } else {
                if ($hasValidRoute) {
                    $filtered[] = $item;
                }
            }
        }

        return $filtered;
    }

    protected function routeExists(string $name): bool
    {
        try {
            app('router')->getRoutes()->getByName($name);
            return app('router')->getRoutes()->getByName($name) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
}
