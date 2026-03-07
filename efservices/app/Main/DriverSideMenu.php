<?php

namespace App\Main;
use Illuminate\Support\Facades\Auth;

class DriverSideMenu
{
    public static function menu()
    {
        return [
            "DASHBOARD",
            [
                'icon' => "LayoutDashboard",
                'route_name' => "driver.dashboard",
                'params' => [],
                'title' => "Dashboard",
            ],

            "INFORMATION",
            [
                'icon' => "User",
                'route_name' => "driver.profile",
                'params' => [],
                'title' => "My Profile",
            ],
            [
                'icon' => "CreditCard",
                'route_name' => "driver.licenses.index",
                'params' => [],
                'title' => "My licenses",
            ],
            [
                'icon' => "Heart",
                'route_name' => "driver.medical.index",
                'params' => [],
                'title' => "Medical Record",
            ],
            [
                'icon' => "Truck",
                'route_name' => "driver.vehicles.index",
                'params' => [],
                'title' => "Vehicles",
            ],
            [
                'icon' => "Wrench",
                'route_name' => "driver.maintenance.index",
                'params' => [],
                'title' => "Vehicle Maintenance",
            ],
            [
                'icon' => "AlertTriangle",
                'route_name' => "driver.emergency-repairs.index",
                'params' => [],
                'title' => "Repairs",
            ],

            "TRAININGS",
            [
                'icon' => "GraduationCap",
                'route_name' => "driver.trainings.index",
                'params' => [],
                'title' => "My trainings",
            ],

            "TRIPS",
            [
                'icon' => "MapPin",
                'route_name' => "driver.trips.index",
                'params' => [],
                'title' => "My Trips",
            ],

            "HOURS OF SERVICE",
            [
                'icon' => "Clock",
                'route_name' => "driver.hos.dashboard",
                'params' => [],
                'title' => "HOS Dashboard",
            ],
            [
                'icon' => "History",
                'route_name' => "driver.hos.history",
                'params' => [],
                'title' => "HOS History",
            ],
            [
                'icon' => "Settings",
                'route_name' => "driver.hos.cycle.index",
                'params' => [],
                'title' => "Cycle Settings",
            ],

            "HISTORY",
            [
                'icon' => "TestTube",
                'route_name' => "driver.testing.index",
                'params' => [],
                'title' => "Tests",
            ],
            [
                'icon' => "Search",
                'route_name' => "driver.inspections.index",
                'params' => [],
                'title' => "Inspections",
            ],

            "MESSAGES",
            [
                'icon' => "Mail",
                'route_name' => "driver.messages.index",
                'params' => [],
                'title' => "My Messages",
            ],

            "NOTIFICATIONS",
            [
                'icon' => "Bell",
                'route_name' => "driver.notifications.index",
                'params' => [],
                'title' => "Notifications",
            ],

            "DOCUMENTS",
            [
                'icon' => "FileText",
                'route_name' => "driver.documents.index",
                'params' => [],
                'title' => "My Documents",
            ],
        ];
    }
}
